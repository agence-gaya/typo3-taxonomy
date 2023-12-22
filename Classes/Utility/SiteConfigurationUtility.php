<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\Utility;

use GAYA\Taxonomy\Domain\Repository\VocabularyRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This class extends the SiteConfiguration pseudo TCA for Taxonomy configuration
 *
 * @internal
 */
final class SiteConfigurationUtility
{
    public static function addTaxonomyFields(): void
    {
        $palettes = [];

        // Add a set of uid/pid fields for each existing vocabulary
        foreach (self::getAllVocabulary() as $vocabulary) {
            $paletteName = 'tx_taxonomy_' . $vocabulary['slug'];

            // UID of the vocabulary is stored in SiteConfiguration for later use
            $fieldNameUid = 'tx_taxonomy_' . $vocabulary['slug'] . '_uid';
            $GLOBALS['SiteConfiguration']['site']['columns'][$fieldNameUid] = [
                'label' => 'UID',
                'config' => [
                    // We use a type select here, because input[readOnly=true] is not stored in yaml,
                    // and there is no other way to both hide and store the uid in the yaml config.
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        [
                            'label' => $vocabulary['uid'],
                            'value' => $vocabulary['uid'],
                        ],
                    ],
                    'required' => true,
                    'default' => $vocabulary['uid'],
                ],
            ];

            // PID can be set here to be local to the site.
            $fieldNamePid = 'tx_taxonomy_' . $vocabulary['slug'] . '_pid';
            $GLOBALS['SiteConfiguration']['site']['columns'][$fieldNamePid] = [
                'label' => 'PID',
                // type=group: not supported in SiteConfiguration pseudo TCA
                // type=link: does not allow to select sysfolder
                // so we fallback to use a "number" field, without any fieldWizard :-(
                'config' => [
                    'type' => 'number',
                    'size' => 10,
                    'range' => [
                        'lower' => 0,
                    ],
                ],
            ];

            $palettes[] = '--palette--;;' . $paletteName;
            $GLOBALS['SiteConfiguration']['site']['palettes'][$paletteName] = [
                'label' => $vocabulary['title'],
                'description' => LocalizationUtility::translate(
                    'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:site_configuration.description',
                    'Taxonomy',
                    [
                        $vocabulary['title'],
                        $vocabulary['slug']
                    ]
                ),
                'showitem' => $fieldNamePid . ',' . $fieldNameUid,
            ];
        }

        // Add all the palettes in a new tab
        if ($palettes !== []) {
            $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] .= ',
            --div--;LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:tabs.taxonomy,'
                . implode(',', $palettes);
        }
    }

    protected static function getAllVocabulary(): array
    {
        return GeneralUtility::makeInstance(VocabularyRepository::class)->getAllVocabulary();
    }
}
