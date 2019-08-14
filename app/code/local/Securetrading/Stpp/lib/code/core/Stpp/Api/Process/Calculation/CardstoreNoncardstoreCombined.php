<?php

class Stpp_Api_Process_Calculation_CardstoreNoncardstoreCombined implements Stpp_Api_Process_Calculation_BaseInterface {
	public function calculate(Stpp_Api_Context $context) {
		$cardStore = $context->getRequestsByRequestType(array(Stpp_Types::API_CARDSTORE));
		$nonCardStore = $context->getRequestsByRequestType(array(Stpp_Types::API_CARDSTORE), true);
		 
		if ($cardStore && $nonCardStore && !$context->getAreAllRequestsSuccessful($cardStore)) {
			if ($context->getAreAllRequestsSuccessful($nonCardStore)) {
				return true; // If one or more CARDSTORE requests failed but other request types exist and are all successful.
			}
			return false;
		}
		return null;
	}
}