<?php

class Securetrading_Stpp_Model_Payment_Redirect extends Securetrading_Stpp_Model_Payment_Abstract implements Mage_Payment_Model_Billing_Agreement_MethodInterface {
  protected $_code                        = 'securetrading_stpp_redirect';
  protected $_formBlockType               = 'securetrading_stpp/payment_redirect_form';
  protected $_infoBlockType               = 'securetrading_stpp/payment_redirect_info';
  
  protected $_isGateway                   = false;
  protected $_canOrder                    = false;
  protected $_canAuthorize                = false;
  protected $_canCapture                  = true;
  protected $_canCapturePartial           = false;
  protected $_canRefund                   = false;
  protected $_canRefundInvoicePartial     = false;
  protected $_canVoid                     = false;
  protected $_canUseInternal              = true;
  protected $_canUseCheckout              = true;
  protected $_canUseForMultishipping	  = true;
  protected $_isInitializeNeeded          = true;
  protected $_canFetchTransactionInfo     = false;
  protected $_canReviewPayment            = true;
  protected $_canCreateBillingAgreement   = true;
  protected $_canManageRecurringProfiles  = false;
  
  public function __construct() {
    if ($this->getConfigData('use_api')) {
      $this->_canCapturePartial = true;
      $this->_canRefund = true;
      $this->_canRefundInvoicePartial = true;
    }
  }
  
  public function initialize($action, $stateObject) {
    $this->log(sprintf('In %s.', __METHOD__));
    parent::initialize($action, $stateObject);
    $stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)->setStatus(Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_PPAGES);
  }
  
  public function getOrderPlaceRedirectUrl() {
    $this->log(sprintf('In %s.', __METHOD__));
    	return Mage::getUrl($this->getOrderPlaceRedirectPath());
  }
  
  public function getOrderPlaceRedirectPath() {
    $this->log(sprintf('In %s.', __METHOD__));
    return $this->_useFirstPathIfIframe('securetrading/redirect_post_onepage/iframe', 'securetrading/redirect_post_onepage/container');
  }
  public function getMultishippingRedirectPath() {
    $this->log(sprintf('In %s.', __METHOD__));
    return $this->_useFirstPathIfIframe('securetrading/redirect_post_multishipping/iframe', 'securetrading/redirect_post_multishipping/container');
  }
  
  public function getMotoOrderRedirectPath() {
    $this->log(sprintf('In %s.', __METHOD__));
    return $this->_useFirstPathIfIframe('*/sales_order_create_securetrading/iframe', '*/sales_order_create_securetrading/post');
  }
  
  public function prepareOrderData(Mage_Sales_Model_Order_Payment $payment, array $orderIncrementIds, $sendEmailConfirmation = true) {
    $this->log(sprintf('In %s.', __METHOD__));
    $data = parent::prepareOrderData($payment, $orderIncrementIds);
    $data += array(
      'customfield1' 		=> $payment->getOrder()->getStoreId(),
      'parentcss'     		=> $this->getConfigData('parent_css'),
      'childcss'      		=> $this->getConfigData('child_css'),
      'parentjs'      		=> $this->getConfigData('parent_js'),
      'childjs'       		=> $this->getConfigData('child_js'),
      //'subsitereference'          => $this->getConfigData('sub_site_reference'),
      '_charset_'     		=> Mage::getStoreConfig('design/head/default_charset'),
      'order_increment_ids' 	=> serialize($orderIncrementIds),
      'send_confirmation' 	=> $sendEmailConfirmation,
      'customer_id'               => Mage::getSingleton('customer/session')->getCustomerId(),
      'savecc'                    => Mage::getSingleton('securetrading_stpp/payment_redirect_session')->getSaveCardDetails(),
    );
    return $data;
  }
  
  public function assignData($data) {
    Mage::getSingleton('securetrading_stpp/payment_redirect_session')->setSaveCardDetails($data->getSecuretradingStppRedirectSaveCc());
    return $this;
  }
  
  public function cancel(Varien_Object $payment) {
    return $this; // Do nothing intentionally.
  }
  
  public function capture(Varien_Object $payment, $amount) {
    return $this->_captureAuthorized($payment, $amount);
  }
  
  public function prepareData($isMoto = false, array $orderIncrementIds = array(), $sendEmailConfirmation = true) {
    $this->log(sprintf('In %s.', __METHOD__));
    $data = $this->prepareOrderData($this->getInfoInstance(), $orderIncrementIds, $sendEmailConfirmation);
    $transport = $this->getIntegration()->runPaymentPages($data, $isMoto);
    return $transport;
  }
  
  public function validateOrdersArePendingPpages(array $orderIncrementIds) {
    if (empty($orderIncrementIds)) {
      throw new Exception(Mage::helper('securetrading_stpp')->__('No order increment IDs.'));
    }
    
    foreach($orderIncrementIds as $orderIncrementId) {
      if ($orderIncrementId === null) {
	throw new Exception(Mage::helper('securetrading_stpp')->__('No order ID.'));
      }
      
      $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
      
      if ($order->getPayment()->getMethodInstance()->getCode() !== Mage::getModel('securetrading_stpp/payment_redirect')->getCode()) {
	throw new Exception(Mage::helper('securetrading_stpp')->__('Cannot access payment method.'));
      }
      
      if (!$this->_canTakeOrderToPpages($order)) {
	throw new Exception(Mage::helper('securetrading_stpp')->__('Order cannot be taken to the Payment Pages.  State: "%s".  Status: "%s".', $order->getState(), $order->getStatus()));
      }
    }
    return $this;
  }
  
  public function getFirstMethodInstance(array $orderIncrementIds) {
    $firstOrderIncrementId = array_shift($orderIncrementIds);
    $order = Mage::getModel('sales/order')->loadByIncrementId($firstOrderIncrementId);
    return $order->getPayment()->getMethodInstance();
  }
  
  public function runRedirect() {
    $this->log(sprintf('In %s.', __METHOD__));
    return $this->getIntegration()->runRedirect();
  }
  
  public function runNotification() {
    $this->log(sprintf('In %s.', __METHOD__));
    $this->getIntegration()->runNotification();
  }
  
  public function updateBillingAgreementStatus(Mage_Payment_Model_Billing_AgreementAbstract $agreement) {
    return $this;
  }
  
  public function initBillingAgreementToken(Mage_Payment_Model_Billing_AgreementAbstract $agreement) {
    throw new Exception('Unused.');
  }
  
  public function getBillingAgreementTokenInfo(Mage_Payment_Model_Billing_AgreementAbstract $agreement) {
    throw new Exception('Unused.');
  }
  
  public function placeBillingAgreement(Mage_Payment_Model_Billing_AgreementAbstract $agreement) {
      throw new Exception('Unused.');
  }
  
  public function ordersAreSuccessful($orderIncrementIds) {
    foreach($orderIncrementIds as $orderIncrementId) {
      if ($this->orderIsSuccessful($orderIncrementId)) {
	continue;
      }
      return false;
    }
    return true;
  }
  
  public function orderIsSuccessful($orderIncrementId) {
    $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
    $successful = (bool) $this->_isProcessingOrPaymentReviewState($order) || $this->_isPendingSofortStatus($order);
    return $successful;
  }

  protected function _isProcessingOrPaymentReviewState(Mage_Sales_Model_Order $order) {
    return (bool) in_array($order->getState(), array(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW), true);
  }

  protected function _isPendingPpagesStatus(Mage_Sales_Model_Order $order) {
    return (bool) ($order->getState() === Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) && ($order->getStatus() === Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_PPAGES);
  }

  protected function _isPendingSofortStatus(Mage_Sales_Model_Order $order) {
    return (bool) ($order->getState() === Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) && ($order->getStatus() === Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_SOFORT);
  }

  protected function _canTakeOrderToPpages($order) {
    return (bool) $this->_isPendingPpagesStatus($order) || $this->_isPendingSofortStatus($order);
    /*
      Note - if Pending Ppages - then Pending Sofort - then go back to Magento, cart has already been cleared in PPG AUTH notification.  Doesn't cause any actual problems but is curious.
     */
  }

  protected function _useFirstPathIfIframe($path1, $path2) {
    if ($this->getConfigData('ppg_use_iframe')) {
      return $path1;
    }
    return $path2;
  }

  public function onRedirect(array $orderIncrementIds, $errorCode, $paymentTypeDescription) {
    if ($errorCode === '0' && $paymentTypeDescription === $this->getIntegration()->getSofortName()) {
      foreach($orderIncrementIds as $orderIncrementId) {
	$order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
	$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Securetrading_Stpp_Model_Payment_Abstract::STATUS_PROCESSING_SOFORT);
	$order->save(); // Must save before sendNewOrderEmail() since that calls $this->load().
	$order->sendNewOrderEmail();
	$order->save();
      }
      $quote = Mage::getModel('sales/quote')->loadByIdWithoutStore($order->getQuoteId());
      if ($quote->getIsActive()) {
	$quote->setIsActive(false)->save();
      }
    }
  }
}