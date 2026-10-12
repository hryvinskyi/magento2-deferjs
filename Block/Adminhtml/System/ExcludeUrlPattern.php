<?php
/**
 * Copyright (c) 2020-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Block\Adminhtml\System;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Admin rows of wildcard patterns; a page whose request URI or full action name matches one keeps its scripts in place.
 */
class ExcludeUrlPattern extends AbstractFieldArray
{
    /**
     * Declare the single `pattern` column without a per-row "Add after" button.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->addColumn('pattern', [
            'label' => __('Expression'),
        ]);

        $this->_addAfter = false;

        parent::_construct();
    }
}
