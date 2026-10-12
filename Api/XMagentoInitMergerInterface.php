<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Api;

/**
 * Merges the JSON of several `text/x-magento-init` script tags into one tag.
 */
interface XMagentoInitMergerInterface
{
    /**
     * Merge the tags whose content is a JSON object, in document order; hand back the others untouched.
     *
     * @param list<string> $scripts whole x-magento-init tags, in document order
     * @return array{merged: string, unmerged: list<string>} merged is one x-magento-init tag holding the merged
     *         JSON, or '' when the merged result is empty; unmerged are the tags whose content is not a JSON
     *         object, in the order they were given
     */
    public function merge(array $scripts): array;
}
