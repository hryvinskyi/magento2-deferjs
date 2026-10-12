<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model\Minify;

use JShrink\Minifier;

/**
 * Minifies JavaScript with the JShrink library.
 */
class JShrinkMinifier implements JavaScriptMinifierInterface
{
    /**
     * @inheritDoc
     */
    public function minify(string $javaScript): string
    {
        $minified = Minifier::minify($javaScript);

        return is_string($minified) ? $minified : $javaScript;
    }
}
