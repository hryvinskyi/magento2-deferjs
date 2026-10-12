<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Api;

/**
 * Minifies the inline JavaScript of one script tag.
 */
interface ScriptMinifierInterface
{
    /**
     * Minify the content of an inline JavaScript tag; any other tag comes back unchanged.
     *
     * @param string $script the whole tag, from `<script` to `</script>`
     * @return string the whole tag, its opening and closing tags unchanged
     */
    public function minify(string $script): string;
}
