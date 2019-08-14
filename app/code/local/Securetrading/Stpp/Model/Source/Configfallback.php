<?php

class Securetrading_Stpp_Model_Source_Configfallback {
  public function toOptionArray() {
    $array = array();
    $paymentMethods = array(
      'Secure Trading Payment Pages' => Mage::getModel('securetrading_stpp/payment_redirect'),
      'Secure Trading API' => Mage::getModel('securetrading_stpp/payment_direct'),
    );
    foreach($paymentMethods as $name => $paymentMethod) {
      $array[] = array(
	'value' => $paymentMethod->getCode(),
	'label' => $name,
      );
    }
    return $array;
  }
}