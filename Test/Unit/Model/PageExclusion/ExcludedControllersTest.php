<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model\PageExclusion;

use Hryvinskyi\DeferJs\Api\ConfigInterface;
use Hryvinskyi\DeferJs\Model\PageExclusion\ExcludedControllers;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * A page is excluded when its full action name is exactly one of the configured controllers.
 */
class ExcludedControllersTest extends TestCase
{
    /**
     * @var ConfigInterface&MockObject
     */
    private ConfigInterface&MockObject $config;

    /**
     * @var ExcludedControllers
     */
    private ExcludedControllers $exclusion;

    /**
     * Build the exclusion over a settings double listing two controllers.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->config->method('getExcludedControllers')
            ->willReturn(['checkout_index_index', 'checkout_cart_index']);
        $this->exclusion = new ExcludedControllers($this->config);
    }

    /**
     * A listed action name excludes the page, whatever the URI.
     *
     * @return void
     */
    public function testListedActionNameIsExcluded(): void
    {
        $this->assertTrue($this->exclusion->isExcluded('checkout_cart_index', '/anything'));
    }

    /**
     * An action name that only resembles a listed one does not exclude the page.
     *
     * @dataProvider unlistedProvider
     * @param string $actionName the action name of the request
     * @param string $requestUri the URI of the request
     * @return void
     */
    public function testUnlistedActionNameIsNotExcluded(string $actionName, string $requestUri): void
    {
        $this->assertFalse($this->exclusion->isExcluded($actionName, $requestUri));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function unlistedProvider(): array
    {
        return [
            'other action' => ['cms_index_index', '/'],
            'prefix of a listed one' => ['checkout_cart', '/checkout/cart'],
            'different case' => ['Checkout_Cart_Index', '/checkout/cart'],
            'listed name only in the uri' => ['cms_page_view', 'checkout_cart_index'],
        ];
    }
}
