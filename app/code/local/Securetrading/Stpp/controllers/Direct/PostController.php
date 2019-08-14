<?php

class Securetrading_Stpp_Direct_PostController extends Mage_Core_Controller_Front_Action {
    protected $_methodInstance;
    
    public function preDispatch() {
        parent::preDispatch();
        
        $orderIncrementId = Mage::getModel('checkout/session')->getLastRealOrderId();
        $this->_methodInstance = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId)->getPayment()->getMethodInstance();
        
        if ($this->_methodInstance->getCode() !== Mage::getModel('securetrading_stpp/payment_direct')->getCode()) {
            throw new Exception(Mage::helper('securetrading_stpp')->__('Cannot access payment method.'));
        }
    }
    
    public function rawAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function containerAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function iframeAction() {
        Mage::register(Securetrading_Stpp_Block_Payment_Iframe::REGISTRY_IFRAME_HEIGHT_KEY, $this->_methodInstance->getConfigData('api_iframe_height'));
        Mage::register(Securetrading_Stpp_Block_Payment_Iframe::REGISTRY_IFRAME_WIDTH_KEY, $this->_methodInstance->getConfigData('api_iframe_width'));
        $this->loadLayout();
        $this->renderLayout();
    }
}