<?php

class Securetrading_Stpp_Model_Actions_Direct extends Securetrading_Stpp_Model_Actions_Abstract implements Stpp_Api_ActionsInterface {
	public function processAuth(Stpp_Data_Response $response) {
		parent::processAuth($response);
		$order = $this->_getOrder($response);
		
		if ($this->_paymentIsSuccessful($response) || $this->_authShouldEnterPaymentReview($response)) {
			if ($response->getRequest()->has('md')) {//TODO - should we check pending 3ds here too?
				Mage::getModel('securetrading_stpp/payment_direct')->registerSuccessfulOrderAfterExternalRedirect($order, $this->_getRequestedSettleStatus($response));
			}
			$order->getPayment()->getMethodInstance()->handleSuccessfulPayment($order, true);
		}
		return $this->_isErrorCodeZero($response);
	}
}