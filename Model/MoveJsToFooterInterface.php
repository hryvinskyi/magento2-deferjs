<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model;

interface MoveJsToFooterInterface
{
    /**
     * @param \Magento\Framework\App\Response\Http $http
     *
     * @return void
     */
    public function execute(\Magento\Framework\App\Response\Http $http);
}