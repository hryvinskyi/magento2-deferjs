<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Integration\Plugin;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * A storefront page dispatched through the real front controller, plugin wiring, exclusion pools and settings.
 *
 * The page is one that renders without a search service. The assertions follow the require.js include, a script
 * tag the root template always prints in the head; other head scripts are not asserted on, because scripts kept in
 * place by an exclusion, or added after the page is rewritten, legitimately stay in the head.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class MoveJsToFooterTest extends AbstractController
{
    private const PAGE = 'customer/account/login';
    private const REQUIRE_JS_TAG = '~<script\b[^>]*\bsrc="[^"]*/requirejs/require(?:\.min)?\.js"[^>]*>~i';

    /**
     * @var array<mixed>|null The server variables before the test, restored after it; nullable because the
     *      test framework empties the properties of a finished test
     */
    private ?array $server = null;

    /**
     * Give the request the client address every web request has; storefront code reads it from the server
     * variables
     *
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->server = $_SERVER;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        if ($this->server !== null) {
            $_SERVER = $this->server;
        }
        parent::tearDown();
    }

    /**
     * With the module on, the head's scripts end up after the head, just before the end of the body.
     *
     * @magentoConfigFixture current_store hryvinskyi_defer_js/general/enabled 1
     * @return void
     */
    public function testEnabledModuleMovesHeadScriptsBeforeTheBodyEnd(): void
    {
        $page = $this->renderPage();

        $script = $this->requireJsPosition($page);
        self::assertGreaterThan($this->position($page, '</head>'), $script, 'require.js is still in the head');
        self::assertLessThan($this->position($page, '</body>'), $script, 'require.js is after the body');
    }

    /**
     * An excluded controller's page keeps its scripts where the page put them.
     *
     * @magentoConfigFixture current_store hryvinskyi_defer_js/general/enabled 1
     * @magentoConfigFixture current_store hryvinskyi_defer_js/general/exclude_controllers {"a":{"controller":"customer_account_login"}}
     * @return void
     */
    public function testExcludedControllerKeepsItsScriptsInTheHead(): void
    {
        $page = $this->renderPage();

        self::assertLessThan($this->position($page, '</head>'), $this->requireJsPosition($page));
    }

    /**
     * With the module off, the page is left as rendered.
     *
     * @magentoConfigFixture current_store hryvinskyi_defer_js/general/enabled 0
     * @return void
     */
    public function testDisabledModuleKeepsScriptsInTheHead(): void
    {
        $page = $this->renderPage();

        self::assertLessThan($this->position($page, '</head>'), $this->requireJsPosition($page));
    }

    /**
     * Dispatch a plain GET request to the storefront page and return the body it rendered.
     *
     * @return string
     */
    private function renderPage(): string
    {
        $request = $this->getRequest();
        self::assertInstanceOf(HttpRequest::class, $request);
        $request->setMethod(HttpRequest::METHOD_GET);
        $request->setRequestUri('/' . self::PAGE . '/');

        $this->dispatch(self::PAGE);

        $response = $this->getResponse();
        self::assertInstanceOf(HttpResponse::class, $response);
        self::assertSame(200, $response->getHttpResponseCode());

        return (string) $response->getBody();
    }

    /**
     * Where the require.js include starts in the page.
     *
     * @param string $page
     * @return int
     */
    private function requireJsPosition(string $page): int
    {
        self::assertSame(
            1,
            preg_match(self::REQUIRE_JS_TAG, $page, $match, PREG_OFFSET_CAPTURE),
            'The page prints no require.js include'
        );

        return $match[0][1];
    }

    /**
     * Where the first occurrence of a tag starts in the page.
     *
     * @param string $page
     * @param string $tag
     * @return int
     */
    private function position(string $page, string $tag): int
    {
        $position = stripos($page, $tag);
        self::assertIsInt($position, sprintf('The page has no %s', $tag));

        return $position;
    }
}
