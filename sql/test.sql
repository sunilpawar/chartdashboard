
-- Contribution table indexes for faster dashboard queries
CREATE INDEX `idx_contribution_dashboard_status_date`
  ON `civicrm_contribution` (`contribution_status_id`, `receive_date`);

CREATE INDEX `idx_contribution_dashboard_date_contact`
  ON `civicrm_contribution` (`receive_date`, `contact_id`);

-- CREATE INDEX `idx_contribution_dashboard_campaign`
--  ON `civicrm_contribution` (`campaign_id`, `receive_date`, `contribution_status_id`);

-- CREATE INDEX `idx_contribution_dashboard_recur`
--  ON `civicrm_contribution` (`contribution_recur_id`, `receive_date`, `contribution_status_id`);

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
