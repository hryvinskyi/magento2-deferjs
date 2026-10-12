<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model;

use Hryvinskyi\DeferJs\Api\ConfigInterface;
use Hryvinskyi\DeferJs\Api\ScriptExclusionInterface;
use Hryvinskyi\DeferJs\Api\ScriptMinifierInterface;
use Hryvinskyi\DeferJs\Api\XMagentoInitMergerInterface;
use Hryvinskyi\DeferJs\Model\ScriptRelocator;
use Hryvinskyi\DeferJs\Model\ScriptTag\ScriptTagReader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Script tags move before the first closing body tag of what remains, in document order, unless an exclusion
 * keeps them; with merging on, x-magento-init tags lead the moved block as one tag; with minification on, the
 * moved ordinary tags are minified.
 */
class ScriptRelocatorTest extends TestCase
{
    /**
     * The marker that makes the exclusion double keep a tag in place.
     */
    private const KEEP = 'data-keep';

    private const INIT_A = '<script type="text/x-magento-init">{"#a":{"w":1}}</script>';
    private const INIT_B = '<script type="text/x-magento-init">[1]</script>';
    private const INIT_KEPT = '<script data-keep type="text/x-magento-init">{"#k":{"w":1}}</script>';
    private const MERGED = '<script type="text/x-magento-init">{"merged":true}</script>';

    /**
     * @var ConfigInterface&MockObject
     */
    private ConfigInterface&MockObject $config;

    /**
     * @var XMagentoInitMergerInterface&MockObject
     */
    private XMagentoInitMergerInterface&MockObject $merger;

    /**
     * @var ScriptMinifierInterface&MockObject
     */
    private ScriptMinifierInterface&MockObject $minifier;

    /**
     * @var ScriptRelocator
     */
    private ScriptRelocator $relocator;

    /**
     * Build the relocator with merging and minification off, an exclusion that keeps tags carrying the marker,
     * and the real tag reader.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $exclusion = $this->createMock(ScriptExclusionInterface::class);
        $exclusion->method('isExcluded')
            ->willReturnCallback(static fn (string $script): bool => str_contains($script, self::KEEP));
        $this->config = $this->createMock(ConfigInterface::class);
        $this->merger = $this->createMock(XMagentoInitMergerInterface::class);
        $this->minifier = $this->createMock(ScriptMinifierInterface::class);

        $this->relocator = new ScriptRelocator(
            $exclusion,
            $this->config,
            $this->merger,
            $this->minifier,
            new ScriptTagReader()
        );
    }

    /**
     * A page without script tags, or whose every tag is kept, comes back as the same string.
     *
     * @dataProvider nothingMovesProvider
     * @param string $html the page
     * @return void
     */
    public function testNothingToMoveReturnsThePageUnchanged(string $html): void
    {
        $this->merger->expects($this->never())->method('merge');

        $this->assertSame($html, $this->relocator->relocate($html));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nothingMovesProvider(): array
    {
        return [
            'no script' => ['<html><head></head><body><p>x</p></body></html>'],
            'every script kept' => [
                '<html><head><script data-keep>a()</script></head><body><script data-keep src="b.js"></script>'
                . '</body></html>',
            ],
            'empty page' => [''],
        ];
    }

    /**
     * Tags from the head and the body move before the closing body tag, in document order, joined by `PHP_EOL`,
     * with no newline before or after the block.
     *
     * @return void
     */
    public function testScriptsMoveBeforeBodyEndInDocumentOrder(): void
    {
        $html = "<html><head><script>a()</script>\n<title>T</title><script\n  src=\"/b.js\"></script></head>"
            . '<body><p>x</p><script type="text/javascript">c()</script><footer></footer></body></html>';

        $this->assertSame(
            "<html><head>\n<title>T</title></head><body><p>x</p><footer></footer>"
            . '<script>a()</script>' . PHP_EOL
            . "<script\n  src=\"/b.js\"></script>" . PHP_EOL
            . '<script type="text/javascript">c()</script>'
            . '</body></html>',
            $this->relocator->relocate($html)
        );
    }

    /**
     * A tag an exclusion keeps stays exactly where it was.
     *
     * @return void
     */
    public function testExcludedScriptStaysInPlace(): void
    {
        $html = '<head><script data-keep>k()</script><script>m()</script></head><body></body>';

        $this->assertSame(
            '<head><script data-keep>k()</script></head><body><script>m()</script></body>',
            $this->relocator->relocate($html)
        );
    }

    /**
     * With merging off an x-magento-init tag moves like any other tag, and the merger is not asked.
     *
     * @return void
     */
    public function testWithMergeOffXMagentoInitMovesAsOrdinary(): void
    {
        $this->config->method('isMergeXMagentoInit')->willReturn(false);
        $this->merger->expects($this->never())->method('merge');

        $html = '<head>' . self::INIT_A . '<script>m()</script></head><body></body>';

        $this->assertSame(
            '<head></head><body>' . self::INIT_A . PHP_EOL . '<script>m()</script></body>',
            $this->relocator->relocate($html)
        );
    }

    /**
     * `<scripts>` and `<script-x>` are not script tags and are left alone; a real tag beside them still moves.
     *
     * @return void
     */
    public function testLookalikeTagsAreNotScripts(): void
    {
        $html = '<head><scripts>s</scripts><script-x>y</script><script>m()</script></head><body></body>';

        $this->assertSame(
            '<head><scripts>s</scripts><script-x>y</script></head><body><script>m()</script></body>',
            $this->relocator->relocate($html)
        );
    }

    /**
     * A page without a closing body tag gets the moved tags appended at its end.
     *
     * @return void
     */
    public function testWithoutBodyEndTheBlockIsAppended(): void
    {
        $this->assertSame(
            '<div>text</div><script>a()</script>',
            $this->relocator->relocate('<div><script>a()</script>text</div>')
        );
    }

    /**
     * A closing body tag inside a moved tag does not count; the first one of the remaining page is used.
     *
     * @return void
     */
    public function testBodyEndInsideMovedScriptIsIgnored(): void
    {
        $html = "<head><script>document.write('</body>')</script></head><body>x</body>";

        $this->assertSame(
            "<head></head><body>x<script>document.write('</body>')</script></body>",
            $this->relocator->relocate($html)
        );
    }

    /**
     * A closing body tag inside a tag that stays in place is the first one of the remaining page, and is used.
     *
     * @return void
     */
    public function testBodyEndInsideKeptScriptCounts(): void
    {
        $html = "<head><script data-keep>w('</body>')</script><script>m()</script></head><body></body>";

        $this->assertSame(
            "<head><script data-keep>w('<script>m()</script></body>')</script></head><body></body>",
            $this->relocator->relocate($html)
        );
    }

    /**
     * A script body larger than a regular expression engine's default backtracking budget still moves, and so do
     * the tags after it.
     *
     * @return void
     */
    public function testScriptLargerThanOneMegabyteMoves(): void
    {
        $large = '<script type="text/x-magento-init">{"a":"' . str_repeat('x', 1200000) . '"}</script>';
        $html = '<head><script src="a.js"></script></head><body><p>x</p>' . $large . '<script>c()</script></body>';
        $limit = ini_get('pcre.backtrack_limit');
        ini_set('pcre.backtrack_limit', '1000000');

        try {
            $relocated = $this->relocator->relocate($html);
        } finally {
            ini_set('pcre.backtrack_limit', (string) $limit);
        }

        $this->assertSame(
            '<head></head><body><p>x</p><script src="a.js"></script>' . PHP_EOL . $large . PHP_EOL
            . '<script>c()</script></body>',
            $relocated
        );
    }

    /**
     * Only `<script` followed by whitespace, `>` or `/` opens a tag; an unclosed tag ends the search, so neither it
     * nor anything after it moves.
     *
     * @dataProvider tagBoundaryProvider
     * @param string $html the page
     * @param string $expected the relocated page
     * @return void
     */
    public function testTagBoundaries(string $html, string $expected): void
    {
        $this->assertSame($expected, $this->relocator->relocate($html));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function tagBoundaryProvider(): array
    {
        return [
            'each whitespace byte, slash and greater-than open a tag' => [
                "<script a()</script><script\ta</script><script\na</script><script\x0Ba</script>"
                . "<script\fa</script><script\ra</script><script/></script>|</body>",
                "|<script a()</script>" . PHP_EOL . "<script\ta</script>" . PHP_EOL . "<script\na</script>"
                . PHP_EOL . "<script\x0Ba</script>" . PHP_EOL . "<script\fa</script>" . PHP_EOL
                . "<script\ra</script>" . PHP_EOL . '<script/></script></body>',
            ],
            'other bytes do not' => [
                "<script\0a</script><script\xA0a</script><script:a</script>|</body>",
                "<script\0a</script><script\xA0a</script><script:a</script>|</body>",
            ],
            'unclosed tag ends the search' => [
                '<script>a()</script><script>b()<script>c()</body>',
                '<script>b()<script>c()<script>a()</script></body>',
            ],
            'script at the very end' => [
                '<script>a()</script>x<script',
                'x<script<script>a()</script>',
            ],
            'close tag right after the name' => [
                '<script></script>x</body>',
                'x<script></script></body>',
            ],
            'the closing tag is searched after the name' => [
                '<script/script>a</script>x</body>',
                'x<script/script>a</script></body>',
            ],
            'closing tag with a space is not one' => [
                '<script>a</script ><b></script>x</body>',
                'x<script>a</script ><b></script></body>',
            ],
        ];
    }

    /**
     * Upper-case script and body tags are recognised.
     *
     * @return void
     */
    public function testUpperCaseTags(): void
    {
        $this->assertSame(
            '<HEAD></HEAD><BODY>x<SCRIPT>a()</SCRIPT></BODY>',
            $this->relocator->relocate('<HEAD><SCRIPT>a()</SCRIPT></HEAD><BODY>x</BODY>')
        );
    }

    /**
     * With merging on, the merged tag leads the block with no separator; an x-magento-init tag the merger hands
     * back moves as an ordinary tag in its document position; a kept x-magento-init tag is neither merged nor moved.
     *
     * @return void
     */
    public function testMergeOnMergedTagLeadsTheBlock(): void
    {
        $this->config->method('isMergeXMagentoInit')->willReturn(true);
        $this->merger->expects($this->once())
            ->method('merge')
            ->with([self::INIT_A, self::INIT_B])
            ->willReturn(['merged' => self::MERGED, 'unmerged' => [self::INIT_B]]);

        $html = '<head>' . self::INIT_A . '<script>one()</script>' . self::INIT_KEPT . self::INIT_B
            . '</head><body><script>two()</script></body>';

        $this->assertSame(
            '<head>' . self::INIT_KEPT . '</head><body>'
            . self::MERGED . '<script>one()</script>' . PHP_EOL . self::INIT_B . PHP_EOL . '<script>two()</script>'
            . '</body>',
            $this->relocator->relocate($html)
        );
    }

    /**
     * An empty merged result writes no merged tag, and the tags that were merged are still removed.
     *
     * @return void
     */
    public function testMergeOnEmptyResultWritesNoMergedTag(): void
    {
        $this->config->method('isMergeXMagentoInit')->willReturn(true);
        $this->merger->method('merge')->willReturn(['merged' => '', 'unmerged' => []]);

        $this->assertSame(
            '<head></head><body><script>one()</script></body>',
            $this->relocator->relocate('<head>' . self::INIT_A . '<script>one()</script></head><body></body>')
        );
        $this->assertSame(
            '<head></head><body></body>',
            $this->relocator->relocate('<head>' . self::INIT_A . '</head><body></body>')
        );
    }

    /**
     * The x-magento-init type is recognised in single quotes and any case; a `data-type` attribute does not count.
     *
     * @return void
     */
    public function testMergeOnRecognisesTheTypeAttributeOnly(): void
    {
        $singleQuoted = "<SCRIPT TYPE='Text/X-Magento-Init'>{\"#a\":{\"w\":1}}</SCRIPT>";
        $dataType = '<script data-type="text/x-magento-init">d()</script>';
        $this->config->method('isMergeXMagentoInit')->willReturn(true);
        $this->merger->expects($this->once())
            ->method('merge')
            ->with([$singleQuoted])
            ->willReturn(['merged' => self::MERGED, 'unmerged' => []]);

        $this->assertSame(
            '<body>' . self::MERGED . $dataType . '</body>',
            $this->relocator->relocate('<body>' . $singleQuoted . $dataType . '</body>')
        );
    }

    /**
     * With merging on and no x-magento-init tag to move, the merger is not asked.
     *
     * @return void
     */
    public function testMergeOnWithoutInitTagsDoesNotCallTheMerger(): void
    {
        $this->config->method('isMergeXMagentoInit')->willReturn(true);
        $this->merger->expects($this->never())->method('merge');

        $this->assertSame(
            '<head>' . self::INIT_KEPT . '</head><body><script>m()</script></body>',
            $this->relocator->relocate('<head>' . self::INIT_KEPT . '<script>m()</script></head><body></body>')
        );
    }

    /**
     * With minification on, only the moved ordinary tags reach the minifier: never the merged tag, a tag the merger
     * took, or a kept tag.
     *
     * @return void
     */
    public function testMinifyOnMinifiesOnlyMovedOrdinaryTags(): void
    {
        $this->config->method('isMergeXMagentoInit')->willReturn(true);
        $this->config->method('isMinifyBodyScripts')->willReturn(true);
        $this->merger->method('merge')->willReturn(['merged' => self::MERGED, 'unmerged' => [self::INIT_B]]);
        $minified = [];
        $this->minifier->method('minify')->willReturnCallback(
            static function (string $script) use (&$minified): string {
                $minified[] = $script;

                return 'min:' . $script;
            }
        );

        $html = '<head>' . self::INIT_A . '<script>one()</script><script data-keep>k()</script>' . self::INIT_B
            . '</head><body></body>';

        $this->assertSame(
            '<head><script data-keep>k()</script></head><body>'
            . self::MERGED . 'min:<script>one()</script>' . PHP_EOL . 'min:' . self::INIT_B . '</body>',
            $this->relocator->relocate($html)
        );
        $this->assertSame(['<script>one()</script>', self::INIT_B], $minified);
    }

    /**
     * With minification off the minifier is never asked.
     *
     * @return void
     */
    public function testMinifyOffDoesNotCallTheMinifier(): void
    {
        $this->config->method('isMinifyBodyScripts')->willReturn(false);
        $this->minifier->expects($this->never())->method('minify');

        $this->assertSame(
            '<head></head><body><script>a()</script></body>',
            $this->relocator->relocate('<head><script>a()</script></head><body></body>')
        );
    }
}
