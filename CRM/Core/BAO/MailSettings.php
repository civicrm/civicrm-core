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
class CRM_Core_BAO_MailSettings extends CRM_Core_DAO_MailSettings {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Return the DAO object containing to the default row of
   * civicrm_mail_settings and cache it for further calls
   *
   * @param bool $reset
   *
   * @return CRM_Core_BAO_MailSettings
   *   DAO with the default mail settings set
   */
  public static function defaultDAO($reset = FALSE) {
    static $mailSettings = [];
    $domainID = CRM_Core_Config::domainID();
    if (empty($mailSettings[$domainID]) || $reset) {
      $dao = new self();
      $dao->is_default = 1;
      $dao->domain_id = $domainID;
      $dao->find(TRUE);
      $mailSettings[$domainID] = $dao;
    }
    return $mailSettings[$domainID];
  }

  /**
   * Return the domain from the default set of settings.
   *
   * @return string
   *   default domain
   */
  public static function defaultDomain() {
    return self::defaultDAO()->domain;
  }

  /**
   * Return the localpart from the default set of settings.
   *
   * @return string
   *   default localpart
   */
  public static function defaultLocalpart() {
    return self::defaultDAO()->localpart;
  }

  /**
   * Return the return path from the default set of settings.
   *
   * @return string
   *   default return path
   */
  public static function defaultReturnPath() {
    return self::defaultDAO()->return_path;
  }

  /**
   * Return the "include message ID" flag from the default set of settings.
   *
   * @return bool
   *   default include message ID
   */
  public static function includeMessageId() {
    return Civi::settings()->get('include_message_id');
  }

  /**
   * Retrieve DB object based on input parameters.
   *
   * It also stores all the retrieved values in the default array.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_BAO_MailSettings
   */
  public static function retrieve(&$params, &$defaults) {
    $mailSettings = new CRM_Core_DAO_MailSettings();
    $mailSettings->copyValues($params);

    $result = NULL;
    if ($mailSettings->find(TRUE)) {
      CRM_Core_DAO::storeValues($mailSettings, $defaults);
      $result = $mailSettings;
    }

    return $result;
  }

  /**
   * Add new mail Settings.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   *
   *
   * @return object
   */
  public static function add(&$params) {
    $result = NULL;
    if (empty($params)) {
      return $result;
    }

    if (empty($params['id'])) {
      $params['is_ssl'] = CRM_Utils_Array::value('is_ssl', $params, FALSE);
      $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);
    }

    //handle is_default.
    if (!empty($params['is_default'])) {
      $query = 'UPDATE civicrm_mail_settings SET is_default = 0 WHERE domain_id = %1';
      $queryParams = [1 => [CRM_Core_Config::domainID(), 'Integer']];
      CRM_Core_DAO::executeQuery($query, $queryParams);
    }

    $mailSettings = new CRM_Core_DAO_MailSettings();
    $mailSettings->copyValues($params);
    $result = $mailSettings->save();

    return $result;
  }

  /**
   * Takes an associative array and creates a mail settings object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Core_BAO_MailSettings
   */
  public static function create(&$params) {
    $transaction = new CRM_Core_Transaction();

    $mailSettings = self::add($params);
    if (is_a($mailSettings, 'CRM_Core_Error')) {
      $mailSettings->rollback();
      return $mailSettings;
    }

    $transaction->commit();
    CRM_Core_BAO_MailSettings::defaultDAO(TRUE);
    return $mailSettings;
  }

  /**
   * Delete the mail settings.
   *
   * @param int $id
   *   Mail settings id.
   *
   * @return mixed|null
   */
  public static function deleteMailSettings($id) {
    $results = NULL;
    $transaction = new CRM_Core_Transaction();

    $mailSettings = new CRM_Core_DAO_MailSettings();
    $mailSettings->id = $id;
    $results = $mailSettings->delete();

    $transaction->commit();

    return $results;
  }

}
