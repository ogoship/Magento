<?php

namespace Ogoship\Ogoship\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class SalesOrderInvoicePay implements ObserverInterface {

    /**
     * @param EventObserver $observer
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected $_redirect;
    protected $_url;
    private $_send;
    protected $_objectmanager;

    public function __construct(
    \Magento\Framework\App\Action\Context $context, \Magento\Framework\App\Response\Http $redirect, \Magento\Framework\UrlInterface $url,
             \Ogoship\Ogoship\Controller\Adminhtml\Ogoship\Sendnettivarasto $send, \Magento\Framework\ObjectManagerInterface $objectmanager
    ) {
        //parent::__construct($context);
        $this->_send = $send;
        $this->_redirect = $redirect;
        $this->_url = $url;
        $this->_objectmanager = $objectmanager::getInstance();
    }

    public function execute(EventObserver $observer) {
        // skip automatic sending if in adminhtml
        if($this->is_admin()){
            return;
        }
        $event = $observer->getEvent();
        $invoice = $event->getInvoice();
        $order  = $invoice->getOrder();
        $orderId = $order->getIncrementId();
        if($this->_objectmanager == null){
    		$this->_objectmanager = \Magento\Framework\App\ObjectManager::getInstance();
        }
        $quote = $this->_objectmanager->create('Magento\Checkout\Model\Cart')->getQuote();
        $this->_send->createorder($orderId, $quote, $order);
        $this->_send->sendorder();
    }

    function is_admin() {
        $state =  $this->_objectmanager->get('Magento\Framework\App\State');
        return 'adminhtml' === $state->getAreaCode();
    }
}
