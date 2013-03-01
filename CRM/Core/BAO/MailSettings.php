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
 * $Id$
 *
 */
class CRM_Core_BAO_MailSettings extends CRM_Core_DAO_MailSettings {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Return the DAO object containing to the default row of
   * civicrm_mail_settings and cache it for further calls
   *
   * @return object  DAO with the default mail settings set
   */
  static function &defaultDAO() {
    static $dao = NULL;
    if (!$dao) {
      $dao             = new self;
      $dao->is_default = 1;
      $dao->domain_id  = CRM_Core_Config::domainID();
      $dao->find(TRUE);
    }
    return $dao;
  }

  /**
   * Return the domain from the default set of settings
   *
   * @return string  default domain
   */
  static function defaultDomain() {
    return self::defaultDAO()->domain;
  }

  /**
   * Return the localpart from the default set of settings
   *
   * @return string  default localpart
   */
  static function defaultLocalpart() {
    return self::defaultDAO()->localpart;
  }

  /**
   * Return the return path from the default set of settings
   *
   * @return string  default return path
   */
  static function defaultReturnPath() {
    return self::defaultDAO()->return_path;
  }

  /**
   * Return the "include message ID" flag from the default set of settings.
   *
   * @return boolean  default include message ID
   */
  static function includeMessageId() {
    return CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'include_message_id',
      NULL,
      FALSE
    );
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * mail settings id. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Core_BAO_MailSettings object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
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
   * function to add new mail Settings.
   *
   * @param array $params reference array contains the values submitted by the form
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params) {
    $result = NULL;
    if (empty($params)) {
      return $result;
    }

    $params['is_ssl'] = CRM_Utils_Array::value('is_ssl', $params, FALSE);
    $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);

    //handle is_default.
    if ($params['is_default']) {
      $query = 'UPDATE civicrm_mail_settings SET is_default = 0 WHERE domain_id = %1';
      $queryParams = array(1 => array(CRM_Core_Config::domainID(), 'Integer'));
      CRM_Core_DAO::executeQuery($query, $queryParams);
    }

    $mailSettings = new CRM_Core_DAO_MailSettings();
    $mailSettings->copyValues($params);
    $result = $mailSettings->save();

    return $result;
  }

  /**
   * takes an associative array and creates a mail settings object
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_Core_BAO_MailSettings object
   * @access public
   * @static
   */
  static function &create(&$params) {
    $transaction = new CRM_Core_Transaction();

    $mailSettings = self::add($params);
    if (is_a($mailSettings, 'CRM_Core_Error')) {
      $mailSettings->rollback();
      return $mailSettings;
    }

    $transaction->commit();

    return $mailSettings;
  }

  /**
   * Function to delete the mail settings.
   *
   * @param int $id mail settings id
   *
   * @access public
   * @static
   *
   */
  static function deleteMailSettings($id) {
    $results = NULL;
    $transaction = new CRM_Core_Transaction();

    $mailSettings     = new CRM_Core_DAO_MailSettings();
    $mailSettings->id = $id;
    $results          = $mailSettings->delete();

    $transaction->commit();

    return $results;
  }
}

