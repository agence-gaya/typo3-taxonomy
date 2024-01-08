<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\Utility;

use GAYA\Taxonomy\TaxonomyRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class TaxonomyTcaUtility
{
    public static function addTaxonomyTree(string $tableName, string $fieldName, string $vocabularyName, string $typesList = '', $position = '', array $fieldConfigurationOverride = []): void
    {
        self::addToRegistry($tableName, $fieldName, $vocabularyName, $typesList, $position, 'selectTree', $fieldConfigurationOverride);
    }

    public static function addTaxonomySingle(string $tableName, string $fieldName, string $vocabularyName, string $typesList = '', $position = '', array $fieldConfigurationOverride = []): void
    {
        self::addToRegistry($tableName, $fieldName, $vocabularyName, $typesList, $position, 'selectSingle', $fieldConfigurationOverride);
    }

    private static function addToRegistry(string $tableName, string $fieldName, string $vocabularyName, string $typesList, string $position, string $renderType, array $fieldConfigurationOverride): void
    {
        $options = [
            'renderType' => $renderType,
            'typesList' => $typesList,
            'fieldList' => $position ? $fieldName : '',
            'position' => $position,
            'fieldConfiguration' => $fieldConfigurationOverride,
        ];
        $result = GeneralUtility::makeInstance(TaxonomyRegistry::class)
            ->add($tableName, $fieldName, $vocabularyName, $options);

        if ($result === false) {
            throw new \InvalidArgumentException(sprintf(
                TaxonomyRegistry::class . ': no vocabulary registered for field "%s.%s". Key was already registered.',
                $tableName,
                $fieldName
            ), 1703267651);
        }
    }
}
