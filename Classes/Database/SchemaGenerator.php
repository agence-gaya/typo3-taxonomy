<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\Database;

use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;

final class SchemaGenerator
{
    /**
     * An event listener to inject the required taxonomy database fields to the
     * tables definition string
     */
    public function addTaxonomyDatabaseSchema(AlterTableDefinitionStatementsEvent $event): void
    {
        $event->addSqlData($this->getDatabaseTableDefinitions());
    }

    /**
     * Generates tables definitions for all taxonomy fields.
     */
    protected function getDatabaseTableDefinitions(): string
    {
        $sql = '';

        $template = str_repeat(PHP_EOL, 3)
            . 'CREATE TABLE %s (' . PHP_EOL
            . '  %s int(11) DEFAULT \'0\' NOT NULL' . PHP_EOL
            . ');' . str_repeat(PHP_EOL, 3);

        foreach ($this->getDefinedFields() as $tableName => $fields) {
            foreach ($fields as $fieldName) {
                $sql .= sprintf($template, $tableName, $fieldName);
            }
        }

        return $sql;
    }

    /**
     * Find all taxonomy fields in the cached TCA
     */
    protected function getDefinedFields(): array
    {
        if (!isset($GLOBALS['TCA'])) {
            throw new \RuntimeException('TCA must be loaded at this point', 1705068918);
        }

        $fields = [];

        foreach ($GLOBALS['TCA'] as $tableName => $tableDefinition) {
            foreach ($tableDefinition['columns'] as $fieldName => $fieldDefinition) {
                if (($fieldDefinition['config']['foreign_table'] ?? '') === 'tx_taxonomy_domain_model_term') {
                    $fields[$tableName][] = $fieldName;
                }
            }
        }

        return $fields;
    }
}
