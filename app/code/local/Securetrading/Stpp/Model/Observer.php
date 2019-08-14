<?php

class Securetrading_Stpp_Model_Observer {
    public function onCheckoutSubmitAllAfter(Varien_Event_Observer $observer) {
        $quote = $observer->getEvent()->getQuote();
        $methodInstance = $quote->getPayment()->getMethodInstance();
        
        if ($methodInstance->getIsSecuretradingPaymentMethod()) {
            $methodInstance->log(sprintf('In %s.', __METHOD__));
            
            $order = $observer->getEvent()->getOrder();
            
            $collection = $order->getStatusHistoryCollection(true);
            foreach($collection as $c) {
                $c->delete();
            }
            
            $orderIncomplete = (bool) $quote->getPayment()->getOrderPlaceRedirectUrl();
            $methodInstance->log(sprintf('In %s.  Order incomplete: %s.', __METHOD__, $orderIncomplete));
            
            if ($orderIncomplete) {
                $stateObject = Mage::getSingleton('securetrading_stpp/transport');
                $order->setState($stateObject->getState(), $stateObject->getStatus(), $stateObject->getMessage());
                $order->save();
                
                $quote->setIsActive(true);//note - quote saved after this observer called (in onepagecontroller)
            }
            else {
                Mage::getModel('securetrading_stpp/payment_direct')->handleSuccessfulPayment($order);
            }
        }
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