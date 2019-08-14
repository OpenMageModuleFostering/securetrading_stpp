<?php

class Securetrading_Stpp_Block_Adminhtml_Sales_Transactions_Type_Transactionupdate extends Securetrading_Stpp_Block_Adminhtml_Sales_Transactions_Type_Abstract {
    protected function _prepareLayout() {
        $filtersBlock = $this->getLayout()->createBlock('securetrading_stpp/adminhtml_sales_transactions_type_transactionupdate_filters');
    	$updatesBlock = $this->getLayout()->createBlock('securetrading_stpp/adminhtml_sales_transactions_type_transactionupdate_updates');
    	$responseBlock = $this->getLayout()->createBlock('securetrading_stpp/adminhtml_sales_transactions_data_response');
        
        $this->setChild('filters', $filtersBlock);
        $this->setChild('updates', $updatesBlock);
        $this->setChild('response', $responseBlock);
    }
    
    protected function _toHtml() {
        $html = '';
        $html .= $this->_addToHtml($this->__("Request - Filters"), $this->getChildHtml('filters'));
        $html .= $this->_addToHtml($this->__("Request - Updates"), $this->getChildHtml('updates'));
        $html .= $this->_addToHtml($this->__("Response"), $this->getChildHtml('response'));
        return $html;
    }
}