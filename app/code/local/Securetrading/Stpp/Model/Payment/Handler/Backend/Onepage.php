<?php

class Securetrading_Stpp_Model_Payment_Handler_Backend_Onepage extends Securetrading_Stpp_Model_Payment_Handler_Backend_Abstract {
  protected function _prepareRefund(Mage_Sales_Model_Order_Payment $payment, $amount) {
    $partialRefundAlreadyProcessed = $this->_paymentHasSuccessfulRefund($payment);
    $orderBaseGrandTotal = $payment->getOrder()->getBaseGrandTotal();
    $baseTotalPaid = $payment->getOrder()->getBaseTotalPaid();
    $baseTotalRefunded = $payment->getOrder()->getBaseTotalRefunded() - $amount; // Before this method is called this happens: $payment->getOrder->setBaseTotalRefunded($payment->getOrder()->getBaseTotalRefunded() - $amount).  That's why we subtract the $amount here - but don't for multishipping above (because we load new temporary orders when multishipping is being calculated.

    return array(
      'partial_refund_already_processed' => $partialRefundAlreadyProcessed,
      'order_base_grand_total' => $orderBaseGrandTotal,
      'base_total_paid' => $baseTotalPaid,
      'base_total_refunded' => $baseTotalRefunded,
    );
  }

  protected function _prepareToCaptureAuthorized(Mage_Sales_Model_Order_Payment $payment) {
    $orderBaseGrandTotal = (string) Mage::app()->getStore()->roundPrice($payment->getOrder()->getBaseGrandTotal());
    $baseAmountPaid = (string) Mage::app()->getStore()->roundPrice($payment->getBaseAmountPaid());
    $baseAmountRefunded = (string) Mage::app()->getStore()->roundPrice($payment->getBaseAmountRefunded());
    return array(
      'order_base_grand_total' => $orderBaseGrandTotal,
      'base_amount_paid' => $baseAmountPaid,
      'base_amount_refunded' => $baseAmountRefunded,
    );
  }

  protected function _canAcceptPayment(Mage_Payment_Model_Info $payment) {
    return true;
  }

  protected function _afterAcceptPayment(Mage_Payment_Model_Info $payment) {
    return $this;
  }
  
  protected function _canDenyPayment(Mage_Payment_Model_Info $payment) {
    return true;
  }

  protected function _beforeDenyPayment(Mage_Payment_Model_Info $payment) {
    return $this;
  }

  protected function _afterDenyPayment(Mage_Payment_Model_Info $payment) {
    return $this;
  }
}