<?php

class Securetrading_Stpp_Block_Payment_Direct_Form extends Mage_Payment_Block_Form {
    public function _construct() {
        $this->setTemplate('securetrading/stpp/payment/direct/form.phtml');
        parent::_construct();
    }

    protected function _getIntegration() {
        return $this->getMethod()->getIntegration();
    }
    
    public function getDescription() {
        return $this->getMethod()->getConfigData('description');
    }

    public function getSaveCcDetailsLabel() {
      $question = $this->getMethod()->getConfigData('save_cc_question');
      if (empty($question)) {
	$question = $this->_getIntegration()->getSaveCcDetailsLabel();
      }
      return $question;
    }

    public function getSaveCcDetailsDescription() {
      return $this->_getIntegration()->getSaveCcDetailsDescription();
    }
    
    public function getAcceptedCards() {
        $method = $this->getMethod();
        return $method->getIntegration()->getAcceptedCards($method->getConfigData('use_3d_secure'), $method->getConfigData('accepted_cards'));
    }
    
    public function getMonths() {
        return $this->_getIntegration()->getMonths();
    }
    
    public function getStartYears() {
        return $this->_getIntegration()->getStartYears();
    }
    
    public function getExpiryYears() {
        return $this->_getIntegration()->getExpiryYears();
    }
    
    public function getCardTypeLabel() {
        return $this->_getIntegration()->getCardTypeLabel();
    }
    
    public function getCardTypeDescription() {
        return $this->_getIntegration()->getCardTypeDescription();
    }
    
    public function getCardNumberLabel() {
        return $this->_getIntegration()->getCardNumberLabel();
    }
    
    public function getCardNumberDescription() {
        return $this->_getIntegration()->getCardNumberDescription();
    }
    
    public function getCardStartDateLabel() {
        return $this->_getIntegration()->getCardStartDateLabel();
    }
    
    public function getCardStartDateDescription() {
        return $this->_getIntegration()->getCardStartDateDescription();
    }
    
    public function getCardExpiryDateLabel() {
        return $this->_getIntegration()->getCardExpiryDateLabel();
    }
    
    public function getCardExpiryDateDescription() {
        return $this->_getIntegration()->getCardExpiryDateDescription();
    }
    
    public function getCardExpiryMonthLabel() {
        return $this->_getIntegration()->getCardExpiryMonthLabel();
    }
    
    public function getCardExpiryMonthDescription() {
        return $this->_getIntegration()->getCardExpiryMonthDescription();
    }

    public function getCardExpiryYearLabel() {
        return $this->_getIntegration()->getCardExpiryYearLabel();
    }
    
    public function getCardExpiryYearDescription() {
        return $this->_getIntegration()->getCardExpiryYearDescription();
    }
    public function getCardSecurityCodeLabel() {
        return $this->_getIntegration()->getCardSecurityCodeLabel();
    }
    
    public function getCardSecurityCodeDescription() {
        return $this->_getIntegration()->getCardSecurityCodeDescription();
    }
    
    public function getCardIssueNumberLabel() {
        return $this->_getIntegration()->getCardIssueNumberLabel();
    }
    
    public function getCardIssueNumberDescription() {
        return $this->_getIntegration()->getCardIssueNumberDescription();
    }
    
    public function canShowStartDate() {
    	return (bool) $this->getMethod()->getConfigData('show_start_date');
    }
    
    public function canShowIssueNumber() {
    	return (bool) $this->getMethod()->getConfigData('show_issue_number');
    }
    
    protected function _canSaveCards() {
      $tokenizationMethod = Mage::getModel('securetrading_stpp/payment_tokenization');
      $collection = $tokenizationMethod->getSavedCardsCollection();
      return $tokenizationMethod->canSaveCards($collection);
    }

    public function getUseCardStore() {
      $customerExists = $this->getMethod()->getInfoInstance()->getQuote()->getCustomerId();
      return $this->getMethod()->getConfigData('use_card_store') && $this->_canSaveCards() && $customerExists;
    }
}