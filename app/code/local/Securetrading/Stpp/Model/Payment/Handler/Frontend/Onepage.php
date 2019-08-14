<?php

class Securetrading_Stpp_Model_Payment_Handler_Frontend_Onepage extends  Securetrading_Stpp_Model_Payment_Handler_Frontend_Abstract {
  protected function _canInitialize() {
    return true;
  }

  protected function _beforeInitialize(Varien_Object $stateObject) {
    return;
  }

  protected function _canAuthorize(Mage_Sales_Model_Order_Payment $payment) {
    return true;
  }

  protected function _canCapture(Mage_Sales_Model_Order_Payment $payment) {
    return true;
  }

  protected function _beforeRun3dAuth() {
    return;
  }
}