<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\Domain\Repository;

use GAYA\Taxonomy\Domain\Model\Term;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class TermRepository extends Repository
{
    private const string TABLE_NAME = 'tx_taxonomy_domain_model_term';

    /**
     * Constructs a new Repository.
     */
    public function __construct(private readonly ConnectionPool $connectionPool)
    {
        parent::__construct();
    }

    public function getTableName(): string
    {
        return self::TABLE_NAME;
    }

    public function findTerm(int $uid): ?array
    {
        if ($uid <= 0) {
            return null;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $row = $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        return is_array($row) ? $row : null;
    }

    public function findTerms(int $vocabularyUid, ?int $pageId = null): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);

        $where = [
            $queryBuilder->expr()->eq('vocabulary', $queryBuilder->createNamedParameter($vocabularyUid, Connection::PARAM_INT)),
            $queryBuilder->expr()->in('sys_language_uid', [0, -1]),
        ];

        if ($pageId !== null) {
            $where[] = $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT));
        }

        return $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where(...$where)
            ->orderBy('parent', 'ASC')
            ->addOrderBy('sorting', 'ASC')
            ->addOrderBy('title', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Return Term objects by relation to other records.
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
     * Return the first Term object by relation to other records.
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

    public function removeTerm(int $uid): void
    {
        $this->processCommandMap([], [
            self::TABLE_NAME => [
                $uid => [
                    'delete' => 1,
                ],
            ],
        ]);
    }

    public function removeChildren(int $parent): void
    {
        $cmd = [];

        foreach ($this->findTerms($parent) as $term) {
            $cmd[$term['uid']] = [
                'delete' => 1,
            ];
        }

        $this->processCommandMap([], [
            self::TABLE_NAME => $cmd,
        ]);
    }

    public function updateTerm(int $uid, array $values): void
    {
        $data = [
            self::TABLE_NAME => [
                $uid => $values,
            ],
        ];

        $this->processCommandMap($data, []);
    }

    private function processCommandMap(array $data, array $cmd): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, $cmd);
        $dataHandler->process_cmdmap();
        $dataHandler->printLogErrorMessages();
    }

    protected function getQueryBuilderByRelation(string $tableName, string $fieldName, int $uid): QueryBuilder
    {
        $queryBuilder = $this->connectionPool
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
            static fn(Term $a, Term $b) => $sorting[$a->getUid()] <=> $sorting[$b->getUid()]
        );

        return $result;
    }
}
