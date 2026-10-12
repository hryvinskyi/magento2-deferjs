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
 * Leaves a page untouched when its full action name is one of the configured excluded controllers.
 */
class ExcludedControllers implements PageExclusionInterface
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
        return in_array($actionName, $this->config->getExcludedControllers(), true);
    }
}
