<?php

class Stpp_Api_Log extends Stpp_Utility_Log_User_Abstract implements Stpp_Api_LogInterface {
    protected $_doNotLog = array(
        '*' => array('0', '30000', '70000'),
    );
    
    public function setDoNotLog(array $array = array()) {
        $this->_doNotLog = $array;
    }
    
    public function getDoNotLog() {
        return $this->_doNotLog;
    }
    
    public function log(Stpp_Data_Response $response) {
        if (!$this->_canLogError($response->get('responsetype'), $response->get('errorcode', false))) {
            return;
        }
        $request = $response->getRequest();
	$request = clone $request;

        $request->removeSecureData();
        
        $message = 'Request' . ':';
        $message .= PHP_EOL;
        $message .= print_r($request->toArray(),1);
        $message .= PHP_EOL;
        $message .= 'Response' . ':';
        $message .= PHP_EOL;
        $message .= print_r($response->toArray(),1);
        $message .= PHP_EOL . PHP_EOL;
        
        $indentedMessage = preg_replace('/^/m', "\t", $message);
        $this->getLogWriter()->log($indentedMessage);
    }
    
    protected function _canLogError($responseType, $errorCode) {
        $canLog = true;
        
        if (!array_key_exists($responseType, $this->_doNotLog)) {
            $responseType = '*';
        }
        
        if (array_key_exists($responseType, $this->_doNotLog)) {
            
            if (!in_array($errorCode, $this->_doNotLog[$responseType])) {
                $errorCode = '*';
            }
            
            if (in_array($errorCode, $this->_doNotLog[$responseType])) {
                $canLog = false;
            }
        }
        return $canLog;
    }
    
    protected function _formatMessage($message) {
        $messageHeader = $this->_getDate() . ': ' . 'Logging gateway transaction.';
        $line = str_repeat('#', strlen($messageHeader));
        return $line . PHP_EOL .$messageHeader . PHP_EOL . $line . PHP_EOL . PHP_EOL . $message;  
    }
}