<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\UserFunc;

/**
 * @internal
 */
final class SlugUserFunc
{
    /**
     * Normalizes the slug to be as simple as possible.
     */
    public function normalize(array $params): string
    {
        return preg_replace(
            '#[^a-z0-9]#',
            '_',
            mb_strtolower((string) $params['slug'])
        );
    }

    /**
     * Returns an empty string to disable the prefix widget aside the slug field.
     */
    public function noPrefix(): string
    {
        return '';
    }
}
