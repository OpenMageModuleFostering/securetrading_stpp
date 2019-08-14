<?php

interface Stpp_PaymentPages_HelperInterface {
    function setAdminAction($bool);
    function prepareStandard(Stpp_Data_Request $request);
    function prepareExtended(Stpp_Data_Request $request);
}