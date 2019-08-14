<?php

class Securetrading_Stpp_Block_Adminhtml_Sales_Transactions_Data_Request extends Securetrading_Stpp_Block_Adminhtml_Sales_Transactions_Data_Abstract {
	protected function _getGridData() {
        return $this->_getTransaction()->getRequestData();
    }
}