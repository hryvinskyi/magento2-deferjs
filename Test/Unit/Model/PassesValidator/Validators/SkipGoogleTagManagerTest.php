<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Test\Unit\Model\PassesValidator\Validators;

use Hryvinskyi\DeferJs\Model\PassesValidator\Validators\SkipGoogleTagManager;
use Magento\Framework\App\Response\Http;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class SkipGoogleTagManagerTest extends TestCase
{
    /**
     * @var Http
     */
    private $http;

    /**
     * @var SkipGoogleTagManager
     */
    private $model;

    /**
     * Sets up the fixture
     */
    protected function setUp()
    {
        $this->http = (new ObjectManager($this))->getObject(Http::class);
        $this->model = (new ObjectManager($this))->getObject(SkipGoogleTagManager::class);
    }

    /**
     * @return string
     */
    private function getScriptSkipped(): string
    {
        return '<script type="text/javascript">(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push(
            {\'gtm.start\': new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=
            \'//www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,\'script\',\'dataLayer\',\'GTM-FFFFFFF\');</script>';
    }

    /**
     * @return string
     */
    public function getScriptNoSkipped(): string
    {
        return '<script> <!-- ko i18n: \'test\' --> <!-- /ko --> </script>';
    }

    /**
     *
     */
    public function testSkipScript(): void
    {
        $this->assertEquals(true, $this->model->validate($this->getScriptSkipped(), $this->http));
    }

    /**
     *
     */
    public function testNoSkipScript(): void
    {
        $this->assertEquals(false, $this->model->validate($this->getScriptNoSkipped(), $this->http));
    }
}
