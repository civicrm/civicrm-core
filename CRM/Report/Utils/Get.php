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
 * $Id$
 *
 */
class CRM_Report_Utils_Get {

  /**
   * @param string $name
   * @param $type
   *
   * @return mixed|null
   */
  public static function getTypedValue($name, $type) {
    $value = CRM_Utils_Array::value($name, $_GET);
    if ($value === NULL) {
      return NULL;
    }
    return CRM_Utils_Type::escape($value,
      CRM_Utils_Type::typeToString($type),
      FALSE
    );
  }

  /**
   * @param string $fieldName
   * @param $field
   * @param $defaults
   *
   * @return bool
   */
  public static function dateParam($fieldName, &$field, &$defaults) {
    // type = 12 (datetime) is not recognized by Utils_Type::escape() method,
    // and therefore the below hack
    $type = 4;

    $from = self::getTypedValue("{$fieldName}_from", $type);
    $to = self::getTypedValue("{$fieldName}_to", $type);

    $relative = CRM_Utils_Array::value("{$fieldName}_relative", $_GET);
    if ($relative) {
      list($from, $to) = CRM_Report_Form::getFromTo($relative, NULL, NULL);
      $from = substr($from, 0, 8);
      $to = substr($to, 0, 8);
    }

    if (!($from || $to)) {
      return FALSE;
    }

    if ($from !== NULL) {
      $dateFrom = CRM_Utils_Date::setDateDefaults($from);
      if ($dateFrom !== NULL &&
        !empty($dateFrom[0])
      ) {
        $defaults["{$fieldName}_from"] = $dateFrom[0];
      }
    }

    if ($to !== NULL) {
      $dateTo = CRM_Utils_Date::setDateDefaults($to);
      if ($dateTo !== NULL &&
        !empty($dateTo[0])
      ) {
        $defaults["{$fieldName}_to"] = $dateTo[0];
      }
    }
  }

  /**
   * @param string $fieldName
   * @param $field
   * @param $defaults
   */
  public static function stringParam($fieldName, &$field, &$defaults) {
    $fieldOP = CRM_Utils_Array::value("{$fieldName}_op", $_GET, 'like');

    switch ($fieldOP) {
      case 'has':
      case 'sw':
      case 'ew':
      case 'nhas':
      case 'like':
      case 'neq':
        $value = self::getTypedValue("{$fieldName}_value", CRM_Utils_Array::value('type', $field));
        if ($value !== NULL) {
          $defaults["{$fieldName}_value"] = $value;
          $defaults["{$fieldName}_op"] = $fieldOP;
        }
        break;

      case 'nll':
      case 'nnll':
        $defaults["{$fieldName}_op"] = $fieldOP;
        break;

      case 'in':
      case 'notin':
      case 'mhas':
        $value = self::getTypedValue("{$fieldName}_value", CRM_Utils_Type::T_STRING);
        if ($value !== NULL) {
          $defaults["{$fieldName}_value"] = explode(",", $value);
          $defaults["{$fieldName}_op"] = $fieldOP;
        }
        break;
    }
  }

  /**
   * @param string $fieldName
   * @param $field
   * @param $defaults
   */
  public static function intParam($fieldName, &$field, &$defaults) {
    $fieldOP = CRM_Utils_Array::value("{$fieldName}_op", $_GET, 'eq');

    switch ($fieldOP) {
      case 'lte':
      case 'gte':
      case 'eq':
      case 'lt':
      case 'gt':
      case 'neq':
        $value = self::getTypedValue("{$fieldName}_value", $field['type']);
        if ($value !== NULL) {
          $defaults["{$fieldName}_value"] = $value;
          $defaults["{$fieldName}_op"] = $fieldOP;
        }
        break;

      case 'bw':
      case 'nbw':
        $minValue = self::getTypedValue("{$fieldName}_min", $field['type']);
        $maxValue = self::getTypedValue("{$fieldName}_max", $field['type']);
        if ($minValue !== NULL ||
          $maxValue !== NULL
        ) {
          if ($minValue !== NULL) {
            $defaults["{$fieldName}_min"] = $minValue;
          }
          if ($maxValue !== NULL) {
            $defaults["{$fieldName}_max"] = $maxValue;
          }
          $defaults["{$fieldName}_op"] = $fieldOP;
        }
        break;

      case 'in':
      case 'notin':
        // send the type as string so that multiple values can also be retrieved from url.
        // for e.g url like - "memtype_in=in&memtype_value=1,2,3"
        $value = self::getTypedValue("{$fieldName}_value", CRM_Utils_Type::T_STRING);
        if (!preg_match('/^(\d+)(,\d+){0,14}$/', $value)) {
          // extra check. Also put a limit of 15 max values.
          $value = NULL;
        }
        if ($value !== NULL) {
          $defaults["{$fieldName}_value"] = explode(",", $value);
          $defaults["{$fieldName}_op"] = $fieldOP;
        }
        break;
    }
  }

  /**
   * @param $defaults
   */
  public static function processChart(&$defaults) {
    $chartType = CRM_Utils_Array::value("charts", $_GET);
    if (in_array($chartType, array(
      'barChart',
      'pieChart',
    ))) {
      $defaults["charts"] = $chartType;
    }
  }

  /**
   * @param $fieldGrp
   * @param $defaults
   */
  public static function processFilter(&$fieldGrp, &$defaults) {
    // process only filters for now
    foreach ($fieldGrp as $tableName => $fields) {
      foreach ($fields as $fieldName => $field) {
        switch (CRM_Utils_Array::value('type', $field)) {
          case CRM_Utils_Type::T_INT:
          case CRM_Utils_Type::T_FLOAT:
          case CRM_Utils_Type::T_MONEY:
            self::intParam($fieldName, $field, $defaults);
            break;

          case CRM_Utils_Type::T_DATE:
          case CRM_Utils_Type::T_DATE | CRM_Utils_Type::T_TIME:
            self::dateParam($fieldName, $field, $defaults);
            break;

          case CRM_Utils_Type::T_STRING:
          default:
            self::stringParam($fieldName, $field, $defaults);
            break;
        }
      }
    }
  }

  /**
   * unset default filters.
   * @param $defaults
   */
  public static function unsetFilters(&$defaults) {
    static $unsetFlag = TRUE;
    if ($unsetFlag) {
      foreach ($defaults as $field_name => $field_value) {
        $newstr = substr($field_name, strrpos($field_name, '_'));
        if ($newstr == '_value' || $newstr == '_op' ||
          $newstr == '_min' || $newstr == '_max' ||
          $newstr == '_from' || $newstr == '_to' ||
          $newstr == '_relative'
        ) {
          unset($defaults[$field_name]);
        }
      }
      $unsetFlag = FALSE;
    }
  }

  /**
   * @param $fieldGrp
   * @param $defaults
   */
  public static function processGroupBy(&$fieldGrp, &$defaults) {
    // process only group_bys for now
    $flag = FALSE;

    if (is_array($fieldGrp)) {
      foreach ($fieldGrp as $tableName => $fields) {
        if ($groupBys = CRM_Utils_Array::value("gby", $_GET)) {
          $groupBys = explode(' ', $groupBys);
          if (!empty($groupBys)) {
            if (!$flag) {
              unset($defaults['group_bys']);
              $flag = TRUE;
            }
            foreach ($groupBys as $gby) {
              if (array_key_exists($gby, $fields)) {
                $defaults['group_bys'][$gby] = 1;
              }
            }
          }
        }
      }
    }
  }

  /**
   * @param $reportFields
   * @param $defaults
   */
  public static function processFields(&$reportFields, &$defaults) {
    //add filters from url
    if (is_array($reportFields)) {
      if ($urlFields = CRM_Utils_Array::value("fld", $_GET)) {
        $urlFields = explode(',', $urlFields);
      }
      if (CRM_Utils_Array::value("ufld", $_GET) == 1) {
        // unset all display columns
        $defaults['fields'] = array();
      }
      if (!empty($urlFields)) {
        foreach ($reportFields as $tableName => $fields) {
          foreach ($urlFields as $fld) {
            if (array_key_exists($fld, $fields)) {
              $defaults['fields'][$fld] = 1;
            }
          }
        }
      }
    }
  }

}
