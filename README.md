# MageOS_ThemeOptimization module

This module provides theme-related features to improve the performance of your Magento store, including:

* Page transitions when navigating between pages on Magento
* Speculative preloading of internal links on hover

## Installation details

To install the module, run the following commands in SSH, from the Magento root directory:

```bash
composer require mage-os/module-theme-optimization
php bin/magento setup:upgrade
```

## Configuration

The module provides settings in the Magento Admin Panel under:
**Stores > Configuration > Advanced > Developer > Speculative Loading**

• **Enable Speculation Rules** - Enables speculative loading to preload pages before links are clicked, making perceived load times faster. (Default: Yes)

• **Eagerness Level** - Controls how aggressively pages are preloaded. (Default: Moderate)
  - Conservative: Minimal preloading, only when very likely to be needed
  - Moderate: Balanced approach between performance and resource usage  
  - Eager: Aggressive preloading for maximum user experience, at the cost of loading pages the user may never visit

• **Exclude URL Patterns** - URL patterns to never preload. One pattern per line. (Default: customer, login, logout, auth, cart, checkout, search, download, redirect, rewrite, store, productalert)
  - URL patterns are matched against the request URI. We recommend entering part or full route paths, like "customer" (to exclude all customer pages) or "customer/account/logout" (to specifically exclude logout).

• **Exclude File Extensions** - File extensions to never preload. (Default: pdf, zip)

• **Exclude Selectors** - CSS selectors for links to never preload. Enter one selector per line. (Default: .do-not-prerender)

All values can be configured at Default, Website, and Store View scopes.

There is no configuration for the Page Transitions feature. When installed, page transitions are always enabled for all Magento themes (frontend and admin panel).

## Contributors

Original module contributed by [@rhoerr](https://github.com/rhoerr).

Credit for the default rules to [David Lambauer and @run_as_root](https://run-as-root.sh/blog/improving-pagespeed-with-speculative-loading).

This module is sponsored and maintained by [Mage-OS](https://mage-os.org). Mage-OS makes it open source and freely available for use by any Magento 2.4+ or Adobe Commerce website.
