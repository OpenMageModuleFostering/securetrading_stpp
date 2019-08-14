<?php

class Magento_Utility_Log_Writer implements Stpp_Utility_Log_WriterInterface {
    public function log($message) {
        Mage::log($message, null, 'securetrading.log');
    }
}