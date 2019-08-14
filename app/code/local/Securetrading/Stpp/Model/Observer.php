<?php

class Securetrading_Stpp_Model_Observer {
	public function onCheckoutSubmitAllAfter(Varien_Event_Observer $observer) {
		$quote = $observer->getEvent()->getQuote();
		$methodInstance = $quote->getPayment()->getMethodInstance();
	
		if ($methodInstance->getIsSecuretradingPaymentMethod()) {
			$methodInstance->log(sprintf('In %s.', __METHOD__));
	
			$orders = $observer->getEvent()->getOrders();
			
			if ($orders) {
				$methodInstance->log('Multishipping checkout.');
				$this->_onCheckoutSubmitAllAfter_Multishipping($orders, $quote);
			}
			else {
				$orderIncomplete = (bool) $quote->getPayment()->getOrderPlaceRedirectUrl();
				$methodInstance->log(sprintf('One page checkout.  Order incomplete: %s.', __METHOD__, $orderIncomplete));
				$this->_onCheckoutSubmitAllAfter_Onepage($observer->getEvent()->getOrder(), $quote, $orderIncomplete);
			}
		}
	}
    
    protected function _onCheckoutSubmitAllAfter_orderLoop(Mage_Sales_Model_Order $order, Mage_Sales_Model_Quote $quote, $orderIncomplete = false) {
    	$collection = $order->getStatusHistoryCollection(true);
    	foreach($collection as $c) {
    		$c->delete();
    	}
    	
    	if ($orderIncomplete) {
    		$stateObject = Mage::getSingleton('securetrading_stpp/transport');
    		$order->setState($stateObject->getState(), $stateObject->getStatus(), $stateObject->getMessage());
    		$order->save();
    		
    		$quote->setIsActive(true)->save();
    	}
    	else {
    		Mage::getModel('securetrading_stpp/payment_direct')->handleSuccessfulPayment($order);
    	}
    }
    
    protected function _onCheckoutSubmitAllAfter_Multishipping($orders, Mage_Sales_Model_Quote $quote) {
    	foreach($orders as $order) {
    		$this->_onCheckoutSubmitAllAfter_orderLoop($order, $quote, true);
    	}
    }
    
    protected function _onCheckoutSubmitAllAfter_Onepage(Mage_Sales_Model_Order $order, Mage_Sales_Model_Quote $quote, $orderIncomplete) {
    		$this->_onCheckoutSubmitAllAfter_orderLoop($order, $quote, $orderIncomplete);
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
	
	public function onCheckoutTypeMultishippingCreateOrdersSingle(Varien_Event_Observer $observer) {
      if (!$observer->getEvent()->getOrder()->getPayment()->getMethodInstance()->getIsSecuretradingPaymentMethod()) {
        return;
      }
      $observer->getEvent()->getOrder()->setCanSendNewEmailFlag(false)->save();
    }
}