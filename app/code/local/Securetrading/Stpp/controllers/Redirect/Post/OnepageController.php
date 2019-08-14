<?php

class Securetrading_Stpp_Redirect_Post_OnepageController extends Securetrading_Stpp_Controller_Redirect_Post_Abstract {
    protected function _getOrderIncrementIds() {
    	return array(Mage::getModel('checkout/session')->getLastRealOrderId());
    }
}