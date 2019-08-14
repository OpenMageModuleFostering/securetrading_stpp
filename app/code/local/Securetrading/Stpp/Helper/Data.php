<?php

class Securetrading_Stpp_Helper_Data extends Mage_Core_Helper_Abstract {
  public function getSecuretradingPaymentMethodCodes() {
    return array('securetrading_stpp_redirect', 'securetrading_stpp_direct', 'securetrading_stpp_tokenization');
  }

  public function getSecuretradingApiTypePaymentMethodCodes() {
    return array('securetrading_stpp_direct', 'securetrading_stpp_tokenization');
  }

  public function isSecuretradingPaymentMethod($code) {
    return in_array($code, $this->getSecuretradingPaymentMethodCodes());
  }

  public function isSecuretradingApiTypePaymentMethod($code) {
    return in_array($code, $this->getSecuretradingApiTypePaymentMethodCodes());
  }
  
  public function getCsvData($paymentModels, $filters, $optionalFields, $startDate, $endDate) {
    $csvData = array();
    foreach(Mage::app()->getStores() as $storeId => $store) {
      foreach($paymentModels as $paymentModel) {
	$paymentModel->setStore($storeId)->getIntegration();
	try {
	  $siteReference = $store->getConfig('payment/' . $paymentModel->getCode() . '/site_reference');
	  $csvData[] = $paymentModel->getIntegration()->newTransactionSearch()
	    ->setStartDate($startDate)
	    ->setEndDate($endDate)
	    ->setSiteReferences($siteReference)
	    ->setFilters($filters)
	    ->setOptionalFields($optionalFields)
	    ->getCsvData()
	  ;
	}
	catch (Exception $e) {
	  Mage::logException($e);
	  continue;
	}
      }
    }
    
    $transactionReferences = array();
    $finalCsvData = array();
    foreach($csvData as $postResponse) {
      foreach($postResponse as $oneRecord) {
	if (in_array($oneRecord[0], $transactionReferences)) {
	  continue;
	}
	$transactionReferences[] = $oneRecord[0];
	$finalCsvData[] = $oneRecord;
      }
    }
    return $finalCsvData;
  }

  public function updateOrders(array $orderIds, $cancelOrders = false) {
    foreach($orderIds as $orderId) {
      $order = Mage::getModel('sales/order')->load($orderId);
      $transactions = Mage::getResourceModel('sales/order_payment_transaction_collection')->addOrderIdFilter($order->getId())->addPaymentIdFilter($order->getPayment()->getId())->addFieldToFilter('is_closed', array('neq' => '1'));
      
      foreach($transactions as $transaction) {
	$transaction->setOrderPaymentObject($order->getPayment())->setIsClosed(true)->save();
      }
      if ($cancelOrders) { // ST_#3508
	if ($order->getState() === Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
	  $order->getPayment()->setNotificationResult(true)->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_DENY, false); // Offline review so no TRANSACTIONUPDATE runs.
	}
	$order->cancel();
      }
      $order->save();
    }
  }

  public function extractCcLast4($pan) {
    if (($return = substr($pan, -4)) === false) {
      throw new Exception(sprintf($this->__('Invalid return: "%s".'), $return));
    }
    return $return;
  }

  public function maskUntilCcLast4($pan) {
    if (($strlen = strlen($pan)) === null) {
      throw new Exception(sprintf($this->__('Invalid pan: "%s".'), $pan));
    }
    $maskCount = $strlen - 4;
    $return = str_repeat('#', $maskCount) . $this->extractCcLast4($pan);
    return $return;
  }

  public function isMultishippingCheckout() {
    return Mage::getSingleton('checkout/session')->getQuote()->getIsMultiShipping();
  }

  public function getOrderIncrementIdsFromSession() {
    if ($this->isMultishippingCheckout()) {
      $orderIncrementIds = Mage::getModel('core/session')->getOrderIds();
    }
    else {
      $orderIncrementIds = array(Mage::getModel('checkout/session')->getLastRealOrderId()); //onepage checkout
    }
    return $orderIncrementIds;
  }
  
  public function addBillingAgreement(Mage_Payment_Model_Method_Abstract $paymentMethod, $customerId, $agreementLabel, $transactionReference, $paymentTypeDescription, $currencyIso3a = null, $orderIncrementIds = array(), $storeId = null) {
    if (!$storeId) {
      $storeId = Mage::app()->getStore()->getId();
    }

    $tokenizationModel = Mage::getModel('securetrading_stpp/payment_tokenization');
    $collection = $tokenizationModel->getSavedCardsCollectionUsingParams($customerId, $storeId, $currencyIso3a, array($paymentTypeDescription));
    if (!$tokenizationModel->canSaveCards($collection)) {
      throw new Exception($this->__('Unable to create billing agreement for customer "%s", store "%s", currency "%s and payment type "%s".  Too many exist already.', $customerId, $storeId, $currencyIso3a, $paymentTypeDescription));
    }
    
    $agreement = Mage::getModel('sales/billing_agreement')
      ->setCustomerId($customerId)
      ->setMethodCode($paymentMethod->getCode())
      ->setReferenceId($transactionReference)
      ->setStatus(Mage_Sales_Model_Billing_Agreement::STATUS_ACTIVE)
      ->setStoreId($storeId)
      ->setAgreementLabel($agreementLabel)
    ;
     
    foreach($orderIncrementIds as $orderIncrementId) {
      $agreement->addOrderRelation(Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId)->getId());
    }
    
    $transaction = Mage::getModel('core_resource/transaction');
    $transaction->addObject($agreement);
    $transaction->addCommitCallback(function() use ($agreement, $paymentTypeDescription, $currencyIso3a) {
	Mage::getModel('securetrading_stpp/billing_agreement_paymenttypedescription')->setAgreementId($agreement->getId())->setPaymentTypeDescription($paymentTypeDescription)->save();
	if ($currencyIso3a) {
	  Mage::getModel('securetrading_stpp/billing_agreement_currency')->setAgreementId($agreement->getId())->setBaseCurrency($currencyIso3a)->save();
	}
    });
    $transaction->save();
    return $agreement;
  }

  public function registerSuccessfulOrderAfterExternalRedirect(Mage_Sales_Model_Order $order, $requestedSettleStatus) {
    $order->getPayment()->getMethodInstance()->log(sprintf('In %s.', __METHOD__));

    $amount = $order->getPayment()->getBaseAmountOrdered();
        
    if (in_array($requestedSettleStatus, array('0', '1', '100'))) {
      $order->getPayment()->registerCaptureNotification($amount, true);
    }
    elseif($requestedSettleStatus === '2') {
      $order->getPayment()->registerAuthorizationNotification($amount);
    }
    else {
      throw new Exception(sprintf('Invalid settle status: "%s".', $requestedSettleStatus));
    }
    $order->save();
  }
}