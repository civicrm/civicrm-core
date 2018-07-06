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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class contains functions for managing PDF Page Formats.
 */
class CRM_Core_BAO_PdfFormat extends CRM_Core_DAO_OptionValue {

  /**
   * Static holder for the PDF Page Formats Option Group ID.
   */
  private static $_gid = NULL;

  /**
   * PDF Page Format fields stored in the 'value' field of the Option Value table.
   */
  private static $optionValueFields = array(
    'paper_size' => array(
      'name' => 'paper_size',
      'type' => CRM_Utils_Type::T_STRING,
      'default' => 'letter',
    ),
    'stationery' => array(
      'name' => 'stationery',
      'type' => CRM_Utils_Type::T_STRING,
      'default' => '',
    ),
    'orientation' => array(
      'name' => 'orientation',
      'type' => CRM_Utils_Type::T_STRING,
      'default' => 'portrait',
    ),
    'metric' => array(
      'name' => 'metric',
      'type' => CRM_Utils_Type::T_STRING,
      'default' => 'in',
    ),
    'margin_top' => array(
      'name' => 'margin_top',
      'type' => CRM_Utils_Type::T_FLOAT,
      'metric' => TRUE,
      'default' => 0.75,
    ),
    'margin_bottom' => array(
      'name' => 'margin_bottom',
      'type' => CRM_Utils_Type::T_FLOAT,
      'metric' => TRUE,
      'default' => 0.75,
    ),
    'margin_left' => array(
      'name' => 'margin_left',
      'type' => CRM_Utils_Type::T_FLOAT,
      'metric' => TRUE,
      'default' => 0.75,
    ),
    'margin_right' => array(
      'name' => 'margin_right',
      'type' => CRM_Utils_Type::T_FLOAT,
      'metric' => TRUE,
      'default' => 0.75,
    ),
  );

  /**
   * Get page orientations recognized by the DOMPDF package used to create PDF letters.
   *
   * @return array
   *   array of page orientations
   */
  public static function getPageOrientations() {
    return array(
      'portrait' => ts('Portrait'),
      'landscape' => ts('Landscape'),
    );
  }

  /**
   * Get measurement units recognized by the DOMPDF package used to create PDF letters.
   *
   * @return array
   *   array of measurement units
   */
  public static function getUnits() {
    return array(
      'in' => ts('Inches'),
      'cm' => ts('Centimeters'),
      'mm' => ts('Millimeters'),
      'pt' => ts('Points'),
    );
  }

  /**
   * Get Option Group ID for PDF Page Formats.
   *
   * @return int
   *   Group ID (null if Group ID doesn't exist)
   */
  private static function _getGid() {
    if (!self::$_gid) {
      self::$_gid = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'pdf_format', 'id', 'name');
      if (!self::$_gid) {
        CRM_Core_Error::fatal(ts('PDF Format Option Group not found in database.'));
      }
    }
    return self::$_gid;
  }

  /**
   * Add ordering fields to Page Format list.
   *
   * @param array (reference) $list List of PDF Page Formats
   * @param string $returnURL
   *   URL of page calling this function.
   */
  public static function addOrder(&$list, $returnURL) {
    $filter = "option_group_id = " . self::_getGid();
    CRM_Utils_Weight::addOrder($list, 'CRM_Core_DAO_OptionValue', 'id', $returnURL, $filter);
  }

  /**
   * Get list of PDF Page Formats.
   *
   * @param bool $namesOnly
   *   Return simple list of names.
   *
   * @return array
   *   (reference)   PDF Page Format list
   */
  public static function &getList($namesOnly = FALSE) {
    static $list = array();
    if (self::_getGid()) {
      // get saved PDF Page Formats from Option Value table
      $dao = new CRM_Core_DAO_OptionValue();
      $dao->option_group_id = self::_getGid();
      $dao->is_active = 1;
      $dao->orderBy('weight');
      $dao->find();
      while ($dao->fetch()) {
        if ($namesOnly) {
          $list[$dao->id] = $dao->name;
        }
        else {
          CRM_Core_DAO::storeValues($dao, $list[$dao->id]);
        }
      }
    }
    return $list;
  }

  /**
   * Get the default PDF Page Format values.
   *
   * @return array
   *   Name/value pairs containing the default PDF Page Format values.
   */
  public static function &getDefaultValues() {
    $params = array('is_active' => 1, 'is_default' => 1);
    $defaults = array();
    if (!self::retrieve($params, $defaults)) {
      foreach (self::$optionValueFields as $name => $field) {
        $defaults[$name] = $field['default'];
      }
      $filter = array('option_group_id' => self::_getGid());
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue', $filter);

      // also set the id to avoid NOTICES, CRM-8454
      $defaults['id'] = NULL;
    }
    return $defaults;
  }

  /**
   * Get PDF Page Format from the DB.
   *
   * @param string $field
   *   Field name to search by.
   * @param int $val
   *   Field value to search for.
   *
   * @return array
   *   (reference) associative array of name/value pairs
   */
  public static function &getPdfFormat($field, $val) {
    $params = array('is_active' => 1, $field => $val);
    $pdfFormat = array();
    if (self::retrieve($params, $pdfFormat)) {
      return $pdfFormat;
    }
    else {
      return self::getDefaultValues();
    }
  }

  /**
   * Get PDF Page Format by Name.
   *
   * @param int $name
   *   PDF Page Format name. Empty = get default PDF Page Format.
   *
   * @return array
   *   (reference) associative array of name/value pairs
   */
  public static function &getByName($name) {
    return self::getPdfFormat('name', $name);
  }

  /**
   * Get PDF Page Format by ID.
   *
   * @param int $id
   *   PDF Page Format id. 0 = get default PDF Page Format.
   *
   * @return array
   *   (reference) associative array of name/value pairs
   */
  public static function &getById($id) {
    return self::getPdfFormat('id', $id);
  }

  /**
   * Get PDF Page Format field from associative array.
   *
   * @param string $field
   *   Name of a PDF Page Format field.
   * @param array (reference) $values associative array of name/value pairs containing
   *                                           PDF Page Format field selections
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
          if (!empty($field['metric'])) {
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
   * Save the PDF Page Format in the DB.
   *
   * @param array $values associative array of name/value pairs
   * @param int $id
   *   Id of the database record (null = new record).
   */
  public function savePdfFormat(&$values, $id = NULL) {
    // get the Option Group ID for PDF Page Formats (create one if it doesn't exist)
    $group_id = self::_getGid();

    // clear other default if this is the new default PDF Page Format
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

    // serialize PDF Page Format fields into a single string to store in the 'value' column of the Option Value table
    $v = json_decode($this->value, TRUE);
    foreach (self::$optionValueFields as $name => $field) {
      $v[$name] = self::getValue($name, $values, CRM_Utils_Array::value($name, $v));
    }
    $this->value = json_encode($v);

    // make sure serialized array will fit in the 'value' column
    $attribute = CRM_Core_DAO::getAttribute('CRM_Core_BAO_PdfFormat', 'value');
    if (strlen($this->value) > $attribute['maxlength']) {
      CRM_Core_Error::fatal(ts('PDF Page Format does not fit in database.'));
    }
    $this->save();

    // fix duplicate weights
    $filter = array('option_group_id' => self::_getGid());
    CRM_Utils_Weight::correctDuplicateWeights('CRM_Core_DAO_OptionValue', $filter);
  }

  /**
   * Delete a PDF Page Format.
   *
   * @param int $id
   *   ID of the PDF Page Format to be deleted.
   *
   */
  public static function del($id) {
    if ($id) {
      $dao = new CRM_Core_DAO_OptionValue();
      $dao->id = $id;
      if ($dao->find(TRUE)) {
        if ($dao->option_group_id == self::_getGid()) {
          $filter = array('option_group_id' => self::_getGid());
          CRM_Utils_Weight::delWeight('CRM_Core_DAO_OptionValue', $id, $filter);
          $dao->delete();
          return;
        }
      }
    }
    CRM_Core_Error::fatal(ts('Invalid value passed to delete function.'));
  }

}
