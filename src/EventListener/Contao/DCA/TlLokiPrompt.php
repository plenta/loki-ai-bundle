<?php

declare(strict_types=1);

/**
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2025, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\EventListener\Contao\DCA;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Plenta\LokiAiBundle\OpenAi\Api;
use Plenta\LokiAiBundle\Repository\FieldRepository;
use Plenta\LokiAiBundle\Repository\PromptRepository;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TlLokiPrompt
{
    public function __construct(
        protected Connection $connection,
        protected FieldRepository $fieldRepository,
        protected Api $api,
        protected PromptRepository $promptRepository,
        protected RouterInterface $router,
        protected TokenStorageInterface $tokenStorage,
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

        $arrTables = array_filter($arrTables, function ($table) {
            DataContainer::loadDataContainer($table);

            if ($GLOBALS['TL_DCA'][$table] ?? null) {
                return true;
            }

            return false;
        });

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
            DataContainer::loadLanguageFile($field->getTableName());

            foreach (($GLOBALS['TL_DCA'][$field->getTableName()]['fields'] ?? []) as $name => $dca) {
                if (empty($dca['inputType'])) {
                    continue;
                }

                if (str_contains($dc->field, 'fields__field__') && !\in_array($dca['inputType'], ['text', 'textarea', 'checkbox', 'checkboxWizard', 'select', 'inputUnit'], true)) {
                    continue;
                }

                $return[$name] = (($dca['label'][0] ?? '')).'<span class="label-info">['.$name.']</span>';
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

    #[AsCallback(table: 'tl_loki_prompt', target: 'config.onload')]
    public function onLoad(?DataContainer $dc): void
    {
        if (!$dc || !$dc->id) {
            return;
        }

        $prompt = $this->promptRepository->find($dc->id);
        $fields = $prompt->getFields();

        foreach ($fields as $field) {
            if ('tl_page' === $field->getTableName() || 'tl_content' === $field->getTableName()) {
                PaletteManipulator::create()
                    ->addField('rootPage', 'config_legend', PaletteManipulator::POSITION_APPEND)
                    ->applyToPalette('default', 'tl_loki_prompt')
                ;

                break;
            }
        }
    }

    #[AsCallback(table: 'tl_loki_prompt', target: 'list.operations.run.button_callback')]
    public function onRunButtonCallback(
        array $row,
        ?string $href,
        string $label,
        string $title,
        ?string $icon,
        string $attributes,
        string $table,
        array $rootRecordIds,
        ?array $childRecordIds,
        bool $circularReference,
        ?string $previous,
        ?string $next,
        DataContainer $dc
    ): string {
        if (!$row['published']) {
            return '';
        }

        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user->isAdmin && $row['protected']) {
            $groups = StringUtil::deserialize($row['userGroups']);
            $isAllowed = false;

            foreach ($user->groups as $group) {
                if (in_array($group, $groups)) {
                    $isAllowed = true;
                }
            }

            if (!$isAllowed) {
                return '';
            }
        }

        $href = $this->router->generate('loki_run_prompt', ['id' => $row['id']]);

        return sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            $href,
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon, $label)
        );
    }
}
