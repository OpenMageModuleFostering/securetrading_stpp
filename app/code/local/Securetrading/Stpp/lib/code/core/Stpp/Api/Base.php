<?php

class Stpp_Api_Base extends Stpp_Component_Abstract implements Stpp_Api_BaseInterface {
    const INTERFACE_NAME = 'SecureTrading API';
    
    public static function getName() {
        return static::INTERFACE_NAME;
    }
    
    public function getSend() {
      if ($this->_apiSend === null) {
	throw new Stpp_Exception($this->__('The sender has not been set.'));
      }
      return $this->_apiSend;
    }

    public function setSend(Stpp_Api_SendInterface $apiSend) {
      $this->_apiSend = $apiSend;
    }

    public function getProcess() {
      if ($this->_apiProcess === null) {
	throw new Stpp_Exception($this->__('The processor has not been set.'));
      }
      return $this->_apiProcess;
    }

    public function setProcess(Stpp_Api_ProcessInterface $apiProcess) {
      $this->_apiProcess = $apiProcess;
    }

    public function run($requests) {
        if (!is_array($requests)) {
            $requests = array($requests);
        }
        
      list($cardStoreRequest, $requests) = $this->_startCardStoreHack($requests); // CARDSTORE HACK.
      
      $context = $this->getSend()->run($requests);
      $result = $this->getProcess()->run($context);

      $this->_endCardStoreHack($cardStoreRequest); // CARDSTORE HACK.

      return $result;
    }
    
    // START CARDSTORE HACK.
    protected function _startCardStoreHack(array $requests) {
      $cardStoreRequest = null;

      foreach($requests as $k => $request) {
        if ($request->get('requesttypedescription') === Stpp_Types::API_CARDSTORE) {
            $cardStoreRequest = $request;
            unset($requests[$k]);
            $requests = array_values($requests);
        }
      }
      return array($cardStoreRequest, $requests);
    }

    protected function _endCardStoreHack(Stpp_Data_Request $cardStoreRequest = null) {
      if ($cardStoreRequest) {
	$requests = array($cardStoreRequest);
	$context = $this->getSend()->run($requests);
	$this->getProcess()->run($context);
      }
    }
    // END CARDSTORE HACK.
}