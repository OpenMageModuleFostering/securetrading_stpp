<?php

class Securetrading_Stpp_Block_Payment_Redirect_Form extends Mage_Payment_Block_Form {
    public function _construct() {
        $this->setTemplate('securetrading/stpp/payment/redirect/form.phtml');
        parent::_construct();
    }
    
    protected function _getIntegration() {
        return $this->getMethod()->getIntegration();
    }

    public function getDescription() {
        return Mage::getModel('securetrading_stpp/payment_redirect')->getConfigData('description');
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

    protected function _canSaveCards() {
      $tokenizationMethod = Mage::getModel('securetrading_stpp/payment_tokenization');
      $collection = $tokenizationMethod->getSavedCardsCollection();
      return $tokenizationMethod->canSaveCards($collection);
    }
    
    public function getUseTokenization() {
      $customerExists = $this->getMethod()->getInfoInstance()->getQuote()->getCustomerId();
      return $this->getMethod()->getConfigData('use_tokenization') && $this->_canSaveCards() && $customerExists;
    }

    public function getSkipChoicePage() {
      return $this->getMethod()->getConfigData('ppg_version') === '2' && $this->getMethod()->getConfigData('skip_choice_page');
    }

    public function getShowPaymentTypeMultiselect() {
      return $this->getMethod()->getConfigData('show_paymenttype_on_magento');
    }

    public function getAcceptedCards() {
      $method = $this->getMethod();
      return $method->getIntegration()->getAcceptedCards(true, $method->getConfigData('accepted_cards'));
    }

    public function getCardTypeLabel() {
      return $this->_getIntegration()->getCardTypeLabel();
    }
}