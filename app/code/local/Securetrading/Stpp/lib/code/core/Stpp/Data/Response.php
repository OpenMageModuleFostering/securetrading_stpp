<?php

class Stpp_Data_Response extends Stpp_Data_Abstract {
    protected $_request;
    
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
}