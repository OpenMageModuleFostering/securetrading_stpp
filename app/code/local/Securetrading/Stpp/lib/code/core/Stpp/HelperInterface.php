<?php

interface Stpp_HelperInterface{
    function getFilteredCardTypes($use3dSecure, $enabledCardTypes = array());
    function getCcLast4($cardNumber);
}