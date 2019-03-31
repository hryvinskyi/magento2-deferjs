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
 * Class SkipScriptsByPath
 */
class SkipScriptsByPath implements ValidatorInterface
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
        $paths = Json::decode($this->config->getExcludePaths());
        $paths = ArrayHelper::getColumn($paths, 'path', false);

        $return = false;

        if (in_array($this->request->getRequestUri(), $paths)) {
            $return = true;
        }

        return $return;
    }
}
