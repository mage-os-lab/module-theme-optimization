<?php declare(strict_types=1);

namespace MageOS\ThemeOptimization\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Context as CustomerContext;

/**
 * BFCache ViewModel
 * 
 * Provides data and configuration for BFCache templates.
 * Handles configuration management and business logic for frontend templates.
 */
class BfCache implements ArgumentInterface
{
    /** Configuration paths */
    const XML_PATH_ENABLE_USER_INTERACTION_RELOAD_MINICART = 'system/bfcache/general/enable_user_interaction_reload_minicart';
    const XML_PATH_AUTO_CLOSE_MENU_MOBILE = 'system/bfcache/general/auto_close_menu_mobile';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var Context */
    private $httpContext;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Context $httpContext
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Context $httpContext
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->httpContext = $httpContext;
    }

    /**
     * Check if user interaction should refresh minicart
     *
     * @return bool
     */
    public function getEnableUserInteractionRefreshMiniCart(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_USER_INTERACTION_RELOAD_MINICART,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if mobile menu should auto-close
     *
     * @return bool
     */
    public function autoCloseMenuMobile(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_AUTO_CLOSE_MENU_MOBILE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool
    {
        return (bool) $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }
}
