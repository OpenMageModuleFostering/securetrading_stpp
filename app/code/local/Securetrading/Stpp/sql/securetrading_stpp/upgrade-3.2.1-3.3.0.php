<?php

$installer = $this;
$installer->startSetup();
Mage::getConfig()->loadDb();

$connectionData = Mage::getModel('core/config_data')->getCollection()->addFieldToFilter('path', array(
		array('like' => 'payment/securetrading_stpp_direct/ws_%'),
		array('like' => 'payment/securetrading_stpp_direct/stapi_%'),
		array('eq' => 'payment/securetrading_stpp_direct/connection')
));

$transactionsearchCollection = Mage::getModel('core/config_data')->getCollection()->addFieldToFilter('path', array('like' => 'payment/securetrading_stpp_transactionsearch/%'));

foreach($transactionsearchCollection as $entry) {
	foreach(array('direct', 'redirect') as $partOfNewKey) {
		$newPath = 'payment/securetrading_stpp_' . $partOfNewKey . '/transactionsearch_' . array_pop(explode('/',$entry->getPath()));
		Mage::getConfig()->saveConfig($newPath,$entry->getValue(),$entry->getScope(),$entry->getScopeId());
		$entry->delete();
	}
}

foreach($connectionData as $entry) {
	$newPath = 'payment/securetrading_stpp_redirect/' . array_pop(explode('/', $entry->getPath()));
	Mage::getConfig()->saveConfig($newPath,$entry->getValue(),$entry->getScope(),$entry->getScopeId());
}

$installer->endSetup();