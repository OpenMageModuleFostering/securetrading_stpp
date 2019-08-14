<?php

abstract class Securetrading_Stpp_Model_Payment_Direct_Abstract extends Securetrading_Stpp_Model_Payment_Abstract {
  protected $_isGateway                   = false;
  protected $_canOrder                    = false;
  protected $_canAuthorize                = true;
  protected $_canCapture                  = true;
  protected $_canCapturePartial           = true;
  protected $_canRefund                   = true;
  protected $_canRefundInvoicePartial     = true;
  protected $_canVoid                     = false;
  protected $_canUseInternal              = true;
  protected $_canUseCheckout              = true;
  protected $_canUseForMultishipping      = true;
  protected $_isInitializeNeeded          = true;
  protected $_canFetchTransactionInfo     = false;
  protected $_canReviewPayment            = true;
  protected $_canCreateBillingAgreement   = false;
  protected $_canManageRecurringProfiles  = false;

  protected $_frontendHandler;
  protected $_sessionModelType = '';

  abstract protected function _get3dAuthData();

  protected function _getFrontendHandler() {
    if (!$this->_frontendHandler) {
      $this->_frontendHandler = Mage::getModel('securetrading_stpp/payment_handler_frontend_factory', array('integration' => $this->getIntegration()))->getHandler($this);
    }
    return $this->_frontendHandler;
  }

  public function getSession() {
    return Mage::getSingleton($this->_sessionModelType);
  }

  public function initialize($action, $stateObject) {
    $this->log(sprintf('In %s.', __METHOD__));
    $this->_getFrontendHandler()->initialize($this->getInfoInstance(), $action, $stateObject);
  }
  
  public function processInvoice($invoice, $payment) {
    $this->log(sprintf('In %s.', __METHOD__));
    $this->_getFrontendHandler()->processInvoice($invoice, $payment);
    return $this;
  }

  public function authorize(Varien_Object $payment, $amount) {
    $this->log(sprintf('In %s.', __METHOD__));
    parent::authorize($payment, $amount);
    $this->_getFrontendHandler()->authorize($payment);
    return $this;
  }

  public function capture(Varien_Object $payment, $amount) {
    $this->log(sprintf('In %s.', __METHOD__));
    parent::capture($payment, $amount);
    if ($payment->lookupTransaction('', Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH)) {
      $this->_captureAuthorized($payment, $amount);
    }
    else {
      $this->_getFrontendHandler()->capture($payment, $amount);
    }
    return $this;
  }

  public function run3dAuth() {
    $this->log(sprintf('In %s.', __METHOD__));
    $data = $this->_get3dAuthData();
    $result = $this->_getFrontendHandler()->run3dAuth($data);
    return $result;
  }
  
  public function getOrderPlaceRedirectUrl() {
    $this->log(sprintf('In %s.', __METHOD__));
    return $this->_getFrontendHandler()->getOrderPlaceRedirectUrl();
  }
  
  public function cancel(Varien_Object $payment) {
    $this->log(sprintf('In %s.', __METHOD__));
    return $this; // Do nothing intentionally.
  }
}