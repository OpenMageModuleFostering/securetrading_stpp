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
        
        $request->set('requesttypedescription', Stpp_Types::API_AUTH);
        $request->set('md', $_POST['MD']);
        $request->set('pares', $_POST['PaRes']);
        $request->set('accounttypedescription', $this->_calculateAccountTypeDescription(Stpp_Types::API_AUTH));
        return array($request);
    }
    
    public function prepareRefund(Stpp_Data_Request $originalRequest) {
        $originalOrderTotal = $originalRequest->get('original_order_total', null);
        $currentOrderTotal = $originalRequest->get('current_order_total', null);
        $amountToRefund = $originalRequest->get('amount_to_refund', null);
        $transactionReference = $originalRequest->get('transaction_reference', null);
        $partialRefundAlreadyProcessed = $originalRequest->get('partial_refund_already_processed', null);
        
        if (in_array(null, array($originalOrderTotal, $currentOrderTotal, $amountToRefund, $transactionReference, $partialRefundAlreadyProcessed), true)) {
            throw new Stpp_Exception($this->__('Not all parameters were passed to the refund function.'));
        }
        
        $partialRefund = $originalOrderTotal - $amountToRefund > 0;
        $settleBaseAmount = $currentOrderTotal - $amountToRefund;
        
        if (!$partialRefundAlreadyProcessed) {
            $filter = Stpp_Data_Request::instance()->set('transactionreference', $transactionReference);
            
            $updates = Stpp_Data_Request::instance();
            
            if ($settleBaseAmount > 0) {
                $updates->set('settlebaseamount', $settleBaseAmount);
            }
            else {
                $updates->set('settlestatus', 3);
            }
            
            $transactionUpdate = Stpp_Data_Request::instance()
                ->set('requesttypedescription', Stpp_Types::API_TRANSACTIONUPDATE)
                ->set('filter', $filter)
                ->set('updates', $updates);
        }
        
        $refund = Stpp_Data_Request::instance()
            ->set('requesttypedescription', Stpp_Types::API_REFUND)
            ->set('parenttransactionreference', $transactionReference);
        
        if ($partialRefund) {
            $refund->set('baseamount', $amountToRefund);
        }
        
        return array(
            $transactionUpdate,
            $refund,
        );
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