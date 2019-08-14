<?php

class Securetrading_Stpp_Block_Adminhtml_Sales_Transactions extends Mage_Adminhtml_Block_Widget_Grid_Container {
    public function __construct() {
        $this->_blockGroup = 'securetrading_stpp';
        $this->_controller = 'adminhtml_sales_transactions';
        $this->_headerText = Mage::helper('securetrading_stpp')->__('Transactions');
        parent::__construct();
        $this->_removeButton('add');
    }
}