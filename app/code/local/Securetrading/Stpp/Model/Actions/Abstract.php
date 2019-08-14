<?php

abstract class Securetrading_Stpp_Model_Actions_Abstract extends Stpp_Actions_Abstract {
  protected $_order;

  abstract protected function _handleSofortPayment(Stpp_Data_Response $response);
  
  public function setOrder(Mage_Sales_Model_Order $order) {
    $this->_order = $order;
    return $this;
  }
  
  protected function _getOrder(Stpp_Data_Response $response) {
    if ($this->_order) {
      return $this->_order;
    }
    else if ($response->has('orderreference')) {
      $orderIncrementId = $response->get('orderreference');
    }
    else if ($response->getRequest()->has('orderreference')) {
      $orderIncrementId = $response->getRequest()->get('orderreference');
    }
    else {
      throw new Stpp_Exception(Mage::helper('securetrading_stpp')->__('The order increment ID could not be obtained.'));
    }
    $this->setOrder(Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId));
    return $this->_order;
  }
  
  protected function _handleSuccessfulOrder(Mage_Sales_Model_Order $order, $emailConfirmation) {
    $quote = Mage::getModel('sales/quote')->loadByIdWithoutStore($order->getQuoteId());
    
    if ($quote->getIsActive()) {
      $quote->setIsActive(false)->save();
    }

    if ($emailConfirmation) {
      $order->sendNewOrderEmail()->save(); // Send last - even if notif times out order status updated etc.  and payment information updated.
    }
  }
  
  protected function _getRequestedSettleStatus(Stpp_Data_Response $response) {
    $transaction = Mage::getModel('securetrading_stpp/transaction')->loadByTransactionReference($response->get('parenttransactionreference'), true);
    if ($transaction && $transaction->getResponseType() === Securetrading_Stpp_Model_Transaction_Types::TYPE_THREEDQUERY) { // api 3d query used - so auth request didn't have settlestatus so need to get 3dq request.
      $requestData = $transaction->getRequestData();
      $requestedSettleStatus = $requestData['settlestatus'];
    }
    else {
      $requestedSettleStatus = $response->getRequest()->get('settlestatus');
    }
    return (string) $requestedSettleStatus;
  }
  
  protected function _getRiskdecTransaction($transactionReference) {
    if ($transaction = Mage::getModel('securetrading_stpp/transaction')->loadByTransactionReference($transactionReference, true)) {
      return $transaction->searchAncestorsForRequestType(Securetrading_Stpp_Model_Transaction_Types::TYPE_RISKDEC);
    }
    return false;
  }
  
  protected function _riskdecTransactionHasShieldStatusCode($transaction, $shieldStatusCodes) {
    if (!is_array($shieldStatusCodes)) {
      $shieldStatusCodes = array($shieldStatusCodes);
    }
    if ($transaction->getRequestType() !== Securetrading_Stpp_Model_Transaction_Types::TYPE_RISKDEC) {
      throw new Stpp_Exception(sprintf(Mage::helper('securetrading_stpp')->__('Invalid transaction type: %s.'), $transaction->getRequestType()));
    }
    if (!in_array($transaction->getResponseData('fraudcontrolshieldstatuscode'), $shieldStatusCodes)) {
      return false;
    }
    return true;
  }
  
  protected function _paymentIsSuccessful(Stpp_Data_Response $response) {
    $result = false;
    if ($response->get('errorcode') === '0') {
      if (in_array($response->get('settlestatus'), array('0', '1', '100'), true)) {
	$result = true;
      }
      else if ($response->get('settlestatus') === '2') {
	if ($this->_getRequestedSettleStatus($response) === '2') {
	  $riskdecTransaction = $this->_getRiskdecTransaction($response->get('parenttransactionreference'));
	  if (!$riskdecTransaction || $this->_riskdecTransactionHasShieldStatusCode($riskdecTransaction, 'ACCEPT')) {
	    $result = true;
	  }
	}
      }
      else if ($response->get('settlestatus') === '10') {
	$result = false;
      }
      else {
	throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('Invalid settle status: "%s".'), $response->get('settlestatus')));
      }
    }
    
    return $result;
  }
  
  protected function _authShouldEnterPaymentReview(Stpp_Data_Response $response) {
    $result = false;
    if ($response->get('errorcode') === '0' && $response->get('settlestatus') === '2') {
      if ($this->_getRequestedSettleStatus($response) === '2') {
	$riskdecTransaction = $this->_getRiskdecTransaction($response->get('parenttransactionreference'));
	if ($riskdecTransaction && $this->_riskdecTransactionHasShieldStatusCode($riskdecTransaction, array('CHALLENGE', 'DENY'))) {
	  $result = true;
	}
      }
      else {
	$result = true;
      }
    }
    return $result;
  }
  
  protected function _authShouldEnterPaymentReviewAndBeDenied(Stpp_Data_Response $response) {
    $result = false;
    if ($response->get('errorcode') === '60107') {
      $result = true;
    }
    return $result;
  }
  
  protected function _paymentReviewAndDeny(Stpp_Data_Response $response, Mage_Sales_Model_Order $order) {
    $payment = $order->getPayment();
    $payment->setNotificationResult(true);
    $payment->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_DENY, false);
    $this->_setCoreTransaction($response, false);
    $order->save();
  }
  
  protected function _addGenericErrorToOrderStatusHistory(Stpp_Data_Response $response, Mage_Sales_Model_Order $order) {
    $message = sprintf('Payment failed: %s - %s.', $response->get('errorcode'), $response->get('errormessage'));
    $order->addStatusHistoryComment($message, false);
    $order->save();
  }
  
  protected function _isSuccessfulSofortPayment($response) {
    $result = false;
    if ($response->get('errorcode') === '0' && $response->get('paymenttypedescription') === Mage::getModel('securetrading_stpp/integration')->getSofortName()) {
      if ($response->get('settlestatus') !== '10') {
	throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('Unexpected settle status: "%s".'), $response->get('settlestatus')));
      }
      $result = true;
    }
    return $result;
  }

  public function processAuth(Stpp_Data_Response $response) {
    $this->_log($response, sprintf('In %s.', __METHOD__));
    
    $order = $this->_getOrder($response);
    $payment = $order->getPayment();
    
    if ($this->_paymentIsSuccessful($response)) {
      $closeCoreTransaction = $response->get('settlestatus') === '0';
      $this->_setCoreTransaction($response, $closeCoreTransaction);
    }
    else if ($this->_authShouldEnterPaymentReview($response)) {
      $payment->setIsTransactionPending(true);
      $this->_setCoreTransaction($response, false);
    }
    else if ($this->_authShouldEnterPaymentReviewAndBeDenied($response)) {
      $this->_paymentReviewAndDeny($response, $order);
    }
    else if ($this->_isSuccessfulSofortPayment($response)) {
      $this->_handleSofortPayment($response);
    }
    else {
      $this->_addGenericErrorToOrderStatusHistory($response, $order);
    }
    
    $this->_addTransaction(Securetrading_Stpp_Model_Transaction_Types::TYPE_AUTH, $response);
    
    $payment
      ->setAdditionalInformation('account_type_description', $response->get('accounttypedescription'))
      ->setAdditionalInformation('security_address', $response->get('securityresponseaddress'))
      ->setAdditionalInformation('security_postcode', $response->get('securityresponsepostcode'))
      ->setAdditionalInformation('security_code', $response->get('securityresponsesecuritycode'))
      ->setAdditionalInformation('enrolled', $response->get('enrolled'))
      ->setAdditionalInformation('status', $response->get('status'))
      ->setCcTransId($response->get('transactionreference'))
      ->setCcLast($payment->getMethodInstance()->getIntegration()->getCcLast4($response->get('maskedpan')))
      ;
    
    $order->save();
    return parent::processAuth($response);
  }
    
  public function process3dQuery(Stpp_Data_Response $response) {
    $this->_log($response, sprintf('In %s.', __METHOD__));
    $this->_addTransaction(Securetrading_Stpp_Model_Transaction_Types::TYPE_THREEDQUERY, $response);
    Mage::getSingleton('checkout/session')
      ->setAcsUrl($response->get('acsurl'))
      ->setPaReq($response->get('pareq'))
      ->setTermUrl($response->getRequest()->get('termurl'))
      ->setMd($response->get('md'))
      ;
            
    if ($this->_authShouldEnterPaymentReviewAndBeDenied($response)) {
      $this->_paymentReviewAndDeny($response, $this->_getOrder($response));
    }
            
    return parent::process3dQuery($response);
  }
    
  public function processRiskDecision(Stpp_Data_Response $response) {
    $this->_log($response, sprintf('In %s.', __METHOD__));
    $this->_addTransaction(Securetrading_Stpp_Model_Transaction_Types::TYPE_RISKDEC, $response);
    $this->_getOrder($response)->getPayment()->setAdditionalInformation('shield_status_code', $response->get('fraudcontrolshieldstatuscode'))->save();
    return parent::processRiskDecision($response);
  }
    
  public function processTransactionUpdate(Stpp_Data_Response $response) {
    $this->_log($response, sprintf('In %s.', __METHOD__));
    $this->_addTransaction(Securetrading_Stpp_Model_Transaction_Types::TYPE_TRANSACTIONUPDATE, $response);
        
    if ($response->get('errorcode') === '0') {
      $this->_setCoreTransaction($response, null, $response->getRequest()->get('filter')->get('transactionreference'));
    }
    elseif($response->get('errorcode') === '60017') { // transaction not updatable
      $response->setMessage(Mage::helper('securetrading_stpp')->__('This transaction cannot be updated: it has already been cancelled or settled.  Please re-order and alter a new order.'), true);
    }
    return parent::processTransactionUpdate($response);
  }
    
  public function processRefund(Stpp_Data_Response $response) {
    $this->_log($response, sprintf('In %s.', __METHOD__));
    if ($response->get('errorcode') === '0') {
      $this->_setCoreTransaction($response, null, $response->getRequest()->get('parenttransactionreference'));
    }
    $this->_addTransaction(Securetrading_Stpp_Model_Transaction_Types::TYPE_REFUND, $response);
    return parent::processRefund($response);
  }
    
  public function processAccountCheck(Stpp_Data_Response $response) {
    $this->_log($response, sprintf('In %s.', __METHOD__));
    $this->_addTransaction(Securetrading_Stpp_Model_Transaction_Types::TYPE_ACCOUNTCHECK, $response);
    return parent::processAccountCheck($response);
  }
    
  protected function _log(Stpp_Data_Response $response, $message) {
    $this->_getOrder($response)->getPayment()->getMethodInstance()->log($message);
  }
    
  protected function _addTransaction($responseType, $response) {
    $orderId = $this->_getOrder($response)->getId();
    $errorCode = $response->get('errorcode');
    $transactionReference = $response->get('transactionreference') ? $response->get('transactionreference') : $response->get('responseblockrequestreference');
    $parentTransactionReference = $response->get('parenttransactionreference');
    $accountTypeDescription = $response->get('accounttypedescription');
    $parentTransactionId = null;
        
    if ($parentTransactionReference) {
      $transaction = Mage::getModel('securetrading_stpp/transaction')->loadByTransactionReference($parentTransactionReference, true);
      if ($transaction) {
	$parentTransactionId = $transaction->getTransactionId();
      }
    }
    $responseData = $response->toArray();
    $requestData = $response->getRequest()->toArray();
        
    $requestTypeDescription = $response->getRequest()->get('requesttypedescription') ?: $response->get('requesttypedescription'); // look in request object for api, response object for ppg.
    $requestType = $this->_mapRequestType($requestTypeDescription);
        
    Mage::getModel('securetrading_stpp/transaction')
      ->setTransactionReference($transactionReference)
      ->setParentTransactionId($parentTransactionId)
      ->setRequestType($requestType)
      ->setResponseType($responseType)
      ->setRequestData($requestData)
      ->setResponseData($responseData)
      ->setErrorCode($errorCode)
      ->setAccountTypeDescription($accountTypeDescription)
      ->setOrderId($orderId)
      ->save()
      ;
  }
    
  protected function _mapRequestType($requestType) {
    switch($requestType) {
    case Stpp_Types::API_AUTH:
      $return = Securetrading_Stpp_Model_Transaction_Types::TYPE_AUTH;
      break;
    case Stpp_Types::API_THREEDQUERY:
      $return = Securetrading_Stpp_Model_Transaction_Types::TYPE_THREEDQUERY;
      break;
    case Stpp_Types::API_RISKDEC:
      $return = Securetrading_Stpp_Model_Transaction_Types::TYPE_RISKDEC;
      break;
    case Stpp_Types::API_TRANSACTIONUPDATE:
      $return = Securetrading_Stpp_Model_Transaction_Types::TYPE_TRANSACTIONUPDATE;
      break;
    case Stpp_Types::API_ACCOUNTCHECK:
      $return = Securetrading_Stpp_Model_Transaction_Types::TYPE_ACCOUNTCHECK;
      break;
    case Stpp_Types::API_REFUND:
      $return = Securetrading_Stpp_Model_Transaction_Types::TYPE_REFUND;
      break;
    default:
      $return = '';
    }
    return $return;
  }
    
  protected function _setCoreTransaction(Stpp_Data_Response $response, $isClosed = null, $parentTransactionId = null) {
    $payment = $this->_getOrder($response)->getPayment();
    
    $transactionId = $response->get('transactionreference') ? $response->get('transactionreference') : $response->get('responseblockrequestreference');
    $parentTransactionId !== null ? $parentTransactionId : null;
     
    $payment->setTransactionId($transactionId);
    $payment->setParentTransactionId($parentTransactionId);
    $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
      $this->_flattenArray(
	array(
	  'Request' => $response->getRequest()->toArray(),
	  'Response' => $response->toArray()
	      )
			   )
					   );
    
    if($isClosed !== null) {
      $payment->setIsTransactionClosed($isClosed);
    }
  }
    
  protected function _flattenArray($input, $separator = ' - ', array $prefix = array()) {
    $oneDimensionArray = array();
    foreach($input as $k => $v) {
      $currentPrefix = array_merge($prefix, array($k));
      if (is_array($v)) {
	$oneDimensionArray += $this->_flattenArray($v, $separator, $currentPrefix);
      }
      else {
	$key = implode($currentPrefix, $separator);
	$oneDimensionArray[$key] = $v;
      }
    }
    return $oneDimensionArray;
  }
}