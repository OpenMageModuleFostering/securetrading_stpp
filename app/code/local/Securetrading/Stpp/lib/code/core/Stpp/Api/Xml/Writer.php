<?php

class Stpp_Api_Xml_Writer extends Stpp_Component_Abstract implements Stpp_Api_Xml_WriterInterface {
    protected $_writer;
    
    protected $_xmlVersion = "1.0";
    
    protected $_xmlEncoding = 'UTF-8';
    
    public function __construct() {
        $this->_initWriter();
        parent::__construct();
    }
    
    protected function _initWriter() {
        $this->_writer = new Stpp_Xml_Writer();
        $this->_writer->openMemory();
        
        $this->_writer->setIndent(true);
        $this->_writer->setIndentString("\t");
    }
    
    public function setXmlVersion($version) {
        $this->_xmlVersion = $version;
        return $this;
    }
    
    public function setXmlEncoding($encoding) {
        $this->_xmlEncoding = $encoding;
        return $this;
    }
    
    public function startRequestBlock($apiVersion, $alias) {
        $this->_writer->startDocument($this->_xmlVersion, $this->_xmlEncoding);
        
        // <requestblock version="">
        $this->_writer->startElement('requestblock');
        $this->_writer->writeAttribute('version', $apiVersion);
        
        // <alias></alias>
        $this->_writer->writeElement('alias', $alias);
        
        return $this;
    }
    
    public function endRequestBlock() {
        $this->_writer->endElement(); // </requestblock>
        $this->_writer->endDocument();
        $requestString = $this->_writer->flush();
        return $requestString;
    }
    
    public function startRequest(Stpp_Data_Request $request) {
        // <request>
        $this->_writer->startElement('request');
        $this->_writer->writeAttribute('type', $request->get('requesttypedescription'));
        
        return $this;
    }
    
    public function endRequest() {
        // </request>
        $this->_writer->endElement();
        
        return $this;
    }
    
    public function prepareAuth(Stpp_Data_Request $request) {
        return $this->_prepareFullParentTransaction($request);
    }
    
    public function prepare3dQuery(Stpp_Data_Request $request) {
        return $this->_prepareFullParentTransaction($request);
    }
    
    public function prepare3dAuth(Stpp_Data_Request $request) {
        $xmlWriter = $this->_writer;
        
        // <operation>
        $xmlWriter->startElement('operation');

            // <md></md>
            $xmlWriter->writeElement('md', $request->get('md'));

            // <pares></pares>
            $xmlWriter->writeElement('pares', $request->get('pares'));

        // </operation>
        $xmlWriter->endElement();
        
        return $this;
    }
    
    public function prepareRiskDecision(Stpp_Data_Request $request) {
        return $this->_prepareFullParentTransaction($request);
    }
    
    public function prepareAccountCheck(Stpp_Data_Request $request) {
        return $this->_prepareFullParentTransaction($request);
    }
    
    public function prepareCardStore(Stpp_Data_Request $request) {
        $xmlWriter = $this->_writer;
        
        // <merchant>
        $xmlWriter->startElement('merchant');

                // <orderreference></orderreference>
                $xmlWriter->writeElement('orderreference', $request->get('orderreference'));

        // </merchant>
        $xmlWriter->endElement();

        // <operation>
        $xmlWriter->startElement('operation');

                // <sitereference></sitereference>
                $xmlWriter->writeElement('sitereference', $request->get('sitereference'));

                // <accounttypedescription></accounttypedescription>
                $xmlWriter->writeElement('accounttypedescription', $request->get('accounttypedescription'));

        // </operation>
        $xmlWriter->endElement();
        
        // <billing>
            $xmlWriter->startElement('billing');

                    // <telephone type=''></telephone>
                    $xmlWriter->startElement('telephone');
                    $xmlWriter->writeAttribute('type', $request->get('billingtelephonetype'));
                    $xmlWriter->text($request->get('billingtelephone'));
                    $xmlWriter->endElement();

                    // <street></street>
                    $xmlWriter->writeElement('street', $request->get('billingstreet'));

                    // <postcode></postcode>
                    $xmlWriter->writeElement('postcode', $request->get('billingpostcode'));

                    // <premise></premise>
                    $xmlWriter->writeElement('premise', $request->get('billingpremise'));

                    // <town></town>
                    $xmlWriter->writeElement('town', $request->get('billingtown'));

                    // <country></country>
                    $xmlWriter->writeElement('country', $request->get('billingcountryiso2a'));

                        // <payment type=''>
                        $xmlWriter->startElement('payment');
                        $xmlWriter->writeAttribute('type', $request->get('paymenttype'));

                                // <startdate></startdate>
                                $xmlWriter->writeElement('startdate', $request->get('startdate'));

                                // <expirydate></expirydate>
                                $xmlWriter->writeElement('expirydate', $request->get('expirydate'));

                                // <pan></pan>
                                $xmlWriter->writeElement('pan', $request->get('pan'));

                                // <securitycode></securitycode>
                                $xmlWriter->writeElement('securitycode', $request->get('securitycode'));

                                // <issuenumber></issuenumber>
                                $xmlWriter->writeElement('issuenumber', $request->get('issuenumber'));

                        // </payment>
                        $xmlWriter->endElement();

                        // <name>
                        $xmlWriter->startElement('name');

                                // <middle></middle>
                                $xmlWriter->writeElement('middle', $request->get('billingmiddlename'));

                                // <prefix></prefix>
                                $xmlWriter->writeElement('prefix', $request->get('billingprefix'));

                                // <last></last>
                                $xmlWriter->writeElement('last', $request->get('billinglastname'));

                                // <first></first>
                                $xmlWriter->writeElement('first', $request->get('billingfirstname'));

                                // <suffix></suffix>
                                $xmlWriter->writeElement('suffix', $request->get('billingsuffix'));

                        // </name>
                        $xmlWriter->endElement();

                    // <email></email>
                    $xmlWriter->writeElement('email', $request->get('billingemail'));

            // </billing>
            $xmlWriter->endElement();
        
        return $this;
    }
    
    public function prepareTransactionUpdate(Stpp_Data_Request $request) {
        $xmlWriter = $this->_writer;
        
        // <filter>
        $xmlWriter->startElement('filter');

            // <transactionreference></transactionreference>
            $xmlWriter->writeElement('transactionreference', $request->get('filter')->get('transactionreference'));

            // <sitereference></sitereference>
            $xmlWriter->writeElement('sitereference', $request->get('filter')->get('sitereference'));

        // </filter>
        $xmlWriter->endElement();

        // <updates>
        $xmlWriter->startElement('updates');
        
                // <merchant>
                $xmlWriter->startElement('merchant');

                    // <orderreference></orderreference>
                    $xmlWriter->writeElement('orderreference', $request->get('updates')->get('orderreference'));

                // </merchant>
                $xmlWriter->fullEndElement();
            
            // <billing>
            $xmlWriter->startElement('billing');

                    if ($request->get('updates')->has('billingtelephonetype') || $request->get('updates')->has('billingtelephone')) {
                        // <telephone type=''></telephone>
                        $xmlWriter->startElement('telephone');
                        $xmlWriter->writeAttribute('type', $request->get('updates')->get('billingtelephonetype'));
                        $xmlWriter->text($request->get('updates')->get('billingtelephone'));
                        $xmlWriter->endElement();
                    }
                    
                    // <street></street>
                    $xmlWriter->writeElement('street', $request->get('updates')->get('billingstreet'));

                    // <postcode></postcode>
                    $xmlWriter->writeElement('postcode', $request->get('updates')->get('billingpostcode'));

                    // <premise></premise>
                    $xmlWriter->writeElement('premise', $request->get('updates')->get('billingpremise'));

                    // <town></town>
                    $xmlWriter->writeElement('town', $request->get('updates')->get('billingtown'));
                    
                    // <country></country>
                    $xmlWriter->writeElement('country', $request->get('updates')->get('billingcountryiso2a'));

                        // <payment type=''>
                        $xmlWriter->startElement('payment');

                                // <startdate></startdate>
                                $xmlWriter->writeElement('startdate', $request->get('updates')->get('startdate'));

                                // <expirydate></expirydate>
                                $xmlWriter->writeElement('expirydate', $request->get('updates')->get('expirydate'));

                                // <issuenumber></issuenumber>
                                $xmlWriter->writeElement('issuenumber', $request->get('updates')->get('issuenumber'));

                        // </payment>
                        $xmlWriter->fullEndElement();
                        
                        // <name>
                        $xmlWriter->startElement('name');

                                // <middle></middle>
                                $xmlWriter->writeElement('middle', $request->get('updates')->get('billingmiddlename'));

                                // <prefix></prefix>
                                $xmlWriter->writeElement('prefix', $request->get('updates')->get('billingprefix'));

                                // <last></last>
                                $xmlWriter->writeElement('last', $request->get('updates')->get('billinglastname'));

                                // <first></first>
                                $xmlWriter->writeElement('first', $request->get('updates')->get('billingfirstname'));

                                // <suffix></suffix>
                                $xmlWriter->writeElement('suffix', $request->get('updates')->get('billingsuffix'));

                        // </name>
                        $xmlWriter->fullEndElement();

                    // <email></email>
                    $xmlWriter->writeElement('email', $request->get('updates')->get('billingemail'));

            // </billing>
            $xmlWriter->endElement();

            // <settlement>
            $xmlWriter->startElement('settlement');
            
                if ($request->get('updates')->has('settlebaseamount')) {
                	// <settlebaseamount></settlebaseamount>
	                $xmlWriter->writeElement('settlebaseamount', $request->get('updates')->get('settlebaseamount'));
                }
                else if ($request->get('updates')->has('settlemainamount')) {
                	// <settlemainamount currencycode=''></settlemainamount>
	                $xmlWriter->startElement('settlemainamount');
	                $xmlWriter->writeAttribute('currencycode', $request->get('updates')->get('currencyiso3a'));
	                $xmlWriter->text($request->get('updates')->get('settlemainamount'));
	                $xmlWriter->endElement();
                }
                
                // <settleduedate></settleduedate>
                $xmlWriter->writeElement('settleduedate', $request->get('updates')->get('settleduedate'));

                // <settlestatus></settlestatus>
                $xmlWriter->writeElement('settlestatus', $request->get('updates')->get('settlestatus'));

            // </settlement>
            $xmlWriter->endElement();

        // </updates>
        $xmlWriter->endElement();

        return $this;
    }
    
    public function prepareRefund(Stpp_Data_Request $request) {
        $xmlWriter = $this->_writer;
        
        // <operation>
        $xmlWriter->startElement('operation');

            // <sitereference></sitereference>
            $xmlWriter->writeElement('sitereference', $request->get('sitereference'));

            // <parenttransactionreference></parenttransactionreference>
            $xmlWriter->writeElement('parenttransactionreference', $request->get('parenttransactionreference'));

        // </operation>
        $xmlWriter->endElement();
        
        // <billing>
        $xmlWriter->startElement('billing');
        
	        // <amount></amount>
	        $xmlWriter->writeElement('amount', $request->get('baseamount'));
	        
	        // <mainamount></mainamount>
	        $xmlWriter->writeElement('mainamount', $request->get('mainamount'));
	        
        // </billing>
        $xmlWriter->endElement();
        
        return $this;
    }
    
    protected function _prepareFullParentTransaction(Stpp_Data_Request $request) {
        $xmlWriter = $this->_writer;

        // <merchant>
        $xmlWriter->startElement('merchant');

                // <orderreference></orderreference>
                $xmlWriter->writeElement('orderreference', $request->get('orderreference'));

                // If request type is 'threedquery':
                if ($request->get('requesttypedescription') === Stpp_Types::API_THREEDQUERY) {

                        // <termurl></termurl>
                        $xmlWriter->writeElement('termurl', $request->get('termurl'));
                }

        // </merchant>
        $xmlWriter->endElement();

        // <customer>
        $xmlWriter->startElement('customer');
                // <ip></ip>
                $xmlWriter->writeElement('ip', $request->get('customerip'));

                // <telephone type=''></telephone>
                $xmlWriter->startElement('telephone');
                $xmlWriter->writeAttribute('type', $request->get('customertelephonetype'));
                $xmlWriter->text($request->get('customertelephone'));
                $xmlWriter->endElement();

                // <street></street>
                $xmlWriter->writeElement('street', $request->get('customerstreet'));

                // <postcode></postcode>
                $xmlWriter->writeElement('postcode', $request->get('customerpostcode'));

                // <premise></premise>
                $xmlWriter->writeElement('premise', $request->get('customerpremise'));

                // <town></town>
                $xmlWriter->writeElement('town', $request->get('customertown'));

                // <country></country>
                $xmlWriter->writeElement('country', $request->get('customercountryiso2a'));

                // <name>
                $xmlWriter->startElement('name');

                        // <middle></middle>
                        $xmlWriter->writeElement('middle', $request->get('customermiddlename'));

                        // <prefix></prefix>
                        $xmlWriter->writeElement('prefix', $request->get('customerprefix'));

                        // <last></last>
                        $xmlWriter->writeElement('last', $request->get('customerlastname'));

                        // <first></first>
                        $xmlWriter->writeElement('first', $request->get('customerfirstname'));

                        // <suffix></suffix>
                        $xmlWriter->writeElement('suffix', $request->get('customersuffix'));

                // </name>
                $xmlWriter->endElement();

                // <email></email>
                $xmlWriter->writeElement('email', $request->get('customeremail'));

                // <customershippingmethod></customershippingmethod>
                if ($request->get('requesttypedescription') === Stpp_Types::API_RISKDEC) {
                    $xmlWriter->writeElement('shippingmethod', $request->get('customershippingmethod'));
                }

        // </customer>
        $xmlWriter->endElement();

        // <billing>
        $xmlWriter->startElement('billing');

                // <telephone type=''></telephone>
                $xmlWriter->startElement('telephone');
                $xmlWriter->writeAttribute('type', $request->get('billingtelephonetype'));
                $xmlWriter->text($request->get('billingtelephone'));
                $xmlWriter->endElement();

                // <street></street>
                $xmlWriter->writeElement('street', $request->get('billingstreet'));

                // <postcode></postcode>
                $xmlWriter->writeElement('postcode', $request->get('billingpostcode'));

                // <premise></premise>
                $xmlWriter->writeElement('premise', $request->get('billingpremise'));

                // <town></town>
                $xmlWriter->writeElement('town', $request->get('billingtown'));

                // <country></country>
                $xmlWriter->writeElement('country', $request->get('billingcountryiso2a'));

                // <payment type=''>
                $xmlWriter->startElement('payment');
                $xmlWriter->writeAttribute('type', $request->get('paymenttype'));

                        // <startdate></startdate>
                        $xmlWriter->writeElement('startdate', $request->get('startdate'));

                        // <expirydate></expirydate>
                        $xmlWriter->writeElement('expirydate', $request->get('expirydate'));

                        // <pan></pan>
                        $xmlWriter->writeElement('pan', $request->get('pan'));

                        // <securitycode></securitycode>
                        $xmlWriter->writeElement('securitycode', $request->get('securitycode'));

                        // <issuenumber></issuenumber>
                        $xmlWriter->writeElement('issuenumber', $request->get('issuenumber'));

                // </payment>
                $xmlWriter->endElement();

                // <name>
                $xmlWriter->startElement('name');

                        // <middle></middle>
                        $xmlWriter->writeElement('middle', $request->get('middlename'));

                        // <prefix></prefix>
                        $xmlWriter->writeElement('prefix', $request->get('billingprefix'));

                        // <last></last>
                        $xmlWriter->writeElement('last', $request->get('billinglastname'));

                        // <first></first>
                        $xmlWriter->writeElement('first', $request->get('billingfirstname'));

                        // <suffix></suffix>
                        $xmlWriter->writeElement('suffix', $request->get('billingsuffix'));

                // </name>
                $xmlWriter->endElement();
                
                if ($request->has('baseamount')) {
                	// <amount currencycode=''></amount>
                	$xmlWriter->writeElement('baseamount', $request->get('baseamount'));
                }
                elseif($request->has('mainamount')) {
	                // <mainamount></mainamount>
	                $xmlWriter->startElement('mainamount');
	                $xmlWriter->writeAttribute('currencycode', $request->get('currencyiso3a'));
	                $xmlWriter->text($request->get('mainamount'));
	                $xmlWriter->endElement();
                }
                
                // <email></email>
                $xmlWriter->writeElement('email', $request->get('billingemail'));

                // <dob</dob>
                if ($request->get('requesttypedescription') === Stpp_Types::API_RISKDEC) {
                    $xmlWriter->writeElement('dob', $request->get('billingdob'));
                }

        // </billing>
        $xmlWriter->endElement();

        // <operation>
        $xmlWriter->startElement('operation');

                // <sitereference></sitereference>
                $xmlWriter->writeElement('sitereference', $request->get('sitereference'));

                // <accounttypedescription></accounttypedescription>
                $xmlWriter->writeElement('accounttypedescription', $request->get('accounttypedescription'));

                // <parenttransactionreference></parenttransactionreference>
                $xmlWriter->writeElement('parenttransactionreference', $request->get('parenttransactionreference'));

		// <customfield1></customfield1>
		$xmlWriter->writeElement('customfield1', $request->get('customfield1'));

		// <customfield2></customfield2>
		$xmlWriter->writeElement('customfield2', $request->get('customfield2'));

		// <customfield3></customfield3>
		$xmlWriter->writeElement('customfield3', $request->get('customfield3'));

		// <customfield4></customfield4>
		$xmlWriter->writeElement('customfield4', $request->get('customfield4'));

		// <customfield5></customfield5>
		$xmlWriter->writeElement('customfield5', $request->get('customfield5'));

        // </operation>
        $xmlWriter->endElement();

        // <settlement>
        $xmlWriter->startElement('settlement');

                // <settleduedate></settleduedate>
                $xmlWriter->writeElement('settleduedate', $request->get('settleduedate'));

                // <settlestatus></settlestatus>
                $xmlWriter->writeElement('settlestatus', $request->get('settlestatus'));

        // </settlement>
        $xmlWriter->endElement();
        
        return $this;
    }
}