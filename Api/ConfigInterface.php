<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Api;

/**
 * The module's settings, as configured for the current store view.
 *
 * @api
 */
interface ConfigInterface
{
    /**
     * Whether scripts are moved to the end of the body at all.
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * The attribute text that keeps a script in place, trimmed; an empty string when none is configured.
     *
     * @return string
     */
    public function getDisableAttribute(): string;

    /**
     * Whether the inline JavaScript of moved scripts is minified.
     *
     * @return bool
     */
    public function isMinifyBodyScripts(): bool;

    /**
     * Whether the moved x-magento-init scripts are merged into one.
     *
     * @return bool
     */
    public function isMergeXMagentoInit(): bool;

    /**
     * The full action names (`<module>_<controller>_<action>`) whose pages are left untouched.
     *
     * Values are trimmed; blank ones are dropped. A setting that is not a list of rows yields an empty list.
     *
     * @return list<string>
     */
    public function getExcludedControllers(): array;

    /**
     * The request URIs whose pages are left untouched.
     *
     * Values are trimmed; blank ones are dropped. A setting that is not a list of rows yields an empty list.
     *
     * @return list<string>
     */
    public function getExcludedPaths(): array;

    /**
     * The wildcard patterns (`*` matches any run of characters) whose matching pages are left untouched.
     *
     * Values are trimmed; blank ones are dropped. A setting that is not a list of rows yields an empty list.
     *
     * @return list<string>
     */
    public function getExcludedUrlPatterns(): array;
}
