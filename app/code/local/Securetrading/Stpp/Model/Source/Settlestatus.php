<?php

class SecureTrading_Stpp_Model_Source_Settlestatus {
    public function toOptionArray()
    {
        $settleStatuses = Mage::getModel('securetrading_stpp/integration')->getSettleStatuses();
        unset($settleStatuses['2'], $settleStatuses['3']);
        
        $newArray = array();
        
        foreach($settleStatuses as $settleStatus => $settleStatusString) {
            $newArray[] = array(
                'value' => $settleStatus,
                'label' => $settleStatusString,
            );
        }
        return $newArray;
    }
}