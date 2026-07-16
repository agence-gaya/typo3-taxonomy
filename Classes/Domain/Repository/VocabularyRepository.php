<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class VocabularyRepository
{
    private const string TABLE_NAME = 'tx_taxonomy_domain_model_vocabulary';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly TermRepository $termRepository
    ) {}

    public function getTableName(): string
    {
        return self::TABLE_NAME;
    }

    public function findVocabulary(int $uid): ?array
    {
        if ($uid <= 0) {
            return null;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $row = $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->in('sys_language_uid', [0, -1])
            )
            ->executeQuery()
            ->fetchAssociative();

        return is_array($row) ? $row : null;
    }

    public function getAllVocabulary(): array
    {
        $qb = $this->connectionPool
            ->getQueryBuilderForTable(self::TABLE_NAME);
        $qb->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction())
            // Selects only "live" records
            ->add(new WorkspaceRestriction(0));

        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $qb->expr()->in('sys_language_uid', [
                    0,
                    -1,
                ])
            )
            ->orderBy('title', 'asc');

        return $qb->executeQuery()->fetchAllAssociative();
    }

    public function remove(int $uid): void
    {
        $this->termRepository->removeChildren($uid);

        $this->processCommandMap([], [
            self::TABLE_NAME => [
                $uid => [
                    'delete' => 1,
                ],
            ],
        ]);
    }

    private function processCommandMap(array $data, array $cmd): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, $cmd);
        $dataHandler->process_cmdmap();
        $dataHandler->printLogErrorMessages();
    }
}
