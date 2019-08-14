<?php

class Stpp_Api_Process_Calculation_RiskdecNonriskdecCombined implements Stpp_Api_Process_Calculation_BaseInterface {
	public function calculate(Stpp_Api_Context $context) {
		$riskDecision = $context->getRequestsByRequestType(array(Stpp_Types::API_RISKDEC));
		$nonRiskDecision = $context->getRequestsByRequestType(array(Stpp_Types::API_RISKDEC), true);
		 
		if ($riskDecision && $nonRiskDecision && !$context->getAreAllRequestsSuccessful($riskDecision)) {
			if ($context->getAreAllRequestsSuccessful($nonRiskDecision)) {
				return true;
			}
			return false;
		}
		return null;
	}
}