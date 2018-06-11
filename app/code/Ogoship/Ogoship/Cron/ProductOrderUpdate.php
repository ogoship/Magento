<?php
namespace Ogoship\Ogoship\Cron;
class ProductOrderUpdate { 
    public function execute() {
    	try {
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			//$response = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->get_latest_changes();
			$deny_latest_changes = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->getCurrentStoreConfigValue('Ogoship/view/deny_latest_changes');
			if(!empty($deny_latest_changes)) {
				$this->messageManager->addError(__('Last changes has been denied'));
			} else {
				$response = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->get_latest_changes();
				if($response) {
					$this->messageManager->addSuccess(__('Product and order data updated from Ogoship.'));
				}
			}
			return $this;
	    }
	    catch(\Exception $e){
			
	    }
    }
}