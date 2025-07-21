<?php

use CRM_Chartdashboard_ExtensionUtil as E;

/**
 * Utility helper class for Chart Dashboard extension
 */
class CRM_Chartdashboard_Utils_Helper {

  /**
   * Check if user has permission to view dashboard
   */
  public static function checkDashboardAccess() {
    return CRM_Core_Permission::check('view chart dashboard');
  }

  /**
   * Check if user has permission to configure dashboard
   */
  public static function checkConfigAccess() {
    return CRM_Core_Permission::check(['configure chart dashboard', 'administer CiviCRM']);
  }

  /**
   * Get extension settings
   */
  public static function getSettings() {
    try {
      return Civi::settings()->get('chartdashboard_settings') ?: [];
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Chart Dashboard: Failed to load settings - ' . $e->getMessage());
    }

    return self::getDefaultSettings();
  }

  /**
   * Get default settings
   */
  public static function getDefaultSettings() {
    return [
      'enable_auto_refresh' => TRUE,
      'refresh_interval' => 300,
      'enable_export' => TRUE,
      'default_time_range' => '7days',
      'api_timeout' => 30,
      'max_data_points' => 100,
      'enable_caching' => TRUE,
      'cache_duration' => 15,
      'dashboard_permissions' => ['view chart dashboard'],
      'enable_alerts' => FALSE,
      'color_scheme' => 'default',
      'primary_color' => '#667eea',
      'secondary_color' => '#764ba2',
      'accent_color' => '#f5576c',
    ];
  }

  /**
   * Save extension settings
   */
  public static function saveSettings($settings) {
    try {
      Civi::settings()->set('chartdashboard_settings', $settings);
      return TRUE;
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Chart Dashboard: Failed to save settings - ' . $e->getMessage());
      return FALSE;
    }
  }

  /**
   * Format currency value
   */
  public static function formatCurrency($amount, $currency = NULL) {
    if ($currency === NULL) {
      $currency = CRM_Core_Config::singleton()->defaultCurrency;
    }

    return CRM_Utils_Money::format($amount, $currency);
  }

  /**
   * Format number with appropriate separators
   */
  public static function formatNumber($number, $decimals = 0) {
    $config = CRM_Core_Config::singleton();
    return number_format($number, $decimals, $config->monetaryDecimalPoint, $config->monetaryThousandSeparator);
  }

  /**
   * Format percentage
   */
  public static function formatPercentage($value, $decimals = 1) {
    return number_format($value, $decimals) . '%';
  }

  /**
   * Get time range options
   */
  public static function getTimeRangeOptions() {
    return [
      '24hr' => E::ts('Last 24 Hours'),
      '2days' => E::ts('Last 2 Days'),
      '7days' => E::ts('Last 7 Days'),
      '1month' => E::ts('Last Month'),
      '3months' => E::ts('Last 3 Months'),
      '6months' => E::ts('Last 6 Months'),
      '1year' => E::ts('Last Year'),
    ];
  }

  /**
   * Get chart type options
   */
  public static function getChartTypeOptions() {
    return [
      'line' => E::ts('Line Chart'),
      'bar' => E::ts('Bar Chart'),
      'stacked_bar' => E::ts('Stacked Bar Chart'),
      'grouped_bar' => E::ts('Grouped Bar Chart'),
      'horizontal_bar' => E::ts('Horizontal Bar Chart'),
      'pie' => E::ts('Pie Chart'),
      'doughnut' => E::ts('Doughnut Chart'),
      'area' => E::ts('Area Chart'),
      'progress' => E::ts('Progress Chart'),
      'funnel' => E::ts('Funnel Chart'),
    ];
  }

  /**
   * Get color scheme definitions
   */
  public static function getColorSchemes() {
    return [
      'default' => [
        'name' => E::ts('Default'),
        'primary' => '#667eea',
        'secondary' => '#764ba2',
        'accent' => '#f5576c',
        'success' => '#28a745',
        'warning' => '#ffc107',
        'danger' => '#dc3545',
        'info' => '#17a2b8',
      ],
      'blue' => [
        'name' => E::ts('Blue Theme'),
        'primary' => '#4facfe',
        'secondary' => '#00f2fe',
        'accent' => '#2196f3',
        'success' => '#4caf50',
        'warning' => '#ff9800',
        'danger' => '#f44336',
        'info' => '#00bcd4',
      ],
      'green' => [
        'name' => E::ts('Green Theme'),
        'primary' => '#43e97b',
        'secondary' => '#38f9d7',
        'accent' => '#4caf50',
        'success' => '#8bc34a',
        'warning' => '#ffeb3b',
        'danger' => '#e91e63',
        'info' => '#009688',
      ],
      'purple' => [
        'name' => E::ts('Purple Theme'),
        'primary' => '#667eea',
        'secondary' => '#764ba2',
        'accent' => '#9c27b0',
        'success' => '#4caf50',
        'warning' => '#ff9800',
        'danger' => '#f44336',
        'info' => '#673ab7',
      ],
      'orange' => [
        'name' => E::ts('Orange Theme'),
        'primary' => '#ff9a9e',
        'secondary' => '#fecfef',
        'accent' => '#ff5722',
        'success' => '#4caf50',
        'warning' => '#ffc107',
        'danger' => '#dc3545',
        'info' => '#ff9800',
      ],
    ];
  }

  /**
   * Generate cache key
   */
  public static function generateCacheKey($chartType, $timeRange, $additionalParams = []) {
    $keyParts = [
      'chartdashboard',
      $chartType,
      $timeRange,
    ];

    if (!empty($additionalParams)) {
      ksort($additionalParams);
      $keyParts[] = md5(serialize($additionalParams));
    }

    return implode('_', $keyParts);
  }

  /**
   * Validate time range
   */
  public static function validateTimeRange($timeRange) {
    $validRanges = array_keys(self::getTimeRangeOptions());
    return in_array($timeRange, $validRanges);
  }

  /**
   * Validate chart type
   */
  public static function validateChartType($chartType) {
    $validTypes = array_keys(self::getChartTypeOptions());
    return in_array($chartType, $validTypes);
  }

  /**
   * Get date range for SQL queries
   */
  public static function getDateRange($timeRange) {
    $endDate = date('Y-m-d 23:59:59');

    switch ($timeRange) {
      case '24hr':
        $startDate = date('Y-m-d 00:00:00', strtotime('-1 day'));
        break;
      case '2days':
        $startDate = date('Y-m-d 00:00:00', strtotime('-2 days'));
        break;
      case '7days':
        $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
        break;
      case '1month':
        $startDate = date('Y-m-d 00:00:00', strtotime('-1 month'));
        break;
      case '3months':
        $startDate = date('Y-m-d 00:00:00', strtotime('-3 months'));
        break;
      case '6months':
        $startDate = date('Y-m-d 00:00:00', strtotime('-6 months'));
        break;
      case '1year':
        $startDate = date('Y-m-d 00:00:00', strtotime('-1 year'));
        break;
      default:
        $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
    }

    return [
      'start_date' => $startDate,
      'end_date' => $endDate,
    ];
  }

  /**
   * Get GROUP BY clause for time range
   */
  public static function getGroupByClause($timeRange, $dateField = 'receive_date') {
    switch ($timeRange) {
      case '24hr':
      case '2days':
        return "DATE_FORMAT({$dateField}, '%Y-%m-%d %H:00:00')";
      case '7days':
      case '1month':
        return "DATE({$dateField})";
      case '3months':
      case '6months':
        return "DATE_FORMAT({$dateField}, '%Y-%u')"; // Year-Week
      case '1year':
        return "DATE_FORMAT({$dateField}, '%Y-%m')"; // Year-Month
      default:
        return "DATE({$dateField})";
    }
  }

  /**
   * Send email alert
   */
  public static function sendAlert($type, $data, $recipients = NULL) {
    $settings = self::getSettings();

    if (!$settings['enable_alerts']) {
      return FALSE;
    }

    if ($recipients === NULL) {
      $recipients = [$settings['alert_email'] ?? ''];
    }

    $recipients = array_filter($recipients); // Remove empty values

    if (empty($recipients)) {
      return FALSE;
    }

    $subject = self::getAlertSubject($type, $data);
    $message = self::getAlertMessage($type, $data);

    try {
      foreach ($recipients as $email) {
        civicrm_api3('Email', 'send', [
          'to' => $email,
          'subject' => $subject,
          'html' => $message,
          'from' => CRM_Core_BAO_Domain::getNameAndEmail()[1],
        ]);

        // Log the alert
        self::logAlert($type, $data, $email, 'sent');
      }

      return TRUE;
    }
    catch (Exception $e) {
      // Log the error
      self::logAlert($type, $data, implode(',', $recipients), 'failed', $e->getMessage());
      return FALSE;
    }
  }

  /**
   * Get alert subject
   */
  private static function getAlertSubject($type, $data) {
    switch ($type) {
      case 'low_donations':
        return E::ts('Low Donation Alert - %1', [1 => date('Y-m-d')]);
      case 'goal_achieved':
        return E::ts('Campaign Goal Achievement - %1', [1 => $data['campaign_name'] ?? 'Campaign']);
      case 'donor_retention':
        return E::ts('Donor Retention Alert - %1', [1 => date('Y-m-d')]);
      default:
        return E::ts('Chart Dashboard Alert - %1', [1 => date('Y-m-d')]);
    }
  }

  /**
   * Get alert message
   */
  private static function getAlertMessage($type, $data) {
    $baseURL = CRM_Utils_System::url('civicrm/chart-dashboard', 'reset=1', TRUE);

    switch ($type) {
      case 'low_donations':
        return "
          <h2>Low Donation Alert</h2>
          <p>Daily donations have fallen below the configured threshold.</p>
          <ul>
            <li>Current Amount: " . self::formatCurrency($data['current_amount'] ?? 0) . "</li>
            <li>Threshold: " . self::formatCurrency($data['threshold'] ?? 0) . "</li>
            <li>Date: " . ($data['date'] ?? date('Y-m-d')) . "</li>
          </ul>
          <p><a href='{$baseURL}'>View Dashboard</a></p>
        ";

      case 'goal_achieved':
        return "
          <h2>Campaign Goal Achievement</h2>
          <p>A campaign has reached its goal threshold!</p>
          <ul>
            <li>Campaign: " . ($data['campaign_name'] ?? 'Unknown') . "</li>
            <li>Progress: " . self::formatPercentage($data['progress'] ?? 0) . "</li>
            <li>Amount Raised: " . self::formatCurrency($data['raised'] ?? 0) . "</li>
            <li>Goal: " . self::formatCurrency($data['goal'] ?? 0) . "</li>
          </ul>
          <p><a href='{$baseURL}'>View Dashboard</a></p>
        ";

      default:
        return "
          <h2>Chart Dashboard Alert</h2>
          <p>An alert has been triggered from the Chart Dashboard.</p>
          <p>Alert Type: {$type}</p>
          <p><a href='{$baseURL}'>View Dashboard</a></p>
        ";
    }
  }

  /**
   * Log alert to database
   */
  private static function logAlert($type, $data, $sentTo, $status, $errorMessage = NULL) {
    try {
      CRM_Core_DAO::executeQuery("
        INSERT INTO civicrm_chartdashboard_alerts
        (alert_type, alert_data, sent_to, status, error_message)
        VALUES (%1, %2, %3, %4, %5)
      ", [
        1 => [$type, 'String'],
        2 => [json_encode($data), 'String'],
        3 => [$sentTo, 'String'],
        4 => [$status, 'String'],
        5 => [$errorMessage, 'String'],
      ]);
    }
    catch (Exception $e) {
      // Fail silently for logging errors
      CRM_Core_Error::debug_log_message('Chart Dashboard: Failed to log alert - ' . $e->getMessage());
    }
  }

  /**
   * Clean up old data
   */
  public static function cleanup() {
    $cleanupResults = [
      'cache_cleaned' => 0,
      'alerts_cleaned' => 0,
      'errors' => [],
    ];

    try {
      // Clean expired cache
      $result = CRM_Core_DAO::executeQuery("
        DELETE FROM civicrm_chartdashboard_cache WHERE expires_at < NOW()
      ");
      $cleanupResults['cache_cleaned'] = $result->affectedRows();

      // Keep only last 1000 alert records
      CRM_Core_DAO::executeQuery("
        DELETE FROM civicrm_chartdashboard_alerts
        WHERE id NOT IN (
          SELECT id FROM (
            SELECT id FROM civicrm_chartdashboard_alerts
            ORDER BY sent_date DESC
            LIMIT 1000
          ) as t
        )
      ");
      $cleanupResults['alerts_cleaned'] = CRM_Core_DAO::$_DB->affectedRows();

    }
    catch (Exception $e) {
      $cleanupResults['errors'][] = $e->getMessage();
    }

    return $cleanupResults;
  }

  /**
   * Get dashboard statistics
   */
  public static function getDashboardStats() {
    $stats = [
      'total_contributions' => 0,
      'total_amount' => 0,
      'active_campaigns' => 0,
      'unique_donors' => 0,
      'cache_entries' => 0,
      'last_updated' => date('Y-m-d H:i:s'),
    ];

    try {
      // Get contribution stats
      $dao = CRM_Core_DAO::executeQuery("
        SELECT
          COUNT(*) as total_contributions,
          SUM(total_amount) as total_amount,
          COUNT(DISTINCT contact_id) as unique_donors
        FROM civicrm_contribution
        WHERE contribution_status_id = 1
        AND receive_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
      ");

      if ($dao->fetch()) {
        $stats['total_contributions'] = (int)$dao->total_contributions;
        $stats['total_amount'] = (float)$dao->total_amount;
        $stats['unique_donors'] = (int)$dao->unique_donors;
      }

      // Get active campaigns
      $dao = CRM_Core_DAO::executeQuery("
        SELECT COUNT(*) as active_campaigns
        FROM civicrm_campaign
        WHERE is_active = 1
        AND (end_date IS NULL OR end_date >= CURDATE())
      ");

      if ($dao->fetch()) {
        $stats['active_campaigns'] = (int)$dao->active_campaigns;
      }

      // Get cache stats
      $dao = CRM_Core_DAO::executeQuery("
        SELECT COUNT(*) as cache_entries
        FROM civicrm_chartdashboard_cache
        WHERE expires_at > NOW()
      ");

      if ($dao->fetch()) {
        $stats['cache_entries'] = (int)$dao->cache_entries;
      }

    }
    catch (Exception $e) {
      // Return default stats on error
    }

    return $stats;
  }

  /**
   * Validate extension requirements
   */
  public static function validateRequirements() {
    $requirements = [
      'php_version' => version_compare(PHP_VERSION, '7.4.0', '>='),
      'json_extension' => extension_loaded('json'),
      'pdo_extension' => extension_loaded('pdo'),
      'mbstring_extension' => extension_loaded('mbstring'),
      'civicrm_version' => version_compare(CRM_Utils_System::version(), '5.39.0', '>='),
      'database_tables' => TRUE,
    ];

    // Check if database tables exist
    try {
      CRM_Core_DAO::executeQuery("SELECT 1 FROM civicrm_chartdashboard_cache LIMIT 1");
    }
    catch (Exception $e) {
      $requirements['database_tables'] = FALSE;
    }

    return $requirements;
  }
}
