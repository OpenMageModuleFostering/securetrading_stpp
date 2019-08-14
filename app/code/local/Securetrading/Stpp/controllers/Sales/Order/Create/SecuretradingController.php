<?php

class Securetrading_Stpp_Sales_Order_Create_SecuretradingController extends Mage_Adminhtml_Controller_Action {
    public $_publicActions = array('redirect');
    
    protected $_methodInstance;
    
    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/create');
    }
    
    protected function _getOrderIncrementids() {
    	return array($this->getRequest()->get('order_increment_id'));
    }
    
    public function preDispatch() {
        parent::preDispatch();
        
        if ($this->getRequest()->getRequestedActionName() !== 'redirect') {
        	Mage::getModel('securetrading_stpp/payment_redirect')->validateOrders($this->_getOrderIncrementIds());
			$this->_methodInstance = Mage::getModel('securetrading_stpp/payment_redirect')->getFirstMethodInstance($this->_getOrderIncrementIds());
        }
    }
    
    protected function _prepareResult() {
    	$transport = $this->_methodInstance->prepareData(true, $this->_getOrderIncrementIds());
    	Mage::register(Securetrading_Stpp_Block_Payment_Redirect_Post::REGISTRY_TRANSPORT_KEY, $transport);
    }
    
    public function postAction() {
        $this->_prepareResult();
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function rawAction() {
    	$this->_prepareResult();
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function iframeAction() {
        Mage::register(Securetrading_Stpp_Block_Payment_Iframe::REGISTRY_IFRAME_HEIGHT_KEY, $this->_methodInstance->getConfigData('ppg_iframe_height'));
        Mage::register(Securetrading_Stpp_Block_Payment_Iframe::REGISTRY_IFRAME_WIDTH_KEY, $this->_methodInstance->getConfigData('ppg_iframe_width'));
        
        $queryArgs = array('order_increment_id' => $this->getRequest()->get('order_increment_id'));
        $src = Mage::getModel('adminhtml/url')->getUrl('adminhtml/sales_order_create_securetrading/raw', array('_query' => $queryArgs));
        
        $this->loadLayout();
        $this->getLayout()->getBlock('securetrading_stpp.payment.iframe')->setSrc($src);
        $this->renderLayout();
    }
    
    public function redirectAction() {
        Mage::getModel('securetrading_stpp/payment_redirect')->runRedirect();
        
        $orderIncrementId = Mage::getSingleton('adminhtml/session_quote')->getLastOrderIncrementId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        
        $path = '*/sales_order/view';
        $arguments = array('order_id' => $order->getId());
        $queryArgs = array('url' => Mage::getUrl($path, $arguments));
        $this->_redirect('securetrading/payment/location', array('_query' => $queryArgs, '_store' => 1)); // So frontend store URL used instead of admin
		
        Mage::getSingleton('adminhtml/session')->clear();
        Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order has been created.'));
    }
}