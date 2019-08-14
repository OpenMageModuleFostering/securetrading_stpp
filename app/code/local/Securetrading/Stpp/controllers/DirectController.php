<?php

class Securetrading_Stpp_DirectController extends Mage_Core_Controller_Front_Action {
    public function returnAction() {
        $result = Mage::getModel('securetrading_stpp/payment_direct')->run3dAuth();
        
        if ($result) {
            $path = 'checkout/onepage/success';
            $arguments = array();
        }
        else {
            $path = 'checkout/onepage/index';
            $arguments = array();
        }
        
        $queryArgs = array('url' => Mage::getUrl($path, $arguments));
        $this->_redirect('securetrading/payment/location', array('_query' => $queryArgs));
    }
}