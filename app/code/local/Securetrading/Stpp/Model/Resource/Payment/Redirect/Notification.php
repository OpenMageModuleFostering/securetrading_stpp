<?php

class Securetrading_Stpp_Model_Resource_Payment_Redirect_Notification extends Mage_Core_Model_Resource_Db_Abstract {
    protected function _construct() {
        $this->_init('securetrading_stpp/notifications', 'notification_id');
    }
}