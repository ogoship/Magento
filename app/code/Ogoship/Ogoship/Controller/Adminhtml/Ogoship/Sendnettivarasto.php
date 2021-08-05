<?php
/**
 *
 * Copyright © 2015 Ogoshipcommerce. All rights reserved.
 */
namespace Ogoship\Ogoship\Controller\Adminhtml\Ogoship;

use Magento\Backend\App\Action;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

// $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
// $lib_internal = $object_manager->get('\Magento\Framework\App\Filesystem\DirectoryList')->getPath('lib_internal');      
// $lib_file = $lib_internal.'/nettivarasto/API.php';
// require_once($lib_file);

class Sendnettivarasto extends \Magento\Sales\Controller\Adminhtml\Order
{
    protected $_increment_id;
    protected $_quote;
    private $_objectmanager;
    private $_dir;
    private $_orderinterface;
    private $_order;
    private $_orderStatusRepository;

    public function __construct(
        Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Framework\App\Filesystem\DirectoryList $directorylist,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface,
        \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $orderStatusRepository
        ) 
    {
        parent::__construct($context, $coreRegistry, $fileFactory, $translateInline,
            $resultPageFactory, $resultJsonFactory, $resultLayoutFactory, $resultRawFactory,
            $orderManagement, $orderRepository, $logger);
            $this->_objectManager = $objectmanager::getInstance();
            $this->_dir = $directorylist;
            $this->_orderinterface = $orderInterface;
            $this->_orderStatusRepository = $orderStatusRepository;
    }
    public function createorder($increment_id, $quote, $order)
    {
        $this->_increment_id = $increment_id;
        $this->_quote = $quote;
        $this->_order = $order;
    }

    function is_admin() {
        if($this->_objectmanager == null){
            $this->_objectmanager = \Magento\Framework\App\ObjectManager::getInstance();
        }
        $state =  $this->_objectmanager->get('Magento\Framework\App\State');
        return 'adminhtml' === $state->getAreaCode();
    }

    public function execute()
    {
        return $this->sendorder();
    }

	public function sendorder()
    {
        if($this->_objectmanager == null){
            $this->_objectmanager = \Magento\Framework\App\ObjectManager::getInstance();
        }
        $lib_internal = $this->_dir->getPath('lib_internal');      
        $lib_file = $lib_internal.'/nettivarasto/API.php';
        require_once($lib_file);

		$resultRedirect = $this->resultRedirectFactory->create();
		$order_id = $this->getRequest()->getParam('order_id');

        if (empty($this->_increment_id) && empty($order_id) && empty($this->_order)) {
            if($this->is_admin()){
                $this->messageManager->addError(__("No order found for sending to Ogoship."));
            }
            return $resultRedirect->setPath('sales/*/');
        }
       
        $_order = null;
        if(!empty($this->_order))
        {
            $_order = $this->_order;
        }
        if(!empty($this->_increment_id))
        {
            $order_id = $this->_increment_id;
        } else {
            $order_id = $this->_orderinterface->load($order_id)->getIncrementId();
        }
        $this->_objectManager->get('Psr\Log\LoggerInterface')->debug('Exporting order "' . $order_id . '" to OGOship');
        if(empty($_order)){
            $_order = $this->_orderinterface->loadByIncrementId($order_id);
        }
		if (!empty($_order)) {
            try {
				$merchant_id = $this->_objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->getCurrentStoreConfigValue('Ogoship/view/merchant_id');
				$secret_token = $this->_objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->getCurrentStoreConfigValue('Ogoship/view/secret_token');
				$api_call = new \NettivarastoAPI($merchant_id, $secret_token);
				$order = new \NettivarastoAPI_Order($api_call,$order_id);
				$nettivarasto_shipping_method = isset($this->_quote) ? $this->_quote->getShippingAddress()->getShippingMethod() : $_order->getShippingMethod();
                if(isset($this->_quote) && empty($nettivarasto_shipping_method))
                {
                    $nettivarasto_shipping_method = $_order->getShippingMethod();
                }
                $nettivarasto_shipping_methods = explode("_", $nettivarasto_shipping_method, 2);
				$nettivarasto_shipping_method = 'nettivarasto_code_'. (isset($nettivarasto_shipping_methods[1]) ? $nettivarasto_shipping_methods[1] : "");
				if(!empty($nettivarasto_shipping_methods)) {
					$_ogoship_shipping_code = $this->_objectManager->get('\Ogoship\Ogoship\Helper\Shippingmethods')->getConfigValue($nettivarasto_shipping_methods[0]);
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
				$orderItems = (isset($this->_quote) && !empty($this->_quote->getAllVisibleItems()) ) ? $this->_quote->getAllVisibleItems() : $_order->getAllVisibleItems();
				$shippingAddress = (isset($this->_quote) && !empty($this->_quote->getShippingAddress()) ) ? $this->_quote->getShippingAddress() : $_order->getShippingAddress();
				$index=0;
				foreach ($orderItems as $item) {
				    $product_id = $this->_objectManager->get('Magento\Catalog\Model\Product')->getIdBySku($item->getSku());
				    $_product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($product_id);
				    $export_to_ogoship = $_product->getExportToOgoship();
				    if(empty($export_to_ogoship)){
					    $order->setOrderLineCode( $index, ($item->getSku()));
						$order->setOrderLineQuantity( $index, empty($item->getQtyOrdered()) ? intval($item->getQty()) : intval($item->getQtyOrdered()));
						$order->setOrderLinePrice( $index, $item->getPrice());
					    $index++;
				    }
				}
				
                $order->setPriceTotal((isset($_quote) && !empty($_quote->getGrandTotal()) ) ? $_quote->getGrandTotal() : $_order->getGrandTotal());
                $order->setPriceCurrency((isset($_quote) && !empty($_quote->getQuoteCurrynceCode()) )? $_quote->getQuoteCurrencyCode() : $_order->getOrderCurrencyCode());
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
                  try{
                    $comment = $_order->addStatusHistoryComment(__('Order transferred to Ogoship.'));
                    $this->_orderStatusRepository->save($comment);
                  } catch(\Exception $e)
                  {
                    $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                  }
				} else {
                  $error_warning = 'Error - Ogoship API: '. $api_call->getLastError();
                  if($this->is_admin())
                  {
				  $this->messageManager->addError($error_warning);				  
                  }
                  $this->_objectManager->get('Psr\Log\LoggerInterface')->error($error_warning);
				}   
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                if($this->is_admin()){
                $this->messageManager->addError($e->getMessage());
                }
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            } catch (\Exception $e) {
                if($this->is_admin()){
				$this->messageManager->addError($e->getMessage());
                }
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            }
        } else {
            $this->_objectManager->get('Psr\Log\LoggerInterface')->error("Order " . $order_id . " not found for export to Ogoship");
        }
        if(empty($this->_increment_id)){
        return $resultRedirect->setPath('sales/order/view', ['order_id' => $_order->getId()]);
        }
    }
}
