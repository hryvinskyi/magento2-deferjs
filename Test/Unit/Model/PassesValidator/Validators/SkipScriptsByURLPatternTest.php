<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model\PassesValidator\Validators;

use Hryvinskyi\DeferJs\Helper\Config;
use Hryvinskyi\DeferJs\Model\PassesValidator\Validators\SkipScriptsByURLPattern;
use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Framework\App\Response\Http;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class SkipScriptsByURLPatternTest extends TestCase
{
    /**
     * @var Config|PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var RequestHttp|PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var Http
     */
    private $http;

    /**
     * @var SkipScriptsByURLPattern
     */
    private $model;

    /**
     * Sets up the fixture
     */
    protected function setUp()
    {
        $this->config = $this->createPartialMock(
            Config::class,
            ['getExcludeUrlPattern']
        );

        $this->request = $this->createPartialMock(
            RequestHttp::class,
            ['getRequestUri']
        );

        $this->config->expects($this->any())->method('getExcludeUrlPattern')
            ->willReturn('{"_1589359003110_110":{"pattern":"*argus-all-weather*"}}');


        $this->http = (new ObjectManager($this))->getObject(Http::class);
        $this->model = (new ObjectManager($this))->getObject(SkipScriptsByURLPattern::class, [
            'config'  => $this->config,
            'request' => $this->request,
        ]);
    }

    /**
     *
     */
    public function testSkipScript(): void
    {
        $this->request->expects($this->any())->method('getRequestUri')->willReturn('/argus-all-weather-tank.html');
        $this->assertEquals(true, $this->model->validate('', $this->http));
    }

    /**
     *
     */
    public function testNoSkipScript(): void
    {
        $this->request->expects($this->any())->method('getRequestUri')->willReturn('/someUrl.html');
        $this->assertEquals(false, $this->model->validate('', $this->http));
    }
}
