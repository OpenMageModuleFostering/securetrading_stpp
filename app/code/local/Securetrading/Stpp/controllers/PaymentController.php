<?php

class Securetrading_Stpp_PaymentController extends Mage_Core_Controller_Front_Action {
    public function locationAction() {
        $path = $this->getRequest()->getParam('path');
        $args = $this->getRequest()->getParam('args');
        Mage::register(Securetrading_Stpp_Block_Payment_Location::PATH_REGISTRY_KEY, $path);
        Mage::register(Securetrading_Stpp_Block_Payment_Location::ARGS_REGISTRY_KEY, $args);
        $this->loadLayout();
        $this->renderLayout();
    }
}