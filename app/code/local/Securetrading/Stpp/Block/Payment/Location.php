<?php

class Securetrading_Stpp_Block_Payment_Location extends Mage_Page_Block_Html {
    const URL_REGISTRY_KEY = 'securetrading_stpp_block_payment_location_url';
    
    public function getRedirectUrl() {
        $redirectUrl = Mage::registry(self::URL_REGISTRY_KEY);
        if (!$redirectUrl) {
            $redirectUrl = Mage::getUrl();
        }
        return $redirectUrl;
    }
}