<?php

class Securetrading_Stpp_Direct_PostController extends Mage_Core_Controller_Front_Action {
    protected $_methodInstance;
    
    const REGISTRY_METHOD_INSTANCE_KEY = 'securetrading_stpp_direct_postcontroller_registry_method_instance_key';

    public function preDispatch() {
        parent::preDispatch();
	$orderIncrementIds = Mage::helper('securetrading_stpp')->getOrderIncrementIdsFromSession();
        $this->_methodInstance = Mage::getModel('sales/order')->loadByIncrementId(array_shift($orderIncrementIds))->getPayment()->getMethodInstance();
	if (!Mage::helper('securetrading_stpp')->isSecuretradingApiTypePaymentMethod($this->_methodInstance->getCode())) {
	    throw new Exception(Mage::helper('securetrading_stpp')->__('Cannot access payment method.'));
        }
	Mage::register(self::REGISTRY_METHOD_INSTANCE_KEY, $this->_methodInstance);
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
        Mage::register(Securetrading_Stpp_Block_Payment_Iframe::REGISTRY_IFRAME_HEIGHT_KEY, $this->_methodInstance->getConfigData('iframe_height'));
        Mage::register(Securetrading_Stpp_Block_Payment_Iframe::REGISTRY_IFRAME_WIDTH_KEY, $this->_methodInstance->getConfigData('iframe_width'));
        $this->loadLayout();
        $this->renderLayout();
    }
}