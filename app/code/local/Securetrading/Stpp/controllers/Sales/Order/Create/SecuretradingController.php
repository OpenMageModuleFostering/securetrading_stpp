<?php

class Securetrading_Stpp_Sales_Order_Create_SecuretradingController extends Mage_Adminhtml_Controller_Action {
    public $_publicActions = array('redirect');
    
    protected $_methodInstance;
    
    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/create');
    }
    
    public function preDispatch() {
        parent::preDispatch();
        
        if ($this->getRequest()->getRequestedActionName() !== 'redirect') {
            $orderIncrementId = $this->getRequest()->get('order_increment_id');

            if (!$orderIncrementId) {
                throw new Exception(Mage::helper('securetrading_stpp')->__('No order ID.'));
            }

            $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
            $this->_methodInstance = $order->getPayment()->getMethodInstance();

            if ($this->_methodInstance->getCode() !== Mage::getModel('securetrading_stpp/payment_redirect')->getCode()) {
                throw new Exception(Mage::helper('securetrading_stpp')->__('Cannot access payment method.'));
            }

            if ($order->getStatus() !== Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_PPAGES) {
               throw new Exception(Mage::helper('securetrading_stpp')->__('Order not pending payment pages.'));
            }
        }
    }
    
    public function postAction() {
        $transport = $this->_methodInstance->prepareData(true);
        Mage::register(Securetrading_Stpp_Block_Payment_Redirect_Post::REGISTRY_TRANSPORT_KEY, $transport);
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function rawAction() {
        $transport = $this->_methodInstance->prepareData(true);
        Mage::register(Securetrading_Stpp_Block_Payment_Redirect_Post::REGISTRY_TRANSPORT_KEY, $transport);
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
        $this->_redirect('securetrading/payment/location', array('_query' => $queryArgs));
        
        Mage::getSingleton('adminhtml/session')->clear();
        Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order has been created.'));
    }
}