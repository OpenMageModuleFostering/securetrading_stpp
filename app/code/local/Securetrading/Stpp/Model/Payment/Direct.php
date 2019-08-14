<?php

class Securetrading_Stpp_Model_Payment_Direct extends Securetrading_Stpp_Model_Payment_Abstract {
    protected $_code                        = 'securetrading_stpp_direct';
    protected $_formBlockType               = 'securetrading_stpp/payment_direct_form';
    protected $_infoBlockType               = 'securetrading_stpp/payment_direct_info';
    
    protected $_isGateway                   = false;
    protected $_canOrder                    = false;
    protected $_canAuthorize                = true;
    protected $_canCapture					= true;
    protected $_canCapturePartial           = true;
    protected $_canRefund					= true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = false;
    protected $_canUseInternal              = true;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = false;
    protected $_isInitializeNeeded          = true;
    protected $_canFetchTransactionInfo     = false;
    protected $_canReviewPayment            = true;
    protected $_canCreateBillingAgreement   = false;
    protected $_canManageRecurringProfiles  = false;
    
    protected $_stateObject;
    protected $_readyforAcsRedirect = false;
    protected static $_reviewingIncrementIds = array();
    
    protected function _setStateObject(Varien_Object $stateObject) {
    	$this->_stateObject = $stateObject;
    }
    
    protected function _getStateObject() {
    	if ($this->_stateObject === null) {
    		throw new Exception(Mage::helper('securetrading_stpp')->__('The state object has not been set.'));
    	}
    	return $this->_stateObject;
    }
    
    protected function _setReadyForAcsUrlRedirect($bool) {
    	$this->_readyforAcsRedirect = (bool) $bool;
    	return $this;
    }
    protected function _getReadyForAcsUrlRedirect() {
    	return (bool) $this->_readyforAcsRedirect;
    }
    
    public function initialize($action, $stateObject) {
    	$this->_setStateObject($stateObject);
    	$payment = $this->getInfoInstance();
    	$order = $payment->getOrder();
    	
    	switch ($action) {
    		case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE:
    			$payment->authorize(true, $order->getBaseTotalDue()); // base amount will be set inside
    			$payment->setAmountAuthorized($order->getTotalDue());
    			break;
    		case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE:
    			$payment->setAmountAuthorized($order->getTotalDue());
    			$payment->setBaseAmountAuthorized($order->getBaseTotalDue());
    			$payment->capture(null);
    			break;
    		default:
    			throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('Invalid payment action: "%s".')));
    	}
    }
    
    public function processInvoice($invoice, $payment) {
    	if ($this->_getReadyForAcsUrlRedirect()) {
    		foreach($this->getInfoInstance()->getOrder()->getStatusHistoryCollection(true) as $c) {
    			$c->delete();
    		}
    		$invoice->setIsPaid(false);
    	}
    }
    
    public function authorize(Varien_Object $payment, $amount) {
        $this->log(sprintf('In %s.', __METHOD__));
        parent::authorize($payment, $amount);
        $result = $this->getIntegration()->runApiStandard($payment);
        $this->_handleStandardPaymentResult($result);
        return $this;
    }
    
    public function capture(Varien_Object $payment, $amount) {
        $this->log(sprintf('In %s.', __METHOD__));
        parent::capture($payment, $amount);
        
        if ($payment->lookupTransaction('', Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH)) {
        	$this->captureAuthorized($payment, $amount);
        }
        else {
        	$this->_authAndCapture($payment);
        }
        return $this;
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
    
	public function prepareToRefund(Varien_Object $payment, $amount, $siteReference) {
    	$transactionReference = $payment->getCcTransId();
    	$orderIncrementIds = $this->_getOrderIncrementIds($transactionReference);
    	
    	if ($orderIncrementIds) {
    		$partialRefundAlreadyProcessed = null;
    		$orderBaseGrandTotal = null;
    		$baseTotalPaid = null;
    		$baseTotalRefunded = null;
    		
    		foreach($orderIncrementIds as $orderIncrementId) {
    			$tempPayment = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId)->getPayment();
    			
    			if ($partialRefundAlreadyProcessed !== true) {
    				$partialRefundAlreadyProcessed = $this->_paymentHasSuccessfulRefund($tempPayment);
    			}
    			
    			$orderBaseGrandTotal += $tempPayment->getOrder()->getBaseGrandTotal();
    			$baseTotalPaid += $tempPayment->getOrder()->getBaseTotalPaid();
    			$baseTotalRefunded += $tempPayment->getOrder()->getBaseTotalRefunded();
    		}
    	}
    	else {
    		$partialRefundAlreadyProcessed = $this->_paymentHasSuccessfulRefund($payment);
    		$orderBaseGrandTotal = $payment->getOrder()->getBaseGrandTotal();
    		$baseTotalPaid = $payment->getOrder()->getBaseTotalPaid();
    		$baseTotalRefunded = $payment->getOrder()->getBaseTotalRefunded() - $amount; // Before this method is called this happens: $payment->getOrder->setBaseTotalRefunded($payment->getOrder()->getBaseTotalRefunded() - $amount).  That's why we subtract the $amount here - but don't for multishipping above (because we load new temporary orders when multishipping is being calculated.
    	}
    	
    	$data = array(
    			'original_order_total'				=> $orderBaseGrandTotal, // Total of original AUTH.
    			'order_total_paid'					=> $baseTotalPaid, // How much has been captured from the AUTH (do not consider how much has been refunded via TU or REFUND).
    			'order_total_refunded'				=> $baseTotalRefunded, // How much has been refunded via TU or REFUND.
    			'amount_to_refund'					=> $amount,
    			'partial_refund_already_processed' 	=> $partialRefundAlreadyProcessed,
				'site_reference'                   	=> $siteReference,
    			'transaction_reference' 			=> $transactionReference,
    			'using_main_amount'					=> true,
    			'currency_iso_3a'					=> $payment->getOrder()->getBaseCurrencyCode(),
    			'allow_suspend'						=> true,
    	);
    	$payment->setShouldCloseParentTransaction(false);
    	return $data;
    }
    
    public function refund(Varien_Object $payment, $amount) {
		$data = $this->prepareToRefund($payment, $amount, $this->getConfigData('site_reference'));
    	$this->getIntegration()->runApiRefund($payment, $data);
    	return $this;
    }
    
    public function cancel(Varien_Object $payment) {
    	return $this; // Do nothing intentionally.
    }
	
	public function denyPaymentAndPrepareApiRequest(Mage_Payment_Model_Info $payment, $siteReference) {
    	parent::denyPayment($payment);
    	$data = null;
    	
    	if (!empty(self::$_reviewingIncrementIds)) {
    		$payment->setShouldCloseParentTransaction(true);
    		$payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID);
    	}
    	else {
    		if ($payment->getOrder()->getState() !== Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
    			throw new Mage_Core_Exception('This order is no longer in the payment review state.');
    		}
    
    		$transactionReference = $payment->getCcTransId();
    		$orderIncrementIds = $this->_getOrderIncrementIds($transactionReference);
    		
    		if ($orderIncrementIds) {
    			self::$_reviewingIncrementIds = $orderIncrementIds;
    			foreach($orderIncrementIds as $orderIncrementId) {
    				$order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
    				$order->getPayment()->deny();
    				$order->save();
    			}
    			self::$_reviewingIncrementIds = array();
    		}
    		else {
    			$payment->setShouldCloseParentTransaction(true);
    			$payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID);
    		}
			$data = $this->_prepareToUpdateSettleStatus($payment, '3', $siteReference);
    	}
    	return $data;
    }
    
	public function acceptPaymentAndPrepareApiRequest(Mage_Payment_Model_Info $payment, $siteReference) {
    	parent::acceptPayment($payment);
    	$data = null;
    	
    	if (empty(self::$_reviewingIncrementIds)) {
	    	if ($payment->getOrder()->getState() !== Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
	    		throw new Mage_Core_Exception('This order is no longer in the payment review state.');
	    	}
	    	 
	    	$transactionReference = $payment->getCcTransId();
	    	$orderIncrementIds = $this->_getOrderIncrementIds($transactionReference);
	    	 
	    	if ($orderIncrementIds) {
	    		self::$_reviewingIncrementIds = $orderIncrementIds;
	    		foreach($orderIncrementIds as $orderIncrementId) {
	    			$order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
	    			$order->getPayment()->accept();
	    			$order->save();
	    		}
	    		self::$_reviewingIncrementIds = array();
	    	}
	    	 
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
	    	
	    	if ($requestedSettleStatus !== 2) { // If the requested settlestatus was 2 there is no need to update the payment (an order should only be put into payment review when the response settlestatus == 2).
				$data = $this->_prepareToUpdateSettleStatus($payment, $requestedSettleStatus, $siteReference);
	    	}
    	}
    	return $data;
    }
    
    public function acceptPayment(Mage_Payment_Model_Info $payment) {
		$data = $this->acceptPaymentAndPrepareApiRequest($payment, $this->getConfigData('site_reference'));
    	if ($data) {
    		$this->getIntegration()->runApiTransactionUpdate($payment, $data);
    	}
    	return true;
    }
    
    public function denyPayment(Mage_Payment_Model_Info $payment) {
		 $data = $this->denyPaymentAndPrepareApiRequest($payment, $this->getConfigData('site_reference'));
    	 if ($data) {
			$this->getIntegration()->runApiTransactionUpdate($payment, $data);
    	 }
    	 return true;
    }
    
    protected function _getOrderIncrementIds($transactionReference) {
    	$transaction = Mage::getModel('securetrading_stpp/transaction')->loadByTransactionReference($transactionReference);
    	$orderIncrementIds = $transaction->getRequestData('order_increment_ids', null);
    	
    	if ($orderIncrementIds) {
    		$orderIncrementIds = unserialize($orderIncrementIds);
    	}
    	if ($orderIncrementIds && count($orderIncrementIds) > 1) {
    		return $orderIncrementIds;
    	}
    	return null;
    }
    
	public function prepareToCaptureAuthorized(Mage_Sales_Model_Order_Payment $payment, $amount, $siteReference) {
    	$transactionReference = $payment->getCcTransId();
    	$orderIncrementIds = $this->_getOrderIncrementIds($transactionReference);
    	
    	if ($orderIncrementIds) {
    		$orderBaseGrandTotal = null;
    		$baseAmountPaid = null;
    		$baseAmountRefunded = null;
    		foreach($orderIncrementIds as $orderIncrementId) {
    			$order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
    			$orderBaseGrandTotal += $order->getBaseGrandTotal();
    			$baseAmountPaid += $order->getPayment()->getBaseAmountPaid();
    			$baseAmountRefunded += $order->getPayment()->getBaseAmountRefunded(); 
    		}
    		$orderBaseGrandTotal = (string) Mage::app()->getStore()->roundPrice($orderBaseGrandTotal);
    		$baseAmountPaid = (string) Mage::app()->getStore()->roundPrice($baseAmountPaid);
    		$baseAmountRefunded = (string) Mage::app()->getStore()->roundPrice($baseAmountRefunded);
    	}
    	else {
    		$orderBaseGrandTotal = (string) Mage::app()->getStore()->roundPrice($payment->getOrder()->getBaseGrandTotal());
    		$baseAmountPaid = (string) Mage::app()->getStore()->roundPrice($payment->getBaseAmountPaid());
    		$baseAmountRefunded = (string) Mage::app()->getStore()->roundPrice($payment->getBaseAmountRefunded());
    	}
    	$amountToCapture = (string) $amount;
    	$updates = array('settlestatus' => '0');
    	
    	if ($amountToCapture !== $orderBaseGrandTotal) {
    		$updates['settlemainamount'] = ($baseAmountPaid + $amountToCapture) - $baseAmountRefunded;
    		$updates['currencyiso3a'] = $payment->getOrder()->getBaseCurrencyCode();
    	}
    	
		$data = $this->_prepareTransactionUpdate($payment, $updates, $siteReference);
        return $data;
    }
    
    public function captureAuthorized(Mage_Sales_Model_Order_Payment $payment, $amount) {
		$data = $this->prepareToCaptureAuthorized($payment, $amount, $this->getConfigData('site_reference'));
    	$this->getIntegration()->runApiTransactionUpdate($payment, $data);
    	return $this;
    }
    
    protected function _handleStandardPaymentResult(Stpp_Api_ResultInterface $result) {
    	$this->log(sprintf('In %s.', __METHOD__));
    	if ($result->getRedirectRequired()) {
    		$this->_setReadyForAcsUrlRedirect(true);
    		$this->_getStateObject()->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)->setStatus(Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_3DSECURE);
    		
    		$redirectPath = $this->getConfigData('api_use_iframe') ? 'securetrading/direct_post/iframe' : 'securetrading/direct_post/container';
    		$params = new Varien_Object();
    		$params
	    		->setOrderPlaceRedirectUrl(Mage::getUrl($redirectPath))
	    		->setRedirectIsPost($result->getRedirectIsPost())
	    		->setRedirectUrl($result->getRedirectUrl())
	    		->setRedirectData($result->getRedirectData())
    		;
    		Mage::getSingleton('securetrading_stpp/payment_direct_session')->setAcsRedirectParams($params);
    	}
    	elseif(!$result->getIsTransactionSuccessful()) {
    		throw new Mage_Payment_Model_Info_Exception($result->getErrorMessage());
    	}
    	else {
    		$this->_getStateObject()->setState(Mage_Sales_Model_Order::STATE_PROCESSING)->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
    	}
    	return $this;
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
    
    protected function _authAndCapture(Mage_Sales_Model_Order_Payment $payment) {
    	$result = $this->getIntegration()->runApiStandard($payment);
    	$this->_handleStandardPaymentResult($result);
    	return $this;
    }
    
    public function getOrderPlaceRedirectUrl() {
        $session = Mage::getSingleton('securetrading_stpp/payment_direct_session');
        $acsParamsExist = $session->hasAcsRedirectParams();
        
        $this->log(sprintf('In %s.  ACS Params exist: %s.', __METHOD__, $acsParamsExist));
        
        if ($acsParamsExist) {
            return $session->getAcsRedirectParams()->getOrderPlaceRedirectUrl();
        }
        return null;
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
        Mage::getModel('securetrading_stpp/payment_direct_session')->setSecurityCode($payment->encrypt($data->getSecuretradingStppSecurityCode())); // Cannot save CC CID due to PCI requirements.
        return $this;
    }
    
    public function prepareOrderData(Mage_Sales_Model_Order_Payment $payment, array $orderIncrementIds = array(), $sendEmailConfirmation = true) {
        $orderIncrementIds = array($payment->getOrder()->getId() => $payment->getOrder()->getIncrementId());
    	$data = parent::prepareOrderData($payment, $orderIncrementIds);
        $payment = $this->getInfoInstance();
        
        $data += array(
            'termurl'       => Mage::getUrl('securetrading/direct/return'),
            'paymenttype'   => $payment->getCcType(),
            'pan'           => $payment->decrypt($payment->getCcNumberEnc()),
            'expirydate'    => $payment->getCcExpMonth() . '/' . $payment->getCcExpYear(),
            'securitycode'  => $payment->decrypt(Mage::getModel('securetrading_stpp/payment_direct_session')->getSecurityCode()),
            'issuenumber'   => $payment->getCcSsIssue(),
        );
        
		if ($payment->getCcSsStartMonth() || $payment->getCcSsStartYear()) {
			$data['startdate'] = $payment->getCcSsStartMonth() . '/' . $payment->getCcSsStartYear();
		}
		return $data;
    }
    
    public function run3dAuth() {
    	$this->log(sprintf('In %s.', __METHOD__));
    	$result = $this->getIntegration()->runApi3dAuth();
    	if ($result->getErrorMessage()) {
    		Mage::getSingleton('checkout/session')->addError($result->getErrorMessage());
    	}
    	return $result->getIsTransactionSuccessful();
    }
    
    public function handleSuccessfulPayment(Mage_Sales_Model_Order $order, $emailConfirmation = true) {
        parent::handleSuccessfulPayment($order, $emailConfirmation);
        Mage::getSingleton('securetrading_stpp/payment_direct_session')->clear();
    }
}