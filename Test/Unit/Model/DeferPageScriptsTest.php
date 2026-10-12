<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model;

use Hryvinskyi\DeferJs\Api\ConfigInterface;
use Hryvinskyi\DeferJs\Api\PageExclusionInterface;
use Hryvinskyi\DeferJs\Api\ScriptRelocatorInterface;
use Hryvinskyi\DeferJs\Model\DeferPageScripts;
use Laminas\Http\Header\ContentType;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\ResponseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * A rendered page has its scripts moved only when the module is on, the request is a full-page HTTP request, the
 * response is an HTML HTTP response and no page exclusion leaves the page alone.
 */
class DeferPageScriptsTest extends TestCase
{
    private const PAGE = '<html><head><script>a()</script></head><body><p>x</p></body></html>';
    private const RELOCATED = '<html><head></head><body><p>x</p><script>a()</script></body></html>';

    /**
     * @var ConfigInterface&MockObject
     */
    private ConfigInterface $config;

    /**
     * @var PageExclusionInterface&MockObject
     */
    private PageExclusionInterface $pageExclusion;

    /**
     * @var ScriptRelocatorInterface&MockObject
     */
    private ScriptRelocatorInterface $scriptRelocator;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->config->method('isEnabled')->willReturn(true);
        $this->pageExclusion = $this->createMock(PageExclusionInterface::class);
        $this->scriptRelocator = $this->createMock(ScriptRelocatorInterface::class);
    }

    /**
     * A disabled module reads nothing of the request and leaves the response alone.
     *
     * @return void
     */
    public function testDisabledModuleLeavesTheResponseAlone(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('isEnabled')->willReturn(false);
        $request = $this->createMock(HttpRequest::class);
        $request->expects($this->never())->method('isAjax');
        $response = $this->response(false);
        $response->expects($this->never())->method('getBody');
        $response->expects($this->never())->method('setBody');
        $this->scriptRelocator->expects($this->never())->method('relocate');

        (new DeferPageScripts($config, $request, $this->pageExclusion, $this->scriptRelocator))->execute($response);
    }

    /**
     * A request that is not an HTTP request has no page to rewrite.
     *
     * @return void
     */
    public function testNonHttpRequestLeavesTheResponseAlone(): void
    {
        $response = $this->response(false);
        $response->expects($this->never())->method('setBody');
        $this->scriptRelocator->expects($this->never())->method('relocate');

        $this->useCase($this->createMock(RequestInterface::class))->execute($response);
    }

    /**
     * A request sent with the `X-Requested-With` header is made by a script and is left alone.
     *
     * @return void
     */
    public function testXmlHttpRequestLeavesTheResponseAlone(): void
    {
        $response = $this->response(false);
        $response->expects($this->never())->method('setBody');
        $this->scriptRelocator->expects($this->never())->method('relocate');

        $this->useCase($this->request(true, []))->execute($response);
    }

    /**
     * A request carrying an `ajax` or `isAjax` parameter, without the header, is left alone too.
     *
     * @dataProvider ajaxParameters
     * @param string $parameter the parameter the request carries
     * @return void
     */
    public function testAjaxParameterWithoutTheHeaderLeavesTheResponseAlone(string $parameter): void
    {
        $response = $this->response(false);
        $response->expects($this->never())->method('setBody');
        $this->scriptRelocator->expects($this->never())->method('relocate');

        $this->useCase($this->request(false, [$parameter => '1']))->execute($response);
    }

    /**
     * The parameters that mark a request as made by a script.
     *
     * @return array<string, array{string}>
     */
    public static function ajaxParameters(): array
    {
        return ['ajax' => ['ajax'], 'isAjax' => ['isAjax']];
    }

    /**
     * A response that is not an HTTP response has no body to read.
     *
     * @return void
     */
    public function testNonHttpResponseIsLeftAlone(): void
    {
        $this->pageExclusion->expects($this->never())->method('isExcluded');
        $this->scriptRelocator->expects($this->never())->method('relocate');

        $this->useCase($this->request(false, []))->execute($this->createMock(ResponseInterface::class));
    }

    /**
     * A response declaring a content type other than HTML is neither scanned nor changed.
     *
     * @return void
     */
    public function testNonHtmlContentTypeLeavesTheResponseAlone(): void
    {
        $response = $this->response(new ContentType('application/json'));
        $response->expects($this->never())->method('getBody');
        $response->expects($this->never())->method('setBody');
        $this->scriptRelocator->expects($this->never())->method('relocate');

        $this->useCase($this->request(false, []))->execute($response);
    }

    /**
     * An excluded page is decided from the full action name and the request URI, and is not even scanned.
     *
     * @return void
     */
    public function testExcludedPageIsLeftAlone(): void
    {
        $this->pageExclusion->expects($this->once())->method('isExcluded')
            ->with('checkout_cart_index', '/checkout/cart/?a=1')
            ->willReturn(true);
        $response = $this->response(false);
        $response->expects($this->never())->method('getBody');
        $response->expects($this->never())->method('setBody');
        $this->scriptRelocator->expects($this->never())->method('relocate');

        $this->useCase($this->request(false, [], 'checkout', 'cart', 'index', '/checkout/cart/?a=1'))
            ->execute($response);
    }

    /**
     * A response without a content type, with an empty one, or with an HTML one in any case and with a charset, is
     * rewritten.
     *
     * @dataProvider htmlContentTypes
     * @param ContentType|false $contentType the header the response carries, false for none
     * @return void
     */
    public function testHtmlPageGetsTheRelocatedBody(ContentType|false $contentType): void
    {
        $this->pageExclusion->method('isExcluded')->willReturn(false);
        $this->scriptRelocator->expects($this->once())->method('relocate')
            ->with(self::PAGE)
            ->willReturn(self::RELOCATED);
        $response = $this->response($contentType);
        $response->method('getBody')->willReturn(self::PAGE);
        $response->expects($this->once())->method('setBody')->with(self::RELOCATED);

        $this->useCase($this->request(false, []))->execute($response);
    }

    /**
     * The content types a page is rewritten under.
     *
     * @return array<string, array{ContentType|false}>
     */
    public static function htmlContentTypes(): array
    {
        return [
            'no header' => [false],
            'html with charset' => [new ContentType('text/html; charset=UTF-8')],
            'upper case' => [new ContentType('TEXT/HTML')],
            'empty value' => [new ContentType('')],
            'only whitespace' => [new ContentType("  \t")],
        ];
    }

    /**
     * A page the relocator gives back unchanged is not written again.
     *
     * @return void
     */
    public function testUnchangedPageIsNotWrittenBack(): void
    {
        $this->pageExclusion->method('isExcluded')->willReturn(false);
        $this->scriptRelocator->expects($this->once())->method('relocate')->willReturn(self::RELOCATED);
        $response = $this->response(false);
        $response->method('getBody')->willReturn(self::RELOCATED);
        $response->expects($this->never())->method('setBody');

        $this->useCase($this->request(false, []))->execute($response);
    }

    /**
     * Build the use case around the shared doubles and the given request.
     *
     * @param RequestInterface $request
     * @return DeferPageScripts
     */
    private function useCase(RequestInterface $request): DeferPageScripts
    {
        return new DeferPageScripts($this->config, $request, $this->pageExclusion, $this->scriptRelocator);
    }

    /**
     * An HTTP request whose AJAX check runs for real against the given header and parameters.
     *
     * @param bool $xmlHttpRequest whether the `X-Requested-With` header marks it
     * @param array<string, string> $parameters the request parameters
     * @param string $module the route's module name
     * @param string $controller the controller name
     * @param string $action the action name
     * @param string $uri the request URI
     * @return HttpRequest&MockObject
     */
    private function request(
        bool $xmlHttpRequest,
        array $parameters,
        string $module = 'cms',
        string $controller = 'index',
        string $action = 'index',
        string $uri = '/'
    ): HttpRequest {
        $request = $this->createPartialMock(HttpRequest::class, [
            'isXmlHttpRequest',
            'getParam',
            'getModuleName',
            'getControllerName',
            'getActionName',
            'getRequestUri',
        ]);
        $request->method('isXmlHttpRequest')->willReturn($xmlHttpRequest);
        $request->method('getParam')->willReturnCallback(
            static fn (string $key): ?string => $parameters[$key] ?? null
        );
        $request->method('getModuleName')->willReturn($module);
        $request->method('getControllerName')->willReturn($controller);
        $request->method('getActionName')->willReturn($action);
        $request->method('getRequestUri')->willReturn($uri);

        return $request;
    }

    /**
     * An HTTP response carrying the given content type header.
     *
     * @param ContentType|false $contentType the header, false for none
     * @return HttpResponse&MockObject
     */
    private function response(ContentType|false $contentType): HttpResponse
    {
        $response = $this->createMock(HttpResponse::class);
        $response->method('getHeader')->with('Content-Type')->willReturn($contentType);

        return $response;
    }
}
