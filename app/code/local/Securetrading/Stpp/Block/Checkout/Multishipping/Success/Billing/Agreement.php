<?php

class Securetrading_Stpp_Block_Checkout_Multishipping_Success_Billing_Agreement extends Mage_Core_Block_Template {
  protected function _beforeToHtml() {
    $this->_prepareLastBillingAgreement();
    return parent::_beforeToHtml();
  }

  // See Mage_Checkout_Block_Onepage_Success::_prepareLastBillingAgreement().
  protected function _prepareLastBillingAgreement() {
    $agreementId = Mage::getSingleton('checkout/session')->getLastBillingAgreementId();
    $customerId = Mage::getSingleton('customer/session')->getCustomerId();
    if ($agreementId && $customerId) {
      $agreement = Mage::getModel('sales/billing_agreement')->load($agreementId);
      if ($agreement->getId() && $customerId == $agreement->getCustomerId()) {
	$this->addData(
	  array(
	    'agreement_ref_id' => $agreement->getReferenceId(),
	    'agreement_url' => $this->getUrl('sales/billing_agreement/view', array('agreement' => $agreementId)),
	  )
	);
      }
    }
  }
}