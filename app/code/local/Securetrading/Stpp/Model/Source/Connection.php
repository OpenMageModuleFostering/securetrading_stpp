<?php

class Securetrading_Stpp_Model_Source_Connection {
    public function toOptionArray()
    {
        $connections = Mage::getModel('securetrading_stpp/integration')->getConnections();
        $newArray = array();
        
        foreach($connections as $key => $name) {
            $newArray[] = array(
                'value' => $key,
                'label' => $name,
            );
        }
        return $newArray;
    }
}