<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model\PassesValidator\Validators;

use Hryvinskyi\DeferJs\Helper\Config;
use Hryvinskyi\DeferJs\Model\PassesValidator\Validators\SkipScriptByAttribute;
use Magento\Framework\App\Response\Http;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class SkipScriptByAttributeTest extends TestCase
{
    const DEFAULT_SKIP_TAG = 'data-deferjs="false"';

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var Http
     */
    private $http;

    /**
     * @var SkipScriptByAttribute
     */
    private $model;

    /**
     * Sets up the fixture
     */
    protected function setUp()
    {
        $this->config = $this->createPartialMock(
            Config::class,
            ['getDisableAttribute']
        );
        $this->config->expects($this->any())->method('getDisableAttribute')->willReturn(self::DEFAULT_SKIP_TAG);
        $this->http = (new ObjectManager($this))->getObject(Http::class);
        $this->model = (new ObjectManager($this))->getObject(SkipScriptByAttribute::class, [
            'config' => $this->config
        ]);
    }

    /**
     * @return string
     */
    private function getBodySkipped(): string
    {
        return '<!DOCTYPE html>' .
            '<html>' .
                '<head>' .
                    '<script ' . self::DEFAULT_SKIP_TAG . '> <!-- ko i18n: \'test\' --> <!-- /ko --> </script>' .
                '</head>' .
                '<body>' .
                    '<h1>My First Heading</h1>' .
                    '<p>My first paragraph.</p>' .
                '</body>' .
            '</html>';
    }

    /**
     * @return string
     */
    public function getBodyNoSkipped(): string
    {
        return '<!DOCTYPE html>' .
            '<html>' .
                '<head>' .
                    '<script> <!-- ko i18n: \'test\' --> <!-- /ko --> </script>' . '</head>' .
                '<body>' .
                    '<h1>My First Heading</h1>' .
                    '<p>My first paragraph.</p>' .
                '</body>' .
            '</html>';
    }

    /**
     *
     */
    public function testSkipScript(): void
    {
        $this->assertEquals(true, $this->model->validate($this->getBodySkipped(), $this->http));
    }

    /**
     *
     */
    public function testNoSkipScript(): void
    {
        $this->assertEquals(false, $this->model->validate($this->getBodyNoSkipped(), $this->http));
    }
}
