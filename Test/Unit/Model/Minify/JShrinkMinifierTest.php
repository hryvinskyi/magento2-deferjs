<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model\Minify;

use Hryvinskyi\DeferJs\Model\Minify\JShrinkMinifier;
use PHPUnit\Framework\TestCase;

/**
 * The adapter hands JavaScript to the library and reports what the library cannot minify by throwing.
 */
class JShrinkMinifierTest extends TestCase
{
    /**
     * Whitespace and comments are removed, string literals are kept as written.
     *
     * @return void
     */
    public function testMinifiesJavaScript(): void
    {
        $this->assertSame(
            "var a=1;var b='x  y';",
            (new JShrinkMinifier())->minify("\n  // comment\n  var a = 1;\n  var b = 'x  y';\n")
        );
    }

    /**
     * Source the library cannot read throws instead of coming back mangled.
     *
     * @return void
     */
    public function testUnreadableSourceThrows(): void
    {
        $this->expectException(\RuntimeException::class);

        (new JShrinkMinifier())->minify('var a = "unterminated;');
    }
}
