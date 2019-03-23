<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);


namespace Hryvinskyi\DeferJs\Model\PassesValidator\Validators;

use Hryvinskyi\DeferJs\Model\PassesValidator\ValidatorInterface;
use Magento\Framework\App\Response\Http;

/**
 * Class SkipGoogleTagManager
 */
class SkipGoogleTagManager implements ValidatorInterface
{
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
        return !!preg_match("/.*?googletagmanager\.com.*?/s", $script);
    }
}