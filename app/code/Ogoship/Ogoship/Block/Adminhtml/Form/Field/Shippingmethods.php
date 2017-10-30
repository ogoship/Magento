<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Adminhtml catalog inventory "Minimum Qty Allowed in Shopping Cart" field
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Ogoship\Ogoship\Block\Adminhtml\Form\Field;

class Shippingmethods extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var Customergroup
     */
    protected $_groupRenderer;

    /**
     * Retrieve group column renderer
     *
     * @return Customergroup
     */
	
    protected function _getGroupRenderer()
    {
        if (!$this->_groupRenderer) {
            $this->_groupRenderer = $this->getLayout()->createBlock(
                'Ogoship\Ogoship\Block\Adminhtml\Form\Field\ShippingGroup',
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->_groupRenderer->setClass('shipping_group_select');
        }
        return $this->_groupRenderer;
    }
	
	
    /**
     * Prepare to render
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'shipping_group_id',
            ['label' => __('Name'), 'renderer' => $this->_getGroupRenderer()]
        );
		$this->addColumn('shipping_method_code', ['label' => __('Code')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Code');
    }

    /**
     * Prepare existing row data object
     *
     * @param \Magento\Framework\DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
		$optionExtraAttr = [];
        $optionExtraAttr['option_' . $this->_getGroupRenderer()->calcOptionHash($row->getData('shipping_group_id'))] =
            'selected="selected"';
        $row->setData(
            'option_extra_attrs',
            $optionExtraAttr
        );
    }
}
