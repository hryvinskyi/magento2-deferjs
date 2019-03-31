<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model\PassesValidator\Validators;

use Hryvinskyi\Base\Helper\ArrayHelper;
use Hryvinskyi\Base\Helper\Json;
use Hryvinskyi\DeferJs\Helper\Config;
use Hryvinskyi\DeferJs\Model\PassesValidator\ValidatorInterface;
use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Framework\App\Response\Http;

/**
 * Class SkipScriptsByController
 */
class SkipScriptsByController implements ValidatorInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var RequestHttp
     */
    private $request;

    /**
     * SkipScriptsByController constructor.
     *
     * @param Config $config
     * @param RequestHttp $request
     */
    public function __construct(
        Config $config,
        RequestHttp $request
    ) {
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * Validator function, handle javascript or not
     *
     * @param string $script
     * @param Http $http
     *
     * @return bool
     */
    public function validate(string $script, Http $http): bool
    {
        $moduleName = $this->request->getModuleName();
        $controller = $this->request->getControllerName();
        $action = $this->request->getActionName();

        $controllerFull = $moduleName . '_' . $controller . '_' . $action;
        $controllersConfig = Json::decode($this->config->getExcludeControllers());
        $controllersConfig = ArrayHelper::getColumn($controllersConfig, 'controller', false);

        $return = false;

        if (in_array($controllerFull, $controllersConfig)) {
            $return = true;
        }

        return $return;
    }
}
