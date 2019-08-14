<?php

$installer = $this;
$this->startSetup();

// START - Combined install-3.0.0.php to upgrade-3.4.0-3.5.0.php

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
    array(
        'type_id' => 'refund',
        'type_name' => 'Refund',
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
    ->addColumn('account_type_description', Varien_Db_Ddl_Table::TYPE_TEXT, 30, array(
        'nullable' => true,
        ), 'Account Type Description')
    ->addColumn('last_updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        'nullable' => false,
        ), 'Last Updated At')
    ->addForeignKey($installer->getFkName('securetrading_stpp/transactions', 'order_id', 'sales/order', 'entity_id'),
        'order_id', $installer->getTable('sales/order'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addForeignKey($installer->getFkName('securetrading_stpp/transactions', 'parent_transaction_id', 'securetrading_stpp/transactions', 'transaction_id'),
        'parent_transaction_id', $installer->getTable('securetrading_stpp/transactions'), 'transaction_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addIndex(
      $installer->getIdxName(
        'securetrading_stpp/transactions',
	array('transaction_reference', 'order_id'),
	Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
      ),
      array('transaction_reference', 'order_id'),
      array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
    )
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
    array(
      'status' => Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_SOFORT,
      'label' => 'Pending Sofort',
    ),
    array(
      'status' => Securetrading_Stpp_Model_Payment_Abstract::STATUS_PROCESSING_SOFORT,
      'label' => 'Processing Sofort',
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
    array(
        'status' => Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_SOFORT,
        'state' => Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
        'is_default' => 0,
    ),
    array(
        'status' => Securetrading_Stpp_Model_Payment_Abstract::STATUS_PROCESSING_SOFORT,
        'state' => Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
        'is_default' => 0,
    ),
);

$statusStateTable = $installer->getTable('sales/order_status_state');
$installer->getConnection()->insertArray($statusStateTable, array('status', 'state', 'is_default'), $data);

// Billing Agreement Currency Table

$table = $installer->getConnection()
  ->newTable($installer->getTable('securetrading_stpp/billing_agreement_currency'))
  ->addColumn('agreement_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
      'unsigned' => true,
      'nullable' => false,
      'primary'  => true,
      ), 'Agreement ID')
  ->addColumn('base_currency', Varien_Db_Ddl_Table::TYPE_CHAR, 3, array(
      'nullable' => false,
      ), 'Base Currency')
  ->addForeignKey($installer->getFkName('securetrading_stpp/billing_agreement_currency', 'agreement_id', 'sales/billing_agreement', 'agreement_id'),
      'agreement_id', $installer->getTable('sales/billing_agreement'), 'agreement_id',
      Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
      )
  ->setComment('Restricts a billing agreement so that it can only be used for one curency.  If no entries in this table assume the billing agreement can be used for all currencies.')
;
$installer->getConnection()->createTable($table);

// Billing Agreement Payment Type Description Table

$table = $installer->getConnection()
  ->newTable($installer->getTable('securetrading_stpp/billing_agreement_paymenttypedescription'))
  ->addColumn('agreement_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
      'unsigned' => true,
      'nullable' => false,
      'primary' => true,
									     ), 'Agreement ID')
  ->addColumn('payment_type_description', Varien_Db_Ddl_Table::TYPE_VARCHAR, 30, array(
      'nullable' => false,
      ), 'Payment Type Description')
  ->addForeignKey($installer->getFkName('securetrading_stpp/billing_agreement_paymenttypedescription', 'agreement_id', 'sales/billing_agreement', 'agreement_id'),
    'agreement_id', $installer->getTable('sales/billing_agreement'), 'agreement_id',
    Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
  ->setComment('Stores the Secure Trading paymenttypedescription used for a Secure Trading transaction (the billing agremeent\'s reference ID)')
;
$installer->getConnection()->createTable($table);

// END - Combined install-3.0.0.php to upgrade-3.4.0-3.5.0.php

if (extension_loaded('openssl')) {
  $config = Mage::getModel('core/config');
  $config->saveConfig('payment/securetrading_stpp_redirect/site_security_password', Mage::helper('core')->encrypt(Mage::helper('securetrading_stpp')->generatePassword()));
  $config->saveConfig('payment/securetrading_stpp_redirect/ws_password', Mage::helper('core')->encrypt(Mage::helper('securetrading_stpp')->generatePassword()));
  $config->saveConfig('payment/securetrading_stpp_direct/ws_password', Mage::helper('core')->encrypt(Mage::helper('securetrading_stpp')->generatePassword()));
}

$installer->endSetup();