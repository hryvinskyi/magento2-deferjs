<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\DeferJs\Block\Adminhtml\System;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class ExcludeControllers
 */
class ExcludePaths extends AbstractFieldArray
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('path', [
            'label' => __('Expression'),
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');

        parent::_construct();
    }
}
