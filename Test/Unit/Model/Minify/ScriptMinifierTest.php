<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model\Minify;

use Hryvinskyi\DeferJs\Model\Minify\JavaScriptMinifierInterface;
use Hryvinskyi\DeferJs\Model\Minify\JShrinkMinifier;
use Hryvinskyi\DeferJs\Model\Minify\ScriptMinifier;
use Hryvinskyi\DeferJs\Model\ScriptTag\ScriptTagReader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Only the content of inline JavaScript tags is minified; every other tag, a tag the minifier fails on, and a tag
 * whose minified content would end the element early or open an HTML comment, comes back as it was.
 */
class ScriptMinifierTest extends TestCase
{
    /**
     * @var JavaScriptMinifierInterface&MockObject
     */
    private JavaScriptMinifierInterface&MockObject $javaScriptMinifier;

    /**
     * @var ScriptMinifier
     */
    private ScriptMinifier $minifier;

    /**
     * Build the minifier over the real tag reader and a JavaScript minifier double.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->javaScriptMinifier = $this->createMock(JavaScriptMinifierInterface::class);
        $this->minifier = new ScriptMinifier(new ScriptTagReader(), $this->javaScriptMinifier);
    }

    /**
     * The content of an inline JavaScript tag is minified; its opening and closing tags are kept byte for byte.
     *
     * @dataProvider inlineJavaScriptProvider
     * @param string $opening the opening tag
     * @param string $closing the closing tag
     * @return void
     */
    public function testInlineJavaScriptContentIsMinified(string $opening, string $closing): void
    {
        $this->javaScriptMinifier->expects($this->once())
            ->method('minify')
            ->with("\n  var a = 1;\n")
            ->willReturn('var a=1;');

        $this->assertSame(
            $opening . 'var a=1;' . $closing,
            $this->minifier->minify($opening . "\n  var a = 1;\n" . $closing)
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function inlineJavaScriptProvider(): array
    {
        return [
            'no type' => ['<script>', '</script>'],
            'text/javascript' => ['<script type="text/javascript">', '</script>'],
            'application/javascript, uppercase' => ["<SCRIPT TYPE='Application/JavaScript'>", '</SCRIPT>'],
            'module' => ['<script type="module">', '</script>'],
            'attributes kept as written' => [
                "<script  data-src=\"a.js\"\n  nonce=\"abc\"   data-type='x' >",
                '</script>',
            ],
        ];
    }

    /**
     * A tag with a source or a type other than JavaScript is never handed to the minifier.
     *
     * @dataProvider untouchedProvider
     * @param string $script the whole tag
     * @return void
     */
    public function testOtherTagsAreUntouched(string $script): void
    {
        $this->javaScriptMinifier->expects($this->never())->method('minify');

        $this->assertSame($script, $this->minifier->minify($script));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function untouchedProvider(): array
    {
        return [
            'src' => ['<script src="/a.js">  var a = 1;  </script>'],
            'src without quotes' => ['<script type="text/javascript" src=/a.js></script>'],
            'ld+json' => ['<script type="application/ld+json">  {"@type": "Product"}  </script>'],
            'magento template' => ['<script type="text/x-magento-template">  <div><%= a %></div>  </script>'],
            'magento init' => ['<script type="text/x-magento-init">  {"*": {}}  </script>'],
            'custom type' => ['<script type="text/x-custom">  var a = 1;  </script>'],
            'empty content' => ['<script>  </script>'],
        ];
    }

    /**
     * A failing minifier leaves the tag as it was.
     *
     * @return void
     */
    public function testMinifierFailureLeavesTagUnchanged(): void
    {
        $script = '<script>var a = "unterminated;</script>';
        $this->javaScriptMinifier->method('minify')->willThrowException(new \RuntimeException('Unclosed string'));

        $this->assertSame($script, $this->minifier->minify($script));
    }

    /**
     * Minified content that gains a `</script` or `<!--`, in any case, keeps the tag as it was written.
     *
     * @dataProvider unsafeOutputProvider
     * @param string $content the content as written
     * @param string $minified what the minifier returns for it
     * @return void
     */
    public function testUnsafeSequenceIntroducedByMinifierKeepsTheTag(string $content, string $minified): void
    {
        $script = '<script>' . $content . '</script>';
        $this->javaScriptMinifier->method('minify')->willReturn($minified);

        $this->assertSame($script, $this->minifier->minify($script));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function unsafeOutputProvider(): array
    {
        return [
            'closing script' => ['var a = 1 < /script>/.source;', 'var a=1</script>/.source;'],
            'closing script, upper case' => ['var a = 1 < /SCRIPT/;', 'var a=1</SCRIPT/;'],
            'comment opener' => ['if (a < !--b) {}', 'if(a<!--b){}'],
        ];
    }

    /**
     * A sequence the original content already held does not stop the minified content being used.
     *
     * @return void
     */
    public function testSequenceAlreadyInTheOriginalIsAccepted(): void
    {
        $this->javaScriptMinifier->method('minify')->willReturn("var s='<!--';");

        $this->assertSame(
            "<script>var s='<!--';</script>",
            $this->minifier->minify("<script>var s = '<!--';</script>")
        );
    }

    /**
     * The JavaScript minifier in use turns these sources into `</script` and `<!--`; the tags stay as written.
     *
     * @dataProvider realMinifierUnsafeProvider
     * @param string $script the whole tag
     * @return void
     */
    public function testRealMinifierOutputThatWouldBreakTheTagIsRejected(string $script): void
    {
        $minifier = new ScriptMinifier(new ScriptTagReader(), new JShrinkMinifier());

        $this->assertSame($script, $minifier->minify($script));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function realMinifierUnsafeProvider(): array
    {
        return [
            'less than a regular expression' => [
                "<script>var ok = 1 < /script>/.source.length; console.log('after');</script>",
            ],
            'less than a negated decrement' => ['<script>if (a < !--b) { x(); }</script>'],
        ];
    }

    /**
     * Ordinary source still goes through the real minifier.
     *
     * @return void
     */
    public function testRealMinifierStillMinifiesOrdinarySource(): void
    {
        $minifier = new ScriptMinifier(new ScriptTagReader(), new JShrinkMinifier());

        $this->assertSame('<script>var a=1;</script>', $minifier->minify("<script>\n  var a = 1;\n</script>"));
    }
}
