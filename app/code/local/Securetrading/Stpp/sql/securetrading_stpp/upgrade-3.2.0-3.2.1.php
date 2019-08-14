<?php

$installer = $this;
$this->startSetup();

$collection = Mage::getModel('sales/order_invoice')->getCollection();
$collection->addFieldToFilter('transaction_id', array('null' => 'dummyvalue'));

foreach($collection as $invoice) {
	$orderId = $invoice->getOrderId();
	$order = Mage::getModel('sales/order')->load($orderId);
	
	$transactions = Mage::getModel('securetrading_stpp/transaction')->findTransactions($orderId, Securetrading_Stpp_Model_Transaction_Types::TYPE_AUTH);
		
	if (count($transactions) !== 1) {
		throw new Exception(sprintf("%s transactions were returned.", (int) count($transactions)));
	}
	
	$invoice->setTransactionId($transactions[0]->getTransactionReference());
	$invoice->save();
}

$installer->endSetup();