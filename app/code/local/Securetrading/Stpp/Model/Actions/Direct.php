<?php

class Securetrading_Stpp_Model_Actions_Direct extends Securetrading_Stpp_Model_Actions_Abstract implements Stpp_Api_ActionsInterface {
  const MULTISHIPPING_ORDERS_REGISTRY_KEY = 'securetrading_stpp_actions_direct_multishipping_orders_registry_key';

  protected function _getOrders(Stpp_Data_Response $response) {
    if (Mage::getModel('checkout/session')->getQuote()->getIsMultiShipping()) {
      $orders = Mage::registry(Securetrading_Stpp_Model_Actions_Direct::MULTISHIPPING_ORDERS_REGISTRY_KEY);
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
    return $this->_isErrorCodeZero($response);
  }
    
  protected function _processAuth(Stpp_Data_Response $response) {
    parent::processAuth($response);
    $order = $this->_getOrder($response);

    if ($this->_paymentIsSuccessful($response) || $this->_authShouldEnterPaymentReview($response)) {
      if ($response->getRequest()->has('md')) {
	Mage::getModel('securetrading_stpp/payment_direct')->registerSuccessfulOrderAfterExternalRedirect($order, $this->_getRequestedSettleStatus($response));	
      }
      $order->getPayment()->getMethodInstance()->handleSuccessfulPayment($order, true);
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
}