<?php

interface Stpp_Utility_Log_UserInterface {
    public function setLogWriter(Stpp_Utility_Log_WriterInterface $logger);
    public function getLogWriter(); 
}