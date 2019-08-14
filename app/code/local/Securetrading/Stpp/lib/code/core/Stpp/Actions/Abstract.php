<?php

abstract class Stpp_Actions_Abstract implements Stpp_Actions_BaseInterface {
    public function processError(Stpp_Data_Response $response) {
        return false;
    }
    
    public function processAuth(Stpp_Data_Response $response) {
        return $this->_isErrorCodeZero($response);
    }
    
    public function process3dQuery(Stpp_Data_Response $response) {
        return $this->_isErrorCodeZero($response);
    }
    
    public function processRiskDecision(Stpp_Data_Response $response) {
        return $this->_isErrorCodeZero($response);
    }
    
    public function processAccountCheck(Stpp_Data_Response $response) {
        return $this->_isErrorCodeZero($response);
    }
    
    public function processCardStore(Stpp_Data_Response $response) {
        return $this->_isErrorCodeZero($response);
    }
    
    public function processTransactionUpdate(Stpp_Data_Response $response) {
        return $this->_isErrorCodeZero($response);
    }
    
    public function processRefund(Stpp_Data_Response $response) {
        return $this->_isErrorCodeZero($response);
    }
    
    public function calculateIsTransactionSuccessful(array $responses, $transactionSuccessful) {
        return $transactionSuccessful;
    }
    
    protected function _isErrorCodeZero($response) {
        return $response->get('errorcode') === '0';
    }
}