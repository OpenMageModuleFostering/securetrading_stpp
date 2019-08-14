<?php

class Securetrading_Stpp_Model_Actions_Redirect extends Securetrading_Stpp_Model_Actions_Abstract implements Stpp_PaymentPages_ActionsInterface {
    protected $_updates = array();
    
    protected function _addUpdate(Stpp_Data_Response $response, $update) {
    	$this->_updates[$this->_getOrder($response)->getId()][] = $update;
    	return $this;
    }
    
    protected function _hasUpdates(Mage_Sales_Model_Order $order) {
    	$orderId = $order->getId();
    	return array_key_exists($orderId, $this->_updates) && !empty($this->_updates[$orderId]);
    }
    
    protected function _getUpdates(Mage_Sales_Model_Order $order) {
    	if ($this->_hasUpdates($order)) {
    		return $this->_updates[$order->getId()];
    	}
    	return array();
    }
    
    protected function _getOrderIncrementIds(Stpp_Data_response $response) {
    	$orderIncrementIdString = $response->get('order_increment_ids', '');
    	
    	if (!is_string($orderIncrementIdString)) {
    		throw new Stpp_Exception(Mage::helper('securetrading_stpp')->__('The order increment IDs are not a string.'));
    	}
    	
    	$orderIncrementIds = @unserialize($orderIncrementIdString);
    	
    	if (!is_array($orderIncrementIds) || empty($orderIncrementIds)) {
    		throw new Stpp_Exception(Mage::helper('securetrading_stpp')->__('Invalid order increment IDs.'));
    	}
    	return $orderIncrementIds;
    }
    
    protected function _authShouldEnterPaymentReviewAndBeDenied(Stpp_Data_Response $response) {
    	return parent::_authShouldEnterPaymentReviewAndBeDenied($response) && $this->_getOrder($response)->getPayment()->getMethodInstance()->getConfigData('ppg_cancel_60107');
    }
    
    public function processAuth(Stpp_Data_Response $response) {
      $transaction = Mage::getModel('core_resource/transaction');	  
      $firstOrder = true;
      foreach($this->_getOrderIncrementIds($response) as $orderIncrementId) {
	$this->setOrder(Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId));
	$this->_processAuth($response, $firstOrder);
	$firstOrder = false;
      }

      if ($this->_paymentIsSuccessful($response) && $response->get('savecc')) {
	$this->_createBillingAgreement($response);
      }
      return $this->_isErrorCodeZero($response);
    }

    protected function _handleSofortPayment(Stpp_Data_Response $response) {
      $this->_setCoreTransaction($response, false);
      $order = $this->_getOrder($response);      
      $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_SOFORT);
      $order->save();
    }

    protected function _createBillingAgreement(Stpp_Data_Response $response) {
      $payment = $this->_getOrder($response)->getPayment();
      Mage::helper('securetrading_stpp')->addBillingAgreement(
	$payment->getMethodInstance(),
	$payment->getOrder()->getCustomerId(),
	$this->_createSavedCcLabel($payment, $response->get('maskedpan'), $response->get('paymenttypedescription'), $response->get('expirydate'), $response->get('currencyiso3a')),
	$response->get('transactionreference'),
	$response->get('paymenttypedescription'),
	$response->get('currencyiso3a'),
	$this->_getOrderIncrementIds($response),
	$payment->getOrder()->getStoreId()
     );
    }
    
    public function process3dQuery(Stpp_Data_Response $response) {
    	foreach($this->_getOrderIncrementIds($response) as $orderIncrementId) {
    		$this->setOrder(Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId));
    		parent::process3dQuery($response);
    	}
    	return $this->_isErrorCodeZero($response);
    }
    
    public function processRiskDecision(Stpp_Data_Response $response) {
    	foreach($this->_getOrderIncrementIds($response) as $orderIncrementId) {
    		$this->setOrder(Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId));
    		parent::processRiskDecision($response);
    	}
    	return $this->_isErrorCodeZero($response);
    }
    
    public function processTransactionUpdate(Stpp_Data_Response $response) {
    	foreach($this->_getOrderIncrementIds($response) as $orderIncrementId) {
    		$this->setOrder(Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId));
    		parent::processTransactionUpdate($response);
    	}
    	return $this->_isErrorCodeZero($response);
    }
    
    public function processRefund(Stpp_Data_Response $response) {
    	foreach($this->_getOrderIncrementIds($response) as $orderIncrementId) {
    		$this->setOrder(Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId));
    		parent::processRefund($response);
    	}
    	return $this->_isErrorCodeZero($response);
    }
    
    public function processAccountCheck(Stpp_Data_Response $response) {
    	foreach($this->_getOrderIncrementIds($response) as $orderIncrementId) {
    		$this->setOrder(Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId));
    		parent::processAccountCheck($response);
    	}
    	return $this->_isErrorCodeZero($response);
    }
    
    public function _processAuth(Stpp_Data_Response $response, $firstOrder) {
        $order = $this->_getOrder($response);
        $payment = $order->getPayment();
        
	if (!in_array($order->getStatus(), array(Securetrading_Stpp_Model_Payment_Redirect::STATUS_PENDING_PPAGES, Securetrading_Stpp_Model_Payment_Redirect::STATUS_PENDING_SOFORT))) {
        	throw new Stpp_Exception(sprintf(Mage::helper('securetrading_stpp')->__('The order status for order "%s" was not pending payment pages or pending sofort.'), $order->getIncrementId()));
        }
        
        parent::processAuth($response);
        
        $payment->setCcType($response->get('paymenttypedescription'));
        $payment->setCcLast4($payment->getMethodInstance()->getIntegration()->getCcLast4($response->get('maskedpan')));
        $payment->save();
        
        $this->_updateOrder($response, $firstOrder);
        
        if ($this->_paymentIsSuccessful($response) || $this->_authShouldEnterPaymentReview($response)) {
        	$payment = $order->getPayment();
	    	Mage::helper('securetrading_stpp')->registerSuccessfulOrderAfterExternalRedirect($order, $this->_getRequestedSettleStatus($response));
	    	$emailConfirmation = $response->get('accounttypedescription') === 'MOTO' ? (bool) $response->get('send_confirmation') : true;
		$this->_handleSuccessfulOrder($order, $emailConfirmation);
        }
    }
    
    protected function _updateOrder(Stpp_Data_Response $response, $firstOrder) {
    	$order = $this->_getOrder($response);
    	if ($firstOrder) {
    		$addresses = array(
    				'billing' => $order->getBillingAddress(),
    				'customer' => $order->getShippingAddress(),
    		);
    	}
    	else {
    		$addresses = array('billing' => $order->getBillingAddress());
    	}
       
        foreach($addresses as $stKeyPrefix => $address) {
            $this->_updateOneToOneMapping($response, $stKeyPrefix, $address);
            $this->_updateStreet($response, $stKeyPrefix, $address);
            $this->_updateCountry($response, $stKeyPrefix, $address);
            $this->_updateRegion($response, $stKeyPrefix, $address);
            $address->save();
        }
        
        if ($this->_hasUpdates($order)) {
            $message = "Updated the following fields: " . implode(', ', $this->_getUpdates($order));
            $order->addStatusHistoryComment($message);
            $order->save();
            $this->_log($response, $message);
        }
    }
    
    protected function _updateOneToOneMapping(Stpp_Data_Response $response, $stKeyPrefix, $address) {
        $fields = array(
            'town' => 'city',
            'postcode' => 'postcode',
            'email' => 'email',
            'telephone' => 'telephone',
            'prefixname' => 'prefix',
            'firstname' => 'firstname',
            'lastname' => 'lastname',
        );
        
        foreach($fields as $stKeySuffix => $coreKey) {
            $stKey = $stKeyPrefix . $stKeySuffix;
            $value = $response->get($stKey);
            if ($value !== (string) $address->getData($coreKey)) {
                $address->setData($coreKey, $value);
                $this->_addUpdate($response, $stKey);
            }
        }
    }
    
    protected function _updateStreet(Stpp_Data_Response $response, $stKeyPrefix, $address) {
        $street = $address->getStreet();
        
        $streetFields = array(
            'premise' => 0,
            'street' => 1,
        );
        
        foreach($streetFields as $stKeySuffix => $streetKey) {
            $stKey = $stKeyPrefix . $stKeySuffix;
            $value = $response->get($stKey);
            $oldValue = array_key_exists($streetKey, $street) ? $street[$streetKey] : '';
            if ($value !== (string) $oldValue) {
                $street[$streetKey] = $value;
                $this->_addUpdate($response, $stKey);
            }
        }
        $address->setStreet($street);
    }
    
    protected function _updateCountry(Stpp_Data_Response $response, $stKeyPrefix, $address) {
        $stCountryKey = $stKeyPrefix . 'countryiso2a';
        $addressCountryId = $address->getCountryId();
        $countryString = $response->get($stCountryKey);
        $stCountryId = '';
        
        if (in_array(strlen($countryString), array(2, 3))) {
            $stCountryId = Mage::getModel('directory/country')->loadByCode($response->get($stCountryKey))->getId();
        }
        if ($addressCountryId !== (string) $stCountryId) {
            $address->setCountryId($stCountryId);
            $this->_addUpdate($response, $stCountryKey);
        }
    }
    
    protected function _updateRegion(Stpp_Data_Response $response, $stKeyPrefix, $address) {
        $stCountyKey = $stKeyPrefix . 'county'; 
        $stCountryKey = $stKeyPrefix . 'countryiso2a';
        $region = $response->get($stCountryKey) === 'US' ? $address->getRegionCode() : $address->getRegion();

        if ($response->get($stCountyKey) !== (string) $region) {
            $address->setRegionId(null)->setRegion($response->get($stCountyKey));
            $this->_addUpdate($response, $stCountryKey);
        }
    }
    
    public function validateNotification(Stpp_Data_Response $response) {
        $fields = array(
            'accounttypedescription',
            'billingprefixname',
            'billingfirstname',
            'billinglastname',
            'billingpremise',
            'billingstreet',
            'billingtown',
            'billingcounty',
            'billingpostcode',
            'billingcountryiso2a',
            'billingtelephone',
            'billingemail',
	    'currencyiso3a',
            'customerprefixname',
            'customerfirstname',
            'customerlastname',
            'customerpremise',
            'customerstreet',
            'customertown',
            'customercounty',
            'customerpostcode',
            'customercountryiso2a',
            'customertelephone',
            'customeremail',
            'enrolled',
            'errorcode',
	    'expirydate',
            'maskedpan',
            'orderreference',
            'parenttransactionreference',
            'paymenttypedescription',
            'requesttypedescription',
            'securityresponseaddress',
            'securityresponsepostcode',
            'securityresponsesecuritycode',
            'settlestatus',
            'status',
            'transactionreference',
	    // custom fields:
	    //'errordata', // Commented out - ST gateway bug? errordata not present on AUTH 7000 notifications.
	    'errormessage',
	    'order_increment_ids',
	    'send_confirmation',
	    'fraudcontrolshieldstatuscode',
	    'customer_id',
	    'savecc',
        );
        
        foreach($fields as $field) {
            if (!$response->has($field)) {
                throw new Stpp_Exception(sprintf(Mage::helper('securetrading_stpp')->__('The "%s" is required.'), $field));
            }
        }

	// Added - ST gateway bug? errordata not present on AUTH 7000 notifications.
	if (!$response->has('errordata')) {
	  $response->set('errordata', '');
	}
	// End added.
    }
    
    public function checkIsNotificationProcessed($notificationReference) {
        $model = Mage::getModel('securetrading_stpp/payment_redirect_notification')->load($notificationReference, 'notification_reference');
        return (bool) $model->getNotificationId();
    }
    
    public function saveNotificationReference($notificationReference) {
        Mage::getModel('securetrading_stpp/payment_redirect_notification')->setNotificationReference($notificationReference)->save();
    }
    
    public function prepareResponse(Stpp_Data_Response $response) {
        $orderReference = $response->get('orderreference');
        if ($orderReference) {
            $request = Mage::getModel('securetrading_stpp/payment_redirect_request')->loadRequestByOrderIncrementId($orderReference);
            
            if (!$request) {
                throw new Stpp_Exception(Mage::helper('securetrading_stpp')->__('This orderreference does not have a mapped request.'));
            }
            $response->setRequest($request);
        }
    }

    protected function _createSavedCcLabel($payment, $maskedPan, $paymentTypeDescription, $expiryDate, $currencyIso3a) {
      $maskedUntilLast4 = Mage::helper('securetrading_stpp')->maskUntilCcLast4($maskedPan);
      $paymentType = $payment->getMethodInstance()->getIntegration()->getCardString($paymentTypeDescription);
      $label = sprintf('%s (%s, %s, %s)', $maskedUntilLast4, $paymentType, $expiryDate, $currencyIso3a);
      return $label;
    }
}