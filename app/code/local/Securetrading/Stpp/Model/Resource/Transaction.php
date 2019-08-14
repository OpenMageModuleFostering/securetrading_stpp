<?php

class Securetrading_Stpp_Model_Resource_Transaction  extends Mage_Core_Model_Resource_Db_Abstract {
    protected $_isPkAutoIncrement = false;
    
    protected function _construct() {
        $this->_init('securetrading_stpp/transactions', 'transaction_id');
    }
}