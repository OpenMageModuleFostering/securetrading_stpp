<?php

class Securetrading_Stpp_Model_Observer {
	public function onCheckoutSubmitAllAfter(Varien_Event_Observer $observer) {
		$quote = $observer->getEvent()->getQuote();
		$methodInstance = $quote->getPayment()->getMethodInstance();
	
		if ($methodInstance->getIsSecuretradingPaymentMethod()) {
			$methodInstance->log(sprintf('In %s.', __METHOD__));
			
			if ($observer->getEvent()->getOrders()) { // Multishipping checkout.
				$orderIncomplete = true;
				$methodInstance->log('Multishipping checkout.');
			}
			else {
				$orderIncomplete = (bool) $quote->getPayment()->getOrderPlaceRedirectUrl();
				$methodInstance->log(sprintf('One page checkout.  Order incomplete: %s.', $orderIncomplete));
			}
			
			if ($orderIncomplete) {
				$quote->setIsActive(true)->save();
			}
		}
	}
	
	public function onCheckoutTypeMultishippingCreateOrdersSingle(Varien_Event_Observer $observer) {
		if (!$observer->getEvent()->getOrder()->getPayment()->getMethodInstance()->getIsSecuretradingPaymentMethod()) {
			return;
		}
		$observer->getEvent()->getOrder()->setCanSendNewEmailFlag(false)->save();
	}
	
    public function onPaymentInfoBlockPrepareSpecificInformation(Varien_Event_Observer $observer) {
        if (!$observer->getEvent()->getPayment()->getMethodInstance()->getIsSecuretradingPaymentMethod()) {
            return;
        }
        
        $data = array(
            'account_type_description',
            'security_address',
            'security_postcode',
            'security_code',
            'enrolled',
            'status',
        );
        
        $payment = $observer->getEvent()->getPayment();
        
        foreach($data as $key) {
            $transport = $observer->getEvent()->getTransport();
            $value = $payment->getAdditionalInformation($key);
            $transport->setData($key, $value);
        }
        
        $transport->setData('payment_type', $payment->getCcType());
        $transport->setData('cc_last_4', $payment->getCcLast4());
        $transport->setData('expiry_month', $payment->getCcExpMonth());
        $transport->setData('expiry_year', $payment->getCcExpYear());
        $transport->setData('start_month', $payment->getCcSsStartMonth());
        $transport->setData('start_year', $payment->getCcSsStartYear());
        $transport->setData('issue_number', $payment->getCcSsIssue());
        $transport->setData('transaction_reference', $payment->getCcTransId());
    }
}