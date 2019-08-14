<?php

class Securetrading_Stpp_RedirectController extends Mage_Core_Controller_Front_Action {
  public function notificationAction() {
    if (!$this->getRequest()->getParam('using_new_strs')) {
      throw new Exception('The latest version of the Secure Trading module does not require merchants to set up their notifications and redirects manually through MyST.  Please disable all notifications and redirects that you have set up.');
    }

    Mage::getModel('securetrading_stpp/payment_redirect')
      ->log(sprintf('In %s.', __METHOD__))
      ->runNotification();
    exit($this->__("Notification complete."));
  }
  
  public function redirectAction() {
    if (!$this->getRequest()->getParam('using_new_strs')) {
      Mage::getModel('securetrading_stpp/payment_redirect')->log('Non-STR redirect fired.');
    }

    Mage::getModel('securetrading_stpp/payment_redirect')
      ->log(sprintf('In %s.', __METHOD__))
      ->runRedirect();
      
    if (Mage::getModel('securetrading_stpp/payment_redirect')->ordersAreSuccessful($this->_getOrderIncrementIds())) {
      $this->_setBillingAgreementToSession();
      Mage::getModel('securetrading_stpp/payment_redirect')->onRedirect($this->_getOrderIncrementIds(), $this->getRequest()->getParam('errorcode'), $this->getRequest()->getParam('paymenttypedescription'));

      if (Mage::getSingleton('checkout/session')->setLoadInactive(true)->getQuote()->getIsMultiShipping()) {
	Mage::getSingleton('checkout/session')->clear();
	Mage::getSingleton('checkout/session')->setDisplaySuccess(true);
	
	Mage::getSingleton('checkout/type_multishipping_state')->setCompleteStep(Mage_Checkout_Model_Type_Multishipping_State::STEP_OVERVIEW);
	Mage::getSingleton('checkout/type_multishipping_state')->setActiveStep(Mage_Checkout_Model_Type_Multishipping_State::STEP_SUCCESS);
	
	$path = 'checkout/multishipping/success';
      }
      else {
	$path = 'checkout/onepage/success';
      }
    }
    else {
      Mage::getSingleton('checkout/session')->addError(sprintf(Mage::helper('securetrading_stpp')->__('There has been a problem with the payment of your order(s): %s.  Please contact us to confirm the status of your order before attempting to place it again.'), implode(', ', $this->_getOrderIncrementIds())));
      $path = 'checkout/cart';
    }
    
    $queryArgs = array('path' => $path);
    $this->_redirect('securetrading/payment/location', array('_query' => $queryArgs));
  }
    
  protected function _getOrderIncrementIds() {
    $serializedIncrementIds = $this->getRequest()->getParam('order_increment_ids');
    $orderIncrementIds = @unserialize($serializedIncrementIds);
    
    if ($orderIncrementIds === false && $serializedIncrementIds !== serialize(false)) {
      throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('The string "%s" could not be unserialized.'), $serializedIncrementIds));
    }
    
    foreach($orderIncrementIds as $orderIncrementId) { // If responsesitesecurity is not returned the user input is untrusted.  Make sure the order IDs are actually in this customers session.
      if ((is_array(Mage::getSingleton('core/session')->getOrderIds()) && !in_array($orderIncrementId,Mage::getSingleton('core/session')->getOrderIds())) && $orderIncrementId !== Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
	throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('The order increment ID "%s" was not in the session.'), $orderIncrementId));
      }
    }
    return $orderIncrementIds;
  }

  protected function _setBillingAgreementToSession() {
    $orderIncrementIds = $this->_getOrderIncrementIds();
    $firstOrderId = Mage::getModel('sales/order')->loadByIncrementId(array_shift($orderIncrementIds))->getId();
    $order = Mage::getModel('securetrading_stpp/payment_tokenization')->getOrderAndBillingAgreementData($firstOrderId);
    if ($order->getAgreementId()) {
      Mage::getSingleton('checkout/session')->setLastBillingAgreementId($order->getAgreementId());
    }
    else {
      Mage::getSingleton('checkout/session')->unsLastBillingAgreementId();
    }
  }
}