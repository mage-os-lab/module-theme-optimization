<?php

namespace MageOS\ThemeOptimization\Plugin\PageCache\Model\Varnish;

use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class VclGenerator
{
    protected const CONFIG_PATH = 'system/full_page_cache/';
    protected const NOCACHE_FILE = 'nocache_snippet.vcl';
    protected const BFCACHE_FILE = 'bfcache_snippet.vcl';
    protected const EXCLUDED_PATHS_PLACEHOLDER = '/* {{ excluded_paths }} */';

    public function __construct(
        protected Reader $reader,
        protected ReadFactory $readFactory,
        protected ScopeConfigInterface $config
    )
    {
    }

    /**
     * Return generated varnish.vcl configuration file
     *
     * @param \Magento\PageCache\Model\Varnish\VclGenerator $subject
     * @param string $result
     * @return string
     */
    public function afterGenerateVcl(\Magento\PageCache\Model\Varnish\VclGenerator $subject, string $result): string
    {
        if ($this->config->isSetFlag(self::CONFIG_PATH . 'bfcache_enabled')) {
            $excludedPaths = $this->config->getValue(self::CONFIG_PATH . 'excluded_paths');
            $bfCacheSnippet = str_replace(
                self::EXCLUDED_PATHS_PLACEHOLDER,
                $excludedPaths,
                $this->getBfCacheSnippet()
            );
            $result = str_replace($this->getNoCacheSnippet(), $bfCacheSnippet, $result);
        }
        return $result;
    }

    /**
     * Read content of nocache_snippet.vcl
     */
    public function getNoCacheSnippet(): string
    {
        $configFilePath  = $this->reader->getModuleDir(Dir::MODULE_ETC_DIR, 'MageOS_ThemeOptimization');
        $directoryRead  = $this->readFactory->create($configFilePath);
        $configFilePath = $directoryRead->getRelativePath($configFilePath . '/' . self::NOCACHE_FILE);
        return $directoryRead->readFile($configFilePath);
    }

    /**
     * Read content of bfcache_snippet.vcl
     */
    public function getBfCacheSnippet(): string
    {
        $configFilePath  = $this->reader->getModuleDir(Dir::MODULE_ETC_DIR, 'MageOS_ThemeOptimization');
        $directoryRead  = $this->readFactory->create($configFilePath);
        $configFilePath = $directoryRead->getRelativePath($configFilePath . '/' . self::BFCACHE_FILE);
         return $directoryRead->readFile($configFilePath);
    }
}
