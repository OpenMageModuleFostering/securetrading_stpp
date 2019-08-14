<?php

class Securetrading_Stpp_Model_Payment_Redirect_Session extends Mage_Core_Model_Session_Abstract {
    public function __construct() {
        $this->init('securetrading_stpp_payment_redirect');
    }
}