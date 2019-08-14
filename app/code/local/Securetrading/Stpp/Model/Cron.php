<?php

class Securetrading_Stpp_Model_Cron {
    const EXPIRED_ORDER_HOURS = 24;
    
    public function abandonedOrderCleanup() {
        $expiredOrderTime = date('Y-m-d H:i:s', strtotime('-' . self::EXPIRED_ORDER_HOURS . ' hours'));
        
        $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('status', array('in', array(Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_PPAGES, Securetrading_Stpp_Model_Payment_Abstract::STATUS_PENDING_3DSECURE)))
            ->addFieldToFilter('updated_at', array('lt' => $expiredOrderTime))
        ;
        
        foreach($collection->getItems() as $order) {
            if (!$order->getPayment()->getMethodInstance()->getIsSecuretradingPaymentMethod()) {
                continue;
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
}