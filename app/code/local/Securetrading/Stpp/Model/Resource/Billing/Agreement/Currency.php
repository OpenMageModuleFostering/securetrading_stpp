<?php

class Securetrading_Stpp_Model_Resource_Billing_Agreement_Currency extends Mage_Core_Model_Resource_Db_Abstract {
  protected $_isPkAutoIncrement = false;

  public function _construct() {
    $this->_init('securetrading_stpp/billing_agreement_currency', 'agreement_id');
  }
}