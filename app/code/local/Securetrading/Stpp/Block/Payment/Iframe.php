<?php

class Securetrading_Stpp_Block_Payment_Iframe extends Mage_Page_Block_Html {
    const REGISTRY_IFRAME_HEIGHT_KEY = 'securetrading_stpp_block_payment_redirect_post_iframe_height';
    const REGISTRY_IFRAME_WIDTH_KEY = 'securetrading_stpp_block_payment_redirect_post_iframe_width';
    
    protected $_src;
    
    public function setSrcByRoute($route, $params = array()) {
        $this->_src = Mage::getUrl($route, $params);
        return $this;
    }
    
    public function setSrc($src) {
        $this->_src = $src;
        return $this;
    }
    
    public function getSrc() {
        if ($this->_src === null) {
            throw new Stpp_Exception(Mage::helper('securetrading_stpp')->__('The iframe source has not been set.'));
        }
        return $this->_src;
    }
    
    public function getWidth() {
        return Mage::registry(self::REGISTRY_IFRAME_WIDTH_KEY);
    }
    
    public function getHeight() {
        return Mage::registry(self::REGISTRY_IFRAME_HEIGHT_KEY);
    }
}