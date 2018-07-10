<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 * $Id$
 *
 */
class CRM_Core_BAO_OptionGroup extends CRM_Core_DAO_OptionGroup {

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
   * @return CRM_Core_BAO_OptionGroup
   */
  public static function retrieve(&$params, &$defaults) {
    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->copyValues($params);
    if ($optionGroup->find(TRUE)) {
      CRM_Core_DAO::storeValues($optionGroup, $defaults);
      return $optionGroup;
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
   *
   * @return Object
   *   DAO object on success, null otherwise
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_OptionGroup', $id, 'is_active', $is_active);
  }

  /**
   * Add the Option Group.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   * @param array $ids
   *   Reference array contains the id.
   *
   *
   * @return object
   */
  public static function add(&$params, $ids = array()) {
    if (empty($params['id']) && !empty($ids['optionGroup'])) {
      CRM_Core_Error::deprecatedFunctionWarning('no $ids array');
      $params['id'] = $ids['optionGroup'];
    }
    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->copyValues($params);;
    $optionGroup->save();
    return $optionGroup;
  }

  /**
   * Delete Option Group.
   *
   * @param int $optionGroupId
   *   Id of the Option Group to be deleted.
   */
  public static function del($optionGroupId) {
    // need to delete all option value field before deleting group
    $optionValue = new CRM_Core_DAO_OptionValue();
    $optionValue->option_group_id = $optionGroupId;
    $optionValue->delete();

    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->id = $optionGroupId;
    $optionGroup->delete();
  }

  /**
   * Get title of the option group.
   *
   * @param int $optionGroupId
   *   Id of the Option Group.
   *
   * @return string
   *   title
   */
  public static function getTitle($optionGroupId) {
    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->id = $optionGroupId;
    $optionGroup->find(TRUE);
    return $optionGroup->name;
  }

  /**
   * Get DataType for a specified option Group
   *
   * @param int $optionGroupId
   *   Id of the Option Group.
   *
   * @return string|null
   *   Data Type
   */
  public static function getDataType($optionGroupId) {
    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->id = $optionGroupId;
    $optionGroup->find(TRUE);
    return $optionGroup->data_type;
  }

  /**
   * Ensure an option group exists.
   *
   * This function is intended to be called from the upgrade script to ensure
   * that an option group exists, without hitting an error if it already exists.
   *
   * This is sympathetic to sites who might pre-add it.
   *
   * @param array $params
   *
   * @return int
   *   ID of the option group.
   */
  public static function ensureOptionGroupExists($params) {
    $existingValues = civicrm_api3('OptionGroup', 'get', array(
      'name' => $params['name'],
      'return' => 'id',
    ));
    if (!$existingValues['count']) {
      $result = civicrm_api3('OptionGroup', 'create', $params);
      return $result['id'];
    }
    else {
      return $existingValues['id'];
    }
  }

  /**
   * Get the title of an option group by name.
   *
   * @param string $name
   *   The name value for the option group table.
   *
   * @return string
   *   The relevant title.
   */
  public static function getTitleByName($name) {
    $groups = self::getTitlesByNames();
    return $groups[$name];
  }

  /**
   * Get a cached mapping of all group titles indexed by their unique name.
   *
   * We tend to only have a limited number of option groups so memory caching
   * makes more sense than multiple look-ups.
   *
   * @return array
   *   Array of all group titles by name.
   *   e.g
   *   array('activity_status' => 'Activity Status', 'msg_mode' => 'Message Mode'....)
   */
  public static function getTitlesByNames() {
    if (!isset(\Civi::$statics[__CLASS__]) || !isset(\Civi::$statics[__CLASS__]['titles_by_name'])) {
      $dao = CRM_Core_DAO::executeQuery("SELECT name, title FROM civicrm_option_group");
      while ($dao->fetch()) {
        \Civi::$statics[__CLASS__]['titles_by_name'][$dao->name] = $dao->title;
      }
    }
    return \Civi::$statics[__CLASS__]['titles_by_name'];
  }

  /**
   * Set the given values to active, and set all other values to inactive.
   *
   * @param string $optionGroupName
   *   e.g "languages"
   * @param array<string> $activeValues
   *   e.g. array("en_CA","fr_CA")
   */
  public static function setActiveValues($optionGroupName, $activeValues) {
    $params = array(
      1 => array($optionGroupName, 'String'),
    );

    // convert activeValues into placeholders / params in the query
    $placeholders = array();
    $i = count($params) + 1;
    foreach ($activeValues as $value) {
      $placeholders[] = "%{$i}";
      $params[$i] = array($value, 'String');
      $i++;
    }
    $placeholders = implode(', ', $placeholders);

    CRM_Core_DAO::executeQuery("
UPDATE civicrm_option_value cov
       LEFT JOIN civicrm_option_group cog ON cov.option_group_id = cog.id
SET cov.is_active = CASE WHEN cov.name IN ({$placeholders}) THEN 1 ELSE 0 END
WHERE cog.name = %1",
      $params
    );
  }

}
