<?php

class Securetrading_Stpp_Block_Adminhtml_Sales_Transactions_Single extends Mage_Adminhtml_Block_Widget_Container {
    protected $_transaction;
    
    protected function _construct() {
        $transaction = Mage::registry('current_transaction');
        
        if ($transaction === null) {
            throw new Exception(Mage::helper('securetrading_stpp')->__('A transaction has not been set.'));
        }
        
        $this->setTransaction($transaction);
        
        $backUrl = $this->getOrderId() ? $this->getUrl('*/sales_order/view/', array('order_id' => $this->getOrderId())) : $this->getUrl('*/securetrading_transactions');
        
        $this->_addButton('back', array(
            'label'   => Mage::helper('sales')->__('Back'),
            'onclick' => "setLocation('{$backUrl}')",
            'class'   => 'back'
        ));
    }
    
    protected function _prepareLayout() {
        switch($this->getTransaction()->getRequestType()) {
            case Securetrading_stpp_Model_Transaction_Types::TYPE_AUTH:
            case Securetrading_stpp_Model_Transaction_Types::TYPE_THREEDQUERY:
            case Securetrading_stpp_Model_Transaction_Types::TYPE_RISKDEC:
            case Securetrading_stpp_Model_Transaction_Types::TYPE_ACCOUNTCHECK:
            case Securetrading_stpp_Model_Transaction_Types::TYPE_CARDSTORE:
            case Securetrading_stpp_Model_Transaction_Types::TYPE_REFUND:
            	$block = $this->getLayout()->createBlock('securetrading_stpp/adminhtml_sales_transactions_type_default', '', array('transaction' => $this->getTransaction()));
            	break;
            case Securetrading_Stpp_Model_Transaction_Types::TYPE_TRANSACTIONUPDATE:
            	$block = $this->getLayout()->createBlock('securetrading_stpp/adminhtml_sales_transactions_type_transactionupdate', '', array('transaction' => $this->getTransaction()));
            	break;
            default:
                throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('Invalid transaction type: "%s".'), $this->getTransaction()->getRequestType()));
        }
        $block->setTransaction($this->getTransaction());
        $this->setChild('transaction_data', $block);
    }
    
    public function getHeaderText() {
        return sprintf('Secure Trading Transaction #%s', $this->getTransaction()->getTransactionReference());
    }
    
    public function getTransaction() {
        if ($this->_transaction === null) {
            throw new Exception(Mage::helper('securetrading_stpp')->__('The transaction has not been set.'));
        }
        return $this->_transaction;
    }
    
    public function setTransaction(Securetrading_Stpp_Model_Transaction $transaction) {
        $this->_transaction = $transaction;
    }
    
    public function getTransactionReference() {
        return $this->getTransaction()->getTransactionReference();
    }
    
    public function replaceIfXReference($transactionReferenceOrXReference) {
      if (substr($transactionReferenceOrXReference, 0, 1) === 'W') {
	$filters = $this->getTransaction()->getRequestData('filter');
	$return = $filters['transactionreference'];
      }
      else {
	$return = $transactionReferenceOrXReference;
      }
      return $return;
    }
    
    public function hasParentTransaction() {
        return $this->getTransaction()->getParentTransactionId();
    }
    
    public function getParentTransactionId() {
        return $this->getTransaction()->getParentTransactionId();
    }
    
    public function getParentTransactionReference() {
        return $this->getTransaction()->getParentTransactionReference();
    }
    
    public function getRequestType() {
        $requestType = $this->getTransaction()->getRequestType();
        return Mage::getModel('securetrading_stpp/transaction_types')->load($requestType)->getTypeName();
    }
    
    public function getResponseType() {
        $responseType = $this->getTransaction()->getResponseType();
        return Mage::getModel('securetrading_stpp/transaction_types')->load($responseType)->getTypeName();
    }
    
    public function getErrorCode() {
        return $this->getTransaction()->getErrorCode();
    }
    
    public function getAccountTypeDescription() {
    	return $this->getTransaction()->getAccountTypeDescription();
    }
    
    public function getLastUpdatedAt() {
        return $this->getTransaction()->getLastUpdatedAt();
    }
    
    public function getOrderId() {
        return $this->getTransaction()->getOrderId();
    }
    
    public function getOrderIncrementId() {
        $orderId = $this->getOrderId();
        $order = Mage::getModel('sales/order')->load($orderId);
        return $order->getIncrementId();
    }
    
    public function getOrderUrl($orderId = '') {
        $orderId = empty($orderId) ? $this->getOrderId() : $orderId;
        return Mage::getUrl('*/sales_order/view', array('order_id' => $orderId));
    }
    
    public function getMystUrl() {
      return 'https://myst.securetrading.net/transactions/singletransaction?transactionreference=' . urlencode($this->replaceIfXReference($this->getTransactionReference()));
    }
    
    public function getParentTransactionIdUrl($parentTransactionId = '') {
        $parentTransactionId = empty($parentTransactionId) ? $this->getParentTransactionId() : $parentTransactionId;
        return Mage::getModel('adminhtml/url')->getUrl('adminhtml/securetrading_transactions/single/', array('transaction_id' => $parentTransactionId));
    }
}