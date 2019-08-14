<?php

class Securetrading_Stpp_Block_Payment_Tokenization_Info extends Securetrading_Stpp_Block_Payment_Info_Abstract {
    public function _construct() {
        parent::_construct();
        $this->setTemplate('securetrading/stpp/payment/tokenization/info.phtml');
    }

    public function getCardLabel() {
      $label = '';
      $activeBillingAgreement = $this->getInfo()->getMethodInstance()->getActiveBillingAgreement(true);
      if ($activeBillingAgreement) {
        $label = $activeBillingAgreement->getAgreementLabel();
      }
      return $label;
    }
}