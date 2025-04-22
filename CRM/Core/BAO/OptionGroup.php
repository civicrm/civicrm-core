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
class CRM_Core_BAO_OptionGroup extends CRM_Core_DAO_OptionGroup implements \Civi\Core\HookInterface {

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
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
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_OptionGroup', $id, 'is_active', $is_active);
  }

  /**
   * Add the Option Group.
   *
   * @param array $params
   *
   * @deprecated
   * @return CRM_Core_DAO_OptionGroup
   */
  public static function add($params) {
    // This is very similar to CRM_Core_DAO::makeNameFromLabel which would be
    // called automatically via `self::writeRecord()`
    // TODO: Check if the differences matter, then deprecate this function and switch to writeRecord.
    if (empty($params['name']) && empty($params['id'])) {
      $params['name'] = CRM_Utils_String::titleToVar(strtolower($params['title']));
    }
    elseif (!empty($params['name']) && strpos($params['name'], ' ')) {
      $params['name'] = CRM_Utils_String::titleToVar(strtolower($params['name']));
    }
    elseif (!empty($params['name'])) {
      $params['name'] = strtolower($params['name']);
    }
    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->copyValues($params);
    $optionGroup->save();
    return $optionGroup;
  }

  /**
   * Delete Option Group.
   *
   * @deprecated
   * @param int $optionGroupId
   */
  public static function del($optionGroupId) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    static::deleteRecord(['id' => $optionGroupId]);
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if ($event->action === 'delete') {
      // need to delete all option value field before deleting group
      \Civi\Api4\OptionValue::delete(FALSE)
        ->addWhere('option_group_id', '=', $event->id)
        ->execute();
    }
  }

  /**
   * Callback for hook_civicrm_post().
   * @param \Civi\Core\Event\PostEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    Civi::cache('metadata')->clear();
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
    $existingValues = civicrm_api3('OptionGroup', 'get', [
      'name' => $params['name'],
      'return' => 'id',
    ]);
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
   * @param string[] $activeValues
   *   e.g. array("en_CA","fr_CA")
   */
  public static function setActiveValues($optionGroupName, $activeValues) {
    $params = [
      1 => [$optionGroupName, 'String'],
    ];

    // convert activeValues into placeholders / params in the query
    $placeholders = [];
    $i = count($params) + 1;
    foreach ($activeValues as $value) {
      $placeholders[] = "%{$i}";
      $params[$i] = [$value, 'String'];
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
