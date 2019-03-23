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

# Features

- Ability to skip javascripts with a special tag that can be set in the admin panel  
- Built-in skipping move Google tag manager (If you use a third-party module and can not add a special tag)  
- Minification of moved javascripts
- Ability to extend the module with your validator

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