<?php

declare(strict_types=1);

/**
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2025, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\Controller;

use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\String\SimpleTokenParser;
use Doctrine\DBAL\Connection;
use Plenta\LokiAiBundle\Exception\PromptException;
use Plenta\LokiAiBundle\OpenAi\Api;
use Plenta\LokiAiBundle\Prompt\PromptBuilder;
use Plenta\LokiAiBundle\Repository\FieldRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('%contao.backend.route_prefix%/_loki', defaults: ['_scope' => 'backend'])]
class GeneratePromptController extends AbstractController
{
    #[Route('/prompt/{id}/{fieldName}/{objectId}', name: 'loki_generate_prompt')]
    public function generatePrompt(
        int $id,
        string $fieldName,
        int $objectId,
        FieldRepository $fieldRepository,
        PromptBuilder $promptBuilder,
        Api $api
    ): JsonResponse {
        $field = $fieldRepository->find($id);

        try {
            $prompt = $promptBuilder->build($field, $objectId, $fieldName);
        } catch (PromptException $e) {
            return new JsonResponse(['error' => $e->getMessage()]);
        }

        $newValue = $api->chat($prompt, $field->getParent()->getModel(), $field->getParent()->getTemperature(), $field->getParent()->getMaxTokens());

        return new JsonResponse(['result' => $newValue]);
    }
}