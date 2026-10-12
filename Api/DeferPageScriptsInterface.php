<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Api;

use Magento\Framework\App\ResponseInterface;

/**
 * Moves the scripts of a rendered storefront page to the end of its body.
 */
interface DeferPageScriptsInterface
{
    /**
     * Rewrite the body of a rendered page so that its scripts sit before `</body>`.
     *
     * The response is left untouched when the module is disabled, the request is not a plain (non-AJAX) HTTP
     * request, the response is not an HTTP response, it declares a content type other than HTML, or a page
     * exclusion leaves the page alone. The body is set only when the rewrite changed it.
     *
     * @param ResponseInterface $response the response holding the rendered page
     * @return void
     */
    public function execute(ResponseInterface $response): void;
}
