<?php

require_once __DIR__ . '/ScheduleService.php';
require_once __DIR__ . '/ErrorHandler.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Config.php';

class CronScheduler {
    
    private $db;
    private $scheduleService;
    private $lockFile;
    
    public function __construct($db) {
        $this->db = $db;
        $this->scheduleService = new ScheduleService($db);
        $this->lockFile = __DIR__ . '/../../sessions/cron.lock';
    }
    
    /**
     * Main cron runner - called every minute
     */
    public function run() {
        // Prevent multiple instances running at the same time
        if (!$this->acquireLock()) {
            AppLogger::info("Cron job already running, skipping");
            return;
        }
        
        try {
            $currentTime = new DateTime();
            $minute = (int) $currentTime->format('i');
            $hour = (int) $currentTime->format('H');
            $dayOfWeek = (int) $currentTime->format('w'); // 0 = Sunday
            
            AppLogger::info("Cron scheduler started", [
                'time' => $currentTime->format('Y-m-d H:i:s'),
                'minute' => $minute,
                'hour' => $hour,
                'day_of_week' => $dayOfWeek
            ]);
            
            $tasksRun = [];
            
            // Process due payments every day at 2:00 AM
            if ($hour === 2 && $minute === 0) {
                $tasksRun[] = $this->runDuePayments();
            }
            
            // Handle expired subscriptions every day at 3:00 AM
            if ($hour === 3 && $minute === 0) {
                $tasksRun[] = $this->runExpiredSubscriptions();
            }
            
            // Generate schedule statistics every day at 8:00 AM
            if ($hour === 8 && $minute === 0) {
                $tasksRun[] = $this->runScheduleStats();
            }
            
            // Health check every hour at minute 0
            if ($minute === 0) {
                $tasksRun[] = $this->runHealthCheck();
            }
            
            // Cleanup logs every Sunday at 4:00 AM
            if ($dayOfWeek === 0 && $hour === 4 && $minute === 0) {
                $tasksRun[] = $this->runLogCleanup();
            }
            
            // Cleanup sessions every 6 hours at minute 30
            if ($minute === 30 && $hour % 6 === 0) {
                $tasksRun[] = $this->runSessionCleanup();
            }
            
            if (!empty($tasksRun)) {
                AppLogger::info("Cron tasks completed", [
                    'tasks_run' => $tasksRun
                ]);
            }
            
        } catch (Exception $e) {
            AppLogger::error("Cron scheduler error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->releaseLock();
        }
    }
    
    /**
     * Run due payments processing
     */
    private function runDuePayments() {
        try {
            AppLogger::info("Starting due payments task");
            $result = $this->scheduleService->processDuePayments();
            
            return [
                'task' => 'due_payments',
                'success' => true,
                'result' => $result
            ];
            
        } catch (Exception $e) {
            AppLogger::error("Due payments task failed", [
                'error' => $e->getMessage()
            ]);
            
            return [
                'task' => 'due_payments',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Run expired subscriptions handling
     */
    private function runExpiredSubscriptions() {
        try {
            AppLogger::info("Starting expired subscriptions task");
            $result = $this->scheduleService->handleExpiredSubscriptions();
            
            return [
                'task' => 'expired_subscriptions',
                'success' => true,
                'result' => $result
            ];
            
        } catch (Exception $e) {
            AppLogger::error("Expired subscriptions task failed", [
                'error' => $e->getMessage()
            ]);
            
            return [
                'task' => 'expired_subscriptions',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Run schedule statistics generation
     */
    private function runScheduleStats() {
        try {
            AppLogger::info("Starting schedule stats task");
            $result = $this->scheduleService->getScheduleStats();
            
            // Log the statistics
            AppLogger::info("Schedule statistics", $result);
            
            return [
                'task' => 'schedule_stats',
                'success' => true,
                'result' => $result
            ];
            
        } catch (Exception $e) {
            AppLogger::error("Schedule stats task failed", [
                'error' => $e->getMessage()
            ]);
            
            return [
                'task' => 'schedule_stats',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Run health check
     */
    private function runHealthCheck() {
        try {
            $checks = [];
            
            // Check database connection
            try {
                $this->db->query("SELECT 1");
                $checks['database'] = true;
            } catch (Exception $e) {
                $checks['database'] = false;
                AppLogger::error("Database health check failed", ['error' => $e->getMessage()]);
            }
            
            // Check log directory writability
            $logDir = __DIR__ . '/../../logs';
            $checks['logs_writable'] = is_writable($logDir);
            
            // Check sessions directory writability
            $sessionDir = __DIR__ . '/../../sessions';
            $checks['sessions_writable'] = is_writable($sessionDir);
            
            // Count active subscriptions
            $activeSubscriptions = $this->db->count('subscriptions', ['status' => 'active']);
            $checks['active_subscriptions'] = $activeSubscriptions;
            
            AppLogger::info("Health check completed", $checks);
            
            return [
                'task' => 'health_check',
                'success' => true,
                'result' => $checks
            ];
            
        } catch (Exception $e) {
            AppLogger::error("Health check failed", [
                'error' => $e->getMessage()
            ]);
            
            return [
                'task' => 'health_check',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Run log cleanup (remove old log files)
     */
    private function runLogCleanup() {
        try {
            $logDir = __DIR__ . '/../../logs';
            $daysToKeep = 30; // Keep logs for 30 days
            $cutoffDate = time() - ($daysToKeep * 24 * 60 * 60);
            
            $cleanedFiles = 0;
            $totalSize = 0;
            
            if (is_dir($logDir)) {
                $files = glob($logDir . '/*.log');
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < $cutoffDate) {
                        $size = filesize($file);
                        if (unlink($file)) {
                            $cleanedFiles++;
                            $totalSize += $size;
                        }
                    }
                }
            }
            
            AppLogger::info("Log cleanup completed", [
                'files_cleaned' => $cleanedFiles,
                'bytes_freed' => $totalSize,
                'days_kept' => $daysToKeep
            ]);
            
            return [
                'task' => 'log_cleanup',
                'success' => true,
                'result' => [
                    'files_cleaned' => $cleanedFiles,
                    'bytes_freed' => $totalSize
                ]
            ];
            
        } catch (Exception $e) {
            AppLogger::error("Log cleanup failed", [
                'error' => $e->getMessage()
            ]);
            
            return [
                'task' => 'log_cleanup',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Run session cleanup (remove old session files)
     */
    private function runSessionCleanup() {
        try {
            $sessionDir = __DIR__ . '/../../sessions';
            $maxAge = 24 * 60 * 60; // 24 hours
            $cutoffTime = time() - $maxAge;
            
            $cleanedFiles = 0;
            $totalSize = 0;
            
            if (is_dir($sessionDir)) {
                $files = glob($sessionDir . '/sess_*');
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < $cutoffTime) {
                        $size = filesize($file);
                        if (unlink($file)) {
                            $cleanedFiles++;
                            $totalSize += $size;
                        }
                    }
                }
                
                // Also clean up rate limit files
                $rateLimitFiles = glob($sessionDir . '/rate_limit_*');
                foreach ($rateLimitFiles as $file) {
                    if (is_file($file) && filemtime($file) < $cutoffTime) {
                        $size = filesize($file);
                        if (unlink($file)) {
                            $cleanedFiles++;
                            $totalSize += $size;
                        }
                    }
                }
            }
            
            AppLogger::info("Session cleanup completed", [
                'files_cleaned' => $cleanedFiles,
                'bytes_freed' => $totalSize,
                'max_age_hours' => $maxAge / 3600
            ]);
            
            return [
                'task' => 'session_cleanup',
                'success' => true,
                'result' => [
                    'files_cleaned' => $cleanedFiles,
                    'bytes_freed' => $totalSize
                ]
            ];
            
        } catch (Exception $e) {
            AppLogger::error("Session cleanup failed", [
                'error' => $e->getMessage()
            ]);
            
            return [
                'task' => 'session_cleanup',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Acquire lock to prevent multiple cron instances
     */
    private function acquireLock() {
        $currentTime = time();
        
        if (file_exists($this->lockFile)) {
            $lockTime = filemtime($this->lockFile);
            
            // If lock is older than 5 minutes, consider it stale and remove it
            if ($currentTime - $lockTime > 300) {
                unlink($this->lockFile);
                AppLogger::warning("Removed stale cron lock file");
            } else {
                return false;
            }
        }
        
        return file_put_contents($this->lockFile, $currentTime) !== false;
    }
    
    /**
     * Release lock
     */
    private function releaseLock() {
        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }
    
    /**
     * Get next scheduled tasks (for debugging/monitoring)
     */
    public function getUpcomingTasks() {
        $currentTime = new DateTime();
        $tasks = [];
        
        // Calculate next due payments run (daily at 2:00 AM)
        $nextDuePayments = clone $currentTime;
        $nextDuePayments->setTime(2, 0, 0);
        if ($currentTime >= $nextDuePayments) {
            $nextDuePayments->add(new DateInterval('P1D'));
        }
        $tasks[] = [
            'name' => 'Process Due Payments',
            'next_run' => $nextDuePayments->format('Y-m-d H:i:s'),
            'frequency' => 'Daily at 2:00 AM'
        ];
        
        // Calculate next expired subscriptions run (daily at 3:00 AM)
        $nextExpired = clone $currentTime;
        $nextExpired->setTime(3, 0, 0);
        if ($currentTime >= $nextExpired) {
            $nextExpired->add(new DateInterval('P1D'));
        }
        $tasks[] = [
            'name' => 'Handle Expired Subscriptions',
            'next_run' => $nextExpired->format('Y-m-d H:i:s'),
            'frequency' => 'Daily at 3:00 AM'
        ];
        
        // Calculate next schedule stats run (daily at 8:00 AM)
        $nextStats = clone $currentTime;
        $nextStats->setTime(8, 0, 0);
        if ($currentTime >= $nextStats) {
            $nextStats->add(new DateInterval('P1D'));
        }
        $tasks[] = [
            'name' => 'Generate Schedule Statistics',
            'next_run' => $nextStats->format('Y-m-d H:i:s'),
            'frequency' => 'Daily at 8:00 AM'
        ];
        
        // Calculate next health check (hourly)
        $nextHealth = clone $currentTime;
        $nextHealth->setTime($currentTime->format('H'), 0, 0);
        $nextHealth->add(new DateInterval('PT1H'));
        $tasks[] = [
            'name' => 'Health Check',
            'next_run' => $nextHealth->format('Y-m-d H:i:s'),
            'frequency' => 'Hourly'
        ];
        
        return $tasks;
    }
} 