<?php

namespace Plenta\LokiAiBundle\Controller;

use Contao\Controller;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Plenta\LokiAiBundle\OpenAi\Api;
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
        Connection $connection,
        SimpleTokenParser $parser,
        Api $api
    ): JsonResponse {
        $field = $fieldRepository->find($id);

        if (null === $field) {
            return new JsonResponse(['error' => 'Field entity not found']);
        }

        $object = $connection->fetchAssociative('SELECT * FROM '.$field->getTableName().' WHERE id = :id', ['table' => $field->getTableName(), 'id' => $objectId]);

        if (empty($object)) {
            return new JsonResponse(['error' => 'Object not found']);
        }

        $this->initializeContaoFramework();

        $includeFields = StringUtil::deserialize($field->getIncludeFields(), true);

        if (empty($includeFields)) {
            return new JsonResponse(['error' => 'No base text found']);
        }

        if (count($includeFields) > 1) {
            $base = '';

            foreach ($includeFields as $includeField) {
                if (!empty($base)) {
                    $base .= '; ';
                }

                $base .= $includeField.': '.$object[$includeField];
            }
        } else {
            $base = $object[$includeFields[0]];
        }

        Controller::loadDataContainer($field->getTableName());

        $dca = $GLOBALS['TL_DCA'][$field->getTableName()]['fields'][$fieldName];

        $options = $dca['options'] ?? [];

        if (empty($options) && !empty($dca['options_callback'])) {
            $callback = System::importStatic($dca['options_callback'][0]);

            $options = $callback->{$dca['options_callback'][1]}();
        }

        $field_options = '';

        if (!empty($options)) {
            foreach ($options as $key => $option) {
                if (!empty($field_options)) {
                    $field_options .= '; ';
                }

                $field_options .= $option.' (Key: '.$key.')';
            }
        }

        $prompt = $parser->parse($field->getParent()->getPrompt(), ['include_fields' => $base, 'field_options' => $field_options]);

        $newValue = $api->chat($prompt, $field->getParent()->getModel(), $field->getParent()->getTemperature(), $field->getParent()->getMaxTokens());

        return new JsonResponse(['result' => $newValue]);
    }
}