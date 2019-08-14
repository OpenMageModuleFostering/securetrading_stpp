<?php

interface Stpp_Api_HelperInterface {
    function generateRequests(Stpp_Data_Request $originalRequest, array $requestTypes);
    function prepareStandard(Stpp_Data_Request $originalRequest);
    function prepare3dAuth(Stpp_Data_Request $request);
    function prepareRefund(Stpp_Data_Request $originalRequest);
    function prepareTransactionUpdate(Stpp_Data_Request $request);
}