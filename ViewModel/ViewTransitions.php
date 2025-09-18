<?php

namespace MageOS\ThemeOptimization\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

class ViewTransitions implements ArgumentInterface
{
    protected const CONFIG_PATH = 'system/view_transitions/';

    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
    )
    {
    }

    protected function getConfigValue(string $key): ?string
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH . $key,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isEnabled(): bool
    {
        return (bool)$this->getConfigValue('enable');
    }

    public function isEnabledForBfcache(): bool
    {
        return (bool)$this->getConfigValue('enable_for_bfcache');
    }
}
