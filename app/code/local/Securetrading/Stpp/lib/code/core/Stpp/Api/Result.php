<?php

class Stpp_Api_Result extends Stpp_Result_Abstract implements Stpp_Api_ResultInterface {
    protected $_context;
    
    protected $_isRedirectRequired = false;
    
    protected $_isTransactionSuccessful = false;
    
    protected $_customerErrorMessage = '';
    
    protected $_merchantErrorMessage = '';
    
    public function getContext() {
        if ($this->_context === null) {
            throw new Stpp_Exception('The context object is null.');
        }
        return $this->_context;
    }
    
    public function setContext(Stpp_Api_ContextInterface $context) {
        $this->_context = $context;
    }
    
    public function getRedirectRequired() {
        return $this->_isRedirectRequired;
    }
    
    public function setRedirectRequired($bool) {
        $this->_isRedirectRequired = (bool) $bool;
        return $this;
    }
    
    public function getIsTransactionSuccessful() {
        return $this->_isTransactionSuccessful;
    }
    
    public function setIsTransactionSuccessful($bool) {
        $this->_isTransactionSuccessful = (bool) $bool;
        return $this;
    }
    
    public function getCustomerErrorMessage() {
        return $this->_customerMessage;
    }
    
    public function setCustomerErrorMessage($message) {
        $this->_customerMessage = $message;
        return $this;
    }
    
    public function getMerchantErrorMessage() {
        return $this->_merchantMessage;
    }
    
    public function setMerchantErrorMessage($message) {
        $this->_merchantMessage = $message;
        return $this;
    }
}