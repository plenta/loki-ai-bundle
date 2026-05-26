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

use Contao\BackendUser;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Message;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Plenta\LokiAiBundle\AiProvider\LokiAiGateway;
use Plenta\LokiAiBundle\Repository\FieldRepository;
use Plenta\LokiAiBundle\Repository\PromptRepository;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TlLokiPrompt
{
    public function __construct(
        protected Connection $connection,
        protected FieldRepository $fieldRepository,
        protected LokiAiGateway $gateway,
        protected PromptRepository $promptRepository,
        protected RouterInterface $router,
        protected TokenStorageInterface $tokenStorage,
    ) {
    }

    /**
     * @return array<string>
     */
    #[AsCallback(table: 'tl_loki_prompt', target: 'fields.tableName.options')]
    public function getTableOptions(): array
    {
        $arrTables = Database::getInstance()->listTables();
        $arrViews = $this->connection->createSchemaManager()->listViews();

        if (!empty($arrViews)) {
            $arrTables = array_merge($arrTables, array_keys($arrViews));
            natsort($arrTables);
        }

        $arrTables = array_filter(
            $arrTables,
            static function ($table) {
                DataContainer::loadDataContainer($table);

                if ($GLOBALS['TL_DCA'][$table] ?? null) {
                    return true;
                }

                return false;
            },
        );

        return array_values($arrTables);
    }

    /**
     * @return array<string, string>
     */
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

                $return[$name] = ($dca['label'][0] ?? '').'<span class="label-info">['.$name.']</span>';
            }
        }

        return $return;
    }

    /**
     * @return array<string, string>
     */
    #[AsCallback(table: 'tl_loki_prompt', target: 'fields.provider.options')]
    public function getProviderOptions(): array
    {
        $options = [];

        foreach ($this->gateway->getProviders() as $name => $provider) {
            if ($provider->isConfigured()) {
                $options[$name] = $provider->getLabel();
            }
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    #[AsCallback(table: 'tl_loki_prompt', target: 'fields.model.options')]
    public function getModelOptions(DataContainer $dc): array
    {
        $providerName = $dc->activeRecord?->provider ?: 'openai';

        try {
            return $this->gateway->getProvider($providerName)->getAvailableModels();
        } catch (\Throwable) {
            return [];
        }
    }

    #[AsCallback(table: 'tl_loki_prompt', target: 'config.onload')]
    public function onLoad(DataContainer|null $dc): void
    {
        // Show a backend notice when no AI provider has a configured API key so that
        // editors know where to look rather than facing an empty select field silently.
        $hasConfigured = false;

        foreach ($this->gateway->getProviders() as $provider) {
            if ($provider->isConfigured()) {
                $hasConfigured = true;
                break;
            }
        }

        if (!$hasConfigured) {
            Message::addInfo(
                ($GLOBALS['TL_LANG']['tl_loki_prompt']['noProviderConfigured'] ?? null)
                    ?? 'Kein KI-Anbieter konfiguriert. Bitte hinterlegen Sie mindestens einen API-Schlüssel (z.&nbsp;B. <code>OPENAI_API_KEY</code> oder <code>ANTHROPIC_API_KEY</code>) in Ihrer <code>.env.local</code> und leeren Sie anschließend den Symfony-Cache (<code>php bin/console cache:clear</code>).',
            );
        }

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

    /**
     * @param array<string, mixed> $row
     * @param array<int>           $rootRecordIds
     * @param array<int>|null      $childRecordIds
     */
    #[AsCallback(table: 'tl_loki_prompt', target: 'list.operations.run.button_callback')]
    public function onRunButtonCallback(
        array $row,
        string|null $href,
        string $label,
        string $title,
        string|null $icon,
        string $attributes,
        string $table,
        array $rootRecordIds,
        array|null $childRecordIds,
        bool $circularReference,
        string|null $previous,
        string|null $next,
        DataContainer $dc,
    ): string {
        if (!$row['published']) {
            return '';
        }

        /** @var BackendUser $user */
        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user->isAdmin && $row['protected']) {
            $groups = StringUtil::deserialize($row['userGroups']);
            $isAllowed = false;

            foreach ($user->groups as $group) {
                if (\in_array($group, $groups, true)) {
                    $isAllowed = true;
                }
            }

            if (!$isAllowed) {
                return '';
            }
        }

        $href = $this->router->generate('loki_run_prompt', ['id' => $row['id']]);

        return \sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            $href,
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon, $label),
        );
    }
}
