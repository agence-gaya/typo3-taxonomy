<?php

declare(strict_types=1);

use GAYA\Taxonomy\UserFunc\SlugUserFunc;

return [
    'ctrl' => [
        'title' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tx_gayataxonomy_domain_model_vocabulary',
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'title',
        'iconfile' => 'EXT:taxonomy/Resources/Public/Icons/Vocabulary.svg',
        'searchFields' => 'title, description',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'versioningWS' => true,
        'adminOnly' => true,
        // todo: activer le hideTable quand le module sera prêt
        //'hideTable' => true,
    ],
    'types' => [
        '1' => [
            'showitem' =>
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    title, slug, description,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden',
        ],
    ],
    'palettes' => [
        'hidden' => [
            'showitem' => '
                hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden
            ',
        ],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
                'readOnly' => true,
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => '',
                        'value' => 0
                    ],
                ],
                'foreign_table' => 'tx_gayataxonomy_domain_model_vocabulary',
                'foreign_table_where' => 'AND {#tx_gayataxonomy_domain_model_vocabulary}.{#pid}=###CURRENT_PID### AND {#tx_gayataxonomy_domain_model_vocabulary}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'title' => [
            'label' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tx_gayataxonomy_domain_model_vocabulary.title',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tx_gayataxonomy_domain_model_vocabulary.description',
            'exclude' => true,
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30,
            ],
        ],
        'slug' => [
            'label' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tx_gayataxonomy_domain_model_vocabulary.slug',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => [ 'title' ],
                    'postModifiers' => [
                        SlugUserFunc::class . '->normalize',
                    ],
                ],
                'appearance' => [
                    'prefix' => SlugUserFunc::class . '->noPrefix',
                ],
                'fallbackCharacter' => '_',
                'eval' => 'unique',
                'default' => '',
            ],
        ],
    ],
];