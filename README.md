# Magento 2 Defer JavaScripts

The module moves javascripts to the bottom of the page.

[![Latest Stable Version](https://poser.pugx.org/hryvinskyi/magento2-deferjs/v/stable)](https://packagist.org/packages/hryvinskyi/magento2-deferjs)
[![Total Downloads](https://poser.pugx.org/hryvinskyi/magento2-deferjs/downloads)](https://packagist.org/packages/hryvinskyi/magento2-deferjs)
[![PayPal donate button](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=volodymyr%40hryvinskyi%2ecom&lc=UA&item_name=Magento%202%20Defer%20Javascript&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted "Donate once-off to this project using Paypal")
[![Latest Unstable Version](https://poser.pugx.org/hryvinskyi/magento2-deferjs/v/unstable)](https://packagist.org/packages/hryvinskyi/magento2-deferjs)
[![License](https://poser.pugx.org/hryvinskyi/magento2-deferjs/license)](https://packagist.org/packages/hryvinskyi/magento2-deferjs)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/hryvinskyi/magento2-deferjs/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/hryvinskyi/magento2-deferjs/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/hryvinskyi/magento2-deferjs/badges/build.png?b=master)](https://scrutinizer-ci.com/g/hryvinskyi/magento2-deferjs/build-status/master)

# Why this module

Magento has its own switch for this, *Stores → Configuration → Advanced → Developer → JavaScript Settings →
Move JS code to the bottom of the page* (`dev/js/move_script_to_bottom`). This module does the same job faster,
without losing scripts, and with control over what stays where.

## Performance

Time to move the scripts of real storefront pages (Magento 2.4.6, PHP 8.1, best of 5 × 200 runs):

| Page | Size | Scripts | Magento built-in | 1.3.3 | 2.0.0 |
|---|---|---|---|---|---|
| Home | 389 KB | 95 | 2.47 ms | 1.25 ms | **0.16 ms** |
| Category | 481 KB | 110 | 3.45 ms | 1.61 ms | **0.18 ms** |
| Product | 559 KB | 121 | 4.68 ms | 1.90 ms | **0.21 ms** |

2.0.0 is 15–22 times faster than the built-in option and 8–9 times faster than 1.3.3. The page is scanned once
with plain string search and rebuilt once from the parts that stay. The built-in option and 1.3.3 copy the whole
page for every script they move. Page exclusions are decided once per request, and an excluded page is not
scanned at all.

The work happens only when a page is rendered, that is on a full-page-cache miss. Cached pages never reach it.

## Compared with Magento's built-in option

| | Magento built-in | This module |
|---|---|---|
| Identical script tags | All copies are cut out and only one is added back, so repeated scripts are lost (one script was lost on the category page measured above) | Every tag is kept, in its order |
| Keep a script in place | Only `text/x-magento-template` | An attribute of your choice (`data-deferjs="false"` by default), Google Tag Manager built in, and your own rules |
| Leave a page alone | Not possible | By controller, by path, by URL pattern, and your own rules |
| Extension points | None | `ScriptExclusionInterface` and `PageExclusionInterface` pools, added from `di.xml` |
| Where it is configured | The Developer section, which the admin hides in production mode | Its own section, available in every mode |
| Minify moved inline scripts | No | Optional, inline JavaScript only, and the original is kept when the result would break the tag |
| Merge `text/x-magento-init` scripts | No | Optional |

Use one or the other: leave the built-in option off while this module is enabled.


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

Only HTML responses are processed. The plugin is an `around` plugin on
`Magento\Framework\Controller\ResultInterface::renderResult` (name `Hryvinskyi_DeferJs::move_js_to_footer`,
sort order `10000000`), so the page is rewritten before any later plugin stores the rendered response.

# Features

- Ability to skip javascripts with a special tag that can be set in the admin panel
- Built-in skipping move Google tag manager (If you use a third-party module and can not add a special tag)
- Exclude by controllers from defer parsing of JavaScript.

     *Controller will be unaffected by defer js. Use like: [module]_[controller]_[action] (Example: cms_index_index)*

- Exclude by store paths from defer parsing of JavaScript.

     *Paths will be unaffected by defer js. Use like: /women/tops-women/jackets-women.html*


- Exclude by url pattern from defer parsing of JavaScript.

    *URL pattern is matched against the request URI or the full action name. Wildcards are allowed. Like:*
    
    ```
    *cell-phones*
    *cell-phones/nokia-2610-phone.html
    customer_account_*
    /customer/account/*
    *?mode=list
    ```
    
- Minification of moved inline javascripts
- Optimize text/x-magento-init scripts
- Ability to extend the module with your own script and page exclusions
- The browser renders the content before it loads the scripts, which improves the Google Page Speed score.

# Extending the module

There are two extension points, split by what they look at:

- a *script exclusion* looks at one script tag and keeps that script where it is;
- a *page exclusion* looks at the request and leaves the whole page untouched (it is not even scanned).

Both are pools: a class that takes named items in its `exclusions` argument, and a script or page is kept when
**any** item says so. `ConfigInterface`, `PageExclusionInterface` and `ScriptExclusionInterface` are the
published (`@api`) contracts; everything else in the package is internal.

Add your item in your module's **global** `etc/di.xml`, never in an area file such as `etc/frontend/di.xml`:
an area file that names the `exclusions` argument replaces the whole array of the global scope, and the
built-in exclusions (and everyone else's) would disappear. Item names must be unique across modules, so prefix
them with your vendor. A class that does not implement the interface makes the pool fail with an
`\InvalidArgumentException` when it is built.

## Keep a script in place

1. Create `app/code/Vendor/Module/Model/DeferJs/KeepConsentScript.php`. `$script` is the whole tag, from
   `<script` to `</script>`:

    ```php
    <?php

    declare(strict_types=1);

    namespace Vendor\Module\Model\DeferJs;

    use Hryvinskyi\DeferJs\Api\ScriptExclusionInterface;

    /**
     * Keeps the consent banner script in the head, where it has to run before the page is painted.
     */
    class KeepConsentScript implements ScriptExclusionInterface
    {
        /**
         * @inheritDoc
         */
        public function isExcluded(string $script): bool
        {
            return str_contains($script, 'data-consent-banner');
        }
    }
    ```

2. Create `app/code/Vendor/Module/etc/di.xml`:

    ```xml
    <?xml version="1.0"?>

    <config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
        <type name="Hryvinskyi\DeferJs\Model\ScriptExclusion\ScriptExclusionPool">
            <arguments>
                <argument name="exclusions" xsi:type="array">
                    <item name="vendorModuleKeepConsentScript"
                          xsi:type="object">Vendor\Module\Model\DeferJs\KeepConsentScript</item>
                </argument>
            </arguments>
        </type>
    </config>
    ```

3. Run `php bin/magento setup:di:compile`.

The built-in items are `googleTagManager` and `disableAttribute`.

## Leave a whole page alone

1. Create `app/code/Vendor/Module/Model/DeferJs/LeaveAccountPages.php`. `$actionName` is
   `<module>_<controller>_<action>`; `$requestUri` is the request URI with its query string:

    ```php
    <?php

    declare(strict_types=1);

    namespace Vendor\Module\Model\DeferJs;

    use Hryvinskyi\DeferJs\Api\PageExclusionInterface;

    /**
     * Leaves the customer account pages untouched: their scripts stay where the layout put them.
     */
    class LeaveAccountPages implements PageExclusionInterface
    {
        /**
         * @inheritDoc
         */
        public function isExcluded(string $actionName, string $requestUri): bool
        {
            return str_starts_with($actionName, 'customer_account_');
        }
    }
    ```

2. Add it to the page pool in `app/code/Vendor/Module/etc/di.xml`:

    ```xml
    <?xml version="1.0"?>

    <config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
        <type name="Hryvinskyi\DeferJs\Model\PageExclusion\PageExclusionPool">
            <arguments>
                <argument name="exclusions" xsi:type="array">
                    <item name="vendorModuleLeaveAccountPages"
                          xsi:type="object">Vendor\Module\Model\DeferJs\LeaveAccountPages</item>
                </argument>
            </arguments>
        </type>
    </config>
    ```

3. Run `php bin/magento setup:di:compile`.

The built-in items are `excludedControllers`, `excludedPaths` and `excludedUrlPatterns`, fed by the settings in
the admin panel.

# Upgrading from 1.x

Version 2.0.0 is a major release; the PHP contract for extensions changed.

**Breaking changes**

- PHP 8.1 or newer is required.
- The validators are split into *page exclusions* (decided once per request) and *script exclusions* (decided
  per script tag). `Hryvinskyi\DeferJs\Model\PassesValidator\ValidatorInterface`, the `deferJsPassesValidators`
  virtual type and `Hryvinskyi\DeferJs\Helper\Config` are removed. Port a 1.x validator to
  `ScriptExclusionInterface` (or `PageExclusionInterface`) as shown above and register it in a global
  `etc/di.xml`. Read settings through `Hryvinskyi\DeferJs\Api\ConfigInterface`.
- Run `php bin/magento setup:di:compile` after upgrading: classes were removed and constructors changed. The
  plugin is still an `around` plugin on `renderResult` with the same name and sort order, so nothing that is
  ordered against it changes.

**Behaviour changes**

- Pages rendered from the command line (an in-process render of a storefront page) are processed too; 1.x
  skipped every CLI run.
- Only HTML responses are processed: a response without a `Content-Type` header, with an empty one, or with one
  starting with `text/html`. JSON and other responses are left alone.
- An empty "Disable move attribute" setting no longer excludes every script; it excludes nothing.
- Exclude Url Pattern also matches the action name, as the setting's description always said.
- Minification touches only the content of inline JavaScript (no `src`, and no `type` or a JavaScript one). It no
  longer minifies attributes, JSON or templates, and no longer collapses whitespace inside string literals. When
  the minifier fails, or when its output would hold a `</script` or `<!--` the source did not (either would break
  the script element), the script is moved as it was.
- Tags such as `<scripts>` or `<script-x>` are no longer taken for scripts.
- A page holding a very large inline script (over about 1 MB under PHP's default `pcre.backtrack_limit`) is
  processed completely; 1.x stopped at that script and left it and every script after it in place.

**With "Enabled optimize x-magento-init scripts" on**

- Scripts carrying the disable attribute, and scripts of an excluded page, are no longer merged.
- An x-magento-init script whose JSON is not an object is moved like any other script instead of being dropped.
- HTML inside JSON string values survives.
- The tags are merged in document order, each later object into what came before: two JSON objects merge
  property by property, recursively; two JSON lists are joined, the earlier items first; any other pair takes the
  later value (1.x let the first one win). Object keys that look like numbers, such as `"123"`, are merged as keys
  instead of being renumbered, and an empty object `{}` stays an object.
- `<` and `>` in merged values are escaped, so a value containing `</script>` can no longer end the tag early.
