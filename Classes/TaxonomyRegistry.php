<?php

declare(strict_types=1);

namespace GAYA\Taxonomy;

use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class to register taxonomy configurations.
 *
 * This file is heavily inspired by former TYPO3 v11 CategoryRegistry
 *
 * @internal
 */
final class TaxonomyRegistry implements SingletonInterface
{
    protected array $registry = [];

    protected array $addedTaxonomyTabs = [];

    /**
     * Adds a new taxonomy configuration to this registry.
     * TCA changes are directly applied
     * Database changes will happen during AlterTableDefinitionStatementsEvent event
     *
     * @param string $tableName Name of the table to be registered
     * @param string $fieldName Name of the field to be registered
     * @param string $vocabularySlug Slug of the taxonomy vocabulary of the field
     * @param array $options Additional configuration options
     *              + fieldList: field configuration to be added to showitems
     *              + typesList: list of types that shall visualize the new field
     *              + position: insert position of the new field
     *              + label: backend label of the new field
     *              + fieldConfiguration: TCA field config array to override defaults
     * @param bool $override If TRUE, any taxonomy configuration for the same table / field is removed before the new configuration is added
     * @return bool
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function add(string $tableName, string $fieldName, string $vocabularySlug, array $options = [], bool $override = false): bool
    {
        $didRegister = false;
        if (empty($tableName)) {
            throw new \InvalidArgumentException('No or invalid table name "' . $tableName . '" given.', 1703252976);
        }
        if (empty($fieldName)) {
            throw new \InvalidArgumentException('No or invalid field name "' . $fieldName . '" given.', 1703252977);
        }
        if (empty($vocabularySlug)) {
            throw new \InvalidArgumentException('No or invalid vocabulary slug "' . $vocabularySlug . '" given.', 1703252978);
        }

        if ($override) {
            $this->remove($tableName, $fieldName);
        }

        if (!$this->isRegistered($tableName, $fieldName)) {
            $options['__vocabularySlug'] = $vocabularySlug;
            $this->registry[$tableName][$fieldName] = $options;

            if (isset($GLOBALS['TCA'][$tableName]['columns'])) {
                $this->applyTcaForTableAndField($tableName, $fieldName);
                $didRegister = true;
            }
        }

        return $didRegister;
    }

    /**
     * Removes the given field in the given table from the registry if it is found.
     */
    protected function remove(string $tableName, string $fieldName): void
    {
        if (!$this->isRegistered($tableName, $fieldName)) {
            return;
        }

        unset($this->registry[$tableName][$fieldName]);

        // If no more fields are configured we unregister the taxonomy tab.
        if (empty($this->registry[$tableName]) && isset($this->addedTaxonomyTabs[$tableName])) {
            unset($this->addedTaxonomyTabs[$tableName]);
        }
    }

    /**
     * Tells whether a table has a taxonomy configuration in the registry.
     *
     * @param string $tableName Name of the table to be looked up
     * @param string $fieldName Name of the field to be looked up
     * @return bool
     */
    protected function isRegistered(string $tableName, string $fieldName): bool
    {
        return isset($this->registry[$tableName][$fieldName]);
    }

    /**
     * Applies the additions directly to the TCA
     *
     * @param string $tableName
     * @param string $fieldName
     */
    protected function applyTcaForTableAndField(string $tableName, string $fieldName): void
    {
        $this->addTcaColumn($tableName, $fieldName, $this->registry[$tableName][$fieldName]);
        $this->addToAllTCAtypes($tableName, $fieldName, $this->registry[$tableName][$fieldName]);
    }

    /**
     * Add a new TCA Column
     *
     * @param string $tableName Name of the table to be configured
     * @param string $fieldName Name of the field to be used to store terms
     * @param array $options Additional configuration options
     */
    protected function addTcaColumn(string $tableName, string $fieldName, array $options): void
    {
        // Makes sure to add more TCA to an existing structure
        if (isset($GLOBALS['TCA'][$tableName]['columns'])) {
            // Take specific label into account
            $label = $options['__vocabularySlug'];
            if (!empty($options['label'])) {
                $label = $options['label'];
            }

            // Take specific value of exclude flag into account
            $exclude = true;
            if (isset($options['exclude'])) {
                $exclude = (bool)$options['exclude'];
            }

            $fieldConfiguration = empty($options['fieldConfiguration']) ? [] : $options['fieldConfiguration'];

            $columns = [
                $fieldName => [
                    'exclude' => $exclude,
                    'label' => $label,
                    'l10n_mode' => 'exclude',
                    'config' => $this->getTcaFieldConfiguration(
                        $tableName,
                        $fieldName,
                        $options['__vocabularySlug'],
                        $options['renderType'],
                        $fieldConfiguration
                    ),
                ],
            ];

            if (isset($options['l10n_mode'])) {
                $columns[$fieldName]['l10n_mode'] = $options['l10n_mode'];
            }
            if (isset($options['l10n_display'])) {
                $columns[$fieldName]['l10n_display'] = $options['l10n_display'];
            }
            if (isset($options['displayCond'])) {
                $columns[$fieldName]['displayCond'] = $options['displayCond'];
            }
            if (isset($options['onChange'])) {
                $columns[$fieldName]['onChange'] = $options['onChange'];
            }

            // Register opposite references for the foreign side of a relation
            if (empty($GLOBALS['TCA']['tx_taxonomy_domain_model_term']['columns']['items']['config']['MM_oppositeUsage'][$tableName])) {
                $GLOBALS['TCA']['tx_taxonomy_domain_model_term']['columns']['items']['config']['MM_oppositeUsage'][$tableName] = [];
            }
            if (!in_array($fieldName, $GLOBALS['TCA']['tx_taxonomy_domain_model_term']['columns']['items']['config']['MM_oppositeUsage'][$tableName])) {
                $GLOBALS['TCA']['tx_taxonomy_domain_model_term']['columns']['items']['config']['MM_oppositeUsage'][$tableName][] = $fieldName;
            }

            // Adding fields to an existing table definition
            ExtensionManagementUtility::addTCAcolumns($tableName, $columns);
        }
    }

    /**
     * Get the config array for given table and field.
     * This method does NOT take care of adding sql fields, adding the field to TCA types
     * nor does it set the MM_oppositeUsage in the tx_taxonomy_domain_model_term TCA.
     */
    protected function getTcaFieldConfiguration(string $tableName, string $fieldName, string $vocabularySlug, string $renderType, array $fieldConfigurationOverride = []): array
    {
        // Forges a new field
        $fieldConfiguration = [
            'type' => 'select',
            'renderType' => $renderType,
            'foreign_table' => 'tx_taxonomy_domain_model_term',
            'foreign_table_where' => '{#tx_taxonomy_domain_model_term}.{#sys_language_uid} IN(0, -1) 
                AND {#tx_taxonomy_domain_model_term}.{#pid} = ###SITE:tx_taxonomy_' . $vocabularySlug . '_pid###
                AND {#tx_taxonomy_domain_model_term}.{#vocabulary} = ###SITE:tx_taxonomy_' . $vocabularySlug . '_uid###',
            'MM' => 'tx_taxonomy_domain_model_term_record_mm',
            'MM_opposite_field' => 'items',
            'MM_match_fields' => [
                'tablenames' => $tableName,
                'fieldname' => $fieldName,
            ],
        ];

        if ($renderType === 'selectTree') {
            $fieldConfiguration['treeConfig'] = [
                'parentField' => 'parent',
                'appearance' => [
                    'showHeader' => true,
                    'expandAll' => true,
                ],
            ];
        } elseif ($renderType === 'selectSingle') {
            $fieldConfiguration['items'] = [
                [
                    'label' => '',
                    'value' => null,
                ],
            ];
        }

        // Merge changes to TCA configuration
        if (!empty($fieldConfigurationOverride)) {
            ArrayUtility::mergeRecursiveWithOverrule(
                $fieldConfiguration,
                $fieldConfigurationOverride
            );
        }

        return $fieldConfiguration;
    }

    /**
     * Add a new field into the TCA types -> showitem
     *
     * @param string $tableName Name of the table to be taxonomized
     * @param string $fieldName Name of the field to be used to store terms
     * @param array $options Additional configuration options
     *              + fieldList: field configuration to be added to showitems
     *              + typesList: list of types that shall visualize the vocabularies field
     *              + position: insert position of the vocabularies field
     */
    protected function addToAllTCAtypes(string $tableName, string $fieldName, array $options): void
    {
        // Makes sure to add more TCA to an existing structure
        if (isset($GLOBALS['TCA'][$tableName]['columns'])) {
            if (empty($options['fieldList'])) {
                $fieldList = $this->addTaxonomyTab($tableName, $fieldName);
            } else {
                $fieldList = $options['fieldList'];
            }

            $typesList = '';
            if (isset($options['typesList']) && $options['typesList'] !== '') {
                $typesList = $options['typesList'];
            }

            $position = '';
            if (!empty($options['position'])) {
                $position = $options['position'];
            }

            // Makes the new "vocabularies" field to be visible in TCE forms.
            ExtensionManagementUtility::addToAllTCAtypes($tableName, $fieldList, $typesList, $position);
        }
    }

    /**
     * Creates the 'fieldList' string for $fieldName which includes a taxonomy tab.
     * But only one taxonomy tab is added per table.
     */
    protected function addTaxonomyTab(string $tableName, string $fieldName): string
    {
        $fieldList = '';
        if (!isset($this->addedTaxonomyTabs[$tableName])) {
            $fieldList .= '--div--;LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tabs.taxonomy, ';
            $this->addedTaxonomyTabs[$tableName] = true;
        }
        $fieldList .= $fieldName;

        return $fieldList;
    }

    /**
     * An event listener to inject the required taxonomy database fields to the
     * tables definition string
     */
    public function addTaxonomyDatabaseSchema(AlterTableDefinitionStatementsEvent $event): void
    {
        $event->addSqlData($this->getDatabaseTableDefinitions());
    }

    /**
     * Generates tables definitions for all registered fields.
     */
    protected function getDatabaseTableDefinitions(): string
    {
        $sql = '';

        $template = str_repeat(PHP_EOL, 3) . 'CREATE TABLE %s (' . PHP_EOL
            . '  %s int(11) DEFAULT \'0\' NOT NULL' . PHP_EOL . ');' . str_repeat(PHP_EOL, 3);

        foreach ($this->registry as $tableName => $fields) {
            foreach ($fields as $fieldName => $options) {
                $sql .= sprintf($template, $tableName, $fieldName);
            }
        }

        return $sql;
    }
}
