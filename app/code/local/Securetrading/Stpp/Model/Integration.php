<?php

class Securetrading_Stpp_Model_Integration extends Mage_Core_Model_Abstract {
    protected $_facade;
    
    protected $_ppagesFacade;
    
    protected $_apiFacade;
    
    protected $_ppagesActionInstance;
    
    protected $_apiActionInstance;
    
    protected $_frontendFields;
    
    protected $_adminFields;
    
    protected $_debugLog;
    
    public function _construct() {
        require_once(Mage::getModuleDir('', 'Securetrading_Stpp') . DS . 'lib' . DS . 'Securetrading.php');
        Securetrading::init();
        
        $config = array();
        $paymentMethod = $this->getPaymentMethod();
    
        if ($paymentMethod) {
            $this->_ppagesActionInstance = Mage::getModel('securetrading_stpp/actions_redirect');
            $this->_apiActionInstance = Mage::getModel('securetrading_stpp/actions_direct');
            
            $config = array(
                'connections' => array(
                    'web_services' => array(
                        'username'                          => $paymentMethod->getConfigData('ws_username'),
                        'password'                          => $paymentMethod->getConfigData('ws_password'),
                        'alias'                             => $paymentMethod->getConfigData('ws_alias'),
                        'verifyssl'                         => $paymentMethod->getConfigData('ws_verify_ca'),
                        'cacertfile'                        => $paymentMethod->getConfigData('ws_ca_file'),
                    ),
                    'api' => array(
                        'host'                              => $paymentMethod->getConfigData('stapi_host'),
                        'port'                              => $paymentMethod->getConfigData('stapi_port'),
                    	'alias'								=> $paymentMethod->getConfigData('stapi_alias'),
                    ),
                ),
                'interfaces' => array(
                    'ppages' => array(
                        'action_instance'                   => $this->_ppagesActionInstance,
                        'notificationhash' => array(
                            'password'                      => $paymentMethod->getConfigData('notification_password'),
                            'algorithm'                     => 'sha256',
                            'use'                           => $paymentMethod->getConfigData('use_notification_password'),
                        ),
                        'sitesecurity' => array(
                            'password'                      => $paymentMethod->getConfigData('site_security_password'),
                            'algorithm'                     => 'sha256',
                            'use'                           => $paymentMethod->getConfigData('use_site_security'),
                        	'fields'						=> array('order_increment_ids'),
                        ),
                        'use_authenticated_moto'            => false,
                        'use_http_post'                     => true,
                    ),
                    'api' => array(
                        'action_instance'                   => $this->_apiActionInstance,
                        'active_connection'                 => $paymentMethod->getConfigData('connection'),
                        'use_3d_secure'                     => $paymentMethod->getConfigData('use_3d_secure'),
                        'use_risk_decision'                 => $paymentMethod->getConfigData('use_risk_decision'),
                        'use_card_store'                    => $paymentMethod->getConfigData('use_card_store'),
                        'use_risk_decision_after_auth'      => $paymentMethod->getConfigData('delay_risk_decision'),
                        'use_auto_card_store'               => $paymentMethod->getConfigData('use_auto_card_store'),
                    )
                ),
            );
        }
        
        $utilityFacade = Magento_Utility_Facade::instance($config); // Must be done before using any other parts of the STPP framework.
        $fieldFacade = Stpp_Fields_Facade::instance($config);
        
        $this->_facade = Stpp_Facade::instance($config);
        $this->_ppagesFacade = Stpp_PaymentPages_Facade::instance($config);
        $this->_apiFacade = Stpp_Api_Facade::instance($config);
        
        $this->_frontendFields = $fieldFacade->newFrontendFields();
        $this->_adminFields = $fieldFacade->newAdminFields();
        $this->_debugLog = $utilityFacade->newDebugLog();
    }
    
    public function getPaymentMethod() {
        $paymentMethod = $this->getData('payment_method');
        
        if (!($paymentMethod instanceof Securetrading_Stpp_Model_Payment_Abstract)) {
            return false;
        }
        return $paymentMethod;   
    }
    
    protected function _getFrontendFields() {
        if ($this->_frontendFields === null) {
            throw new Stpp_Exception(Mage::helper('securetrading_stpp')->__('An instance of the frontend fields has not been set.'));
        }
        return $this->_frontendFields;
    }
    
    public function getDebugLog() {
        if ($this->_debugLog === null) {
            throw new Stpp_Exception(Mage::helper('securetrading_stpp')->__('An instance of the debug log has not been set.'));
        }
        return $this->_debugLog;
    }
    
    public function getAdminFields() {
        if ($this->_adminFields === null) {
            throw new Stpp_Exception(Mage::helper('securetrading_stpp')->__('An instance of the admin fields has not been set.'));
        }
        return $this->_adminFields;
    }
    
    public function getSettleDueDates() {
        return Stpp_Types::getSettleDueDates();
    }
    
    public function getSettleStatuses() {
        return Stpp_Types::getSettleStatuses(true);
    }
    
    public function getCardTypes() {
        return Stpp_Types::getCardTypes();
    }
    
    public function getConnections() {
        $connections = array();
        foreach($this->_apiFacade->newApiConnectionStore()->getAll() as $k => $v) {
            $connections[$k] = $v::getName();
        }
        return $connections;
    }
    
    protected function _setOrderToActionInstances(Mage_Sales_Model_Order $order) {
    	$this->_apiActionInstance->setOrder($order);
    	$this->_ppagesActionInstance->setOrder($order);
    	return $this;
    }
    public function runApiTransactionUpdate(Mage_Sales_Model_Order_Payment $payment, array $data) {
    	$this->_setOrderToActionInstances($payment->getOrder());
    	$filter = Stpp_Data_Request::instance()->setMultiple($data['filter']);
    	$updates = Stpp_Data_Request::instance()->setMultiple($data['updates']);
    	$request = Stpp_Data_Request::instance()->set('filter', $filter)->set('updates', $updates);
    	$result = $this->_apiFacade->runApiTransactionUpdate($request);
    	
    	if (!$result->getIsTransactionSuccessful()) {
    		throw new Mage_Core_Exception($result->getErrorMessage());
    	}
    	return $this;
    }
    
    public function runApiRefund(Mage_Sales_Model_Order_Payment $payment, array $data) {
    	$this->_setOrderToActionInstances($payment->getOrder());
    	$request = Stpp_Data_Request::instance()->setMultiple($data);
    	$result = $this->_apiFacade->runApiRefund($request);
    	
    	if (!$result->getIsTransactionSuccessful()) {
    		throw new Mage_Core_Exception('The gateway did not process the refund successfully.');
    	}
    	return $this;
    }
    
    public function runApiStandard(Mage_Sales_Model_Order_Payment $payment, $isMoto = false) {
        $isMoto = $payment->getOrder()->getQuote()->getIsSuperMode();
        $this->_setOrderToActionInstances($payment->getOrder());
        $data = $this->getPaymentMethod()->prepareOrderData($payment);
        $request = Stpp_Data_Request::instance()->setMultiple($data);
        return $this->_apiFacade->runApiStandard($request, $isMoto);
    }
    
    public function runApi3dAuth() {
    	return $this->_apiFacade->runApi3dAuth(new Stpp_Data_Request());
    }
    
    public function runPaymentPages(array $data, $isMoto = false) {
        $request = Stpp_Data_Request::instance()->setMultiple($data);
        $result = $this->_ppagesFacade->runPaymentPagesStandard($request, $isMoto);
        Mage::getModel('securetrading_stpp/payment_redirect_request')->addRequest($this->getPaymentMethod()->getInfoInstance(), $result->getRequest());
        
        $transport = new Varien_Object();
        $transport->setRedirectIsPost($result->getRedirectIsPost());
        $transport->setRedirectUrl($result->getRedirectUrl());
        $transport->setRedirectData($result->getRedirectData());
        return $transport;
    }
    
    public function runRedirect() {
        $this->_ppagesFacade->newPaymentPages()->validateRedirect();
    }
    
    public function runNotification() {
        $this->_ppagesFacade->newPaymentPages()->runNotification();
    }
    
    public function getAcceptedCards($use3dSecure, $acceptedCards = array()) {
    	if (!is_array($acceptedCards)) {
    		$acceptedCards = array();
    	}
        $helper = $this->_facade->newHelper();
        return $helper->getFilteredCardTypes($use3dSecure, $acceptedCards);
    }
    
    public function getCcLast4($pan) {
        $helper = $this->_facade->newHelper();
        return $helper->getCcLast4($pan);
    }
    
    public function getCardString($cardKey) {
        $cards = Stpp_Types::getCardTypes();
        if (array_key_exists($cardKey, $cards)) {
            return $cards[$cardKey];
        }
        else {
            return '';
        }
    }
    
    public function getAvsString($avsKey) {
        $avsCodes = Stpp_Types::getAvsCodes();
        if (array_key_exists($avsKey, $avsCodes)) {
            return $avsCodes[$avsKey];
        }
        return $avsKey;
    }
    
    public function getMonths() {
        $months = Stpp_Types::getMonths();
        $array = array();
        foreach($months as $month) {
            $array[$month['numeric']] = $month['short'];
        }
        return $array;
    }
    
    public function getRefundTransactionName() {
    	return Stpp_Types::API_REFUND;
    }
    
    public function getStartYears() {
        return Stpp_Types::getStartYears();
    }
    
    public function getExpiryYears() {
        return Stpp_Types::getExpiryYears();
    }
    
    public function getCardTypeLabel() {
        return $this->_getFrontendFields()->getLabel(Stpp_Fields_Frontend::FIELD_PAYMENT_TYPE);
    }
    
    public function getCardTypeDescription() {
        return $this->_getFrontendFields()->getDescription(Stpp_Fields_Frontend::FIELD_PAYMENT_TYPE);
    }
    
    public function getCardNumberLabel() {
        return $this->_getFrontendFields()->getLabel(Stpp_Fields_Frontend::FIELD_PAN);
    }
    
    public function getCardNumberDescription() {
        return $this->_getFrontendFields()->getDescription(Stpp_Fields_Frontend::FIELD_PAN);
    }
    
    public function getCardStartDateLabel() {
        return $this->_getFrontendFields()->getLabel(Stpp_Fields_Frontend::FIELD_START_DATE);
    }
    
    public function getCardStartDateDescription() {
        return $this->_getFrontendFields()->getDescription(Stpp_Fields_Frontend::FIELD_START_DATE);
    }
    
    public function getCardExpiryDateLabel() {
        return $this->_getFrontendFields()->getLabel(Stpp_Fields_Frontend::FIELD_EXPIRY_DATE);
    }
    
    public function getCardExpiryDateDescription() {
        return $this->_getFrontendFields()->getDescription(Stpp_Fields_Frontend::FIELD_EXPIRY_DATE);
    }
    
    public function getCardSecurityCodeLabel() {
        return $this->_getFrontendFields()->getLabel(Stpp_Fields_Frontend::FIELD_SECURITY_CODE);
    }
    
    public function getCardSecurityCodeDescription() {
        return $this->_getFrontendFields()->getDescription(Stpp_Fields_Frontend::FIELD_SECURITY_CODE);
    }
    
    public function getCardIssueNumberLabel() {
        return $this->_getFrontendFields()->getLabel(Stpp_Fields_Frontend::FIELD_ISSUE_NUMBER);
    }
    
    public function getCardIssueNumberDescription() {
        return $this->_getFrontendFields()->getDescription(Stpp_Fields_Frontend::FIELD_ISSUE_NUMBER);
    }
}