<?php

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_loki_system_instruction'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'markAsCopy' => 'title',
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
        ],
    ],
    'palettes' => [
        'default' => '{title_legend},title;{ai_legend},systemInstructionPrompt;{publish_legend},published',
    ],
    'fields' => [
        'id' => [
        ],
        'tstamp' => [
        ],
        'title' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'markAsCopy' => true, 'tl_class' => 'w50'],
        ],
        'systemInstructionPrompt' => [
            'inputType' => 'textarea',
            'eval' => ['decodeEntities' => true],
        ],
        'published' => [
            'inputType' => 'checkbox',
            'toggle' => true,
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
    ],
];