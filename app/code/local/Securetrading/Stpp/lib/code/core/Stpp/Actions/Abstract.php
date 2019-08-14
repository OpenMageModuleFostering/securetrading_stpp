<?php

abstract class Stpp_Actions_Abstract implements Stpp_Actions_BaseInterface {
	protected $_result;
	
	protected $_calculationObjects = array();
	
	public function __construct() {
		$this->_initCalculationObjects();
	}
	
	protected function _getResult() {
		if ($this->_result === null) {
			throw new Stpp_Exception('The result object is null.');
		}
		return $this->_result;
	}
	
	public function setResult(Stpp_Api_ResultInterface $result) {
		$this->_result = $result;
		return $this;
	}
	
	public function prepareMessages() {
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
	
	protected function _initCalculationObjects() {
		$this->_calculationObjects = array(
				'stpp_actions_abstract_cardstore_with_noncardstore' => new Stpp_Api_Process_Calculation_CardstoreNoncardstoreCombined(),
				'stpp_actions_abstract_riskdec_with_nonriskdec' => new Stpp_Api_Process_Calculation_RiskdecNonriskdecCombined(),
				'stpp_actions_abstract_tu_refund_combined' => new Stpp_Api_Process_Calculation_TuRefundCombined(),
				'stpp_actions_abstract_default' => new Stpp_Api_Process_Calculation_Default(),
		);
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
	
    public function processError(Stpp_Data_Response $response) {
        return false;
    }
    
    public function processAuth(Stpp_Data_Response $response) {
        return $this->_isErrorCodeZero($response);
    }
    
    public function process3dQuery(Stpp_Data_Response $response) {
        return $this->_isErrorCodeZero($response);
    }
    
    public function processRiskDecision(Stpp_Data_Response $response) {
        return $this->_isErrorCodeZero($response);
    }
    
    public function processAccountCheck(Stpp_Data_Response $response) {
        return $this->_isErrorCodeZero($response);
    }
    
    public function processCardStore(Stpp_Data_Response $response) {
        return $this->_isErrorCodeZero($response);
    }
    
    public function processTransactionUpdate(Stpp_Data_Response $response) {
        return $this->_isErrorCodeZero($response);
    }
    
    public function processRefund(Stpp_Data_Response $response) {
        return $this->_isErrorCodeZero($response);
    }
    
    public function calculateIsTransactionSuccessful(array $responses, $transactionSuccessful) {
        return $transactionSuccessful;
    }
    
    protected function _isErrorCodeZero($response) {
        return $response->get('errorcode') === '0';
    }
}