<?php

interface Stpp_Transactionsearch_BaseInterface extends Stpp_Http_BaseInterface {
	function setStartDate($startDate);
	function getStartDate();
	function setEndDate($endDate);
	function getEndDate();
	function addSiteReference($siteReference);
	function removeSiteReference($siteReference);
	function setSiteReferences($siteReferences);
	function getSiteReferences();
	function addFilter($filterType, $filterValue);
	function removeFilter($filterType, $filterValue);
	function setFilter($filterType, $filterValues);
	function getFilter($filterTypes);
	function addOptionalField($field);
	function removeOptionalField($field);
	function setOptionalFields($fields);
	function getOptionalFields();
	function getCsvData();
	function getRawCsvData();
}