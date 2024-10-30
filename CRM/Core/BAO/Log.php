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
 * BAO object for crm_log table
 */
class CRM_Core_BAO_Log extends CRM_Core_DAO_Log {
  public static $_processed = NULL;

  /**
   * @param int $id
   * @param string $table
   *
   * @return array|null
   *
   */
  public static function &lastModified($id, $table = 'civicrm_contact') {

    $log = new CRM_Core_DAO_Log();

    $log->entity_table = $table;
    $log->entity_id = $id;
    $log->orderBy('modified_date desc');
    $log->limit(1);
    $displayName = $result = $contactImage = NULL;
    if ($log->find(TRUE)) {
      if ($log->modified_id) {
        [$displayName, $contactImage] = CRM_Contact_BAO_Contact::getDisplayAndImage($log->modified_id);
      }
      $result = [
        'id' => $log->modified_id,
        'name' => $displayName,
        'image' => $contactImage,
        'date' => $log->modified_date,
      ];
    }
    return $result;
  }

  /**
   * Add log to civicrm_log table.
   *
   * @param array $params
   *   Array of name-value pairs of log table.
   * @return CRM_Core_DAO_Log
   */
  public static function add(&$params) {
    $log = new CRM_Core_DAO_Log();
    $log->copyValues($params);
    $log->save();
    return $log;
  }

  /**
   * @param int $contactID
   * @param string $tableName
   * @param int $tableID
   * @param int $userID
   *
   * @throws \CRM_Core_Exception
   */
  public static function register(
    $contactID,
    $tableName,
    $tableID,
    $userID = NULL
  ) {
    if (!self::$_processed) {
      self::$_processed = [];
    }

    if (!$userID) {
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
    }

    if (!$userID) {
      $api_key = CRM_Utils_Request::retrieve('api_key', 'String');

      if ($api_key && strtolower($api_key) !== 'null') {
        $userID = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $api_key, 'id', 'api_key');
      }
    }

    if (!$userID) {
      $userID = $contactID;
    }

    if (!$userID) {
      return;
    }

    $log = new CRM_Core_DAO_Log();
    $log->id = NULL;

    if (isset(self::$_processed[$contactID])) {
      if (isset(self::$_processed[$contactID][$userID])) {
        $log->id = self::$_processed[$contactID][$userID];
      }
      self::$_processed[$contactID][$userID] = 1;
    }
    else {
      self::$_processed[$contactID] = [$userID => 1];
    }

    $logData = "$tableName,$tableID";
    if (!$log->id) {
      $log->entity_table = 'civicrm_contact';
      $log->entity_id = $contactID;
      $log->modified_id = $userID;
      $log->modified_date = date("YmdHis");
      $log->data = $logData;
      $log->save();
    }
    else {
      $query = "
UPDATE civicrm_log
   SET data = concat( data, ':$logData' )
 WHERE id = {$log->id}
";
      CRM_Core_DAO::executeQuery($query);
    }

    self::$_processed[$contactID][$userID] = $log->id;
  }

  /**
   * Get log record count for a Contact.
   *
   * @param int $contactID
   *
   * @return int
   *   count of log records
   */
  public static function getContactLogCount($contactID) {
    $query = "SELECT count(*) FROM civicrm_log
                   WHERE civicrm_log.entity_table = 'civicrm_contact' AND civicrm_log.entity_id = {$contactID}";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * Get the id of the report to use to display the change log.
   *
   * If logging is not enabled a return value of FALSE means to use the
   * basic change log view.
   *
   * @return int|false
   *   report id of Contact Logging Report (Summary)
   */
  public static function useLoggingReport() {
    $loggingSchema = new CRM_Logging_Schema();
    if (!$loggingSchema->isEnabled()) {
      return FALSE;
    }
    try {
      // Use civicrm_api4 wrapper as it will exception rather than fatal if civi-report disabled.
      return civicrm_api4('ReportInstance', 'get', [
        'where' => [['report_id', '=', 'logging/contact/summary']],
      ])->first()['id'] ?? FALSE;
    }
    catch (CRM_Core_Exception $e) {
      // Either CiviReport is disabled or the contact does not have permission to
      // view the summary report. Return FALSE to use the basic log.
      return FALSE;
    }
  }

}
