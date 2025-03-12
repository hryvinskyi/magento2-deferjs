# Magento 2 Defer JavaScripts

The module moves javascripts to the bottom of the page.

[![Latest Stable Version](https://poser.pugx.org/hryvinskyi/magento2-deferjs/v/stable)](https://packagist.org/packages/hryvinskyi/magento2-deferjs)
[![Total Downloads](https://poser.pugx.org/hryvinskyi/magento2-deferjs/downloads)](https://packagist.org/packages/hryvinskyi/magento2-deferjs)
[![PayPal donate button](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=legionerblack%40yandex%2eru&lc=UA&item_name=Magento%202%20Defer%20Javascript&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted "Donate once-off to this project using Paypal")
[![Latest Unstable Version](https://poser.pugx.org/hryvinskyi/magento2-deferjs/v/unstable)](https://packagist.org/packages/oakcms/oakcms)
[![License](https://poser.pugx.org/hryvinskyi/magento2-deferjs/license)](https://packagist.org/packages/hryvinskyi/magento2-deferjs)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/hryvinskyi/magento2-deferjs/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/hryvinskyi/magento2-deferjs/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/hryvinskyi/magento2-deferjs/badges/build.png?b=master)](https://scrutinizer-ci.com/g/hryvinskyi/magento2-deferjs/build-status/master)
[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Fhryvinskyi%2Fmagento2-deferjs.svg?type=shield)](https://app.fossa.com/projects/git%2Bgithub.com%2Fhryvinskyi%2Fmagento2-deferjs?ref=badge_shield)


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

# Features

- Ability to skip javascripts with a special tag that can be set in the admin panel
- Built-in skipping move Google tag manager (If you use a third-party module and can not add a special tag)
- Exclude by controllers from defer parsing of JavaScript.

     *Controller will be unaffected by defer js. Use like: [module]_[controller]_[action] (Example: cms_index_index)*

- Exclude by store paths from defer parsing of JavaScript.

     *Paths will be unaffected by defer js. Use like: /women/tops-women/jackets-women.html*


- Exclude by url pattern from defer parsing of JavaScript.

    *URL pattern can be a full action name or a request path. Wildcards are allowed. Like:*
    
    ```
    *cell-phones*
    *cell-phones/nokia-2610-phone.html
    customer_account_*
    /customer/account/*
    *?mode=list
    ```
    
- Minification of moved javascripts
- Optimize text/x-magento-init scripts
- Ability to extend the module with your validator
- Increased rendering time improves the Google Page Speed score.

# Add custom validator
To add your validator:

1. Create Validator file `app/code/Vendor/Module/Model/PassesValidator/Validators/YourValidator.php`:

    ```php
    <?php
    
    declare(strict_types=1);
    
    namespace Vendor\Module\Model\PassesValidator\Validators;
    
    use Hryvinskyi\DeferJs\Model\PassesValidator\ValidatorInterface;
    use Magento\Framework\App\Response\Http;
    
    /**
     * Class YourValidator
     */
    class YourValidator implements ValidatorInterface
    {
        /**
         * Validator function, handle javascript or not
         *
         * @param string $script
         * @param Http $http
         *
         * @return bool
         */
        public function validate(string $script, Http $http): bool
        {
            // Your code validate
        }
    }
    ```

2. Create Dependency Injection file `app/code/Vendor/Module/etc/frontend/di.xml`:

    ```xml
    <?xml version="1.0"?>
    
    <config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    
        <virtualType name="deferJsPassesValidators">
            <arguments>
                <argument name="entityTypes" xsi:type="array">
                    <item name="yourValidationName"
                          xsi:type="object">Vendor\Module\Model\PassesValidator\Validators\YourValidator</item>
                </argument>
            </arguments>
        </virtualType>
    </config>
    ```

3. Run command:
    ```
    php bin/magento setup:di:compile
    ```


## License
[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Fhryvinskyi%2Fmagento2-deferjs.svg?type=large)](https://app.fossa.com/projects/git%2Bgithub.com%2Fhryvinskyi%2Fmagento2-deferjs?ref=badge_large)