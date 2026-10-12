<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model\ScriptTag;

use Hryvinskyi\DeferJs\Model\ScriptTag\ScriptTagReader;
use PHPUnit\Framework\TestCase;

/**
 * A tag splits at the first `>` and before `</script>`; attributes are found only as whole attribute names.
 */
class ScriptTagReaderTest extends TestCase
{
    /**
     * @var ScriptTagReader
     */
    private ScriptTagReader $reader;

    /**
     * Build the reader; it has no collaborators.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->reader = new ScriptTagReader();
    }

    /**
     * The opening tag ends at the first `>`, the content runs to the closing tag, and nothing is lost.
     *
     * @return void
     */
    public function testSplitsAtFirstGreaterThanAndClosingTag(): void
    {
        $script = '<script type="text/javascript">if (a > b) { c("</div>"); }</SCRIPT>';

        $this->assertSame(
            [
                'opening' => '<script type="text/javascript">',
                'content' => 'if (a > b) { c("</div>"); }',
                'closing' => '</SCRIPT>',
            ],
            $this->reader->split($script)
        );
    }

    /**
     * An empty tag has empty content.
     *
     * @return void
     */
    public function testEmptyTagHasEmptyContent(): void
    {
        $this->assertSame(
            ['opening' => '<script src="a.js">', 'content' => '', 'closing' => '</script>'],
            $this->reader->split('<script src="a.js"></script>')
        );
    }

    /**
     * Text that does not end with a closing tag keeps everything after the opening tag as content.
     *
     * @return void
     */
    public function testTextWithoutClosingTag(): void
    {
        $this->assertSame(
            ['opening' => '<script>', 'content' => 'var a;', 'closing' => ''],
            $this->reader->split('<script>var a;')
        );
    }

    /**
     * An opening tag with no `>` before the closing tag is the whole text before it, with no content.
     *
     * @return void
     */
    public function testOpeningTagWithoutGreaterThan(): void
    {
        $this->assertSame(
            ['opening' => '<script src="a.js"', 'content' => '', 'closing' => '</script>'],
            $this->reader->split('<script src="a.js"</script>')
        );
    }

    /**
     * Double-quoted, single-quoted, unquoted and value-less attributes are read; the name is case-insensitive.
     *
     * @return void
     */
    public function testReadsAttributeValuesInEveryQuoting(): void
    {
        $opening = "<script TYPE='module' src=\"/a.js\" data-x=plain async nomodule>";

        $this->assertSame('module', $this->reader->getAttribute($opening, 'type'));
        $this->assertSame('/a.js', $this->reader->getAttribute($opening, 'SRC'));
        $this->assertSame('plain', $this->reader->getAttribute($opening, 'data-x'));
        $this->assertSame('', $this->reader->getAttribute($opening, 'async'));
        $this->assertSame('', $this->reader->getAttribute($opening, 'nomodule'));
    }

    /**
     * Whitespace around `=` is allowed, and a newline counts as whitespace before the name.
     *
     * @return void
     */
    public function testWhitespaceAroundEquals(): void
    {
        $this->assertSame(
            'text/x-magento-init',
            $this->reader->getAttribute("<script\ntype = \"text/x-magento-init\">", 'type')
        );
    }

    /**
     * A longer name ending in the requested one does not count as the attribute.
     *
     * @return void
     */
    public function testPartOfALongerNameIsNotTheAttribute(): void
    {
        $opening = '<script data-src="a.js" data-type="module" srcset="x">';

        $this->assertNull($this->reader->getAttribute($opening, 'src'));
        $this->assertNull($this->reader->getAttribute($opening, 'type'));
    }

    /**
     * An attribute just before the closing `>` or a self-closing `/` is found.
     *
     * @return void
     */
    public function testAttributeBeforeTagEnd(): void
    {
        $this->assertSame('', $this->reader->getAttribute('<script defer>', 'defer'));
        $this->assertSame('', $this->reader->getAttribute('<script defer/>', 'defer'));
    }

    /**
     * An absent attribute is null.
     *
     * @return void
     */
    public function testAbsentAttributeIsNull(): void
    {
        $this->assertNull($this->reader->getAttribute('<script>', 'type'));
    }
}
