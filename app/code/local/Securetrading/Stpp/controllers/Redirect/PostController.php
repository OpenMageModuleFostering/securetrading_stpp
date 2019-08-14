<?php

class Securetrading_Stpp_Redirect_PostController extends Mage_Core_Controller_Front_Action {
    protected $_methodInstance;
    
    public function preDispatch() {
        parent::preDispatch();
        
        try {
            $orderIncrementId = Mage::getModel('checkout/session')->getLastRealOrderId();

            if ($orderIncrementId === null) {
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
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_redirect(null);
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }
    }
    
    protected function _prepareResult() {
        $transport = $this->_methodInstance->prepareData();
        Mage::register(Securetrading_Stpp_Block_Payment_Redirect_Post::REGISTRY_TRANSPORT_KEY, $transport);
    }
    
    public function rawAction() {
        $this->_prepareResult();
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function containerAction() {
        $this->_prepareResult();
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function iframeAction() {
        Mage::register(Securetrading_Stpp_Block_Payment_Iframe::REGISTRY_IFRAME_HEIGHT_KEY, $this->_methodInstance->getConfigData('ppg_iframe_height'));
        Mage::register(Securetrading_Stpp_Block_Payment_Iframe::REGISTRY_IFRAME_WIDTH_KEY, $this->_methodInstance->getConfigData('ppg_iframe_width'));
        $this->loadLayout();
        $this->renderLayout();
    }
}