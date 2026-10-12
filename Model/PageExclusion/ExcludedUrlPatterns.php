<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model\PageExclusion;

use Hryvinskyi\DeferJs\Api\ConfigInterface;
use Hryvinskyi\DeferJs\Api\PageExclusionInterface;

/**
 * Leaves a page untouched when a configured wildcard pattern matches its request URI or its full action name.
 *
 * In a pattern `*` stands for any run of characters, none included; every other character is taken literally.
 * A pattern matches only the whole string.
 */
class ExcludedUrlPatterns implements PageExclusionInterface
{
    /**
     * @param ConfigInterface $config the module's settings
     */
    public function __construct(
        private readonly ConfigInterface $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isExcluded(string $actionName, string $requestUri): bool
    {
        foreach ($this->config->getExcludedUrlPatterns() as $pattern) {
            $regex = $this->toRegex($pattern);
            if (preg_match($regex, $requestUri) === 1 || preg_match($regex, $actionName) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Turn a wildcard pattern into a regular expression anchored at both ends of the subject.
     *
     * @param string $pattern the wildcard pattern
     * @return non-empty-string
     */
    private function toRegex(string $pattern): string
    {
        return '~\A' . str_replace('\*', '.*', preg_quote($pattern, '~')) . '\z~s';
    }
}
