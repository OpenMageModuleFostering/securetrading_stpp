<?php

class Magento_Utility_Facade extends Stpp_Utility_Facade {
    public function newDebugLog() {
        $logWriter = new Magento_Utility_Log_Writer();
        $debugLog = new Stpp_Utility_Log_Base();
        $debugLog->setLogWriter($logWriter);
        return $debugLog;
    }
}