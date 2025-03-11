<?php

declare(strict_types=1);

/**
 * Plenta Jobs Basic Geo Search Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2025, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\EventListener\Contao\DCA;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Plenta\LokiAiBundle\OpenAi\Api;
use Plenta\LokiAiBundle\Repository\FieldRepository;

class TlLokiPrompt
{
    public function __construct(
        protected Connection $connection,
        protected FieldRepository $fieldRepository,
        protected Api $api,
    ) {
    }

    #[AsCallback(table: 'tl_loki_prompt', target: 'fields.tableName.options')]
    public function getTableOptions()
    {
        $arrTables = Database::getInstance()->listTables();
        $arrViews = $this->connection->createSchemaManager()->listViews();

        if (!empty($arrViews)) {
            $arrTables = array_merge($arrTables, array_keys($arrViews));
            natsort($arrTables);
        }

        return array_values($arrTables);
    }

    #[AsCallback(table: 'tl_loki_prompt', target: 'fields.field.options')]
    #[AsCallback(table: 'tl_loki_prompt', target: 'fields.includeFields.options')]
    public function getFieldOptions(DataContainer $dc): array
    {
        $key = str_replace(['fields__field__', 'fields__includeFields__'], '', $dc->field);

        $field = $this->fieldRepository->find($key);
        $return = [];

        if ($field->getTableName()) {
            DataContainer::loadDataContainer($field->getTableName());
            DataContainer::loadLanguageFile($field->getTableName());


            foreach (($GLOBALS['TL_DCA'][$field->getTableName()]['fields'] ?? []) as $name => $dca) {
                if (empty($dca['inputType'])) {
                    continue;
                }

                if (str_contains($dc->field, 'fields__field__') && !in_array($dca['inputType'], ['text', 'textarea', 'checkbox', 'checkboxWizard', 'select'])) {
                    continue;
                }

                $return[$name] = $dca['label'][0] ?? $name;
            }
        }


        return $return;
    }

    #[AsCallback(table: 'tl_loki_prompt', target: 'fields.model.options')]
    public function getModelOptions()
    {
        $return = [];

        foreach ($this->api->getModels() as $model) {
            $return[$model->getName()] = $model->getName();
        }

        return $return;
    }
}
