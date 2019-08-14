<?php

class Securetrading_Stpp_Model_Payment_Redirect extends Securetrading_Stpp_Model_Payment_Abstract {
    protected $_code                        = 'securetrading_stpp_redirect';
    protected $_formBlockType               = 'securetrading_stpp/payment_redirect_form';
    protected $_infoBlockType               = 'securetrading_stpp/payment_redirect_info';
    
    protected $_isGateway                   = false;
    protected $_canOrder                    = false;
    protected $_canAuthorize                = false;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = false;
   	protected $_canRefund                   = false;
    protected $_canRefundInvoicePartial     = false;
    protected $_canVoid                     = false;
    protected $_canUseInternal              = true;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping		= true;
    protected $_isInitializeNeeded          = true;
    protected $_canFetchTransactionInfo     = false;
    protected $_canReviewPayment            = true;
    protected $_canCreateBillingAgreement   = false;
   	protected $_canManageRecurringProfiles  = false;
    
    public function __construct() {
    	if ($this->getConfigData('use_api')) {
    		$this->_canCapturePartial = true;
    		$this->_canRefund = true;
    		$this->_canRefundInvoicePartial = true;
    	}
    }
    
    public function initialize($action, $stateObject) {
        $this->log(sprintf('In %s.', __METHOD__));
        parent::initialize($action, $stateObject);
        $stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)->setStatus(Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_PPAGES);
    }
    
    public function acceptPayment(Mage_Payment_Model_Info $payment) {
        $this->log(sprintf('In %s.', __METHOD__));
		$data = $this->_getApi()->acceptPaymentAndPrepareApiRequest($payment, $this->getConfigData('site_reference'));
    	if ($data) {
    		$this->getIntegration()->runApiTransactionUpdate($payment, $data);
    	}
    	return true;
    }
    
    public function denyPayment(Mage_Payment_Model_Info $payment) {
        $this->log(sprintf('In %s.', __METHOD__));
		$data = $this->_getApi()->denyPaymentAndPrepareApiRequest($payment, $this->getConfigData('site_reference'));
        if ($data) {
        	$this->getIntegration()->runApiTransactionUpdate($payment, $data);
        }
        return true;
    }
    
    protected function _useFirstPathIfIframe($path1, $path2) {
    	if ($this->getConfigData('ppg_use_iframe')) {
    		return $path1;
    	}
    	return $path2;
    }
    
    public function getOrderPlaceRedirectUrl() {
    	$this->log(sprintf('In %s.', __METHOD__));
    	return Mage::getUrl($this->getOrderPlaceRedirectPath());
    }
    
    public function getOrderPlaceRedirectPath() {
    	$this->log(sprintf('In %s.', __METHOD__));
    	return $this->_useFirstPathIfIframe('securetrading/redirect_post_onepage/iframe', 'securetrading/redirect_post_onepage/container');
    }
    public function getMultishippingRedirectPath() {
    	$this->log(sprintf('In %s.', __METHOD__));
    	return $this->_useFirstPathIfIframe('securetrading/redirect_post_multishipping/iframe', 'securetrading/redirect_post_multishipping/container');
    }
    
    public function getMotoOrderRedirectPath() {
    	$this->log(sprintf('In %s.', __METHOD__));
    	return $this->_useFirstPathIfIframe('*/sales_order_create_securetrading/iframe', '*/sales_order_create_securetrading/post');
    }
    
    public function prepareOrderData(Mage_Sales_Model_Order_Payment $payment, array $orderIncrementIds, $sendEmailConfirmation = true) {
        $this->log(sprintf('In %s.', __METHOD__));
        $data = parent::prepareOrderData($payment, $orderIncrementIds);
        
        return $data += array(
        	'customfield1' 			=> $payment->getOrder()->getStoreId(),
            'parentcss'     		=> $this->getConfigData('parent_css'),
            'childcss'      		=> $this->getConfigData('child_css'),
            'parentjs'      		=> $this->getConfigData('parent_js'),
            'childjs'       		=> $this->getConfigData('child_js'),
            '_charset_'     		=> Mage::getStoreConfig('design/head/default_charset'),
        	'order_increment_ids' 	=> serialize($orderIncrementIds),
        	'send_confirmation' 	=> $sendEmailConfirmation,
        );
    }
    
    protected function _getApi() {
    	return Mage::getModel('securetrading_stpp/payment_direct');
    }
    
    public function capture(Varien_Object $payment, $amount) {
    	$this->log(sprintf('In %s.', __METHOD__));
    	parent::capture($payment, $amount);
		$data = $this->_getApi()->prepareToCaptureAuthorized($payment, $amount, $this->getConfigData('site_reference'));
    	$this->getIntegration()->runApiTransactionUpdate($payment, $data);
    	return $this;
    }
    
    public function refund(Varien_Object $payment, $amount) {
    	$this->log(sprintf('In %s.', __METHOD__));
    	parent::refund($payment, $amount);
		$data = $this->_getApi()->prepareToRefund($payment, $amount, $this->getConfigData('site_reference'));
    	$this->getIntegration()->runApiRefund($payment, $data);
    	return $this;
    }
    
    public function cancel(Varien_Object $payment) {
    	return $this; // Do nothing intentionally.
    }
  
    public function prepareData($isMoto = false, array $orderIncrementIds = array(), $sendEmailConfirmation = true) {
        $this->log(sprintf('In %s.', __METHOD__));
        $data = $this->prepareOrderData($this->getInfoInstance(), $orderIncrementIds, $sendEmailConfirmation);
        $transport = $this->getIntegration()->runPaymentPages($data, $isMoto);
        return $transport;
    }
    
    public function validateOrders(array $orderIncrementIds) {
		if (empty($orderIncrementIds)) {
			throw new Exception(Mage::helper('securetrading_stpp')->__('No order increment IDs.'));
		}
		
    	foreach($orderIncrementIds as $orderIncrementId) {
    		if ($orderIncrementId === null) {
    			throw new Exception(Mage::helper('securetrading_stpp')->__('No order ID.'));
    		}
    	
    		$order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
			
    		if ($order->getPayment()->getMethodInstance()->getCode() !== Mage::getModel('securetrading_stpp/payment_redirect')->getCode()) {
    			throw new Exception(Mage::helper('securetrading_stpp')->__('Cannot access payment method.'));
    		}
    	
    		if ($order->getStatus() !== Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_PPAGES) {
    			throw new Exception(Mage::helper('securetrading_stpp')->__('Order not pending payment pages.'));
    		}
    	}
    	return $this;
    }
    
	public function getFirstMethodInstance(array $orderIncrementIds) {
		$firstOrderIncrementId = array_shift($orderIncrementIds);
		$order = Mage::getModel('sales/order')->loadByIncrementId($firstOrderIncrementId);
		return $order->getPayment()->getMethodInstance();
	}
	
    public function runRedirect() {
        $this->log(sprintf('In %s.', __METHOD__));
        return $this->getIntegration()->runRedirect();
    }
    
    public function runNotification() {
        $this->log(sprintf('In %s.', __METHOD__));
        $this->getIntegration()->runNotification();
    }
}