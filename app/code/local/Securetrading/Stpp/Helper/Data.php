<?php

class Securetrading_Stpp_Helper_Data extends Mage_Core_Helper_Abstract {
	public function orderIsSuccessful($orderIncrementId) {
		$order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
		$successful = in_array($order->getState(), array(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW), true);
		return $successful;
	}
	
	public function ordersAreSuccessful($orderIncrementIds) {
		foreach($orderIncrementIds as $orderIncrementId) {
			if ($this->orderIsSuccessful($orderIncrementId)) {
				continue;
			}
			return false;
		}
		return true;
	}
	
	protected function _getCsvData($startDate, $endDate) {
		$csvData = array();
		foreach(Mage::app()->getStores() as $storeId => $store) {
			$models = array(
				Mage::getModel('securetrading_stpp/payment_redirect')->setStore($storeId)->getIntegration(),
				Mage::getModel('securetrading_stpp/payment_direct')->setStore($storeId)->getIntegration(),
			);
			foreach($models as $integration) {
				try {
					$siteReference = $store->getConfig('payment/' . $integration->getPaymentMethod()->getCode() . '/site_reference');
					$csvData[] = $integration->newTransactionSearch()
						->setStartDate($startDate)
						->setEndDate($endDate)
						->setSiteReferences($siteReference)
						->addFilter('requesttypedescriptions', 'AUTH')
						->addOptionalField('orderreference')
						->addOptionalField('settlestatus')
						->getCsvData()
					;
				}
				catch (Exception $e) {
					Mage::logException($e);
					continue;
				}
			}
		}
		
		$transactionReferences = array();
		$finalCsvData = array();
		foreach($csvData as $postResponse) {
			foreach($postResponse as $oneRecord) {
				if (in_array($oneRecord[0], $transactionReferences)) {
					continue;
				}
				$transactionReferences[] = $oneRecord[0];
				$finalCsvData[] = $oneRecord;
			}
		}
		return $finalCsvData;
	}
	
	public function updateOldOrders($startDate, $endDate) {
		$csvData = $this->_getCsvData($startDate, $endDate);
		
		foreach($csvData as $transactionArray) {
			$transactionReference = $transactionArray[0];
			$orderIncrementId = $transactionArray[1];
			$settleStatus = $transactionArray[2];
	
			$multishippingModel = Mage::getModel('securetrading_multishipping/order_set_factory');
			$orderId = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId)->getId();
	
			if (!$orderId) {
				Mage::getModel('securetrading_stpp/integration')->getDebugLog()->log(sprintf(Mage::helper('securetrading_stpp')->__('Update old orders: The order with ID "%s" and t\
ransaction reference "%s" could not be loaded.', $orderIncrementId, $transactionReference)));
				continue;
			}
			
			if ($multishippingModel->orderBelongsToAnySet($orderId)) {
				$orderIds = $multishippingModel->getOrderIdsInSameSet($orderId, false);
			} else {
				$orderIds = array($orderId);
			}
			
			switch($settleStatus) {
				case '2':
				case '3':
					$this->_updateOrders($orderIds, true);
					break;
				case '100':
					$this->_updateOrders($orderIds, false);
					break;
				case '0':
				case '1':
					throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('Orders "%s" should not still be pending settlement.'), $orderIncrementId));
					break;
				default:
					throw new Exception(sprintf(Mage::helper('securetrading_stpp')->__('Order "%s" has an unhandled settle status: "%s".'), $orderIncrementId, $settleStatus));
			}
		}
	}
	
	protected function _updateOrders(array $orderIds, $cancelOrders = false) {
		foreach($orderIds as $orderId) {
			$order = Mage::getModel('sales/order')->load($orderId);
			$transactions = Mage::getResourceModel('sales/order_payment_transaction_collection')->addOrderIdFilter($order->getId())->addPaymentIdFilter($order->getPayment()->getId())->addFieldToFilter('is_closed', array('neq' => '1'));
	
			foreach($transactions as $transaction) {
				$transaction->setOrderPaymentObject($order->getPayment())->setIsClosed(true)->save();
			}
			if ($cancelOrders) {
				$order->cancel();
			}
			$order->save();
		}
	}
}