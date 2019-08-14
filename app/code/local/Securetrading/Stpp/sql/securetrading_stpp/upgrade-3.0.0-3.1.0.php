<?php

$installer = $this;
$this->startSetup();

$installer->getConnection()->dropIndex(
	$installer->getTable('securetrading_stpp/transactions'),
	$installer->getIdxName('securetrading_stpp/transactions', array('transaction_reference'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
);

$installer->getConnection()->addIndex(
	$installer->getTable('securetrading_stpp/transactions'),
	$installer->getIdxName(
		'securetrading_stpp/transactions',
		array('transaction_reference', 'order_id'),
		Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
	),
    array('transaction_reference', 'order_id'),
	Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
);

$installer->endSetup();