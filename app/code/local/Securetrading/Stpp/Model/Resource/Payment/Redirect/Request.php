<?php

class Securetrading_Stpp_Model_Resource_Payment_Redirect_Request extends Mage_Core_Model_Resource_Db_Abstract {
    protected function _construct() {
        $this->_init('securetrading_stpp/requests', 'request_id');
    }
}