<?php

class Stpp_Api_Process extends Stpp_Component_Abstract implements Stpp_Api_ProcessInterface {
    protected $_actionInstance;
    
    protected $_context;
    
    protected $_calculationObjects;
    
    protected $_result;
    
    protected $_apiLog;
    
    public function __construct() {
    	parent::__construct();
    	$this->setCalculationObjects(array(
			'stpp_actions_abstract_cardstore_with_noncardstore' => new Stpp_Api_Process_Calculation_CardstoreNoncardstoreCombined(),
			'stpp_actions_abstract_riskdec_with_nonriskdec' => new Stpp_Api_Process_Calculation_RiskdecNonriskdecCombined(),
			'stpp_actions_abstract_tu_refund_combined' => new Stpp_Api_Process_Calculation_TuRefundCombined(),
 			'stpp_actions_abstract_default' => new Stpp_Api_Process_Calculation_Default(),
    	));
    }
    
    public function setActionInstance(Stpp_Api_ActionsInterface $actionInstance) {
        $this->_actionInstance = $actionInstance;
        return $this;
    }
    
    protected function _getActionInstance() {
        if ($this->_actionInstance === null) {
            throw new Stpp_Exception($this->__('The action instance has not been set.'));
        }
        return $this->_actionInstance;
    }
    
    protected function _setContext(Stpp_Api_ContextInterface $context) {
        $this->_context = $context;
        return $this;
    }
    
    protected function _getContext() {
        if ($this->_context === null) {
            throw new Stpp_Exception($this->__('The context object is null.'));
        }
        return $this->_context;
    }
    
    public function setResult(Stpp_Api_ResultInterface $result) {
        $this->_result = $result;
        return $this;
    }
    
    public function addCalculationObject($code, Stpp_Api_Process_Calculation_BaseInterface $calculationObject) {
    	$this->_calculationObjects[$code] = $calculationObject;
    }
    
    public function removeCalculationObject($code) {
    	unset($this->_calculationObjects);
    }
    
    public function getCalculationObjects() {
    	return $this->_calculationObjects;
    }
    
    public function setCalculationObjects(array $objects = array()) {
    	$this->_calculationObjects = $objects;
    }
    
    protected function _getResult() {
        if ($this->_result === null) {
            throw new Stpp_Exception($this->__('The result object is null.'));
        }
        return $this->_result;
    }
    
    public function setApiLog(Stpp_Api_LogInterface $log) {
        $this->_apiLog = $log;
        return $this;
    }
    
    public function getApiLog() {
        if ($this->_apiLog === null) {
            throw new Stpp_Exception($this->__('The API log has not been set.'));
        }
        return $this->_apiLog;
    }

    protected function _init(Stpp_Api_ContextInterface $context) {
    	$this->_getResult()->setContext($context);
    	$this->_setContext($context);
    }
    
    public function run(Stpp_Api_ContextInterface $context) {
        $this->_init($context);
        $this->_handleResponses();
        $this->_calculateIsTransactionSuccessful();
        $this->_calculateMessages();
        return $this->_getResult();
    }

    protected function _handleResponses() {
        $responses = $this->_getContext()->getResponses();
        
        $this->_runCommonRoutines($responses);

        $lastResponseResponseType = $responses[count($responses)-1]->get('responsetype');
        
        foreach($responses as $response) {
            switch($response->get('responsetype')) {
                case Stpp_Types::API_ERROR:
                    $result = $this->_handleError($response);
                    break;
                case Stpp_Types::API_AUTH:
                    $result = $this->_handleAuth($response);
                    break;
                case Stpp_Types::API_THREEDQUERY:
                    $redirToAcsUrl = ($lastResponseResponseType === Stpp_Types::API_THREEDQUERY && $response->get('errorcode') === '0' && $response->get('enrolled') === 'Y');
                    $result = $this->_handle3dQuery($response, $redirToAcsUrl);
                    break;
                case Stpp_Types::API_RISKDEC:
                    $result = $this->_handleRiskDecision($response);
                    break;
                case Stpp_Types::API_ACCOUNTCHECK:
                    $result = $this->_handleAccountCheck($response);
                    break;
                case Stpp_Types::API_CARDSTORE:
                    $result = $this->_handleCardStore($response);
                    break;
                case Stpp_Types::API_TRANSACTIONUPDATE:
                    $result = $this->_handleTransactionUpdate($response);
                    break;
                case Stpp_Types::API_REFUND:
                    $result = $this->_handleRefund($response);
                    break;
                default:
                    throw new Stpp_Exception(sprintf($this->__('Unsupported response: "%s".'), $response->getResponseType()));
            }
            $response->getRequest()->setIsSuccessful($result);
        }
        return $this;
    }
    
    protected function _runCommonRoutines($responses) {
        foreach($responses as $response) {
	    	$this->getApiLog()->log($response);
            $this->_formatErrorMessages($response);
        }
        return $this;
    }
    
    protected function _formatErrorMessages(Stpp_Data_Response $response) {
        $errorCode = (string) $response->get('errorcode');
        $errorMessage = (string) $response->get('errormessage');
        $errorData = (string) $response->get('errordata');
        
        switch($errorCode) {
            case "0":
                $response->setMessage($this->__('Transaction successful.'))->setMessageIsError(false);
                break;
            case "30000":
                $errorData = $errorData === 'pan' ? $this->__('credit/debit card number') : $errorData;
                $response->setMessage(sprintf($this->__('The %s was not provided or was incorrect.'), $errorData))->setMessageIsError(true);
                break;
            case "70000": 
                $response->setMessage($this->__('Your credit/debit card was declined.  Please try again using a different card.'))->setMessageIsError(true);
                break;
            default:
                $response->setMessage($this->__('An unexpected error occurred.  Please try again.'))->setMessageIsError(true);
        }
        return $this;
    }
    
    protected function _calculateIsTransactionSuccessful() {
    	$result = null;
    	$calculationObjects = $this->getCalculationObjects();
    	 
    	while ($object = array_shift($calculationObjects)) {
    		if (($result = $object->calculate($this->_getContext())) !== null) {
    			$result = (bool) $result;
    			$this->_getResult()->setIsTransactionSuccessful($result);
    			return;
    		}
    	}
    	throw new Stpp_Exception($this->__('No calculation objects matched the gateway response.'));
    }
    
    protected function _calculateMessages() {
		$result = $this->_getResult();
		$responses = $result->getContext()->getResponses();
		
		foreach($responses as $response) {
			$errorMessages = array();
			$successMessages = array();
	
			if ($response->getMessageIsError()) {
				$errorMessages[] = $response->getMessage();
			}
			else {
				$successMessages[] = $response->getMessage();
			}
		}
		 
		$result->setErrorMessage(implode(' - ', $errorMessages));
		$result->setSuccessMessage(implode(' - ', $successMessages));
    }
    
    protected function _handleError(Stpp_Data_Response $response) {
        return $this->_getActionInstance()->processError($response);
    }
    
    protected function _handleAuth(Stpp_Data_Response $response) {
        return $this->_getActionInstance()->processAuth($response);
    }
    
    protected function _handle3dQuery(Stpp_Data_Response $response, $performAcsUrlRedirect) {
        if ($performAcsUrlRedirect) {
            $hiddenInputElements = array(
                'PaReq' => $response->get('pareq'),
                'TermUrl' => $response->getRequest()->get('termurl'),
                'MD' => $response->get('md'),
            );
            
            $this->_getResult()
                ->setRedirectRequired(true)
                ->setRedirectIsPost(true)
                ->setRedirectUrl($response->get('acsurl'))
                ->setRedirectData($hiddenInputElements)
                ->setIsTransactionSuccessful(null)
            ;
        }
        return $this->_getActionInstance()->process3dQuery($response);
    }
    
    protected function _handleRiskDecision(Stpp_Data_Response $response) {
        return $this->_getActionInstance()->processRiskDecision($response);
    }
    
    protected function _handleAccountCheck(Stpp_Data_Response $response) {
        return $this->_getActionInstance()->processAccountCheck($response);
    }
    
    protected function _handleCardStore(Stpp_Data_Response $response) {
        return $this->_getActionInstance()->processCardStore($response);
    }
    
    protected function _handleTransactionUpdate(Stpp_Data_Response $response) {
        return $this->_getActionInstance()->processTransactionUpdate($response);
    }
    
    protected function _handleRefund(Stpp_Data_Response $response) {
        return $this->_getActionInstance()->processRefund($response);
    }
}