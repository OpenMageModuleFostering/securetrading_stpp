<?php

class Stpp_Component_Store {     
    protected static $_components = array();
    
    protected static $_translator;
    
    protected static $_debugLog;
    
    public static function registerComponent(Stpp_Component_BaseInterface $component) {
        $component->setTranslator(self::$_translator);
        $component->setDebugLog(self::$_debugLog);
        self::$_components[] = $component;
    }
    
    public static function registerTranslator(Stpp_Utility_Translator_BaseInterface $translator) {
        self::$_translator = $translator;
        
        foreach(self::$_components as $component) {
            $component->setTranslator($translator);
        }
    }
    
    public static function registerDebugLog(Stpp_Utility_Log_BaseInterface $log) {
        self::$_debugLog = $log;
        
        foreach(self::$_components as $component) {
            $component->setDebugLog($log);
        }
    }
}