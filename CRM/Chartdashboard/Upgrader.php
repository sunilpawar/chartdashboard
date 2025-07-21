<?php

use CRM_Chartdashboard_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Chartdashboard_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Installation tasks
   */
  public function install() {
    //$this->executeSqlFile('sql/install.sql');
    $this->createDefaultSettings();
    $this->createPermissions();
  }

  /**
   * Uninstallation tasks
   */
  public function uninstall() {
    $this->executeSqlFile('sql/uninstall.sql');
    $this->cleanupSettings();
    $this->clearCache();
  }

  /**
   * Enable tasks
   */
  public function enable() {
    $this->createDefaultSettings();
  }

  /**
   * Disable tasks
   */
  public function disable() {
    $this->clearCache();
  }

  /**
   * Upgrade to version 1.1
   */
  public function upgrade_1100() {
    $this->ctx->log->info('Applying update 1100 - Enhanced caching system');

    // Add new cache tables if needed
    CRM_Core_DAO::executeQuery("
      CREATE TABLE IF NOT EXISTS civicrm_chartdashboard_cache (
        id int unsigned NOT NULL AUTO_INCREMENT,
        cache_key varchar(255) NOT NULL,
        cache_data longtext,
        expires_at datetime,
        created_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY cache_key (cache_key),
        KEY expires_at (expires_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    return TRUE;
  }

  /**
   * Upgrade to version 1.2
   */
  public function test_upgrade_1200() {
    $this->ctx->log->info('Applying update 1200 - Email alerts system');

    // Create alerts log table
    CRM_Core_DAO::executeQuery("
      CREATE TABLE IF NOT EXISTS civicrm_chartdashboard_alerts (
        id int unsigned NOT NULL AUTO_INCREMENT,
        alert_type varchar(50) NOT NULL,
        alert_data text,
        sent_to varchar(255),
        sent_date datetime DEFAULT CURRENT_TIMESTAMP,
        status varchar(20) DEFAULT 'sent',
        PRIMARY KEY (id),
        KEY alert_type (alert_type),
        KEY sent_date (sent_date)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    return TRUE;
  }

  /**
   * Upgrade to version 1.3
   */
  public function test_upgrade_1300() {
    $this->ctx->log->info('Applying update 1300 - User dashboard configurations');

    // Migration handled by settings API
    // Just ensure indexes exist
    //$this->createIndexes();

    return TRUE;
  }

  /**
   * Create default settings
   */
  private function createDefaultSettings() {
    $defaultSettings = [
      'enable_auto_refresh' => 1,
      'refresh_interval' => 300,
      'enable_export' => 1,
      'default_time_range' => '7days',
      'api_timeout' => 30,
      'max_data_points' => 100,
      'enable_caching' => 1,
      'cache_duration' => 15,
      'dashboard_permissions' => ['view chart dashboard'],
      'enable_alerts' => 0,
      'color_scheme' => 'default',
      'primary_color' => '#667eea',
      'secondary_color' => '#764ba2',
      'accent_color' => '#f5576c',
    ];

    // Enable all charts by default
    $availableCharts = [
      'realtime_donations',
      'recurring_vs_onetime',
      'lapsed_donors',
      'donor_retention',
      'avg_gift_trend',
      'campaign_progress',
      'pledged_vs_actual',
      'membership_revenue',
    ];

    foreach ($availableCharts as $chartId) {
      $defaultSettings["enable_chart_{$chartId}"] = 1;
      $defaultSettings["default_chart_type_{$chartId}"] = 'line';
    }

    try {
      Civi::settings()->set('chartdashboard_settings', $defaultSettings);
    }
    catch (Exception $e) {
      Civi::log()->warning('Failed to create default chart dashboard settings: ' . $e->getMessage());
    }
  }

  /**
   * Create necessary permissions
   */
  private function createPermissions() {
    // Permissions are handled in the main extension file
    // This method is for any additional permission setup if needed
  }

  /**
   * Create database indexes for performance
   */
  private function createIndexes() {
    // Index on contribution table for dashboard queries
    $indexes = [
      "CREATE INDEX IF NOT EXISTS idx_contribution_dashboard
       ON civicrm_contribution (contribution_status_id, receive_date, contact_id, campaign_id)",

      "CREATE INDEX IF NOT EXISTS idx_contribution_recur_dashboard
       ON civicrm_contribution_recur (is_active, start_date, contact_id)",

      "CREATE INDEX IF NOT EXISTS idx_campaign_dashboard
       ON civicrm_campaign (is_active, start_date, end_date)",

      "CREATE INDEX IF NOT EXISTS idx_membership_dashboard
       ON civicrm_membership (membership_type_id, start_date, status_id)",
    ];

    foreach ($indexes as $sql) {
      try {
        CRM_Core_DAO::executeQuery($sql);
      }
      catch (Exception $e) {
        // Index might already exist, continue
        Civi::log()->debug('Index creation skipped: ' . $e->getMessage());
      }
    }
  }

  /**
   * Clean up settings on uninstall
   */
  private function cleanupSettings() {
    try {
      // Remove main settings
      Civi::settings()->set('chartdashboard_settings', []);

      // Remove all user dashboard configurations
      $result = civicrm_api3('Setting', 'get', [
        'name' => ['chartdashboard_config_%'],
      ]);

      foreach ($result['values'] as $setting) {
        foreach ($setting as $key => $value) {
          if (strpos($key, 'chartdashboard_config_') === 0) {
            civicrm_api3('Setting', 'create', [$key => NULL]);
          }
        }
      }
    }
    catch (Exception $e) {
      Civi::log()->warning('Failed to cleanup chart dashboard settings: ' . $e->getMessage());
    }
  }

  /**
   * Clear all caches
   */
  private function clearCache() {
    // Clear CiviCRM cache
    CRM_Core_BAO_Cache::deleteGroup('chartdashboard');

    // Clear custom cache table if it exists
    try {
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_chartdashboard_cache");
    }
    catch (Exception $e) {
      // Table might not exist
    }

    // Clear any file-based cache if implemented
    $cacheDir = Civi::paths()->getPath('[civicrm.files]/chartdashboard_cache/');
    if (is_dir($cacheDir)) {
      $this->recursiveRemoveDirectory($cacheDir);
    }
  }

  /**
   * Recursively remove directory
   */
  private function recursiveRemoveDirectory($dir) {
    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (is_dir($dir . "/" . $object)) {
            $this->recursiveRemoveDirectory($dir . "/" . $object);
          }
          else {
            unlink($dir . "/" . $object);
          }
        }
      }
      rmdir($dir);
    }
  }

  /**
   * Run a CustomData file.
   *
   * @param string $relativePath
   *   The CustomData XML file path (relative to this extension's dir).
   * @param string $op
   *   The operation being performed ('install', 'uninstall', 'enable', 'disable').
   */
  public function executeCustomDataFile($relativePath, $op = 'install') {
    $xml_file = $this->extensionDir . '/' . $relativePath;
    if (file_exists($xml_file)) {
      require_once 'CRM/Utils/Migrate/Import.php';
      $import = new CRM_Utils_Migrate_Import();
      $import->run($xml_file);
    }
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks that require entities or
   * functionality that may not be available during the installation process.
   */
  public function postInstall() {
    // Schedule initial data warming if caching is enabled
    $this->scheduleDataWarmup();

    // Send installation notification if configured
    //$this->sendInstallationNotification();
  }

  /**
   * Schedule initial data warming for better performance
   */
  private function scheduleDataWarmup() {
    try {
      // Create a scheduled job to warm up the cache
      $job = civicrm_api3('Job', 'create', [
        'name' => 'Chart Dashboard Cache Warmup',
        'description' => 'Warms up the chart dashboard cache with initial data',
        'api_entity' => 'ChartData',
        'api_action' => 'WarmupCache',
        'run_frequency' => 'Hourly',
        'is_active' => 1,
      ]);

      Civi::log()->info('Chart Dashboard cache warmup job created with ID: ' . $job['id']);
    }
    catch (Exception $e) {
      Civi::log()->warning('Failed to create cache warmup job: ' . $e->getMessage());
    }
  }

  /**
   * Send installation notification
   */
  private function sendInstallationNotification() {
    try {
      // Get administrator email
      $config = CRM_Core_Config::singleton();
      $fromEmail = $config->defaultContactEmail;

      if ($fromEmail) {
        $subject = 'Chart Dashboard Extension Installed';
        $message = "
          <h2>Chart Dashboard Extension Successfully Installed</h2>
          <p>The Chart Dashboard extension has been successfully installed and is ready to use.</p>

          <h3>Next Steps:</h3>
          <ul>
            <li>Visit the <a href='" . CRM_Utils_System::url('civicrm/chart-dashboard', 'reset=1', TRUE) . "'>Chart Dashboard</a> to view your donation analytics</li>
            <li>Configure dashboard settings at <a href='" . CRM_Utils_System::url('civicrm/chart-dashboard/configure', 'reset=1', TRUE) . "'>Dashboard Configuration</a></li>
            <li>Set up user permissions for 'view chart dashboard' and 'configure chart dashboard'</li>
          </ul>

          <h3>Available Charts:</h3>
          <ul>
            <li>Real-Time Donation Dashboard</li>
            <li>Recurring vs One-Time Contributions</li>
            <li>Lapsed Donor Value Analysis</li>
            <li>Donor Retention Funnel</li>
            <li>Average Gift Size Over Time</li>
            <li>Campaign-Specific Fundraising Progress</li>
            <li>Pledged vs Actual Income</li>
            <li>Membership Revenue Breakdown</li>
          </ul>

          <p>For support and documentation, please visit the extension documentation.</p>
        ";

        civicrm_api3('Email', 'send', [
          'to' => $fromEmail,
          'subject' => $subject,
          'html' => $message,
          'from' => $fromEmail,
        ]);
      }
    }
    catch (Exception $e) {
      // Fail silently for notification email
      Civi::log()->debug('Installation notification email failed: ' . $e->getMessage());
    }
  }

  /**
   * Handle version-specific post-upgrade tasks
   */
  public function onPostInstall() {
    $this->postInstall();
  }

  /**
   * Pre-uninstall cleanup
   */
  public function onPreUninstall() {
    // Remove scheduled jobs
    try {
      $jobs = civicrm_api3('Job', 'get', [
        'name' => 'Chart Dashboard Cache Warmup',
      ]);

      foreach ($jobs['values'] as $job) {
        civicrm_api3('Job', 'delete', ['id' => $job['id']]);
      }
    }
    catch (Exception $e) {
      // Continue with uninstall even if job deletion fails
    }
  }
}
