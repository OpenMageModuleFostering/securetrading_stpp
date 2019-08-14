<?php

$installer = $this;
$this->startSetup();

// Request data table:

$table = $installer->getConnection()
    ->newTable($installer->getTable('securetrading_stpp/requests'))
    ->addColumn('request_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
        ), 'Request ID')
    ->addColumn('order_increment_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 50, array(
        'nullable' => false,
        ), 'Order Increment ID')
    ->addColumn('request', Varien_Db_Ddl_Table::TYPE_BLOB, null, array(
        'nullable' => false,
        ), 'Request (Serialized)')
    ->addForeignKey($installer->getFkName('securetrading_stpp/requests', 'order_increment_id', 'sales/order', 'increment_id'),
        'order_increment_id', $installer->getTable('sales/order'), 'increment_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addIndex($installer->getIdxName('securetrading_stpp/requests', array('order_increment_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
        array('order_increment_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
    ->setComment('Stores Secure Trading STPP Payment Pages request data.  This data is mapped to the response object in the notification.')
;
$installer->getConnection()->createTable($table);

// Notifications table:

$table = $installer->getConnection()
    ->newTable($installer->getTable('securetrading_stpp/notifications'))
    ->addColumn('notification_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Notification ID')
    ->addColumn('notification_reference', Varien_Db_Ddl_Table::TYPE_CHAR, 11, array(
        'nullable' => false,
        ), 'Notification Reference')
    ->addIndex($installer->getIdxName('securetrading_stpp/notifications', array('notification_reference')),
        array('notification_reference'))
    ->setComment('Stores Payment Pages notification references.')
;
$installer->getConnection()->createTable($table);

// Transaction types table:

$table = $installer->getConnection()
    ->newTable($installer->getTable('securetrading_stpp/transaction_types'))
    ->addColumn('type_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 32, array(
        'nullable' => false,
        'primary' => true,
    ), 'ID')
    ->addColumn('type_name', Varien_Db_Ddl_Table::TYPE_VARCHAR, 32, array(
        'nullable' => false,
        ), 'Name')
    ->setComment('Secure Trading API transaction types (e.g. AUTH, RISKDEC)')
;
$installer->getConnection()->createTable($table);

$data = array(
    array(
        'type_id' => 'auth',
        'type_name' => 'Auth',
    ),
    array(
        'type_id' => 'threedquery',
        'type_name' => '3D Query',
    ),
    array(
        'type_id' => 'riskdec',
        'type_name' => 'Risk Decision',
    ),
    array(
        'type_id' => 'cardstore',
        'type_name' => 'Card Store',
    ),
    array(
        'type_id' => 'error',
        'type_name' => 'Error',
    ),
    array(
        'type_id' => 'transactionupdate',
        'type_name' => 'Transaction Update',
    ),
    array(
        'type_id' => 'transactionquery',
        'type_name' => 'Transaction Query',
    ),
    array(
        'type_id' => 'accountcheck',
        'type_name' => 'Account Check',
    ),
);

$transactionTypesTable = $installer->getTable('securetrading_stpp/transaction_types');
$installer->getConnection()->insertArray($transactionTypesTable, array('type_id', 'type_name'), $data);

// Transactions table:

$table = $installer->getConnection()
    ->newTable($installer->getTable('securetrading_stpp/transactions'))
    ->addColumn('transaction_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => false,
        'primary' => true,
        'identity' => true,
        'unsigned' => true,
    ), 'Transaction ID')
    ->addColumn('transaction_reference', Varien_Db_Ddl_Table::TYPE_VARCHAR, 25, array(
        'nullable' => true, //e.g. for PPG saving data before post.
    ), 'Transaction Reference')
    ->addColumn('parent_transaction_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable' => true, //e.g. if transaction has no parent
        'unsigned' => true,
    ), 'Parent Transaction ID')
    ->addColumn('request_type', Varien_Db_Ddl_Table::TYPE_VARCHAR, 32, array(
        'nullable' => false,
        ), 'Request Type')
    ->addColumn('response_type', Varien_Db_Ddl_Table::TYPE_VARCHAR, 32, array(
        'nullable' => false,
        ), 'Response Type')
    ->addColumn('request_data', Varien_Db_Ddl_Table::TYPE_BLOB, null, array(
        'nullable' => false,
        ), 'Request Data')
    ->addColumn('response_data', Varien_Db_Ddl_Table::TYPE_BLOB, null, array(
        'nullable' => false,
        ), 'Response Data')
    ->addColumn('error_code', Varien_Db_Ddl_Table::TYPE_VARCHAR, 5, array(
        'nullable' => false,
        ), 'Error Code')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        ), 'Order ID')
    ->addColumn('last_updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable' => false,
        ), 'Last Updated At')
    ->addForeignKey($installer->getFkName('securetrading_stpp/transactions', 'order_id', 'sales/order', 'entity_id'),
        'order_id', $installer->getTable('sales/order'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addForeignKey($installer->getFkName('securetrading_stpp/transactions', 'parent_transaction_id', 'securetrading_stpp/transactions', 'transaction_id'),
        'parent_transaction_id', $installer->getTable('securetrading_stpp/transactions'), 'transaction_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addIndex($installer->getIdxName('securetrading_stpp/transactions', array('transaction_reference'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
        array('transaction_reference'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
    ->setComment('Stores Secure Trading STPP transaction details.')
;
$installer->getConnection()->createTable($table);

// Order statuses/states:

$data = array(
    array(
        'status' => 'authorized',
        'label' => 'Authorized',
    ),
    array(
        'status' => 'suspended',
        'label' => 'Suspended',
    ),
    array(
        'status' => 'pending_ppages',
        'label' => 'Payment Pages',
    ),
    array(
        'status' => 'pending_3dsecure',
        'label' => '3D Secure',
    ),
);

$statusTable = $installer->getTable('sales/order_status');
$installer->getConnection()->insertArray($statusTable, array('status', 'label'), $data);

$data = array(
    array(
        'status' => 'authorized',
        'state' => 'processing',
        'is_default' => 0,
    ),
    array(
        'status' => 'suspended',
        'state' => 'payment_review',
        'is_default' => 0,
    ),
    array(
        'status' => 'pending_ppages',
        'state' => 'pending_payment',
        'is_default' => 0,
    ),
    array(
        'status' => 'pending_3dsecure',
        'state' => 'pending_payment',
        'is_default' => 0,
    ),
);

$statusStateTable = $installer->getTable('sales/order_status_state');
$installer->getConnection()->insertArray($statusStateTable, array('status', 'state', 'is_default'), $data);

$installer->endSetup();