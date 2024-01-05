<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class VocabularyRepository
{
    public function getAllVocabulary(): array
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_taxonomy_domain_model_vocabulary');
        $qb->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction())
            // Selects only "live" records
            ->add(new WorkspaceRestriction(0));

        $qb->select('*')
            ->from('tx_taxonomy_domain_model_vocabulary')
            ->where(
                $qb->expr()->in('sys_language_uid', [
                    0,
                    -1
                ])
            )
            ->orderBy('title', 'asc');

        return $qb->executeQuery()->fetchAllAssociative();
    }
}
