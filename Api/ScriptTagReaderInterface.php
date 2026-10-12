<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Api;

/**
 * Reads the parts of one script tag.
 *
 * The opening tag runs from `<script` to the first `>`; the content is what lies between that `>` and the closing
 * `</script>`. An attribute is present when its name appears in the opening tag as a whole attribute name:
 * preceded by whitespace and followed by `=`, whitespace, `/` or `>`, so `data-src` is not `src`.
 */
interface ScriptTagReaderInterface
{
    /**
     * Split a tag into its opening tag, its content and its closing tag; the three concatenated give the tag back.
     *
     * @param string $script the whole tag, from `<script` to `</script>`
     * @return array{opening: string, content: string, closing: string} closing is '' when the text does not end
     *         with `</script>`; content is '' when the opening tag has no `>` before the closing tag
     */
    public function split(string $script): array;

    /**
     * The value of an attribute of an opening tag, compared by name case-insensitively.
     *
     * @param string $openingTag the opening tag, from `<script` to the first `>`
     * @param string $name the attribute name
     * @return string|null the value without its quotes, '' for an attribute written without a value, or null
     *         when the attribute is absent
     */
    public function getAttribute(string $openingTag, string $name): ?string;
}
