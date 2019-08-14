<?php

class Securetrading_Stpp_Model_Source_PpgVersion {
    public function toOptionArray()
    {
      return array(array('value' => '1', 'label' => '1'), array('value' => '2', 'label' => '2'));
      /*
        $settleStatuses = Mage::getModel('securetrading_stpp/integration')->getSettleStatuses();
        unset($settleStatuses['2'], $settleStatuses['3'], $settleStatuses['100']);
        
        $newArray = array();
        
        foreach($settleStatuses as $settleStatus => $settleStatusString) {
            $newArray[] = array(
                'value' => $settleStatus,
                'label' => $settleStatusString,
            );
        }
        return $newArray;
      */
    }
}