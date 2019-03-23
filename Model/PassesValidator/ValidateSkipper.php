<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Model\PassesValidator;

use Hryvinskyi\Base\Helper\VarDumper;
use Magento\Framework\App\Response\Http;

/**
 * Class Validate
 */
class ValidateSkipper
{
    /**
     * @var EntityList
     */
    private $deferJsPassesValidators;

    /**
     * Validate constructor.
     *
     * @param EntityList $deferJsPassesValidators
     */
    public function __construct(
        EntityList $deferJsPassesValidators
    ) {
        $this->deferJsPassesValidators = $deferJsPassesValidators;
    }

    /**
     * @param string $script
     * @param Http $http
     *
     * @return bool
     */
    public function execute(string $script, Http $http): bool
    {
        foreach ($this->deferJsPassesValidators->getList() as $deferJsPassesValidator) {
            if($deferJsPassesValidator->validate($script, $http)) {
                return true;
            }
        }

        return false;
    }
}