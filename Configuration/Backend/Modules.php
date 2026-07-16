<?php

declare(strict_types=1);

use GAYA\Taxonomy\Controller\VocabulariesController;
use GAYA\Taxonomy\Controller\TermsController;

return [
    'content_taxonomy' => [
        'parent' => 'content',
        'position' => ['after' => 'records'],
        'access' => 'user',
        'workspaces' => 'live',
        'iconIdentifier' => 'gaya-taxonomy',
        'path' => '/module/content/taxonomy',
        'labels' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_module.xlf',
        'inheritNavigationComponentFromMainModule' => false,
    ],
    'content_taxonomy_vocabularies' => [
        'parent' => 'content_taxonomy',
        'access' => 'user',
        'iconIdentifier' => 'gaya-taxonomy',
        'path' => '/module/content/taxonomy/vocabularies',
        'labels' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_module.xlf',
        'routes' => [
            '_default' => [
                'target' => VocabulariesController::class . '::processRequest',
            ],
            'deleteVocabulary' => [
                'target' => VocabulariesController::class . '::processRequest',
            ],
        ],
    ],
    'content_taxonomy_terms' => [
        'parent' => 'content_taxonomy',
        'access' => 'user',
        'iconIdentifier' => 'gaya-taxonomy',
        'path' => '/module/content/taxonomy/terms',
        'labels' => 'LLL:EXT:taxonomy/Resources/Private/Language/locallang_module.xlf',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'routes' => [
            '_default' => [
                'target' => TermsController::class . '::processRequest',
            ],
            'deleteTerm' => [
                'target' => TermsController::class . '::processRequest',
            ],
            'sortTerms' => [
                'target' => TermsController::class . '::processRequest',
                'methods' => ['POST'],
            ],
        ],
    ],
];
