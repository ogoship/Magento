<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ogoship\Ogoship\Helper;

use Magento\Customer\Api\GroupManagementInterface;
use Magento\Store\Model\Store;

/**
 * Shippingmethods value manipulation helper
 */
class Shippingmethods
{
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    
	protected $_shippingConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param GroupManagementInterface $groupManagement
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Shipping\Model\Config $shippingConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->mathRandom = $mathRandom;
        $this->_shippingConfig	=	$shippingConfig;
    }

    /**
     * Retrieve fixed qty value
     *
     * @param int|float|string|null $qty
     * @return float|null
     */
    protected function fixCode($code)
    {
        return !empty($code) ? $code : null;
    }

    /**
     * Generate a storable representation of a value
     *
     * @param int|float|string|array $value
     * @return string
     */
    protected function serializeValue($value)
    {
        if (is_numeric($value)) {
            $data = (float) $value;
            return (string) $data;
        } elseif (is_array($value)) {
            $data = [];
            foreach ($value as $groupId => $code) {
                if (!array_key_exists($groupId, $data)) {
                    $data[$groupId] = $this->fixCode($code);
                }
            }
            if (count($data) == 1 && array_key_exists($this->getAllShippingGroupId(), $data)) {
                return (string) $data[$this->getAllShippingGroupId()];
            }
            return serialize($data);
        } else {
            return '';
        }
    }

    /**
     * Create a value from a storable representation
     *
     * @param int|float|string $value
     * @return array
     */
    protected function unserializeValue($value)
    {
        if (is_numeric($value)) {
            return [$this->getAllShippingGroupId() => $this->fixCode($value)];
        } elseif (is_string($value) && !empty($value)) {
            return unserialize($value);
        } else {
            return [];
        }
    }

    /**
     * Check whether value is in form retrieved by _encodeArrayFieldValue()
     *
     * @param string|array $value
     * @return bool
     */
    protected function isEncodedArrayFieldValue($value)
    {
        if (!is_array($value)) {
            return false;
        }
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!is_array($row)
                || !array_key_exists('shipping_group_id', $row)
                || !array_key_exists('shipping_method_code', $row)
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Encode value to be used in \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param array $value
     * @return array
     */
    protected function encodeArrayFieldValue(array $value)
    {
        $result = [];
        foreach ($value as $groupId => $code) {
            $resultId = $this->mathRandom->getUniqueHash('_');
            $result[$resultId] = ['shipping_group_id' => $groupId, 'shipping_method_code' => $this->fixCode($code)];
        }
        return $result;
    }

    /**
     * Decode value from used in \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param array $value
     * @return array
     */
    protected function decodeArrayFieldValue(array $value)
    {
        $result = [];
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!is_array($row)
                || !array_key_exists('shipping_group_id', $row)
				|| !array_key_exists('shipping_method_code', $row)
            ) {
                continue;
            }
            $groupId = $row['shipping_group_id'];
            $code = $this->fixCode($row['shipping_method_code']);
            $result[$groupId] = $code;
        }
        return $result;
    }

    /**
     * Retrieve min_sale_qty value from config
     *
     * @param int $customerGroupId
     * @param null|string|bool|int|Store $store
     * @return float|null
     */
    public function getConfigValue($shippingGroupId, $store = null)
    {
        $value = $this->scopeConfig->getValue(
            'Ogoship/view/ogoship_shipping_method',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        $value = $this->unserializeValue($value);
        if ($this->isEncodedArrayFieldValue($value)) {
            $value = $this->decodeArrayFieldValue($value);
        }
        $result = null;
        foreach ($value as $groupId => $code) {
            if ($groupId == $shippingGroupId) {
                $result = $code;
                break;
            } elseif ($groupId == $this->getAllShippingGroupId()) {
                $result = $code;
            }
        }
        return $this->fixCode($result);
    }

    /**
     * Make value readable by \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param string|array $value
     * @return array
     */
    public function makeArrayFieldValue($value)
    {
        $value = $this->unserializeValue($value);
        if (!$this->isEncodedArrayFieldValue($value)) {
            $value = $this->encodeArrayFieldValue($value);
        }
        return $value;
    }

    /**
     * Make value ready for store
     *
     * @param string|array $value
     * @return string
     */
    public function makeStorableArrayFieldValue($value)
    {
        if ($this->isEncodedArrayFieldValue($value)) {
            $value = $this->decodeArrayFieldValue($value);
        }
        $value = $this->serializeValue($value);
        return $value;
    }

    public function getActiveCarriers($store = null)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$deliveryMethods = $objectManager->create('\Magento\Shipping\Model\Config')->getActiveCarriers();
		$deliveryMethodsArray = array();
        foreach ($deliveryMethods as $shippigCode => $shippingModel) {
            $shippingTitle = $this->scopeConfig->getValue('carriers/'.$shippigCode.'/title');
            $deliveryMethodsArray[$shippigCode] = array(
                'label' => $shippingTitle,
                'value' => $shippigCode
            );
        }
        return $deliveryMethodsArray;
    }
	
	public function getAllShippingGroupId($store = null)
    {
		return "all";
	}
}
