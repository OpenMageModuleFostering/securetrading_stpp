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
            
      $siteSecurity = $paymentMethod->getConfigData('site_security_password');
      $notificationHash = $paymentMethod->getConfigData('notification_password');

      $useNotificationHash = (bool) is_string($siteSecurity) && !empty($siteSecurity);
      $notificationHash = $siteSecurity;

      $config = array(
	'connections' => array(
	  'web_services' => array(
	    'username'                          => $paymentMethod->getConfigData('ws_username'),
	    'password'                          => $paymentMethod->getConfigData('ws_password'),
	    'alias'                             => $paymentMethod->getConfigData('ws_username'),
	    'ssl_verify_peer'                   => $paymentMethod->getConfigData('ws_verify_ca'),
	    'ssl_verify_host'=> 2,
	    'ssl_cacertfile'                    => $paymentMethod->getConfigData('ws_ca_file'),
	    'user_agent'                        => Securetrading_Stpp_Model_Payment_Abstract::getVersionInformation(),
				  ),
			       ),
	'interfaces' => array(
	  'ppages' => array(
	    'action_instance'                   => $this->_ppagesActionInstance,
	    'notificationhash' => array(
	      'password'                      => $notificationHash,
	      'algorithm'                     => 'sha256',
	      'use'                           => $useNotificationHash,
					),
	    'sitesecurity' => array(
	      'password'                      => $paymentMethod->getConfigData('site_security_password'),
	      'algorithm'                     => 'sha256',
	      'use'                           => is_string($siteSecurity) && !empty($siteSecurity),
				    ),
	    'use_authenticated_moto'            => false,
	    'use_http_post'                     => true,
			    ),
	  'api' => array(
	    'action_instance'                   => $this->_apiActionInstance,
	    'active_connection'                 => Stpp_Api_Connection_Webservices::getKey(),
	    'use_3d_secure'                     => $paymentMethod->getConfigData('use_3d_secure'),
	    'use_risk_decision'                 => $paymentMethod->getConfigData('use_risk_decision'),
	    'use_account_check'                 => $paymentMethod->getConfigData('use_account_check'),
	    'use_card_store'                    => $paymentMethod->getConfigData('use_card_store'),
			 )
			      ),
	'transactionsearch' => array(
	  'username'=> $paymentMethod->getConfigData('ws_username'),
	  'password'=> $paymentMethod->getConfigData('ws_password'),
	  'ssl_verify_peer'=> $paymentMethod->getConfigData('ws_verify_ca'),
	  'ssl_verify_host'=> 2,
	  'ssl_cacertfile'=> $paymentMethod->getConfigData('ws_ca_file'),
				     )
		      );


      $config['interfaces']['ppages']['sitesecurity']['fields'] = array('orderreference', 'accounttypedescription', 'order_increment_ids', 'order_increment_id');
    }
    
    $utilityFacade = Magento_Utility_Facade::instance($config); // Must be done before using any other parts of the STPP framework.
    $fieldFacade = Stpp_Fields_Facade::instance($config);
        
    $this->_facade = Stpp_Facade::instance($config);
    $this->_ppagesFacade = Stpp_PaymentPages_Facade::instance($config);
    $this->_apiFacade = Magento_Api_Facade::instance($config);
        
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
    
  public function runApiStandard(Mage_Sales_Model_Order_Payment $payment, $isMoto = false) {
    $isMoto = $payment->getOrder()->getQuote()->getIsSuperMode();
    $this->_setOrderToActionInstances($payment->getOrder());
    $orderIncrementIds = Mage::getModel('core/session')->getOrderIds() ? Mage::getModel('core/session')->getOrderIds() : array($payment->getOrder()->getIncrementId()); //multishipping or onepage
    $data = $this->getPaymentMethod()->prepareOrderData($payment, $orderIncrementIds);
    $request = Stpp_Data_Request::instance()->setMultiple($data);
    $saveCcDetails = $payment->getMethodInstance()->getSession()->getSaveCardDetails();
    $useCardStore = $saveCcDetails && $payment->getMethodInstance()->getConfigData('use_card_store');
    $this->_apiFacade->getConfig()->set('interfaces/api/use_card_store', $useCardStore);
    return $this->_apiFacade->runApiStandard($request, $isMoto);
  }

  public function runApi3dAuth(array $data = array()) {
    $request = new Stpp_Data_Request();
    if (!empty($data)) {
      $request->setMultiple($data);
    }
    $paymentMethod = $this->getPaymentMethod();
    $saveCcDetails = $paymentMethod->getSession()->getSaveCardDetails();
    $useCardStore = $saveCcDetails && $paymentMethod->getConfigData('use_card_store');
    $this->_apiFacade->getConfig()->set('interfaces/api/use_card_store', $useCardStore);
    return $this->_apiFacade->runApi3dAuth($request);
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
    

    public function runApiCardstore(array $data) {
      return $this->_apiFacade->runApiRequests($data, array(Stpp_Types::API_CARDSTORE));
    }

    public function runPaymentPages(array $data, $bypassChoicePage = false, $isMoto = false) {
        $request = Stpp_Data_Request::instance()->setMultiple($data);
        $result = $this->_ppagesFacade->runPaymentPagesStandard($request, $bypassChoicePage, $isMoto);
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
  
  public function getThreedqueryName() {
    return Stpp_Types::API_THREEDQUERY;
  }

  public function getSofortName() {
    return Stpp_Types::CARD_SOFORT;
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
    
  public function getCardExpiryMonthLabel() {
    return $this->_getFrontendFields()->getLabel(Stpp_Fields_Frontend::FIELD_EXPIRY_MONTH);
  }	       
    
  public function getCardExpiryMonthDescription() {
    return $this->_getFrontendFields()->getDescription(Stpp_Fields_Frontend::FIELD_EXPIRY_MONTH);
  }
  
  public function getCardExpiryYearLabel() {
    return $this->_getFrontendFields()->getLabel(Stpp_Fields_Frontend::FIELD_EXPIRY_YEAR);
  }
  
  public function getCardExpiryYearDescription() {
    return $this->_getFrontendFields()->getDescription(Stpp_Fields_Frontend::FIELD_EXPIRY_YEAR);
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
  
  public function getSaveCcDetailsLabel() {
    return $this->_getFrontendFields()->getLabel(Stpp_Fields_Frontend::FIELD_SAVE_CARD_QUESTION);
  }
  
  public function getSaveCcDetailsDescription() {
    return $this->_getFrontendFields()->getDescription(Stpp_Fields_Frontend::FIELD_SAVE_CARD_QUESTION);
  }
   
  public function getCardIssueNumberDescription() {
    return $this->_getFrontendFields()->getDescription(Stpp_Fields_Frontend::FIELD_ISSUE_NUMBER);
  }
    
  public function newTransactionSearch() {
    return $this->_apiFacade->newTransactionSearch();
  }
}