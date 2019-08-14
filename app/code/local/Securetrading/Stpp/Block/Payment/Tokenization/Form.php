<?php

class Securetrading_Stpp_Block_Payment_Tokenization_Form extends Mage_Payment_Block_Form {
    public function _construct() {
        $this->setTemplate('securetrading/stpp/payment/tokenization/form.phtml');
        parent::_construct();
    }
    
    public function getSavedCards() {
      return $this->getMethod()->getSavedCardsCollection();
    }

    public function getCardNumberLabel() {
        return $this->_getIntegration()->getCardNumberLabel();
    }

    protected function _getIntegration() {
        return $this->getMethod()->getIntegration();
    }
    
    public function getDescription() {
        return $this->getMethod()->getConfigData('description');
    }
    
    public function getAcceptedCards() {
        $method = $this->getMethod();
        return $method->getIntegration()->getAcceptedCards($method->getConfigData('use_3d_secure'), $method->getConfigData('accepted_cards'));
    }

    public function getAgreementLabel(Mage_Sales_Model_Billing_Agreement $agreement) {
      $agreementLabel = $agreement->getAgreementLabel();
      return preg_replace('/^([^(]+\([^,]+,[^,]+),([^,]+)(\))$/ ', '$1$3', $agreementLabel);
    }
}