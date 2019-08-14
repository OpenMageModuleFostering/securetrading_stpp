<?php

class Securetrading_Stpp_Model_Source_Settleduedate {
    public function toOptionArray()
    {
        $settleDueDates = Mage::getModel('securetrading_stpp/integration')->getSettleDueDates();
        $newArray = array();
        
        foreach($settleDueDates as $date => $dateString) {
            $newArray[] = array(
                'value' => $date,
                'label' => $dateString,
            );
        }
        return $newArray;
    }
}