<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model\PageExclusion;

use Hryvinskyi\DeferJs\Api\PageExclusionInterface;

/**
 * Leaves a page untouched when any of the page exclusions it is given says so.
 *
 * The members come from the `exclusions` argument in `etc/di.xml`; a member that is not a page exclusion fails
 * loudly when the pool is built rather than being skipped.
 */
class PageExclusionPool implements PageExclusionInterface
{
    /**
     * @var list<PageExclusionInterface>
     */
    private readonly array $exclusions;

    /**
     * @param array<array-key, mixed> $exclusions the page exclusions by name, each a PageExclusionInterface
     * @throws \InvalidArgumentException when a member is not a PageExclusionInterface
     */
    public function __construct(array $exclusions = [])
    {
        $members = [];
        foreach ($exclusions as $name => $exclusion) {
            if (!$exclusion instanceof PageExclusionInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Page exclusion "%s" must implement %s, %s given.',
                    $name,
                    PageExclusionInterface::class,
                    get_debug_type($exclusion)
                ));
            }
            $members[] = $exclusion;
        }

        $this->exclusions = $members;
    }

    /**
     * @inheritDoc
     */
    public function isExcluded(string $actionName, string $requestUri): bool
    {
        foreach ($this->exclusions as $exclusion) {
            if ($exclusion->isExcluded($actionName, $requestUri)) {
                return true;
            }
        }

        return false;
    }
}
