<?php declare(strict_types=1);

namespace MageOS\ThemeOptimization\Plugin\Framework\App\Response;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\PageCache\Model\Config;
use Magento\Store\Model\ScopeInterface;

/**
 * Plugin to modify cache headers for BFCache functionality
 */
class Http
{
    /** Configuration paths */
    const XML_PATH_ENABLE = 'system/bfcache/general/enable';
    const XML_PATH_BLACK_LIST_URLS = 'system/bfcache/scope/black_list_urls';

    /** @var Config */
    private $config;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var HttpRequest */
    private $request;

    /** @var bool */
    private $isRequestCacheable = false;

    /**
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     * @param HttpRequest $request
     */
    public function __construct(
        Config $config,
        ScopeConfigInterface $scopeConfig,
        HttpRequest $request
    ) {
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
    }

    /**
     * Intercept before setting no-cache headers to determine if request is cacheable
     *
     * @param \Magento\Framework\App\Response\Http $subject
     * @return void
     */
    public function beforeSetNoCacheHeaders(\Magento\Framework\App\Response\Http $subject): void
    {
        if ($this->config->getType() !== Config::BUILT_IN || !$this->isEnabled()) {
            return;
        }

        $cacheControlHeader = $subject->getHeader('Cache-Control');
        if (!$cacheControlHeader) {
            return;
        }

        $cacheControl = $cacheControlHeader->getFieldValue();
        $requestURI = ltrim($this->request->getRequestURI(), '/');
        
        if ($this->isRequestCacheable($cacheControl) && !$this->isRequestInBlackListUrls($requestURI)) {
            $this->isRequestCacheable = true;
        }
    }

    /**
     * Update cache headers after setting no-cache headers
     *
     * @param \Magento\Framework\App\Response\Http $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterSetNoCacheHeaders(\Magento\Framework\App\Response\Http $subject, $result)
    {
        if ($this->config->getType() !== Config::BUILT_IN || !$this->isEnabled()) {
            return $result;
        }

        $cacheControlHeader = $subject->getHeader('Cache-Control');
        if (!$cacheControlHeader) {
            return $result;
        }

        if ($this->isRequestCacheable == true) {
            $cacheControlHeader = $subject->getHeader('cache-control');
            $cacheControlHeader->removeDirective('no-store');
        }
        $this->isRequestCacheable = false;

        return $result;
    }

    /**
     * Check if request is cacheable based on cache control header
     *
     * @param string $cacheControl
     * @return bool
     */
    private function isRequestCacheable(string $cacheControl): bool
    {
        return (bool) preg_match('/public.*s-maxage=(\d+)/', $cacheControl);
    }

    /**
     * Check if request URI matches blacklisted URLs
     *
     * @param string $requestURI
     * @return bool
     */
    private function isRequestInBlackListUrls(string $requestURI): bool
    {
        $blackListUrls = $this->convertListUrls(self::XML_PATH_BLACK_LIST_URLS);
        if (!$blackListUrls) {
            return false;
        }
        
        return (bool) preg_match('/' . $blackListUrls . '/', $requestURI);
    }

    /**
     * Check if BFCache is enabled
     *
     * @return bool
     */
    private function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get configuration value by path
     *
     * @param string $configPath
     * @param int|string|null $store
     * @return string|null
     */
    private function getConfig(string $configPath, $store = null): ?string
    {
        return $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Convert comma-separated URLs to pipe-separated regex pattern
     *
     * @param string $configPath
     * @return string
     */
    private function convertListUrls(string $configPath): string
    {
        $listUrls = $this->getConfig($configPath);
        if (!$listUrls) {
            return '';
        }
        
        $urlList = array_map('trim', explode(',', $listUrls));
        $urlList = array_filter($urlList);
        
        return implode('|', array_map('preg_quote', $urlList, array_fill(0, count($urlList), '/')));
    }
}
