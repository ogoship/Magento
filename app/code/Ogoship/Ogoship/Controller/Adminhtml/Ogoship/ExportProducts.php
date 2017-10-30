<?php
/**
 *
 * Copyright © 2015 Ogoshipcommerce. All rights reserved.
 */
namespace Ogoship\Ogoship\Controller\Adminhtml\Ogoship;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class exportProducts extends \Magento\Backend\App\Action
{
	
	protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
    */
	
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }
    
    public function execute()
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$deny_product_export = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->getCurrentStoreConfigValue('Ogoship/view/deny_product_export');
		if(!empty($deny_product_export)) {
			$this->messageManager->addError(__('Export product has been denied'));
		} else {
    		$response = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->export_all_products();
    		if ( $response ) {
    			if (!((string)$response['Response']['Info']['@Success'] == 'true' ) ) {
    				$strError = $response['Response']['Info']['@Error'];
    				$this->messageManager->addError($strError);
    			} else {
    				$this->messageManager->addSuccess(__('Product export completed.'));
    			}
    		}
		}
		$resultPage = $this->resultPageFactory->create();
		$resultRedirect = $this->resultRedirectFactory->create();
		return $resultRedirect->setPath('ogoship/ogoship/index');
    }
}
