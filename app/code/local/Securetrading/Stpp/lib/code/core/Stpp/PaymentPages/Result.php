<?php

class Stpp_PaymentPages_Result extends Stpp_Result_Abstract implements Stpp_PaymentPages_ResultInterface {
    protected $_request;
    
    public function getRequest() {
        if ($this->_request === null) {
            throw new Stpp_Exception('The request has not been set.');
        }
        return $this->_request;
    }
    
    public function setRequest(Stpp_Data_Request $request) {
        $this->_request = $request;
        return $this;
    }
}