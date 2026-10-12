<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model;

use Hryvinskyi\DeferJs\Api\ConfigInterface;
use Hryvinskyi\DeferJs\Api\DeferPageScriptsInterface;
use Hryvinskyi\DeferJs\Api\PageExclusionInterface;
use Hryvinskyi\DeferJs\Api\ScriptRelocatorInterface;
use Laminas\Http\Header\HeaderInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\ResponseInterface;

/**
 * Moves the scripts of a rendered storefront page to the end of its body, unless the page is to be left alone.
 *
 * Only a full page is rewritten: a request made by a script (the `X-Requested-With` header, or an `ajax` or
 * `isAjax` parameter) and a response that declares a content type other than HTML are left as they are. A
 * response without a content type, or with an empty one, is HTML, which is what PHP sends by default. An excluded
 * page is not even scanned.
 */
class DeferPageScripts implements DeferPageScriptsInterface
{
    private const HTML_CONTENT_TYPE = 'text/html';

    /**
     * @param ConfigInterface $config tells whether the module is enabled
     * @param RequestInterface $request the request whose page is rendered
     * @param PageExclusionInterface $pageExclusion decides whether the whole page is left alone
     * @param ScriptRelocatorInterface $scriptRelocator rewrites the page
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly RequestInterface $request,
        private readonly PageExclusionInterface $pageExclusion,
        private readonly ScriptRelocatorInterface $scriptRelocator
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(ResponseInterface $response): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $request = $this->request;
        if (!$request instanceof HttpRequest || $request->isAjax()) {
            return;
        }

        if (!$response instanceof HttpResponse || !$this->isHtml($response)) {
            return;
        }

        $actionName = $request->getModuleName() . '_' . $request->getControllerName() . '_'
            . $request->getActionName();
        if ($this->pageExclusion->isExcluded($actionName, (string) $request->getRequestUri())) {
            return;
        }

        $html = (string) $response->getBody();
        $relocated = $this->scriptRelocator->relocate($html);
        if ($relocated !== $html) {
            $response->setBody($relocated);
        }
    }

    /**
     * Whether the response carries HTML: it declares no content type, an empty one, or one starting with
     * `text/html`, ignoring surrounding whitespace and case.
     *
     * @param HttpResponse $response
     * @return bool
     */
    private function isHtml(HttpResponse $response): bool
    {
        $header = $response->getHeader('Content-Type');
        if (!$header instanceof HeaderInterface) {
            return true;
        }

        $contentType = trim($header->getFieldValue());

        return $contentType === '' || stripos($contentType, self::HTML_CONTENT_TYPE) === 0;
    }
}
