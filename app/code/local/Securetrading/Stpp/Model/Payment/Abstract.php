<?php

abstract class Securetrading_Stpp_Model_Payment_Abstract extends Mage_Payment_Model_Method_Abstract {
  const STATUS_AUTHORIZED  = 'authorized';
  const STATUS_SUSPENDED  = 'suspended';
  const STATUS_PENDING_PPAGES = 'pending_ppages';
  const STATUS_PENDING_3DSECURE = 'pending_3dsecure';
  const STATUS_PENDING_SOFORT = 'pending_sofort';
  const STATUS_PROCESSING_SOFORT = 'processing_sofort';
  
  protected function _getBackendHandler($transactionReference) {
    return Mage::getModel('securetrading_stpp/payment_handler_backend_factory', array('transaction_reference' => $transactionReference, 'method_instance' => $this))->getHandler();
  }

  protected function _captureAuthorized(Mage_Sales_Model_Order_Payment $payment, $amount) {
    $this->log(sprintf('In %s.', __METHOD__));
    $this->_getBackendHandler($payment->getCcTransId())->captureAuthorized($payment, $amount);
    return $this;
  }
  
  final public function getIsSecuretradingPaymentMethod() {
    return true;
  }

  public static function getVersionInformation() {
    $stppVersion = (string) Mage::getConfig()->getModuleConfig('Securetrading_Stpp')->version;
    $multishippingVersion = (string) Mage::getConfig()->getModuleConfig('Securetrading_Multishipping')->version;
    $str = sprintf('Magento %s %s (Securetrading_Stpp-%s Securetrading_Multishipping-%s)', Mage::getEdition(), Mage::getVersion(), $stppVersion, $multishippingVersion);
    return $str;
  }

  public function getIntegration() {
    return Mage::getModel('securetrading_stpp/integration', array('payment_method' => $this));
  }
    
  public function log($message) {
    try {
      $order = $this->getInfoInstance()->getOrder();
    }
    catch (Exception $e) {
      // Do nothing here intentionally.
    }
    $sidToken = md5(Mage::getModel('core/session')->getSessionId());
    $orderIncrementId = isset($order) && $order ? $order->getIncrementId() : 'N/A';
    $message = $this->_code . ' - ' .$orderIncrementId . ' - ' . $sidToken . ' - ' . $message;
    $this->getIntegration()->getDebugLog()->log($message);
    return $this;
  }

  public function refund(Varien_Object $payment, $amount) {
    $this->log(sprintf('In %s.', __METHOD__));
    parent::refund($payment, $amount);
    $this->_getBackendHandler($payment->getCcTransId())->refund($payment, $amount);
    return $this;
  }

  public function acceptPayment(Mage_Payment_Model_Info $payment) {
    $this->log(sprintf('In %s.', __METHOD__));
    parent::acceptPayment($payment);
    $this->_getBackendHandler($payment->getCcTransId())->acceptPayment($payment);
    return true;
  }

  public function denyPayment(Mage_Payment_Model_Info $payment) {
    $this->log(sprintf('In %s.', __METHOD__));
    parent::denyPayment($payment);
    $this->_getBackendHandler($payment->getCcTransId())->denyPayment($payment);
    return true;
  }
  
  public function prepareOrderData(Mage_Sales_Model_Order_Payment $payment, array $orderIncrementIds, $sendEmailConfirmation = true) {
    $order = $payment->getOrder();
    $billingAddress = $order->getBillingAddress();
    $billingCounty = $billingAddress->getCountry() == 'US' ? $billingAddress->getRegionCode() : $billingAddress->getRegion();
    $billingTelephoneNumber = $billingAddress->getTelephone();
    $billingTelephoneType = !empty($billingTelephoneNumber) ? 'H' : '';
    $customerDobFull = $order->getCustomerDob();
    $customerDobArray = explode(' ', $customerDobFull);
    $customerDob = $customerDobArray[0];
        
    $baseTotalDue = 0;
    
    foreach($orderIncrementIds as $orderIncrementId) {
      $baseTotalDue += Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId)->getBaseTotalDue();
    }
        
    $data = array(
      'sitereference'             => $this->getConfigData("site_reference"),
      'currencyiso3a'             => $order->getBaseCurrencyCode(),
      'mainamount'=> $baseTotalDue,
            
      'billingprefixname'         => $billingAddress->getPrefix(),
      'billingfirstname'          => $billingAddress->getFirstname(),
      'billingmiddlename'         => $billingAddress->getMiddlename(),
      'billinglastname'           => $billingAddress->getLastname(),
      'billingsuffixname'         => $billingAddress->getSuffix(),
      'billingemail'              => $billingAddress->getEmail(),
      'billingtelephone'          => $billingTelephoneNumber,
      'billingtelephonetype'      => $billingTelephoneType,
      'billingpremise'            => $billingAddress->getStreet(1),
      'billingstreet'             => $billingAddress->getStreet(2),
      'billingtown'               => $billingAddress->getCity(),
      'billingcounty'             => $billingCounty,
      'billingpostcode'           => $billingAddress->getPostcode(),
      'billingcountryiso2a'       => $billingAddress->getCountry(),
      'billingdob'                => $customerDob,
            
      'settleduedate'             => $this->getConfigData('settle_due_date'),
      'settlestatus'              => $this->getConfigData('payment_action') === Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE ? 2 : $this->getConfigData('settle_status'),
      'orderreference'            => $order->getIncrementId(),

      'customfield4'             => $this->_getCartInformation(),
      'customfield5'             => self::getVersionInformation(),
		  );
        
    if ($order->getShippingMethod()) {
      $customerAddress = $order->getShippingAddress();
      $customerCounty = $customerAddress->getCountry() == 'US' ? $customerAddress->getRegionCode() : $customerAddress->getRegion();
      $customerTelephoneNumber = $customerAddress->getTelephone();
      $customerTelephoneType = !empty($customerTelephoneNumber) ? 'H' : '';
            
      $data += array(
	'customerprefixname'        => $customerAddress->getPrefix(),
	'customerfirstname'         => $customerAddress->getFirstname(),
	'customermiddlename'        => $customerAddress->getMiddlename(),
	'customerlastname'          => $customerAddress->getLastname(),
	'customersuffixname'        => $customerAddress->getSuffix(),
	'customeremail'             => $customerAddress->getEmail(),
	'customertelephone'         => $customerTelephoneNumber,
	'customertelephonetype'     => $customerTelephoneType,
	'customerpremise'           => $customerAddress->getStreet(1),
	'customerstreet'            => $customerAddress->getStreet(2),
	'customertown'              => $customerAddress->getCity(),
	'customercounty'            => $customerCounty,
	'customerpostcode'          => $customerAddress->getPostcode(),
	'customercountryiso2a'      => $customerAddress->getCountry(),
      );
    }
    return $data;
  }

  protected function _getCartInformation() {
    return 'MAGENTO';
  }
}