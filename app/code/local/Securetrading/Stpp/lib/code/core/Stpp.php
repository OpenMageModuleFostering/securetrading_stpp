<?php

class Stpp {
    public static function getLogsPath() {
        return Securetrading::getRootPath() . 'stpp_logs' . DIRECTORY_SEPARATOR;
    }
    
    public static function getLogsArchivePath() {
        return static::getLogsPath() . 'archive' . DIRECTORY_SEPARATOR;
    }
    
    public static function getTranslationsPath() {
        return Securetrading::getRootPath() . 'stpp_translations' . DIRECTORY_SEPARATOR;
    }
}