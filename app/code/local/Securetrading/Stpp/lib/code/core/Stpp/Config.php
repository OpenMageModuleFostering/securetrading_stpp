<?php

class Stpp_Config {
    protected $_config = array();
    
    public function __construct(array $config = array()) {
	$this->_config = $config;
    }

    public function has($key) {
      $config = &$this->_config;
      $array = explode('/', $key);

      foreach($array as $keySegment) {
	if (array_key_exists($keySegment, $config)) {
	    $config = &$config[$keySegment];
	    continue;
	}
	return false;
      }
      return true;
    }

    public function get($key) {
      $config = &$this->_config;
      $array = explode('/', $key);

      foreach($array as $keySegment) {
	if (array_key_exists($keySegment, $config)) {
	    $config = &$config[$keySegment];
	    continue;
	}
	throw new Stpp_Exception(sprintf('Could not retrieve the key "%s".', $key));
      }
      return $config;
    }

    public function set($key, $value) {
      $config = &$this->_config;
      $array = explode('/', $key);
      $lastIndex = array_pop($array);

      foreach($array as $keySegment) {
	if (array_key_exists($keySegment, $config)) {
	  $config = &$config[$keySegment]; 
	    continue;
	}
	$config[$keySegment] = array();
	$config = &$config[$keySegment];
      }
      $config[$lastIndex] = $value;
      return $this;
    }
}