<?php

class Securetrading_Stpp_Redirect_Post_MultishippingController extends Securetrading_Stpp_Controller_Redirect_Post_Abstract {
    protected function _getOrderIncrementIds() {
    	return Mage::getSingleton('core/session')->getOrderIds();
    }
}