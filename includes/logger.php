<?php
class Logger {
    private static $logFile = 'logs/app.log';
    
    public static function initialize() {
        if (!file_exists('logs')) {
            mkdir('logs', 0777, true);
        }
    }
    
    public static function log($message, $type = 'INFO') {
        self::initialize();
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;
        
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
    }
    
    public static function error($message) {
        self::log($message, 'ERROR');
    }
    
    public static function info($message) {
        self::log($message, 'INFO');
    }
    
    public static function warning($message) {
        self::log($message, 'WARNING');
    }
    
    public static function debug($message) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            self::log($message, 'DEBUG');
        }
    }
}
?> 