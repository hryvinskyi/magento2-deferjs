<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model\Minify;

/**
 * Minifies JavaScript source text.
 */
interface JavaScriptMinifierInterface
{
    /**
     * Minify JavaScript source.
     *
     * @param string $javaScript the source, without any surrounding tag
     * @return string the minified source
     * @throws \Throwable when the source cannot be minified
     */
    public function minify(string $javaScript): string;
}
