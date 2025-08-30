<?php

namespace MageOS\ThemeOptimization\Plugin\Framework\App\Response;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\PageCache\Model\Config;

class Http
{

    protected const CONFIG_PATH = 'system/full_page_cache/';

    public function __construct(
        protected ScopeConfigInterface $config,
        protected RequestInterface $request,
        protected Config $pageCacheConfig
    )
    {
    }

    /**
     * @param \Magento\Framework\App\Response\Http $subject
     * @return void
     */
    public function afterSetNoCacheHeaders(\Magento\Framework\App\Response\Http $subject): void
    {
        if ($this->config->isSetFlag(self::CONFIG_PATH . 'bfcache_enabled')) {
            $excludedPaths = explode('|', $this->config->getValue(self::CONFIG_PATH . 'excluded_paths'));
            if (!in_array($this->request->getModuleName(), $excludedPaths)) {
                $subject->setPublicHeaders($this->pageCacheConfig->getTtl());
            }
        }
    }
}
