<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tx_taxonomy_domain_model_term',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'default_sortby' => 'vocabulary ASC, parent ASC, sorting ASC, title ASC',
        'iconfile' => 'EXT:taxonomy/Resources/Public/Icons/Term.svg',
        'useColumnsForDefaultValues' => 'vocabulary',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'versioningWS' => true,
        'type' => 'vocabulary:name',
        // @todo Enable hideTable when the backend module is ready.
        //'hideTable' => true,
    ],
    'types' => [
        '0' => [
            'showitem' =>
                '--div--;core.form.tabs:general,
                    title, vocabulary, parent,
                --div--;LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tabs.items,
                    items,
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
            'label' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tx_taxonomy_domain_model_term.title',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'vocabulary' => [
            'label' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tx_taxonomy_domain_model_term.vocabulary',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'required' => true,
                'items' => [
                    [
                        'label' => '',
                        'value' => null,
                    ],
                ],
                'foreign_table' => 'tx_taxonomy_domain_model_vocabulary',
                // @todo: could be useful to display only vocabularies available for the current site.
                //   It is not as easy as it could be: database is not available in TCA building phase.
                //   One way of doing it is in ext_tables.php, but it will need a layer of caching to be efficient.
                'foreign_table_where' => 'AND {#tx_taxonomy_domain_model_vocabulary}.{#sys_language_uid} IN (-1,0)',
            ],
        ],
        'parent' => [
            'label' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tx_taxonomy_domain_model_term.parent',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'tx_taxonomy_domain_model_term',
                'foreign_table_where' => 'AND {#tx_taxonomy_domain_model_term}.{#pid} = ###CURRENT_PID### AND {#tx_taxonomy_domain_model_term}.{#sys_language_uid} IN (-1,0) AND {#tx_taxonomy_domain_model_term}.{#vocabulary} = ###REC_FIELD_vocabulary###',
                'size' => 20,
                'maxitems' => 1,
                'treeConfig' => [
                    'parentField' => 'parent',
                    'appearance' => [
                        'expandAll' => true,
                        'showHeader' => true,
                    ],
                ],
            ],
        ],
        'items' => [
            'label' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tx_taxonomy_domain_model_term.items',
            // https://forge.typo3.org/issues/90430 prevents us to use l10n_mode=exclude here
            // 'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'group',
                'readOnly' => true,
                'allowed' => '*',
                'MM' => 'tx_taxonomy_domain_model_term_record_mm',
                'MM_oppositeUsage' => [],
                'size' => 10,
                'fieldWizard' => [
                    'recordsOverview' => [
                        'disabled' => true,
                    ],
                ],
            ],
        ],
    ],
];
