<?php

class Securetrading_Stpp_Block_Payment_Direct_Tokenization_Form extends Securetrading_Stpp_Block_Payment_Direct_Form {
  public function _construct() {
    parent::_construct();
    $this->setData('method', Mage::getModel('securetrading_stpp/payment_direct'));
    $this->setTemplate('securetrading/stpp/payment/direct/tokenization/form.phtml');
  }

  public function getTokenizationPostUrl() {
    return Mage::getUrl('securetrading_stpp/tokenization/newPost');
  }

  public function getCardstoreDescription() {
    return $this->__('Please enter your credit/debit card details and click the "Submit" button to safely store your credit/debit card details with us.  This will let you quickly and easily make future purchases with us.');
  }
}