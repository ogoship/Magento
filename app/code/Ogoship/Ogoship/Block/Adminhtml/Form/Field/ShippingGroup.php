<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ogoship\Ogoship\Block\Adminhtml\Form\Field;

use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * HTML select element block with customer groups options
 */
class ShippingGroup extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * Customer groups cache
     *
     * @var array
     */
    private $_shippingGroups;

    /**
     * Flag whether to add group all option or no
     *
     * @var bool
     */
    protected $_addGroupAllOption = false;

    /**
     * @var GroupManagementInterface
     */
    protected $groupManagement;

    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Construct
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param GroupManagementInterface $groupManagement
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        GroupManagementInterface $groupManagement,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->groupManagement = $groupManagement;
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Retrieve allowed customer groups
     *
     * @param int $groupId return name by customer group id
     * @return array|string
     */
    protected function _getShippingGroups($groupId = null)
    {
        
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$this->_shippingGroups = $objectManager->create('Ogoship\Ogoship\Helper\Shippingmethods')->getActiveCarriers();
		return $this->_shippingGroups;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            if ($this->_addGroupAllOption) {
                $this->addOption(
                    'all',__('ALL Shipping methods')
                );
            }
            foreach ($this->_getShippingGroups() as $groupId => $groupLabel) {
				$this->addOption($groupLabel['value'], addslashes($groupLabel['label']));
            }
        }
        return parent::_toHtml();
    }
}
