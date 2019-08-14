<?php

class Stpp_Api_Xml_Reader extends Stpp_Component_Abstract implements Stpp_Api_Xml_ReaderInterface {
    protected $_reader;
    
    protected $_responseClassName;
    
    public function __construct($responseClassName) {
        parent::__construct();
        $this->_responseClassName = $responseClassName;
    }
    
    protected function _newResponseObject() {
        $responseClassName = $this->_responseClassName;
        return $responseClassName::instance();
    }
    
    public function parseResponses($xmlString) {
        $this->_reader = new SimpleXmlElement($xmlString);
       
        $xmlResponses = $this->_reader->xpath('response');  
        
        $responseBlockVersion = (string) $this->_reader->attributes()->version;
        $responseBlockRequestReference = (string) $this->_reader->requestreference;
        
        if (empty($xmlResponses)) {
            throw new Stpp_Exception($this->__('No response element in response XML.'));
        }
        
        $responses = array();
        
        foreach($xmlResponses as $xmlResponse) {
            $responseType = (string) $xmlResponse->attributes()->type;
            
            if (!in_array($responseType, Stpp_Types::getRequestAndResponseTypes())) {
                throw new Stpp_Exception(sprintf($this->__('Unsupported response type "%s" returned.'), $responseType));
            }
            
            $response = $this->_parseStandardInformation($xmlResponse, $responseType);
            $response->set('responseblockversion', $responseBlockVersion);
            $response->set('responseblockrequestreference', $responseBlockRequestReference);
                    
            switch($responseType) {
                case Stpp_Types::API_ERROR:
                    $this->_parseErrorResponse($xmlResponse, $response);
                    break;
                case Stpp_Types::API_AUTH:
                    $this->_parseAuthResponse($xmlResponse, $response);
                    break;
                case Stpp_Types::API_THREEDQUERY:
                    $this->_parse3dQueryResponse($xmlResponse, $response);
                    break;
                case Stpp_Types::API_RISKDEC:
                    $this->_parseRiskDecisionResponse($xmlResponse, $response);
                    break;
                case Stpp_Types::API_ACCOUNTCHECK:
                    $this->_parseAccountCheckResponse($xmlResponse, $response);
                    break;
                case Stpp_Types::API_CARDSTORE:
                    $this->_parseCardStoreResponse($xmlResponse, $response);
                    break;
                case Stpp_Types::API_TRANSACTIONUPDATE:
                    // The _parseStandardInformation() method sets everything returned in a TRANSACTIONUPDATE response so no method call is needed here.
                    break;
                case Stpp_Types::API_REFUND:
                    $this->_parseRefundResponse($xmlResponse, $response);
                    break;
                default:
                    throw new Stpp_Exception(sprintf($this->__('Response type "%s" unhandled.'), $responseType));
            }
            $responses[] = $response;
        }
        return $responses;
    }
    
    protected function _parseStandardInformation($xmlResponse, $responseType) {
        $response = $this->_newResponseObject();
        $response->set('responsetype', $responseType);
        
        $response->set('timestamp', (string)$xmlResponse->timestamp);
        $response->set('errorcode', (string) $xmlResponse->error->code);
        $response->set('errormessage', (string) $xmlResponse->error->message); // May not be present.
        $response->set('errordata', (string) $xmlResponse->error->data); // May not be present.
        
        return $response;
    }
    
    protected function _parseErrorResponse($xmlResponse, &$response) {
        $response->set('transactionreference', (string) $xmlResponse->transactionreference);
    }
    
    protected function _parseRiskDecisionResponse($xmlResponse, &$response) {
        $response->set('orderreference', (string) $xmlResponse->merchant->orderreference);
        $response->set('transactionreference', (string) $xmlResponse->transactionreference);
        $response->set('maskedpan', (string) $xmlResponse->billing->payment->pan);
        
        $response->set('fraudcontrolreference', (string) $xmlResponse->fraudcontrol->reference);
        $response->set('fraudcontrolshieldstatuscode', (string) $xmlResponse->fraudcontrol->shieldstatuscode);
        $response->set('fraudcontrolrecommendedaction', (string) $xmlResponse->fraudcontrol->recommendedaction);
        $response->set('fraudcontrolcategoryflag', (string) $xmlResponse->fraudcontrol->categoryflag);
        $response->set('fraudcontrolcategorymessage', (string) $xmlResponse->fraudcontrol->categorymessage);
        $response->set('fraudcontrolcode', (string) $xmlResponse->fraudcontrolcode);
        $response->set('fraudcontrollive', (string) $xmlResponse->live);
        
        $response->set('parenttransactionreference', (string) $xmlResponse->operation->parenttransactionreference);
        $response->set('accounttypedescription', (string) $xmlResponse->operation->accounttypedescription);
    }
    
    protected function _parseAccountCheckResponse($xmlResponse, &$response) {
        $this->_parseAuthOr3dQueryResponse($xmlResponse, $response, false);
    }

    protected function _parseAuthResponse($xmlResponse, &$response) {
        $this->_parseAuthOr3dQueryResponse($xmlResponse, $response, false);
    }
    
    protected function _parse3dQueryResponse($xmlResponse, &$response) {
        $this->_parseAuthOr3dQueryResponse($xmlResponse, $response, true);
    }
    
    protected function _parseAuthOr3dQueryResponse($xmlResponse, &$response, $is3dResponse) {
        $response->set('merchantname', (string) $xmlResponse->merchant->merchantname);
        $response->set('orderreference', (string) $xmlResponse->merchant->orderreference);
        $response->set('tid', (string) $xmlResponse->merchant->tid);
        $response->set('merchantnumber', (string) $xmlResponse->merchant->merchantnumber);
        $response->set('merchantcountryiso2a', (string) $xmlResponse->merchant->merchantcountryiso2a);
        $response->set('transactionreference', (string) $xmlResponse->transactionreference);
        $response->set('securityresponsesecuritycode', (string) $xmlResponse->security->securitycode);
        $response->set('securityresponsepostcode', (string) $xmlResponse->security->postcode);
        $response->set('securityresponseaddress', (string) $xmlResponse->security->address);
        
        if (isset($xmlResponse->billing->amount)) { // Not present in THREEDQUERY response.
            $response->set('baseamount', (string) $xmlResponse->billing->amount);
            $response->set('currencyiso3a', (string) $xmlResponse->billing->amount->attributes()->currencycode);
        }
        
        $response->set('paymenttypedescription', (string) $xmlResponse->billing->payment->attributes()->type);
        $response->set('maskedpan', (string) $xmlResponse->billing->payment->pan);
        $response->set('authcode', (string) $xmlResponse->authcode);
        
        $response->set('acsurl', (string) $xmlResponse->threedsecure->acsurl);
        $response->set('cavv', (string) $xmlResponse->threedsecure->cavv);
        $response->set('status', (string) $xmlResponse->threedsecure->status);
        $response->set('xid', (string) $xmlResponse->threedsecure->xid);
        $response->set('eci', (string) $xmlResponse->threedsecure->eci);
        $response->set('enrolled', (string) $xmlResponse->threedsecure->enrolled);
        $response->set('termurl', (string) $xmlResponse->threedsecure->termurl);
        $response->set('md', (string) $xmlResponse->threedsecure->md);
        $response->set('pareq', (string) $xmlResponse->threedsecure->pareq);
        
        $response->set('live', (string) $xmlResponse->live);
        
        $response->set('parenttransactionreference', (string) $xmlResponse->operation->parenttransactionreference);
        $response->set('accounttypedescription', (string) $xmlResponse->operation->accounttypedescription);
        $response->set('settleduedate', (string) $xmlResponse->settlement->settleduedate);
        $response->set('settlestatus', (string) $xmlResponse->settlement->settlestatus);

	$response->set('redirecturl', (string) $xmlResponse->other->redirecturl);
    }
    
    protected function _parseCardStoreResponse($xmlResponse, &$response) {
        $response->set('merchantname', (string) $xmlResponse->merchant->merchantname);
        $response->set('orderreference', (string) $xmlResponse->merchant->orderreference);
        $response->set('transactionreference', (string) $xmlResponse->transactionreference);
        $response->set('paymenttypedescription', (string) $xmlResponse->billing->payment->attributes()->type);
        $response->set('paymentactive', (string) $xmlResponse->billing->payment->active);
        $response->set('maskedpan', (string) $xmlResponse->billing->payment->pan);
        $response->set('live', (string) $xmlResponse->live);
        $response->set('accounttypedescription', (string) $xmlResponse->operation->accounttypedescription);
    }
    
    protected function _parseRefundResponse($xmlResponse, &$response) {
        $response->set('merchantname', (string) $xmlResponse->merchant->merchantname);
        $response->set('orderreference', (string) $xmlResponse->merchant->orderreference);
        $response->set('tid', (string) $xmlResponse->merchant->tid);
        $response->set('merchantnumber', (string) $xmlResponse->merchant->merchantnumber);
        $response->set('merchantcountryiso2a', (string) $xmlResponse->merchant->merchantcountryiso2a);
        $response->set('transactionreference', (string) $xmlResponse->transactionreference);
        $response->set('baseamount', (string) $xmlResponse->billing->amount);
        $response->set('currencyiso3a', (string) $xmlResponse->billing->amount->attributes()->currencycode);
        $response->set('paymenttypedescription', (string) $xmlResponse->billing->payment->attributes()->type);
        $response->set('maskedpan', (string) $xmlResponse->billing->payment->pan);
        $response->set('authcode', (string) $xmlResponse->authcode);
        $response->set('securityresponsecode', (string) $xmlResponse->security->securitycode);
        $response->set('securityresponsepostcode', (string) $xmlResponse->security->postcode);
        $response->set('securityresponseaddress', (string) $xmlResponse->security->address);
        $response->set('parenttransactionreference', (string) $xmlResponse->operation->parenttransactionreference);
        $response->set('accounttypedescription', (string) $xmlResponse->operation->accounttypedescription);
        $response->set('settleduedate', (string) $xmlResponse->settlement->settleduedate);
        $response->set('settlestatus', (string) $xmlResponse->settlement->settlestatus);
    }
}