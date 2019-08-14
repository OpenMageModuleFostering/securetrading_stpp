<?php

class Securetrading_Stpp_Block_Adminhtml_Sales_Transactions_Type_Transactionupdate_Updates extends Securetrading_Stpp_Block_Adminhtml_Sales_Transactions_Data_Abstract {
    protected function _getGridData() {
    	$requestData = $this->_getTransaction()->getRequestData();
    	return $requestData['updates'];
    }
}