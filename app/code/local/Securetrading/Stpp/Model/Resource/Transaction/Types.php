<?php

class Securetrading_Stpp_Model_Resource_Transaction_Types extends Mage_Core_Model_resource_Db_Abstract {
    const TYPE_AUTH = 'auth';
    const TYPE_THREEDQUERY = 'threedquery';
    const TYPE_RISKDEC = 'riskdec';
    const TYPE_CARDSTORE = 'cardstore';
    const TYPE_TRANSACTIONUPDATE = 'transactionupdate';
    const TYPE_TRANSACTIONQUERY = 'transactionquery';
    const TYPE_ACCOUNTCHECK = 'accountcheck';
    const TYPE_ERROR = 'error';
    
    protected function _construct() {
        $this->_init('securetrading_stpp/transaction_types', 'type_id');
    }
}