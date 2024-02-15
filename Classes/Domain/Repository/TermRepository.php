<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\Domain\Repository;

use GAYA\Taxonomy\Domain\Model\Term;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class TermRepository extends Repository
{
    /**
     * Return Term objects by relation to other records
     */
    public function findByRelation(string $tableName, string $fieldName, int $uid): array
    {
        $result = $this->getQueryBuilderByRelation($tableName, $fieldName, $uid)
            ->executeQuery();

        // Get all the term's uid and save the sorting_foreign to reapply later
        $terms = [];
        $sortingForeign = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $terms[] = $row['uid_local'];
            $sortingForeign[$row['uid_local']] = $row['sorting_foreign'];
        }

        if ($terms === []) {
            return [];
        }

        // Load all the Extbase entities from the term's uid.
        $queryResult = $this->loadExtbaseEntities($terms);

        // Finally, reapply sorting
        return $this->reapplySortingForeign($queryResult->toArray(), $sortingForeign);
    }

    /**
     * Return the first Term object by relation to other records
     */
    public function findOneByRelation(string $tableName, string $fieldName, int $uid): ?Term
    {
        $result = $this->getQueryBuilderByRelation($tableName, $fieldName, $uid)
            ->setMaxResults(1)
            ->executeQuery();

        $terms = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $terms[] = $row['uid_local'];
        }

        if ($terms === []) {
            return null;
        }

        // Load all the Extbase entities from the term's uid.
        $queryResult = $this->loadExtbaseEntities($terms);

        // Finally, return the first (and only) result
        return $queryResult->getFirst();
    }

    protected function getQueryBuilderByRelation(string $tableName, string $fieldName, int $uid): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_taxonomy_domain_model_term_record_mm');

        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        return $queryBuilder
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
            ->orderBy('sorting_foreign');
    }

    protected function loadExtbaseEntities(array $terms): QueryResultInterface
    {
        // We need to discard sys_language clause because relations are stored and loaded
        // directly for the translated records.
        $query = $this->createQuery();
        $query->getQuerySettings()
            ->setRespectSysLanguage(false)
            ->setRespectStoragePage(false);

        return $query
            ->matching(
                $query->in('uid', $terms)
            )
            ->execute();
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
