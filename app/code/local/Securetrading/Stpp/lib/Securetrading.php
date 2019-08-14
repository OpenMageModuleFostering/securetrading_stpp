<?php

class Securetrading {
    protected static $_init = false;
    
    protected static $_rootPath;
    
    protected static $_codePath;
    
    protected static $_corePath;
    
    protected static $_overridesPath;
    
    public static function init() {
        if (static::$_init === false) {
            static::_init();
            static::$_init = true;
        }
    }
    
    public static function cleanup() {
        if (static::$_init === true) {
            spl_autoload_unregister(array('static', '_autoload'));
	    static::$_init = false;
	}
    }
    
    public static function getRootPath() {
        static::init();
        return static::$_rootPath;
    }
    
    public static function getCodePath() {
        static::init();
        return static::$_codePath; 
    }
    
    public static function getCorePath() {
        static::init();
        return static::$_corePath; 
    }
    
    public static function getOverridesPath() {
        static::init();
        return static::$_overridesPath;
    }
    
    protected static function _init() {
        static::$_rootPath = __DIR__ . DIRECTORY_SEPARATOR;
        static::$_codePath = realpath(static::$_rootPath . 'code') . DIRECTORY_SEPARATOR;
        static::$_corePath = realpath(static::$_codePath . 'core') . DIRECTORY_SEPARATOR;
        static::$_overridesPath = realpath(static::$_codePath . 'overrides') . DIRECTORY_SEPARATOR;
        
        $paths = array(
            static::$_rootPath,
            static::$_codePath,
            static::$_corePath,
            static::$_overridesPath,
        );
        
	foreach($paths as $k => $path) {
	    if ($path === DIRECTORY_SEPARATOR) {
	        throw new Exception(sprintf('Invald path: "%s".', $k));
	    }
	}
        
        spl_autoload_register(array('static', '_autoload'), true, true);
    }
    
    protected static function _autoload($class) {
        foreach(array(static::$_overridesPath, static::$_corePath) as $folder) {
            $filePath = $folder . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
            if (file_exists($filePath)) {
                require_once($filePath);
                break;
            }
        }
    }
}