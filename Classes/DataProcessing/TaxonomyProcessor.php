<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\DataProcessing;

use GAYA\Taxonomy\Domain\Repository\TermRepository;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * This data processor can be used for processing data for record which contain relations to Term records
 *
 * Example TypoScript configuration:
 *
 * 10 = GAYA\Taxonomy\DataProcessing\TaxonomyProcessor
 * # You can also use the alias
 * # 10 = taxonomy
 * 10 {
 *   fieldName = my_taxonomy_field
 *   as = my_taxonomy_field
 *
 *   # If you do not need a collection, you can get the first or null
 *   # returnFirst = 1
 *
 *   # If not given, tableName is taken from the current cObj table.
 *   # tableName = tx_myother_table
 *
 *   # If not given, recUid is taken from the current cObj data, but you can override this with any stdWrap function.
 *   # You just need to pass the localized uid of the record.
 *   # recUid.field = _LOCALIZED_UID // _PAGES_OVERLAY_UID // uid
 * }
 */
class TaxonomyProcessor implements DataProcessorInterface
{
    public function __construct(protected TermRepository $termRepository) {}

    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData)
    {
        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }

        // The table name to process
        $tableName = $cObj->stdWrapValue('tableName', $processorConfiguration, $cObj->getCurrentTable());
        if (empty($tableName)) {
            return $processedData;
        }

        // The field name to process
        $fieldName = $cObj->stdWrapValue('fieldName', $processorConfiguration);
        if (empty($fieldName)) {
            return $processedData;
        }

        // The uid of the record to find relations
        if (isset($processorConfiguration['recUid']) || isset($processorConfiguration['recUid.'])) {
            $recUid = (int)$cObj->stdWrapValue('recUid', $processorConfiguration);
        } elseif ($tableName === 'pages') {
            $recUid = $cObj->data['_PAGES_OVERLAY_UID'] ?? $cObj->data['uid'] ?? 0;
        } else {
            $recUid = $cObj->data['_LOCALIZED_UID'] ?? $cObj->data['uid'] ?? 0;
        }
        if (empty($recUid)) {
            return $processedData;
        }

        $returnFirst = (bool) ($processorConfiguration['returnFirst'] ?? false);

        // Gather data
        if ($returnFirst) {
            $data = $this->termRepository->findOneByRelation($tableName, $fieldName, $recUid);
        } else {
            $data = $this->termRepository->findByRelation($tableName, $fieldName, $recUid);
        }

        // set the terms into a variable, default to the field name
        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, $fieldName);
        $processedData[$targetVariableName] = $data;

        return $processedData;
    }
}
