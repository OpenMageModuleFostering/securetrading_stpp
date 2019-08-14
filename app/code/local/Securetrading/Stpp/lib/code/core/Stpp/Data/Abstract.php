<?php

abstract class Stpp_Data_Abstract extends Stpp_Component_Abstract implements Stpp_Data_AbstractInterface, ArrayAccess, Countable, Iterator {
    protected $_data = array();
    
    protected $_position = 0; // Required by our implementation of Iterator.
    
    protected $_count = 0; // Used by our implementation of Countable and Iterator.
    
    protected $_skipNextIteration = false; // Used by our implementation of Iterator.
    
    public static function instance() {
        return new static();
    }
    
    public function cloneObject() {
        return clone $this;
    }
    
    public function getAll() {
        $keys = array_keys($this->_data);
        return $this->getMultiple($keys);
    }
    
    public function setMultiple(array $values = array()) {
        foreach($values as $k => $v) {
            $this->set($k, $v);
        }
        return $this;
    }
    
    public function getMultiple(array $keys = array(), $default = null) {
        $array = array();
        foreach($keys as $key) {
            $array[$key] = $this->get($key, $default);
        }
        return $array;
    }
    
    protected function _set($key, $value) {
        $this->_data[$key] = $value;
    }
    
    public function set($key, $value) {
        $setterMethod = '_set' . $key;
        if (method_exists($this, $setterMethod)) {
            $this->$setterMethod($value);
        }
        else {
            $this->_set($key, $value);
        }
        $this->_count = count($this->_data);
        return $this;
    }
    
    public function get($key, $default = null) {
        $value = array_key_exists($key, $this->_data) ? $this->_data[$key] : $default;
        $getterMethod = '_get' . $key;
        
        if (method_exists($this, $getterMethod)) {
            $value = $this->$getterMethod($value);
        }
        return $value;
    }
    
    public function has($key) {
        return array_key_exists($key, $this->_data);
    }
    
    public function uns($key) {
        unset($this->_data[$key]);
        $this->_skipNextIteration = true;
        $this->_count = count($this->_data);
    }
    
    public function toArray() {
        $array = array();
        foreach($this->_data as $k => $v) {
            if ($v instanceof A_Stpp_Data) {
               $array[$k] = $this->_data[$k]->toArray();
            }
            else {
                $array[$k] = $v;
            }
        }
        return $array;
    }
    
    // Countable:
    
    function count() {
        return $this->_count;
    }
    
    // Iterable:
    
    public function current() {
        $this->_skipNextIteration = false;
        return current($this->_data);
    }
    
    public function key() {
        return key($this->_data);
    }
    
    public function next() {
        if ($this->_skipNextIteration) {
            $this->_skipNextIteration = false;
            return;
        }
        $this->_position++;
        next($this->_data);
    }
    
    public function rewind() {
        $this->_skipNextIteration = false;
        $this->_position = 0;
        reset($this->_data);
    }
    
    public function valid() {
        return $this->_position < $this->_count;
    }
    
    // ArrayAccess
    
    public function offsetExists($offset) {
        return $this->has($offset);
    }
    
    public function offsetGet($offset) {
        return $this->get($offset);
    }
    
    public function offsetSet($offset, $value) {
        return $this->set($offset, $value);
    }
    
    public function offsetUnset($offset) {
        return $this->unset($offset);
    }
}