<?php

abstract class Securetrading_Stpp_Controller_Redirect_Post_Abstract extends Mage_Core_Controller_Front_Action {
    protected $_methodInstance;
    
    abstract protected function _getOrderIncrementIds();
    
    public function preDispatch() {
        parent::preDispatch();
        try {
	    Mage::getModel('securetrading_stpp/payment_redirect')->validateOrdersArePendingPpages($this->_getOrderIncrementIds());
	    $this->_methodInstance = Mage::getModel('securetrading_stpp/payment_redirect')->getFirstMethodInstance($this->_getOrderIncrementIds());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_redirect(null);
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }
    }
    
    protected function _prepareResult() {
        $transport = $this->_methodInstance->prepareData(false, $this->_getOrderIncrementIds());
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