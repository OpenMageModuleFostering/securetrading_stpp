<?php

class Securetrading_Stpp_Securetrading_PasswordController extends Mage_Adminhtml_Controller_Action {
  protected $_publicActions = array(
    'generate',
  );

  public function generateAction() {
    $password = Mage::helper('securetrading_stpp')->generatePassword();
    $this->getResponse()->setBody($password);
  }
}
    