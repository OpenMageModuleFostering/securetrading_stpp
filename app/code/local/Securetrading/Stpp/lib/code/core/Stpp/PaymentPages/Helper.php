<?php

class Stpp_PaymentPages_Helper extends Stpp_Component_Abstract implements Stpp_PaymentPages_HelperInterface {
    protected $_adminAction;
    
    protected $_use3dSecure;
    
    protected $_useRiskDecision;
    
    protected $_useAccountCheck;
    
    public function setAdminAction($bool) {
        $this->_adminAction = $bool;
        return $this;
    }
    
    public function prepareStandard(Stpp_Data_Request $request) {
        $accountTypeDescription = $this->_adminAction ? Stpp_Types::ACCOUNT_MOTO : Stpp_Types::ACCOUNT_ECOM;
        $request->set('accounttypedescription', $accountTypeDescription);
        return $request;
    }
    
    public function prepareExtended(Stpp_Data_Request $request) {
        $requestTypeDescriptions = array(Stpp_Types::API_AUTH);
        
        if ($this->_use3dSecure && !$this->_adminAction) {
            $requestTypeDescriptions[] = Stpp_Types::API_THREEDQUERY;
        }
        
        if ($this->_useRiskDecision) {
            $requestTypeDescriptions[] = Stpp_Types::API_RISKDEC;
        }
        
        if ($this->_useAccountCheck) {
            $requestTypeDescriptions[] = Stpp_Types::API_ACCOUNTCHECK;
        }
        
        $accountTypeDescription = $this->_adminAction ? Stpp_Types::ACCOUNT_MOTO : Stpp_Types::ACCOUNT_ECOM;
        
        $request->set('accounttypedescription', $accountTypeDescription);
        $request->set('requesttypedescriptions', $requestTypeDescriptions);
        
        return $request;
    }
}