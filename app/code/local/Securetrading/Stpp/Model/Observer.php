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

		$order = $observer->getEvent()->getOrder();
		$order->getPayment()->getMethodInstance()->setPaymentPlaceWithoutMakingApiRequest(true);
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
			'shield_status_code',
            'enrolled',
            'status',
        );
        
        $payment = $observer->getEvent()->getPayment();
        $transport = $observer->getEvent()->getTransport();
		 
        foreach($data as $key) {   
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
    
    public function onAdminhtmlInitSystemConfig(Varien_Event_Observer $observer) { // The event this observer is attached to is only present in Magento 1.7.0.1+.
    	$oneDimensionalSectionsWithFields = $observer->getConfig()->getNode('sections/securetrading_stpp/groups');
    	$nestedSectionsWithoutFields = $observer->getConfig()->getNode('securetrading_stpp_sections');
    
    	foreach($oneDimensionalSectionsWithFields->children() as $group) {
 			$emptyFieldElement = $nestedSectionsWithoutFields->xpath('.//' . $group->getName());
    
			if (count($emptyFieldElement) !== 1) {
    			continue;
    		}
    		$emptyFieldElement[0]->extend($group);
    	}
    	
    	$observer->getConfig()->getNode('sections')->extend($nestedSectionsWithoutFields);
    	
    	$observer->getConfig()->setNode('sections/payment/groups/securetrading_stpp/sort_order', '0');
    	
    	// Hide config page from menu:
    	$observer->getConfig()->setNode('sections/securetrading_stpp/show_in_default', 0);
    	$observer->getConfig()->setNode('sections/securetrading_stpp/show_in_website', 0);
    	$observer->getConfig()->setNode('sections/securetrading_stpp/show_in_store', 0);
    }
}