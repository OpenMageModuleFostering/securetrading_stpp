<?php

abstract class Securetrading_Stpp_Model_Payment_Handler_Frontend_Abstract extends Varien_Object {
  protected $_stateObject;

  abstract protected function _canInitialize();

  abstract protected function _beforeInitialize(Varien_Object $stateObject);

  abstract protected function _canAuthorize(Mage_Sales_Model_Order_Payment $payment);

  abstract protected function _canCapture(Mage_Sales_Model_Order_Payment $payment);

  public function initialize(Mage_Sales_Model_Order_Payment $payment, $action, $stateObject) {
    if (!$this->_canInitialize()) {
      return;
    }
    $this->_beforeInitialize($stateObject);
    $this->_initialize($payment, $stateObject, $action);    
  }
  
  public function authorize(Varien_Object $payment) {
    if ($this->_canAuthorize($payment)) {
      $this->_authorize($payment);
    }
  }

  public function capture(Varien_Object $payment, $amount) {
    if ($this->_canCapture($payment)) {
      $this->_capture($payment, $amount);
    }
  }

  public function processInvoice($invoice, $payment) {
    if ($this->_getReadyForAcsUrlRedirect()) {
      foreach($payment->getOrder()->getStatusHistoryCollection(true) as $c) {
	$c->delete();
      }
      $invoice->setIsPaid(false);
    }
  }

  public function run3dAuth(array $data = array()) {
    $this->_beforeRun3dAuth();
    $result = $this->getIntegration()->runApi3dAuth($data);
    if ($result->getErrorMessage()) {
      Mage::getSingleton('checkout/session')->addError($result->getErrorMessage());
    }
    return $result->getIsTransactionSuccessful();
  }

  public function getOrderPlaceRedirectUrl() {
    $session = $this->getSession();
    $acsParamsExist = $session->hasAcsRedirectParams();
    
    $this->getMethodInstance()->log(sprintf('In %s.  ACS Params exist: %s.', __METHOD__, $acsParamsExist));
        
    if ($acsParamsExist) {
      return $session->getAcsRedirectParams()->getOrderPlaceRedirectUrl();
    }
    return null;
  }
  
  protected function _initialize(Mage_Sales_Model_Order_Payment $payment, Varien_Object $stateObject, $action) {
    $this->_setStateObject($stateObject);
    $order = $payment->getOrder();
    
    switch ($action) {
    case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE:
      $payment->authorize(true, $order->getBaseTotalDue()); // base amount will be set inside
      $payment->setAmountAuthorized($order->getTotalDue());
      break;
    case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE:
      $payment->setAmountAuthorized($order->getTotalDue());
      $payment->setBaseAmountAuthorized($order->getBaseTotalDue());
      $payment->capture(null);
      break;
    default:
      throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('Invalid payment action: "%s".'), $action));
    }
  }

  protected function _authorize(Varien_Object $payment) {
    $result = $this->getIntegration()->runApiStandard($payment);
    $this->_handleStandardPaymentResult($payment, $result);
    return $this;
  }
  
  protected function _capture(Varien_Object $payment, $amount) {
    $result = $this->getIntegration()->runApiStandard($payment);
    $this->_handleStandardPaymentResult($payment, $result);
    return $this;
  }
  
  protected function _handleStandardPaymentResult(Mage_Sales_Model_Order_Payment $payment, Stpp_Api_ResultInterface $result) {
    if ($result->getRedirectRequired()) {
      $this->_setReadyForAcsUrlRedirect(true);
      $this->_getStateObject()->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)->setStatus(Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_3DSECURE);
      
      $redirectPath = $payment->getMethodInstance()->getConfigData('use_iframe') ? 'securetrading/direct_post/iframe' : 'securetrading/direct_post/container';
      $params = new Varien_Object();
      $params
	->setOrderPlaceRedirectUrl(Mage::getUrl($redirectPath))
	->setRedirectIsPost($result->getRedirectIsPost())
	->setRedirectUrl($result->getRedirectUrl())
	->setRedirectData($result->getRedirectData())
	;
      $this->getSession()->setAcsRedirectParams($params);
    }
    elseif(!$result->getIsTransactionSuccessful()) {
      throw new Mage_Payment_Model_Info_Exception($result->getErrorMessage());
    }
    else {
      $this->_getStateObject()->setState(Mage_Sales_Model_Order::STATE_PROCESSING)->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
    }
    return $this;
  }

  protected function _setStateObject(Varien_Object $stateObject) {
    $this->_stateObject = $stateObject;
  }
  
  protected function _getStateObject($graceful = false) {
    if ($this->_stateObject === null) {
      if ($graceful) {
	return false;
      }
      throw new Exception(Mage::helper('securetrading_stpp')->__('The state object has not been set.'));
    }
    return $this->_stateObject;
  }
    
  protected function _setReadyForAcsUrlRedirect($bool) {
    $this->_getStateObject()->setReadyForAcsRedirect((bool)$bool);
    return $this;
  }

  protected function _getReadyForAcsUrlRedirect() {
    $stateObject = $this->_getStateObject(true); // Because processInvoice() is called when capturing a pending invoice.
    if ($stateObject) {
      return (bool) $stateObject->getReadyForAcsRedirect();
    }
    return false;
  }
}