<?php

abstract class Stpp_Utility_Log_User_Abstract implements Stpp_Utility_Log_UserInterface {
    protected $_logger;
    
    public function setLogWriter(Stpp_Utility_Log_WriterInterface $logger) {
        $this->_logger = $logger;
        return $this;
    }
    
    public function getLogWriter() {
        if ($this->_logger === null) {
            throw new Stpp_Exception('The log writer has not been set.');
        }
        return $this->_logger;
    }
}