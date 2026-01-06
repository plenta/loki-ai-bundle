<?php

declare(strict_types=1);

/**
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2025, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\Prompt;

use Contao\Controller;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\PageModel;
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
        protected InsertTagParser $insertTagParser,
    ) {
    }

    public function build(Field|null $field, int $objectId, string $fieldName): string
    {
        if (null === $field) {
            throw new PromptException('Field entity not found');
        }

        $object = $this->connection->fetchAssociative('SELECT * FROM '.$field->getTableName().' WHERE id = :id', ['table' => $field->getTableName(), 'id' => $objectId]);

        if (empty($object)) {
            throw new PromptException('Object not found');
        }

        $includeFields = StringUtil::deserialize($field->getIncludeFields(), true);

        if (empty($includeFields) && !str_contains($field->getParent()->getPrompt(), '##current_value##')) {
            throw new PromptException('No base text found');
        }

        Controller::loadDataContainer($field->getTableName());

        if ('inputUnit' === $GLOBALS['TL_DCA'][$field->getTableName()]['fields'][$fieldName]['inputType']) {
            $currentValue = StringUtil::deserialize($object[$fieldName], true)['value'] ?? '';
        } else {
            $options = $this->getOptions($GLOBALS['TL_DCA'][$field->getTableName()]['fields'][$fieldName]);

            $currentValue = $options[$object[$fieldName]] ?? $object[$fieldName] ?? '';
        }

        $fieldValues = [];

        if (\count($includeFields) > 1) {
            $base = '';
            $empty = true;

            foreach ($includeFields as $includeField) {
                if (!empty($base)) {
                    $base .= '; ';
                }

                if ('inputUnit' === $GLOBALS['TL_DCA'][$field->getTableName()]['fields'][$includeField]['inputType']) {
                    $value = StringUtil::deserialize($object[$includeField], true)['value'] ?? '';
                } else {
                    $options = $this->getOptions($GLOBALS['TL_DCA'][$field->getTableName()]['fields'][$includeField]);

                    $value = $options[$object[$includeField]] ?? $object[$includeField];
                }

                if (empty($value)) {
                    continue;
                }

                $empty = false;

                $base .= $includeField.': '.$value;
                $fieldValues[$includeField] = $value;
            }
        } else {
            $base = $object[$includeFields[0] ?? null] ?? null;

            if ($base) {
                $fieldValues[$includeFields[0] ?? null] = $base;
            }

            $empty = empty($base);
        }

        $dca = $GLOBALS['TL_DCA'][$field->getTableName()]['fields'][$fieldName] ?? null;

        if (!$dca) {
            throw new PromptException('Field '.$fieldName.' not found');
        }

        $affectedFields = StringUtil::deserialize($field->getField(), true);

        if (!\in_array($fieldName, $affectedFields, true)) {
            throw new PromptException('Field '.$fieldName.' is not selected');
        }

        $hasIncludeFields = str_contains($field->getParent()->getPrompt(), '##include_fields##');
        $hasCurrentValue = str_contains($field->getParent()->getPrompt(), '##current_value##');

        if ($field->getParent()->isSkipIfEmpty()) {
            if ($hasCurrentValue && $hasIncludeFields && $empty && empty($currentValue)) {
                return '';
            }

            if ($hasIncludeFields && $empty) {
                return '';
            }

            if ($hasCurrentValue && empty($currentValue)) {
                return '';
            }
        }

        $options = $this->getOptions($dca);

        $field_options = '';

        foreach ($options as $key => $option) {
            if (!empty($field_options)) {
                $field_options .= '; ';
            }

            $field_options .= $option.' (Key: '.$key.')';
        }

        $tokens = ['include_fields' => $base, 'field_options' => $field_options, 'current_value' => $currentValue];

        foreach ($fieldValues as $key => $value) {
            $tokens['field_'.$key] = $value;
        }

        return StringUtil::decodeEntities($this->insertTagParser->replace($this->simpleTokenParser->parse($field->getParent()->getPrompt(), $tokens)));
    }

    /**
     * @return array<string>
     */
    public function getPages(Field $field): array
    {
        $return = [];

        if ('tl_page' === $field->getTableName() || 'tl_content' === $field->getTableName()) {
            if ($field->getParent()->getRootPage()) {
                $this->buildPages(PageModel::findById($field->getParent()->getRootPage()), $return);
            }
        }

        return $return;
    }

    /**
     * @return array<int>
     */
    public function getContentElements(Field $field): array
    {
        $pages = $this->getPages($field);

        $qb = $this->connection->createQueryBuilder();

        return $qb
            ->select('c.id')
            ->from('tl_content', 'c')
            ->leftJoin('c', 'tl_article', 'a', 'c.pid=a.id and c.ptable = :article')
            ->where($qb->expr()->in('a.pid', $pages))
            ->setParameter('article', 'tl_article')
            ->executeQuery()
            ->fetchFirstColumn()
        ;
    }

    public function buildHeadline(string $newValue, int $id, Field $field, string $fieldName): string
    {
        if ('inputUnit' === $GLOBALS['TL_DCA'][$field->getTableName()]['fields'][$fieldName]['inputType']) {
            $currentValue = StringUtil::deserialize($this->connection->fetchOne('SELECT '.$fieldName.' FROM '.$field->getTableName().' WHERE id = ?', [$id]), true);

            $currentValue['value'] = $newValue;

            $newValue = serialize($currentValue);
        }

        return $newValue;
    }

    /**
     * @param  array<string, mixed> $dca
     * @return array<string, mixed>
     */
    protected function getOptions(array $dca): array
    {
        $options = $dca['options'] ?? [];

        if (empty($options) && !empty($dca['options_callback'])) {
            $callback = System::importStatic($dca['options_callback'][0]);

            $options = $callback->{$dca['options_callback'][1]}();
        }

        if (empty($options) && !empty($dca['foreignKey'])) {
            $options = [];

            [$table, $label] = explode('.', (string) $dca['foreignKey']);

            $data = $this->connection->fetchAllAssociative('SELECT id, '.$label.' FROM '.$table);

            foreach ($data as $row) {
                $options[$row['id']] = $row[$label];
            }
        }

        return $options;
    }

    /**
     * @param array<string> $pageIds
     */
    protected function buildPages(PageModel $pageObj, array &$pageIds): void
    {
        $pages = PageModel::findPublishedByPid($pageObj->id);

        if ($pages) {
            foreach ($pages as $page) {
                $pageIds[] = (string) $page->id;
                $this->buildPages($page, $pageIds);
            }
        }
    }
}
