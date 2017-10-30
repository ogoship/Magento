<?php
namespace Ogoship\Ogoship\Plugin;
 
class saveOrderToNettivarasto extends \Magento\Framework\View\Element\Template
{
	/**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
 
    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;
 
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Http\Context $httpContext,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->httpContext = $httpContext;
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
    }
	
    public function beforeGetOrderId(\Magento\Sales\Block\Adminhtml\Order\View $subject){
        $subject->addButton(
                'order_send_nettivarasto',
                ['label' => __('Send Ogoship'), 'onclick' => 'setLocation(\'' . $this->getSendNettivarastoUrl() . '\')', 'class' => 'order_send_nettivarasto'],
                -1
            );
        return null;
    }
	
	public function getSendNettivarastoUrl()
    {
		$orderId = $this->getRequest()->getParam('order_id');
        return $this->getUrl('ogoship/ogoship/sendnettivarasto', ['order_id' => $orderId]);
    }
}