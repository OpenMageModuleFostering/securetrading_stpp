<?php

class Securetrading_Stpp_Block_Adminhtml_Sales_Order_View_Tab_Transactions 
    extends Securetrading_Stpp_Block_Adminhtml_Sales_Transactions_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function getTabLabel() {
        return Mage::helper('securetrading_stpp')->__('SecureTrading Transactions');
    }
    
    public function getTabTitle() {
        return Mage::helper('securetrading_stpp')->__('SecureTrading Transactions');
    }
    
    public function isHidden() {
        return false;
    }
    
    public function canShowTab() {
        return true;
    }
}