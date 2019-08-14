<?php

class Securetrading_Stpp_Model_Payment_Direct extends Securetrading_Stpp_Model_Payment_Direct_Abstract implements Mage_Payment_Model_Billing_Agreement_MethodInterface {
  protected $_code                        = 'securetrading_stpp_direct';
  protected $_formBlockType               = 'securetrading_stpp/payment_direct_form';
  protected $_infoBlockType               = 'securetrading_stpp/payment_direct_info';

  protected $_canCreateBillingAgreement   = true;
  
  protected $_sessionModelType = 'securetrading_stpp/payment_direct_session';

  public function canManageBillingAgreements() {
    $tokenizationMethod = Mage::getModel('securetrading_stpp/payment_tokenization');
    return parent::canManageBillingAgreements() && $tokenizationMethod->canSaveCards($tokenizationMethod->getSavedCardsCollection());
  }

  protected function _get3dAuthData() {
    $payment = $this->getInfoInstance();
    $array = array(
      'paymenttype' => $payment->getCcType(),
      'pan' => $payment->decrypt($payment->getCcNumberEnc()),
      'expirydate' => sprintf('%2s', $payment->getCcExpMonth()) . '/' . $payment->getCcExpYear(),
      'securitycode' => $payment->decrypt($this->getSession()->getSecurityCode()),
      'issuenumber' => $payment->getCcSsIssue(),
      'sitereference' => $this->getConfigData("site_reference"),
    );
    return $array;
  }

  public function assignData($data) {
    $payment = $this->getInfoInstance();
    $payment->setCcType($data->getSecuretradingStppPaymentType());
    $payment->setCcNumberEnc($payment->encrypt($data->getSecuretradingStppCardNumber()));
    $payment->setCcLast4($this->getIntegration()->getCcLast4($data->getSecuretradingStppCardNumber()));
    $payment->setCcExpMonth($data->getSecuretradingStppExpiryDateMonth());
    $payment->setCcExpYear($data->getSecuretradingStppExpiryDateYear());
    $payment->setCcSsStartMonth($data->getSecuretradingStppStartDateMonth());
    $payment->setCcSsStartYear($data->getSecuretradingStppStartDateYear());
    $payment->setCcSsIssue($data->getSecuretradingStppIssueNumber());
    $this->getSession()->setSecurityCode($payment->encrypt($data->getSecuretradingStppSecurityCode())); // Cannot save CC CID due to PCI requirements.
    $this->getSession()->setSaveCardDetails($data->getSecuretradingStppSaveCc());
    return $this;
  }

  public function prepareOrderData(Mage_Sales_Model_Order_Payment $payment, array $orderIncrementIds = array(), $sendEmailConfirmation = true) {
    $data = parent::prepareOrderData($payment, $orderIncrementIds);        
    $data += array(
      'termurl'       => Mage::getUrl('securetrading/direct/return'),
      'paymenttype'   => $payment->getCcType(),
      'pan'           => $payment->decrypt($payment->getCcNumberEnc()),
      'expirydate'    => sprintf('%02s', $payment->getCcExpMonth()) . '/' . $payment->getCcExpYear(),
      'securitycode'  => $payment->decrypt($this->getSession()->getSecurityCode()),
      'issuenumber'   => $payment->getCcSsIssue(),
    );
    if ($payment->getCcSsStartMonth() && $payment->getCcSsStartYear()) {
      $data['startdate'] = sprintf('%02s', $payment->getCcSsStartMonth()) . '/' . $payment->getCcSsStartYear();
    }
    return $data;
  }
  
  public function runCardstore($paymentType, $startMonth, $startYear, $expiryMonth, $expiryYear, $cardNumber, $issueNumber) {
    $data = array(
      'sitereference' => $this->getConfigData('site_reference'),
      'paymenttype' => $paymentType,
      'pan' => $cardNumber,
      'issuenumber' => $issueNumber,
      'expirydate' => sprintf('%02s', $expiryMonth) . '/' . $expiryYear,
    );
    
    if ($startMonth && $startYear) {
      $data['startdate'] = sprintf('%02s', $startMonth) . '/' . $startYear;
    }
    
    $result = $this->getIntegration()->runApiCardstore($data);
    
    if ($result->getIsTransactionSuccessful()) {
      $agreement = Mage::getModel('sales/billing_agreement')->load(Mage::getSingleton('customer/session')->getLastBillingAgreementId());
    }
    else {
      throw new Exception($result->getErrorMessage());
    }
    return $agreement;
  }
  
  public function prepareCardstoreLabel($maskedPan, $paymentType, $expiryDate) {
    $maskedUntilLast4 = Mage::helper('securetrading_stpp')->maskUntilCcLast4($maskedPan);
    return sprintf('%s (%s, %s)', $maskedUntilLast4, $this->getIntegration()->getCardString($paymentType), $expiryDate);
  }

  public function updateBillingAgreementStatus(Mage_Payment_Model_Billing_AgreementAbstract $agreement) {
    return $this;
  }

  public function initBillingAgreementToken(Mage_Payment_Model_Billing_AgreementAbstract $agreement) {
    $agreement->setRedirectUrl(Mage::getUrl('securetrading_stpp/tokenization/new'));
    return $this;
  }

  // Function not used.
  public function getBillingAgreementTokenInfo(Mage_Payment_Model_Billing_AgreementAbstract $agreement) {
    return $this;
  }
  
  // Function not used.
  public function placeBillingAgreement(Mage_Payment_Model_Billing_AgreementAbstract $agreement) {
    return $this;
  }
}