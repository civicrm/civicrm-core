<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright (C) 2011 Marty Wright                                    |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class contains functions for managing Paper Sizes.
 */
class CRM_Core_BAO_PaperSize extends CRM_Core_DAO_OptionValue {

  /**
   * Static holder for the Paper Size Option Group ID.
   */
  private static $_gid = NULL;

  /**
   * Paper Size fields stored in the 'value' field of the Option Value table.
   */
  private static $optionValueFields = [
    'metric' => [
      'name' => 'metric',
      'type' => CRM_Utils_Type::T_STRING,
      'default' => 'mm',
    ],
    'width' => [
      'name' => 'width',
      'type' => CRM_Utils_Type::T_FLOAT,
      'metric' => TRUE,
      'default' => 612,
    ],
    'height' => [
      'name' => 'height',
      'type' => CRM_Utils_Type::T_FLOAT,
      'metric' => TRUE,
      'default' => 792,
    ],
  ];

  /**
   * Get Option Group ID for Paper Sizes.
   *
   * @return int
   *   Group ID (null if Group ID doesn't exist)
   */
  private static function _getGid() {
    if (!self::$_gid) {
      self::$_gid = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'paper_size', 'id', 'name');
      if (!self::$_gid) {
        CRM_Core_Error::fatal(ts('Paper Size Option Group not found in database.'));
      }
    }
    return self::$_gid;
  }

  /**
   * Add ordering fields to Paper Size list.
   *
   * @param array (reference) $list List of Paper Sizes
   * @param string $returnURL
   *   URL of page calling this function.
   *
   */
  public static function &addOrder(&$list, $returnURL) {
    $filter = "option_group_id = " . self::_getGid();
    CRM_Utils_Weight::addOrder($list, 'CRM_Core_DAO_OptionValue', 'id', $returnURL, $filter);
  }

  /**
   * Retrieve list of Paper Sizes.
   *
   * @param bool $namesOnly
   *   Return simple list of names.
   *
   * @return array
   *   (reference)   Paper Size list
   */
  public static function &getList($namesOnly = FALSE) {
    static $list = [];
    if (self::_getGid()) {
      // get saved Paper Sizes from Option Value table
      $dao = new CRM_Core_DAO_OptionValue();
      $dao->option_group_id = self::_getGid();
      $dao->is_active = 1;
      $dao->orderBy('weight');
      $dao->find();
      while ($dao->fetch()) {
        if ($namesOnly) {
          $list[$dao->name] = $dao->label;
        }
        else {
          CRM_Core_DAO::storeValues($dao, $list[$dao->id]);
        }
      }
    }
    return $list;
  }

  /**
   * Retrieve the default Paper Size values.
   *
   * @return array
   *   Name/value pairs containing the default Paper Size values.
   */
  public static function &getDefaultValues() {
    $params = ['is_active' => 1, 'is_default' => 1];
    $defaults = [];
    if (!self::retrieve($params, $defaults)) {
      foreach (self::$optionValueFields as $name => $field) {
        $defaults[$name] = $field['default'];
      }
      $filter = ['option_group_id' => self::_getGid()];
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue', $filter);
    }
    return $defaults;
  }

  /**
   * Get Paper Size from the DB.
   *
   * @param string $field
   *   Field name to search by.
   * @param int $val
   *   Field value to search for.
   *
   * @return array
   *   (reference) associative array of name/value pairs
   */
  public static function &getPaperFormat($field, $val) {
    $params = ['is_active' => 1, $field => $val];
    $paperFormat = [];
    if (self::retrieve($params, $paperFormat)) {
      return $paperFormat;
    }
    else {
      return self::getDefaultValues();
    }
  }

  /**
   * Get Paper Size by Name.
   *
   * @param int $name
   *   Paper Size name. Empty = get default Paper Size.
   *
   * @return array
   *   (reference) associative array of name/value pairs
   */
  public static function &getByName($name) {
    return self::getPaperFormat('name', $name);
  }

  /**
   * Get Paper Size by ID.
   *
   * @param int $id
   *   Paper Size id. 0 = get default Paper Size.
   *
   * @return array
   *   (reference) associative array of name/value pairs
   */
  public static function &getById($id) {
    return self::getPaperFormat('id', $id);
  }

  /**
   * Get Paper Size field from associative array.
   *
   * @param string $field
   *   Name of a Paper Size field.
   * @param array (reference) $values associative array of name/value pairs containing
   *                                           Paper Size field selections
   *
   * @param null $default
   *
   * @return value
   */
  public static function getValue($field, &$values, $default = NULL) {
    if (array_key_exists($field, self::$optionValueFields)) {
      switch (self::$optionValueFields[$field]['type']) {
        case CRM_Utils_Type::T_INT:
          return (int) CRM_Utils_Array::value($field, $values, $default);

        case CRM_Utils_Type::T_FLOAT:
          // Round float values to three decimal places and trim trailing zeros.
          // Add a leading zero to values less than 1.
          $f = sprintf('%05.3f', $values[$field]);
          $f = rtrim($f, '0');
          $f = rtrim($f, '.');
          return (float) (empty($f) ? '0' : $f);
      }
      return CRM_Utils_Array::value($field, $values, $default);
    }
    return $default;
  }

  /**
   * Retrieve DB object based on input parameters.
   *
   * It also stores all the retrieved values in the default array.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $values
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_DAO_OptionValue
   */
  public static function retrieve(&$params, &$values) {
    $optionValue = new CRM_Core_DAO_OptionValue();
    $optionValue->copyValues($params);
    $optionValue->option_group_id = self::_getGid();
    if ($optionValue->find(TRUE)) {
      // Extract fields that have been serialized in the 'value' column of the Option Value table.
      $values = json_decode($optionValue->value, TRUE);
      // Add any new fields that don't yet exist in the saved values.
      foreach (self::$optionValueFields as $name => $field) {
        if (!isset($values[$name])) {
          $values[$name] = $field['default'];
          if ($field['metric']) {
            $values[$name] = CRM_Utils_PDF_Utils::convertMetric($field['default'],
              self::$optionValueFields['metric']['default'],
              $values['metric'], 3
            );
          }
        }
      }
      // Add fields from the OptionValue base class
      CRM_Core_DAO::storeValues($optionValue, $values);
      return $optionValue;
    }
    return NULL;
  }

  /**
   * Save the Paper Size in the DB.
   *
   * @param array (reference) $values associative array of name/value pairs
   * @param int $id
   *   Id of the database record (null = new record).
   */
  public function savePaperSize(&$values, $id) {
    // get the Option Group ID for Paper Sizes (create one if it doesn't exist)
    $group_id = self::_getGid(TRUE);

    // clear other default if this is the new default Paper Size
    if ($values['is_default']) {
      $query = "UPDATE civicrm_option_value SET is_default = 0 WHERE option_group_id = $group_id";
      CRM_Core_DAO::executeQuery($query);
    }
    if ($id) {
      // fetch existing record
      $this->id = $id;
      if ($this->find()) {
        $this->fetch();
      }
    }
    else {
      // new record: set group = custom
      $values['grouping'] = self::customGroupName();
    }
    // copy the supplied form values to the corresponding Option Value fields in the base class
    foreach ($this->fields() as $name => $field) {
      $this->$name = trim(CRM_Utils_Array::value($name, $values, $this->$name));
      if (empty($this->$name)) {
        $this->$name = 'null';
      }
    }
    $this->id = $id;
    $this->option_group_id = $group_id;
    $this->label = $this->name;
    $this->is_active = 1;

    // serialize Paper Size fields into a single string to store in the 'value' column of the Option Value table
    $v = json_decode($this->value, TRUE);
    foreach (self::$optionValueFields as $name => $field) {
      $v[$name] = self::getValue($name, $values, $v[$name]);
    }
    $this->value = json_encode($v);

    // make sure serialized array will fit in the 'value' column
    $attribute = CRM_Core_DAO::getAttribute('CRM_Core_BAO_PaperSize', 'value');
    if (strlen($this->value) > $attribute['maxlength']) {
      CRM_Core_Error::fatal(ts('Paper Size does not fit in database.'));
    }
    $this->save();

    // fix duplicate weights
    $filter = ['option_group_id' => self::_getGid()];
    CRM_Utils_Weight::correctDuplicateWeights('CRM_Core_DAO_OptionValue', $filter);
  }

  /**
   * Delete a Paper Size.
   *
   * @param int $id
   *   ID of the Paper Size to be deleted.
   *
   */
  public static function del($id) {
    if ($id) {
      $dao = new CRM_Core_DAO_OptionValue();
      $dao->id = $id;
      if ($dao->find(TRUE)) {
        if ($dao->option_group_id == self::_getGid()) {
          $filter = ['option_group_id' => self::_getGid()];
          CRM_Utils_Weight::delWeight('CRM_Core_DAO_OptionValue', $id, $filter);
          $dao->delete();
          return;
        }
      }
    }
    CRM_Core_Error::fatal(ts('Invalid value passed to delete function.'));
  }

}
