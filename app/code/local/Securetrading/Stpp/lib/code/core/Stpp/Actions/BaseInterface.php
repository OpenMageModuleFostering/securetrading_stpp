<?php

interface Stpp_Actions_BaseInterface {
    function processError(Stpp_Data_Response $response);
    function processAuth(Stpp_Data_Response $response);
    function process3dQuery(Stpp_Data_Response $response);
    function processRiskDecision(Stpp_Data_Response $response);
    function processAccountCheck(Stpp_Data_Response $response);
    function processCardStore(Stpp_Data_Response $response);
    function processTransactionUpdate(Stpp_Data_Response $response);
    function processRefund(Stpp_Data_Response $response);
    function calculateIsTransactionSuccessful(array $responses, $transactionSuccessful);
}