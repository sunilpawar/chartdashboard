-- Chart Dashboard Extension Installation SQL

-- Cache table for improved performance
CREATE TABLE IF NOT EXISTS `civicrm_chartdashboard_cache` (
                                                            `id` int unsigned NOT NULL AUTO_INCREMENT,
                                                            `cache_key` varchar(255) NOT NULL COMMENT 'Unique cache key',
  `cache_data` longtext COMMENT 'Cached data in JSON format',
  `expires_at` datetime COMMENT 'Cache expiration time',
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'When cache entry was created',
  `updated_date` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When cache entry was last updated',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cache_key` (`cache_key`),
  KEY `expires_at` (`expires_at`),
  KEY `created_date` (`created_date`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cache table for Chart Dashboard extension';

-- Email alerts log table
CREATE TABLE IF NOT EXISTS `civicrm_chartdashboard_alerts` (
                                                             `id` int unsigned NOT NULL AUTO_INCREMENT,
                                                             `alert_type` varchar(50) NOT NULL COMMENT 'Type of alert (low_donations, goal_achieved, etc.)',
  `alert_data` text COMMENT 'Alert data in JSON format',
  `sent_to` varchar(255) COMMENT 'Email address alert was sent to',
  `sent_date` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'When alert was sent',
  `status` varchar(20) DEFAULT 'sent' COMMENT 'Alert status (sent, failed, pending)',
  `retry_count` int DEFAULT 0 COMMENT 'Number of retry attempts',
  `error_message` text COMMENT 'Error message if alert failed',
  PRIMARY KEY (`id`),
  KEY `alert_type` (`alert_type`),
  KEY `sent_date` (`sent_date`),
  KEY `status` (`status`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Email alerts log for Chart Dashboard extension';

-- Dashboard configurations table (optional - can use settings API instead)
CREATE TABLE IF NOT EXISTS `civicrm_chartdashboard_configs` (
                                                              `id` int unsigned NOT NULL AUTO_INCREMENT,
                                                              `contact_id` int unsigned COMMENT 'User ID who owns this configuration',
                                                              `config_name` varchar(255) DEFAULT 'Default' COMMENT 'Configuration name',
  `config_data` longtext COMMENT 'Dashboard configuration in JSON format',
  `is_default` tinyint(1) DEFAULT 0 COMMENT 'Whether this is the default configuration for the user',
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_date` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  KEY `is_default` (`is_default`),
  CONSTRAINT `FK_chartdashboard_configs_contact_id`
  FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`)
                                                     ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Dashboard configurations for Chart Dashboard extension';

-- Performance indexes for main CiviCRM tables used by dashboard
-- These improve query performance for dashboard analytics

-- Contribution table indexes for faster dashboard queries
CREATE INDEX `idx_contribution_dashboard_status_date`
  ON `civicrm_contribution` (`contribution_status_id`, `receive_date`);

CREATE INDEX `idx_contribution_dashboard_date_contact`
  ON `civicrm_contribution` (`receive_date`, `contact_id`);

CREATE INDEX `idx_contribution_dashboard_campaign`
  ON `civicrm_contribution` (`campaign_id`, `receive_date`, `contribution_status_id`);

CREATE INDEX `idx_contribution_dashboard_recur`
  ON `civicrm_contribution` (`contribution_recur_id`, `receive_date`, `contribution_status_id`);

-- Contribution recur table indexes
CREATE INDEX `idx_contribution_recur_dashboard`
  ON `civicrm_contribution_recur` (`start_date`, `contact_id`);

-- Campaign table indexes
CREATE INDEX `idx_campaign_dashboard_active`
  ON `civicrm_campaign` (`is_active`, `start_date`, `end_date`);

-- Membership table indexes for membership revenue analysis
CREATE INDEX `idx_membership_dashboard_type_date`
  ON `civicrm_membership` (`membership_type_id`, `start_date`, `status_id`);

-- Pledge table indexes for pledged vs actual analysis
CREATE INDEX `idx_pledge_dashboard_date`
  ON `civicrm_pledge` (`create_date`, `status_id`);

CREATE INDEX `idx_pledge_payment_dashboard`
  ON `civicrm_pledge_payment` (`pledge_id`, `scheduled_date`, `status_id`);

-- Contact table indexes for donor analysis
CREATE INDEX `idx_contact_dashboard_created`
  ON `civicrm_contact` (`created_date`, `contact_type`, `is_deleted`);

-- Activity table indexes if needed for engagement tracking (optional)
CREATE INDEX `idx_activity_dashboard_contact_date`
  ON `civicrm_activity_contact` (`contact_id`, `record_type_id`);

-- Insert default dashboard layouts for different user roles (optional)
INSERT IGNORE INTO `civicrm_chartdashboard_configs`
(`contact_id`, `config_name`, `config_data`, `is_default`)
VALUES
(NULL, 'Default Admin Layout',
 JSON_OBJECT(
   'layout', 'grid',
   'charts', JSON_ARRAY(
     JSON_OBJECT('chart_id', 'realtime_donations', 'chart_type', 'line', 'time_range', '7days', 'position', 1, 'size', 'large'),
     JSON_OBJECT('chart_id', 'recurring_vs_onetime', 'chart_type', 'stacked_bar', 'time_range', '1month', 'position', 2, 'size', 'medium'),
     JSON_OBJECT('chart_id', 'campaign_progress', 'chart_type', 'progress', 'time_range', '', 'position', 3, 'size', 'medium'),
     JSON_OBJECT('chart_id', 'donor_retention', 'chart_type', 'funnel', 'time_range', '', 'position', 4, 'size', 'large'),
     JSON_OBJECT('chart_id', 'avg_gift_trend', 'chart_type', 'line', 'time_range', '6months', 'position', 5, 'size', 'medium'),
     JSON_OBJECT('chart_id', 'membership_revenue', 'chart_type', 'pie', 'time_range', '1year', 'position', 6, 'size', 'medium')
   )
 ),
 1);

-- Create a view for commonly used dashboard metrics (optional optimization)
CREATE OR REPLACE VIEW `vw_chartdashboard_metrics` AS
SELECT
  DATE(c.receive_date) as donation_date,
  COUNT(*) as donation_count,
  SUM(c.total_amount) as total_amount,
  AVG(c.total_amount) as avg_amount,
  COUNT(DISTINCT c.contact_id) as unique_donors,
  SUM(CASE WHEN cr.id IS NOT NULL THEN c.total_amount ELSE 0 END) as recurring_amount,
  SUM(CASE WHEN cr.id IS NULL THEN c.total_amount ELSE 0 END) as onetime_amount,
  COUNT(CASE WHEN cr.id IS NOT NULL THEN 1 END) as recurring_count,
  COUNT(CASE WHEN cr.id IS NULL THEN 1 END) as onetime_count
FROM civicrm_contribution c
  LEFT JOIN civicrm_contribution_recur cr ON c.contribution_recur_id = cr.id
WHERE c.contribution_status_id = 1  -- Completed contributions only
  AND c.receive_date >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR)  -- Last 2 years
GROUP BY DATE(c.receive_date)
ORDER BY donation_date DESC;

-- Create stored procedure for cache cleanup (optional)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS CleanChartDashboardCache()
BEGIN
DELETE FROM civicrm_chartdashboard_cache
WHERE expires_at < NOW();

-- Keep only last 1000 alert records to prevent table bloat
DELETE FROM civicrm_chartdashboard_alerts
WHERE id NOT IN (
  SELECT id FROM (
                   SELECT id FROM civicrm_chartdashboard_alerts
                   ORDER BY sent_date DESC
                     LIMIT 1000
                 ) as t
);
END //
DELIMITER ;

-- Create event scheduler for automatic cache cleanup (if MySQL events are enabled)
-- Note: This requires EVENT privilege and @@global.event_scheduler to be ON
CREATE EVENT IF NOT EXISTS chartdashboard_cleanup
ON SCHEDULE EVERY 1 HOUR
DO
  CALL CleanChartDashboardCache();

-- Insert sample alert configurations
INSERT IGNORE INTO civicrm_option_group (name, title, description, is_reserved, is_active)
VALUES ('chartdashboard_alert_types', 'Chart Dashboard Alert Types', 'Types of alerts that can be sent by Chart Dashboard', 1, 1);

SET @option_group_id = (SELECT id FROM civicrm_option_group WHERE name = 'chartdashboard_alert_types');

INSERT IGNORE INTO civicrm_option_value (option_group_id, label, value, name, description, weight, is_active)
VALUES
(@option_group_id, 'Low Donation Alert', 'low_donations', 'low_donations', 'Alert when daily donations fall below threshold', 1, 1),
(@option_group_id, 'Goal Achievement Alert', 'goal_achieved', 'goal_achieved', 'Alert when campaign reaches goal threshold', 2, 1),
(@option_group_id, 'Donor Retention Alert', 'donor_retention', 'donor_retention', 'Alert about donor retention metrics', 3, 1),
(@option_group_id, 'System Error Alert', 'system_error', 'system_error', 'Alert when dashboard encounters errors', 4, 1);

-- Create indexes on option tables for faster lookups
CREATE INDEX `idx_option_value_ chartdashboard`
  ON `civicrm_option_value` (`option_group_id`, `name`, `is_active`);

-- Grant necessary permissions (these are typically handled by CiviCRM's permission system)
-- The actual permission enforcement is done in PHP code

COMMIT;
