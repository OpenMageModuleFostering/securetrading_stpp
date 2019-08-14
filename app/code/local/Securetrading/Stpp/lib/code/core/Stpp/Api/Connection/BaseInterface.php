<?php

interface Stpp_Api_Connection_BaseInterface {
    static function getKey();
    static function getName();
    function setAlias($alias);
    function getAlias();
    function sendAndReceiveData($xmlString);
}