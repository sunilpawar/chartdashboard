-- Chart Dashboard Extension Uninstallation SQL

-- Drop event scheduler first
DROP EVENT IF EXISTS chartdashboard_cleanup;

-- Drop stored procedures
DROP PROCEDURE IF EXISTS CleanChartDashboardCache;

-- Drop views
DROP VIEW IF EXISTS vw_chartdashboard_metrics;

-- Drop extension-specific tables
DROP TABLE IF EXISTS `civicrm_chartdashboard_configs`;
DROP TABLE IF EXISTS `civicrm_chartdashboard_alerts`;
DROP TABLE IF EXISTS `civicrm_chartdashboard_cache`;

-- Remove dashboard-specific indexes from core CiviCRM tables
-- Note: Be careful with this - only remove indexes we specifically created
-- and ensure they're not being used by other extensions or core functionality

DROP INDEX IF EXISTS `idx_contribution_dashboard_status_date` ON `civicrm_contribution`;
DROP INDEX IF EXISTS `idx_contribution_dashboard_date_contact` ON `civicrm_contribution`;
DROP INDEX IF EXISTS `idx_contribution_dashboard_campaign` ON `civicrm_contribution`;
DROP INDEX IF EXISTS `idx_contribution_dashboard_recur` ON `civicrm_contribution`;

DROP INDEX IF EXISTS `idx_contribution_recur_dashboard` ON `civicrm_contribution_recur`;
DROP INDEX IF EXISTS `idx_campaign_dashboard_active` ON `civicrm_campaign`;
DROP INDEX IF EXISTS `idx_membership_dashboard_type_date` ON `civicrm_membership`;
DROP INDEX IF EXISTS `idx_pledge_dashboard_date` ON `civicrm_pledge`;
DROP INDEX IF EXISTS `idx_pledge_payment_dashboard` ON `civicrm_pledge_payment`;
DROP INDEX IF EXISTS `idx_contact_dashboard_created` ON `civicrm_contact`;
DROP INDEX IF EXISTS `idx_activity_dashboard_contact_date` ON `civicrm_activity_contact`;
DROP INDEX IF EXISTS `idx_option_value_chartdashboard` ON `civicrm_option_value`;

-- Remove option group and values for alert types
DELETE ov FROM civicrm_option_value ov
INNER JOIN civicrm_option_group og ON ov.option_group_id = og.id
WHERE og.name = 'chartdashboard_alert_types';

DELETE FROM civicrm_option_group WHERE name = 'chartdashboard_alert_types';

-- Remove any scheduled jobs created by the extension
DELETE FROM civicrm_job WHERE name = 'Chart Dashboard Cache Warmup';
DELETE FROM civicrm_job WHERE api_entity = 'ChartData' AND api_action = 'WarmupCache';

-- Clean up any remaining settings (these are typically handled by the upgrader)
-- This is a safety net in case the PHP cleanup fails

-- Note: Settings are typically stored as serialized data in civicrm_setting table
-- or in the newer format as individual rows. The actual cleanup is better handled
-- in PHP using the Settings API, but we include this as a fallback.

DELETE FROM civicrm_setting WHERE name LIKE 'chartdashboard_%';

-- Remove any custom permissions (if they were stored in database)
-- Note: Most CiviCRM permissions are handled in code, not database
-- This is included for completeness but may not be necessary

-- Clean up any remaining cache entries in core cache tables
DELETE FROM civicrm_cache WHERE group_name = 'chartdashboard';

-- Remove any log entries specific to this extension (optional cleanup)
-- DELETE FROM civicrm_log WHERE entity_table LIKE '%chartdashboard%';

-- Remove any custom menu entries (typically handled by hook_civicrm_navigationMenu)
-- DELETE FROM civicrm_navigation WHERE url LIKE '%chart-dashboard%';

-- Clean up any extension-specific user preferences
-- DELETE FROM civicrm_user_preference WHERE name LIKE 'chartdashboard_%';

-- Clean up any files or directories (this needs to be done at filesystem level)
-- File cleanup is typically handled in the PHP upgrader

-- Final cleanup: Remove any orphaned references
-- This ensures database integrity after extension removal

-- Check for any foreign key constraints and remove safely
-- (Our tables use ON DELETE CASCADE, so this should be automatic)

COMMIT;

-- Note: After running this script, you may want to:
-- 1. Clear CiviCRM cache via UI or drush
-- 2. Rebuild navigation menu
-- 3. Clear browser cache
-- 4. Check for any remaining files in the extensions directory
