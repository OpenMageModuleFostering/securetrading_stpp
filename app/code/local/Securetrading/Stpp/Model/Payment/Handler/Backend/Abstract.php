<?php

abstract class Securetrading_Stpp_Model_Payment_Handler_Backend_Abstract extends Varien_Object {
  abstract protected function _prepareRefund(Mage_Sales_Model_Order_Payment $payment, $amount);
  
  abstract protected function _prepareToCaptureAuthorized(Mage_Sales_Model_Order_Payment $payment);

  abstract protected function _canAcceptPayment(Mage_Payment_Model_Info $payment);

  abstract protected function _afterAcceptPayment(Mage_Payment_Model_Info $payment);

  abstract protected function _canDenyPayment(Mage_Payment_Model_Info $payment);

  abstract protected function _beforeDenyPayment(Mage_Payment_Model_Info $payment);

  abstract protected function _afterDenyPayment(Mage_Payment_Model_Info $payment);

  protected function _validateKeysExist(array $keys, array $data, $graceful = false) {
    foreach($keys as $key) {
      if (!array_key_exists($key, $data)) {
	if ($graceful) {
	  return false;
	}
	throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('The key "%s" does not exist.'), $key));
      }
    }
    return true;
  }

  public function refund(Mage_Sales_Model_Order_payment $payment, $amount) {
    $data = $this->_prepareRefund($payment, $amount);
    $this->_validateKeysExist(array('partial_refund_already_processed', 'order_base_grand_total', 'base_total_paid', 'base_total_refunded'), $data);
    
    $fullData = array(
      'original_order_total'=> $data['order_base_grand_total'], // Total of original AUTH.
      'order_total_paid'=> $data['base_total_paid'], // How much has been captured from the AUTH (do not consider how much has been refunded via TU or REFUND).
      'order_total_refunded'=> $data['base_total_refunded'], // How much has been refunded via TU or REFUND.
      'amount_to_refund'=> $amount,
      'partial_refund_already_processed' => $data['partial_refund_already_processed'],
      'site_reference' => $payment->getMethodInstance()->getConfigData('site_reference'),
      'transaction_reference' => $this->getTransactionReference(),
      'using_main_amount'=> true,
      'currency_iso_3a'=> $payment->getOrder()->getBaseCurrencyCode(),
      'allow_suspend'=> true,
    );
    $payment->setShouldCloseParentTransaction(false);
    $this->getIntegration()->runApiRefund($payment, $fullData);
  }
  
  public function captureAuthorized(Mage_Sales_Model_Order_Payment $payment, $amount) {
    $data = $this->_prepareToCaptureAuthorized($payment);
    $this->_validateKeysExist(array('order_base_grand_total', 'base_amount_paid', 'base_amount_refunded'), $data);
    
    $amountToCapture = (string) $amount;
    $updates = array('settlestatus' => '0');
    
    if ($amountToCapture !== $data['order_base_grand_total']) {
      $updates['settlemainamount'] = ($data['base_amount_paid'] + $amountToCapture) - $data['base_amount_refunded'];
      $updates['currencyiso3a'] = $payment->getOrder()->getBaseCurrencyCode();
    }
    
    $fullData = $this->_prepareTransactionUpdate($payment, $updates, $payment->getMethodInstance()->getConfigData('site_reference'));
    $this->getIntegration()->runApiTransactionUpdate($payment, $fullData);
  }
  
  public function acceptPayment(Mage_Payment_Model_Info $payment) {
    if ($payment->getOrder()->getState() !== Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
      throw new Mage_Core_Exception('This order is no longer in the payment review state.');
    }
    if ($this->_canAcceptPayment($payment)) {
      $this->_acceptPayment($payment);
      $this->_afterAcceptPayment($payment);
    }
  }
  
  public function denyPayment(Mage_Payment_Model_Info $payment) {
    if ($payment->getOrder()->getState() !== Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
      throw new Mage_Core_Exception('This order is no longer in the payment review state.');
    }
    
    $payment->setShouldCloseParentTransaction(true);
    $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID);

    if ($this->_canDenyPayment($payment)) {
      $this->_beforeDenyPayment($payment);
      $this->_denyPayment($payment);
      $this->_afterDenyPayment($payment);
    }    
  }

  protected function _acceptPayment(Mage_Sales_Model_Order_Payment $payment) {
    $transaction = Mage::getModel('securetrading_stpp/transaction')->loadByTransactionReference($payment->getCcTransId());
    $requestedSettleStatus = $transaction->getRequestData('settlestatus');
    
    if ($requestedSettleStatus === null) { // Will be null if the $transaction was a 3D AUTH (which has MD/PaRes instead).
      if (!$parentTransaction = $transaction->getParentTransaction(true)) {
	throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('Payment "%s" had transaction reference "%s" but had no settle status and no parent transaction reference.'), $payment->getId(), $payment->getCcTransId()));
      }
      if ($parentTransaction->getRequestData('requesttypedescription') !== $this->getIntegration()->getThreedqueryName()) {
	throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('Payment "%s" had transaction reference "%s" but had no settle status and no parent THREEDQUERY.'), $payment->getId(), $payment->getCcTransId()));
      }
      if ($parentTransaction->getRequestData('settlestatus') === null) {
	throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('Payment "%s" had transaction reference "%s" but had no settle status and its parent THREEDQUERY had no settle status.'), $payment->getId(), $payment->getCcTransId()));
      }
      $requestedSettleStatus = $parentTransaction->getRequestData('settlestatus');
    }
    
    $data = null;
    if ($requestedSettleStatus !== 2) { // If the requested settlestatus was 2 there is no need to update the payment (an order should only be put into payment review when the response settlestatus == 2).
      $data = $this->_prepareToUpdateSettleStatus($payment, $requestedSettleStatus, $payment->getMethodInstance()->getConfigData('site_reference'));
    }
    
    if ($data) {
      $this->getIntegration()->runApiTransactionUpdate($payment, $data);
    }
  }

  protected function _denyPayment(Mage_Payment_Model_Info $payment) {
    $data = $this->_prepareToUpdateSettleStatus($payment, '3', $payment->getMethodInstance()->getConfigData('site_reference'));
    $this->getIntegration()->runApiTransactionUpdate($payment, $data);
  }

  protected function _paymentHasSuccessfulRefund(Mage_Sales_Model_Order_Payment $payment) {
    $refundTransaction = $payment->lookupTransaction('', Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND);
    if ($refundTransaction) {
      $securetradingRefundTransaction = Mage::getModel('securetrading_stpp/transaction')->loadByTransactionReference($refundTransaction->getTxnId(), true);
      if ($securetradingRefundTransaction && $securetradingRefundTransaction->getRequestType() === Securetrading_Stpp_Model_Transaction_Types::TYPE_REFUND) {
	return true;
      }
    }
    return false;
  }

  protected function _prepareToUpdateSettleStatus(Mage_Payment_Model_Info $payment, $settleStatus, $siteReference) {
    $updates = array('settlestatus' => $settleStatus);
    $data = $this->_prepareTransactionUpdate($payment, $updates, $siteReference);
    return $data;
  }

  protected function _prepareTransactionUpdate(Mage_Sales_Model_Order_Payment $payment, $updates, $siteReference) {
    $data = array(
      'filter' => array(
	'sitereference' => $siteReference,
	'transactionreference' => $payment->getCcTransId(),
			),
      'updates' => $updates
		  );
    return $data;
  }
}