<?php

class Securetrading_Stpp_Block_Payment_Redirect_Form extends Mage_Payment_Block_Form {
    public function _construct() {
        $this->setTemplate('securetrading/stpp/payment/redirect/form.phtml');
        parent::_construct();
    }
    
    public function getDescription() {
        return Mage::getModel('securetrading_stpp/payment_redirect')->getConfigData('description');
    }
}