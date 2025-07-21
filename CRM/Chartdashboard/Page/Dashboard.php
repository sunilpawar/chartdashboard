<?php

use CRM_Chartdashboard_ExtensionUtil as E;

/**
 * Dashboard page for Chart Dashboard extension
 */
class CRM_Chartdashboard_Page_Dashboard extends CRM_Core_Page {

  public function run() {
    // Check permissions
    if (!CRM_Core_Permission::check('view chart dashboard')) {
      CRM_Core_Error::statusBounce('You do not have permission to access this page.');
    }

    // Set page title
    CRM_Utils_System::setTitle(E::ts('Chart Dashboard'));

    // Get available charts
    try {
      $availableCharts = civicrm_api3('ChartData', 'GetAvailableCharts');
      $this->assign('availableCharts', $availableCharts['values']);
    }
    catch (CiviCRM_API3_Exception $e) {
      $this->assign('availableCharts', []);
      CRM_Core_Session::setStatus(E::ts('Error loading available charts: %1', [1 => $e->getMessage()]), E::ts('Error'), 'error');
    }

    // Get user's dashboard configuration
    $userID = CRM_Core_Session::getLoggedInContactID();
    $dashboardConfig = $this->getUserDashboardConfig($userID);
    $settings = CRM_Chartdashboard_Utils_Helper::getSettings();

    $this->assign('dashboardConfig', $dashboardConfig);
    $this->assign('settings', $settings);

    // Time range options
    $timeRangeOptions = [
      '24hr' => E::ts('Last 24 Hours'),
      '2days' => E::ts('Last 2 Days'),
      '7days' => E::ts('Last 7 Days'),
      '1month' => E::ts('Last Month'),
      '3months' => E::ts('Last 3 Months'),
      '6months' => E::ts('Last 6 Months'),
      '1year' => E::ts('Last Year'),
    ];
    $this->assign('timeRangeOptions', $timeRangeOptions);

    // Chart type options
    $chartTypeOptions = [
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
    $this->assign('chartTypeOptions', $chartTypeOptions);

    // Add JavaScript variables
    $jsVars = [
      'apiURL' => CRM_Utils_System::url('civicrm/ajax/rest', 'entity=ChartData&action=Get&json=1'),
      'availableChartsURL' => CRM_Utils_System::url('civicrm/ajax/rest', 'entity=ChartData&action=GetAvailableCharts&json=1'),
      'exportURL' => CRM_Utils_System::url('civicrm/ajax/chart-dashboard/export'),
      'saveConfigURL' => CRM_Utils_System::url('civicrm/ajax/chart-dashboard/save-config'),
      'userID' => $userID,
      'settings' => CRM_Chartdashboard_Utils_Helper::getSettings(),
    ];
    CRM_Core_Resources::singleton()->addVars('chartDashboard', $jsVars);

    // Add required resources
    CRM_Core_Resources::singleton()
      ->addScriptFile('com.skvare.chartdashboard', 'js/dashboard.js')
      ->addStyleFile('com.skvare.chartdashboard', 'css/dashboard.css')
      ->addScriptUrl('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js')
      ->addScriptUrl('https://cdnjs.cloudflare.com/ajax/libs/date-fns/1.30.1/date_fns.min.js');

    parent::run();
  }

  /**
   * Get user's dashboard configuration
   */
  private function getUserDashboardConfig($userID) {
    $defaultConfig = [
      'layout' => 'grid',
      'charts' => [
        [
          'chart_id' => 'realtime_donations',
          'chart_type' => 'line',
          'time_range' => '7days',
          'position' => 1,
          'size' => 'large',
        ],
        [
          'chart_id' => 'recurring_vs_onetime',
          'chart_type' => 'stacked_bar',
          'time_range' => '1month',
          'position' => 2,
          'size' => 'medium',
        ],
        [
          'chart_id' => 'campaign_progress',
          'chart_type' => 'progress',
          'time_range' => '',
          'position' => 3,
          'size' => 'medium',
        ],
        [
          'chart_id' => 'donor_retention',
          'chart_type' => 'funnel',
          'time_range' => '',
          'position' => 4,
          'size' => 'large',
        ],
      ],
    ];

    // Try to get saved configuration from database
    try {
      $result = civicrm_api3('Setting', 'get', [
        'name' => 'chartdashboard_config_' . $userID,
      ]);

      if (!empty($result['values'])) {
        $savedConfig = reset($result['values']);
        return json_decode($savedConfig['chartdashboard_config_' . $userID], TRUE) ?: $defaultConfig;
      }
    }
    catch (Exception $e) {
      // Fall back to default configuration
    }

    return $defaultConfig;
  }

  /**
   * AJAX endpoint to save dashboard configuration
   */
  public static function saveConfig() {
    $userID = CRM_Core_Session::getLoggedInContactID();

    if (!$userID || !CRM_Core_Permission::check('view chart dashboard')) {
      CRM_Utils_JSON::output(['error' => 'Permission denied']);
    }

    $config = json_decode(file_get_contents('php://input'), TRUE);

    if (!$config) {
      CRM_Utils_JSON::output(['error' => 'Invalid configuration data']);
    }

    try {
      civicrm_api3('Setting', 'create', [
        'chartdashboard_config_' . $userID => json_encode($config),
      ]);

      CRM_Utils_JSON::output(['success' => TRUE]);
    }
    catch (Exception $e) {
      CRM_Utils_JSON::output(['error' => 'Failed to save configuration: ' . $e->getMessage()]);
    }
  }
}
