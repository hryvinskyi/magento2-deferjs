<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Plugin;

use Hryvinskyi\DeferJs\Api\DeferPageScriptsInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Moves the scripts of a rendered result's page to the end of its body.
 *
 * It wraps the render rather than following it because the page must be rewritten before anything that stores
 * the rendered response sees it: every after-plugin ordered below this one runs only once this one returns.
 */
class MoveJsToFooter
{
    /**
     * @param DeferPageScriptsInterface $deferPageScripts rewrites the rendered page
     */
    public function __construct(
        private readonly DeferPageScriptsInterface $deferPageScripts
    ) {
    }

    /**
     * Render the result, then move the scripts of the page it rendered into the response.
     *
     * @param ResultInterface $subject the result being rendered
     * @param \Closure $proceed renders the result into the response
     * @param ResponseInterface $response the response the result renders into
     * @return mixed the value the render returned, unchanged
     */
    public function aroundRenderResult(
        ResultInterface $subject,
        \Closure $proceed,
        ResponseInterface $response
    ): mixed {
        $result = $proceed($response);
        $this->deferPageScripts->execute($response);

        return $result;
    }
}
