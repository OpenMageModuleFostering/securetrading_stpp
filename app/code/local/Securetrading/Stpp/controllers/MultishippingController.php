<?php

require_once(Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'MultishippingController.php');

class Securetrading_Stpp_MultishippingController extends Mage_Checkout_MultishippingController {
	public function overviewPostAction()
	{
		// start st added
		if ($this->_getCheckout()->getQuote()->getPayment()->getMethodInstance()->getCode() !== 'securetrading_stpp_redirect') {
			return parent::overviewPostAction();
		}
		
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
			$this->_getCheckout()->getQuote()
				->setIsActive(true)
				->save();
			$path = Mage::getModel('securetrading_stpp/payment_redirect')->getMultishippingRedirectPath();
			$this->_redirect($path);
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
}