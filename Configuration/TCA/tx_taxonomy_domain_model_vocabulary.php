<?php

declare(strict_types=1);

use GAYA\Taxonomy\UserFunc\SlugUserFunc;

return [
    'ctrl' => [
        'title' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tx_taxonomy_domain_model_vocabulary',
        'label' => 'title',
        'descriptionColumn' => 'rowDescription',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'title',
        'iconfile' => 'EXT:taxonomy/Resources/Public/Icons/Vocabulary.svg',
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
        '0' => [
            'showitem' =>
                '--div--;core.form.tabs:general,
                    title, name,
                --div--;core.form.tabs:notes,
                    rowDescription,
                --div--;core.form.tabs:access,
                    --palette--;;hidden',
        ],
    ],
    'palettes' => [
        'hidden' => [
            'showitem' => '
                hidden;frontend.db.tt_content:hidden
            ',
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tx_taxonomy_domain_model_vocabulary.title',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'name' => [
            'label' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tx_taxonomy_domain_model_vocabulary.name',
            'description' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tx_taxonomy_domain_model_vocabulary.name.description',
            'l10n_mode' => 'exclude',
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
                'searchable' => false,
            ],
        ],
        'rowDescription' => [
            'label' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tx_taxonomy_domain_model_vocabulary.rowDescription',
            'exclude' => true,
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30,
                'searchable' => false,
            ],
        ],
    ],
];
