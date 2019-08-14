<?php

interface Stpp_Api_ResultInterface extends Stpp_Result_AbstractInterface {
    function getContext();
    function setContext(Stpp_Api_ContextInterface $context);
    function getRedirectRequired();
    function setRedirectRequired($bool);
    function getIsTransactionSuccessful();
    function setIsTransactionSuccessful($bool);
    function getCustomerErrorMessage();
    function setCustomerErrorMessage($message);
    function getMerchantErrorMessage();
    function setMerchantErrorMessage($message);
}