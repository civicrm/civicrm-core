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
class CRM_Core_BAO_PreferencesDate extends CRM_Core_DAO_PreferencesDate {

  /**
   * Static holder for the default LT.
   * @var string
   */
  public static $_defaultPreferencesDate = NULL;

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_BAO_PreferencesDate|null
   *   object on success, null otherwise
   */
  public static function retrieve(&$params, &$defaults) {
    $dao = new CRM_Core_DAO_PreferencesDate();
    $dao->copyValues($params);
    if ($dao->find(TRUE)) {
      CRM_Core_DAO::storeValues($dao, $defaults);
      return $dao;
    }
    return NULL;
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   * @throws CRM_Core_Exception
   */
  public static function setIsActive($id, $is_active) {
    throw new CRM_Core_Exception('Cannot call setIsActive function');
  }

  /**
   * Delete preference dates.
   *
   * @param int $id
   * @throws CRM_Core_Exception
   */
  public static function del($id) {
    throw new CRM_Core_Exception('Cannot call del function');
  }

  /**
   * (Setting Callback - On Change)
   * Respond to changes in the "timeInputFormat" setting.
   *
   * @param array $oldValue
   *   List of component names.
   * @param array $newValue
   *   List of component names.
   * @param array $metadata
   *   Specification of the setting (per *.settings.php).
   */
  public static function onChangeSetting($oldValue, $newValue, $metadata) {
    if ($oldValue == $newValue) {
      return;
    }

    $query = "
UPDATE civicrm_preferences_date
SET    time_format = %1
WHERE  time_format IS NOT NULL
AND    time_format <> ''
";
    $sqlParams = [1 => [$newValue, 'String']];
    CRM_Core_DAO::executeQuery($query, $sqlParams);
  }

}
