<?php

interface Stpp_Api_BaseInterface {
    static function getName();
    function run($requests);
}