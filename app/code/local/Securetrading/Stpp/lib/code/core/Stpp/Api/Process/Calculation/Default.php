<?php

class Stpp_Api_Process_Calculation_Default implements Stpp_Api_Process_Calculation_BaseInterface {  
	public function calculate(Stpp_Api_Context $context) {
		return $context->getAreAllRequestsSuccessful();
	}
}