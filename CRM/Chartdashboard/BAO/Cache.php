<?php

/**
 * Cache management class for Chart Dashboard
 */
class CRM_Chartdashboard_BAO_Cache {

  /**
   * Cache duration in minutes (default 15 minutes)
   */
  const DEFAULT_CACHE_DURATION = 15;

  /**
   * Set cache value
   */
  public static function set($key, $data, $duration = NULL) {
    if ($duration === NULL) {
      $duration = self::getCacheDuration();
    }

    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));

    // Try database cache first
    try {
      CRM_Core_DAO::executeQuery("
        INSERT INTO civicrm_chartdashboard_cache (cache_key, cache_data, expires_at)
        VALUES (%1, %2, %3)
        ON DUPLICATE KEY UPDATE
        cache_data = VALUES(cache_data),
        expires_at = VALUES(expires_at),
        updated_date = NOW()
      ", [
        1 => [$key, 'String'],
        2 => [json_encode($data), 'String'],
        3 => [$expiresAt, 'String'],
      ]);
      return TRUE;
    }
    catch (Exception $e) {
      // Fall back to CiviCRM cache
      CRM_Core_BAO_Cache::setItem($data, 'chartdashboard', $key, $duration * 60);
      return TRUE;
    }
  }

  /**
   * Get cache value
   */
  public static function get($key) {
    // Try database cache first
    try {
      $dao = CRM_Core_DAO::executeQuery("
        SELECT cache_data, expires_at
        FROM civicrm_chartdashboard_cache
        WHERE cache_key = %1 AND expires_at > NOW()
      ", [
        1 => [$key, 'String'],
      ]);

      if ($dao->fetch()) {
        return json_decode($dao->cache_data, TRUE);
      }
    }
    catch (Exception $e) {
      // Fall back to CiviCRM cache
      return CRM_Core_BAO_Cache::getItem('chartdashboard', $key);
    }

    return NULL;
  }

  /**
   * Check if cache is valid
   */
  public static function isValid($key) {
    try {
      $dao = CRM_Core_DAO::executeQuery("
        SELECT id FROM civicrm_chartdashboard_cache
        WHERE cache_key = %1 AND expires_at > NOW()
      ", [
        1 => [$key, 'String'],
      ]);

      return $dao->fetch() ? TRUE : FALSE;
    }
    catch (Exception $e) {
      // Fall back to checking CiviCRM cache
      $cached = CRM_Core_BAO_Cache::getItem('chartdashboard', $key);
      return $cached !== NULL;
    }
  }

  /**
   * Clear cache
   */
  public static function clear($key = NULL) {
    if ($key) {
      // Clear specific key
      try {
        CRM_Core_DAO::executeQuery("
          DELETE FROM civicrm_chartdashboard_cache WHERE cache_key = %1
        ", [
          1 => [$key, 'String'],
        ]);
      }
      catch (Exception $e) {
        // Ignore database errors
      }

      CRM_Core_BAO_Cache::deleteItem('chartdashboard', $key);
    }
    else {
      // Clear all cache
      try {
        CRM_Core_DAO::executeQuery("DELETE FROM civicrm_chartdashboard_cache");
      }
      catch (Exception $e) {
        // Ignore database errors
      }

      CRM_Core_BAO_Cache::deleteGroup('chartdashboard');
    }
  }

  /**
   * Get cache duration from settings
   */
  private static function getCacheDuration() {
    try {
      $settings = Civi::settings()->get('chartdashboard_settings') ?: [];
      if (!empty($settings)) {
        if (isset($settings['cache_duration']) && is_numeric($settings['cache_duration'])) {
          return (int)$settings['cache_duration'];
        }
      }
    }
    catch (Exception $e) {
      // Use default if settings can't be loaded
    }

    return self::DEFAULT_CACHE_DURATION;
  }

  /**
   * Clean up expired cache entries
   */
  public static function cleanup() {
    try {
      $result = CRM_Core_DAO::executeQuery("
        DELETE FROM civicrm_chartdashboard_cache WHERE expires_at < NOW()
      ");

      return $result->affectedRows();
    }
    catch (Exception $e) {
      return 0;
    }
  }

  /**
   * Get cache statistics
   */
  public static function getStats() {
    try {
      $dao = CRM_Core_DAO::executeQuery("
        SELECT
          COUNT(*) as total_entries,
          SUM(CASE WHEN expires_at > NOW() THEN 1 ELSE 0 END) as valid_entries,
          SUM(CASE WHEN expires_at <= NOW() THEN 1 ELSE 0 END) as expired_entries,
          AVG(LENGTH(cache_data)) as avg_size
        FROM civicrm_chartdashboard_cache
      ");

      if ($dao->fetch()) {
        return [
          'total_entries' => (int)$dao->total_entries,
          'valid_entries' => (int)$dao->valid_entries,
          'expired_entries' => (int)$dao->expired_entries,
          'avg_size_bytes' => (int)$dao->avg_size,
        ];
      }
    }
    catch (Exception $e) {
      // Return empty stats if table doesn't exist
    }

    return [
      'total_entries' => 0,
      'valid_entries' => 0,
      'expired_entries' => 0,
      'avg_size_bytes' => 0,
    ];
  }
}
