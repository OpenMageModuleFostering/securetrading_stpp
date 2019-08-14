<?php

class Securetrading_Stpp_RedirectController extends Mage_Core_Controller_Front_Action {
	public function redirectAction() {
		Mage::getModel('securetrading_stpp/payment_redirect')
		->log(sprintf('In %s.', __METHOD__))
		->runRedirect();
		
		if (Mage::getSingleton('checkout/session')->setLoadInactive(true)->getQuote()->getIsMultiShipping()) {
			$path = 'checkout/multishipping/success';
		
			Mage::getSingleton('checkout/session')->clear();
			Mage::getSingleton('checkout/session')->setDisplaySuccess(true);
			 
			Mage::getSingleton('checkout/type_multishipping_state')->setCompleteStep(Mage_Checkout_Model_Type_Multishipping_State::STEP_OVERVIEW);
			Mage::getSingleton('checkout/type_multishipping_state')->setActiveStep(Mage_Checkout_Model_Type_Multishipping_State::STEP_SUCCESS);
		}
		else {
			$path = 'checkout/onepage/success';
		}
		
		$queryArgs = array('path' => $path);
		$this->_redirect('securetrading/payment/location', array('_query' => $queryArgs));
	}
	
    public function notificationAction() {
        $model = Mage::getModel('securetrading_stpp/payment_redirect')
            ->log(sprintf('In %s.', __METHOD__))
            ->runNotification();
        exit($this->__("Notification complete."));
    }
}