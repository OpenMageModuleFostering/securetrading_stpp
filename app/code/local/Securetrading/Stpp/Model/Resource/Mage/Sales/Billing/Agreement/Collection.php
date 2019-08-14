<?php

class Securetrading_Stpp_Model_Resource_Mage_Sales_Billing_Agreement_Collection extends Mage_Sales_Model_Resource_Billing_Agreement_Collection {
  public function joinWithCurrencyTable() {
    $this->getSelect()->joinLeft(
      array('joined_curr' => $this->getTable('securetrading_stpp/billing_agreement_currency')),
      'joined_curr.agreement_id = main_table.agreement_id',
      array('joined_curr.base_currency')
    );
    return $this;
  }

  public function joinWithPaymenttypedescriptionTable() {
    $this->getSelect()->joinLeft(
      array('joined_ptd' => $this->getTable('securetrading_stpp/billing_agreement_paymenttypedescription')),
      'joined_ptd.agreement_id = main_table.agreement_id',
      array('joined_ptd.payment_type_description')
    );
    return $this;
  }
}