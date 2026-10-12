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
 * Admin rows of request URIs whose pages keep their scripts in place.
 */
class ExcludePaths extends AbstractFieldArray
{
    /**
     * Declare the single `path` column without a per-row "Add after" button.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->addColumn('path', [
            'label' => __('Expression'),
        ]);

        $this->_addAfter = false;

        parent::_construct();
    }
}
