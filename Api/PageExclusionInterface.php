<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Api;

/**
 * Decides, once per request, that a whole page keeps all its scripts where they are.
 *
 * An excluded page is returned untouched. Add an implementation as an item of the `exclusions` argument of
 * `Hryvinskyi\DeferJs\Model\PageExclusion\PageExclusionPool` in a global `etc/di.xml`; the page is excluded
 * when any item excludes it.
 *
 * @api
 */
interface PageExclusionInterface
{
    /**
     * Whether the page of this request is left untouched.
     *
     * @param string $actionName the full action name, `<module>_<controller>_<action>`
     * @param string $requestUri the request URI, query string included
     * @return bool
     */
    public function isExcluded(string $actionName, string $requestUri): bool;
}
