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
 * This class holds all the Pseudo constants that are specific for CiviCase.
 */
class CRM_Case_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * Get all the case statues.
   *
   *
   * @param string $column
   * @param bool $onlyActive
   * @param string|null $condition
   * @param bool $fresh
   *
   * @return array
   *   array reference of all case statues
   */
  public static function caseStatus($column = 'label', $onlyActive = TRUE, $condition = NULL, $fresh = FALSE) {
    if (!$condition) {
      $condition = 'AND filter = 0';
    }

    return CRM_Core_OptionGroup::values('case_status',
      FALSE, FALSE, FALSE, $condition,
      $column, $onlyActive, $fresh
    );

  }

  /**
   * Get all the redaction rules.
   *
   * @param int $filter
   *
   * @return array
   *   array reference of all redaction rules
   */
  public static function redactionRule($filter = NULL) {
    $condition = NULL;
    if ($filter === 0) {
      $condition = "  AND (v.filter = 0 OR v.filter IS NULL)";
    }
    elseif ($filter === 1) {
      $condition = "  AND  v.filter = 1";
    }

    return CRM_Core_OptionGroup::values('redaction_rule', TRUE, FALSE, FALSE, $condition);
  }

  /**
   * Get all the case type.
   *
   *
   * @param string $column
   * @param bool $onlyActive
   *
   * @return array
   *   array reference of all case type
   */
  public static function caseType($column = 'title', $onlyActive = TRUE) {
    if ($onlyActive) {
      $condition = " is_active = 1 ";
    }
    else {
      $condition = NULL;
    }
    $caseType = NULL;
    // FIXME: deprecated?
    CRM_Core_PseudoConstant::populate(
      $caseType,
      'CRM_Case_DAO_CaseType',
      TRUE,
      $column,
      '',
      $condition,
      'weight',
      'id'
    );

    return $caseType;
  }

  /**
   * Get all the Encounter Medium.
   *
   *
   * @param string $column
   * @param bool $onlyActive
   *
   * @return array
   *   array reference of all Encounter Medium.
   */
  public static function encounterMedium($column = 'label', $onlyActive = TRUE) {
    return CRM_Core_OptionGroup::values('encounter_medium',
      FALSE, FALSE, FALSE, NULL,
      $column, $onlyActive
    );
  }

  /**
   * Get all Activity types for the CiviCase component.
   *
   * The static array activityType is returned
   *
   * @param bool $indexName
   *   True return activity name in array.
   *   key else activity id as array key.
   *
   * @param bool $all
   *
   *
   * @return array
   *   array reference of all activity types.
   */
  public static function &caseActivityType($indexName = TRUE, $all = FALSE) {
    $cache = (int) $indexName . '_' . (int) $all;

    if (!isset(Civi::$statics[__CLASS__]['activityTypeList'][$cache])) {
      Civi::$statics[__CLASS__]['activityTypeList'][$cache] = [];

      $query = "
              SELECT  v.label as label ,v.value as value, v.name as name, v.description as description, v.icon
              FROM   civicrm_option_value v,
                     civicrm_option_group g
              WHERE  v.option_group_id = g.id
                     AND  g.name         = 'activity_type'
                     AND  v.is_active    = 1
                     AND  g.is_active    = 1";

      if (!$all) {
        $componentId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Component',
          'CiviCase',
          'id', 'name'
        );
        $query .= " AND  v.component_id = {$componentId} ";
      }

      $query .= "  ORDER BY v.weight";

      $dao = CRM_Core_DAO::executeQuery($query);

      $activityTypes = [];
      while ($dao->fetch()) {
        if ($indexName) {
          $index = $dao->name;
        }
        else {
          $index = $dao->value;
        }
        $activityTypes[$index] = [];
        $activityTypes[$index]['id'] = $dao->value;
        $activityTypes[$index]['label'] = $dao->label;
        $activityTypes[$index]['name'] = $dao->name;
        $activityTypes[$index]['icon'] = $dao->icon;
        $activityTypes[$index]['description'] = $dao->description;
      }
      Civi::$statics[__CLASS__]['activityTypeList'][$cache] = $activityTypes;
    }
    return Civi::$statics[__CLASS__]['activityTypeList'][$cache];
  }

  /**
   * Flush given pseudoconstant so it can be reread from db
   * next time it's requested.
   *
   *
   * @param bool|string $name pseudoconstant to be flushed
   */
  public static function flush($name = 'cache') {
    if (isset(self::$$name)) {
      self::$$name = NULL;
    }
  }

}
