<?php
class Securetrading_Stpp_TokenizationController extends Mage_Core_Controller_Front_Action {
  public function preDispatch() {
    parent::preDispatch();
    if (!Mage::getSingleton('customer/session')->authenticate($this)) {
      $this->setFlag('', 'no-dispatch', true);
    }
  }
  
  public function newAction() {
    $this->_title($this->__('Billing Agreements'));
    $this->loadLayout();
    $this->renderLayout();
  }
  
  public function newPostAction() {
    try {
      $request = $this->getRequest();
      $agreement = Mage::getModel('securetrading_stpp/payment_direct')->runCardstore(
	$request->getParam('payment_type'),
	$request->getParam('start_date_month'),
	$request->getParam('start_date_year'),
	$request->getParam('expiry_date_month'),
	$request->getParam('expiry_date_year'),
	$request->getParam('card_number'),
	$request->getParam('issue_number')
      );
      
      Mage::getSingleton('customer/session')->addSuccess(
	$this->__('The billing agreement "%s" has been created.', $agreement->getReferenceId())
      );
      $this->_redirect('sales/billing_agreement/view', array('agreement' => $agreement->getId()));
    }
    catch (Exception $e) {
      Mage::logException($e);
      Mage::getSingleton('customer/session')->addError($this->__('Unable to create billing agreement.'));
      $this->_redirect('sales/billing_agreement/index');
    }
  }
}