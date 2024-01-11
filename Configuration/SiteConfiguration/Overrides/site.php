<?php

declare(strict_types=1);

use GAYA\Taxonomy\Utility\SiteConfigurationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

GeneralUtility::makeInstance(SiteConfigurationUtility::class)->addTaxonomyFields();
