<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model\ScriptExclusion;

use Hryvinskyi\DeferJs\Api\ScriptExclusionInterface;

/**
 * Keeps a script tag in place when any of the script exclusions it is given says so.
 *
 * The members come from the `exclusions` argument in `etc/di.xml`; a member that is not a script exclusion fails
 * loudly when the pool is built rather than being skipped.
 */
class ScriptExclusionPool implements ScriptExclusionInterface
{
    /**
     * @var list<ScriptExclusionInterface>
     */
    private readonly array $exclusions;

    /**
     * @param array<array-key, mixed> $exclusions the script exclusions by name, each a ScriptExclusionInterface
     * @throws \InvalidArgumentException when a member is not a ScriptExclusionInterface
     */
    public function __construct(array $exclusions = [])
    {
        $members = [];
        foreach ($exclusions as $name => $exclusion) {
            if (!$exclusion instanceof ScriptExclusionInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Script exclusion "%s" must implement %s, %s given.',
                    $name,
                    ScriptExclusionInterface::class,
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
    public function isExcluded(string $script): bool
    {
        foreach ($this->exclusions as $exclusion) {
            if ($exclusion->isExcluded($script)) {
                return true;
            }
        }

        return false;
    }
}
