<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'gaya-taxonomy' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:taxonomy/Resources/Public/Icons/Extension.svg',
    ],
    'gaya-taxonomy-vocabulary' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:taxonomy/Resources/Public/Icons/Vocabulary.svg',
    ],
    'gaya-taxonomy-term' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:taxonomy/Resources/Public/Icons/Term.svg',
    ],
    'gaya-taxonomy-folder' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:taxonomy/Resources/Public/Icons/Folder.svg',
    ],
];
