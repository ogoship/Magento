<?php
/**
 *
 * Copyright © 2015 Ogoshipcommerce. All rights reserved.
 */
namespace Ogoship\Ogoship\Controller\Adminhtml\Ogoship;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class importLastChanges extends \Magento\Backend\App\Action
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
		$deny_latest_changes = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->getCurrentStoreConfigValue('Ogoship/view/deny_latest_changes');
		if(!empty($deny_latest_changes)) {
			$this->messageManager->addError(__('Last changes has been denied'));
		} else {
			$response = $objectManager->get('\Ogoship\Ogoship\Model\Ogoship')->get_latest_changes();
			if($response) {
				$this->messageManager->addSuccess(__('Product and order data updated from Ogoship.'));
			}
		}
		$resultPage = $this->resultPageFactory->create();
		$resultRedirect = $this->resultRedirectFactory->create();
		return $resultRedirect->setPath('ogoship/ogoship/index');
    }
}
