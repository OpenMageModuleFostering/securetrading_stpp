<?php

class Securetrading_Stpp_Model_Adminhtml_System_Config_Backend_Multiselect extends Mage_Core_Model_Config_Data {
    protected function _afterLoad() {
        if (!is_array($this->getValue())) {
            $this->setValue(unserialize($this->getValue()));
        }
    }

    protected function _beforeSave() {
        if (is_array($this->getValue())) {
            $this->setValue(serialize($this->getValue()));
        }
    }
}