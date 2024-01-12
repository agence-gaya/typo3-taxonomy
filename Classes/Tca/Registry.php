<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\Tca;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class to register taxonomy configurations.
 *
 * This file is heavily inspired by former TYPO3 v11 CategoryRegistry
 */
final class Registry implements SingletonInterface
{
    protected array $registry = [];

    protected array $addedTaxonomyTabs = [];

    /**
     * Adds a new taxonomy configuration to this registry.
     * TCA changes are directly applied.
     *
     * Database changes will happen during AlterTableDefinitionStatementsEvent event.
     *
     * @param TaxonomyConfiguration $configuration Configuration of the field
     * @param bool $override If TRUE, any taxonomy configuration for the same table / field is removed before the new configuration is added
     * @throws \InvalidArgumentException
     */
    public function configureTaxonomyField(TaxonomyConfiguration $configuration, bool $override = false): void
    {
        if (empty($configuration->getTableName())) {
            throw new \InvalidArgumentException('No or invalid table name "' . $configuration->getTableName() . '" given.', 1703252976);
        }
        if (empty($configuration->getFieldName())) {
            throw new \InvalidArgumentException('No or invalid field name "' . $configuration->getFieldName() . '" given.', 1703252977);
        }
        if (empty($configuration->getVocabularyName())) {
            throw new \InvalidArgumentException('No or invalid vocabulary name "' . $configuration->getVocabularyName() . '" given.', 1703252978);
        }

        if ($override) {
            $this->remove($configuration->getTableName(), $configuration->getFieldName());
        }

        $didRegister = false;
        if (!$this->isRegistered($configuration->getTableName(), $configuration->getFieldName())) {
            $this->registry[$configuration->getTableName()][$configuration->getFieldName()] = $configuration;

            // Makes sure to add more TCA to an existing structure
            if (isset($GLOBALS['TCA'][$configuration->getTableName()]['columns'])) {
                $this->applyTcaForConfiguration($configuration);
                $didRegister = true;
            }
        }

        if (!$didRegister) {
            throw new \InvalidArgumentException(sprintf(
                Registry::class . ': no vocabulary registered for field "%s.%s". Key was already registered.',
                $configuration->getTableName(),
                $configuration->getFieldName()
            ), 1703267651);
        }
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
     * @param TaxonomyConfiguration $configuration
     */
    protected function applyTcaForConfiguration(TaxonomyConfiguration $configuration): void
    {
        $this->addTcaColumn($configuration);
        $this->addToAllTCAtypes($configuration);
    }

    /**
     * Add a new TCA Column
     *
     * @param TaxonomyConfiguration $configuration Configuration of the field
     */
    protected function addTcaColumn(TaxonomyConfiguration $configuration): void
    {
        $columns = [
            $configuration->getFieldName() => [
                'exclude' => true,
                'label' => $configuration->getLabel() ?: $configuration->getVocabularyName(),
                'description' => $configuration->getDescription(),
                'l10n_mode' => 'exclude',
                'displayCond' => $configuration->getDisplayCond() ?: null,
                'onChange' => $configuration->getOnChange(),
                'config' => $this->getTcaFieldConfiguration(
                    $configuration->getTableName(),
                    $configuration->getFieldName(),
                    $configuration->getVocabularyName(),
                    $configuration->getRenderType(),
                    $configuration->getConfigurationOverride()
                ),
            ],
        ];

        // Register opposite references for the foreign side of a relation
        if (empty($GLOBALS['TCA']['tx_taxonomy_domain_model_term']['columns']['items']['config']['MM_oppositeUsage'][$configuration->getTableName()])) {
            $GLOBALS['TCA']['tx_taxonomy_domain_model_term']['columns']['items']['config']['MM_oppositeUsage'][$configuration->getTableName()] = [];
        }
        if (!in_array($configuration->getFieldName(), $GLOBALS['TCA']['tx_taxonomy_domain_model_term']['columns']['items']['config']['MM_oppositeUsage'][$configuration->getTableName()])) {
            $GLOBALS['TCA']['tx_taxonomy_domain_model_term']['columns']['items']['config']['MM_oppositeUsage'][$configuration->getTableName()][] = $configuration->getFieldName();
        }

        // Adding fields to an existing table definition
        ExtensionManagementUtility::addTCAcolumns($configuration->getTableName(), $columns);
    }

    /**
     * Get the config array for given table and field.
     * This method does NOT take care of adding sql fields, adding the field to TCA types
     * nor does it set the MM_oppositeUsage in the tx_taxonomy_domain_model_term TCA.
     */
    protected function getTcaFieldConfiguration(string $tableName, string $fieldName, string $vocabularyName, string $renderType, array $fieldConfigurationOverride = []): array
    {
        // Forges a new field
        $fieldConfiguration = [
            'type' => 'select',
            'renderType' => $renderType,
            'foreign_table' => 'tx_taxonomy_domain_model_term',
            'foreign_table_where' => '{#tx_taxonomy_domain_model_term}.{#sys_language_uid} IN(0, -1) 
                AND {#tx_taxonomy_domain_model_term}.{#pid} = ###SITE:tx_taxonomy_' . $vocabularyName . '_pid###
                AND {#tx_taxonomy_domain_model_term}.{#vocabulary} = ###SITE:tx_taxonomy_' . $vocabularyName . '_uid###',
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
        } else {
            throw new \InvalidArgumentException('No or invalid renderType "' . $renderType . '" given.', 1704757909);
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
     * @param TaxonomyConfiguration $configuration
     */
    protected function addToAllTCAtypes(TaxonomyConfiguration $configuration): void
    {
        // Makes sure to add more TCA to an existing structure
        if (!isset($GLOBALS['TCA'][$configuration->getTableName()]['columns'])) {
            return;
        }

        $fieldList = $configuration->getPosition() ? $configuration->getFieldName() : '';
        if (empty($fieldList)) {
            $fieldList = $this->addTaxonomyTab($configuration->getTableName(), $configuration->getFieldName());
        }
        $typesList = implode(',', $configuration->getTypes());
        $position = $configuration->getPosition();

        // Makes the new "vocabularies" field to be visible in TCE forms.
        ExtensionManagementUtility::addToAllTCAtypes($configuration->getTableName(), $fieldList, $typesList, $position);
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
}
