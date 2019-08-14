<?php

require_once(Mage::getModuleDir('controllers', 'Mage_Adminhtml') . DS . 'Sales' . DS . 'Order' . DS . 'CreateController.php');

class Securetrading_Stpp_Sales_Order_CreateController extends Mage_Adminhtml_Sales_Order_CreateController{
    public function saveAction() {
        try {
            // Start ST added.
            if ($tempPaymentData = $this->getRequest()->getPost('payment')) {
                if ($tempPaymentData['method'] !== Mage::getModel('securetrading_stpp/payment_redirect')->getCode()) {
                    return parent::saveAction();
                }
            }
            // End ST added.
            $this->_processActionData('save');
            if ($paymentData = $this->getRequest()->getPost('payment')) {
                $this->_getOrderCreateModel()->setPaymentData($paymentData);
                $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($paymentData);
            }
            
            $order = $this->_getOrderCreateModel()
                ->setIsValidate(true)
                ->importPostData($this->getRequest()->getPost('order'))
                // Start ST Added
                ->setSendConfirmation(false)
            	// End ST Added
                ->createOrder();

            // Start ST added.
            $orderInfo = $this->getRequest()->getPost('order');
            $sendConfirmation = isset($orderInfo['send_confirmation']);
            
            $this->_getSession()->setLastOrderIncrementId($order->getIncrementId());
            $path = Mage::getModel('securetrading_stpp/payment_redirect')->getMotoOrderRedirectPath();
            $this->_redirect($path, array('order_increment_id' => $order->getIncrementId(), 'send_confirmation' => $sendConfirmation));
            return;
            // end ST added.
            $this->_getSession()->clear();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order has been created.'));
            $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
        } catch (Mage_Payment_Model_Info_Exception $e) {
            $this->_getOrderCreateModel()->saveQuote();
            $message = $e->getMessage();
            if( !empty($message) ) {
                $this->_getSession()->addError($message);
            }
            $this->_redirect('*/*/');
        } catch (Mage_Core_Exception $e){
            $message = $e->getMessage();
            if( !empty($message) ) {
                $this->_getSession()->addError($message);
            }
            $this->_redirect('*/*/');
        }
        catch (Exception $e){
            $this->_getSession()->addException($e, $this->__('Order saving error: %s', $e->getMessage()));
            $this->_redirect('*/*/');
        }
    }
}