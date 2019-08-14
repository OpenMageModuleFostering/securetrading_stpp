<?php

class Securetrading_Stpp_Model_Resource_Payment_Redirect_Request_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {
    protected function _construct() {
        $this->_init('securetrading_stpp/payment_redirect_request');
    }
}