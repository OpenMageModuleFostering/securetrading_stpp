<?php

class Securetrading_Stpp_RedirectController extends Mage_Core_Controller_Front_Action {
    public function redirectAction() {
        Mage::getModel('securetrading_stpp/payment_redirect')
            ->log(sprintf('In %s.', __METHOD__))
            ->runRedirect();
        
        $path = 'checkout/onepage/success';
        $arguments = array();
        
        $storeId = $this->getRequest()->get('storeid');
        
        $queryArgs = array('url' => Mage::getModel('core/url')->setStore($storeId)->getUrl($path, $arguments));
        $this->_redirect('securetrading/payment/location', array('_query' => $queryArgs));
    }
    
    public function notificationAction() {
        $model = Mage::getModel('securetrading_stpp/payment_redirect')
            ->log(sprintf('In %s.', __METHOD__))
            ->runNotification();
        exit($this->__("Notification complete."));
    }
}