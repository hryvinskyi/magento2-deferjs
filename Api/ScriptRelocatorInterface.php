<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Api;

/**
 * Moves the script tags of a rendered page to just before its closing body tag.
 */
interface ScriptRelocatorInterface
{
    /**
     * Rewrite a page so that every script tag no script exclusion keeps in place sits before `</body>`.
     *
     * The moved tags keep their text and document order and are joined by `PHP_EOL`. They are inserted before
     * the first `</body>` of the page that remains once they are removed, or appended when there is none. A page
     * where nothing moves comes back unchanged.
     *
     * @param string $html the rendered page
     * @return string the rewritten page
     */
    public function relocate(string $html): string;
}
