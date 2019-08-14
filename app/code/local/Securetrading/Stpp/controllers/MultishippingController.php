<?php

require_once(Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'MultishippingController.php');

class Securetrading_Stpp_MultishippingController extends Mage_Checkout_MultishippingController {
  protected function _getMultishippingOrders() {
    $orders = array();
    foreach(Mage::getModel('core/session')->getOrderIds() as $orderId => $orderIncrementId) {
      $orders[] = Mage::getModel('sales/order')->load($orderId);
    }
    return $orders;
  }

  protected function _doApiMultishippingPayment() {
    $orders = $this->_getMultishippingOrders();
    Mage::register(Securetrading_Stpp_Model_Actions_Direct::MULTISHIPPING_ORDERS_REGISTRY_KEY, $orders);
    try {
      for ($i = 0; $i < count($orders); $i++) {
	$order = $orders[$i];
	$order->getPayment()->getMethodInstance()->setIsMultishipping(true);
	if ($i === 0) {
	  $order->getPayment()->getMethodInstance()->setIsFirstMultishipping(true);
	}
	$order->setQuote(Mage::getModel('sales/quote')->load($order->getQuoteId()));
      }

      for ($i = 0; $i < count($orders); $i++) { # two loops so is_first_multishipping and is_multishipping set on all orders before this loop begins (first order in this loop makes api request so process*() functions are called)
	$order = $orders[$i];
	$order->getPayment()->place();
	$order->save();
      }
    }
    catch (Exception $e) {
      foreach($orders as $order) {
	$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
      }
      throw $e;
    }
  }
  
  public function overviewPostAction()
  {
    // start st added                    
    if (!Mage::helper('securetrading_stpp')->isSecuretradingPaymentMethod($this->_getCheckout()->getQuote()->getPayment()->getMethodInstance()->getCode())) {
      return parent::overviewPostAction();
    }

    Mage::getSingleton('checkout/session')->unsLastBillingAgreementId();
    
    $versionInfo = Mage::getVersionInfo();
    $validateFormKey = false;
    
    if ($versionInfo['minor'] == 8 && $versionInfo['revision'] >= 1) {
      $validateFormKey = true;
    }
    elseif($versionInfo['minor'] > 8) {
      $validateFormKey = true;
    }
    // end st added
    
    if ($validateFormKey) { // conditional st added
      if (!$this->_validateFormKey()) {
	$this->_forward('backToAddresses');
	return;
      }
    } // conditional st added
    
    if (!$this->_validateMinimumAmount()) {
      return;
    }
    
    try {
      if ($requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds()) {
	$postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
	if ($diff = array_diff($requiredAgreements, $postedAgreements)) {
	  $this->_getCheckoutSession()->addError($this->__('Please agree to all Terms and Conditions before placing the order.'));
	  $this->_redirect('*/*/billing');
	  return;
	}
      }
      
      $payment = $this->getRequest()->getPost('payment');
      $paymentInstance = $this->_getCheckout()->getQuote()->getPayment();
      if (isset($payment['cc_number'])) {
	$paymentInstance->setCcNumber($payment['cc_number']);
      }
      if (isset($payment['cc_cid'])) {
	$paymentInstance->setCcCid($payment['cc_cid']);
      }

      $this->_getCheckout()->createOrders();

      // start st added
      if (Mage::helper('securetrading_stpp')->isSecuretradingApiTypePaymentMethod($this->_getCheckout()->getQuote()->getPayment()->getMethodInstance()->getCode())) {
	$this->_doApiMultishippingPayment();

      }
      
      $this->_getCheckout()->getQuote()
	->setIsActive(true)
	->save();

      if (Mage::helper('securetrading_stpp')->isSecuretradingApiTypePaymentMethod($this->_getCheckout()->getQuote()->getPayment()->getMethodInstance()->getCode())) {
	$orderPlaceRedirectUrl = $this->_getCheckout()->getQuote()->getPayment()->getMethodInstance()->getOrderPlaceRedirectUrl();
	if ($orderPlaceRedirectUrl) {
	  $this->getResponse()->setRedirect($orderPlaceRedirectUrl);
	}
	else {
	  $this->_getState()->setActiveStep(Mage_Checkout_Model_Type_Multishipping_State::STEP_SUCCESS);
	  $this->_getState()->setCompleteStep(Mage_Checkout_Model_Type_Multishipping_State::STEP_OVERVIEW);
	  $this->_getCheckout()->getCheckoutSession()->clear();
	  $this->_getCheckout()->getCheckoutSession()->setDisplaySuccess(true);
	  $this->_redirect('*/*/success');
	}
      }
      else {
	$path = Mage::getModel('securetrading_stpp/payment_redirect')->getMultishippingRedirectPath();
	$this->_redirect($path);
      }
      // end st added
    } catch (Mage_Payment_Model_Info_Exception $e) {
      $message = $e->getMessage();
      if ( !empty($message) ) {
	$this->_getCheckoutSession()->addError($message);
      }
      $this->_redirect('*/*/billing');
    } catch (Mage_Checkout_Exception $e) {
      Mage::helper('checkout')
	->sendPaymentFailedEmail($this->_getCheckout()->getQuote(), $e->getMessage(), 'multi-shipping');
      $this->_getCheckout()->getCheckoutSession()->clear();
      $this->_getCheckoutSession()->addError($e->getMessage());
      $this->_redirect('*/cart');
    }
    catch (Mage_Core_Exception $e) {
      Mage::helper('checkout')
	->sendPaymentFailedEmail($this->_getCheckout()->getQuote(), $e->getMessage(), 'multi-shipping');
      $this->_getCheckoutSession()->addError($e->getMessage());
      $this->_redirect('*/*/billing');
    } catch (Exception $e) {
      Mage::logException($e);
      Mage::helper('checkout')
	->sendPaymentFailedEmail($this->_getCheckout()->getQuote(), $e->getMessage(), 'multi-shipping');
      $this->_getCheckoutSession()->addError($this->__('Order place error.'));
      $this->_redirect('*/*/billing');
    }
  }

  public function successAction() {
    Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
    Mage::getSingleton('checkout/session')->clear();    
    parent::successAction();
  }
}