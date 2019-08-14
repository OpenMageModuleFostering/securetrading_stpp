<?php

class Securetrading_Stpp_Model_Payment_Redirect_Request extends Mage_Core_Model_Abstract {
    protected function _construct() {
        $this->_init('securetrading_stpp/payment_redirect_request');
    }
    
    public function setRequest(Stpp_Data_Request $request) {
        $this->setData('request', serialize($request));
        return $this;
    }
    
    public function getRequest() {
        return unserialize($this->getData('request'));
    }
    
    public function addRequest(Mage_Payment_Model_Info $payment, Stpp_Data_Request $request) {
        $orderIncrementId = $payment->getOrder()->getIncrementId();
        $this->load($orderIncrementId, 'order_increment_id')
            ->setOrderIncrementId($orderIncrementId)
            ->setRequest($request)
            ->save();
        return $this;
    }
    
    public function loadRequestByOrderIncrementId($orderIncrementId) {
        return $this->load($orderIncrementId, 'order_increment_id')->getRequest();
    }
}