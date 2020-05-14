<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
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
use Magento\Framework\App\Response\Http;

/**
 * Class MoveJsToFooter
 */
class MoveJsToFooter implements MoveJsToFooterInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var MinifyJsInterface
     */
    private $minifyJs;

    /**
     * @var ValidateSkipper
     */
    private $validateSkipper;

    /**
     * MoveJsToFooter constructor.
     *
     * @param Config $config
     * @param MinifyJsInterface $minifyJs
     * @param ValidateSkipper $validateSkipper
     */
    public function __construct(
        Config $config,
        MinifyJsInterface $minifyJs,
        ValidateSkipper $validateSkipper
    ) {
        $this->config = $config;
        $this->minifyJs = $minifyJs;
        $this->validateSkipper = $validateSkipper;
    }

    /**
     * @param Http $http
     *
     * @return void
     */
    public function execute(Http $http)
    {
        $scripts = [];
        $html = $http->getBody();

        $jsons = [];
        $scriptStart = '<script';
        $scriptEnd = '</script>';

        $start = 0;
        $i = 0;
        while (($start = stripos($html, $scriptStart, $start)) !== false) {
            $end = stripos($html, $scriptEnd, $start);

            if ($end === false) {
                break;
            }

            $len = $end + strlen($scriptEnd) - $start;
            $script = substr($html, $start, $len);

            if (
                $this->config->isOptimizeXMagentoInitScripts() &&
                strpos($script, 'text/x-magento-init') !== false
            ) {
                $jsons[] = strip_tags($script);
                $html = str_replace($script, '', $html);
                continue;
            }

            if ($this->validateSkipper->execute($script, $http)) {
                $start++;
                continue;
            }

            $html = str_replace($script, '', $html);
            $scripts[] = $script;

            $i++;
        }

        if ($this->config->isMinifyBodyScript()) {
            $scripts = $this->minifyJs->execute($scripts);
        }

        $merged = [];
        foreach ($jsons as $json) {
            $json = Json::decode($json);
            $merged = ArrayHelper::merge($merged, $json);
        }

        if (count($merged) > 0) {
            $merged = '<script type=text/x-magento-init>' . Json::encode($merged) . '</script>';
        } else {
            $merged = '';
        }

        $scripts = implode('', $scripts);

        if ($endBody = stripos($html, '</body>')) {
            $html = substr($html, 0, $endBody) . $merged . $scripts . substr($html, $endBody);
        } else {
            $html .= $merged . $scripts;
        }

        $http->setBody($html);
    }
}