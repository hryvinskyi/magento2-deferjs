<?php
/**
 * Copyright (c) 2019-2024. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
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

/**
 * Class MoveJsToFooter
 */
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

    public function execute(Http $http): void
    {
        $html = $http->getBody();

        if (!$this->request instanceof RequestHttp ||  $this->request->isAjax() === true) {
            return;
        }

        [$scripts, $jsons] = $this->extractScripts($html, $http);

        if ($this->config->isMinifyBodyScript()) {
            $scripts = $this->minifyJs->execute($scripts);
        }

        $mergedJson = $this->mergeJsons($jsons);
        $scripts = implode('', $scripts);

        $html = $this->insertScriptsIntoHtml($html, $mergedJson, $scripts);

        $http->setBody($html);
    }

    private function extractScripts(string &$html, Http $http): array
    {
        $scripts = [];
        $jsons = [];

        preg_match_all('/<script.*?<\/script>/is', $html, $matches, PREG_OFFSET_CAPTURE);
        $offsetAdjustment = 0;

        foreach ($matches[0] as [$script, $offset]) {
            $offset += $offsetAdjustment;

            if ($this->shouldExtractJson($script)) {
                $jsons[] = strip_tags($script);
                $html = substr_replace($html, '', $offset, strlen($script));
                $offsetAdjustment -= strlen($script);
                continue;
            }

            if ($this->validateSkipper->execute($script, $http)) {
                continue;
            }

            $scripts[] = $script;
            $html = substr_replace($html, '', $offset, strlen($script));
            $offsetAdjustment -= strlen($script);
        }

        return [$scripts, $jsons];
    }

    private function shouldExtractJson(string $script): bool
    {
        return $this->config->isOptimizeXMagentoInitScripts() && preg_match('/<script[^>]*type=["\']text\/x-magento-init["\'][^>]*>/i', $script);
    }

    private function mergeJsons(array $jsons): string
    {
        $merged = [];
        foreach ($jsons as $json) {
            $json = Json::decode($json);
            $merged = ArrayHelper::merge($merged, $json);
        }

        return count($merged) > 0
            ? '<script type="text/x-magento-init">' . Json::encode($merged) . '</script>'
            : '';
    }

    private function insertScriptsIntoHtml(string $html, string $mergedJson, string $scripts): string
    {
        $endBody = stripos($html, '</body>');
        if ($endBody !== false) {
            return substr_replace($html, $mergedJson . $scripts, $endBody, 0);
        }

        return $html . $mergedJson . $scripts;
    }
}
