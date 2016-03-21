<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class holds all the Pseudo constants that are specific for CiviCase.
 */
class CRM_Case_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * Case statues
   * @var array
   */
  static $caseStatus = array();

  /**
   * Redaction rules
   * @var array
   */
  static $redactionRule;

  /**
   * Case type
   * @var array
   */
  static $caseType = array();

  /**
   * Encounter Medium
   * @var array
   */
  static $encounterMedium = array();

  /**
   * Activity type
   * @var array
   */
  static $activityTypeList = array();

  /**
   * Get all the case statues.
   *
   *
   * @param string $column
   * @param bool $onlyActive
   * @param null $condition
   * @param bool $fresh
   *
   * @return array
   *   array reference of all case statues
   */
  public static function caseStatus($column = 'label', $onlyActive = TRUE, $condition = NULL, $fresh = FALSE) {
    $cacheKey = "{$column}_" . (int) $onlyActive;
    if (!$condition) {
      $condition = 'AND filter = 0';
    }

    if (!isset(self::$caseStatus[$cacheKey]) || $fresh) {
      self::$caseStatus[$cacheKey] = CRM_Core_OptionGroup::values('case_status',
        FALSE, FALSE, FALSE, $condition,
        $column, $onlyActive, $fresh
      );
    }

    return self::$caseStatus[$cacheKey];
  }

  /**
   * Get all the redaction rules.
   *
   *
   * @param null $filter
   *
   * @return array
   *   array reference of all redaction rules
   */
  public static function redactionRule($filter = NULL) {
    // if ( ! self::$redactionRule ) {
    self::$redactionRule = array();

    if ($filter === 0) {
      $condition = "  AND (v.filter = 0 OR v.filter IS NULL)";
    }
    elseif ($filter === 1) {
      $condition = "  AND  v.filter = 1";
    }
    elseif ($filter === NULL) {
      $condition = NULL;
    }

    self::$redactionRule = CRM_Core_OptionGroup::values('redaction_rule', TRUE, FALSE, FALSE, $condition);
    // }
    return self::$redactionRule;
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
    $cacheKey = "{$column}_" . (int) $onlyActive;
    if (!isset(self::$encounterMedium[$cacheKey])) {
      self::$encounterMedium[$cacheKey] = CRM_Core_OptionGroup::values('encounter_medium',
        FALSE, FALSE, FALSE, NULL,
        $column, $onlyActive
      );
    }

    return self::$encounterMedium[$cacheKey];
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

    if (!array_key_exists($cache, self::$activityTypeList)) {
      self::$activityTypeList[$cache] = array();

      $query = "
              SELECT  v.label as label ,v.value as value, v.name as name, v.description as description
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

      $activityTypes = array();
      while ($dao->fetch()) {
        if ($indexName) {
          $index = $dao->name;
        }
        else {
          $index = $dao->value;
        }
        $activityTypes[$index] = array();
        $activityTypes[$index]['id'] = $dao->value;
        $activityTypes[$index]['label'] = $dao->label;
        $activityTypes[$index]['name'] = $dao->name;
        $activityTypes[$index]['description'] = $dao->description;
      }
      self::$activityTypeList[$cache] = $activityTypes;
    }
    return self::$activityTypeList[$cache];
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
