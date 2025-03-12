<?php
/**
 * Copyright (c) 2020-2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
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
     * Check if a string matches a wildcard pattern
     *
     * @param string $string The string to check
     * @param string $pattern The wildcard pattern (using * as wildcard)
     * @return bool True if the string matches the pattern
     */
    public function checkPattern(string $string, string $pattern): bool
    {
        // Convert wildcard pattern to regex
        $regex = preg_quote($pattern, '/');
        $regex = str_replace('\*', '.*', $regex);
        $regex = '/^' . $regex . '$/';

        return (bool)preg_match($regex, $string);
    }
}
