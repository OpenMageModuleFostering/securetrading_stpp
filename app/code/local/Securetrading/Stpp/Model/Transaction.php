<?php

class Securetrading_Stpp_Model_Transaction extends Mage_Core_Model_Abstract {
    protected function _construct() {
        $this->_init('securetrading_stpp/transaction');
    }
    
    protected function _beforeSave() {
        $this->setLastUpdatedAt(Mage::getModel('core/date')->gmtDate());
        return parent::_beforeSave();
    }
    
    protected function _setApiData($key, array $data = array()) {
        $this->setData($key, serialize($data));
        return $this;
    }
    
    protected function _getApiData($key) {
        $array = unserialize($this->getData($key));
        $return = is_array($array) ? $array : array();
        return $return;
    }
    
    public function getRequestData() {
        return $this->_getApiData('request_data');
    }
    
    public function setRequestData(array $data = array()) {
        return $this->_setApiData('request_data', $data);
    }
    
    public function getResponseData() {
        return $this->_getApiData('response_data');
    }
    
    public function setResponseData(array $data = array()) {
        if (array_key_exists('maskedpan', $data)) {
            unset($data['maskedpan']);
        }
        return $this->_setApiData('response_data', $data);
    }
    
    public function getParentTransactionReference() {
        $transaction = Mage::getModel('securetrading_stpp/transaction')->load($this->getParentTransactionId());
        
        if ($transaction) {
            return $transaction->getTransactionReference();
        }
        return '';
    }
    
    public function loadByParentTransactionReference($parentTransactionReference, $graceful = false) {
        $this->load($parentTransactionReference, 'transaction_reference');
        
        if (!$this->getTransactionId()) {
            if ($graceful) {
                return false;
            }
            throw new Stpp_Exception(sprintf(Mage::helper('securetrading_stpp')->__('A transaction with parent transaction reference "%s" cannot be found.'), $parentTransactionReference));
        }
        return $this;
    }
}