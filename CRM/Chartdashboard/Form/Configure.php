<?php

use CRM_Chartdashboard_ExtensionUtil as E;

/**
 * Configuration form for Chart Dashboard extension
 */
class CRM_Chartdashboard_Form_Configure extends CRM_Core_Form {

  /**
   * Pre-process form
   */
  public function preProcess() {
    parent::preProcess();

    // Check permissions
    if (!CRM_Core_Permission::check(['configure chart dashboard', 'administer CiviCRM'])) {
      CRM_Core_Error::statusBounce('You do not have permission to configure the chart dashboard.');
    }

    CRM_Utils_System::setTitle(E::ts('Configure Chart Dashboard'));
  }

  /**
   * Build the form object
   */
  public function buildForm() {

    // Global Dashboard Settings
    $this->add('checkbox', 'enable_auto_refresh', E::ts('Enable Auto-Refresh'));

    $this->add('select', 'refresh_interval', E::ts('Refresh Interval'), [
      '60' => E::ts('1 minute'),
      '300' => E::ts('5 minutes'),
      '600' => E::ts('10 minutes'),
      '1800' => E::ts('30 minutes'),
      '3600' => E::ts('1 hour'),
    ], FALSE, ['class' => 'form-control']);

    $this->add('checkbox', 'enable_export', E::ts('Enable Data Export'));

    $this->add('select', 'default_time_range', E::ts('Default Time Range'), [
      '24hr' => E::ts('Last 24 Hours'),
      '2days' => E::ts('Last 2 Days'),
      '7days' => E::ts('Last 7 Days'),
      '1month' => E::ts('Last Month'),
      '3months' => E::ts('Last 3 Months'),
      '6months' => E::ts('Last 6 Months'),
      '1year' => E::ts('Last Year'),
    ], FALSE, ['class' => 'form-control']);

    // Chart-specific settings
    $availableCharts = $this->getAvailableCharts();
    $this->assign('availableCharts', $availableCharts);
    foreach ($availableCharts as $chartId => $chartInfo) {
      $this->add('checkbox', "enable_chart_{$chartId}", $chartInfo['title']);

      if ($chartInfo['supports_time_range']) {
        $this->add('select', "default_time_range_{$chartId}", E::ts('Default Time Range'), [
          '' => E::ts('Use Global Default'),
          '24hr' => E::ts('Last 24 Hours'),
          '2days' => E::ts('Last 2 Days'),
          '7days' => E::ts('Last 7 Days'),
          '1month' => E::ts('Last Month'),
          '3months' => E::ts('Last 3 Months'),
          '6months' => E::ts('Last 6 Months'),
          '1year' => E::ts('Last Year'),
        ], FALSE, ['class' => 'form-control']);
      }

      $this->add('select', "default_chart_type_{$chartId}", E::ts('Default Chart Type'),
        array_combine($chartInfo['chart_types'], $chartInfo['chart_types']),
        FALSE, ['class' => 'form-control']
      );
    }

    // Performance Settings
    $this->add('text', 'api_timeout', E::ts('API Timeout (seconds)'), ['class' => 'form-control'], FALSE);
    $this->add('text', 'max_data_points', E::ts('Maximum Data Points per Chart'), ['class' => 'form-control'], FALSE);
    $this->add('checkbox', 'enable_caching', E::ts('Enable Data Caching'));
    $this->add('text', 'cache_duration', E::ts('Cache Duration (minutes)'), ['class' => 'form-control'], FALSE);

    // Access Control
    $this->add('select', 'dashboard_permissions', E::ts('Dashboard Access'), [
      'view chart dashboard' => E::ts('Chart Dashboard Users Only'),
      'access CiviContribute' => E::ts('All Contribution Users'),
      'administer CiviCRM' => E::ts('Administrators Only'),
    ], TRUE, ['class' => 'crm-select2 huge form-control', 'multiple' => 'multiple']);

    // Email Alerts
    $this->add('checkbox', 'enable_alerts', E::ts('Enable Email Alerts'));
    $this->add('text', 'alert_email', E::ts('Alert Email Address'), ['class' => 'form-control'], FALSE);
    $this->add('text', 'low_donation_threshold', E::ts('Low Donation Alert Threshold'), ['class' => 'form-control'], FALSE);
    $this->add('text', 'goal_achievement_threshold', E::ts('Goal Achievement Alert Threshold (%)'), ['class' => 'form-control'], FALSE);

    // Color Scheme
    $this->add('select', 'color_scheme', E::ts('Color Scheme'), [
      'default' => E::ts('Default'),
      'blue' => E::ts('Blue Theme'),
      'green' => E::ts('Green Theme'),
      'purple' => E::ts('Purple Theme'),
      'orange' => E::ts('Orange Theme'),
      'custom' => E::ts('Custom Colors'),
    ], FALSE, ['class' => 'form-control']);

    // Custom Colors (shown when custom scheme selected)
    $this->add('color', 'primary_color', E::ts('Primary Color'), ['class' => 'form-control'], FALSE);
    $this->add('color', 'secondary_color', E::ts('Secondary Color'), ['class' => 'form-control'], FALSE);
    $this->add('color', 'accent_color', E::ts('Accent Color'), ['class' => 'form-control'], FALSE);

    // Add buttons
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Save Settings'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ],
      [
        'type' => 'submit',
        'name' => E::ts('Reset to Defaults'),
        'subName' => 'reset',
      ],
    ]);

    parent::buildForm();
  }

  /**
   * Set default values
   */
  public function setDefaultValues() {
    $defaults = [];

    // Get current settings
    try {
      $defaults = Civi::settings()->get('chartdashboard_settings') ?: [];
    }
    catch (Exception $e) {
      // Use defaults if settings can't be loaded
    }

    // Set default values if not already set
    $defaults = array_merge([
      'enable_auto_refresh' => 1,
      'refresh_interval' => '300',
      'enable_export' => 1,
      'default_time_range' => '7days',
      'api_timeout' => '30',
      'max_data_points' => '100',
      'enable_caching' => 1,
      'cache_duration' => '15',
      'dashboard_permissions' => ['view chart dashboard'],
      'enable_alerts' => 0,
      'color_scheme' => 'default',
      'primary_color' => '#667eea',
      'secondary_color' => '#764ba2',
      'accent_color' => '#f5576c',
    ], $defaults);

    // Enable all charts by default
    $availableCharts = $this->getAvailableCharts();
    foreach ($availableCharts as $chartId => $chartInfo) {
      if (!isset($defaults["enable_chart_{$chartId}"])) {
        $defaults["enable_chart_{$chartId}"] = 1;
      }
      if (!isset($defaults["default_chart_type_{$chartId}"])) {
        $defaults["default_chart_type_{$chartId}"] = $chartInfo['chart_types'][0];
      }
    }

    return $defaults;
  }

  /**
   * Process the form submission
   */
  public function postProcess() {
    $values = $this->exportValues();

    // Handle reset action
    if ($this->_submitValues['_qf_Configure_submit_reset']) {
      $this->resetToDefaults();
      CRM_Core_Session::setStatus(E::ts('Settings have been reset to defaults.'), E::ts('Settings Reset'), 'success');
      return;
    }

    // Process and save settings
    $settings = [];

    // Global settings
    $settings['enable_auto_refresh'] = !empty($values['enable_auto_refresh']);
    $settings['refresh_interval'] = (int)$values['refresh_interval'];
    $settings['enable_export'] = !empty($values['enable_export']);
    $settings['default_time_range'] = $values['default_time_range'];

    // Performance settings
    $settings['api_timeout'] = (int)$values['api_timeout'];
    $settings['max_data_points'] = (int)$values['max_data_points'];
    $settings['enable_caching'] = !empty($values['enable_caching']);
    $settings['cache_duration'] = (int)$values['cache_duration'];

    // Access control
    $settings['dashboard_permissions'] = $values['dashboard_permissions'];

    // Email alerts
    $settings['enable_alerts'] = !empty($values['enable_alerts']);
    $settings['alert_email'] = $values['alert_email'];
    $settings['low_donation_threshold'] = (float)$values['low_donation_threshold'];
    $settings['goal_achievement_threshold'] = (float)$values['goal_achievement_threshold'];

    // Color scheme
    $settings['color_scheme'] = $values['color_scheme'];
    $settings['primary_color'] = $values['primary_color'];
    $settings['secondary_color'] = $values['secondary_color'];
    $settings['accent_color'] = $values['accent_color'];

    // Chart-specific settings
    $availableCharts = $this->getAvailableCharts();
    foreach ($availableCharts as $chartId => $chartInfo) {
      $settings["enable_chart_{$chartId}"] = !empty($values["enable_chart_{$chartId}"]);
      $settings["default_chart_type_{$chartId}"] = $values["default_chart_type_{$chartId}"];

      if ($chartInfo['supports_time_range']) {
        $settings["default_time_range_{$chartId}"] = $values["default_time_range_{$chartId}"];
      }
    }

    // Save settings
    try {
      Civi::settings()->set('chartdashboard_settings', $settings);

      CRM_Core_Session::setStatus(E::ts('Chart Dashboard settings have been saved.'), E::ts('Settings Saved'), 'success');

      // Clear any cached data if caching was disabled
      if (!$settings['enable_caching']) {
        $this->clearCache();
      }

    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus(E::ts('Error saving settings: %1', [1 => $e->getMessage()]), E::ts('Save Error'), 'error');
    }

    // Redirect back to dashboard
    $redirectUrl = CRM_Utils_System::url('civicrm/chart-dashboard/configure', 'reset=1');
    CRM_Utils_System::redirect($redirectUrl);
  }

  /**
   * Get available charts configuration
   */
  private function getAvailableCharts() {
    try {
      $result = civicrm_api3('ChartData', 'GetAvailableCharts');
      return $result['values'];
    }
    catch (Exception $e) {
      return [];
    }
  }

  /**
   * Reset settings to defaults
   */
  private function resetToDefaults() {
    try {
      Civi::settings()->set('chartdashboard_settings', []);

      // Clear all user dashboard configurations
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

      $this->clearCache();

    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus(E::ts('Error resetting settings: %1', [1 => $e->getMessage()]), E::ts('Reset Error'), 'error');
    }
  }

  /**
   * Clear cached data
   */
  private function clearCache() {
    // Clear CiviCRM cache
    CRM_Core_BAO_Cache::deleteGroup('chartdashboard');

    // Clear any custom cache if implemented
    $cacheKey = 'chartdashboard_*';
    CRM_Utils_Cache::singleton()->deleteGroup($cacheKey);
  }

  /**
   * Add validation rules
   */
  public function addRules() {
    $this->addFormRule(['CRM_Chartdashboard_Form_Configure', 'formRule']);
  }

  /**
   * Form validation rules
   */
  public static function formRule($values) {
    $errors = [];

    // Validate numeric fields
    if (!empty($values['api_timeout']) && (!is_numeric($values['api_timeout']) || $values['api_timeout'] < 5)) {
      $errors['api_timeout'] = E::ts('API timeout must be a number greater than 5 seconds.');
    }

    if (!empty($values['max_data_points']) && (!is_numeric($values['max_data_points']) || $values['max_data_points'] < 10)) {
      $errors['max_data_points'] = E::ts('Maximum data points must be a number greater than 10.');
    }

    if (!empty($values['cache_duration']) && (!is_numeric($values['cache_duration']) || $values['cache_duration'] < 1)) {
      $errors['cache_duration'] = E::ts('Cache duration must be a number greater than 1 minute.');
    }

    // Validate email if alerts are enabled
    if (!empty($values['enable_alerts']) && !empty($values['alert_email'])) {
      if (!filter_var($values['alert_email'], FILTER_VALIDATE_EMAIL)) {
        $errors['alert_email'] = E::ts('Please enter a valid email address.');
      }
    }

    // Validate thresholds
    if (!empty($values['low_donation_threshold']) && (!is_numeric($values['low_donation_threshold']) || $values['low_donation_threshold'] < 0)) {
      $errors['low_donation_threshold'] = E::ts('Low donation threshold must be a positive number.');
    }

    if (!empty($values['goal_achievement_threshold']) && (!is_numeric($values['goal_achievement_threshold']) || $values['goal_achievement_threshold'] < 0 || $values['goal_achievement_threshold'] > 100)) {
      $errors['goal_achievement_threshold'] = E::ts('Goal achievement threshold must be a percentage between 0 and 100.');
    }

    // Validate color codes
    if ($values['color_scheme'] === 'custom') {
      $colorFields = ['primary_color', 'secondary_color', 'accent_color'];
      foreach ($colorFields as $field) {
        if (!empty($values[$field]) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $values[$field])) {
          $errors[$field] = E::ts('Please enter a valid hex color code (e.g., #667eea).');
        }
      }
    }

    return $errors;
  }
}
