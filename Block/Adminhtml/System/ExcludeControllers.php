<?php
/**
 * Copyright (c) 2019-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Block\Adminhtml\System;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Admin rows of full action names (`<module>_<controller>_<action>`) whose pages keep their scripts in place.
 */
class ExcludeControllers extends AbstractFieldArray
{
    /**
     * Declare the single `controller` column without a per-row "Add after" button.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->addColumn('controller', [
            'label' => __('Expression'),
        ]);

        $this->_addAfter = false;

        parent::_construct();
    }
}
