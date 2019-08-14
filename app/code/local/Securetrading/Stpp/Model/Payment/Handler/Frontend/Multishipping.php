<?php

class Securetrading_Stpp_Model_Payment_Handler_Frontend_Multishipping extends  Securetrading_Stpp_Model_Payment_Handler_Frontend_Abstract {
  const MULTISHIPPING_STATE_OBJECT_REGISTRY_KEY = 'securetrading_stpp_payment_direct_multishipping_state_object_registry_key';

  protected function _canInitialize() {
    if ($this->getPaymentPlaceWithoutMakingApiRequest()) {
      return false;
    }
    return true;
  }

  protected function _beforeInitialize(Varien_Object $stateObject) {
    if ($this->getIsFirstMultishipping()) {
      $this->_setFirstMultishippingStateObject($stateObject);
    }
    else {
      $stateObject->setData($this->_getFirstMultishippingStateObject()->getData());
    }
  }

  protected function _canAuthorize(Mage_Sales_Model_Order_Payment $payment) {
    if ($this->getIsFirstMultishipping()) {
      return true;
    }
    if (Mage::registry('stpp_test_key')) { //TODO - ugly fix.  improve.
      $payment->setIsTransactionPending(true);
    }
    return false;
  }

  protected function _canCapture(Mage_Sales_Model_Order_Payment $payment) {
    if ($this->getIsFirstMultishipping()) {
      return true;
    }
    if (Mage::registry('stpp_test_key')) { //TODO - ugly fix.  improve.
      $payment->setIsTransactionPending(true);
    }
    return false;
  }

  protected function _beforeRun3dAuth() {
    $orderIncrementIds = Mage::getModel('core/session')->getOrderIds();
    $orders = array();
    foreach($orderIncrementIds as $orderId => $orderIncrementId) {
      $orders[] = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
    }
    Mage::register(Securetrading_Stpp_Model_Actions_Direct::MULTISHIPPING_ORDERS_REGISTRY_KEY, $orders);
  }
  
  protected function _getFirstMultishippingStateObject() {
    return Mage::registry(Securetrading_Stpp_Model_Payment_Handler_Frontend_Multishipping::MULTISHIPPING_STATE_OBJECT_REGISTRY_KEY);
  }

  protected function _setFirstMultishippingStateObject(Varien_Object $stateObject) {
    Mage::register(Securetrading_Stpp_Model_Payment_Handler_Frontend_Multishipping::MULTISHIPPING_STATE_OBJECT_REGISTRY_KEY, $stateObject);
    return $this;
  }
}