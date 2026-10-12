<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model\Minify;

use Hryvinskyi\DeferJs\Api\ScriptMinifierInterface;
use Hryvinskyi\DeferJs\Api\ScriptTagReaderInterface;

/**
 * Minifies the content of inline JavaScript tags and leaves every other tag as it is.
 *
 * A tag is inline JavaScript when it has no `src` attribute and its `type` is absent or one of
 * `text/javascript`, `application/javascript` and `module` (compared case-insensitively). Only the content is
 * minified; the opening and closing tags are kept byte for byte. A tag the minifier fails on is kept as it was,
 * and so is a tag whose minified content gains a `</script` or a `<!--` the original content did not have: inside
 * a script element the first ends the element early and the second can keep it from ending where it should.
 */
class ScriptMinifier implements ScriptMinifierInterface
{
    private const JAVASCRIPT_TYPES = ['text/javascript', 'application/javascript', 'module'];
    private const UNSAFE_SEQUENCES = ['</script', '<!--'];

    /**
     * @param ScriptTagReaderInterface $tagReader splits a tag and reads its attributes
     * @param JavaScriptMinifierInterface $javaScriptMinifier minifies the JavaScript source
     */
    public function __construct(
        private readonly ScriptTagReaderInterface $tagReader,
        private readonly JavaScriptMinifierInterface $javaScriptMinifier
    ) {
    }

    /**
     * @inheritDoc
     */
    public function minify(string $script): string
    {
        $parts = $this->tagReader->split($script);
        if (trim($parts['content']) === '' || !$this->isInlineJavaScript($parts['opening'])) {
            return $script;
        }

        try {
            $content = $this->javaScriptMinifier->minify($parts['content']);
        } catch (\Throwable) {
            return $script;
        }

        if ($this->introducesUnsafeSequence($parts['content'], $content)) {
            return $script;
        }

        return $parts['opening'] . $content . $parts['closing'];
    }

    /**
     * Whether the minified content holds a `</script` or `<!--`, in any case, that the original content did not.
     *
     * @param string $original the content as written
     * @param string $minified the content the minifier returned
     * @return bool
     */
    private function introducesUnsafeSequence(string $original, string $minified): bool
    {
        foreach (self::UNSAFE_SEQUENCES as $sequence) {
            if (stripos($minified, $sequence) !== false && stripos($original, $sequence) === false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether an opening tag introduces inline JavaScript.
     *
     * @param string $openingTag the opening tag, from `<script` to the first `>`
     * @return bool
     */
    private function isInlineJavaScript(string $openingTag): bool
    {
        if ($this->tagReader->getAttribute($openingTag, 'src') !== null) {
            return false;
        }

        $type = $this->tagReader->getAttribute($openingTag, 'type');

        return $type === null || in_array(strtolower($type), self::JAVASCRIPT_TYPES, true);
    }
}
