<?php

$installer = $this;
$this->startSetup();

$logFilename = 'securetrading_stpp_320_321_upgrade.log';

$collection = Mage::getModel('sales/order_invoice')->getCollection();
$collection->addFieldToFilter('transaction_id', array('null' => 'dummyvalue'));

foreach($collection as $invoice) {
	$orderId = $invoice->getOrderId();
	$order = Mage::getModel('sales/order')->load($orderId);

	if (!$order->getIsSecuretradingPaymentMethod()) {
	  continue;
	}

	$transactions = Mage::getModel('securetrading_stpp/transaction')->findTransactions($orderId, Securetrading_Stpp_Model_Transaction_Types::TYPE_AUTH);
	
	if (count($transactions) !== 1) {
	  Mage::log(sprintf('Skipping invoice "%s".', $invoice->getId()), null, $logFilename, true);
	  continue;
	}

	$invoice->setTransactionId($transactions[0]->getTransactionReference());
	$invoice->save();
	
	Mage::log(sprintf('Updated invoice "%s" with transaction reference "%s".', $invoice->getId(), $transactions[0]->getTransactionReference()), null, $logFilename, true);
}

$installer->endSetup();