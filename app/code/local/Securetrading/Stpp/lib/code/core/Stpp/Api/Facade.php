<?php

class Stpp_Api_Facade extends Stpp_Facade {
    public function newApi() {
        $api = new Stpp_Api_Base();
        $api->setSend($this->newApiSend());
        $api->setProcess($this->newApiProcess());
        return $api;
    }

    public function newApiSend() {
        $apiSend = new Stpp_Api_Send();
        $apiSend->setXmlWriter($this->newApiXmlWriter());
        $apiSend->setXmlReader($this->newApiXmlReader());
		$apiSend->setContext($this->newApiContext());

        if ($this->_config->has('interfaces/api/active_connection')) {
            $connectionStore = $this->newApiConnectionStore();
            $activeConnection = $this->_config->get('interfaces/api/active_connection');
	    	$apiSend->setConnection($connectionStore->get($activeConnection));
        }
        return $apiSend;
    }

    public function newApiProcess() {
        $apiProcess = new Stpp_Api_Process();

        $apiProcess->setResult($this->newApiResult());
        $apiProcess->setApiLog($this->newApiLog());

        if ($this->_config->has('interfaces/api/action_instance')) {
            $apiProcess->setActionInstance($this->_config->get('interfaces/api/action_instance'));
        }
        return $apiProcess;
    }

    public function newApiHelper() {
        $helper = new Stpp_Api_Helper();

        if ($this->_config->has('interfaces/api/use_3d_secure')) {
            $helper->setUse3dSecure($this->_config->get('interfaces/api/use_3d_secure'));
        }

        if ($this->_config->has('interfaces/api/use_risk_decision')) {
            $helper->setUseRiskDecision($this->_config->get('interfaces/api/use_risk_decision'));
        }

        if ($this->_config->has('interfaces/api/use_card_store')) {
            $helper->setUseCardStore($this->_config->get('interfaces/api/use_card_store'));
        }

        if ($this->_config->has('interfaces/api/use_risk_decision_after_auth')) {
            $helper->setUseRiskDecisionAfterAuth($this->_config->get('interfaces/api/use_risk_decision_after_auth'));
        }
        return $helper;
    }
    
    public function newApiResult() {
        return new Stpp_Api_Result();
    }
    
    public function newApiContext() {
        return new Stpp_Api_Context();
    }
    
    public function newApiLog() {
        $logWriter = new Stpp_Utility_Log_Writer_File('api', Stpp::getLogsPath(), Stpp::getLogsArchivePath());
        $apiLog = new Stpp_Api_Log();
        $apiLog->setLogWriter($logWriter);
        return $apiLog;
    }
    
    public function newApiConnectionStore() {
        $stApiConnection = $this->newApiConnectionStApi();
        $webServicesConnection = $this->newApiConnectionWebServices();

        $connectionStore = new Stpp_Api_Connection_Store();
        $connectionStore->registerConnection($stApiConnection);
        $connectionStore->registerConnection($webServicesConnection);
        return $connectionStore;
    }
    
    public function newApiConnectionStApi() {
        $stApiConnection = new Stpp_Api_Connection_Stapi();
        
        if ($this->_config->has('connections/api/host')) {
            $stApiConnection->setHost($this->_config->get('connections/api/host'));
        }
        
        if ($this->_config->has('connections/api/port')) {
            $stApiConnection->setPort($this->_config->get('connections/api/port'));
        }
        
        if ($this->_config->has('connections/api/alias')) {
            $stApiConnection->setAlias($this->_config->get('connections/api/alias'));
        }
        return $stApiConnection;
    }
    
    public function newApiConnectionWebServices() {
        $webServicesConnection = new Stpp_Api_Connection_Webservices();
        
        if ($this->_config->has('connections/web_services/username')) {
            $webServicesConnection->setUsername($this->_config->get('connections/web_services/username'));
        }
        
        if ($this->_config->has('connections/web_services/password')) {
            $webServicesConnection->setPassword($this->_config->get('connections/web_services/password'));
        }
        
        if ($this->_config->has('connections/web_services/alias')) {
            $webServicesConnection->setAlias($this->_config->get('connections/web_services/alias'));
        }
        
        if ($this->_config->has('connections/web_services/connect_timeout')) {
        	$webServicesConnection->setConnectTimeout($this->_config->get('connections/web_services/connect_timeout'));
        }
        
        if ($this->_config->has('connections/web_services/timeout')) {
        	$webServicesConnection->setTimeout($this->_config->get('connections/web_services/timeout'));
        }
        
        if ($this->_config->has('connections/web_services/connect_attempts')) {
        	$webServicesConnection->setConnectAttempts($this->_config->get('connections/web_services/connect_attempts'));
        }
        
        if ($this->_config->has('connections/web_services/connect_retries')) {
        	$webServicesConnection->setConnectRetries($this->_config->get('connections/web_services/connect_retries'));
        }
        
        if ($this->_config->has('connections/web_services/sleep_useconds')) {
        	$webServicesConnection->setSleepUseconds($this->_config->get('connections/web_services/sleep_useconds'));
        }
        
        if ($this->_config->has('connections/web_services/verifyssl')) {
            $webServicesConnection->setVerifySsl($this->_config->get('connections/web_services/verifyssl'));
        }
        
        if ($this->_config->has('connections/web_services/cacertfile')) {
            $webServicesConnection->setCaCertFile($this->_config->get('connections/web_services/cacertfile'));
        }
        
        if ($this->_config->has('connections/web_services/options')) {
            $webServicesConnection->setOptions($this->_config->get('connections/web_services/options'));
        }
        return $webServicesConnection;
    }
    
    public function newApiXmlWriter() {
        $xmlWriter = new Stpp_Api_Xml_Writer();

        if ($this->_config->has('interfaces/api/xmlwriter/version')) {
            $xmlWriter->setXmlVersion($this->_config->get('interfaces/api/xmlwriter/version'));
        }

        if ($this->_config->has('interfaces/api/xmlwriter/encoding')) {
            $xmlWriter->setXmlEncoding($this->_config->get('interfaces/api/xmlwriter/encoding'));
        }
        return $xmlWriter;
    }
    
    public function newApiXmlReader() {
        return new Stpp_Api_Xml_Reader('Stpp_Data_Response');
    }
    
    public function runApiStandard(Stpp_Data_Request $request, $adminAction = false) {
        $helper = $this->newApiHelper()->setAdminAction($adminAction);
        $requests = $helper->prepareStandard($request);
        $result = $this->newApi()->run($requests);
        return $result;
    }

    public function runApi3dAuth(Stpp_Data_Request $request) {
        $requests = $this->newApiHelper()->prepare3dAuth($request);
        $result = $this->newApi()->run($requests);
        return $result;
    }
    
    public function runApiTransactionUpdate(Stpp_Data_Request $request) {
        $requests = $this->newApiHelper()->prepareTransactionUpdate($request);
        $result = $this->newApi()->run($requests);
        return $result;
    }
    
    public function runApiRefund(Stpp_Data_Request $request) {
        $requests = $this->newApiHelper()->prepareRefund($request);
        $result = $this->newApi()->run($requests);
        return $result;
    }
}