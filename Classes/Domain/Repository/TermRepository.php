<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\Domain\Repository;

use GAYA\Taxonomy\Domain\Model\Term;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

class TermRepository extends Repository
{
    /**
     * Return Term objects by relation to other records
     */
    public function findByRelation(string $tableName, string $fieldName, int $uid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_taxonomy_domain_model_term_record_mm');

        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        $result = $queryBuilder
            ->select('uid_local', 'sorting_foreign')
            ->from('tx_taxonomy_domain_model_term_record_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter($tableName)
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter($fieldName)
                )
            )
            ->orderBy('sorting_foreign')
            ->executeQuery();

        $terms = [];
        $sortingForeign = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $terms[] = $row['uid_local'];
            $sortingForeign[$row['uid_local']] = $row['sorting_foreign'];
        }

        // Load all the Extbase entities from the references.
        // We need to discard sys_language clause because relations are stored and loaded
        // directly for the translated records.
        $query = $this->createQuery();
        $query->getQuerySettings()
            ->setRespectSysLanguage(false)
            ->setRespectStoragePage(false);

        $result = $query
            ->matching(
                $query->in('uid', $terms)
            )
            ->execute();

        // Finally, reapply sorting
        return $this->reapplySortingForeign($result->toArray(), $sortingForeign);
    }

    protected function reapplySortingForeign(array $result, $sorting): array
    {
        uasort(
            $result,
            static function (Term $a, Term $b) use ($sorting) {
                $sortA = $sorting[$a->getUid()];
                $sortB = $sorting[$b->getUid()];

                if ($sortA === $sortB) {
                    return 0;
                }

                return ($sortA < $sortB) ? -1 : 1;
            }
        );

        return $result;
    }
}
