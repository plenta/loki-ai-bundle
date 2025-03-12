<?php

namespace Plenta\LokiAiBundle\Prompt;

use Contao\Controller;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Plenta\LokiAiBundle\Entity\Field;
use Plenta\LokiAiBundle\Exception\PromptException;

class PromptBuilder
{
    public function __construct(
        protected Connection $connection,
        protected SimpleTokenParser $simpleTokenParser,
    ) {
    }

    public function build(?Field $field, int $objectId, string $fieldName)
    {
        if (null === $field) {
            throw new PromptException('Field entity not found');
        }

        $object = $this->connection->fetchAssociative('SELECT * FROM '.$field->getTableName().' WHERE id = :id', ['table' => $field->getTableName(), 'id' => $objectId]);

        if (empty($object)) {
            throw new PromptException('Object not found');
        }

        $includeFields = StringUtil::deserialize($field->getIncludeFields(), true);

        if (empty($includeFields)) {
            throw new PromptException('No base text found');
        }

        Controller::loadDataContainer($field->getTableName());

        if (count($includeFields) > 1) {
            $base = '';

            foreach ($includeFields as $includeField) {
                if (!empty($base)) {
                    $base .= '; ';
                }

                $options = $this->getOptions($GLOBALS['TL_DCA'][$field->getTableName()]['fields'][$includeField]);

                $value = $options[$object[$includeField]] ?? $object[$includeField];

                $base .= $includeField.': '.$value;
            }
        } else {
            $base = $object[$includeFields[0]];
        }

        $dca = $GLOBALS['TL_DCA'][$field->getTableName()]['fields'][$fieldName];

        $options = $this->getOptions($dca);

        $field_options = '';

        if (!empty($options)) {
            foreach ($options as $key => $option) {
                if (!empty($field_options)) {
                    $field_options .= '; ';
                }

                $field_options .= $option.' (Key: '.$key.')';
            }
        }

        return $this->simpleTokenParser->parse($field->getParent()->getPrompt(), ['include_fields' => $base, 'field_options' => $field_options]);
    }

    protected function getOptions($dca)
    {
        $options = $dca['options'] ?? [];

        if (empty($options) && !empty($dca['options_callback'])) {
            $callback = System::importStatic($dca['options_callback'][0]);

            $options = $callback->{$dca['options_callback'][1]}();
        }

        if (empty($options) && !empty($dca['foreignKey'])) {
            $options = [];

            [$table, $label] = explode('.', $dca['foreignKey']);

            $data = $this->connection->fetchAllAssociative('SELECT id, '.$label.' FROM '.$table);

            foreach ($data as $row) {
                $options[$row['id']] = $row[$label];
            }
        }

        return $options;
    }
}