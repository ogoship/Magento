<?php
/**
 * Copyright © 2015 Ogoshipcommerce. All rights reserved.
 */
namespace Ogoship\Ogoship\Model;

$object_manager = \Magento\Framework\App\ObjectManager::getInstance();
$lib_internal = $object_manager->get('\Magento\Framework\App\Filesystem\DirectoryList')->getPath('lib_internal');      
$lib_file = $lib_internal.'/nettivarasto/API.php';
require_once($lib_file);
/**
 * Ogoship Config model
 */
class Ogoship extends \Magento\Framework\DataObject
{

	/**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
	/**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface 
     */
    protected $_scopeConfig;
	/**
     * @var \Magento\Framework\App\Config\ValueInterface
     */
    protected $_backendModel;
	/**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;
	/**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $_configValueFactory;
	/**
     * @var int $_storeId
     */
    protected $_storeId;
	/**
     * @var string $_storeCode
     */
    protected $_storeCode;

	/**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager,
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
     * @param \Magento\Framework\App\Config\ValueInterface $backendModel,
     * @param \Magento\Framework\DB\Transaction $transaction,
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory,
     * @param array $data
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\ValueInterface $backendModel,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        array $data = []
    ) {
        parent::__construct($data);
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_backendModel = $backendModel;
        $this->_transaction = $transaction;
        $this->_configValueFactory = $configValueFactory;
		$this->_storeId=(int)$this->_storeManager->getStore()->getId();
		$this->_storeCode=$this->_storeManager->getStore()->getCode();
	}
	
	/**
	 * Function for getting Config value of current store
     * @param string $path,
     */
	public function getCurrentStoreConfigValue($path){
		return $this->_scopeConfig->getValue($path,'store',$this->_storeCode);
	}
	
	/**
	 * Function for setting Config value of current store
     * @param string $path,
	 * @param string $value,
     */
	public function setCurrentStoreConfigValue($path,$value){
		$data = [
                    'path' => $path,
                    'scope' =>  'stores',
                    'scope_id' => $this->_storeId,
                    'scope_code' => $this->_storeCode,
                    'value' => $value,
                ];

		$this->_backendModel->addData($data);
		$this->_transaction->addObject($this->_backendModel);
		$this->_transaction->save();
	}
	
	
	public function export_all_products(){
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$_productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
		$collection = $_productCollection->addAttributeToSelect('*')->load();
		$NV_products = array();
		foreach ($collection as $product){
			$_product = $objectManager->create('Magento\Catalog\Model\Product')->load($product->getId());
			$export_to_ogoship = $_product->getExportToOgoship();
			if(empty($export_to_ogoship)){
				$store = $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
				$currency_iso_code = $store->getBaseCurrencyCode();
				$imageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getImage();
				$product_array = array(
					'Code' => $_product->getSku(),
					'Name' => $_product->getName(),
					'Description' => strip_tags($_product->getDescription()),
					'ShortDescription' => strip_tags($_product->getShortDescription()),
					'InfoUrl' => $_product->getProductUrl(),
					'SalesPrice' => intval($_product->getPrice()),
					'Price' => intval($_product->getPrice()),
					'Weight'=> $_product->getWeight(),
					'VatPercentage'=> '',
					'PictureUrl'=>$imageUrl,
					'Currency' => $currency_iso_code
				);
				$NV_products['Products']['Product'][] = $product_array;
				$product_array = '';
			}
		}
		$merchant_id = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->getCurrentStoreConfigValue('Ogoship/view/merchant_id');
		$secret_token = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->getCurrentStoreConfigValue('Ogoship/view/secret_token');
		$api_call = new \NettivarastoAPI($merchant_id, $secret_token);
		$response = $api_call->updateAllProducts($NV_products);
		return $response;
	}
  
	public function get_latest_changes() {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$merchant_id = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->getCurrentStoreConfigValue('Ogoship/view/merchant_id');
		$secret_token = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->getCurrentStoreConfigValue('Ogoship/view/secret_token');
        $latest = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->getCurrentStoreConfigValue('Ogoship/view/ogoship_last_updated');
        $api_call = new \NettivarastoAPI($merchant_id, $secret_token);
        $api_call->setTimestamp($latest);
        $success = $api_call->latestChanges($latestProducts, $latestOrders);
		if($latestOrders) {
			foreach($latestOrders as $latestOrder) {
                //$objectManager->get('\Psr\Log\LoggerInterface')->debug('latest: ' . print_r($latestOrder, true));
                $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($latestOrder->getReference());
                if(!(isset($order) && $order->getId() > 0))
                {
                    $objectManager->get('\Psr\Log\LoggerInterface')->error("OGOship: Order with reference " . $latestOrder->getReference() . " not found, skipping");
                    continue;
                }
				switch ( $latestOrder->getStatus() ) {	
					 case  'SHIPPED': 
						if($order->getState() != \Magento\Sales\Model\Order::ACTION_FLAG_SHIP)
						{
							if($order->canShip() == true)
							{
								if($latestOrder->getTrackingNumber() != null)
								{
                                    $convertOrder = $objectManager->create('Magento\Sales\Model\Convert\Order');
									$trackFactory = $objectManager->get('Magento\Sales\Model\Order\Shipment\TrackFactory');
                                    $shipment = $convertOrder->toShipment($order);
                                    
                                    $lines = $latestOrder->getOrderLines();
                                    if(isset($lines["OrderLine"][1])){
                                        $lines = $lines["OrderLine"];
                                    }

                                    foreach($lines as $line)
                                    {
                                        $sku = isset($line['Code']) ? $line['Code'] : null;

                                        if(!$sku){
                                            continue;
                                        }

                                        foreach($order->getAllVisibleItems() as $item)
                                        {
                                            if($item->getSku() == $sku)
                                            {
                                                $shipItem = $convertOrder->itemToShipmentItem($item)->setQty($line['Quantity']);
                                                $shipment->addItem($shipItem);
                                            }
                                        }
                                    }

									//$objectManager->get('\Psr\Log\LoggerInterface')->debug("Tracking: " . $latestOrder->getTrackingNumber());
                                    foreach(explode(',', $latestOrder->getTrackingNumber()) as $track)
                                    {
                                        if($track != null && $track != '')
                                        {
                                            $number = array(
                                                'carrier_code' => 'Custom',
                                                'title' => str_replace('()', '', $latestOrder->getShipping()),
                                                'number' => $track
                                            );
                                            $trackobj = $trackFactory->create()->addData($number);
                                            $shipment->addTrack($trackobj);//->save();
                                        }
                                    }
                                    $shipment->register();
                                    // send notification mail
                                    //$objectManager->create('Magento\Shipping\Model\ShipmentNotifier')->notify($shipment);

                                    $shipment->save();
								}
							}
						}
						$order->setState(\Magento\Sales\Model\Order::ACTION_FLAG_SHIP, true);
						$order->setStatus(\Magento\Sales\Model\Order::ACTION_FLAG_SHIP);
						$order->addStatusToHistory($order->getStatus(), 'Ogoship change of status to SHIPPED. ');
						$order->save();
                        break;
                    case  'CANCELLED':
						//$order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
						//$order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
						$order->addStatusToHistory($order->getStatus(), 'Ogoship change of status to CANCELLED. ');
						$order->save();
                        break;
                    case  'COLLECTING':
						//$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
						//$order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
						$order->addStatusToHistory($order->getStatus(), 'Ogoship change of status to COLLECTING. ');
						$order->save();
                        break;
                    case  'PENDING':
						//$order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, true);
						//$order->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
						$order->addStatusToHistory($order->getStatus(), 'Ogoship change of status to PENDING. ');
						$order->save();
                        break;
                    case  'RESERVED':
						//if($order->canHold() == true){
						//	$order->setState(\Magento\Sales\Model\Order::STATE_HOLDED, true);
						//	$order->setStatus(\Magento\Sales\Model\Order::STATE_HOLDED);
						//}
						$order->addStatusToHistory($order->getStatus(), 'Ogoship change of status to RESERVED. ');
						$order->save();
                        break;
				}
			}
		}
		if($latestProducts) {
			 foreach($latestProducts as $latestProduct) {
				$product_id = $objectManager->get('Magento\Catalog\Model\Product')->getIdBySku($latestProduct->getCode());
				$_product = $objectManager->create('Magento\Catalog\Model\Product')->load($product_id);
				if(!empty($_product)){
					if ($latestProduct->getStock()) {
						$_product->setQuantityAndStockStatus(['qty' => $latestProduct->getStock(), 'is_in_stock' => 1]);
						$_product->save();
					}
				}
			 }
        }
        if($success == true)
        {
            $latest = $api_call->getTimestamp();
            $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->setCurrentStoreConfigValue('Ogoship/view/ogoship_last_updated', $latest);
        }
		return true;
	}
	
}
