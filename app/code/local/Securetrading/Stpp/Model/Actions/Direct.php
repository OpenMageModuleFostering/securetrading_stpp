<?php

class Securetrading_Stpp_Model_Actions_Direct extends Securetrading_Stpp_Model_Actions_Abstract implements Stpp_Api_ActionsInterface {
  const MULTISHIPPING_ORDERS_REGISTRY_KEY = 'securetrading_stpp_actions_direct_multishipping_orders_registry_key';

  protected function _getOrders(Stpp_Data_Response $response) {
    if ($orders = Mage::registry(Securetrading_Stpp_Model_Actions_Direct::MULTISHIPPING_ORDERS_REGISTRY_KEY)) {
      return $orders;
    }
    else { // onepage
      return array($this->_getOrder($response));
    }
  }

  public function processAuth(Stpp_Data_Response $response) {
    foreach($this->_getOrders($response) as $order) {
      $this->setOrder($order);
      $this->_processAuth($response);
    }
    $quote = Mage::getModel('sales/quote')->loadByIdWithoutStore($order->getQuoteId());;
    if ($quote->getIsMultiShipping()) { // The multishipping successAction() requires an active quote (the quote was set to inactive in handleSuccessfulPayment() above.
      Mage::getSingleton('checkout/type_multishipping_state')
	->setActiveStep(Mage_Checkout_Model_Type_Multishipping_State::STEP_SUCCESS)
	->setCompleteStep(Mage_Checkout_Model_Type_Multishipping_State::STEP_OVERVIEW)
      ;
      $quote->setIsActive(true)->save();
    }
    $order->getPayment()->getMethodInstance()->getSession()->clear();

    return $this->_isErrorCodeZero($response);
  }

  protected function _handleSofortPayment(Stpp_Data_Response $response) {
    throw new Exception(Mage::helper('securetrading_stpp')->__('The direct payment method does not currently allow sofort payments.'));
  }
    
  protected function _processAuth(Stpp_Data_Response $response) {
    parent::processAuth($response);
    $order = $this->_getOrder($response);
    $payment = $order->getPayment();
    $methodInstance = $payment->getMethodInstance();

    if ($this->_authShouldEnterPaymentReview($response)) {
      if ($methodInstance->getIsMultishipping() && !$methodInstance->getIsFirstMultishipping()) {
	$payment->setIsTransactionPending(false); ## reverse the setting to pending - so exception at bottom of mage_sales_model_order_payment::capture() isn't thrown - but it will be reset by our code in direct payment method's capture() func.
	Mage::register('stpp_test_key', true);
      }

    }

    if ($this->_paymentIsSuccessful($response) || $this->_authShouldEnterPaymentReview($response)) {
      if ($response->getRequest()->has('md')) {
	Mage::helper('securetrading_stpp')->registerSuccessfulOrderAfterExternalRedirect($order, $this->_getRequestedSettleStatus($response));	
      }
      $this->_handleSuccessfulOrder($order, true);
    }
    
    if ($methodInstance->getCode() === Mage::getModel('securetrading_stpp/payment_tokenization')->getCode()) {
      $payment
	->setCcType($response->get('paymenttypedescription'))
	->setCcLast4($payment->getMethodInstance()->getIntegration()->getCcLast4($response->get('maskedpan')))
	->save()
      ;
    }
  }
    
  public function process3dQuery(Stpp_Data_Response $response) {
    foreach($this->_getOrders($response) as $order) {
      $this->setOrder($order);
      parent::process3dQuery($response);
    }
    return $this->_isErrorCodeZero($response);
  }
    
  public function processRiskDecision(Stpp_Data_Response $response) {
    foreach($this->_getOrders($response) as $order) {
      $this->setOrder($order);
      parent::processRiskDecision($response);
    }
    return $this->_isErrorCodeZero($response);
  }
    
  public function processTransactionUpdate(Stpp_Data_Response $response) {
    foreach($this->_getOrders($response) as $order) {
      $this->setOrder($order);
      parent::processTransactionUpdate($response);
    }
    return $this->_isErrorCodeZero($response);
  }
    
  public function processRefund(Stpp_Data_Response $response) {
    foreach($this->_getOrders($response) as $order) {
      $this->setOrder($order);
      parent::processRefund($response);
    }
    return $this->_isErrorCodeZero($response);
  }
    
  public function processAccountCheck(Stpp_Data_Response $response) {
    foreach($this->_getOrders($response) as $order) {
      $this->setOrder($order);
      parent::processAccountCheck($response);
    }
    return $this->_isErrorCodeZero($response);
  }

  public function processCardstore(Stpp_Data_Response $response) {    
    $paymentMethod = Mage::getModel('securetrading_stpp/payment_direct');
    $checkoutUsed = (bool) $this->_getOrder($response)->getPayment();

    if ($checkoutUsed) { // cardstore done through a checkout        
      $orders = $this->_getOrders($response);
      $orderIncrementIds = array();
      foreach($orders as $order) {
	$orderIncrementIds[] = $order->getIncrementId();
      }
    }
    else { // cardstore done thorugh wizard
      $orderIncrementIds = array();
    }

    $agreement = Mage::helper('securetrading_stpp')->addBillingAgreement(
      $paymentMethod,
      Mage::getSingleton('customer/session')->getId(),
      $paymentMethod->prepareCardstoreLabel($response->get('maskedpan'), $response->get('paymenttypedescription'), $response->getRequest()->get('expirydate')),
      $response->get('transactionreference'),
      $response->get('paymenttypedescription'),
      null,
      $orderIncrementIds
    );


    if ($checkoutUsed) { //checkout
      Mage::getSingleton('checkout/session')->setLastBillingAgreementId($agreement->getId());
      Mage::getSingleton('checkout/session')->setLastSecuretradingBillingAgreementId($agreement->getId());// Must do this for onepage checkout (see Mage_Checkout_ModeL_Type_Onepage::saveOrder(), call to Mage_checkout_Model_Session::clearHelperData().  see securetrading_stpp_model_observer::onCheckoutTypeOnepageSaveOrderAfter() too.
    }
    else {
      Mage::getSingleton('customer/session')->setLastBillingAgreementId($agreement->getId());
    }

    return $this->_isErrorCodeZero($response);
  }
}