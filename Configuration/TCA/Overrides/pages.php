<?php

declare(strict_types=1);

// Add folder configuration
$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
    'label' =>  'LLL:EXT:taxonomy/Resources/Private/Language/locallang_db.xlf:pages.module.taxonomy-folder',
    'value' => 'taxonomy',
    'icon' => 'gaya-taxonomy-folder',
];
$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-taxonomy'] = 'gaya-taxonomy-folder';
