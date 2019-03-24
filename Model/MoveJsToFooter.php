<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model;

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

        $scripts = implode('', $scripts);

        if ($endBody = stripos($html, '</body>')) {
            $html = substr($html, 0, $endBody) . $scripts . substr($html, $endBody);
        } else {
            $html .= $scripts;
        }

        $http->setBody($html);
    }
}