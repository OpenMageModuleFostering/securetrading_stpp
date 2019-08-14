<?php

interface Stpp_Result_AbstractInterface {
    function getRedirectIsPost();
    function setRedirectIsPost($bool);
    function getRedirectUrl();
    function setRedirectUrl($url);
    function getRedirectData();
    function setRedirectData(array $data);
}