<?php

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

        if (empty($includeFields) && !str_contains($field->getParent()->getPrompt(), '##current_value##')) {
            throw new PromptException('No base text found');
        }

        Controller::loadDataContainer($field->getTableName());

        if ($GLOBALS['TL_DCA'][$field->getTableName()]['fields'][$fieldName]['inputType'] === 'inputUnit') {
            $currentValue = StringUtil::deserialize($object[$fieldName], true)['value'] ?? '';
        } else {
            $options = $this->getOptions($GLOBALS['TL_DCA'][$field->getTableName()]['fields'][$fieldName]);

            $currentValue = $options[$object[$fieldName]] ?? $object[$fieldName] ?? '';
        }

        if (count($includeFields) > 1) {
            $base = '';

            foreach ($includeFields as $includeField) {
                if (!empty($base)) {
                    $base .= '; ';
                }

                if ($GLOBALS['TL_DCA'][$field->getTableName()]['fields'][$includeField]['inputType'] === 'inputUnit') {
                    $value = StringUtil::deserialize($object[$includeField], true)['value'] ?? '';
                } else {
                    $options = $this->getOptions($GLOBALS['TL_DCA'][$field->getTableName()]['fields'][$includeField]);

                    $value = $options[$object[$includeField]] ?? $object[$includeField];
                }


                $base .= $includeField.': '.$value;
            }
        } else {
            $base = $object[$includeFields[0] ?? null] ?? null;
        }

        $dca = $GLOBALS['TL_DCA'][$field->getTableName()]['fields'][$fieldName] ?? null;

        if (!$dca) {
            throw new PromptException('Field '.$fieldName.' not found');
        }

        $affectedFields = StringUtil::deserialize($field->getField(), true);

        if (!in_array($fieldName, $affectedFields)) {
            throw new PromptException('Field '.$fieldName.' is not selected');
        }

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

        return StringUtil::decodeEntities($this->insertTagParser->replace($this->simpleTokenParser->parse($field->getParent()->getPrompt(), ['include_fields' => $base, 'field_options' => $field_options, 'current_value' => $currentValue])));
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

    public function getPages(Field $field): array
    {
        $return = [];

        if ($field->getTableName() === 'tl_page' || $field->getTableName() === 'tl_content') {
            if ($field->getParent()->getRootPage()) {
                $this->buildPages(PageModel::findByPk($field->getParent()->getRootPage()), $return);
            }
        }

        return $return;
    }

    public function getContentElements(Field $field)
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

    protected function buildPages($pageObj, &$pageIds)
    {
        $pages = PageModel::findPublishedByPid($pageObj->id);

        if ($pages) {
            foreach ($pages as $page) {
                $pageIds[] = $page->id;
                $this->buildPages($page, $pageIds);
            }
        }
    }

    public function buildHeadline($newValue, int $id, Field $field, string $fieldName)
    {
        if ($GLOBALS['TL_DCA'][$field->getTableName()]['fields'][$fieldName]['inputType'] === 'inputUnit') {
            $currentValue = StringUtil::deserialize($this->connection->fetchOne('SELECT '.$fieldName.' FROM '.$field->getTableName().' WHERE id = ?', [$id]), true);

            $currentValue['value'] = $newValue;

            $newValue = serialize($currentValue);
        }

        return $newValue;
    }
}