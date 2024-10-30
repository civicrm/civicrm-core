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
   * Get a list of setup-actions.
   *
   * @return array{array{title:string, callback: mixed, url: string}}
   *   List of available actions. See description in the hook-docs.
   * @see CRM_Utils_Hook::mailSetupActions()
   */
  public static function getSetupActions() {
    $setupActions = [];
    $setupActions['standard'] = [
      'title' => ts('Standard Mail Account'),
      'callback' => ['CRM_Core_BAO_MailSettings', 'setupStandardAccount'],
    ];

    CRM_Utils_Hook::mailSetupActions($setupActions);

    foreach ($setupActions as $key => &$setupAction) {
      if (!isset($setupAction['url'])) {
        $setupAction['url'] = (string) Civi::url('//civicrm/ajax/setupMailAccount')->addQuery(['type' => $key]);
      }
    }

    return $setupActions;
  }

  public static function setupStandardAccount($setupAction) {
    return [
      'url' => CRM_Utils_System::url('civicrm/admin/mailSettings/edit', 'action=add&reset=1', TRUE, NULL, FALSE),
    ];
  }

  /**
   * Return the BAO object containing to the default row of
   * civicrm_mail_settings and cache it for further calls
   *
   * @return CRM_Core_BAO_MailSettings
   *   DAO with the default mail settings set
   */
  public static function defaultDAO(): self {
    $domainID = CRM_Core_Config::domainID();
    if (!isset(\Civi::$statics[__CLASS__][__FUNCTION__][$domainID])) {
      \Civi::$statics[__CLASS__][__FUNCTION__][$domainID] = [];
      $dao = new self();
      $dao->is_default = 1;
      $dao->domain_id = $domainID;
      $dao->find(TRUE);
      \Civi::$statics[__CLASS__][__FUNCTION__][$domainID] = $dao;
    }
    return \Civi::$statics[__CLASS__][__FUNCTION__][$domainID];
  }

  /**
   * Clear cached variables.
   */
  public static function clearCache(): void {
    unset(\Civi::$statics[__CLASS__]);
  }

  /**
   * Return the domain from the default set of settings.
   *
   * @return string
   *   default domain
   */
  public static function defaultDomain(): string {
    return self::defaultDAO()->domain ?? '';
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
   * Add new mail Settings.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   * @deprecated since 5.72 will be removed around 5.82
   *
   * @return CRM_Core_DAO_MailSettings
   */
  public static function add($params) {
    CRM_Core_Error::deprecatedFunctionWarning('use apiv4');
    $result = NULL;
    if (empty($params)) {
      return $result;
    }

    if (empty($params['id'])) {
      $params['is_ssl'] ??= FALSE;
      $params['is_default'] ??= FALSE;
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
   *
   * @return CRM_Core_DAO_MailSettings
   * @throws \CRM_Core_Exception
   */
  public static function create(array $params): CRM_Core_DAO_MailSettings {
    if (empty($params['id'])) {
      $params['is_ssl'] ??= FALSE;
      $params['is_default'] ??= FALSE;
    }

    $transaction = new CRM_Core_Transaction();

    if (!empty($params['is_default'])) {
      $query = 'UPDATE civicrm_mail_settings SET is_default = 0 WHERE domain_id = %1';
      $queryParams = [1 => [CRM_Core_Config::domainID(), 'Integer']];
      CRM_Core_DAO::executeQuery($query, $queryParams);
    }

    $result = self::writeRecord($params);
    $transaction->commit();
    CRM_Core_BAO_MailSettings::clearCache();
    return $result;
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
