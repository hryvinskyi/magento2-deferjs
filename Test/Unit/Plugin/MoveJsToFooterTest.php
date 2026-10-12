<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Plugin;

use Hryvinskyi\DeferJs\Api\DeferPageScriptsInterface;
use Hryvinskyi\DeferJs\Plugin\MoveJsToFooter;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The plugin renders the result first, then hands the same response to the use case, and returns what the
 * render returned.
 */
class MoveJsToFooterTest extends TestCase
{
    /**
     * @var DeferPageScriptsInterface&MockObject
     */
    private DeferPageScriptsInterface $deferPageScripts;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->deferPageScripts = $this->createMock(DeferPageScriptsInterface::class);
    }

    /**
     * The render runs once with the response, the use case runs after it with the same response, and the
     * render's value comes back untouched.
     *
     * @dataProvider renderResults
     * @param mixed $rendered what the render returns
     * @return void
     */
    public function testRendersThenDefersAndReturnsTheRenderValue(mixed $rendered): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $calls = [];
        $proceed = static function (ResponseInterface $given) use (&$calls, $response, $rendered): mixed {
            self::assertSame($response, $given);
            $calls[] = 'render';

            return $rendered;
        };
        $this->deferPageScripts->expects($this->once())->method('execute')
            ->with($this->identicalTo($response))
            ->willReturnCallback(static function () use (&$calls): void {
                $calls[] = 'defer';
            });

        $result = (new MoveJsToFooter($this->deferPageScripts))
            ->aroundRenderResult($this->createMock(ResultInterface::class), $proceed, $response);

        $this->assertSame($rendered, $result);
        $this->assertSame(['render', 'defer'], $calls);
    }

    /**
     * Values a render may return: the result itself, nothing, or something else entirely.
     *
     * @return array<string, array{mixed}>
     */
    public static function renderResults(): array
    {
        return [
            'an object' => [new \stdClass()],
            'null' => [null],
            'a string' => ['rendered'],
        ];
    }

    /**
     * The result itself, as most renders return it, comes back as the same instance.
     *
     * @return void
     */
    public function testReturnsTheSameResultInstance(): void
    {
        $subject = $this->createMock(ResultInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $proceed = static fn (ResponseInterface $given): ResultInterface => $subject;

        $result = (new MoveJsToFooter($this->deferPageScripts))->aroundRenderResult($subject, $proceed, $response);

        $this->assertSame($subject, $result);
    }

    /**
     * A render that fails leaves no page to rewrite: the use case is not called and the failure propagates.
     *
     * @return void
     */
    public function testFailingRenderSkipsTheUseCase(): void
    {
        $this->deferPageScripts->expects($this->never())->method('execute');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('render failed');

        (new MoveJsToFooter($this->deferPageScripts))->aroundRenderResult(
            $this->createMock(ResultInterface::class),
            static function (): never {
                throw new \RuntimeException('render failed');
            },
            $this->createMock(ResponseInterface::class)
        );
    }
}
