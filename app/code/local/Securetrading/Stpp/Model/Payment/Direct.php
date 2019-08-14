<?php

class Securetrading_Stpp_Model_Payment_Direct extends Securetrading_Stpp_Model_Payment_Abstract {
    protected $_code                        = 'securetrading_stpp_direct';
    protected $_formBlockType               = 'securetrading_stpp/payment_direct_form';
    protected $_infoBlockType               = 'securetrading_stpp/payment_direct_info';
    
    protected $_isGateway                   = false;
    protected $_canOrder                    = false;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = false;
    protected $_canCapturePartial           = false;
    protected $_canRefund                   = false;
    protected $_canRefundInvoicePartial     = false;
    protected $_canVoid                     = false;
    protected $_canUseInternal              = true;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = false;
    protected $_isInitializeNeeded          = true;
    protected $_canFetchTransactionInfo     = false;
    protected $_canReviewPayment            = false;
    protected $_canCreateBillingAgreement   = false;
    protected $_canManageRecurringProfiles  = false;
    
    public function initialize($action, $stateObject) {
        $this->log(sprintf('In %s.  Action is %s.', __METHOD__, $action));
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        
        switch ($action) {
            case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE:
                $payment->authorize(true, $order->getBaseTotalDue());
                $payment->setAmountAuthorized($order->getTotalDue());
                break;
            case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE:
                $payment->setAmountAuthorized($order->getTotalDue());
                $payment->setBaseAmountAuthorized($order->getBaseTotalDue());
                $payment->capture(null);
                break;
            default:
                break;
        }
        return $this;
    }
    
    public function authorize(Varien_Object $payment, $amount) {
        $this->log(sprintf('In %s.', __METHOD__));
        parent::authorize($payment, $amount);
        $result = $this->getIntegration()->runApiStandard($payment);
        $this->handleStandardPaymentResult($result);
        return $this;
    }
    
    public function capture(Varien_Object $payment, $amount) {
        $this->log(sprintf('In %s.', __METHOD__));
        parent::capture($payment, $amount);
        $result = $this->getIntegration()->runApiStandard($payment);
        $this->handleStandardPaymentResult($result);
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
    
    public function prepareOrderData(Mage_Sales_Model_Order_Payment $payment, array $orderIncrementIds) {//TODO - the api module must pass this new 2nd param through
        $data = parent::prepareOrderData($payment, $orderIncrementIds);
        $payment = $this->getInfoInstance();
        
        return $data += array(
            'termurl'       => Mage::getUrl('securetrading/direct/return'),
            'paymenttype'   => $payment->getCcType(),
            'pan'           => $payment->decrypt($payment->getCcNumberEnc()),
            'startdate'     => $payment->getCcSsStartMonth() . '/' . $payment->getCcSsStartYear(),
            'expirydate'    => $payment->getCcExpMonth() . '/' . $payment->getCcExpYear(),
            'securitycode'  => $payment->decrypt(Mage::getModel('securetrading_stpp/payment_direct_session')->getSecurityCode()),
            'issuenumber'   => $payment->getCcSsIssue(),
        );
    }
    
    public function handleStandardPaymentResult(Stpp_Api_ResultInterface $result) {
        $this->log(sprintf('In %s.', __METHOD__));
        if ($result->getRedirectRequired()) {
            Mage::getSingleton('securetrading_stpp/transport')->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)->setStatus(Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_3DSECURE)->setMessage("Customer redirected to the 3D Secure ACS URL.");
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
            throw new Mage_Payment_Model_Info_Exception($result->getCustomerErrorMessage());
        }
        return $this;
    }
    
    public function run3dAuth() {
        $this->log(sprintf('In %s.', __METHOD__));
        $result = $this->getIntegration()->runApi3dAuth();
        
        if($result->getIsTransactionSuccessful()) {
            $this->registerSuccessfulOrderAfterExternalRedirect();
            return true;
        }
        return false;
    }
    
    public function handleSuccessfulPayment(Mage_Sales_Model_Order $order, $emailConfirmation = true) {
        parent::handleSuccessfulPayment($order);
        Mage::getSingleton('securetrading_stpp/payment_direct_session')->clear();
    }
}