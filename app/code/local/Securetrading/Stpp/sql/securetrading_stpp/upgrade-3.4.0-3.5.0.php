<?php

$installer = $this;
$installer->startSetup();

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

$data = array(
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

$installer->endSetup();