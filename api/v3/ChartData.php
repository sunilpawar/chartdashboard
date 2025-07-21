<?php
use CRM_Chartdashboard_ExtensionUtil as E;
/**
 * ChartData.Get API specification
 */
function _civicrm_api3_chart_data_Get_spec(&$spec) {
  $spec['chart_type'] = [
    'name' => 'chart_type',
    'title' => 'Chart Type',
    'description' => 'Type of chart data to retrieve',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'options' => [
      'realtime_donations' => 'Real-time Donation Dashboard',
      'recurring_vs_onetime' => 'Recurring vs One-Time Contributions',
      'lapsed_donors' => 'Lapsed Donor Value Analysis',
      'donor_retention' => 'Donor Retention Funnel',
      'avg_gift_trend' => 'Average Gift Size Over Time',
      'campaign_progress' => 'Campaign-Specific Fundraising Progress',
      'pledged_vs_actual' => 'Pledged vs Actual Income',
      'membership_revenue' => 'Membership Revenue Breakdown',
    ],
  ];
  $spec['time_range'] = [
    'name' => 'time_range',
    'title' => 'Time Range',
    'description' => 'Time range for the data',
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => '7days',
    'options' => [
      '24hr' => 'Last 24 Hours',
      '2days' => 'Last 2 Days',
      '7days' => 'Last 7 Days',
      '1month' => 'Last Month',
      '3months' => 'Last 3 Months',
      '6months' => 'Last 6 Months',
      '1year' => 'Last Year',
    ],
  ];
}

/**
 * ChartData.Get API
 */
function civicrm_api3_chart_data_Get($params) {
  try {
    $chartType = $params['chart_type'];
    $timeRange = CRM_Utils_Array::value('time_range', $params, '7days');

    // Check permissions
    if (!CRM_Core_Permission::check('view chart dashboard')) {
      return civicrm_api3_create_error('Permission denied: view chart dashboard required');
    }

    $data = [];

    switch ($chartType) {
      case 'realtime_donations':
        $data = CRM_Chartdashboard_BAO_DashboardData::getRealTimeDonationData($timeRange);
        break;

      case 'recurring_vs_onetime':
        $data = CRM_Chartdashboard_BAO_DashboardData::getRecurringVsOneTimeData($timeRange);
        break;

      case 'lapsed_donors':
        $data = CRM_Chartdashboard_BAO_DashboardData::getLapsedDonorData($timeRange);
        break;

      case 'donor_retention':
        $data = CRM_Chartdashboard_BAO_DashboardData::getDonorRetentionData();
        break;

      case 'avg_gift_trend':
        $data = CRM_Chartdashboard_BAO_DashboardData::getAverageGiftTrendData($timeRange);
        break;

      case 'campaign_progress':
        $data = CRM_Chartdashboard_BAO_DashboardData::getCampaignProgressData();
        break;

      case 'pledged_vs_actual':
        $data = CRM_Chartdashboard_BAO_DashboardData::getPledgedVsActualData($timeRange);
        break;

      case 'membership_revenue':
        $data = CRM_Chartdashboard_BAO_DashboardData::getMembershipRevenueData($timeRange);
        break;

      default:
        return civicrm_api3_create_error('Invalid chart type: ' . $chartType);
    }

    return civicrm_api3_create_success($data, $params, 'ChartData', 'Get');

  }
  catch (Exception $e) {
    return civicrm_api3_create_error('Error retrieving chart data: ' . $e->getMessage());
  }
}

/**
 * ChartData.GetAvailableCharts API specification
 */
function _civicrm_api3_chart_data_GetAvailableCharts_spec(&$spec) {
  // No specific parameters needed
}

/**
 * ChartData.GetAvailableCharts API
 */
function civicrm_api3_chart_data_GetAvailableCharts($params) {
  try {
    // Check permissions
    if (!CRM_Core_Permission::check('view chart dashboard')) {
      return civicrm_api3_create_error('Permission denied: view chart dashboard required');
    }

    $availableCharts = [
      'realtime_donations' => [
        'title' => E::ts('Real-Time Donation Dashboard'),
        'description' => E::ts('Visual real-time display of donation totals, goals, and progress bars'),
        'chart_types' => ['line', 'bar'],
        'supports_time_range' => TRUE,
      ],
      'recurring_vs_onetime' => [
        'title' => E::ts('Recurring vs One-Time Contributions'),
        'description' => E::ts('Visual comparison of recurring vs. one-time donations over time'),
        'chart_types' => ['stacked_bar', 'line'],
        'supports_time_range' => TRUE,
      ],
      'lapsed_donors' => [
        'title' => E::ts('Lapsed Donor Value Analysis'),
        'description' => E::ts('Charts showing donation drop-offs by year, cohort, or segment'),
        'chart_types' => ['bar', 'pie'],
        'supports_time_range' => FALSE,
      ],
      'donor_retention' => [
        'title' => E::ts('Donor Retention Funnel'),
        'description' => E::ts('Visualisation of how many donors give again year over year'),
        'chart_types' => ['funnel', 'bar'],
        'supports_time_range' => FALSE,
      ],
      'avg_gift_trend' => [
        'title' => E::ts('Average Gift Size Over Time'),
        'description' => E::ts('Insightful trend lines showing how average donation amounts evolve'),
        'chart_types' => ['line', 'area'],
        'supports_time_range' => TRUE,
      ],
      'campaign_progress' => [
        'title' => E::ts('Campaign-Specific Fundraising Progress'),
        'description' => E::ts('Visual goal progress charts for active campaigns'),
        'chart_types' => ['progress', 'horizontal_bar'],
        'supports_time_range' => FALSE,
      ],
      'pledged_vs_actual' => [
        'title' => E::ts('Pledged vs Actual Income'),
        'description' => E::ts('Bar charts comparing expected pledges and actual receipts'),
        'chart_types' => ['grouped_bar', 'line'],
        'supports_time_range' => TRUE,
      ],
      'membership_revenue' => [
        'title' => E::ts('Membership Revenue Breakdown by Type'),
        'description' => E::ts('Revenue analysis by membership type and category'),
        'chart_types' => ['pie', 'doughnut', 'bar'],
        'supports_time_range' => TRUE,
      ],
    ];

    return civicrm_api3_create_success($availableCharts, $params, 'ChartData', 'GetAvailableCharts');

  }
  catch (Exception $e) {
    return civicrm_api3_create_error('Error retrieving available charts: ' . $e->getMessage());
  }
}


/**
 * ChartData.WarmupCache API specification
 */
function _civicrm_api3_chart_data_WarmupCache_spec(&$spec) {
  $spec['chart_types'] = [
    'name' => 'chart_types',
    'title' => 'Chart Types',
    'description' => 'Specific chart types to warm up (comma-separated). If empty, all charts will be warmed up.',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
  ];
  $spec['time_ranges'] = [
    'name' => 'time_ranges',
    'title' => 'Time Ranges',
    'description' => 'Specific time ranges to cache (comma-separated). If empty, common ranges will be cached.',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
  ];
  $spec['force_refresh'] = [
    'name' => 'force_refresh',
    'title' => 'Force Refresh',
    'description' => 'Force refresh of existing cache entries',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => FALSE,
  ];
}

/**
 * ChartData.WarmupCache API
 *
 * This API warms up the cache for chart dashboard data to improve performance
 */
function civicrm_api3_chart_data_WarmupCache($params) {
  try {
    // Check permissions - require administrative access for cache operations
    if (!CRM_Core_Permission::check(['administer CiviCRM', 'configure chart dashboard'])) {
      return civicrm_api3_create_error('Permission denied: administrative access required for cache operations');
    }

    $forceRefresh = !empty($params['force_refresh']);

    // Get chart types to warm up
    $chartTypes = [];
    if (!empty($params['chart_types'])) {
      $chartTypes = explode(',', $params['chart_types']);
      $chartTypes = array_map('trim', $chartTypes);
    }
    else {
      // Default to all available chart types
      $availableCharts = civicrm_api3('ChartData', 'GetAvailableCharts');
      $chartTypes = array_keys($availableCharts['values']);
    }

    // Get time ranges to cache
    $timeRanges = [];
    if (!empty($params['time_ranges'])) {
      $timeRanges = explode(',', $params['time_ranges']);
      $timeRanges = array_map('trim', $timeRanges);
    }
    else {
      // Default to common time ranges
      $timeRanges = ['24hr', '7days', '1month', '3months', '1year'];
    }

    $results = [
      'warmed_up' => 0,
      'skipped' => 0,
      'errors' => 0,
      'details' => [],
    ];

    // Warm up cache for each combination
    foreach ($chartTypes as $chartType) {
      foreach ($timeRanges as $timeRange) {
        try {
          $cacheKey = "chartdashboard_{$chartType}_{$timeRange}";

          // Check if cache exists and is not expired (unless force refresh)
          if (!$forceRefresh && CRM_Chartdashboard_BAO_Cache::isValid($cacheKey)) {
            $results['skipped']++;
            $results['details'][] = [
              'chart_type' => $chartType,
              'time_range' => $timeRange,
              'status' => 'skipped',
              'reason' => 'Valid cache exists',
            ];
            continue;
          }

          // Get data and cache it
          $data = civicrm_api3('ChartData', 'Get', [
            'chart_type' => $chartType,
            'time_range' => $timeRange,
          ]);

          if ($data['is_error'] == 0) {
            // Store in cache
            CRM_Chartdashboard_BAO_Cache::set($cacheKey, $data['values']);

            $results['warmed_up']++;
            $results['details'][] = [
              'chart_type' => $chartType,
              'time_range' => $timeRange,
              'status' => 'success',
              'data_points' => is_array($data['values']) ? count($data['values']) : 1,
            ];
          }
          else {
            $results['errors']++;
            $results['details'][] = [
              'chart_type' => $chartType,
              'time_range' => $timeRange,
              'status' => 'error',
              'error' => $data['error_message'] ?? 'Unknown error',
            ];
          }

        }
        catch (Exception $e) {
          $results['errors']++;
          $results['details'][] = [
            'chart_type' => $chartType,
            'time_range' => $timeRange,
            'status' => 'error',
            'error' => $e->getMessage(),
          ];
        }
      }
    }

    // Log the warmup activity
    CRM_Core_Error::debug_log_message(
      "Chart Dashboard cache warmup completed: {$results['warmed_up']} warmed up, " .
      "{$results['skipped']} skipped, {$results['errors']} errors"
    );

    return civicrm_api3_create_success($results, $params, 'ChartData', 'WarmupCache');

  }
  catch (Exception $e) {
    return civicrm_api3_create_error('Cache warmup failed: ' . $e->getMessage());
  }
}
