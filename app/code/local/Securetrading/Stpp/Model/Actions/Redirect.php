<?php

class Securetrading_Stpp_Model_Actions_Redirect extends Securetrading_Stpp_Model_Actions_Abstract implements Stpp_PaymentPages_ActionsInterface {
    protected $_updates = array();
    
    public function processAuth(Stpp_Data_Response $response) {
        parent::processAuth($response);
        if ($response->get('errorcode') === '0') {
            Mage::getModel('securetrading_stpp/payment_redirect')->registerSuccessfulOrderAfterExternalRedirect();
        }
        
        $order = Mage::getModel('sales/order')->loadByIncrementId($response->get('orderreference'));
        $payment = $order->getPayment();
        
        $payment->setCcType($response->get('paymenttypedescription'));
        $payment->setCcLast4($payment->getMethodInstance()->getIntegration()->getCcLast4($response->get('maskedpan')));
        $payment->save();
        
        $this->_updateOrder($response, $order);
        
        return $this->_isErrorCodeZero($response);
    }
    
    protected function _updateOrder(Stpp_Data_Response $response, Mage_Sales_Model_Order $order) {
        $addresses = array(
            'billing' => $order->getBillingAddress(),
            'customer' => $order->getShippingAddress(),
        );
       
        foreach($addresses as $stKeyPrefix => $address) {
            $this->_updateOneToOneMapping($response, $stKeyPrefix, $address);
            $this->_updateStreet($response, $stKeyPrefix, $address);
            $this->_updateCountry($response, $stKeyPrefix, $address);
            $this->_updateRegion($response, $stKeyPrefix, $address);
            $address->save();
        }
        
        if (!empty($this->_updates)) {
            $message = "Updated the following fields: " . implode(', ', $this->_updates);
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
                $this->_updates[] = $stKey;
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
                $this->_updates[] = $stKey;
            }
        }
        $address->setStreet($street);
    }
    
    protected function _updateCountry(Stpp_Data_Response $response, $stKeyPrefix, $address) {
        $stCountryKey = $stKeyPrefix . 'countryiso2a';
        $addressCountryId = $address->getCountryId();
        
        $stCountryId = Mage::getModel('directory/country')->loadByCode($response->get($stCountryKey))->getId();
        if ($addressCountryId !== (string) $stCountryId) {
            $address->setCountryId($stCountryId);
            $this->_updates[] = $stCountryKey;
        }
    }
    
    protected function _updateRegion(Stpp_Data_Response $response, $stKeyPrefix, $address) {
        $stCountyKey = $stKeyPrefix . 'county'; 
        $stCountryKey = $stKeyPrefix . 'countryiso2a';
        $region = $response->get($stCountryKey) === 'US' ? $address->getRegionCode() : $address->getRegion();

        if ($response->get($stCountyKey) !== (string) $region) {
            $address->setRegionId(null)->setRegion($response->get($stCountyKey));
            $this->_updates[] = $stCountyKey;
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
            'errormessage',
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
        );
        
        foreach($fields as $field) {
            if (!$response->has($field)) {
                throw new Stpp_Exception(sprintf(Mage::helper('securetrading_stpp')->__('The "%s" is required.'), $field));
            }
        }
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
}