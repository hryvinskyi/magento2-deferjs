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
 * Keeps in place a script tag that mentions the Google Tag Manager host, `googletagmanager.com`.
 *
 * The match is case-sensitive.
 */
class GoogleTagManager implements ScriptExclusionInterface
{
    private const HOST = 'googletagmanager.com';

    /**
     * @inheritDoc
     */
    public function isExcluded(string $script): bool
    {
        return str_contains($script, self::HOST);
    }
}
