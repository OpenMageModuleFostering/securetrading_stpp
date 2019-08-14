<?php

class Securetrading_Stpp_Model_Payment_Handler_Frontend_Factory extends Varien_Object {
  public function getHandler(Mage_Payment_Model_Method_Abstract $methodInstance) {
    $methodInstance->log(sprintf('In %s.', __METHOD__));
    $data = array(
      'integration' => $this->getIntegration(),
      'method_instance' => $methodInstance,
      'session' => $methodInstance->getSession(),
    );
    if ($methodInstance->getIsMultishipping()) {
      $data['is_first_multishipping'] = $methodInstance->getIsFirstMultishipping();
      $data['payment_place_without_making_api_request'] = $methodInstance->getPaymentPlaceWithoutMakingApiRequest();
      return $this->_createMultishippingHandler($methodInstance, $data);
    }
    return $this->_createOnepageHandler($methodInstance, $data);
  }

  protected function _createMultishippingHandler(Securetrading_Stpp_Model_Payment_Direct_Abstract $methodInstance, array $data) {
    $methodInstance->log(sprintf('In %s.', __METHOD__));
    return Mage::getModel('securetrading_stpp/payment_handler_frontend_multishipping', $data);
  }

  protected function _createOnepageHandler(Securetrading_Stpp_Model_Payment_Direct_Abstract $methodInstance, array $data) {
    $methodInstance->log(sprintf('In %s.', __METHOD__));
    return Mage::getModel('securetrading_stpp/payment_handler_frontend_onepage', $data);
  }
}