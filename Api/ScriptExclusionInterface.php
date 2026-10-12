<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Api;

/**
 * Decides that one script tag stays where it is instead of moving to the end of the body.
 *
 * Add an implementation as an item of the `exclusions` argument of
 * `Hryvinskyi\DeferJs\Model\ScriptExclusion\ScriptExclusionPool` in a global `etc/di.xml`; the script stays
 * when any item excludes it.
 *
 * @api
 */
interface ScriptExclusionInterface
{
    /**
     * Whether this script tag stays in place.
     *
     * @param string $script the whole tag, from `<script` to `</script>`
     * @return bool
     */
    public function isExcluded(string $script): bool;
}
