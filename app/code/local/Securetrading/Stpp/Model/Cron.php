<?php

class Securetrading_Stpp_Model_Cron {
  const EXPIRED_ORDER_HOURS = 24;

  const SOFORT_HOURS_UPDATED_AFTER = 12;
  
  public function abandonedOrderCleanup() {
    $expiredOrderTime = date('Y-m-d H:i:s', strtotime('-' . self::EXPIRED_ORDER_HOURS . ' hours'));
    $orderStatuses = array(
      Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_PPAGES,
      Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_3DSECURE,
      Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_SOFORT,
    );
    
    $collection = Mage::getResourceModel('sales/order_collection')
      ->addFieldToFilter('status', array('in', $orderStatuses))
      ->addFieldToFilter('updated_at', array('lt' => $expiredOrderTime))
    ;
    
    foreach($collection->getItems() as $order) {
      if (!$order->getPayment()->getMethodInstance()->getIsSecuretradingPaymentMethod()) {
	continue;
      }
      
      foreach($order->getInvoiceCollection() as $invoice) { // ST_#3503
	$invoice->cancel()->save();
      }

      $order->cancel();
      $order->save();
    }
  }
  
  public function requestTableCleanup() {
    $collection = Mage::getModel('securetrading_stpp/payment_redirect_request')->getCollection();
    $collection->join(array('orders' => 'sales/order'),'orders.increment_id = main_table.order_increment_id', array('status'));
    $collection->addFieldToFilter('orders.status', array('neq' => Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_PPAGES));
    
    foreach($collection->getItems() as $item) {
      $item->delete();
    }
  }
  
  public function updateOldOrders() {
    $csvData = $this->_getOldOrders();
    $this->_updateOldOrders($csvData);
  }

  public function updateSofortTransactions() {
    $csvData = $this->_getSofortTransactions();
    $this->_updateSofortTransactions($csvData);
  }

  protected function _getOldOrders() {
    $startDate = date("Y-m-d", strtotime("-30 day"));
    $endDate = date("Y-m-d", strtotime("-8 day"));
    
    $models = array(
      Mage::getModel('securetrading_stpp/payment_redirect'),
      Mage::getModel('securetrading_stpp/payment_direct'),
    );
    $filters = array('requesttypedescriptions' => array('AUTH'));
    $additionalFields = array('orderreference', 'settlestatus');
    
    $csvData = Mage::helper('securetrading_stpp')->getCsvData($models, $filters, $additionalFields, $startDate, $endDate);
    return $csvData;
  }

  protected function _updateOldOrders(array $csvData) {
    $errorPending = array();
    $errorOther = array();
    foreach($csvData as $transactionArray) {
      $transactionReference = $transactionArray[0];
      $orderIncrementId = $transactionArray[1];
      $settleStatus = $transactionArray[2];
      
      $orderIds = Mage::helper('securetrading_multishipping')->getRelatedMultishippingOrders($orderIncrementId);
      
      if ($orderIds === false) {
	Mage::getModel('securetrading_stpp/integration')->getDebugLog()->log(sprintf(Mage::helper('securetrading_stpp')->__('Update old orders: The order with ID "%s" and transaction reference "%s" could not be loaded.', $orderIncrementId, $transactionReference)));
	continue;
      }
      
      switch($settleStatus) {
      case '2':
      case '3':
	Mage::helper('securetrading_stpp')->updateOrders($orderIds, true);
	break;
      case '100':
	Mage::helper('securetrading_stpp')->updateOrders($orderIds, false);
	break;
      case '0':
      case '1':
	Mage::getModel('securetrading_stpp/integration')->getDebugLog()->log(Mage::helper('securetrading_stpp')->__('Update old orders: Order "%s" should not still be pending settlement.', $orderIncrementId));
	$errorPending[] = $transactionReference;
	break;
      default:
	$errorOther[] = $transactionReference;
      }
    }
    if (!empty($errorPending) || !empty($errorOther)) {
      throw new Exception(Mage::helper('securetrading_stpp')->__('Update old orders: errors.  These transaction references should not still be pending settlement: %s.  These transaction references had an unexpected settle status: %s.', implode(', ', $errorPending), implode(', ', $errorOther)));
    }
  }

  protected function _getSofortTransactions() {
    $collection = Mage::getModel('sales/order')->getCollection();
    $collection->addFieldToFilter('status', array('eq' => Securetrading_Stpp_Model_Payment_Abstract::STATUS_PROCESSING_SOFORT));
    $collection->addFieldToSelect('increment_id');
    $orderIncrementIds = array();
    foreach($collection as $order) {
      $orderIncrementIds[] = $order->getIncrementId();
    }

    $startDate = date('Y-m-d', strtotime('-30 day'));//TODO - the crons have arbitrary limits on start dates.  E.g. merchant runs module for year, doesn't configure cron.  then sets it up.  not all transactions will be retrieved and updated.  two potential solutions: allow cron to be manually fired from admin area with no limits, or make start dates configurable.
    $endDate = date('Y-m-d');
    
    $models = array(Mage::getModel('securetrading_stpp/payment_redirect'));
    $filters = array('orderreferences' => $orderIncrementIds);
    $additionalFields = array('orderreference', 'settlestatus');
    $csvData = Mage::helper('securetrading_stpp')->getCsvData($models, $filters, $additionalFields, $startDate, $endDate);

    // take order to SOFORT auth page - it is now pending sofort.  if decline - customer can try again.  new ST transaction made for each try.  all one order id.  so multiple transactionreferences - some cancelled, one may be settled - for each order increment id.  handle this case - if there is a 100 then the order was a success.
    $finalCsvData = array();
    foreach($csvData as $row) {
      $orderIncrementId = $row[1];
      if (array_key_exists($orderIncrementId, $finalCsvData) && $finalCsvData[$orderIncrementId][2] === '100') { // If a transaction already exists for this order increement ID and is settlestatus 100, continue so that the transaction isn't set back to a 3 again (if a 3 also exists)
	continue;
      }
      $finalCsvData[$orderIncrementId] = $row;
    }
    return $finalCsvData;
  }

  protected function _updateSofortTransactions(array $csvData) {
    $errors = array();
    foreach($csvData as $transactionArray) {
      $transactionReference = $transactionArray[0];
      $orderIncrementId = $transactionArray[1];
      $settleStatus = $transactionArray[2];
      $orderIds = Mage::helper('securetrading_multishipping')->getRelatedMultishippingOrders($orderIncrementId);
      
      if ($orderIds === false) {
	Mage::getModel('securetrading_stpp/integration')->getDebugLog()->log(sprintf(Mage::helper('securetrading_stpp')->__('Update old orders: The order with ID "%s" and transaction reference "%s" could not be loaded.', $orderIncrementId, $transactionReference)));
	continue;
      }
      
      switch($settleStatus) {
      case '100':
	Mage::helper('securetrading_stpp')->updateOrders($orderIds, false);
	foreach($orderIds as $orderId) {
	  $order = Mage::getModel('sales/order')->load($orderId);
	  Mage::helper('securetrading_stpp')->registerSuccessfulOrderAfterExternalRedirect($order, $settleStatus);
	}
	break;
      case '3':
	Mage::helper('securetrading_stpp')->updateOrders($orderIds, true);
	break;
      case '10':
      default:
	$errors[$orderIncrementId] = $settleStatus;
	break;
      }
    }

    if (!empty($errors)) {
      $e = array();
      foreach($errors as $incrementId => $sStatus) {
	$e[] =  $incrementId . ':' . $sStatus;
      }
      throw new Exception(Mage::helper('securetrading_stpp')->__('Update sofort orders: errors.  These orders had incorrect settle statuses: %s.', implode(', ', $e)));
    }
  }
}