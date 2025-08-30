<?php

namespace MageOS\ThemeOptimization\ViewModel;

use Magento\Customer\Block\SectionConfig;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class BFCacheSections implements ArgumentInterface
{

    const SECTION_TO_CHECK = 'bfcache';

    public function __construct(
        protected SectionConfig $sectionConfig
    )
    {
    }

    /**
     * @return string
     */
    public function getSectionsToReload(): string
    {
        return implode(
            ',',
            array_map(fn($v) => "'$v'", $this->sectionConfig->getSections()[self::SECTION_TO_CHECK])
        );
    }
}
