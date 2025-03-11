<?php

$GLOBALS['TL_DCA']['tl_loki_prompt'] = [
    'config' => [
        'dataContainer' => \Contao\DC_Table::class,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => \Contao\DataContainer::MODE_SORTED,
            'flag' => \Contao\DataContainer::SORT_INITIAL_LETTER_ASC,
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
                'icon' => 'edit',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{title_legend},title;{config_legend},fields;{ai_legend},prompt,model,maxTokens,temperature',
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
            'eval' => ['includeBlankOption' => true, 'submitOnChange' => true, 'mandatory' => true],
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
            'inputType' => 'select',
            'eval' => ['includeBlankOption' => true, 'blankOptionLabel' => 'Default: '.\Contao\System::getContainer()->getParameter('loki_ai.open_ai.model')],
        ],
        'maxTokens' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'placeholder' => \Contao\System::getContainer()->getParameter('loki_ai.open_ai.max_tokens')],
            'sql' => [
                'type' => 'integer',
                'notnull' => false,
            ],
        ],
        'temperature' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'digit', 'minval' => 0, 'maxval' => 2, 'placeholder' => \Contao\System::getContainer()->getParameter('loki_ai.open_ai.temperature')],
            'sql' => [
                'type' => 'float',
                'notnull' => false,
            ],
        ],
    ],
];