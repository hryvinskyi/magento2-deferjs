<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model\ScriptExclusion;

use Hryvinskyi\DeferJs\Model\ScriptExclusion\GoogleTagManager;
use PHPUnit\Framework\TestCase;

/**
 * A script tag that mentions `googletagmanager.com`, in that exact case, stays in place.
 */
class GoogleTagManagerTest extends TestCase
{
    /**
     * The host in the source or in the inline code keeps the tag.
     *
     * @dataProvider mentioningProvider
     * @param string $script the whole tag
     * @return void
     */
    public function testTagMentioningTheHostIsExcluded(string $script): void
    {
        $this->assertTrue((new GoogleTagManager())->isExcluded($script));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function mentioningProvider(): array
    {
        return [
            'external source' => ['<script async src="https://www.googletagmanager.com/gtag/js?id=G-1"></script>'],
            'inline loader' => [
                "<script>(function(w,d){var j=d.createElement('script');\n"
                . "j.src='https://www.googletagmanager.com/gtm.js?id=GTM-1';})(window,document);</script>",
            ],
        ];
    }

    /**
     * A tag without the host, or with it in another case, moves.
     *
     * @dataProvider notMentioningProvider
     * @param string $script the whole tag
     * @return void
     */
    public function testTagNotMentioningTheHostIsNotExcluded(string $script): void
    {
        $this->assertFalse((new GoogleTagManager())->isExcluded($script));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notMentioningProvider(): array
    {
        return [
            'other source' => ['<script src="https://example.com/app.js"></script>'],
            'inline code' => ['<script>window.dataLayer = window.dataLayer || [];</script>'],
            'host in another case' => ['<script src="https://www.GoogleTagManager.com/gtm.js"></script>'],
        ];
    }
}
