<?php

class Securetrading_Stpp_Block_Adminhtml_Widget_Grid_Column_Renderer_Parenttransactionreference extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    protected function _getValue(Varien_Object $row) {
        $ptr = '';
        if ($row->getParentTransactionId()) {
            $temp = Mage::getModel('securetrading_stpp/transaction')->load($row->getParentTransactionId())->getTransactionReference();
            if (!empty($temp)) {
                $ptr = $temp;
            }
        }
        return $ptr;
    }
}