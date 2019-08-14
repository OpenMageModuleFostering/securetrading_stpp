<?php

class Stpp_Api_Helper extends Stpp_Component_Abstract implements Stpp_Api_HelperInterface {
    protected $_use3dSecure = false;
    
    protected $_useRiskDecision = false;
    
    protected $_useCardStore = false;
    
    protected $_useRiskDecisionAfterAuth = false;
    
    protected $_adminAction = false;
    
    public function setUse3dSecure($bool) {
        $this->_use3dSecure = (bool) $bool;
        return $this;
    }
    
    public function setUseRiskDecision($bool) {
        $this->_useRiskDecision = (bool) $bool;
        return $this;
    }
    
    public function setUseCardStore($bool) {
        $this->_useCardStore = (bool) $bool;
        return $this;
    }
    
    public function setUseRiskDecisionAfterAuth($bool) {
        $this->_useRiskDecisionAfterAuth = (bool) $bool;
        return $this;
    }
    
    public function setAdminAction($bool) {
        $this->_adminAction = (bool) $bool;
        return $this;
    }
    
    public function generateRequests(Stpp_Data_Request $originalRequest, array $requestTypes) {
        $requests = array();
        foreach($requestTypes as $requestType) {
            $request = $originalRequest->cloneObject()->setMultiple(
                array(
		      'requesttypedescription'  => $requestType,
                      'accounttypedescription'  => $this->_calculateAccountTypeDescription($requestType),
                )
            );
            $requests[] = $request;
        }
        return $requests;
    }
    
    public function prepareStandard(Stpp_Data_Request $originalRequest) {
        $requestTypes = array();
        
        if ($this->_useCardStore) {
            $requestTypes[] = Stpp_Types::API_CARDSTORE;
	}

        if ($this->_useRiskDecision && !$this->_useRiskDecisionAfterAuth) {
            $requestTypes[] = Stpp_Types::API_RISKDEC;
        }

        if ($this->_use3dSecure && !$this->_adminAction) {
            $requestTypes[] = Stpp_Types::API_THREEDQUERY;
        }
        
        $requestTypes[] = Stpp_Types::API_AUTH;
        
        if ($this->_useRiskDecision && $this->_useRiskDecisionAfterAuth) {
            $requestTypes[] = Stpp_Types::API_RISKDEC;
        }
        
        return $this->generateRequests($originalRequest, $requestTypes);
    }
	
	public function prepare3dAuth(Stpp_Data_Request $request) {
        if (!isset($_POST['MD'])) {
            throw new Stpp_Exception($this->__('The MD has not been set.'));
        }
        
        if (!isset($_POST['PaRes'])) {
            throw new Stpp_Exception($this->__('The PaRes has not been set.'));
        }
		
        if ($this->_useRiskDecision && $this->_useRiskDecisionAfterAuth) {
			$requestTypes  = array(Stpp_Types::API_AUTH, Stpp_Types::API_RISKDEC);
        }
		else {
			$requestTypes = array(Stpp_Types::API_AUTH);
		}
		
        $request->set('md', $_POST['MD']);
        $request->set('pares', $_POST['PaRes']);
        $request->set('accounttypedescription', $this->_calculateAccountTypeDescription(Stpp_Types::API_AUTH));
		
		return $this->generateRequests($request, $requestTypes);

    }
	
    public function prepareRefund(Stpp_Data_Request $originalRequest) {
        $originalOrderTotal = $originalRequest->get('original_order_total', null);
        $orderTotalPaid = $originalRequest->get('order_total_paid', null);
        $orderTotalRefunded = $originalRequest->get('order_total_refunded', null);
        $amountToRefund = $originalRequest->get('amount_to_refund', null);
        $transactionReference = $originalRequest->get('transaction_reference', null);
        $partialRefundAlreadyProcessed = $originalRequest->get('partial_refund_already_processed', null);
        $usingMainAmount = $originalRequest->get('using_main_amount', null);
        $currencyIso3a = $originalRequest->get('currency_iso_3a', null);
        $allowSuspend = $originalRequest->get('allow_suspend', null);
        $siteReference = $originalRequest->get('site_reference');
        
        $requests = array();
        
        if (in_array(null, array($originalOrderTotal, $orderTotalPaid, $orderTotalRefunded, $amountToRefund, $transactionReference, $partialRefundAlreadyProcessed, $usingMainAmount, $currencyIso3a, $allowSuspend), true)) {
            throw new Stpp_Exception($this->__('Not all parameters were passed to the refund function.'));
        }
        
        $partialRefund = $originalOrderTotal - $amountToRefund > 0;
        $settleAmount = $orderTotalPaid - $orderTotalRefunded - $amountToRefund;
        
        if (!$partialRefundAlreadyProcessed) {
            $filter = Stpp_Data_Request::instance()->set('transactionreference', $transactionReference)->set('sitereference', $siteReference);
            $updates = Stpp_Data_Request::instance();
            
            if ($settleAmount > 0) {
            	$this->_setAmount($updates, $usingMainAmount, $settleAmount, $currencyIso3a);
            }
            else {
            	if ($allowSuspend) {
            		$settleAmount = $originalOrderTotal - $orderTotalPaid;
        			if ($settleAmount > 0) {
            			$this->_setAmount($updates, $usingMainAmount, $settleAmount, $currencyIso3a);
            			$settleStatus = 2;
        			}
        			else {
        				$settleStatus = 3;
        			}
            	}
            	else {
            		$settleStatus = 3;
            	}
            	$updates->set('settlestatus', $settleStatus);
            }
            
            $transactionUpdate = Stpp_Data_Request::instance()
                ->set('requesttypedescription', Stpp_Types::API_TRANSACTIONUPDATE)
                ->set('filter', $filter)
                ->set('updates', $updates);
            
            $requests[] = $transactionUpdate;
        }
        
        $refund = Stpp_Data_Request::instance()
            ->set('requesttypedescription', Stpp_Types::API_REFUND)
            ->set('parenttransactionreference', $transactionReference)
        	->set('sitereference', $siteReference);
        
        if ($partialRefund) {
        	if ($usingMainAmount) {
        		$refund->set('mainamount', $amountToRefund);
        	}
        	else {
        		$refund->set('baseamount', $amountToRefund);
        	}
        }
        
        $requests[] = $refund;
        return $requests;
    }
    
    protected function _setAmount(Stpp_Data_Request $updates, $usingMainAmount, $settleAmount, $currencyIso3a) {
    	if ($usingMainAmount) {
    		$updates->set('settlemainamount', $settleAmount);
    		$updates->set('currencyiso3a', $currencyIso3a);
    	}
    	else {
    		$updates->set('settlebaseamount', $settleAmount);
    	}
    }
    
    public function prepareTransactionUpdate(Stpp_Data_Request $request) {
        $request->set('requesttypedescription', Stpp_Types::API_TRANSACTIONUPDATE);
        
        if (!$request->has('filter')) {
            throw new Stpp_Exception($this->__('The filters do not exist.'));
        }
        
        if (!$request->has('updates')) {
            throw new Stpp_Exception($this->__('The updates do not exist.'));
        }
        return array($request);
    }
    
    protected function _calculateAccountTypeDescription($requestType) {
        $accountType = null;
        switch($requestType) {
            case Stpp_Types::API_AUTH:
                $accountType = $this->_adminAction ? Stpp_Types::ACCOUNT_MOTO : Stpp_Types::ACCOUNT_ECOM;
                break;
            case Stpp_Types::API_THREEDQUERY:
                $accountType = Stpp_Types::ACCOUNT_ECOM;
                break;
            case Stpp_Types::API_RISKDEC:
                $accountType = Stpp_Types::ACCOUNT_FRAUDCONTROL;
                break;
            case Stpp_Types::API_CARDSTORE:
                $accountType = Stpp_Types::ACCOUNT_CARDSTORE;
            case Stpp_Types::API_REFUND:
            case Stpp_Types::API_TRANSACTIONUPDATE:
                break;
            default:
                throw new Stpp_Exception(sprintf($this->__('Unhandled request type "%s" provided.'), $requestType));
        }
        return $accountType;
    }
}