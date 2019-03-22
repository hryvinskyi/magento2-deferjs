<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);


namespace Hryvinskyi\DeferJs\Model;

/**
 * Class MinifyJsInterface
 */
interface MinifyJsInterface
{
    /**
     * @param array $scripts
     *
     * @return array
     */
    public function execute(array $scripts): array;
}