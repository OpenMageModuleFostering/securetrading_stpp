<?php

class Securetrading_Stpp_Model_Payment_Handler_Backend_Factory extends Varien_Object {
  const REGISTRY_KEY_IS_BACKEND_OP = 'securetrading_stpp_model_payment_handler_backend_factory_registry_key_is_backend_op';

  public function getHandler() {
    $transactionReference = $this->getTransactionReference();
    if (!$transactionReference) {
      throw new Exception(Mage::helper('securetrading_stpp')->__('The transaction reference has not been set.'));
    }

    $methodInstance = $this->getMethodInstance();
    if (!($methodInstance instanceof Securetrading_Stpp_Model_Payment_Abstract)) {
      throw new Exception(Mage::helper('securetrading_stpp')->__('The method instance was not set.'));
    }
    
    $data = array(
      'transaction_reference' => $transactionReference,
      'integration' => $methodInstance->getIntegration(),
    );
    $orderIncrementIds = $this->_getOrderIncrementIds($transactionReference);
    if ($orderIncrementIds) {
      $data['order_increment_ids'] = $orderIncrementIds;
      return Mage::getModel('securetrading_stpp/payment_handler_backend_multishipping', $data);
    }
    return Mage::getModel('securetrading_stpp/payment_handler_backend_onepage', $data);
  }

  protected function _getOrderIncrementIds($transactionReference) {
    $transaction = Mage::getModel('securetrading_stpp/transaction')->loadByTransactionReference($transactionReference);
    $orderId = $transaction->getOrderId();
    $factory = Mage::getModel('securetrading_multishipping/order_set_factory');
    
    if ($factory->orderBelongsToAnySet($orderId)) {
      $orderIds = $factory->getOrderIdsInSameSet($orderId, false);
      $orderIncrementIds = array();
      foreach($orderIds as $orderId) {
	$orderIncrementIds[] = Mage::getModel('sales/order')->load($orderId)->getIncrementId();
      }
      return $orderIncrementIds;
    }
    return null;
  }
}