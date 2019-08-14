<?php

class Securetrading_Stpp_Block_Adminhtml_Sales_Transactions_Children extends Securetrading_Stpp_Block_Adminhtml_Sales_Transactions_Grid {
    public function _construct() {
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
    }
    
    protected function _prepareCollection() {
        $transaction = Mage::registry('current_transaction');
        
        if ($transaction === null) {
            throw new Stpp_Exception(Mage::helper('securetrading_stpp')->__('A transaction has not been set.'));
        }
        
        $collection = Mage::getResourceModel('securetrading_stpp/transaction_collection');
        $collection->addFieldToFilter('parent_transaction_id', $transaction->getTransactionId());
        $this->setCollection($collection);
    }
}