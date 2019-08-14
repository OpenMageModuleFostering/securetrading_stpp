<?php

class Securetrading_Stpp_Model_Payment_Handler_Backend_Multishipping extends Securetrading_Stpp_Model_Payment_Handler_Backend_Abstract {
  protected function _getOrderIncrementIds() {
    $orderIncrementIds = $this->getOrderIncrementIds();
    if (!is_array($orderIncrementIds) || empty($orderIncrementIds)) {
      throw new Exception(Mage::helper('securetrading_stpp')->__('The order increement IDs have not been set.'));
    }
    return $orderIncrementIds;
  }
  
  protected function _prepareRefund(Mage_Sales_Model_Order_Payment $payment, $amount) {
    $partialRefundAlreadyProcessed = null;
    $orderBaseGrandTotal = null;
    $baseTotalPaid = null;
    $baseTotalRefunded = null;
      
    foreach($this->_getOrderIncrementIds() as $orderIncrementId) {
      $tempPayment = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId)->getPayment();
      
      if ($partialRefundAlreadyProcessed !== true) {
	$partialRefundAlreadyProcessed = $this->_paymentHasSuccessfulRefund($tempPayment);
      }
      
      $orderBaseGrandTotal += $tempPayment->getOrder()->getBaseGrandTotal();
      $baseTotalPaid += $tempPayment->getOrder()->getBaseTotalPaid();
      $baseTotalRefunded += $tempPayment->getOrder()->getBaseTotalRefunded();
    }

    return array(
      'partial_refund_already_processed' => $partialRefundAlreadyProcessed,
      'order_base_grand_total' => $orderBaseGrandTotal,
      'base_total_paid' => $baseTotalPaid,
      'base_total_refunded' => $baseTotalRefunded,
    );
  }

  protected function _prepareToCaptureAuthorized(Mage_Sales_Model_Order_Payment $payment) {
    $orderBaseGrandTotal = null;
    $baseAmountPaid = null;
    $baseAmountRefunded = null;
    foreach($this->_getOrderIncrementIds() as $orderIncrementId) {
      $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
      $orderBaseGrandTotal += $order->getBaseGrandTotal();
      $baseAmountPaid += $order->getPayment()->getBaseAmountPaid();
      $baseAmountRefunded += $order->getPayment()->getBaseAmountRefunded(); 
    }
    $orderBaseGrandTotal = (string) Mage::app()->getStore()->roundPrice($orderBaseGrandTotal);
    $baseAmountPaid = (string) Mage::app()->getStore()->roundPrice($baseAmountPaid);
    $baseAmountRefunded = (string) Mage::app()->getStore()->roundPrice($baseAmountRefunded);
    return array(
      'order_base_grand_total' => $orderBaseGrandTotal,
      'base_amount_paid' => $baseAmountPaid,
      'base_amount_refunded' => $baseAmountRefunded,
    );
  }
  
  protected function _canAcceptPayment(Mage_Payment_Model_Info $payment) {
    if ($payment->getSkipAcceptPayment()) {
      return false;
    }
    return true;
  }

  protected function _afterAcceptPayment(Mage_Payment_Model_Info $payment) {
    foreach($this->_getOrderIncrementIds() as $orderIncrementId) {
      $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
      $payment = $order->getPayment();
      $payment->setSkipAcceptPayment(true);
      $payment->accept();
      $order->save();
    }
  }

  protected function _canDenyPayment(Mage_Payment_Model_Info $payment) {
    if ($payment->getSkipDenyPayment()) {
      return false;
    }
    return true;
  }

  protected function _beforeDenyPayment(Mage_Payment_Model_Info $payment) {
    foreach($this->_getOrderIncrementIds() as $incrementId) {
      $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
      $orders[$order->getId()] = $order;
    }
    Mage::register(Securetrading_Stpp_Model_Actions_Direct::MULTISHIPPING_ORDERS_REGISTRY_KEY, $orders);
    return $this;
  }

  protected function _afterDenyPayment(Mage_Payment_Model_Info $payment) {
    foreach(Mage::registry(Securetrading_Stpp_Model_Actions_Direct::MULTISHIPPING_ORDERS_REGISTRY_KEY) as $order) {
      $payment = $order->getPayment();
      $payment->setSkipDenyPayment(true);
      $payment->deny();
      $order->save();
    }
  }
}