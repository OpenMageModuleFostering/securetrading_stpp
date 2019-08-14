<?php

interface Stpp_Api_LogInterface {
    function setDoNotLog(array $array = array());
    function getDoNotLog();
    function log(Stpp_Data_Response $request);
}