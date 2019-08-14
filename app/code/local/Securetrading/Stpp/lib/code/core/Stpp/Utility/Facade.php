<?php

class Stpp_Utility_Facade extends Stpp_Facade {
    public function __construct(array $config = array()) {
        parent::__construct($config);
        
        Stpp_Exception::setExceptionLog($this->newExceptionLog());

        $translator = $this->newTranslator();
        $debugLog = $this->newDebugLog();
        
        Stpp_Component_Store::registerTranslator($translator);
        Stpp_Component_Store::registerDebugLog($debugLog);
    }
    
    public function newTranslator() {
        return new Stpp_Utility_Translator_Base(Securetrading::getRootPath(), Stpp::getTranslationsPath());
    }
    
    public function newDebugLog() {
        $logWriter = new Stpp_Utility_Log_Writer_File('debug', Stpp::getLogsPath(), Stpp::getLogsArchivePath());
        $debugLog = new Stpp_Utility_Log_Base();
        $debugLog->setLogWriter($logWriter);
        return $debugLog;
    }
    
    public function newExceptionLog() {
        $logWriter = new Stpp_Utility_Log_Writer_File('exception', Stpp::getLogsPath(), Stpp::getLogsArchivePath());
        $exceptionLog = new Stpp_Utility_Log_Base();
        $exceptionLog->setLogWriter($logWriter);
        return $exceptionLog;
    }
}