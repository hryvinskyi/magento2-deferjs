<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Plugin;

use Closure;
use Hryvinskyi\DeferJs\Helper\Config;
use Hryvinskyi\DeferJs\Model\MoveJsToFooterInterface;
use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class MoveJsToFooter
 */
class MoveJsToFooter
{
    /**
     * Configuration Module
     *
     * @var Config
     */
    private $config;

    /**
     * Request HTTP
     *
     * @var RequestHttp
     */
    private $request;

    /**
     * Mover Js
     *
     * @var MoveJsToFooterInterface
     */
    private $moveJsToFooter;

    /**
     * MoveJsToFooter constructor.
     *
     * @param Config $config
     * @param RequestHttp $request
     * @param MoveJsToFooterInterface $moveJsToFooter
     */
    public function __construct(
        Config $config,
        RequestHttp $request,
        MoveJsToFooterInterface $moveJsToFooter
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->moveJsToFooter = $moveJsToFooter;
    }

    /**
     * @param ResultInterface $subject
     * @param Closure $proceed
     * @param Http $response
     *
     * @return string
     */
    public function aroundRenderResult(
        ResultInterface $subject,
        Closure $proceed,
        Http $response
    ) {
        $result = $proceed($response);

        if (!$this->config->isEnabled() || PHP_SAPI === 'cli' || $this->request->isXmlHttpRequest()) {
            return $result;
        }

        $this->moveJsToFooter->execute($response);

        return $result;
    }
}