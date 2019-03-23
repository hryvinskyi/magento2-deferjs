<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model\PassesValidator\Validators;

use Hryvinskyi\DeferJs\Helper\Config;
use Hryvinskyi\DeferJs\Model\PassesValidator\ValidatorInterface;
use Magento\Framework\App\Response\Http;

/**
 * Class SkipScriptByAttribute
 */
class SkipScriptByAttribute implements ValidatorInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * SkipScriptByAttribute constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config) {
        $this->config = $config;
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
        $return = false;

        if (stripos($script, $this->config->getDisableAttribute()) !== false) {
            $return = true;
        }

        return $return;
    }
}