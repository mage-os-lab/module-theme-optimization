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
    /** @var string */
    public const XML_PATH_ENABLE = 'system/bfcache/general/enable';

    /** @var string */
    public const XML_PATH_EXCLUDE_URL_PATTERNS = 'system/bfcache/scope/exclude_url_patterns';

    /** @var bool */
    private $isRequestCacheable = false;

    /**
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     * @param HttpRequest $request
     */
    public function __construct(
        private Config $config,
        private ScopeConfigInterface $scopeConfig,
        private HttpRequest $request
    ) {
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

        if ($this->isRequestCacheable($cacheControl) && !$this->isRequestInExcludePatterns($requestURI)) {
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

        if ($this->isRequestCacheable === true) {
            $cacheControlHeader = $subject->getHeader('Cache-Control');
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
     * Check if the request URI contains any excluded URL patterns (case-insensitive, partial match).
     *
     * @param string $requestURI
     * @return bool
     */
    private function isRequestInExcludePatterns(string $requestURI): bool
    {
        $patterns = $this->getConfig(self::XML_PATH_EXCLUDE_URL_PATTERNS);

        if (empty($patterns)) {
            return false;
        }

        foreach ($this->parseExcludePatterns($patterns) as $pattern) {
            if ($pattern !== '' && mb_stripos($requestURI, $pattern, 0, 'UTF-8') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse exclude patterns from config string.
     *
     * @param string $patterns
     * @return array
     */
    private function parseExcludePatterns(string $patterns): array
    {
        return array_filter(array_map('trim', explode("\n", $patterns)));
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
     * @return string
     */
    private function getConfig(string $configPath, $store = null): string
    {
        return (string)$this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
