<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model\PageExclusion;

use Hryvinskyi\DeferJs\Api\ConfigInterface;
use Hryvinskyi\DeferJs\Model\PageExclusion\ExcludedUrlPatterns;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * A wildcard pattern (`*` = any run of characters, everything else literal) must match the whole request URI or
 * the whole action name to exclude the page.
 */
class ExcludedUrlPatternsTest extends TestCase
{
    /**
     * @var ConfigInterface&MockObject
     */
    private ConfigInterface&MockObject $config;

    /**
     * @var ExcludedUrlPatterns
     */
    private ExcludedUrlPatterns $exclusion;

    /**
     * Build the exclusion over a settings double.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->exclusion = new ExcludedUrlPatterns($this->config);
    }

    /**
     * Patterns that match the request URI exclude the page.
     *
     * @dataProvider matchingUriProvider
     * @param string $pattern the configured pattern
     * @param string $requestUri the URI of the request
     * @return void
     */
    public function testPatternMatchingTheUriExcludes(string $pattern, string $requestUri): void
    {
        $this->givenPatterns([$pattern]);

        $this->assertTrue($this->exclusion->isExcluded('cms_page_view', $requestUri));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function matchingUriProvider(): array
    {
        return [
            'wildcard at the start' => ['*/nokia-2610-phone.html', '/cell-phones/nokia-2610-phone.html'],
            'wildcard in the middle' => ['/customer/*/login', '/customer/account/login'],
            'wildcard at the end' => ['/customer/account/*', '/customer/account/create/'],
            'wildcard on both sides' => ['*cell-phones*', '/cell-phones/nokia-2610-phone.html'],
            'wildcard alone' => ['*', '/anything/at/all?x=1'],
            'wildcard matching nothing' => ['/customer/account*', '/customer/account'],
            'query string' => ['*?mode=list', '/women.html?mode=list'],
            'no wildcard, exact' => ['/about-us', '/about-us'],
            'literal question mark' => ['/search?q=*', '/search?q=lipstick'],
            'literal dot' => ['/page.html', '/page.html'],
            'literal parenthesis' => ['/sale(1)*', '/sale(1)/shoes'],
            'literal slashes' => ['/a/b/c', '/a/b/c'],
        ];
    }

    /**
     * Patterns that do not match the whole URI, and whose regex metacharacters are not taken as such, leave the
     * page alone.
     *
     * @dataProvider notMatchingProvider
     * @param string $pattern the configured pattern
     * @param string $requestUri the URI of the request
     * @return void
     */
    public function testPatternNotMatchingLeavesThePage(string $pattern, string $requestUri): void
    {
        $this->givenPatterns([$pattern]);

        $this->assertFalse($this->exclusion->isExcluded('cms_page_view', $requestUri));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function notMatchingProvider(): array
    {
        return [
            'only a part of the uri' => ['/customer/account', '/customer/account/login'],
            'only the end of the uri' => ['account/login', '/customer/account/login'],
            'question mark is not "optional"' => ['/pages?', '/page'],
            'dot is not "any character"' => ['/page.html', '/pageXhtml'],
            'parentheses are not a group' => ['/(sale)', '/sale'],
            'other uri' => ['/customer/*', '/checkout/cart/'],
        ];
    }

    /**
     * A pattern that matches the action name excludes the page even when it does not match the URI.
     *
     * @return void
     */
    public function testPatternMatchingOnlyTheActionNameExcludes(): void
    {
        $this->givenPatterns(['customer_account_*']);

        $this->assertTrue($this->exclusion->isExcluded('customer_account_login', '/customer/account/login/'));
    }

    /**
     * The page is excluded when any one of several patterns matches.
     *
     * @return void
     */
    public function testAnyPatternMatchingExcludes(): void
    {
        $this->givenPatterns(['/checkout/*', '*?mode=list', 'cms_*']);

        $this->assertTrue($this->exclusion->isExcluded('catalog_category_view', '/women.html?mode=list'));
    }

    /**
     * With no patterns nothing is excluded.
     *
     * @return void
     */
    public function testNoPatternsExcludesNothing(): void
    {
        $this->givenPatterns([]);

        $this->assertFalse($this->exclusion->isExcluded('cms_index_index', '/'));
    }

    /**
     * Make the settings answer the given patterns.
     *
     * @param list<string> $patterns the configured patterns
     * @return void
     */
    private function givenPatterns(array $patterns): void
    {
        $this->config->method('getExcludedUrlPatterns')->willReturn($patterns);
    }
}
