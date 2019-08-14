<?php

interface Stpp_TypesInterface {
    static function getRequestAndResponseTypes();
    static function getAccountTypeDescriptions();
    static function getCardTypes();
    static function getTelTypes();
    static function getCustomerShippingMethods();
    static function getSettleStatuses();
    static function getSettleDueDates();
}