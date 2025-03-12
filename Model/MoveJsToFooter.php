<?php
/**
 * Copyright (c) 2019-2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model;

use Hryvinskyi\Base\Helper\ArrayHelper;
use Hryvinskyi\Base\Helper\Json;
use Hryvinskyi\DeferJs\Helper\Config;
use Hryvinskyi\DeferJs\Model\Minify\MinifyJsInterface;
use Hryvinskyi\DeferJs\Model\PassesValidator\ValidateSkipper;
use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http;

class MoveJsToFooter implements MoveJsToFooterInterface
{
    private Config $config;
    private MinifyJsInterface $minifyJs;
    private ValidateSkipper $validateSkipper;
    private RequestInterface $request;

    public function __construct(
        Config $config,
        MinifyJsInterface $minifyJs,
        ValidateSkipper $validateSkipper,
        RequestInterface $request
    ) {
        $this->config = $config;
        $this->minifyJs = $minifyJs;
        $this->validateSkipper = $validateSkipper;
        $this->request = $request;
    }

    /**
     * Executes the JS to footer movement process on the HTTP response
     *
     * @param Http $http The HTTP response object to process
     * @return void
     */
    public function execute(Http $http): void
    {
        if (!$this->request instanceof RequestHttp || $this->request->isAjax()) {
            return;
        }

        $html = $http->getBody();

        // Process all scripts in a single pass
        $processedHtml = $this->processScripts($html, $http);

        if ($processedHtml !== $html) {
            $http->setBody($processedHtml);
        }
    }

    /**
     * Processes script tags in the HTML to move them to footer
     *
     * @param string $html The original HTML content
     * @param Http $http The HTTP response object
     * @return string The processed HTML with scripts moved to footer
     */
    private function processScripts(string $html, Http $http): string
    {
        // Find all script tags
        preg_match_all('/<script.*?<\/script>/is', $html, $matches, PREG_OFFSET_CAPTURE);

        if (empty($matches[0])) {
            return $html;
        }

        // Prepare collections
        $scriptsToMove = [];
        $jsonScripts = [];
        $positions = [];

        // Process matches in reverse order to avoid offset issues
        foreach (array_reverse($matches[0]) as [$script, $offset]) {
            if ($this->shouldExtractJson($script)) {
                $jsonScripts[] = strip_tags($script);
                $positions[] = [$offset, strlen($script)];
                continue;
            }

            if (!$this->validateSkipper->execute($script, $http)) {
                $scriptsToMove[] = $script;
                $positions[] = [$offset, strlen($script)];
            }
        }

        // If nothing to move, return original
        if (empty($scriptsToMove) && empty($jsonScripts)) {
            return $html;
        }

        // Prepare footer content
        $footerContent = '';

        // Add merged JSON scripts if any
        if (!empty($jsonScripts)) {
            $mergedJson = $this->mergeJsons($jsonScripts);
            if (!empty($mergedJson)) {
                $footerContent .= $mergedJson;
            }
        }

        // Add scripts if any
        if (!empty($scriptsToMove)) {
            if ($this->config->isMinifyBodyScript()) {
                $footerContent .= implode(PHP_EOL, $this->minifyJs->execute(array_reverse($scriptsToMove)));
            } else {
                $footerContent .= implode(PHP_EOL, array_reverse($scriptsToMove));
            }
        }

        // Remove scripts from original positions
        foreach ($positions as [$pos, $len]) {
            $html = substr_replace($html, '', $pos, $len);
        }

        // Insert scripts before closing body tag
        return $this->insertBeforeBodyEnd($html, $footerContent);
    }

    /**
     * Determines if a script should be extracted as JSON
     *
     * @param string $script The script tag to check
     * @return bool True if the script should be extracted as JSON
     */
    private function shouldExtractJson(string $script): bool
    {
        return $this->config->isOptimizeXMagentoInitScripts() &&
            preg_match('/<script[^>]*type=["\']text\/x-magento-init["\'][^>]*>/i', $script);
    }

    /**
     * Merges multiple JSON scripts into a single script
     *
     * @param array $jsons Array of JSON strings to merge
     * @return string The merged JSON in a single script tag or empty string
     */
    private function mergeJsons(array $jsons): string
    {
        $merged = [];
        foreach ($jsons as $json) {
            $data = Json::decode($json);
            if (is_array($data)) {
                $merged = ArrayHelper::merge($merged, $data);
            }
        }

        return !empty($merged)
            ? '<script type="text/x-magento-init">' . Json::encode($merged) . '</script>'
            : '';
    }

    /**
     * Inserts content before the closing body tag
     *
     * @param string $html The HTML to modify
     * @param string $content The content to insert before the body end tag
     * @return string The modified HTML
     */
    private function insertBeforeBodyEnd(string $html, string $content): string
    {
        $bodyEndPos = stripos($html, '</body>');
        return $bodyEndPos !== false
            ? substr_replace($html, $content, $bodyEndPos, 0)
            : $html . $content;
    }
}
