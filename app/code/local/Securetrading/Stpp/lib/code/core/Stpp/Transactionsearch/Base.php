<?php

class Stpp_Transactionsearch_Base extends Stpp_Http_Base implements Stpp_Transactionsearch_BaseInterface {
	protected $_filterTypes = array(
		'accounttypedescriptions',
		'requesttypedescriptions',
		'currencyiso3a',
		'paymentttypedescriptions',
		'settlestatus',
		'errorcodes',
		'transactionreferences',
		'orderreferences',
	);
	
	protected $_url = 'https://myst.securetrading.net/auto/transactions/transactionsearch?';
	
	protected $_startDate = '';
	
	protected $_endDate = '';
	
	protected $_siteReferences = array();
	
	protected $_filters = array();
	
	protected $_optionalFields = array();
	
	protected $_rawCsvString = '';
	
	protected $_parsedCsvArray = array();
	
	public function setStartDate($startDate) {
		$this->_startDate = $startDate;
		return $this;
	}
	
	public function getStartDate() {
		return $this->_startDate;
	}
	
	public function setEndDate($endDate) {
		$this->_endDate = $endDate;
		return $this;
	}
	
	public function getEndDate() {
		return $this->_endDate;
	}
	
	public function addSiteReference($siteReference) {
		$this->_siteReferences[$siteReference] = true;
		return $this;
	}
	
	public function removeSiteReference($siteReference) {
		unset($this->_siteReferences[$siteReference]);
		return $this;
	}
	
	public function setSiteReferences($siteReferences) {
		if (!is_array($siteReferences)) {
			$siteReferences = array($siteReferences);
		}
		$this->_siteReferences = array_fill_keys($siteReferences, true);
		return $this;
	}
	
	public function getSiteReferences() {
		return $this->_siteReferences;
	}
	
	public function addFilter($filterType, $filterValue) {
		$this->_validateFilterType($filterType);
		$this->_filters[$filterType][$filterValue] = true;
		return $this;
	}
	
	public function removeFilter($filterType, $filterValue) {
		$this->_validateFilterType($filterType);
		unset($this->_filters[$filterType][$filterValue]);
		return $this;
	}
	
	public function setFilter($filterType, $filterValue) {
		$this->_validateFilterType($filterType);
		if (!is_array($filterValue)) {
			$filterValue = array($filterValue);
		}
		$this->_filters[$filterType] = array_fill_keys($filterValue, true);
		return $this;
	}

	public function setFilters($filters) {
	  foreach($filters as $filterType => $filterValue) {
	    $this->setFilter($filterType, $filterValue);
	  }
	  return $this;
	}

	public function getFilter($filterType) {
		$this->_validateFilterType($filterType);
		return $this->_filters[$filterType];
	}
	
	public function addOptionalField($field) {
		$this->_optionalFields[$field] = true;
		return $this;
	}
	
	public function removeOptionalField($field) {
		unset($this->_optionalFields[$field]);
		return $this;
	}
	
	public function setOptionalFields($fields) {
		if (!is_array($fields)) {
			$fields = array($fields);
		}
		$this->_optionalFields = array_fill_keys($fields, true);
		return $this;
	}
	
	public function getOptionalFields() {
		return $this->_optionalFields;
	}
	
	public function getCsvData() {
		if (empty($this->_rawCsvString)) {
			$this->httpPost();
		}
		return $this->_parsedCsvArray;
	}
	
	public function getRawCsvData() {
		if (empty($this->_rawCsvString)) {
			$this->httpPost();
		}
		return $this->_rawCsvString;
	}
	
	public function httpPost($requestBody = '') {
		$this->_formUrl();
		$httpResponseBody = parent::httpPost($requestBody);
		if (($httpResponseCode = $this->getInfo(CURLINFO_HTTP_CODE)) !== 200) {
		  throw new Stpp_Exception(sprintf($this->__('Unexpected HTTP response code: "%s".'), $httpResponseCode));
		}
		$this->_rawCsvString = $httpResponseBody;
		$this->_parsedCsvArray = $this->_parseCsv($this->_rawCsvString);
		return $this;
	}
	
	protected function _validateFilterType($filterType) {
		if (!in_array($filterType, $this->_filterTypes)) {
			throw new Stpp_Exception(sprintf($this->__('The filter type "%s" does not exist.'), $filterType));
		}
		return $this;
	}
	
	protected function _formUrl() {
		$array = $this->_filters;
		$array['startdate'] = array($this->getStartDate() => true);
		$array['enddate'] = array($this->getEndDate() => true);
		$array['sitereferences'] = $this->getSiteReferences();
		$array['optionalfields'] = $this->getOptionalFields();
	
		$queryParams = array();
		foreach($array as $queryKey => $queryValues) {
			foreach($queryValues as $queryValue => $dummy) {
				$queryParams[] = urlencode($queryKey) . '=' . urlencode($queryValue);
			}
		}
		$queryString = implode('&', $queryParams);
		$this->_url .= $queryString;
		return $this;
	}
	
	protected function _parseCsv($rawCsvString) {
		$rows = str_getcsv($rawCsvString, "\n");
		$finalRows = array();
		
		foreach($rows as $row) {
			$finalRows[] = str_getcsv($row, ',');
		}
		array_shift($finalRows);
		return $finalRows;
	}
}