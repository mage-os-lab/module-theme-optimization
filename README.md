# MageOS_ThemeOptimization module

This module provides theme-related features to improve the performance of your Magento store, including:

* Page transitions when navigating between pages on Magento
* Speculative preloading of internal links on hover

## Installation details

To install the module, run the following commands in SSH, from the Magento root directory:

```bash
composer require mageos/theme-optimization
php bin/magento setup:upgrade
```

## Contributors

Credit for the default generic rules to [David Lambauer / @run_as_root](https://run-as-root.sh/blog/improving-pagespeed-with-speculative-loading).
