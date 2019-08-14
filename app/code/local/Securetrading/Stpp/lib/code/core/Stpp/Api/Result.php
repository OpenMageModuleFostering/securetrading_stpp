<?php

class Stpp_Api_Result extends Stpp_Result_Abstract implements Stpp_Api_ResultInterface {
    protected $_context;
    
    protected $_isRedirectRequired = false;
    
    protected $_isTransactionSuccessful = false;
    
    protected $_successMessage = '';
    
    protected $_errorMessage = '';
    
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
    
    public function getSuccessMessage() {
    	return $this->_successMessage;
    }
    
    public function setSuccessMessage($message) {
    	$this->_successMessage = $message;
    	return $this;
    }
    
    public function getErrorMessage() {
    	return $this->_errorMessage;
    }
    
    public function setErrorMessage($message) {
    	$this->_errorMessage = $message;
    	return $this;
    }
}