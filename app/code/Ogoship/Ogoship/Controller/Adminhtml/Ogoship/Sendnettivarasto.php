<?php
/**
 *
 * Copyright © 2015 Ogoshipcommerce. All rights reserved.
 */
namespace Ogoship\Ogoship\Controller\Adminhtml\Ogoship;

$object_manager = \Magento\Framework\App\ObjectManager::getInstance();
$lib_internal = $object_manager->get('\Magento\Framework\App\Filesystem\DirectoryList')->getPath('lib_internal');      
$lib_file = $lib_internal.'/nettivarasto/API.php';
require_once($lib_file);

class Sendnettivarasto extends \Magento\Sales\Controller\Adminhtml\Order
{
    protected $_increment_id;
    protected $_quote;

    public function create($increment_id, $quote)
    {
        $this->_increment_id = $increment_id;
        $this->_quote = $quote;
    }

    function is_admin() {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $state =  $object_manager->get('Magento\Framework\App\State');
        return 'adminhtml' === $state->getAreaCode();
    }

	public function execute()
    {
		$resultRedirect = $this->resultRedirectFactory->create();
		$order_id = $this->getRequest()->getParam('order_id');
        file_put_contents('php://stderr', "order_id: " . $order_id . "\n");
        if (empty($this->_increment_id) && empty($order_id)) {
                $this->messageManager->addError(__("No order found to send."));
            return $resultRedirect->setPath('sales/*/');
        }
       
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $_order = null;
        if(!empty($this->_increment_id))
        {
            $order_id = $this->_increment_id;
        } else {
            $order_id = $objectManager->create('Magento\Sales\Model\Order')->load($order_id)->getIncrementId();
        }
        $objectManager->get('Psr\Log\LoggerInterface')->debug('Exporting order "' . $order_id . '" to OGOship');
        $_order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($order_id);
		if (!empty($_order)) {
            try {
				$merchant_id = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->getCurrentStoreConfigValue('Ogoship/view/merchant_id');
				$secret_token = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->getCurrentStoreConfigValue('Ogoship/view/secret_token');
				$api_call = new \NettivarastoAPI($merchant_id, $secret_token);
				$order = new \NettivarastoAPI_Order($api_call,$order_id);
				$nettivarasto_shipping_method = isset($this->_quote) ? $this->_quote->getShippingAddress()->getShippingMethod() : $_order->getShippingMethod();
                $nettivarasto_shipping_methods = explode("_", $nettivarasto_shipping_method, 2);
				$nettivarasto_shipping_method = 'nettivarasto_code_'. (isset($nettivarasto_shipping_methods[1]) ? $nettivarasto_shipping_methods[1] : "");
				if(!empty($nettivarasto_shipping_methods)) {
					$_ogoship_shipping_code = $objectManager->get('\Ogoship\Ogoship\Helper\Shippingmethods')->getConfigValue($nettivarasto_shipping_methods[0]);
					if(!empty($_ogoship_shipping_code)) {
						$nettivarasto_shipping_method = $_ogoship_shipping_code;
					} else {
                        if($this->is_admin())
                        {
						$this->messageManager->addError(__('Order Shipping method not enabled in settings'));
						return $resultRedirect->setPath('sales/order/view', ['order_id' => $_order->getId()]);
                        }
                        return;
					}
				}
				$orderItems = isset($this->_quote) ? $this->_quote->getAllVisibleItems() : $_order->getAllVisibleItems();
				$shippingAddress = isset($this->_quote) ? $this->_quote->getShippingAddress() : $_order->getShippingAddress();
				$index=0;
				foreach ($orderItems as $item) {
				    $product_id = $objectManager->get('Magento\Catalog\Model\Product')->getIdBySku($item->getSku());
				    $_product = $objectManager->create('Magento\Catalog\Model\Product')->load($product_id);
				    $export_to_ogoship = $_product->getExportToOgoship();
                    file_put_contents('php://stderr', "export: " . $item->getSku() . " : " . $export_to_ogoship . "\n");
				    if(empty($export_to_ogoship)){
					    $order->setOrderLineCode( $index, ($item->getSku()));
						$order->setOrderLineQuantity( $index, empty($item->getQtyOrdered()) ? intval($item->getQty()) : intval($item->getQtyOrdered()));
						$order->setOrderLinePrice( $index, $item->getPrice());
					    $index++;
				    }
				}
				
				$order->setPriceTotal($_order->getGrandTotal());
				$order->setCustomerName($shippingAddress->getFirstname().' '.$shippingAddress->getLastname());
				$order->setCustomerAddress1($shippingAddress->getStreet());
				$order->setCustomerAddress2('');
				$order->setCustomerCity($shippingAddress->getCity());
				$order->setCustomerCountry($shippingAddress->getCountryId());
				$order->setCustomerEmail($shippingAddress->getEmail());
				$order->setCustomerPhone($shippingAddress->getTelephone());
				$order->setCustomerZip($shippingAddress->getPostcode());
				$order->setShipping($nettivarasto_shipping_method);
				if ($order->save()) {
                  if($this->is_admin())
                  {
				  $this->messageManager->addSuccess(__('Order successfully transferred to Ogoship.'));
                  }
				} else {
                  $error_warning = 'Error - Ogoship API: '. $api_call->getLastError();
                  if($this->is_admin())
                  {
				  $this->messageManager->addError($error_warning);				  
                  }
                  $objectManager->get('Psr\Log\LoggerInterface')->error($error_warning);
				}   
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                if($this->is_admin()){
                $this->messageManager->addError($e->getMessage());
                }
                $objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            } catch (\Exception $e) {
                if($this->is_admin()){
				$this->messageManager->addError($e->getMessage());
                }
                $objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            }
        }
        if(empty($this->_increment_id)){
        return $resultRedirect->setPath('sales/order/view', ['order_id' => $_order->getId()]);
        }
    }
}
