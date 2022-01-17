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
