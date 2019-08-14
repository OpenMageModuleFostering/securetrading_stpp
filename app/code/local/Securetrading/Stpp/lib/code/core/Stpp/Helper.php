<?php

class Stpp_Helper extends Stpp_Component_Abstract implements Stpp_HelperInterface {
    public function getFilteredCardTypes($use3dSecure, $enabledCardTypes = array()) {
        $allCardTypes = Stpp_Types::getCardTypes();
        $filteredCardTypes = array();
        
        foreach($enabledCardTypes as $cardKey) {
            if (!array_key_exists($cardKey, $allCardTypes)) {
                continue;
            }
            if ($cardKey === Stpp_Types::CARD_MAESTRO && !$use3dSecure) {
                continue;
            }
            $filteredCardTypes[$cardKey] = $allCardTypes[$cardKey];
        }
        
        if (empty($filteredCardTypes)) {
            throw new Stpp_Exception($this->__('No payment types are available for selection.'));
        }
        return $filteredCardTypes;
    }
    
    public function getCcLast4($cardNumber) {
        if (strlen($cardNumber) <= 4) {
            return $cardNumber;
        }
        return substr($cardNumber, strlen($cardNumber)-4, 4);
    }
}