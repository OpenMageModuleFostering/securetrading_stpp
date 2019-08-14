<?php

abstract class Stpp_Component_Abstract implements Stpp_Component_BaseInterface {
     protected $_debugLog;
     
     protected $_translator;
     
     public function __construct() {
        Stpp_Component_Store::registerComponent($this);
     }
     
     public function setDebugLog(Stpp_Utility_Log_BaseInterface $log) {
         $this->_debugLog = $log;
         return $this;
     }
     
     public function setTranslator(Stpp_Utility_Translator_BaseInterface $translator) {
        $this->_translator = $translator;
        return $this;
    }
    
    public function getDebugLog() {
        return $this->_debugLog;
    }
    
    public function getTranslator() {
        return $this->_translator;
    }
    
    public function __($message) {
        return $this->getTranslator()->translate($message);
    }
}