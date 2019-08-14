<?php

class Stpp_Exception extends Exception {
    protected static $_logInstance;
    
    public static function getExceptionLog() {
        return static::$_logInstance;
    }
    
    public static function setExceptionLog(Stpp_Utility_Log_BaseInterface $exceptionLog) {
        static::$_logInstance = $exceptionLog;
    }
    
    public static function hasExceptionLog() {
        return self::$_logInstance !== null;
    }
    
    public function log() {
        try {
            if ($this::hasExceptionLog()) {
                $this::getExceptionLog()->log($this->__toString());
            }
            else {
                throw $this;
            }
        }
        catch (Exception $e) {
            trigger_error($e->__toString(), E_USER_WARNING); // Production servers should have display_errors set to 0.
        }
    }
}