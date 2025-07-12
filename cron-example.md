# Accounting Panel - Consolidated Cron Job Configuration
# 
# Add this single line to your crontab to automate all scheduled tasks
# To edit crontab: crontab -e
# To view current crontab: crontab -l

# NEW CONSOLIDATED CRON (runs every minute with internal scheduling)
* * * * * cd /path/to/your/accounting-panel && php control schedule cron >> logs/cron.log 2>&1

# The consolidated cron automatically handles:
# - Process due payments (daily at 2:00 AM)
# - Handle expired subscriptions (daily at 3:00 AM)
# - Generate schedule statistics (daily at 8:00 AM)
# - Health checks (hourly)
# - Log cleanup (weekly on Sunday at 4:00 AM)
# - Session cleanup (every 6 hours)

# LEGACY CRON COMMANDS (deprecated - use consolidated cron above instead)
# 0 2 * * * cd /path/to/your/accounting-panel && php control schedule run >> logs/cron.log 2>&1
# 0 3 * * * cd /path/to/your/accounting-panel && php control schedule expired >> logs/cron.log 2>&1
# 0 8 * * * cd /path/to/your/accounting-panel && php control schedule stats >> logs/cron.log 2>&1

# NOTE: The retry failed transactions cron has been removed as it's not connected to external services

# Notes:
# - Replace "/path/to/your/accounting-panel" with the actual path to your project
# - Make sure the logs directory exists and is writable
# - Test the commands manually first: php control schedule run
# - Monitor the logs/cron.log file for any errors
# - Adjust timing based on your business needs

# Cron format explanation:
# * * * * * command
# │ │ │ │ │
# │ │ │ │ └─── Day of week (0-7, Sunday = 0 or 7)
# │ │ │ └───── Month (1-12)
# │ │ └─────── Day of month (1-31)
# │ └───────── Hour (0-23)
# └─────────── Minute (0-59) 

# Edit your crontab
crontab -e

# Add the consolidated cron job (adjust path as needed)
* * * * * cd /path/to/your/project && php control schedule cron >> logs/cron.log 2>&1