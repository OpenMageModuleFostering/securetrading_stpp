<?php

class Stpp_Utility_Log_Base extends Stpp_Utility_Log_User_Abstract implements Stpp_Utility_Log_BaseInterface {
    protected $_logLevel;
    
    public function setLogLevel($logLevel) {
        $this->_logLevel = $logLevel;
        return $this;
    }
    
    public function getLogLevel() {
        return $this->_logLevel;
    }
    
    public function log($message) {
        $this->getLogWriter()->log($message);
    }
}