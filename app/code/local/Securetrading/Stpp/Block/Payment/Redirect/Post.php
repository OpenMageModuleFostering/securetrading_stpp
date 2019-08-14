<?php

class Securetrading_Stpp_Block_Payment_Redirect_Post extends Mage_Core_Block_Template {
    const REGISTRY_TRANSPORT_KEY = 'securetrading_stpp_block_payment_redirect_post_transport';
    
    protected $_transport;
    
    public function _construct() {
        parent::_construct();
        $transport = Mage::registry(SecureTrading_Stpp_Block_Payment_Redirect_Post::REGISTRY_TRANSPORT_KEY);
        $this->_setTransport($transport);
    }
    
    protected function _setTransport(Varien_Object $transport) {
        $this->_transport = $transport;
    }
    
    public function getTransport() {
        if ($this->_transport === null) {
            throw new Stpp_Exception(Mage::helper('securetrading_stpp')->__('The transport object could not be retrieved.'));
        }
        return $this->_transport;
    }
    
    public function getRedirectUrl() {
        return $this->getTransport()->getRedirectUrl();
    }
    
    public function getRedirectData() {
        return $this->getTransport()->getRedirectData();
    }
}