<?php

declare(strict_types=1);

/**
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2025, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

use Contao\System;
use Contao\DC_Table;
use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_loki_prompt'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'fields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label' => [
            'fields' => ['title'],
            'format' => '%s',
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'icon' => 'all',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'duplicate' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if (!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\')) return false; Backend.getScrollOffset();"',
            ],
            'toggle' => [
                'href' => 'act=toggle&field=published',
                'icon' => 'visible.svg',
            ],
            'run' => [
                'href' => 'act=run',
                'icon' => 'sync.svg',
                'attributes' => 'onclick="if (!confirm(\''.($GLOBALS['TL_LANG']['tl_loki_prompt']['runConfirm'] ?? null).'\')) return false; Backend.getScrollOffset();" data-turbo="false"',
            ]
        ],
    ],
    'palettes' => [
        '__selector__' => ['protected'],
        'default' => '{title_legend},title;{config_legend},fields;{ai_legend},prompt,model,maxTokens,temperature;{publish_legend},published,autoRun,protected',
    ],
    'subpalettes' => [
        'protected' => 'userGroups',
    ],
    'fields' => [
        'id' => [
        ],
        'tstamp' => [
        ],
        'title' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true],
        ],
        'tableName' => [
            'inputType' => 'select',
            'eval' => ['includeBlankOption' => true, 'submitOnChange' => true, 'mandatory' => true, 'chosen' => true],
        ],
        'field' => [
            'inputType' => 'checkbox',
            'eval' => ['multiple' => true],
        ],
        'includeFields' => [
            'inputType' => 'checkbox',
            'eval' => ['multiple' => true],
        ],
        'fields' => [
            'inputType' => 'group',
            'storage' => 'entity',
            'palette' => ['tableName', 'field', 'includeFields'],
        ],
        'prompt' => [
            'inputType' => 'textarea',
            'eval' => ['decodeEntities' => true],
        ],
        'model' => [
            'exclude' => true,
            'inputType' => 'select',
            'eval' => ['includeBlankOption' => true, 'blankOptionLabel' => 'Default: '.System::getContainer()->getParameter('loki_ai.open_ai.model')],
        ],
        'maxTokens' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'placeholder' => System::getContainer()->getParameter('loki_ai.open_ai.max_tokens')],
            'sql' => [
                'type' => 'integer',
                'notnull' => false,
            ],
        ],
        'temperature' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'digit', 'minval' => 0, 'maxval' => 2, 'placeholder' => System::getContainer()->getParameter('loki_ai.open_ai.temperature')],
            'sql' => [
                'type' => 'float',
                'notnull' => false,
            ],
        ],
        'published' => [
            'inputType' => 'checkbox',
            'toggle' => true,
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'autoRun' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'protected' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'userGroups' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'foreignKey' => 'tl_user_group.name',
            'eval' => ['tl_class' => 'clr', 'multiple' => true],
        ],
        'rootPage' => [
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => ['fieldType' => 'radio'],
            'relation' => [
                'type' => 'hasOne',
                'load' => 'lazy',
            ],
            'sql' => [
                'type' => 'integer',
                'default' => 0,
            ],
        ],
    ],
];
