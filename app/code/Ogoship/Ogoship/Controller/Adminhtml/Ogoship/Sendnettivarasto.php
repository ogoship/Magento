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

	public function execute()
    {
		$resultRedirect = $this->resultRedirectFactory->create();
		$order_id = $this->getRequest()->getParam('order_id');
        if (empty($order_id)) {
            $this->messageManager->addError(__('You have not send nettivarasto the item.'));
            return $resultRedirect->setPath('sales/*/');
        }
       
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$_order = $objectManager->create('Magento\Sales\Model\Order')->load($order_id);
		if (!empty($_order)) {
            try {
				$merchant_id = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->getCurrentStoreConfigValue('Ogoship/view/merchant_id');
				$secret_token = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->getCurrentStoreConfigValue('Ogoship/view/secret_token');
				$api_call = new \NettivarastoAPI($merchant_id, $secret_token);
				$order = new \NettivarastoAPI_Order($api_call,$order_id);
				$nettivarasto_shipping_method	=	$_order->getShippingMethod();
				$nettivarasto_shipping_methods = explode("_", $nettivarasto_shipping_method);
				$nettivarasto_shipping_method = 'nettivarasto_code_'.$nettivarasto_shipping_methods[1];
				if(!empty($nettivarasto_shipping_methods)) {
					$_ogoship_shipping_code = $objectManager->get('\Ogoship\Ogoship\Helper\Shippingmethods')->getConfigValue($nettivarasto_shipping_methods[0]);
					if(!empty($_ogoship_shipping_code)) {
						$nettivarasto_shipping_method = $_ogoship_shipping_code;
					} else {
						$this->messageManager->addSuccess(__('Order Shipping method not enabled in settings'));
						return $resultRedirect->setPath('sales/order/view', ['order_id' => $_order->getId()]);
					}
				}
				$orderItems = $_order->getAllItems();
				$shippingAddress = $_order->getShippingAddress();
				$index=0;
				foreach ($orderItems as $item) {
				    $product_id = $objectManager->get('Magento\Catalog\Model\Product')->getIdBySku($item->getSku());
				    $_product = $objectManager->create('Magento\Catalog\Model\Product')->load($product_id);
				    $export_to_ogoship = $_product->getExportToOgoship();
				    if(empty($export_to_ogoship)){
					    $order->setOrderLineCode( $index, ($item->getSku()));
					    $order->setOrderLineQuantity( $index, intval($item->getQtyOrdered()));
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
				  $this->messageManager->addSuccess(__('Order successfully transferred to Ogoship.'));
				} else {
				  $error_warning = 'Error - Ogoship API'. $api_call->getLastError();
				  $this->messageManager->addError($error_warning);				  
				}   
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
				$this->messageManager->addError($e->getMessage());
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            }
        }
        return $resultRedirect->setPath('sales/order/view', ['order_id' => $_order->getId()]);
    }
}
