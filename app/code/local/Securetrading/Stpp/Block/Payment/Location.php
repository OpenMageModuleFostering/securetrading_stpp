<?php

class Securetrading_Stpp_Block_Payment_Location extends Mage_Page_Block_Html {
    const PATH_REGISTRY_KEY = 'securetrading_stpp_block_payment_location_path';
    const ARGS_REGISTRY_KEY = 'securetrading_stpp_block_payment_location_args';
    
    public function getRedirectUrl() {
    	$redirectPath = Mage::registry(self::PATH_REGISTRY_KEY);
    	$redirectArgs = Mage::registry(self::ARGS_REGISTRY_KEY);
    	$redirectUrl = Mage::getUrl($redirectPath, $redirectArgs);
    	return $redirectUrl;
    }
}