<?php

class Securetrading_Stpp_DirectController extends Mage_Core_Controller_Front_Action {
  public function returnAction() {
    $paymentMethod = $this->_getPaymentMethod();
    $isMultishipping = Mage::helper('securetrading_stpp')->isMultishippingCheckout();
    if ($isMultishipping) {
      $paymentMethod->setIsMultishipping(true);
    }
    $result = $paymentMethod->run3dAuth();

    $path = $this->_calculatePath($result, $isMultishipping);
    $queryArgs = array('path' => $path);
    $this->_redirect('securetrading/payment/location', array('_query' => $queryArgs));
  }
  
  protected function _getPaymentMethod() {
    $orderIncrementIds = Mage::helper('securetrading_stpp')->getOrderIncrementIdsFromSession();
    $firstOrderIncrementId = array_shift($orderIncrementIds);
    return Mage::getModel('sales/order')->loadByIncrementId($firstOrderIncrementId)->getPayment()->getMethodInstance();
  }
  
  protected function _calculatePath($result, $isMultishipping) {
    $paths = array(
      'success' => array(
	'onepage' => 'checkout/onepage/success',
	'multishipping' => 'checkout/multishipping/success',
      ),
      'failure' => array(
	'onepage' => 'checkout/cart',
	'multishipping' => 'checkout/multishipping/billing'
      )
    );
    $key1 = $result ? 'success' : 'failure';
    $key2 = $isMultishipping ? 'multishipping' : 'onepage';
    return $paths[$key1][$key2];
  }
}