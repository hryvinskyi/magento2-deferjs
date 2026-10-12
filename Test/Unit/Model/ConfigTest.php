<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model;

use Hryvinskyi\DeferJs\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The settings are read for the current store view, and the stored exclusion rows become plain lists.
 */
class ConfigTest extends TestCase
{
    private const PATH_ENABLED = 'hryvinskyi_defer_js/general/enabled';
    private const PATH_DISABLE_ATTRIBUTE = 'hryvinskyi_defer_js/general/disable_attribute';
    private const PATH_MINIFY = 'hryvinskyi_defer_js/general/minify_body_scripts';
    private const PATH_MERGE = 'hryvinskyi_defer_js/general/optimize_x_magento_init_scripts';
    private const PATH_CONTROLLERS = 'hryvinskyi_defer_js/general/exclude_controllers';
    private const PATH_PATHS = 'hryvinskyi_defer_js/general/exclude_paths';
    private const PATH_URL_PATTERNS = 'hryvinskyi_defer_js/general/exclude_url_pattern';

    /**
     * @var ScopeConfigInterface&MockObject
     */
    private ScopeConfigInterface&MockObject $scopeConfig;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * Build the configuration reader over a store configuration double and the real JSON serializer.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfig, new Json());
    }

    /**
     * Each flag is read through isSetFlag at store scope, without a scope code.
     *
     * @dataProvider flagProvider
     * @param \Closure(Config): bool $read calls the reader under test
     * @param string $path the configuration path it reads
     * @param bool $stored what the store configuration answers
     * @return void
     */
    public function testFlagsAreReadAtStoreScope(\Closure $read, string $path, bool $stored): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with($path, ScopeInterface::SCOPE_STORE)
            ->willReturn($stored);

        $this->assertSame($stored, $read($this->config));
    }

    /**
     * @return array<string, array{\Closure(Config): bool, string, bool}>
     */
    public static function flagProvider(): array
    {
        $enabled = static fn (Config $config): bool => $config->isEnabled();
        $minify = static fn (Config $config): bool => $config->isMinifyBodyScripts();
        $merge = static fn (Config $config): bool => $config->isMergeXMagentoInit();

        return [
            'enabled on' => [$enabled, self::PATH_ENABLED, true],
            'enabled off' => [$enabled, self::PATH_ENABLED, false],
            'minify on' => [$minify, self::PATH_MINIFY, true],
            'minify off' => [$minify, self::PATH_MINIFY, false],
            'merge on' => [$merge, self::PATH_MERGE, true],
            'merge off' => [$merge, self::PATH_MERGE, false],
        ];
    }

    /**
     * The disable attribute comes back trimmed; a missing or non-text value is an empty string.
     *
     * @dataProvider disableAttributeProvider
     * @param mixed $stored what the store configuration answers
     * @param string $expected the attribute the reader returns
     * @return void
     */
    public function testDisableAttributeIsTrimmed(mixed $stored, string $expected): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(self::PATH_DISABLE_ATTRIBUTE, ScopeInterface::SCOPE_STORE)
            ->willReturn($stored);

        $this->assertSame($expected, $this->config->getDisableAttribute());
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function disableAttributeProvider(): array
    {
        return [
            'as stored' => ['data-deferjs="false"', 'data-deferjs="false"'],
            'surrounding whitespace' => ["  data-deferjs=\"false\"\n", 'data-deferjs="false"'],
            'whitespace only' => ['   ', ''],
            'not set' => [null, ''],
            'not text' => [['data-deferjs'], ''],
        ];
    }

    /**
     * Each list reads its own column of the stored rows, at store scope, in row order.
     *
     * @dataProvider listProvider
     * @param \Closure(Config): list<string> $read calls the reader under test
     * @param string $path the configuration path it reads
     * @param string $column the row key holding the value
     * @return void
     */
    public function testListsReadTheirColumnFromValidRows(\Closure $read, string $path, string $column): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with($path, ScopeInterface::SCOPE_STORE)
            ->willReturn(json_encode([
                '_1589359003110_110' => [$column => 'first'],
                '_1589359003111_111' => [$column => 'second'],
            ], JSON_THROW_ON_ERROR));

        $this->assertSame(['first', 'second'], $read($this->config));
    }

    /**
     * @return array<string, array{\Closure(Config): list<string>, string, string}>
     */
    public static function listProvider(): array
    {
        return [
            'controllers' => [
                static fn (Config $config): array => $config->getExcludedControllers(),
                self::PATH_CONTROLLERS,
                'controller',
            ],
            'paths' => [
                static fn (Config $config): array => $config->getExcludedPaths(),
                self::PATH_PATHS,
                'path',
            ],
            'url patterns' => [
                static fn (Config $config): array => $config->getExcludedUrlPatterns(),
                self::PATH_URL_PATTERNS,
                'pattern',
            ],
        ];
    }

    /**
     * A row without the column, or with another list's column, contributes nothing.
     *
     * @return void
     */
    public function testRowWithoutTheColumnIsSkipped(): void
    {
        $this->givenStoredControllers(json_encode([
            'a' => ['controller' => 'cms_index_index'],
            'b' => ['path' => '/checkout'],
            'c' => [],
            'd' => ['controller' => 'checkout_cart_index'],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame(['cms_index_index', 'checkout_cart_index'], $this->config->getExcludedControllers());
    }

    /**
     * Values are trimmed, and blank or whitespace-only ones are dropped.
     *
     * @return void
     */
    public function testBlankValuesAreDroppedAndValuesTrimmed(): void
    {
        $this->givenStoredControllers(json_encode([
            'a' => ['controller' => '  cms_index_index '],
            'b' => ['controller' => ''],
            'c' => ['controller' => " \t\n"],
            'd' => ['controller' => "checkout_cart_index\n"],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame(['cms_index_index', 'checkout_cart_index'], $this->config->getExcludedControllers());
    }

    /**
     * A column holding anything but a string, and a row that is not an object, are skipped.
     *
     * @return void
     */
    public function testNonTextValuesAndRowsAreSkipped(): void
    {
        $this->givenStoredControllers(json_encode([
            'a' => ['controller' => 42],
            'b' => ['controller' => ['cms_index_index']],
            'c' => ['controller' => null],
            'd' => 'cms_index_index',
            'e' => ['controller' => 'checkout_cart_index'],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame(['checkout_cart_index'], $this->config->getExcludedControllers());
    }

    /**
     * Rows stored as a JSON array rather than an object keyed by row id are read the same way.
     *
     * @return void
     */
    public function testRowsStoredAsArrayAreRead(): void
    {
        $this->givenStoredControllers('[{"controller":"cms_index_index"}]');

        $this->assertSame(['cms_index_index'], $this->config->getExcludedControllers());
    }

    /**
     * A stored value that is not a list of rows yields an empty list instead of an error.
     *
     * @dataProvider unusableListProvider
     * @param mixed $stored what the store configuration answers
     * @return void
     */
    public function testUnusableStoredValueYieldsEmptyList(mixed $stored): void
    {
        $this->givenStoredControllers($stored);

        $this->assertSame([], $this->config->getExcludedControllers());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function unusableListProvider(): array
    {
        return [
            'malformed json' => ['{"a":{"controller":"cms_index_index"}'],
            'json string scalar' => ['"cms_index_index"'],
            'json number scalar' => ['42'],
            'json null' => ['null'],
            'empty string' => [''],
            'whitespace only' => ['  '],
            'not set' => [null],
        ];
    }

    /**
     * Make the store configuration answer the excluded controllers setting with the given value.
     *
     * @param mixed $stored the stored value
     * @return void
     */
    private function givenStoredControllers(mixed $stored): void
    {
        $this->scopeConfig->method('getValue')
            ->with(self::PATH_CONTROLLERS, ScopeInterface::SCOPE_STORE)
            ->willReturn($stored);
    }
}
