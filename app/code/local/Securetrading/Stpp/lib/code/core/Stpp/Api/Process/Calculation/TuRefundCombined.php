<?php

class Stpp_Api_Process_Calculation_TuRefundCombined implements Stpp_Api_Process_Calculation_BaseInterface {
	public function calculate(Stpp_Api_Context $context) {
		$requests = $context->getRequests();
		
		if (count($requests) == 2) {
			$request1 = $requests[0];
			$request2 = $requests[1];
		
			if ($request1->get('requesttypedescription') === Stpp_Types::API_TRANSACTIONUPDATE && $request2->get('requesttypedescription') === Stpp_Types::API_REFUND) {
				if ($request1->getIsSuccessful() || $request2->getIsSuccessful()) {
					return true;
				}
				return false;
			}
		}
		return null;
	}
}