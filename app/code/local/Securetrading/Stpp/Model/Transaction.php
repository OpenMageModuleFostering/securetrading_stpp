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
    
    public function getRequestData($key = null, $default = null) {
    	$data = $this->_getApiData('request_data');
    	if ($key === null) {
    		return $data;
    	}
    	if (array_key_exists($key, $data)) {
    		return $data[$key];
    	}
    	return $default;
    }
    
    public function setRequestData(array $data = array()) {
      if (array_key_exists('pan', $data)) {
	$data['pan'] = Mage::helper('securetrading_stpp')->mask($data['pan']);
      }
      if (array_key_exists('securitycode', $data)) {
	$data['securitycode'] = '';
      }
        return $this->_setApiData('request_data', $data);
    }
    
    public function getResponseData($key = null) {
        $data = $this->_getApiData('response_data');
        return $key === null ? $data : $data[$key];
    }
    
    public function setResponseData(array $data = array()) {
        if (array_key_exists('maskedpan', $data)) {
            unset($data['maskedpan']);
        }
        return $this->_setApiData('response_data', $data);
    }
    
    public function getParentTransactionReference() {
    	if ($parentTransaction = $this->getParentTransaction(true)) {
    		return $parentTransaction->getTransactionReference();
    	}
        return '';
    }
    
    public function getParentTransaction($graceful = false) {
    	if ($this->getParentTransactionId()) {
    		$parentTransaction = Mage::getModel('securetrading_stpp/transaction')->load($this->getParentTransactionId());
    		if ($parentTransaction->getId()) {
    			return $parentTransaction;
    		}
    	}
    	if ($graceful) {
    		return false;
    	}
    	throw new Exception(Mage::helper('securetrading_stpp')->__('The parent transaction could not be loaded.'));
    }
    
    public function loadByTransactionReference($transactionReference, $graceful = false) {
        $this->load($transactionReference, 'transaction_reference');
        
        if (!$this->getTransactionId()) {
            if ($graceful) {
                return false;
            }
            throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('A transaction with a transaction reference of "%s" cannot be found.'), $transactionReference));
        }
        return $this;
    }
    
    public function getAncestors($includeSelf = true) {
    	$pt = $this;
    	$array = array();
    	while ($pt = $pt->getParentTransaction(true)) {
    		$array[] = $pt;
    	}
    	
    	if ($includeSelf) {
    		array_unshift($array, $this);
    	}
		return $array;
    }
    
    public function searchAncestorsForRequestType($requestType) {
    	foreach($this->getAncestors() as $ancestor) {
    		if ($ancestor->getRequestType() === $requestType) {
    			return $ancestor;
    		}
    	}
    	return false;
    }
	
    public function findTransactions($orderId, $requestType) {
        return array_values($this->getCollection()->addFieldToFilter('order_id', $orderId)->addFieldToFilter('request_type', $requestType)->getItems());
    }
}