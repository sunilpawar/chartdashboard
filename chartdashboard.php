<?php

require_once 'chartdashboard.civix.php';
use CRM_Chartdashboard_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 */
function chartdashboard_civicrm_config(&$config) {
  _chartdashboard_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 */
function chartdashboard_civicrm_install() {
  _chartdashboard_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 */
function chartdashboard_civicrm_enable() {
  _chartdashboard_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_navigationMenu().
 */
function chartdashboard_civicrm_navigationMenu(&$menu) {
  _chartdashboard_civix_insert_navigation_menu($menu, 'Contributions', [
    'label' => E::ts('Chart Dashboard'),
    'name' => 'chart_dashboard',
    'url' => 'civicrm/chart-dashboard',
    'permission' => 'view chart dashboard',
    'operator' => 'OR',
    'separator' => 0,
    'weight' => 10,
  ]);
  _chartdashboard_civix_navigationMenu($menu);
}

/**
 * Implements hook_civicrm_permission().
 */
function chartdashboard_civicrm_permission(&$permissions) {
  $permissions['view chart dashboard'] = [
    'label' => E::ts('View Chart Dashboard'),
    'description' => E::ts('Access the donation analytics chart dashboard'),
  ];
  $permissions['configure chart dashboard'] = [
    'label' => E::ts('Configure Chart Dashboard'),
    'description' => E::ts('Configure chart dashboard settings and layout'),
  ];
}

/**
 * Implements hook_civicrm_buildForm().
 */
function chartdashboard_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Chartdashboard_Form_Dashboard') {
    // Add resources for the dashboard form
    CRM_Core_Resources::singleton()
      ->addScriptFile('com.skvare.chartdashboard', 'js/dashboard.js')
      ->addStyleFile('com.skvare.chartdashboard', 'css/dashboard.css')
      ->addScriptUrl('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js');
  }
}

/**
 * Implements hook_civicrm_pageRun().
 */
function chartdashboard_civicrm_pageRun(&$page) {
  $pageName = $page->getVar('_name');
  if ($pageName == 'CRM_Chartdashboard_Page_Dashboard') {
    // Add dashboard page resources
    CRM_Core_Resources::singleton()
      ->addScriptFile('com.skvare.chartdashboard', 'js/dashboard.js')
      ->addStyleFile('com.skvare.chartdashboard', 'css/dashboard.css')
      ->addScriptUrl('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js');
  }
}
