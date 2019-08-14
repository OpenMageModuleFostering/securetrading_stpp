<?php

class Securetrading_Stpp_Block_Payment_Direct_Info extends Securetrading_Stpp_Block_Payment_Info_Abstract {
    public function _construct() {
        parent::_construct();
        $this->setTemplate('securetrading/stpp/payment/direct/info.phtml');
    }
}