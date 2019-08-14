<?php

interface Stpp_Api_Xml_WriterInterface {
    function startRequestBlock($apiVersion, $alias);
    function endRequestBlock();
    
    function startRequest(Stpp_Data_Request $request);
    function endRequest();
    
    function prepareAuth(Stpp_Data_Request $request);
    function prepare3dQuery(Stpp_Data_Request $request);
    function prepare3dAuth(Stpp_Data_Request $request);
    function prepareRiskDecision(Stpp_Data_Request $request);
    function prepareAccountCheck(Stpp_Data_Request $request);
    function prepareCardStore(Stpp_Data_Request $request);
    function prepareTransactionUpdate(Stpp_Data_Request $request);
    function prepareRefund(Stpp_Data_Request $request);
}