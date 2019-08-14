<?php

class Securetrading_Stpp_Model_Source_Cardtypes {
  public function toOptionArray()
  {
    $integration = Mage::getModel('securetrading_stpp/integration');
    $cardTypes = $integration->getCardTypes();
    unset($cardTypes[$integration->getSofortName()]);
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