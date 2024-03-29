<?php

class Securetrading_Stpp_Block_Payment_Direct_Post extends Mage_Core_Block_Template {
  protected $_params;
  
  protected function _getMethodInstance() {
    return Mage::registry(Securetrading_Stpp_Direct_PostController::REGISTRY_METHOD_INSTANCE_KEY);
  }
  
  protected function _construct() {
    $session = $this->_getMethodInstance()->getSession();    
    
    if (!$session->hasAcsRedirectParams() || !($session->getAcsRedirectParams() instanceof Varien_Object)) {
      throw new Exception(Mage::helper('securetrading_stpp')->__('The ACS parameters could not be retrieved correctly.'));
    }
    $this->_params = $session->getAcsRedirectParams();
  }
  
  public function getRedirectUrl() {
    return $this->_params->getRedirectUrl();
  }
  
  public function getRedirectData() {
    return $this->_params->getRedirectData();
  }
}