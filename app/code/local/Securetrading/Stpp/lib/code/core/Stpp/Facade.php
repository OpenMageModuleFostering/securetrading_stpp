<?php

class Stpp_Facade {
    protected $_config;
    
    public function __construct(array $config = array()) {
        $this->_config = $this->newStppConfig($config);
    }
    
    public static function instance(array $config = array()) {
        return new static($config);
    }
    
    public function newStppConfig(array $config) {
        return new Stpp_Config($config);
    }
    
    public function newHelper() {
        return new Stpp_Helper();
    }
}