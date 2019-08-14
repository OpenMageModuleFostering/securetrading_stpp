<?php

class Securetrading_Stpp_Model_Source_Paymentaction
{
    public function toOptionArray()
    {
        return array(
            array('value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE, 'label' => 'Authorize Only'),
            array('value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE, 'label' => 'Authorize & Capture'),
        );      
    }
}