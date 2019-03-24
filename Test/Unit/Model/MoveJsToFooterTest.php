<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model;

use Hryvinskyi\DeferJs\Helper\Config;
use Hryvinskyi\DeferJs\Model\Minify\MinifyJsInterface;
use Hryvinskyi\DeferJs\Model\MoveJsToFooter;
use Hryvinskyi\DeferJs\Model\PassesValidator\ValidateSkipper;
use Magento\Framework\App\Response\Http;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class MoveJsToFooterTest extends TestCase
{

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var MinifyJsInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $minifyJs;

    /**
     * @var ValidateSkipper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $validateSkipper;

    /**
     * @var Http
     */
    private $http;

    /**
     * @var MoveJsToFooter
     */
    private $model;

    /**
     * Sets up the fixture
     *
     * @return void
     */
    protected function setUp()
    {
        $this->config = $this->createPartialMock(
            Config::class,
            ['isMinifyBodyScript']
        );

        $this->minifyJs = $this->createPartialMock(
            MinifyJsInterface::class,
            ['execute']
        );

        $this->validateSkipper = $this->createPartialMock(
            ValidateSkipper::class,
            ['execute']
        );

        $this->http = (new ObjectManager($this))->getObject(Http::class);
        $this->model = (new ObjectManager($this))->getObject(MoveJsToFooter::class, [
            'config' => $this->config,
            'minifyJs' => $this->minifyJs,
            'validateSkipper' => $this->validateSkipper,
        ]);
    }

    public function testExecute(): void
    {
        $beforeBody = '<!DOCTYPE html>' .
            '<html>' .
                '<head>' .
                    '<script> <!-- ko i18n: \'test\' --> <!-- /ko --> </script>' .
                '</head>' .
            '<body>' .
                '<h1>My First Heading</h1>' .
                '<p>My first paragraph.</p>' .
            '</body>' .
        '</html>';

        $afterBody = '<!DOCTYPE html>' .
            '<html>' .
                '<head></head>' .
            '<body>' .
                '<h1>My First Heading</h1>' .
                '<p>My first paragraph.</p>' .
                '<script> <!-- ko i18n: \'test\' --> <!-- /ko --> </script>' .
            '</body>' .
        '</html>';
        $this->http->setBody($beforeBody);
        $this->model->execute($this->http);


        $this->assertEquals($this->http->getBody(), $afterBody);
    }
}
