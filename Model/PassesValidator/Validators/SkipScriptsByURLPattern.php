<?php
/**
 * Copyright (c) 2020. Volodymyr Hryvinskyi.  All rights reserved.
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
 * Class SkipScriptsByURLPattern
 */
class SkipScriptsByURLPattern implements ValidatorInterface
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
        $executeUrlPattern = Json::decode($this->config->getExcludeUrlPattern());
        $executeUrlPattern = ArrayHelper::getColumn($executeUrlPattern, 'pattern', false);

        $return = false;

        foreach ($executeUrlPattern as $pattern) {
            if ($this->checkPattern($this->request->getRequestUri(), $pattern)) {
                $return = true;
                break;
            }
        }

        return $return;
    }

    /**
     * @param $string
     * @param $pattern
     *
     * @return bool
     */
    public function checkPattern(string $string, string $pattern): bool
    {
        $parts = explode('*', $pattern);
        $index = 0;

        $shouldBeFirst = true;

        foreach ($parts as $part) {
            if ($part == '') {
                $shouldBeFirst = false;
                continue;
            }

            $index = strpos($string, $part, $index);

            if ($index === false) {
                return false;
            }

            if ($shouldBeFirst && $index > 0) {
                return false;
            }

            $shouldBeFirst = false;
            $index += strlen($part);
        }

        if (count($parts) == 1) {
            return $string == $pattern;
        }

        $last = end($parts);

        if ($last == '') {
            return true;
        }

        if (strrpos($string, $last) === false) {
            return false;
        }

        if (strlen($string) - strlen($last) - strrpos($string, $last) > 0) {
            return false;
        }

        return true;
    }
}