<?php

class Securetrading_Stpp_Block_Adminhtml_Sales_Transactions_Type_Default extends Securetrading_Stpp_Block_Adminhtml_Sales_Transactions_Type_Abstract {
	protected function _prepareLayout() {
        $requestBlock = $this->getLayout()->createBlock('securetrading_stpp/adminhtml_sales_transactions_data_request');
        $responseBlock = $this->getLayout()->createBlock('securetrading_stpp/adminhtml_sales_transactions_data_response');
        
        $this->setChild('request', $requestBlock);
        $this->setChild('response', $responseBlock);
    }
    
    protected function _toHtml() {
        $html = '';
        $html .= $this->_addToHtml($this->__("Request"), $this->getChildHtml('request'));
        $html .= $this->_addToHtml($this->__("Response"), $this->getChildHtml('response'));
        return $html;
    }
}