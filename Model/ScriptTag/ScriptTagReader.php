<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model\ScriptTag;

use Hryvinskyi\DeferJs\Api\ScriptTagReaderInterface;

/**
 * Reads a script tag's opening tag, content and attributes by plain string rules, without an HTML parser.
 */
class ScriptTagReader implements ScriptTagReaderInterface
{
    private const CLOSING_TAG = '</script>';

    /**
     * @inheritDoc
     */
    public function split(string $script): array
    {
        $closingLength = strlen(self::CLOSING_TAG);
        $closing = strlen($script) >= $closingLength
            && strcasecmp(substr($script, -$closingLength), self::CLOSING_TAG) === 0
            ? substr($script, -$closingLength)
            : '';
        $rest = $closing === '' ? $script : substr($script, 0, -$closingLength);

        $end = strpos($rest, '>');
        if ($end === false) {
            return ['opening' => $rest, 'content' => '', 'closing' => $closing];
        }

        return [
            'opening' => substr($rest, 0, $end + 1),
            'content' => substr($rest, $end + 1),
            'closing' => $closing,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAttribute(string $openingTag, string $name): ?string
    {
        $pattern = '~\s' . preg_quote($name, '~')
            . '(?=[\s=/>]|$)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+)))?~i';

        if (preg_match($pattern, $openingTag, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
            return null;
        }

        return $matches[1] ?? $matches[2] ?? $matches[3] ?? '';
    }
}
