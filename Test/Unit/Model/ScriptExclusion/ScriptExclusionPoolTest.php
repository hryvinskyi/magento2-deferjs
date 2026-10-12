<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model\ScriptExclusion;

use Hryvinskyi\DeferJs\Api\ScriptExclusionInterface;
use Hryvinskyi\DeferJs\Model\ScriptExclusion\ScriptExclusionPool;
use PHPUnit\Framework\TestCase;

/**
 * The pool keeps a script in place when any member does, and refuses a member that is not a script exclusion.
 */
class ScriptExclusionPoolTest extends TestCase
{
    private const SCRIPT = '<script>var a = 1;</script>';

    /**
     * An empty pool lets every script move.
     *
     * @return void
     */
    public function testEmptyPoolExcludesNothing(): void
    {
        $this->assertFalse((new ScriptExclusionPool())->isExcluded(self::SCRIPT));
    }

    /**
     * One member excluding is enough, and members after it are not asked.
     *
     * @return void
     */
    public function testAnyMemberExcludingWins(): void
    {
        $first = $this->createMock(ScriptExclusionInterface::class);
        $first->expects($this->once())->method('isExcluded')->with(self::SCRIPT)->willReturn(false);
        $second = $this->createMock(ScriptExclusionInterface::class);
        $second->expects($this->once())->method('isExcluded')->with(self::SCRIPT)->willReturn(true);
        $third = $this->createMock(ScriptExclusionInterface::class);
        $third->expects($this->never())->method('isExcluded');

        $pool = new ScriptExclusionPool(['first' => $first, 'second' => $second, 'third' => $third]);

        $this->assertTrue($pool->isExcluded(self::SCRIPT));
    }

    /**
     * When no member excludes the script, neither does the pool.
     *
     * @return void
     */
    public function testNoMemberExcludingLetsTheScriptMove(): void
    {
        $member = $this->createMock(ScriptExclusionInterface::class);
        $member->method('isExcluded')->willReturn(false);

        $pool = new ScriptExclusionPool(['a' => $member, 'b' => $member]);

        $this->assertFalse($pool->isExcluded(self::SCRIPT));
    }

    /**
     * A member that is not a script exclusion fails when the pool is built, naming the member.
     *
     * @return void
     */
    public function testNonExclusionMemberThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"notAnExclusion"');

        new ScriptExclusionPool([
            'valid' => $this->createMock(ScriptExclusionInterface::class),
            'notAnExclusion' => 'Vendor\Module\Model\SomeExclusion',
        ]);
    }
}
