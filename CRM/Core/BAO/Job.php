<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class contains scheduled jobs related functions.
 */
class CRM_Core_BAO_Job extends CRM_Core_DAO_Job {

  /**
   * Add the payment-processor type in the db
   *
   * @param array $params
   *   An assoc array of name/value pairs.
   *
   * @return CRM_Core_DAO_Job
   */
  public static function create($params) {
    $job = new CRM_Core_DAO_Job();
    $job->copyValues($params);
    return $job->save();
  }

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_Job', $id, 'is_active', $is_active);
  }

  /**
   * Function  to delete scheduled job.
   *
   * @param $jobID
   *
   * @return bool|null
   * @deprecated
   * @throws CRM_Core_Exception
   */
  public static function del($jobID) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    self::deleteRecord(['id' => $jobID]);
    return TRUE;
  }

  /**
   * Trim job table on a regular basis to keep it at a good size.
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-10513
   *
   * @param int $maxEntriesToKeep
   * @param int $minDaysToKeep
   */
  public static function cleanup($maxEntriesToKeep = 1000, $minDaysToKeep = 30) {
    // Prevent the job log from getting too big
    // For now, keep last minDays days and at least maxEntries records
    $query = 'SELECT COUNT(*) FROM civicrm_job_log';
    $count = (int) CRM_Core_DAO::singleValueQuery($query);

    if ($count <= $maxEntriesToKeep) {
      return;
    }

    $count = $count - (int) $maxEntriesToKeep;

    $minDaysToKeep = (int) $minDaysToKeep;
    $query = "DELETE FROM civicrm_job_log WHERE run_time < SUBDATE(NOW(), $minDaysToKeep) ORDER BY id LIMIT $count";
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Make a copy of a Job.
   *
   * @param int $id The job id to copy.
   * @param array $params
   * @return CRM_Core_DAO
   */
  public static function copy($id, $params = []) {
    $fieldsFix = [
      'suffix' => [
        'name' => ' - ' . ts('Copy'),
      ],
      'replace' => $params,
    ];
    $copy = CRM_Core_DAO::copyGeneric('CRM_Core_DAO_Job', ['id' => $id], NULL, $fieldsFix);
    $copy->save();
    CRM_Utils_Hook::copy('Job', $copy, $id);

    return $copy;
  }

  /**
   * Parse multi-line `$parameters` string into an array
   *
   * @param string|null $parameters
   * @return array
   * @throws CRM_Core_Exception
   */
  public static function parseParameters(?string $parameters): array {
    $parameters = trim($parameters ?? '');
    if (!empty($parameters) && $parameters[0] === '{') {
      try {
        return json_decode($parameters, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      catch (JsonException $e) {
        throw new CRM_Core_Exception('Job parameters error: ' . $e->getMessage() . '. Parameters: ' . print_r($parameters, TRUE));
      }
    }
    $result = ['version' => 3];
    $lines = $parameters ? explode("\n", $parameters) : [];

    foreach ($lines as $line) {
      $pair = explode("=", $line);
      if ($pair === FALSE || count($pair) !== 2 || !trim($pair[0]) || trim($pair[1]) === '') {
        throw new CRM_Core_Exception('Malformed API parameters in scheduled job');
      }
      $result[trim($pair[0])] = trim($pair[1]);
    }
    return $result;
  }

}
