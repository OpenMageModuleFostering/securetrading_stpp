<?php

class Stpp_Api_Context extends Stpp_Component_Abstract implements Stpp_Api_ContextInterface {
    protected $_requests = array();
    
    protected $_responses = array();
    
    public function setRequests(array $requests) {
      $this->_requests = $requests;
      return $this;
    }

    public function &getRequests() {
        if (empty($this->_requests)) {
            throw new Stpp_Exception($this->__('No requests have been set.'));
        }
        return $this->_requests;
    }
    
    public function &getRequest($index) {
        $requests = $this->getRequests();
        
        if(!array_key_exists($index, $requests)) {
            throw new Stpp_Exception($this->__('The array index does not exist.'));
        }
        return $requests[$index];
    }
    
    public function setResponses(array $responses) {
        $this->_responses = $responses;
        return $this;
    }
    
    public function &getResponses() {
        if (!is_array($this->_responses)) {
            throw new Stpp_Exception($this->__('The responses must be an array.'));
        }
        
        if (empty($this->_responses)) {
            throw new Stpp_Exception($this->__('No responses have been set.'));
        }
        return $this->_responses;
    }
    
    public function &getResponse($index) {
        $responses = $this->getResponses();
        
        if(!array_key_exists($index, $responses)) {
            throw new Stpp_Exception($this->__('The array index does not exist.'));
        }
        return $responses[$index];
    }  
}