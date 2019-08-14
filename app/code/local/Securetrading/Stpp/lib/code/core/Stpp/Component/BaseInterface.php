<?php

interface Stpp_Component_BaseInterface {
    function setTranslator(Stpp_Utility_Translator_BaseInterface $translator);
    function setDebugLog(Stpp_Utility_Log_BaseInterface $log);
    function getTranslator();
    function getDebugLog();
    function __($message);
}