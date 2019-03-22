# Magento 2 Defer JavaScripts

The module moves javascripts to the bottom of the page.

![Packagist](https://img.shields.io/packagist/v/hryvinskyi/magento2-deferjs.svg)
![Packagist](https://img.shields.io/packagist/dt/hryvinskyi/magento2-deferjs.svg)
[![Code Coverage](https://scrutinizer-ci.com/g/hryvinskyi/magento2-deferjs/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/hryvinskyi/magento2-deferjs/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/hryvinskyi/magento2-deferjs/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/hryvinskyi/magento2-deferjs/?branch=master)
![Packagist](https://img.shields.io/packagist/vpre/hryvinskyi/magento2-deferjs.svg)
![Packagist](https://img.shields.io/packagist/l/hryvinskyi/magento2-deferjs.svg)

# Installation Guide
### Install by composer
````
composer require hryvinskyi/magento2-deferjs
bin/magento module:enable Hryvinskyi_Base
bin/magento module:enable Hryvinskyi_DeferJs
bin/magento setup:upgrade
````
### Install download package
1. Download module https://github.com/hryvinskyi/magento2-base "Clone or download -> Download Zip" 
2. Download this module "Clone or download -> Download Zip"
3. Unzip two modules in the folder app\code\Hryvinskyi\Base and app\code\Hryvinskyi\DeferJs
4. Run commands:

```
bin/magento module:enable Hryvinskyi_Base
bin/magento module:enable Hryvinskyi_DeferJs
bin/magento setup:upgrade
```
5. Configure module in admin panel

# General Settings
To get the access to the 'Defer JavaScripts' settings please go to
Stores -> Configuration -> Hryvinskyi Extensions -> Defer JavaScripts and expand the General Settings section.

***Enabled deferred javascript:*** Enable or disable the extension from here.  
***Disable move attribute:*** Enter the attribute that will block the move of the script to the bottom.  
***Enabled minify body scripts:*** Enable script minification.  