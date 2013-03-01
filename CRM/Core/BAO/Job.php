<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id: $
 *
 */

/**
 * This class contains scheduled jobs related functions.
 */
class CRM_Core_BAO_Job extends CRM_Core_DAO_Job {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }
  /**
   * Function to add the payment-processor type in the db
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   * @param array $ids    the array that holds all the db ids
   *
   * @return object CRM_Financial_DAO_PaymentProcessorType
   * @access public
   * @static
   *
   */
  static function create($params) {
    $job = new CRM_Core_DAO_Job();
    $job->copyValues($params);
    return $job->save();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Core_DAO_Job object on success, null otherwise
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $job = new CRM_Core_DAO_Job();
    $job->copyValues($params);
    if ($job->find(TRUE)) {
      CRM_Core_DAO::storeValues($job, $defaults);
      return $job;
    }
    return NULL;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   *
   * @access public
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_Job', $id, 'is_active', $is_active);
  }

  /**
   * Function  to delete scheduled job
   *
   * @param  int  $jobId     ID of the job to be deleted.
   *
   * @access public
   * @static
   */
  static function del($jobID) {
    if (!$jobID) {
      CRM_Core_Error::fatal(ts('Invalid value passed to delete function'));
    }

    $dao = new CRM_Core_DAO_Job();
    $dao->id = $jobID;
    if (!$dao->find(TRUE)) {
      return NULL;
    }

    if ($dao->delete()) {
      return TRUE;
    }
  }

  /**
   * Trim job table on a regular basis to keep it at a good size
   *
   * CRM-10513
   */
  static function cleanup($maxEntriesToKeep = 1000, $minDaysToKeep = 30) {
    // Prevent the job log from getting too big
    // For now, keep last minDays days and at least maxEntries records
    $query = 'SELECT COUNT(*) FROM civicrm_job_log';
    $count = CRM_Core_DAO::singleValueQuery($query);

    if ( $count <= $maxEntriesToKeep) {
      return;
    }

    $count = $count - $maxEntriesToKeep;

    $query = "DELETE FROM civicrm_job_log WHERE run_time < SUBDATE(NOW(), $minDaysToKeep) LIMIT $count";
    CRM_Core_DAO::executeQuery($query);
  }

}
