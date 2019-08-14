<?php

class Stpp_Api_Connection_Store extends Stpp_Component_Abstract implements Stpp_Api_Connection_StoreInterface {
    protected $_store = array();
    
    protected $_active;
    
    public function registerConnection(Stpp_Api_Connection_BaseInterface $connection) {
        $key = $connection::getKey();
        if (array_key_exists($key, $this->_store)) {
            throw new Stpp_Exception(sprintf($this->__('An object with the key "%s" already exists.'), $key));
        }
        $this->_store[$key] = $connection;
        return $this;
    }
    
    public function setActive($key) {
        $store = $this->getAll();
        
        if (!isset($store[$key])) {
            throw new Stpp_Exception(sprintf($this->__('Invalid key specified: "%s".'), $key));
        }
        $this->_active = $store[$key];
        return $this;
    }
    
    public function getActive() {
        if ($this->_active === null) {
            throw new Stpp_Exception($this->__('The active connection has not been set.'));
        }
        return $this->_active;
    }
    
    public function get($key) {
        if (!array_key_exists($key, $this->_store)) {
            throw new Stpp_Exception(sprintf($this->__('The object with key "%s" does not exist.'), $key));
        }
        return $this->_store[$key];
    }
    
    public function getAll() {
        if (!is_array($this->_store)) {
            throw new Stpp_Exception($this->__('The connection store must be an array.'));
        }
        
        if (empty($this->_store)) {
            throw new Stpp_Exception($this->__('The connection store is empty.'));
        }
        return $this->_store;
    }
}