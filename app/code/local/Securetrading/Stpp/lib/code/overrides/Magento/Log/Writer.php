<?php

class Magento_Log_Writer implements Stpp_Utility_Log_WriterInterface {
	protected $_filename = '';
	
	public function __construct($filename) {
		$this->_filename = $filename;
	}
	
    public function log($message) {
        Mage::log($message, null, $this->_filename . '.log');
    }
}