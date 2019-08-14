<?php

class Stpp_Data_Request extends Stpp_Data_Abstract {
    protected $_frameworkLogic;
    
    protected $_response;
    
    protected $_isSuccessful;
    
    public function __construct() {
        if(isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
            $this->set('customerip', $_SERVER['REMOTE_ADDR']);
        }
        parent::__construct();
    }
    
    public function setUseFrameworkLogic($string) {
        $this->_frameworkLogic = $string;
        return $this;
    }
    
    public function getUseFrameworkLogic() {
        return $this->_frameworkLogic;
    }
    
    public function setResponse(Stpp_Data_Response $response) {
        $this->_response = $response;
        return $this;
    }
    
    public function getResponse() {
        return $this->_response;
    }
    
    public function setIsSuccessful($bool) {
        $this->_isSuccessful = $bool;
        return $this;
    }
    
    public function getIsSuccessful() {
        return $this->_isSuccessful;
    }
    
    public function removeSecureData() {
        if ($this->has('pan')) {
            $this->set('pan', $this->__('Removed.'));
        }
        if ($this->has('securitycode')) {
            $this->set('securitycode', $this->__('Removed.'));
        }
        return $this;
    }
    
    protected function _setAccounttypedescription($accountTypeDescription) {
        if (!in_array($accountTypeDescription, Stpp_Types::getAccountTypeDescriptions())) {
            throw new Stpp_Exception(sprintf($this->__("Invalid accounttypedescription: '%s'."), $accountTypeDescription));
        }
        $this->_set('accounttypedescription', $accountTypeDescription);
    }
    
    protected function _setRequesttypedescription($requestTypeDescription) {
        if (!in_array($requestTypeDescription, Stpp_Types::getRequestAndResponseTypes())) {
            throw new Stpp_Exception(sprintf($this->__("Invalid request type description: '%s'."), $requestTypeDescription));
        }
        $this->_set('requesttypedescription', $requestTypeDescription);
    }
    
    protected function _setPaymenttype($paymentType) {
        if (!array_key_exists($paymentType, Stpp_Types::getCardTypes())) {
            throw new Exception(sprintf($this->__("Invalid paymenttype: '%s'."), $paymentType));
        }
        $this->_set('paymenttype', $paymentType);
    }
    
    protected function _setSettleduedate($settleDueDate) {
        $settleDueDate = (int) $settleDueDate;
        if (!array_key_exists($settleDueDate, Stpp_Types::getSettleDueDates())) {
            throw new Stpp_Exception(sprintf($this->__('An invalid settleduedate ("%s") has been provided.'), $settleDueDate));
        }
        $daysToAdd = '+ ' . $settleDueDate . ' days';
        $formattedSettleDueDate = date('Y-m-d', strtotime($daysToAdd));
        $this->_set('settleduedate', $formattedSettleDueDate);
    }
    
    protected function _setSettlestatus($settleStatus) {
        $settleStatusArray = Stpp_Types::getSettleStatuses();
        unset($settleStatusArray['3']);
        if (!array_key_exists($settleStatus, $settleStatusArray)) {
            throw new Stpp_Exception(sprintf($this->__('An invalid settle status ("%s") has been provided.'), $settleStatus));
        }
        $this->_set('settlestatus', $settleStatus);
    }
    
    protected function _setBillingtelephonetype($type) {
        $billingTelephone = $this->get('billingtelephone', '');
		if (!empty($billingTelephone)) {
            $this->_validateTelType($type);
        }
        $this->_set('billingtelephonetype', $type);
    }
    
    protected function _setCustomertelephonetype($type) {
        $customerTelephone = $this->get('customertelephone', '');
		if (!empty($customerTelephone)) {
            $this->_validateTelType($type);
        }
        $this->_set('customertelephonetype', $type);
    }
    
    protected function _setBillingcountryiso2a($country) {
        if ($country === 'US') { // For Payment Pages.
            $this->set('locale', 'en_US');
        }
        $this->_set('billingcountryiso2a', $country);
    }
    
    protected function _setCustomershippingmethod($customerShippingMethod) {
        if (!array_key_exists($customerShippingMethod, Stpp_Types::getCustomerShippingMethods())) {
            throw new Stpp_Exception(sprintf($this->__('Invalid shipping method provided; "%s".'), $customerShippingMethod));
        }
        $this->_set('customershippingmethod', $customerShippingMethod);
    }
    
    protected function _setParentcss($fileName) {
        if ($fileName) {
            $this->_set('parentcss', $fileName);
        }
    }
    
    protected function _setChildcss($fileName) {
        if ($fileName) {
            $this->_set('childcss', $fileName);
        }
    }
    
    protected function _setParentjs($fileName) {
        if ($fileName) {
            $this->_set('parentjs', $fileName);
        }
    }
    
    protected function _setChildjs($fileName) {
        if ($fileName) {
            $this->_set('childjs', $fileName);
        }
    }
    
    protected  function _validateTelType($type) {
        if (!in_array($type, Stpp_Types::getTelTypes())) {
            throw new Stpp_Exception(sprintf($this->__('Invalid telephone type provided: "%s".'), $type));
        }
        return $type;
    }
    
    protected function _getFilter($filter) {
        if (!($filter instanceof Stpp_Data_Request)) {
            throw new Stpp_Exception($this->__('The filter has not been set correctly.'));
        }
        return $filter;
    }
    
    protected function _getUpdates($updates) {
        if (!($updates instanceof Stpp_Data_Request)) {
            throw new Stpp_Exception($this->__('The updates have not been set correctly.'));
        }
        return $updates;
    }
}