<?php

use CRM_Chartdashboard_ExtensionUtil as E;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Export page for Chart Dashboard data
 */
class CRM_Chartdashboard_Page_Export extends CRM_Core_Page {

  public function run() {
    // Check permissions
    if (!CRM_Core_Permission::check('view chart dashboard')) {
      CRM_Core_Error::statusBounce('You do not have permission to export chart data.');
    }

    // Check if export is enabled
    $settings = CRM_Chartdashboard_Utils_Helper::getSettings();
    if (empty($settings['enable_export'])) {
      CRM_Core_Session::setStatus(E::ts('Data export is not enabled. Please contact your administrator.'), E::ts('Export Disabled'), 'error');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/chart-dashboard'));
      return;
    }

    // Get export parameters
    $chartType = CRM_Utils_Request::retrieve('chart_type', 'String', $this, TRUE);
    $timeRange = CRM_Utils_Request::retrieve('time_range', 'String', $this, FALSE, '7days');
    $format = CRM_Utils_Request::retrieve('format', 'String', $this, FALSE, 'csv');
    $includeSummary = CRM_Utils_Request::retrieve('include_summary', 'Boolean', $this, FALSE, TRUE);
    $includeMetadata = CRM_Utils_Request::retrieve('include_metadata', 'Boolean', $this, FALSE, TRUE);

    try {
      // Get chart data
      $data = civicrm_api3('ChartData', 'Get', [
        'chart_type' => $chartType,
        'time_range' => $timeRange,
      ]);

      if (empty($data['values'])) {
        CRM_Core_Session::setStatus(E::ts('No data available for export.'), E::ts('Export Error'), 'error');
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/chart-dashboard'));
        return;
      }

      // Export data based on format
      switch ($format) {
        case 'csv':
          $this->exportCSV($chartType, $data['values'], $timeRange);
          break;
        case 'excel':
          $this->exportExcel($chartType, $data['values'], $timeRange);
          break;
        case 'pdf':
          $this->exportPDF($chartType, $data['values'], $timeRange);
          break;
        case 'json':
          $this->exportJSON($chartType, $data['values'], $timeRange);
          break;
        default:
          throw new Exception('Unsupported export format: ' . $format);
      }

    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus(E::ts('Export failed: %1', [1 => $e->getMessage()]), E::ts('Export Error'), 'error');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/chart-dashboard'));
    }
  }

  /**
   * Export data as CSV
   */
  private function exportCSV($chartType, $data, $timeRange) {
    $filename = $this->generateFilename($chartType, $timeRange, 'csv');

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    $csvData = $this->prepareDataForExport($chartType, $data);

    // Write header
    if (!empty($csvData)) {
      fputcsv($output, array_keys($csvData[0]));

      // Write data rows
      foreach ($csvData as $row) {
        fputcsv($output, $row);
      }
    }

    fclose($output);
    CRM_Utils_System::civiExit();
  }

  /**
   * Export data as Excel
   */
  private function exportExcel($chartType, $data, $timeRange) {
    require_once 'packages/PHPExcel/PHPExcel.php';

    $filename = $this->generateFilename($chartType, $timeRange, 'xlsx');
    $csvData = $this->prepareDataForExport($chartType, $data);

    $objPHPExcel = new PHPExcel();
    $objPHPExcel->setActiveSheetIndex(0);
    $worksheet = $objPHPExcel->getActiveSheet();

    $chartInfo = $this->getChartInfo($chartType);
    $worksheet->setTitle(substr($chartInfo['title'], 0, 31)); // Excel sheet name limit

    if (!empty($csvData)) {
      // Write header
      $headers = array_keys($csvData[0]);
      $col = 0;
      foreach ($headers as $header) {
        $worksheet->setCellValueByColumnAndRow($col, 1, $header);
        $worksheet->getStyleByColumnAndRow($col, 1)->getFont()->setBold(TRUE);
        $col++;
      }

      // Write data
      $row = 2;
      foreach ($csvData as $dataRow) {
        $col = 0;
        foreach ($dataRow as $value) {
          $worksheet->setCellValueByColumnAndRow($col, $row, $value);
          $col++;
        }
        $row++;
      }

      // Auto-size columns
      foreach (range(0, count($headers) - 1) as $col) {
        $worksheet->getColumnDimensionByColumn($col)->setAutoSize(TRUE);
      }
    }

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');

    CRM_Utils_System::civiExit();
  }

  /**
   * Export data as PDF
   */
  private function exportPDF($chartType, $data, $timeRange) {
    //require_once 'packages/dompdf/dompdf_config.inc.php';

    $filename = $this->generateFilename($chartType, $timeRange, 'pdf');
    $csvData = $this->prepareDataForExport($chartType, $data);
    $chartInfo = $this->getChartInfo($chartType);

    // Generate HTML for PDF
    $html = $this->generatePDFHTML($chartInfo, $csvData, $timeRange);

    $dompdf = new DOMPDF();
    $dompdf->load_html($html);
    $dompdf->set_paper('A4', 'landscape');
    $dompdf->render();

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo $dompdf->output();
    CRM_Utils_System::civiExit();
  }

  /**
   * Export data as JSON
   */
  private function exportJSON($chartType, $data, $timeRange) {
    $filename = $this->generateFilename($chartType, $timeRange, 'json');
    $chartInfo = $this->getChartInfo($chartType);

    $exportData = [
      'chart_type' => $chartType,
      'chart_title' => $chartInfo['title'],
      'time_range' => $timeRange,
      'export_date' => date('Y-m-d H:i:s'),
      'data' => $data,
    ];

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo json_encode($exportData, JSON_PRETTY_PRINT);
    CRM_Utils_System::civiExit();
  }

  /**
   * Prepare data for export based on chart type
   */
  private function prepareDataForExport($chartType, $data) {
    switch ($chartType) {
      case 'realtime_donations':
        return $this->prepareRealTimeDonationExport($data);
      case 'recurring_vs_onetime':
        return $this->prepareRecurringVsOneTimeExport($data);
      case 'lapsed_donors':
        return $this->prepareLapsedDonorExport($data);
      case 'donor_retention':
        return $this->prepareDonorRetentionExport($data);
      case 'avg_gift_trend':
        return $this->prepareAvgGiftTrendExport($data);
      case 'campaign_progress':
        return $this->prepareCampaignProgressExport($data);
      case 'pledged_vs_actual':
        return $this->preparePledgedVsActualExport($data);
      case 'membership_revenue':
        return $this->prepareMembershipRevenueExport($data);
      default:
        return [];
    }
  }

  /**
   * Prepare real-time donation data for export
   */
  private function prepareRealTimeDonationExport($data) {
    if (!isset($data['chartData']) || !is_array($data['chartData'])) {
      return [];
    }

    $exportData = [];
    foreach ($data['chartData'] as $item) {
      $exportData[] = [
        'Date' => $item['date'],
        'Total Amount' => '$' . number_format($item['amount'], 2),
        'Donation Count' => $item['count'],
        'Average Amount' => '$' . number_format($item['avg_amount'], 2),
      ];
    }
    return $exportData;
  }

  /**
   * Prepare recurring vs one-time data for export
   */
  private function prepareRecurringVsOneTimeExport($data) {
    if (!is_array($data)) {
      return [];
    }

    $exportData = [];
    foreach ($data as $item) {
      $exportData[] = [
        'Date' => $item['date'],
        'Recurring Amount' => '$' . number_format($item['recurring_amount'] ?? 0, 2),
        'One-Time Amount' => '$' . number_format($item['one_time_amount'] ?? 0, 2),
        'Recurring Count' => $item['recurring_count'] ?? 0,
        'One-Time Count' => $item['one_time_count'] ?? 0,
      ];
    }
    return $exportData;
  }

  /**
   * Prepare lapsed donor data for export
   */
  private function prepareLapsedDonorExport($data) {
    if (!is_array($data)) {
      return [];
    }

    $exportData = [];
    foreach ($data as $item) {
      $exportData[] = [
        'Year' => $item['year'],
        'Lapsed Donors' => $item['lapsed_donors'],
        'Lost Value' => '$' . number_format($item['lost_value'], 2),
      ];
    }
    return $exportData;
  }

  /**
   * Prepare donor retention data for export
   */
  private function prepareDonorRetentionExport($data) {
    if (!is_array($data)) {
      return [];
    }

    $exportData = [];
    foreach ($data as $item) {
      $exportData[] = [
        'Year' => $item['year'],
        'New Donors' => $item['new_donors'],
        'Retained Donors' => $item['retained_donors'],
        'Retention Rate' => $item['retention_rate'] . '%',
      ];
    }
    return $exportData;
  }

  /**
   * Prepare average gift trend data for export
   */
  private function prepareAvgGiftTrendExport($data) {
    if (!is_array($data)) {
      return [];
    }

    $exportData = [];
    foreach ($data as $item) {
      $exportData[] = [
        'Period' => $item['period'],
        'Average Gift Size' => '$' . number_format($item['avg_gift_size'], 2),
        'Donation Count' => $item['donation_count'],
        'Minimum Gift' => '$' . number_format($item['min_gift'], 2),
        'Maximum Gift' => '$' . number_format($item['max_gift'], 2),
      ];
    }
    return $exportData;
  }

  /**
   * Prepare campaign progress data for export
   */
  private function prepareCampaignProgressExport($data) {
    if (!is_array($data)) {
      return [];
    }

    $exportData = [];
    foreach ($data as $item) {
      $exportData[] = [
        'Campaign' => $item['campaign_name'],
        'Goal Amount' => '$' . number_format($item['goal_amount'], 2),
        'Raised Amount' => '$' . number_format($item['raised_amount'], 2),
        'Progress' => $item['progress_percentage'] . '%',
        'Donation Count' => $item['donation_count'],
        'Start Date' => $item['start_date'],
        'End Date' => $item['end_date'] ?: 'Ongoing',
      ];
    }
    return $exportData;
  }

  /**
   * Prepare pledged vs actual data for export
   */
  private function preparePledgedVsActualExport($data) {
    if (!is_array($data)) {
      return [];
    }

    $exportData = [];
    foreach ($data as $item) {
      $exportData[] = [
        'Period' => $item['period'],
        'Pledged Amount' => '$' . number_format($item['pledged_amount'], 2),
        'Actual Amount' => '$' . number_format($item['actual_amount'], 2),
        'Fulfillment Rate' => $item['fulfillment_rate'] . '%',
      ];
    }
    return $exportData;
  }

  /**
   * Prepare membership revenue data for export
   */
  private function prepareMembershipRevenueExport($data) {
    if (!is_array($data)) {
      return [];
    }

    $exportData = [];
    foreach ($data as $item) {
      $exportData[] = [
        'Membership Type' => $item['membership_type'],
        'Member Count' => $item['member_count'],
        'Revenue' => '$' . number_format($item['revenue'], 2),
        'Average Fee' => '$' . number_format($item['avg_fee'], 2),
      ];
    }
    return $exportData;
  }

  /**
   * Generate filename for export
   */
  private function generateFilename($chartType, $timeRange, $extension) {
    $chartInfo = $this->getChartInfo($chartType);
    $title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $chartInfo['title']);
    $date = date('Y-m-d');

    return "{$title}_{$timeRange}_{$date}.{$extension}";
  }

  /**
   * Get chart information
   */
  private function getChartInfo($chartType) {
    try {
      $result = civicrm_api3('ChartData', 'GetAvailableCharts');
      return $result['values'][$chartType] ?? ['title' => $chartType];
    }
    catch (Exception $e) {
      return ['title' => $chartType];
    }
  }

  /**
   * Generate HTML for PDF export
   */
  private function generatePDFHTML($chartInfo, $data, $timeRange) {
    $title = $chartInfo['title'];
    $date = date('F j, Y');

    $html = "
    <html>
    <head>
      <meta charset='UTF-8'>
      <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #667eea; text-align: center; }
        h2 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .header-info { text-align: center; margin-bottom: 30px; color: #666; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
      </style>
    </head>
    <body>
      <h1>{$title}</h1>
      <div class='header-info'>
        <p>Time Range: {$timeRange} | Export Date: {$date}</p>
      </div>

      <h2>Data Summary</h2>
    ";

    if (!empty($data)) {
      $html .= "<table>";

      // Add header
      $headers = array_keys($data[0]);
      $html .= "<tr>";
      foreach ($headers as $header) {
        $html .= "<th>" . htmlspecialchars($header) . "</th>";
      }
      $html .= "</tr>";

      // Add data rows
      foreach ($data as $row) {
        $html .= "<tr>";
        foreach ($row as $cell) {
          $html .= "<td>" . htmlspecialchars($cell) . "</td>";
        }
        $html .= "</tr>";
      }

      $html .= "</table>";
    }
    else {
      $html .= "<p>No data available for the selected time range.</p>";
    }

    $html .= "
      <div class='footer'>
        <p>Generated by CiviCRM Chart Dashboard Extension</p>
      </div>
    </body>
    </html>";

    return $html;
  }
}
