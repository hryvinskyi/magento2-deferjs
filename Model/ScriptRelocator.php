<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model;

use Hryvinskyi\DeferJs\Api\ConfigInterface;
use Hryvinskyi\DeferJs\Api\ScriptExclusionInterface;
use Hryvinskyi\DeferJs\Api\ScriptMinifierInterface;
use Hryvinskyi\DeferJs\Api\ScriptRelocatorInterface;
use Hryvinskyi\DeferJs\Api\ScriptTagReaderInterface;
use Hryvinskyi\DeferJs\Api\XMagentoInitMergerInterface;

/**
 * Moves a page's script tags before its closing body tag in a single pass over the page.
 *
 * A script tag is `<script` followed by whitespace (space, tab, line feed, vertical tab, form feed, carriage
 * return), `>` or `/`, up to the first `</script>` after it, case-insensitively and across lines; `<scripts>` or
 * `<script-x>` is not one, and a `<script` with no `</script>` after it ends the search. The page is searched as
 * plain text, so a script of any size is found. A tag a script exclusion keeps stays where it is; every other tag
 * is cut out, and the moved tags are inserted before the first `</body>` of what remains, so a `</body>` inside a
 * moved tag never counts.
 *
 * With x-magento-init merging on, the moved `type="text/x-magento-init"` tags are merged into one tag that leads
 * the moved block, directly followed by the other moved tags; a tag whose JSON is not an object moves as an
 * ordinary tag in its document position. With minification on, the moved ordinary tags pass through the minifier;
 * the merged tag never does.
 */
class ScriptRelocator implements ScriptRelocatorInterface
{
    private const TAG_OPEN = '<script';
    private const TAG_CLOSE = '</script>';
    private const TAG_NAME_END_BYTES = " \t\n\x0B\f\r>/";
    private const BODY_END = '</body>';
    private const X_MAGENTO_INIT_TYPE = 'text/x-magento-init';

    /**
     * @param ScriptExclusionInterface $scriptExclusion decides which tags stay in place
     * @param ConfigInterface $config tells whether to merge x-magento-init tags and minify moved tags
     * @param XMagentoInitMergerInterface $merger merges the moved x-magento-init tags
     * @param ScriptMinifierInterface $minifier minifies a moved tag
     * @param ScriptTagReaderInterface $tagReader reads a tag's type
     */
    public function __construct(
        private readonly ScriptExclusionInterface $scriptExclusion,
        private readonly ConfigInterface $config,
        private readonly XMagentoInitMergerInterface $merger,
        private readonly ScriptMinifierInterface $minifier,
        private readonly ScriptTagReaderInterface $tagReader
    ) {
    }

    /**
     * @inheritDoc
     */
    public function relocate(string $html): string
    {
        $merge = $this->config->isMergeXMagentoInit();
        $kept = '';
        $cursor = 0;
        $moved = [];
        $initTags = [];
        foreach ($this->findScriptTags($html) as $offset => $script) {
            if ($this->scriptExclusion->isExcluded($script)) {
                continue;
            }

            $kept .= substr($html, $cursor, $offset - $cursor);
            $cursor = $offset + strlen($script);
            if ($merge && $this->isXMagentoInit($script)) {
                $initTags[count($moved)] = $script;
            }
            $moved[] = $script;
        }

        if ($moved === []) {
            return $html;
        }
        $kept .= substr($html, $cursor);

        return $this->insertBeforeBodyEnd($kept, $this->buildMovedBlock($moved, $initTags));
    }

    /**
     * Find the page's script tags, in document order.
     *
     * @param string $html the page
     * @return \Generator<int, string, mixed, void> each whole tag, keyed by its byte offset in the page
     */
    private function findScriptTags(string $html): \Generator
    {
        // One lower-cased copy searched with strpos(): stripos() would lower-case the whole page again on every
        // call. Lower-casing maps byte to byte, so an offset in the copy is the same offset in the page.
        $folded = strtolower($html);
        $openLength = strlen(self::TAG_OPEN);
        $offset = 0;
        while (($start = strpos($folded, self::TAG_OPEN, $offset)) !== false) {
            $nameEnd = substr($folded, $start + $openLength, 1);
            if ($nameEnd === '') {
                return;
            }
            if (!str_contains(self::TAG_NAME_END_BYTES, $nameEnd)) {
                $offset = $start + 1;
                continue;
            }

            $close = strpos($folded, self::TAG_CLOSE, $start + $openLength);
            if ($close === false) {
                return;
            }

            $offset = $close + strlen(self::TAG_CLOSE);
            yield $start => substr($html, $start, $offset - $start);
        }
    }

    /**
     * Build the text inserted before the closing body tag.
     *
     * @param list<string> $moved every moved tag, in document order
     * @param array<int, string> $initTags the x-magento-init tags to merge, keyed by their position in $moved
     * @return string
     */
    private function buildMovedBlock(array $moved, array $initTags): string
    {
        $merged = '';
        $ordinary = $moved;
        if ($initTags !== []) {
            $result = $this->merger->merge(array_values($initTags));
            $merged = $result['merged'];
            $ordinary = $this->withoutMergedTags($moved, $initTags, $result['unmerged']);
        }

        if ($this->config->isMinifyBodyScripts()) {
            $ordinary = array_map($this->minifier->minify(...), $ordinary);
        }

        return $merged . implode(PHP_EOL, $ordinary);
    }

    /**
     * Drop the x-magento-init tags the merger took, keeping the ones it handed back in their document position.
     *
     * The tags handed back are a subsequence of the tags given to the merger, so they are matched in order.
     *
     * @param list<string> $moved every moved tag, in document order
     * @param array<int, string> $initTags the x-magento-init tags given to the merger, keyed by position in $moved
     * @param list<string> $unmerged the tags the merger did not merge, in document order
     * @return list<string>
     */
    private function withoutMergedTags(array $moved, array $initTags, array $unmerged): array
    {
        $ordinary = [];
        $next = 0;
        foreach ($moved as $position => $script) {
            if (isset($initTags[$position])) {
                if (($unmerged[$next] ?? null) !== $script) {
                    continue;
                }
                $next++;
            }
            $ordinary[] = $script;
        }

        return $ordinary;
    }

    /**
     * Whether a tag's opening tag declares `type="text/x-magento-init"`.
     *
     * @param string $script the whole tag
     * @return bool
     */
    private function isXMagentoInit(string $script): bool
    {
        $type = $this->tagReader->getAttribute($this->tagReader->split($script)['opening'], 'type');

        return $type !== null && strcasecmp($type, self::X_MAGENTO_INIT_TYPE) === 0;
    }

    /**
     * Insert the moved block before the first closing body tag, or append it when there is none.
     *
     * @param string $html the page without the moved tags
     * @param string $block the moved tags
     * @return string
     */
    private function insertBeforeBodyEnd(string $html, string $block): string
    {
        $position = stripos($html, self::BODY_END);
        if ($position === false) {
            return $html . $block;
        }

        return substr($html, 0, $position) . $block . substr($html, $position);
    }
}
