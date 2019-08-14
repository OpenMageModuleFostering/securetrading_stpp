<?php

class Securetrading_Stpp_PaymentController extends Mage_Core_Controller_Front_Action {
    public function locationAction() {
        $url = $this->getRequest()->getParam('url');
        Mage::register(Securetrading_Stpp_Block_Payment_Location::URL_REGISTRY_KEY, $url);
        $this->loadLayout();
        $this->renderLayout();
    }
}