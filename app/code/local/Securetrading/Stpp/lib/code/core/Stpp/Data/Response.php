<?php

class Stpp_Data_Response extends Stpp_Data_Abstract {
    protected $_request;
    
    protected $_message = '';
    
    protected $_messageIsError = true;
    
    public function setRequest(Stpp_Data_Request $request) {
        $this->_request = $request;
        return $this;
    }
    
    public function getRequest($graceful = false) {
        if ($this->_request === null) {
            if ($graceful) {
                return false;
            }
            throw new Stpp_Exception($this->__('The request is null.'));
        }
        return $this->_request;
    }
    
    public function hasRequest() {
        return $this->_request !== null;
    }
    
    public function setMessage($message, $isError = null) {
    	$this->_message = $message;
    	if ($isError !== null) {
    		$this->setMessageIsError($isError);
    	}
    	return $this;
    }
    
    public function getMessage() {
    	return $this->_message;
    }
    
    public function setMessageIsError($bool) {
    	$this->_messageIsError = (bool) $bool;
    	return $this;
    }
    
    public function getMessageIsError() {
    	return $this->_messageIsError;
    }
}