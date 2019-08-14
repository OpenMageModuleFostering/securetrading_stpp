<?php

interface Stpp_PaymentPages_ResultInterface extends Stpp_Result_AbstractInterface {
    function getRequest();
    function setRequest(Stpp_Data_Request $request);
}