<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\Utility;

use Doctrine\DBAL\ArrayParameterType;
use GAYA\Taxonomy\Domain\Repository\VocabularyRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This class extends the SiteConfiguration pseudo TCA for Taxonomy configuration
 *
 * @internal
 */
final class SiteConfigurationUtility
{
    public function __construct(protected VocabularyRepository $vocabularyRepository) {}

    public function addTaxonomyFields(): void
    {
        $palettes = [];

        // Add a set of uid/pid fields for each existing vocabulary
        foreach ($this->getAllVocabulary() as $vocabulary) {
            $paletteName = 'tx_taxonomy_' . $vocabulary['name'];

            // UID of the vocabulary is stored in SiteConfiguration for later use in TCA foreign_table_where,
            // where the vocabulary's name can't be directly used.
            $fieldNameUid = 'tx_taxonomy_' . $vocabulary['name'] . '_uid';
            $GLOBALS['SiteConfiguration']['site']['columns'][$fieldNameUid] = [
                'label' => 'UID',
                'description' => LocalizationUtility::translate(
                    'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:site_configuration.uid.description',
                    'Taxonomy'
                ),
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
            $fieldNamePid = 'tx_taxonomy_' . $vocabulary['name'] . '_pid';
            $GLOBALS['SiteConfiguration']['site']['columns'][$fieldNamePid] = [
                'label' => 'PID',
                'description' => LocalizationUtility::translate(
                    'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:site_configuration.description',
                    'Taxonomy',
                    [
                        $vocabulary['title'],
                        $vocabulary['name'],
                    ]
                ),
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => $this->buildStoragePidSelectItems($vocabulary),
                ],
            ];

            $palettes[] = '--palette--;;' . $paletteName;
            $GLOBALS['SiteConfiguration']['site']['palettes'][$paletteName] = [
                'label' => $vocabulary['title'],
                'description' => $vocabulary['rowDescription'],
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

    protected function getAllVocabulary(): array
    {
        return $this->vocabularyRepository->getAllVocabulary();
    }

    protected function buildStoragePidSelectItems(): array
    {
        $items = [];

        // Select all pages flagged with "module=taxomy" and get their rootline.
        foreach ($this->getAllTaxonomySysFolders() as $sysFolder) {
            $rootline = BackendUtility::BEgetRootLine($sysFolder['uid']);

            // Remove the rootlevel, and reverse
            array_pop($rootline);
            $rootline = array_reverse($rootline);

            // Build the label for each level of the rootline
            $slugs = array_map(static fn($page) => '[' . $page['uid'] . '] ' . $page['title'], $rootline);

            $items[] = [
                'label' => implode(' / ', $slugs),
                'value' => $sysFolder['uid'],
            ];
        }

        // Sort by rootline label
        usort($items, static fn($a, $b) => strcasecmp($a['label'], $b['label']));

        // If there is more than one level, add a "placeholder" item
        if (count($items) > 1) {
            array_unshift($items, [
                'label' => '',
                'value' => 0,
            ]);
        }

        return $items;
    }

    protected function getAllTaxonomySysFolders(): array
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $qb->getRestrictions()
            ->removeByType(HiddenRestriction::class)
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class);

        return $qb->select('uid', 'title')
            ->from('pages')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->in(
                        'sys_language_uid',
                        $qb->createNamedParameter([
                            0,
                            -1,
                        ], ArrayParameterType::INTEGER)
                    ),
                    $qb->expr()->eq(
                        'module',
                        $qb->createNamedParameter('taxonomy')
                    ),
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
