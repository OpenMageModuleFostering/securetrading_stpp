<?php

class Magento_Utility_Facade extends Stpp_Utility_Facade {
    public function newDebugLog() {
        $logWriter = new Magento_Log_Writer('securetrading');
        $debugLog = new Stpp_Utility_Log_Base();
        $debugLog->setLogWriter($logWriter);
        return $debugLog;
    }
	
	public function newExceptionLog() {
		$logWriter = new Magento_Log_Writer('securetrading_exception');
        $debugLog = new Stpp_Utility_Log_Base();
        $debugLog->setLogWriter($logWriter);
        return $debugLog;
	}
}