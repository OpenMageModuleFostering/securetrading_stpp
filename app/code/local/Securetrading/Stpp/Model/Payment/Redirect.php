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
    protected $_canUseForMultishipping      = false;
    protected $_isInitializeNeeded          = true;
    protected $_canFetchTransactionInfo     = false;
    protected $_canReviewPayment            = true;
    protected $_canCreateBillingAgreement   = false;
    protected $_canManageRecurringProfiles  = false;
    
    public function initialize($action, $stateObject) {
        $this->log(sprintf('In %s.', __METHOD__));
        parent::initialize($action, $stateObject);
        Mage::getSingleton('securetrading_stpp/transport')->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)->setStatus(Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_PPAGES)->setMessage("Customer redirect to the SecureTrading Payment Pages.");
    }
    
    public function acceptPayment(Mage_Payment_Model_Info $payment) {
        $this->log(sprintf('In %s.', __METHOD__));
        parent::acceptPayment($payment);
        $invoice = $payment->getOrder()->prepareInvoice()->register()->pay();
        $payment->getOrder()->addRelatedObject($invoice)->save();
        return true;
    }
    
    public function denyPayment(Mage_Payment_Model_Info $payment) {
        $this->log(sprintf('In %s.', __METHOD__));
        parent::denyPayment($payment);
        return true;
    }
    
    public function getOrderPlaceRedirectUrl() {
        $this->log(sprintf('In %s.', __METHOD__));
        if ($this->getConfigData('ppg_use_iframe')) {
            return Mage::getUrl('securetrading/redirect_post/iframe');
        }
        return Mage::getUrl('securetrading/redirect_post/container');
    }
    
    public function prepareOrderData(Mage_Sales_Model_Order_Payment $payment) {
        $this->log(sprintf('In %s.', __METHOD__));
        $data = parent::prepareOrderData($payment);
        return $data += array(
            'storeid'       => $payment->getOrder()->getStoreId(),
            'parentcss'     => $this->getConfigData('parent_css'),
            'childcss'      => $this->getConfigData('child_css'),
            'parentjs'      => $this->getConfigData('parent_js'),
            'childjs'       => $this->getConfigData('child_js'),
            '_charset_'     => Mage::getStoreConfig('design/head/default_charset'),
        );
    }
    
    public function prepareData($isMoto = false) {
        $this->log(sprintf('In %s.', __METHOD__));
        $payment = $this->getInfoInstance();
        $data = $this->prepareOrderData($payment);
        $transport = $this->getIntegration()->runPaymentPages($data, $isMoto);
        return $transport;
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