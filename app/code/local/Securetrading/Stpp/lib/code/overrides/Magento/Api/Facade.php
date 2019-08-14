<?php

class Magento_Api_Facade extends Stpp_Api_Facade {
	public function newApiLog() {
		$apiLog = new Stpp_Api_Log();
		$apiLog->setLogWriter(new Magento_Log_Writer('securetrading_api'));
        return $apiLog;
	}
}