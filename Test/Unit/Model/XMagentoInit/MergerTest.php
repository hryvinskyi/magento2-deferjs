<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model\XMagentoInit;

use Hryvinskyi\DeferJs\Model\ScriptTag\ScriptTagReader;
use Hryvinskyi\DeferJs\Model\XMagentoInit\Merger;
use PHPUnit\Framework\TestCase;

/**
 * The JSON objects of x-magento-init tags merge in document order into one safely encoded tag: objects merge
 * property by property, lists concatenate, any other pair takes the later value; anything that is not a JSON
 * object is handed back unmerged.
 */
class MergerTest extends TestCase
{
    /**
     * @var Merger
     */
    private Merger $merger;

    /**
     * Build the merger over the real tag reader.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->merger = new Merger(new ScriptTagReader());
    }

    /**
     * Objects under the same string key merge recursively; distinct keys are all kept.
     *
     * @return void
     */
    public function testStringKeysMergeRecursively(): void
    {
        $result = $this->merger->merge([
            $this->tag('{"*":{"Magento_Ui/js/core/app":{"components":{"a":{"x":1}}}}}'),
            $this->tag('{"*":{"Magento_Ui/js/core/app":{"components":{"b":{"y":2}}}},"#id":{"widget":{"k":1}}}'),
        ]);

        $this->assertSame(
            $this->tag(
                '{"*":{"Magento_Ui/js/core/app":{"components":{"a":{"x":1},"b":{"y":2}}}},"#id":{"widget":{"k":1}}}'
            ),
            $result['merged']
        );
        $this->assertSame([], $result['unmerged']);
    }

    /**
     * A scalar under the same key takes the later tag's value.
     *
     * @return void
     */
    public function testLaterScalarWins(): void
    {
        $result = $this->merger->merge([
            $this->tag('{"#a":{"w":{"mode":"first","keep":true}}}'),
            $this->tag('{"#a":{"w":{"mode":"second"}}}'),
        ]);

        $this->assertSame($this->tag('{"#a":{"w":{"mode":"second","keep":true}}}'), $result['merged']);
    }

    /**
     * A later array replaces an earlier scalar under the same key, and a later scalar replaces an earlier array.
     *
     * @return void
     */
    public function testMismatchedTypesTakeTheLaterValue(): void
    {
        $result = $this->merger->merge([
            $this->tag('{"a":"text","b":{"x":1}}'),
            $this->tag('{"a":{"y":2},"b":"text"}'),
        ]);

        $this->assertSame($this->tag('{"a":{"y":2},"b":"text"}'), $result['merged']);
    }

    /**
     * Two lists under the same key concatenate, the earlier items first, across any number of tags.
     *
     * @return void
     */
    public function testListsConcatenateInDocumentOrder(): void
    {
        $result = $this->merger->merge([
            $this->tag('{"list":["first","second"]}'),
            $this->tag('{"list":["third"]}'),
            $this->tag('{"list":[{"k":1}]}'),
        ]);

        $this->assertSame($this->tag('{"list":["first","second","third",{"k":1}]}'), $result['merged']);
    }

    /**
     * Numeric object keys are keys like any other: the same key merges, a new key is added, nothing is renumbered.
     *
     * @return void
     */
    public function testNumericObjectKeysMergeByKey(): void
    {
        $result = $this->merger->merge([
            $this->tag('{"#f":{"C":{"prices":{"123":{"p":1,"q":1}}}}}'),
            $this->tag('{"#f":{"C":{"prices":{"123":{"p":2},"124":{"p":3}}}}}'),
        ]);

        $this->assertSame(
            $this->tag('{"#f":{"C":{"prices":{"123":{"p":2,"q":1},"124":{"p":3}}}}}'),
            $result['merged']
        );
    }

    /**
     * An empty object stays an object, and an object whose keys look like list positions stays an object.
     *
     * @return void
     */
    public function testObjectsStayObjects(): void
    {
        $json = '{"*":{"A":{"config":{},"list":{"0":"x","1":"y"},"items":[],"empty":""}}}';

        $this->assertSame($this->tag($json), $this->merger->merge([$this->tag($json)])['merged']);
    }

    /**
     * An object meeting a list, in either order, takes the later value; so does a value meeting null.
     *
     * @return void
     */
    public function testObjectMeetingListTakesTheLaterValue(): void
    {
        $result = $this->merger->merge([
            $this->tag('{"a":{"0":"x"},"b":["y"],"c":{"k":1},"d":null}'),
            $this->tag('{"a":["z"],"b":{"0":"w"},"c":null,"d":{"k":2}}'),
        ]);

        $this->assertSame($this->tag('{"a":["z"],"b":{"0":"w"},"c":null,"d":{"k":2}}'), $result['merged']);
    }

    /**
     * Properties keep the position they first appeared in; an empty key merges like any other.
     *
     * @return void
     */
    public function testPropertyOrderAndEmptyKey(): void
    {
        $result = $this->merger->merge([
            $this->tag('{"b":1,"":{"x":1},"a":2}'),
            $this->tag('{"c":3,"a":4,"":{"y":2}}'),
        ]);

        $this->assertSame($this->tag('{"b":1,"":{"x":1,"y":2},"a":4,"c":3}'), $result['merged']);
    }

    /**
     * The same key in three tags merges all three, each later object into what the earlier ones made.
     *
     * @return void
     */
    public function testSameKeyInThreeTagsMergesAllThree(): void
    {
        $result = $this->merger->merge([
            $this->tag('{"#a":{"w":{"one":1}}}'),
            $this->tag('{"#a":{"w":{"two":2}}}'),
            $this->tag('{"#a":{"w":{"three":3}}}'),
        ]);

        $this->assertSame($this->tag('{"#a":{"w":{"one":1,"two":2,"three":3}}}'), $result['merged']);
    }

    /**
     * A value holding `</script>` is written with `<` and `>` escaped, so it cannot end the merged tag, and still
     * decodes to `</script>`.
     *
     * @return void
     */
    public function testClosingScriptTagInsideValueIsEscaped(): void
    {
        $result = $this->merger->merge([$this->tag('{"#a":{"w":{"html":"<\/script><b>x</b>"}}}')]);

        $this->assertSame(
            $this->tag('{"#a":{"w":{"html":"\u003C/script\u003E\u003Cb\u003Ex\u003C/b\u003E"}}}'),
            $result['merged']
        );
        $this->assertSame(1, substr_count(strtolower($result['merged']), '</script>'));
        $this->assertSame(['#a' => ['w' => ['html' => '</script><b>x</b>']]], $this->decodeMerged($result['merged']));
    }

    /**
     * HTML inside a JSON string value survives the merge; it is only escaped, never stripped.
     *
     * @return void
     */
    public function testHtmlInsideStringValueSurvives(): void
    {
        $result = $this->merger->merge([$this->tag('{"#a":{"w":{"label":"<span class=\"x\">Æble/ø</span>"}}}')]);

        $this->assertSame(
            ['#a' => ['w' => ['label' => '<span class="x">Æble/ø</span>']]],
            $this->decodeMerged($result['merged'])
        );
        $this->assertStringContainsString('Æble/ø', $result['merged']);
    }

    /**
     * Only content that is a JSON object merges; an array literal, a scalar, broken JSON and an empty tag are
     * handed back in their order.
     *
     * @return void
     */
    public function testNonObjectContentIsUnmerged(): void
    {
        $arrayLiteral = $this->tag('[{"a":1}]');
        $scalar = $this->tag('"text"');
        $broken = $this->tag('{"a":');
        $empty = $this->tag('');
        $object = $this->tag(" \n{\"#a\":{\"w\":1}}\n");

        $result = $this->merger->merge([$arrayLiteral, $scalar, $object, $broken, $empty]);

        $this->assertSame($this->tag('{"#a":{"w":1}}'), $result['merged']);
        $this->assertSame([$arrayLiteral, $scalar, $broken, $empty], $result['unmerged']);
    }

    /**
     * An object PHP cannot hold, such as one whose key starts with a NUL character, is handed back unmerged.
     *
     * @return void
     */
    public function testUndecodableObjectIsUnmerged(): void
    {
        $undecodable = $this->tag('{"\u0000a":1}');

        $result = $this->merger->merge([$undecodable, $this->tag('{"#a":1}')]);

        $this->assertSame($this->tag('{"#a":1}'), $result['merged']);
        $this->assertSame([$undecodable], $result['unmerged']);
    }

    /**
     * When every decoded object is empty there is no merged tag.
     *
     * @return void
     */
    public function testAllEmptyObjectsGiveNoMergedTag(): void
    {
        $result = $this->merger->merge([$this->tag('{}'), $this->tag(' { } ')]);

        $this->assertSame('', $result['merged']);
        $this->assertSame([], $result['unmerged']);
    }

    /**
     * No tags give no merged tag and nothing unmerged.
     *
     * @return void
     */
    public function testNoTags(): void
    {
        $this->assertSame(['merged' => '', 'unmerged' => []], $this->merger->merge([]));
    }

    /**
     * The opening tag's attributes are not part of the decoded content.
     *
     * @return void
     */
    public function testContentIsReadAfterTheOpeningTag(): void
    {
        $result = $this->merger->merge([
            '<script type=\'text/x-magento-init\' data-role="x">{"#a":{"w":1}}</SCRIPT>',
        ]);

        $this->assertSame($this->tag('{"#a":{"w":1}}'), $result['merged']);
    }

    /**
     * Wrap JSON in an x-magento-init tag as the merger writes it.
     *
     * @param string $json the tag content
     * @return string
     */
    private function tag(string $json): string
    {
        return '<script type="text/x-magento-init">' . $json . '</script>';
    }

    /**
     * Decode the JSON of a merged tag.
     *
     * @param string $merged the merged tag
     * @return mixed
     */
    private function decodeMerged(string $merged): mixed
    {
        $prefix = '<script type="text/x-magento-init">';
        $this->assertStringStartsWith($prefix, $merged);
        $this->assertStringEndsWith('</script>', $merged);

        return json_decode(substr($merged, strlen($prefix), -strlen('</script>')), true, 512, JSON_THROW_ON_ERROR);
    }
}
