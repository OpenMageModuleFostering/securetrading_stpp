<?php

class Securetrading_Stpp_Securetrading_TransactionsController extends Mage_Adminhtml_Controller_Action {
    public function indexAction() {
        $this
            ->_title($this->__('Sales'))
            ->_title($this->__('SecureTrading Transactions')); 
        
        $this->loadLayout()->_setActiveMenu('sales/securetrading_transactions');
        $this->renderlayout();
    }
    
    public function singleAction() {
        $tid = $this->getRequest()->getParam('transaction_id');
        $transaction = Mage::getModel('securetrading_stpp/transaction')->load($tid);
        
        Mage::register('current_transaction', $transaction);
        
        $tRef = $transaction->getTransactionReference() ?: 'No Ref';
        
        $this
            ->_title($this->__('Sales'))
            ->_title($this->__('SecureTrading Transactions'))
            ->_title('#' . $transaction->getTransactionId() . ' (' . $tRef . ')');
                
        $this->loadLayout()->_setActiveMenu('sales/securetrading_transactions');
        $this->renderLayout();
    }
}