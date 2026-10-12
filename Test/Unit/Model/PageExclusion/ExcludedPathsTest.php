<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model\PageExclusion;

use Hryvinskyi\DeferJs\Api\ConfigInterface;
use Hryvinskyi\DeferJs\Model\PageExclusion\ExcludedPaths;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * A page is excluded when its request URI is exactly one of the configured paths.
 */
class ExcludedPathsTest extends TestCase
{
    /**
     * @var ConfigInterface&MockObject
     */
    private ConfigInterface&MockObject $config;

    /**
     * @var ExcludedPaths
     */
    private ExcludedPaths $exclusion;

    /**
     * Build the exclusion over a settings double listing two paths.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->config->method('getExcludedPaths')
            ->willReturn(['/women/tops-women/jackets-women.html', '/checkout/cart/']);
        $this->exclusion = new ExcludedPaths($this->config);
    }

    /**
     * A listed URI excludes the page, whatever the action name.
     *
     * @return void
     */
    public function testListedUriIsExcluded(): void
    {
        $this->assertTrue($this->exclusion->isExcluded('catalog_product_view', '/women/tops-women/jackets-women.html'));
    }

    /**
     * A URI that only resembles a listed one does not exclude the page.
     *
     * @dataProvider unlistedProvider
     * @param string $actionName the action name of the request
     * @param string $requestUri the URI of the request
     * @return void
     */
    public function testUnlistedUriIsNotExcluded(string $actionName, string $requestUri): void
    {
        $this->assertFalse($this->exclusion->isExcluded($actionName, $requestUri));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function unlistedProvider(): array
    {
        return [
            'other uri' => ['cms_index_index', '/'],
            'with a query string' => ['checkout_cart_index', '/checkout/cart/?a=1'],
            'without the trailing slash' => ['checkout_cart_index', '/checkout/cart'],
            'listed path only as the action name' => ['/checkout/cart/', '/basket'],
        ];
    }
}
