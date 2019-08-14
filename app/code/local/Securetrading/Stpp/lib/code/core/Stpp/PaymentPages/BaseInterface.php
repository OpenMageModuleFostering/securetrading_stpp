<?php

interface Stpp_PaymentPages_BaseInterface {
    static function getName();
    function run(Stpp_Data_Request $request);
    function runNotification();
    function validateRedirect();
}