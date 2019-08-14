<?php

interface Stpp_Api_Connection_StoreInterface {
    function registerConnection(Stpp_Api_Connection_BaseInterface $connection);
    function setActive($key);
    function getActive();
    function get($key);
    function getAll();
}