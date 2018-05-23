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

    public function __construct(
    \Magento\Framework\App\Action\Context $context, \Magento\Framework\App\Response\Http $redirect, \Magento\Framework\UrlInterface $url,
             \Ogoship\Ogoship\Controller\Adminhtml\Ogoship\Sendnettivarasto $send
    ) {
        parent::__construct($context);
        $this->_send = $send;
        $this->_redirect = $redirect;
        $this->_url = $url;
    }

    public function execute(EventObserver $observer) {
        $event = $observer->getEvent()->getOrder();
        $orderId = $event->getId();
        return $this->getUrl('ogoship/ogoship/sendnettivarasto', ['order_id' => $orderId]);
    }

}
