<?php

abstract class Securetrading_Stpp_Model_Actions_Abstract extends Stpp_Actions_Abstract {
    protected $_orderIncrementId = '';
    
    public function getOrderIncrementId(Stpp_Data_Response $response) {
        if ($response->has('orderreference')) {
            return $response->get('orderreference');
        }
        elseif ($response->getRequest()->has('orderreference')) {
            return $response->getRequest()->get('orderreference');
        }
        elseif ($this->_orderIncrementId) {
            return $this->_orderIncrementId;
        }
        else {
            throw new Stpp_Exception(Mage::helper('securetrading_stpp')->__('The order increment ID could not be obtained.'));
        }
    }
    
    public function setOrderIncrementId($orderIncrementId) {
        $this->_orderIncrementId = $orderIncrementId;
        return $this;
    }
    
    public function processAuth(Stpp_Data_Response $response) {
        $this->_log($response, sprintf('In %s.', __METHOD__));
        $errorCode = $response->get('errorcode');
        
        $state = $status = $message = null;
        
        if ($errorCode === '0') {
            if (in_array($response->get('settlestatus'), array('0', '1', '100'), true)) {
                $state = Mage_Sales_Model_Order::STATE_PROCESSING;
                $status = Mage::getModel('sales/order')->getConfig()->getStateDefaultStatus($state);
                $message = 'Payment captured.';
            }
            elseif ($response->get('settlestatus') === '2') {
                if ($response->getRequest()->get('settlestatus') !== 2) {
                    $state = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
                    $status = Securetrading_Stpp_Model_Payment_Abstract::STATUS_SUSPENDED;
                    $message = 'Payment suspended.  Request and response settle status mismatch.';
                }
                else {
                    $state = Mage_Sales_Model_Order::STATE_PROCESSING;
                    $status = Securetrading_Stpp_Model_Payment_Abstract::STATUS_AUTHORIZED;
                    $message = 'Payment authorized.';
                }
            }
            else {
                throw new Stpp_Exception(sprintf(Mage::helper('securetrading_stpp')->__('Unhandled settle status: "%s".'), $response->get('settlestatus')));
            }
        }
        elseif($errorCode === '60107') {
            $status = Securetrading_Stpp_Model_Payment_Abstract::STATUS_SUSPENDED;
            $state = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
            $message = 'Payment suspended by parent Risk Decision.';
        }
        else {
            $message = sprintf('Payment failed: %s - %s.', $response->get('errorcode'), $response->get('errormessage'));
            $order = Mage::getModel('sales/order')->loadByIncrementId($response->get('orderreference'));
            $order->addStatusHistoryComment($message, false);
            $order->save();
        }
        
        $additionalInformation = array(
            'account_type_description'  => $response->get('accounttypedescription'),
            'security_address'          => $response->get('securityresponseaddress'),
            'security_postcode'         => $response->get('securityresponsepostcode'),
            'security_code'             => $response->get('securityresponsesecuritycode'),
            'enrolled'                  => $response->get('enrolled'),
            'status'                    => $response->get('status'),
        );
        
        $transactionReference = $response->get('transactionreference');
        $maskedPan = $response->get('maskedpan');
        Mage::getSingleton('securetrading_stpp/transport')->setState($state)->setStatus($status)->setMessage($message)->setOrderReference($response->get("orderreference"))->setAdditionalInformation($additionalInformation)->setTransactionReference($transactionReference)->setMaskedPan($maskedPan);
        $this->_addTransaction(Securetrading_Stpp_Model_Transaction_Types::TYPE_AUTH, $response);

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
        return parent::process3dQuery($response);
    }
    
    public function processRiskDecision(Stpp_Data_Response $response) {
        $this->_log($response, sprintf('In %s.', __METHOD__));
        $this->_addTransaction(Securetrading_Stpp_Model_Transaction_Types::TYPE_RISKDEC, $response);
        return parent::processRiskDecision($response);
    }
    
    public function processTransactionUpdate(Stpp_Data_Response $response) {
        $this->_log($response, sprintf('In %s.', __METHOD__));
        $this->_addTransaction(Securetrading_Stpp_Model_Transaction_Types::TYPE_TRANSACTIONUPDATE, $response);
        return parent::processTransactionUpdate($response);
    }
    
    public function processRefund(Stpp_Data_Response $response) {
        $this->_log($response, sprintf('In %s.', __METHOD__));
        $this->_addTransaction(Securetrading_Stpp_Model_Transaction_Types::TYPE_REFUND, $response);
        return parent::processRefund($response);
    }
    
    public function processAccountCheck(Stpp_Data_Response $response) {
        $this->_log($response, sprintf('In %s.', __METHOD__));
        $this->_addTransaction(Securetrading_Stpp_Model_Transaction_Types::TYPE_ACCOUNTCHECK, $response);
        return parent::processAccountCheck($response);
    }
    
    protected function _log(Stpp_Data_Response $response, $message) {
        Mage::getModel('sales/order')->loadByIncrementId($this->getOrderIncrementId($response))->getPayment()->getMethodInstance()->log($message);
    }
    
    protected function _addTransaction($responseType, $response) {
        $orderId = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderIncrementId($response))->getId();
        $errorCode = $response->get('errorcode');
        $transactionReference = $response->get('transactionreference');
        $parentTransactionReference = $response->get('parenttransactionreference');
        $parentTransactionId = null;
        
        if ($parentTransactionReference) {
            $transaction = Mage::getModel('securetrading_stpp/transaction')->loadByParentTransactionReference($parentTransactionReference, true);
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
            default:
                $return = '';
        }
        return $return;
    }
}