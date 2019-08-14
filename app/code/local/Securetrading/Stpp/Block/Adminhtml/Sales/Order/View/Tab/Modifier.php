<?php

class Securetrading_Stpp_Block_Adminhtml_Sales_Order_View_Tab_Modifier extends Mage_Core_Block_Abstract {
    protected function _prepareLayout() {
        if ($this->getLayout()->getBlock('sales_order_tabs')->getOrder()->getPayment()->getMethodInstance()->getIsSecuretradingPaymentMethod()) {
            $this->getLayout()->getBlock('sales_order_tabs')->addTab('securetrading_order_transactions', 'securetrading_stpp/adminhtml_sales_order_view_tab_transactions');
        }
    }
}