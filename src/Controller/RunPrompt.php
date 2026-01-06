<?php

declare(strict_types=1);

/*
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\Controller;

use Contao\Backend;
use Contao\BackendUser;
use Contao\CoreBundle\Controller\AbstractBackendController;
use Contao\Message;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Plenta\LokiAiBundle\OpenAi\Api;
use Plenta\LokiAiBundle\Prompt\PromptBuilder;
use Plenta\LokiAiBundle\Repository\FieldRepository;
use Plenta\LokiAiBundle\Repository\PromptRepository;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('%contao.backend.route_prefix%/_loki', defaults: ['_scope' => 'backend'])]
class RunPrompt extends AbstractBackendController
{
    #[Route('/run/{id}', name: 'loki_run_prompt')]
    public function doRunPrompt(
        int $id,
        PromptRepository $promptRepository,
        TokenStorageInterface $tokenStorage,
        PromptBuilder $promptBuilder,
        Connection $connection,
        Packages $packages,
        TranslatorInterface $translator,
    ): Response {
        $prompt = $promptRepository->find($id);

        if (!$prompt->isPublished()) {
            Backend::redirect(Backend::getReferer());
        }

        if ($prompt->isProtected()) {
            /** @var BackendUser $user */
            $user = $tokenStorage->getToken()->getUser();

            if (!$user->isAdmin) {
                $groups = StringUtil::deserialize($prompt->getUserGroups());
                $isAllowed = false;

                foreach ($user->groups as $group) {
                    if (\in_array($group, $groups, true)) {
                        $isAllowed = true;
                    }
                }

                if (!$isAllowed) {
                    Backend::redirect(Backend::getReferer());
                }
            }
        }

        $GLOBALS['TL_JAVASCRIPT']['lokiBackend'] = $packages->getUrl('lokiai/backend.js', 'lokiai');
        $GLOBALS['TL_CSS']['lokiBackend'] = $packages->getUrl('lokiai/backend.css', 'lokiai');

        $fields = $prompt->getFields();
        $fieldArr = [];

        foreach ($fields as $field) {
            $affectedFields = StringUtil::deserialize($field->getField(), true);

            if ('tl_page' === $field->getTableName() && $prompt->getRootPage()) {
                $ids = $promptBuilder->getPages($field);
            } elseif ('tl_content' === $field->getTableName() && $prompt->getRootPage()) {
                $ids = $promptBuilder->getContentElements($field);
            } else {
                $ids = $connection->createQueryBuilder()
                    ->select('t.id')
                    ->from($field->getTableName(), 't')
                    ->executeQuery()
                    ->fetchFirstColumn()
                ;
            }

            foreach ($affectedFields as $affectedField) {
                foreach ($ids as $id) {
                    $fieldArr[] = [
                        'fieldName' => $affectedField,
                        'field' => $field->getId(),
                        'id' => $id,
                    ];
                }
            }
        }

        if (empty($fieldArr)) {
            Message::addInfo('Keine Felder gefunden.');
            Backend::redirect(Backend::getReferer());
        }

        return $this->render('@Contao/backend/run_prompt.html.twig', [
            'fields' => $fieldArr,
            'headline' => $translator->trans('loki.title', [':title' => $prompt->getTitle()], 'loki'),
            'title' => $translator->trans('loki.title', [':title' => $prompt->getTitle()], 'loki'),
        ]);
    }

    #[Route('/execute/{id}/{fieldName}/{objectId}', name: 'loki_execute_prompt')]
    public function executePrompt(
        int $id,
        string $fieldName,
        int $objectId,
        PromptBuilder $promptBuilder,
        FieldRepository $fieldRepository,
        Api $api,
        Connection $connection,
    ): JsonResponse {
        $field = $fieldRepository->find($id);

        $htmlSanitizer = new HtmlSanitizer(
            (new HtmlSanitizerConfig())->allowSafeElements(),
        );

        try {
            $prompt = $promptBuilder->build($field, $objectId, $fieldName);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $htmlSanitizer->sanitizeFor('dialog', $e->getMessage())]);
        }

        if (empty($prompt)) {
            return new JsonResponse(['warning' => 'empty']);
        }

        try {
            $newValue = $promptBuilder->buildHeadline($api->chat($prompt, $field->getParent()->getModel(), $field->getParent()->getTemperature(), $field->getParent()->getMaxTokens()), $objectId, $field, $fieldName);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $htmlSanitizer->sanitizeFor('dialog', $e->getMessage())]);
        }

        $connection->createQueryBuilder()
            ->update($field->getTableName())
            ->set($fieldName, ':value')
            ->where('id = :id')
            ->setParameter('value', $newValue)
            ->setParameter('id', $objectId)
            ->executeQuery()
        ;

        return new JsonResponse(['success' => true]);
    }
}
