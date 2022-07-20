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
   * @return CRM_Financial_DAO_PaymentProcessorType
   */
  public static function create($params) {
    $job = new CRM_Core_DAO_Job();
    $job->copyValues($params);
    return $job->save();
  }

  /**
   * Retrieve DB object and copy to defaults array.
   *
   * @param array $params
   *   Array of criteria values.
   * @param array $defaults
   *   Array to be populated with found values.
   *
   * @return self|null
   *   The DAO object, if found.
   *
   * @deprecated
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return bool
   *   true if we found and updated the object, else false
   */
  public static function setIsActive($id, $is_active) {
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
    CRM_Utils_Hook::copy('Job', $copy);

    return $copy;
  }

}
