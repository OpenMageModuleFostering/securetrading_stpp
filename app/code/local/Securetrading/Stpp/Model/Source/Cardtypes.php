<?php

class Securetrading_Stpp_Model_Source_Cardtypes {
    public function toOptionArray()
    {
        $cardTypes = Mage::getModel('securetrading_stpp/integration')->getCardTypes();
        $newArray = array();
        
        foreach($cardTypes as $cardKey => $cardString) {
            $newArray[] = array(
                'value' => $cardKey,
                'label' => $cardString,
            );
        }
        return $newArray;
    }
}