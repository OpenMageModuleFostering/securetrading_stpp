<?php

class Securetrading_Stpp_DirectController extends Mage_Core_Controller_Front_Action {
    public function returnAction() {
        $result = Mage::getModel('securetrading_stpp/payment_direct')->run3dAuth();
                
        if ($result) {
            $path = 'checkout/onepage/success';
        }
        else {
            $path = 'checkout/cart';
        }
        
        $queryArgs = array('path' => $path);
        $this->_redirect('securetrading/payment/location', array('_query' => $queryArgs));
    }
}