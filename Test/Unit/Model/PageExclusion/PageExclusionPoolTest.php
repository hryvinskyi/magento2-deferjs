<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model\PageExclusion;

use Hryvinskyi\DeferJs\Api\PageExclusionInterface;
use Hryvinskyi\DeferJs\Model\PageExclusion\PageExclusionPool;
use PHPUnit\Framework\TestCase;

/**
 * The pool excludes a page when any member does, and refuses a member that is not a page exclusion.
 */
class PageExclusionPoolTest extends TestCase
{
    /**
     * An empty pool leaves every page alone.
     *
     * @return void
     */
    public function testEmptyPoolExcludesNothing(): void
    {
        $this->assertFalse((new PageExclusionPool())->isExcluded('cms_index_index', '/'));
    }

    /**
     * One member excluding is enough, and members after it are not asked.
     *
     * @return void
     */
    public function testAnyMemberExcludingWins(): void
    {
        $first = $this->createMock(PageExclusionInterface::class);
        $first->expects($this->once())->method('isExcluded')
            ->with('checkout_cart_index', '/checkout/cart/')
            ->willReturn(false);
        $second = $this->createMock(PageExclusionInterface::class);
        $second->expects($this->once())->method('isExcluded')
            ->with('checkout_cart_index', '/checkout/cart/')
            ->willReturn(true);
        $third = $this->createMock(PageExclusionInterface::class);
        $third->expects($this->never())->method('isExcluded');

        $pool = new PageExclusionPool(['first' => $first, 'second' => $second, 'third' => $third]);

        $this->assertTrue($pool->isExcluded('checkout_cart_index', '/checkout/cart/'));
    }

    /**
     * When no member excludes the page, neither does the pool.
     *
     * @return void
     */
    public function testNoMemberExcludingLeavesThePage(): void
    {
        $member = $this->createMock(PageExclusionInterface::class);
        $member->method('isExcluded')->willReturn(false);

        $pool = new PageExclusionPool(['a' => $member, 'b' => $member]);

        $this->assertFalse($pool->isExcluded('cms_index_index', '/'));
    }

    /**
     * A member that is not a page exclusion fails when the pool is built, naming the member.
     *
     * @return void
     */
    public function testNonExclusionMemberThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"notAnExclusion"');

        new PageExclusionPool([
            'valid' => $this->createMock(PageExclusionInterface::class),
            'notAnExclusion' => new \stdClass(),
        ]);
    }
}
