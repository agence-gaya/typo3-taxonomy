<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\TsConfig;

use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\ModifyLoadedPageTsConfigEvent;

/**
 * Dynamically adds Page TsConfig on a Taxonomy SysFolder
 */
final class Loader
{
    private const TS_CONFIG = <<<TSCONFIG
mod.web_list.hideTables = pages
mod.web_list.allowedNewTables = tx_taxonomy_domain_model_term,tx_taxonomy_domain_model_vocabulary
TSCONFIG;

    public function addTaxonomyConfiguration(ModifyLoadedPageTsConfigEvent $event): void
    {
        $event->setTsConfig(
            $this->addConfiguration(
                $event->getRootLine(),
                $event->getTsConfig()
            )
        );
    }

    public function addConfiguration(array $rootLine, array $tsConfig): array
    {
        foreach ($rootLine as $page) {
            if (($page['module'] ?? '') === 'taxonomy') {
                if (!isset($tsConfig['page_' . $page['uid']])) {
                    $tsConfig['page_' . $page['uid']] = '';
                }
                $tsConfig['page_' . $page['uid']] .= LF . self::TS_CONFIG;
            }
        }

        return $tsConfig;
    }
}
