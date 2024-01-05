<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Term extends AbstractEntity
{
    protected string $title = '';

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
