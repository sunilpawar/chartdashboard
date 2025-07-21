<?php

use CRM_Chartdashboard_ExtensionUtil as E;

/**
 * BAO class for Dashboard Data
 */
class CRM_Chartdashboard_BAO_DashboardData extends CRM_Core_DAO {

  /**
   * Get real-time donation data with trends
   */
  public static function getRealTimeDonationData($timeRange = '7days') {
    $dateFilter = self::getDateFilter($timeRange);

    $sql = "
      SELECT
        DATE(receive_date) as donation_date,
        SUM(total_amount) as daily_total,
        COUNT(*) as donation_count,
        AVG(total_amount) as avg_amount
      FROM civicrm_contribution c
      WHERE c.contribution_status_id = 1
      AND c.receive_date >= '{$dateFilter['start_date']}'
      AND c.receive_date <= '{$dateFilter['end_date']}'
      GROUP BY DATE(receive_date)
      ORDER BY donation_date ASC
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $data = [];
    $totalAmount = 0;
    $totalCount = 0;

    while ($dao->fetch()) {
      $data[] = [
        'date' => $dao->donation_date,
        'amount' => (float)$dao->daily_total,
        'count' => (int)$dao->donation_count,
        'avg_amount' => (float)$dao->avg_amount,
      ];
      $totalAmount += $dao->daily_total;
      $totalCount += $dao->donation_count;
    }

    return [
      'chartData' => $data,
      'summary' => [
        'total_amount' => $totalAmount,
        'total_count' => $totalCount,
        'avg_donation' => $totalCount > 0 ? $totalAmount / $totalCount : 0,
        'time_range' => $timeRange,
      ]
    ];
  }

  /**
   * Get recurring vs one-time contributions data
   */
  public static function getRecurringVsOneTimeData($timeRange = '7days') {
    $dateFilter = self::getDateFilter($timeRange);

    $sql = "
      SELECT
        DATE(c.receive_date) as contribution_date,
        CASE
          WHEN cr.id IS NOT NULL THEN 'recurring'
          ELSE 'one_time'
        END as contribution_type,
        SUM(c.total_amount) as amount,
        COUNT(*) as count
      FROM civicrm_contribution c
      LEFT JOIN civicrm_contribution_recur cr ON c.contribution_recur_id = cr.id
      WHERE c.contribution_status_id = 1
      AND c.receive_date >= '{$dateFilter['start_date']}'
      AND c.receive_date <= '{$dateFilter['end_date']}'
      GROUP BY DATE(c.receive_date), contribution_type
      ORDER BY contribution_date ASC
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $data = [];

    while ($dao->fetch()) {
      $date = $dao->contribution_date;
      if (!isset($data[$date])) {
        $data[$date] = [
          'date' => $date,
          'recurring_amount' => 0,
          'one_time_amount' => 0,
          'recurring_count' => 0,
          'one_time_count' => 0,
        ];
      }

      $data[$date][$dao->contribution_type . '_amount'] = (float)$dao->amount;
      $data[$date][$dao->contribution_type . '_count'] = (int)$dao->count;
    }

    return array_values($data);
  }

  /**
   * Get lapsed donor analysis data
   */
  public static function getLapsedDonorData($timeRange = '1year') {
    $sql = "
      SELECT
        YEAR(last_contribution.receive_date) as last_donation_year,
        COUNT(DISTINCT contact_id) as donor_count,
        SUM(lifetime_total) as total_value
      FROM (
        SELECT
          c.contact_id,
          MAX(c.receive_date) as receive_date,
          SUM(c.total_amount) as lifetime_total
        FROM civicrm_contribution c
        WHERE c.contribution_status_id = 1
        GROUP BY c.contact_id
        HAVING MAX(c.receive_date) < DATE_SUB(NOW(), INTERVAL 1 YEAR)
      ) as last_contribution
      GROUP BY YEAR(last_contribution.receive_date)
      ORDER BY last_donation_year DESC
      LIMIT 5
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $data = [];

    while ($dao->fetch()) {
      $data[] = [
        'year' => (int)$dao->last_donation_year,
        'lapsed_donors' => (int)$dao->donor_count,
        'lost_value' => (float)$dao->total_value,
      ];
    }

    return $data;
  }

  /**
   * Get donor retention funnel data
   */
  public static function getDonorRetentionData() {
    $sql = "
      SELECT
        first_year,
        COUNT(*) as first_year_donors,
        SUM(CASE WHEN donated_next_year = 1 THEN 1 ELSE 0 END) as retained_donors,
        ROUND(
          (SUM(CASE WHEN donated_next_year = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2
        ) as retention_rate
      FROM (
        SELECT
          c1.contact_id,
          YEAR(MIN(c1.receive_date)) as first_year,
          CASE
            WHEN EXISTS (
              SELECT 1 FROM civicrm_contribution c2
              WHERE c2.contact_id = c1.contact_id
              AND YEAR(c2.receive_date) = YEAR(MIN(c1.receive_date)) + 1
              AND c2.contribution_status_id = 1
            ) THEN 1
            ELSE 0
          END as donated_next_year
        FROM civicrm_contribution c1
        WHERE c1.contribution_status_id = 1
        GROUP BY c1.contact_id
      ) retention_analysis
      WHERE first_year >= YEAR(NOW()) - 5
      GROUP BY first_year
      ORDER BY first_year DESC
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $data = [];

    while ($dao->fetch()) {
      $data[] = [
        'year' => (int)$dao->first_year,
        'new_donors' => (int)$dao->first_year_donors,
        'retained_donors' => (int)$dao->retained_donors,
        'retention_rate' => (float)$dao->retention_rate,
      ];
    }

    return $data;
  }

  /**
   * Get average gift size trend data
   */
  public static function getAverageGiftTrendData($timeRange = '1year') {
    $dateFilter = self::getDateFilter($timeRange);
    $groupBy = self::getGroupByInterval($timeRange);

    $sql = "
      SELECT
        {$groupBy} as period,
        AVG(total_amount) as avg_gift_size,
        COUNT(*) as donation_count,
        MIN(total_amount) as min_gift,
        MAX(total_amount) as max_gift
      FROM civicrm_contribution c
      WHERE c.contribution_status_id = 1
      AND c.receive_date >= '{$dateFilter['start_date']}'
      AND c.receive_date <= '{$dateFilter['end_date']}'
      GROUP BY {$groupBy}
      ORDER BY period ASC
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $data = [];

    while ($dao->fetch()) {
      $data[] = [
        'period' => $dao->period,
        'avg_gift_size' => (float)$dao->avg_gift_size,
        'donation_count' => (int)$dao->donation_count,
        'min_gift' => (float)$dao->min_gift,
        'max_gift' => (float)$dao->max_gift,
      ];
    }

    return $data;
  }

  /**
   * Get campaign-specific fundraising progress
   */
  public static function getCampaignProgressData() {
    $sql = "
      SELECT
        camp.id as campaign_id,
        camp.title as campaign_name,
        camp.goal_amount,
        camp.start_date,
        camp.end_date,
        COALESCE(SUM(c.total_amount), 0) as raised_amount,
        COUNT(c.id) as donation_count,
        CASE
          WHEN camp.goal_amount > 0
          THEN ROUND((COALESCE(SUM(c.total_amount), 0) / camp.goal_amount) * 100, 2)
          ELSE 0
        END as progress_percentage
      FROM civicrm_campaign camp
      LEFT JOIN civicrm_contribution c ON c.campaign_id = camp.id
        AND c.contribution_status_id = 1
      WHERE camp.is_active = 1
      AND (camp.end_date IS NULL OR camp.end_date >= CURDATE())
      GROUP BY camp.id, camp.title, camp.goal_amount, camp.start_date, camp.end_date
      ORDER BY camp.start_date DESC
      LIMIT 10
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $data = [];

    while ($dao->fetch()) {
      $data[] = [
        'campaign_id' => (int)$dao->campaign_id,
        'campaign_name' => $dao->campaign_name,
        'goal_amount' => (float)$dao->goal_amount,
        'raised_amount' => (float)$dao->raised_amount,
        'donation_count' => (int)$dao->donation_count,
        'progress_percentage' => (float)$dao->progress_percentage,
        'start_date' => $dao->start_date,
        'end_date' => $dao->end_date,
      ];
    }

    return $data;
  }

  /**
   * Get pledged vs actual income data
   */
  public static function getPledgedVsActualData($timeRange = '1year') {
    $dateFilter = self::getDateFilter($timeRange);
    $groupBy = self::getGroupByInterval($timeRange);

    $sql = "
      SELECT
        {$groupBy} as period,
        SUM(CASE WHEN p.id IS NOT NULL THEN p.amount ELSE 0 END) as pledged_amount,
        SUM(CASE WHEN c.contribution_status_id = 1 THEN c.total_amount ELSE 0 END) as actual_amount
      FROM civicrm_pledge p
      LEFT JOIN civicrm_pledge_payment pp ON p.id = pp.pledge_id
      LEFT JOIN civicrm_contribution c ON pp.contribution_id = c.id
      WHERE p.create_date >= '{$dateFilter['start_date']}'
      AND p.create_date <= '{$dateFilter['end_date']}'
      GROUP BY {$groupBy}
      ORDER BY period ASC
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $data = [];

    while ($dao->fetch()) {
      $data[] = [
        'period' => $dao->period,
        'pledged_amount' => (float)$dao->pledged_amount,
        'actual_amount' => (float)$dao->actual_amount,
        'fulfillment_rate' => $dao->pledged_amount > 0
          ? round(($dao->actual_amount / $dao->pledged_amount) * 100, 2)
          : 0,
      ];
    }

    return $data;
  }

  /**
   * Get membership revenue breakdown by type
   */
  public static function getMembershipRevenueData($timeRange = '1year') {
    $dateFilter = self::getDateFilter($timeRange);

    $sql = "
      SELECT
        mt.name as membership_type,
        COUNT(DISTINCT m.id) as member_count,
        SUM(c.total_amount) as revenue,
        AVG(c.total_amount) as avg_fee
      FROM civicrm_membership m
      JOIN civicrm_membership_type mt ON m.membership_type_id = mt.id
      LEFT JOIN civicrm_membership_payment mp ON m.id = mp.membership_id
      LEFT JOIN civicrm_contribution c ON mp.contribution_id = c.id
      WHERE m.start_date >= '{$dateFilter['start_date']}'
      AND m.start_date <= '{$dateFilter['end_date']}'
      AND c.contribution_status_id = 1
      GROUP BY mt.id, mt.name
      ORDER BY revenue DESC
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $data = [];

    while ($dao->fetch()) {
      $data[] = [
        'membership_type' => $dao->membership_type,
        'member_count' => (int)$dao->member_count,
        'revenue' => (float)$dao->revenue,
        'avg_fee' => (float)$dao->avg_fee,
      ];
    }

    return $data;
  }

  /**
   * Helper function to get date filter based on time range
   */
  private static function getDateFilter($timeRange) {
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
   * Helper function to get GROUP BY interval based on time range
   */
  private static function getGroupByInterval($timeRange) {
    switch ($timeRange) {
      case '24hr':
      case '2days':
        return "DATE_FORMAT(receive_date, '%Y-%m-%d %H:00:00')";
      case '7days':
      case '1month':
        return "DATE(receive_date)";
      case '3months':
      case '6months':
        return "DATE_FORMAT(receive_date, '%Y-%u')"; // Year-Week
      case '1year':
        return "DATE_FORMAT(receive_date, '%Y-%m')"; // Year-Month
      default:
        return "DATE(receive_date)";
    }
  }
}
