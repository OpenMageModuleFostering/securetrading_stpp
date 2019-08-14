<?php

class Securetrading_Stpp_Model_Payment_Tokenization extends Securetrading_Stpp_Model_Payment_Direct_Abstract {
  protected $_code                        = 'securetrading_stpp_tokenization';
  protected $_formBlockType               = 'securetrading_stpp/payment_tokenization_form';
  protected $_infoBlockType               = 'securetrading_stpp/payment_tokenization_info';
  
  protected $_canCreateBillingAgreement   = false;
  
  protected $_sessionModelType = 'securetrading_stpp/payment_tokenization_session';

  protected function _get3dAuthData() {
    return array();
  }
  
  public function isAvailable($quote = null) {
    return parent::isAvailable($quote) && count($this->getSavedCardsCollection());
  }

  public function assignData($data) {
    $this->getSession()->setReferenceId($data->getSecuretradingStppReferenceId());
    $this->getSession()->setIsAdmin(Mage::app()->getStore()->isAdmin());
    return $this;
  }

  public function prepareOrderData(Mage_Sales_Model_Order_Payment $payment, array $orderIncrementIds = array(), $sendEmailConfirmation = true) {
    if (empty($orderIncrementIds)) {
      $orderIncrementIds = array($payment->getOrder()->getId() => $payment->getOrder()->getIncrementId());
    }
    $data = parent::prepareOrderData($payment, $orderIncrementIds);
    $data += array(
      'parenttransactionreference' => $this->_getParentTransactionReference(),
      'termurl'       => Mage::getUrl('securetrading/direct/return'),
    );
    return $data;
  }
 
  public function getSavedCardsCollectionUsingParams($customerId, $storeId, $baseCurrencyCode, $paymentTypeDescriptions) {
    if (!is_array($paymentTypeDescriptions)) {
      $paymentTypeDescriptions = array();
    }
    return Mage::getResourceModel('securetrading_stpp/mage_sales_billing_agreement_collection')
      ->joinWithCurrencyTable()
      ->joinWithPaymenttypedescriptionTable()
      ->addFieldToFilter('customer_id', array('eq' => $customerId))
      ->addFieldToFilter('store_id', $storeId)
      ->addFieldToFilter('base_currency', array(array('eq' => $baseCurrencyCode), array('null' => 'dummyvalue')))
      ->addFieldToFilter('status', array('eq' => Mage_Sales_Model_Billing_Agreement::STATUS_ACTIVE))
      ->addFieldToFilter('payment_type_description', array('in' => $paymentTypeDescriptions));
    ;
  }
  
  public function getSavedCardsCollection() {
    $customerId = $this->_getCustomerId();
    $storeId = $this->_getStoreId();
    $baseCurrencyCode = $this->_getBaseCurrencyCode();
    $paymentTypeDescriptions = $this->getConfigData('accepted_cards');
    return $this->getSavedCardsCollectionUsingParams($customerId, $storeId, $baseCurrencyCode, $paymentTypeDescriptions);
  }
  
  public function getBillingAgreement($referenceId, $graceful = false) {
    $collection = $this->getSavedCardsCollection();
    $collection->addFieldToFilter('reference_id', array('eq' => $referenceId));

    if ($collection->getSize() === 1) {
      $return = $collection->getFirstItem();
    }
    else if ($graceful) {
      $return = false;
    }
    else {
      throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('Invalid reference ID "%s" selected.  Possible fraud attempt.'), $referenceId));
    }
    return $return;
  }

  public function getActiveBillingAgreement($graceful = false) {
    return $this->getBillingAgreement($this->_getReferenceId(), $graceful);
  }
  
  public function getConfigData($field, $storeId = null) {
    $return = parent::getConfigData($field, $storeId);
    if ($return === null && $field !== 'config_fallback') { // prevent infinite recurison
      $fallback = $this->getConfigData('config_fallback', $storeId);
      $methodInstance = Mage::helper('payment')->getMethodInstance($fallback); //  $fallback must be a payment method code
      if ($methodInstance) {
	$return = $methodInstance->getConfigData($field, $storeId);
      }
    }
    return $return;
  }

  public function getOrderAndBillingAgreementData($orderId) {
    $collection = Mage::getResourceModel('sales/order_collection');
    $collection->addFieldToSelect('*')
      ->addFieldToFilter('entity_id', $orderId)
      ->getSelect()->joinLeft(
	array('sbao' => $collection->getTable('sales/billing_agreement_order')),
	'main_table.entity_id = sbao.order_id',
	array('sbao.agreement_id')
			      )
      ;
    
    if ($collection->getSize() !== 1) {
      throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('Unexpected size: %s.'), $collection->getSize()));
    }
    
    $order = $collection->getFirstItem();
    return $order;
  }

  public function canSaveCards($collection) {
    return (bool) ($collection->getSize() < $this->getConfigData('max_saved_cc'));
  }

  protected function _getParentTransactionReference() {
    return $this->getActiveBillingAgreement()->getReferenceId(); // doing this lets us validate the ba belongs to the correct person and is valid.
  }

  protected function _getReferenceId() {
    return $this->getSession()->getReferenceId();
  }

  protected function _isBackendOp() {
    return Mage::registry(Securetrading_Stpp_Model_Payment_Handler_Backend_Factory::REGISTRY_KEY_IS_BACKEND_OP);
  }

  protected function _isAdmin() {
    return Mage::app()->getStore()->isAdmin() || $this->getSession()->getIsAdmin(); // We don't just do Mage::app()->getStore()->isAdmin() since when Mage_Sales_Model_Order::sendNewOrderEmail() starts environment emulation Magento thinks we are in the frontend (since it uses the frontend payment info form).
  }

  protected function _getStoreId() {
    if ($this->_isAdmin()) {
      $storeId = Mage::getSingleton('adminhtml/session_quote')->getStoreId();
    }
    else {
      $storeId = Mage::app()->getStore()->getId();
    }
    return $storeId;
  }

  protected function _getCustomerId() {
    if ($this->_isAdmin()) {
      $customerId = Mage::getSingleton('adminhtml/session_quote')->getCustomerId();
    }
    else {
      $customerId = Mage::getSingleton('customer/session')->getCustomerId();
    }
    return $customerId;
  }

  protected function _getBaseCurrencyCode() {
    if ($this->_isAdmin()) {
      $baseCurrencyCode = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getBaseCurrencyCode();
    }
    else {
      $baseCurrencyCode = Mage::getSingleton('checkout/session')->getQuote()->getBaseCurrencyCode();
    }
    return $baseCurrencyCode;
  }
}