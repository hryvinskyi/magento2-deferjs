<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model\Minify;

use JShrink\Minifier;

/**
 * Class MinifyJs
 */
class MinifyJs implements MinifyJsInterface
{
    /**
     * MinifyJs constructor.
     */
    public function __construct() {

    }

    /**
     * @param array $scripts
     *
     * @return array
     * @throws \Exception
     */
    public function execute(array $scripts): array
    {
        foreach ($scripts as &$script) {
            try {
                $script = Minifier::minify($script);
            } catch (\Exception $exception) {
                // Remove comments
                $script = preg_replace("/[^:']\/\/.*/", '', $script);
            }

            $script = preg_replace('!\s+!', ' ', $script);
        }

        return $scripts;
    }
}