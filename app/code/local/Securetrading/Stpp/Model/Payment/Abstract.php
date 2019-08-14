<?php

abstract class Securetrading_Stpp_Model_Payment_Abstract extends Mage_Payment_Model_Method_Abstract {
    const STATUS_AUTHORIZED  = 'authorized';
    const STATUS_SUSPENDED  = 'suspended';
    const STATUS_PENDING_PPAGES = 'pending_ppages';
    const STATUS_PENDING_3DSECURE = 'pending_3dsecure';
    
    final public function getIsSecuretradingPaymentMethod() {
        return true;
    }
    
    public function getIntegration() {
        return Mage::getModel('securetrading_stpp/integration', array('payment_method' => $this));
    }
    
    public function log($message) {
        try {
            $order = $this->getInfoInstance()->getOrder();
        }
        catch (Exception $e) {
            // Do nothing here intentionally.
        }
        $sidToken = md5(Mage::getModel('core/session')->getSessionId());
        $orderIncrementId = isset($order) && $order ? $order->getIncrementId() : 'N/A';
        $message = $this->_code . ' - ' .$orderIncrementId . ' - ' . $sidToken . ' - ' . $message;
        $this->getIntegration()->getDebugLog()->log($message);
        return $this;
    }
    
    public function prepareOrderData(Mage_Sales_Model_Order_Payment $payment, array $orderIncrementIds, $sendEmailConfirmation = true) {
        $order = $payment->getOrder();
        $billingAddress = $order->getBillingAddress();
        $billingCounty = $billingAddress->getCountry() == 'US' ? $billingAddress->getRegionCode() : $billingAddress->getRegion();
        $billingTelephoneNumber = $billingAddress->getTelephone();
        $billingTelephoneType = !empty($billingTelephoneNumber) ? 'H' : '';
        $customerDobFull = $order->getCustomerDob();
        $customerDobArray = explode(' ', $customerDobFull);
        $customerDob = $customerDobArray[0];
        
        $baseTotalDue = 0;
        
        foreach($orderIncrementIds as $orderIncrementId) {
        	$baseTotalDue += Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId)->getBaseTotalDue();
        }
        
        $data = array(
            'sitereference'             => $this->getConfigData("site_reference"),
            'currencyiso3a'             => $order->getBaseCurrencyCode(),
            'mainamount'				=> $baseTotalDue,
            
            'billingprefixname'         => $billingAddress->getPrefix(),
            'billingfirstname'          => $billingAddress->getFirstname(),
            'billingmiddlename'         => $billingAddress->getMiddlename(),
            'billinglastname'           => $billingAddress->getLastname(),
            'billingsuffixname'         => $billingAddress->getSuffix(),
            'billingemail'              => $billingAddress->getEmail(),
            'billingtelephone'          => $billingTelephoneNumber,
            'billingtelephonetype'      => $billingTelephoneType,
            'billingpremise'            => $billingAddress->getStreet(1),
            'billingstreet'             => $billingAddress->getStreet(2),
            'billingtown'               => $billingAddress->getCity(),
            'billingcounty'             => $billingCounty,
            'billingpostcode'           => $billingAddress->getPostcode(),
            'billingcountryiso2a'       => $billingAddress->getCountry(),
            'billingdob'                => $customerDob,
            
            'settleduedate'             => $this->getConfigData('settle_due_date'),
            'settlestatus'              => $this->getConfigData('payment_action') === Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE ? 2 : $this->getConfigData('settle_status'),
            'orderreference'            => $order->getIncrementId(),
        );
        
        if ($order->getShippingMethod()) {
            $customerAddress = $order->getShippingAddress();
            $customerCounty = $customerAddress->getCountry() == 'US' ? $customerAddress->getRegionCode() : $customerAddress->getRegion();
            $customerTelephoneNumber = $customerAddress->getTelephone();
            $customerTelephoneType = !empty($customerTelephoneNumber) ? 'H' : '';
            
            $data += array(
                'customerprefixname'        => $customerAddress->getPrefix(),
                'customerfirstname'         => $customerAddress->getFirstname(),
                'customermiddlename'        => $customerAddress->getMiddlename(),
                'customerlastname'          => $customerAddress->getLastname(),
                'customersuffixname'        => $customerAddress->getSuffix(),
                'customeremail'             => $customerAddress->getEmail(),
                'customertelephone'         => $customerTelephoneNumber,
                'customertelephonetype'     => $customerTelephoneType,
                'customerpremise'           => $customerAddress->getStreet(1),
                'customerstreet'            => $customerAddress->getStreet(2),
                'customertown'              => $customerAddress->getCity(),
                'customercounty'            => $customerCounty,
                'customerpostcode'          => $customerAddress->getPostcode(),
                'customercountryiso2a'      => $customerAddress->getCountry(),
            );
        }
        return $data;
    }
    
    public function registerSuccessfulOrderAfterExternalRedirect(Mage_Sales_Model_Order $order, $requestedSettleStatus) {
        $this->log(sprintf('In %s.', __METHOD__));

        $amount = $order->getPayment()->getBaseAmountOrdered();
        
        if (in_array($requestedSettleStatus, array('0', '1', '100'))) {
        	$order->getPayment()->registerCaptureNotification($amount, true);
        }
        elseif($requestedSettleStatus === '2') {
        	$order->getPayment()->registerAuthorizationNotification($amount);
        }
        else {
        	throw new Exception(sprintf('Invalid settle status: %s', $requestedSettleStatus));
        }
        $order->save();
    }
    
    public function handleSuccessfulPayment(Mage_Sales_Model_Order $order, $emailConfirmation) {
    	$this->log(sprintf('In %s.', __METHOD__));
    	$quote = Mage::getModel('sales/quote')->loadByIdWithoutStore($order->getQuoteId());
    	
    	if ($quote->getIsActive()) {
    		$quote->setIsActive(false)->save();
    	}
		
		if ($emailConfirmation) {
           $order->sendNewOrderEmail()->save(); // Send last - even if notif times out order status updated etc.  and payment information updated.
        }
    }
}