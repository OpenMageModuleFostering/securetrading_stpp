<?php

require_once(Mage::getModuleDir('controllers', 'Mage_Adminhtml') . DS . 'Sales' . DS . 'Order' . DS . 'EditController.php');

class Securetrading_Stpp_Sales_Order_EditController extends Mage_Adminhtml_Sales_Order_EditController {
    public function saveAction() {
        $paymentData = $this->getRequest()->getPost('payment');
        
        if ($paymentData && $paymentData['method'] === Mage::getModel('securetrading_stpp/payment_redirect')->getCode()) {
            return $this->_redirect('*/sales_order_create/save');
        }
        return $this->saveAction();
    }
}