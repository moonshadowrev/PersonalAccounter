<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class AppLogger
{
    private static $logger = null;
    private static $initialized = false;

    /**
     * Initialize the logger
     */
    public static function init()
    {
        if (self::$initialized) {
            return;
        }

        try {
            $config = Config::get('logging');
            $channel = $config['channels'][$config['default']];
            
            // Ensure log directory exists
            $logDir = dirname($channel['path']);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            // Create formatter
            $formatter = new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                "Y-m-d H:i:s",
                true,
                true
            );

            // Create handler based on configuration
            if (isset($channel['max_files']) && $channel['max_files'] > 1) {
                $handler = new RotatingFileHandler(
                    $channel['path'],
                    $channel['max_files'],
                    self::getLogLevel($channel['level'])
                );
            } else {
                $handler = new StreamHandler(
                    $channel['path'],
                    self::getLogLevel($channel['level'])
                );
            }

            $handler->setFormatter($formatter);

            // Create logger
            self::$logger = new Logger('AccountingPanel');
            self::$logger->pushHandler($handler);

            self::$initialized = true;

        } catch (Exception $e) {
            // Fallback to error_log if logger initialization fails
            error_log("Logger initialization failed: " . $e->getMessage());
            self::createFallbackLogger();
        }
    }

    /**
     * Get the logger instance
     */
    public static function getLogger()
    {
        if (!self::$initialized) {
            self::init();
        }

        return self::$logger;
    }

    /**
     * Log an info message
     */
    public static function info($message, array $context = [])
    {
        if (self::shouldLog('info')) {
            self::getLogger()->info($message, $context);
        }
    }

    /**
     * Log an error message
     */
    public static function error($message, array $context = [])
    {
        if (self::shouldLog('error')) {
            self::getLogger()->error($message, $context);
        }
    }

    /**
     * Log a warning message
     */
    public static function warning($message, array $context = [])
    {
        if (self::shouldLog('warning')) {
            self::getLogger()->warning($message, $context);
        }
    }

    /**
     * Log a debug message
     */
    public static function debug($message, array $context = [])
    {
        if (self::shouldLog('debug')) {
            self::getLogger()->debug($message, $context);
        }
    }

    /**
     * Log a critical message
     */
    public static function critical($message, array $context = [])
    {
        if (self::shouldLog('critical')) {
            self::getLogger()->critical($message, $context);
        }
    }

    /**
     * Check if we should log based on environment and debug settings
     */
    private static function shouldLog($level)
    {
        // Check if logging is completely disabled
        if (filter_var($_ENV['LOG_DISABLED'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        // Always log critical errors (unless completely disabled above)
        if ($level === 'critical' || $level === 'error') {
            return true;
        }

        // Check if debug is enabled
        $debug = Config::get('debug', false);
        $env = Config::get('env', 'production');

        // In production with debug off, only log warnings and above
        if ($env === 'production' && !$debug) {
            return in_array($level, ['warning', 'error', 'critical']);
        }

        // In development or with debug on, log everything
        return true;
    }

    /**
     * Convert string log level to Monolog constant
     */
    private static function getLogLevel($level)
    {
        $levels = [
            'debug' => Logger::DEBUG,
            'info' => Logger::INFO,
            'notice' => Logger::NOTICE,
            'warning' => Logger::WARNING,
            'error' => Logger::ERROR,
            'critical' => Logger::CRITICAL,
            'alert' => Logger::ALERT,
            'emergency' => Logger::EMERGENCY,
        ];

        return $levels[strtolower($level)] ?? Logger::DEBUG;
    }

    /**
     * Create a fallback logger when initialization fails
     */
    private static function createFallbackLogger()
    {
        try {
            $logFile = __DIR__ . '/../../logs/fallback.log';
            $logDir = dirname($logFile);
            
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $handler = new StreamHandler($logFile, Logger::ERROR);
            $formatter = new LineFormatter(
                "[%datetime%] FALLBACK.%level_name%: %message%\n",
                "Y-m-d H:i:s"
            );
            $handler->setFormatter($formatter);

            self::$logger = new Logger('FallbackLogger');
            self::$logger->pushHandler($handler);

            self::$initialized = true;

        } catch (Exception $e) {
            // Ultimate fallback - just use error_log
            self::$logger = null;
        }
    }

    /**
     * Check if logger is properly initialized
     */
    public static function isInitialized()
    {
        return self::$initialized && self::$logger !== null;
    }
} 