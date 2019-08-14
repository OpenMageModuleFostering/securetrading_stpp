<?php

$installer = $this;
$this->startSetup();

$data = array(
    array(
        'type_id' => 'refund',
        'type_name' => 'Refund',
    ),
);

$transactionTypesTable = $installer->getTable('securetrading_stpp/transaction_types');
$installer->getConnection()->insertArray($transactionTypesTable, array('type_id', 'type_name'), $data);

$installer->getConnection()->addColumn(
	$installer->getTable('securetrading_stpp/transactions'),
	'account_type_description',
	array(
		'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
		'length' => 30, 
		'nullable' => true,
		'comment' => 'Account Type Description',
	)
);

$installer->endSetup();