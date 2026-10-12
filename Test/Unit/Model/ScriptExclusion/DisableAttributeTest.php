<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model\ScriptExclusion;

use Hryvinskyi\DeferJs\Api\ConfigInterface;
use Hryvinskyi\DeferJs\Model\ScriptExclusion\DisableAttribute;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * A script tag containing the configured disable attribute, in any case, stays in place; with no attribute
 * configured every tag may move. The setting is read once per store view.
 */
class DisableAttributeTest extends TestCase
{
    /**
     * @var ConfigInterface&MockObject
     */
    private ConfigInterface&MockObject $config;

    /**
     * @var StoreManagerInterface&MockObject
     */
    private StoreManagerInterface&MockObject $storeManager;

    /**
     * @var DisableAttribute
     */
    private DisableAttribute $exclusion;

    /**
     * Build the exclusion over a settings double, in store view 1.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager->method('getStore')->willReturn($this->store('1'));
        $this->exclusion = new DisableAttribute($this->config, $this->storeManager);
    }

    /**
     * A tag carrying the attribute stays, whatever the case of either side.
     *
     * @dataProvider carryingProvider
     * @param string $attribute the configured attribute
     * @param string $script the whole tag
     * @return void
     */
    public function testTagCarryingTheAttributeIsExcluded(string $attribute, string $script): void
    {
        $this->config->method('getDisableAttribute')->willReturn($attribute);

        $this->assertTrue($this->exclusion->isExcluded($script));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function carryingProvider(): array
    {
        return [
            'same case' => ['data-deferjs="false"', '<script data-deferjs="false">var a = 1;</script>'],
            'tag in upper case' => ['data-deferjs="false"', '<SCRIPT DATA-DEFERJS="FALSE">var a = 1;</SCRIPT>'],
            'setting in upper case' => ['DATA-DEFERJS="false"', '<script src="a.js" data-deferjs="false"></script>'],
        ];
    }

    /**
     * A tag without the attribute moves.
     *
     * @return void
     */
    public function testTagWithoutTheAttributeIsNotExcluded(): void
    {
        $this->config->method('getDisableAttribute')->willReturn('data-deferjs="false"');

        $this->assertFalse($this->exclusion->isExcluded('<script data-deferjs="true">var a = 1;</script>'));
    }

    /**
     * An empty setting keeps nothing in place.
     *
     * @return void
     */
    public function testEmptyAttributeExcludesNothing(): void
    {
        $this->config->method('getDisableAttribute')->willReturn('');

        $this->assertFalse($this->exclusion->isExcluded('<script data-deferjs="false">var a = 1;</script>'));
    }

    /**
     * Every tag of a page is checked against a setting read only once, an empty one included.
     *
     * @dataProvider attributeProvider
     * @param string $attribute the configured attribute
     * @return void
     */
    public function testSettingIsReadOncePerStoreView(string $attribute): void
    {
        $this->config->expects($this->once())->method('getDisableAttribute')->willReturn($attribute);

        for ($i = 0; $i < 5; $i++) {
            $this->exclusion->isExcluded('<script>var a = ' . $i . ';</script>');
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function attributeProvider(): array
    {
        return [
            'configured' => ['data-deferjs="false"'],
            'empty' => [''],
        ];
    }

    /**
     * A switch of the current store view reads that store's own setting, and a switch back reuses the first one.
     *
     * @return void
     */
    public function testStoreSwitchReadsTheOtherStoresSetting(): void
    {
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturnOnConsecutiveCalls(
            $this->store('1'),
            $this->store('2'),
            $this->store('1'),
            $this->store('2')
        );
        $this->config->expects($this->exactly(2))
            ->method('getDisableAttribute')
            ->willReturnOnConsecutiveCalls('data-first', 'data-second');
        $exclusion = new DisableAttribute($this->config, $storeManager);
        $first = '<script data-first>a()</script>';

        $this->assertTrue($exclusion->isExcluded($first));
        $this->assertFalse($exclusion->isExcluded($first));
        $this->assertTrue($exclusion->isExcluded($first));
        $this->assertTrue($exclusion->isExcluded('<script data-second>b()</script>'));
    }

    /**
     * A store view double with the given id, typed as the string a loaded store returns.
     *
     * @param string $id the store id
     * @return StoreInterface&MockObject
     */
    private function store(string $id): StoreInterface&MockObject
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn($id);

        return $store;
    }
}
