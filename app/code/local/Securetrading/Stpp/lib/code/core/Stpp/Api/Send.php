<?php

class Stpp_Api_Send extends Stpp_Component_Abstract implements Stpp_Api_SendInterface {
    const API_VERSION = '3.67';
    
    protected $_connection;
    
    protected $_xmlWriter;
    
    protected $_xmlReader;
    
    protected $_context;
    
    public function setConnection(Stpp_Api_Connection_BaseInterface $connection) {
        $this->_connection = $connection;
    }
    
    public function getConnection() {
        if ($this->_connection === null) {
            throw new Stpp_Exception($this->__('The active connection has not been set.'));
        }
        return $this->_connection;
    }
    
    public function setXmlWriter(Stpp_Api_Xml_WriterInterface $xmlWriter) {
        $this->_xmlWriter = $xmlWriter;
        return $this;
    }
    
    protected function _getXmlWriter() {
        if ($this->_xmlWriter === null) {
            throw new Stpp_Exception($this->__('The XmlWriter has not been set.'));
        }
        return $this->_xmlWriter;
    }
    
    public function setXmlReader(Stpp_Api_Xml_ReaderInterface $xmlReader) {
        $this->_xmlReader = $xmlReader;
        return $this;
    }
    
    protected function _getXmlReader() {
        if ($this->_xmlReader === null) {
            throw new Stpp_Exception($this->__('The XmlReader has not been set.'));
        }
        return $this->_xmlReader;
    }
    
    public function setContext(Stpp_Api_ContextInterface $context) {
        $this->_context = $context;
    }
    
    protected function _getContext() {
        if ($this->_context === null) {
            throw new Stpp_Exception($this->__('The context object is null.'));
        }
        return $this->_context;
    }
    
    public function run(array $requestArray) {
        $xmlRequestString = $this->_formXmlRequests($requestArray);
        $xmlResponseString = $this->getConnection()->sendAndReceiveData($xmlRequestString);
        $responseArray = $this->_getXmlReader()->parseResponses($xmlResponseString);
		$this->_mapRequestsToResponses($requestArray, $responseArray);
		$this->_getContext()->setRequests($requestArray)->setResponses($responseArray);
		return $this->_getContext();
    }

    protected function _formXmlRequests(array $requests) {
        $xmlWriter = $this->_getXmlWriter();
        $xmlWriter->startRequestBlock(static::API_VERSION, $this->getConnection()->getAlias());
        
		foreach($requests as $request) {
            $xmlWriter->startRequest($request);
            
            $requestType = $request->get('requesttypedescription');
            
            switch($requestType) {
                case Stpp_Types::API_AUTH:
		    if ($request->has('md')) {
		        $xmlWriter->prepare3dAuth($request);
		    }
		    else {
		        $xmlWriter->prepareAuth($request);
		    }
                    break;
                case Stpp_Types::API_THREEDQUERY:
                    $xmlWriter->prepare3dQuery($request);
                    break;
                case Stpp_Types::API_RISKDEC:
                    $xmlWriter->prepareRiskDecision($request);
                    break;
                case Stpp_Types::API_ACCOUNTCHECK:
                    $xmlWriter->prepareAccountCheck($request);
                    break;
                case Stpp_Types::API_CARDSTORE:
                    $xmlWriter->prepareCardStore($request);
                    break;
                case Stpp_Types::API_TRANSACTIONUPDATE:
                    $xmlWriter->prepareTransactionUpdate($request);
                    break;
                case Stpp_Types::API_REFUND:
                    $xmlWriter->prepareRefund($request);
                    break;
                default:
                    throw new Stpp_Exception(sprintf($this->__('Invalid request type: "%s".'), $requestType));
            }
            $xmlWriter->endRequest();
        }
        return $xmlWriter->endRequestBlock();
    }
    
    protected function _mapRequestsToResponses(array &$requests, array &$responses) {
        for ($i = 0; $i < count($responses); $i++) {
            $requests[$i]->setResponse($responses[$i]);
            $responses[$i]->setRequest($requests[$i]);
        }
        return $this;
    }
}