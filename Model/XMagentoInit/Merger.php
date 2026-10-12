<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model\XMagentoInit;

use Hryvinskyi\DeferJs\Api\ScriptTagReaderInterface;
use Hryvinskyi\DeferJs\Api\XMagentoInitMergerInterface;

/**
 * Merges the JSON objects of x-magento-init tags into one tag, in document order.
 *
 * Each tag's content is decoded as it is, so HTML inside a JSON string value survives, and JSON objects stay
 * distinct from JSON lists: a key such as "123" is an object key like any other, and `{}` stays an object. The
 * tags merge one by one, each later value into what came before, by these rules:
 * - two JSON objects merge property by property, recursively; a property only one side has is kept as it is, in
 *   the position it first appeared;
 * - two JSON lists concatenate, the earlier items first;
 * - any other pair (a scalar, null, or an object meeting a list) takes the later value.
 * The merged JSON is written with `<` and `>` escaped, so a value holding `</script>` cannot end the tag early.
 */
class Merger implements XMagentoInitMergerInterface
{
    private const ENCODE_FLAGS = JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

    /**
     * @param ScriptTagReaderInterface $tagReader finds each tag's content
     */
    public function __construct(
        private readonly ScriptTagReaderInterface $tagReader
    ) {
    }

    /**
     * @inheritDoc
     *
     * @throws \JsonException when the merged data cannot be encoded, which data decoded from JSON never causes
     */
    public function merge(array $scripts): array
    {
        $merged = null;
        $unmerged = [];
        foreach ($scripts as $script) {
            $object = $this->decodeObject($this->tagReader->split($script)['content']);
            if ($object === null) {
                $unmerged[] = $script;
                continue;
            }
            $merged = $merged === null ? $object : $this->mergeObjects($merged, $object);
        }

        return [
            'merged' => $merged === null || get_object_vars($merged) === []
                ? ''
                : '<script type="text/x-magento-init">' . json_encode($merged, self::ENCODE_FLAGS) . '</script>',
            'unmerged' => $unmerged,
        ];
    }

    /**
     * Decode a tag's content when it is a JSON object.
     *
     * @param string $content the text between the opening tag and `</script>`
     * @return \stdClass|null the decoded object, or null when the content is not a JSON object
     */
    private function decodeObject(string $content): ?\stdClass
    {
        if (!str_starts_with(ltrim($content), '{')) {
            return null;
        }

        try {
            $decoded = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return $decoded instanceof \stdClass ? $decoded : null;
    }

    /**
     * Merge a later value into an earlier one.
     *
     * @param mixed $earlier the value merged so far
     * @param mixed $later the value from a later tag
     * @return mixed the merged value
     */
    private function mergeValues(mixed $earlier, mixed $later): mixed
    {
        if ($earlier instanceof \stdClass && $later instanceof \stdClass) {
            return $this->mergeObjects($earlier, $later);
        }

        if (is_array($earlier) && is_array($later)) {
            return array_merge($earlier, $later);
        }

        return $later;
    }

    /**
     * Merge a later object into an earlier one, property by property, without changing either.
     *
     * @param \stdClass $earlier the object merged so far
     * @param \stdClass $later the object from a later tag
     * @return \stdClass the merged object
     */
    private function mergeObjects(\stdClass $earlier, \stdClass $later): \stdClass
    {
        $merged = clone $earlier;
        foreach (get_object_vars($later) as $key => $value) {
            $property = (string) $key;
            $merged->{$property} = property_exists($merged, $property)
                ? $this->mergeValues($merged->{$property}, $value)
                : $value;
        }

        return $merged;
    }
}
