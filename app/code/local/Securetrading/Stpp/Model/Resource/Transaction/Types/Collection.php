<?php

class Securetrading_Stpp_Model_Resource_Transaction_Types_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {
    protected function _construct() {
        $this->_init('securetrading_stpp/transaction_types');
    }
    
    public function toSingleDimensionArray() {
        $array = $this->toArray();
        $finalArray = array();
        
        foreach($array['items'] as $item) {
            $finalArray[$item['type_id']] = $item['type_name'];
        }
        return $finalArray;
    }
}