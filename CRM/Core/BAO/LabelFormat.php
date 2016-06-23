<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class contains functions for managing Label Formats.
 */
class CRM_Core_BAO_LabelFormat extends CRM_Core_DAO_OptionValue {

  /**
   * Static holder for the Label Formats Option Group ID.
   */
  private static $_gid = NULL;

  /**
   * Label Format fields stored in the 'value' field of the Option Value table.
   */
  private static $optionValueFields = array(
    'paper-size' => array(
      // Paper size: names defined in option_value table (option_group = 'paper_size')
      'name' => 'paper-size',
      'type' => CRM_Utils_Type::T_STRING,
      'default' => 'letter',
    ),
    'orientation' => array(
      // Paper orientation: 'portrait' or 'landscape'
      'name' => 'orientation',
      'type' => CRM_Utils_Type::T_STRING,
      'default' => 'portrait',
    ),
    'font-name' => array(
      // Font name: 'courier', 'helvetica', 'times'
      'name' => 'font-name',
      'type' => CRM_Utils_Type::T_STRING,
      'default' => 'helvetica',
    ),
    'font-size' => array(
      // Font size: always in points
      'name' => 'font-size',
      'type' => CRM_Utils_Type::T_INT,
      'default' => 8,
    ),
    'font-style' => array(
      // Font style: 'B' bold, 'I' italic, 'BI' bold+italic
      'name' => 'font-style',
      'type' => CRM_Utils_Type::T_STRING,
      'default' => '',
    ),
    'NX' => array(
      // Number of labels horizontally
      'name' => 'NX',
      'type' => CRM_Utils_Type::T_INT,
      'default' => 3,
    ),
    'NY' => array(
      // Number of labels vertically
      'name' => 'NY',
      'type' => CRM_Utils_Type::T_INT,
      'default' => 10,
    ),
    'metric' => array(
      // Unit of measurement for all of the following fields
      'name' => 'metric',
      'type' => CRM_Utils_Type::T_STRING,
      'default' => 'mm',
    ),
    'lMargin' => array(
      // Left margin
      'name' => 'lMargin',
      'type' => CRM_Utils_Type::T_FLOAT,
      'metric' => TRUE,
      'default' => 4.7625,
    ),
    'tMargin' => array(
      // Right margin
      'name' => 'tMargin',
      'type' => CRM_Utils_Type::T_FLOAT,
      'metric' => TRUE,
      'default' => 12.7,
    ),
    'SpaceX' => array(
      // Horizontal space between two labels
      'name' => 'SpaceX',
      'type' => CRM_Utils_Type::T_FLOAT,
      'metric' => TRUE,
      'default' => 3.96875,
    ),
    'SpaceY' => array(
      // Vertical space between two labels
      'name' => 'SpaceY',
      'type' => CRM_Utils_Type::T_FLOAT,
      'metric' => TRUE,
      'default' => 0,
    ),
    'width' => array(
      // Width of label
      'name' => 'width',
      'type' => CRM_Utils_Type::T_FLOAT,
      'metric' => TRUE,
      'default' => 65.875,
    ),
    'height' => array(
      // Height of label
      'name' => 'height',
      'type' => CRM_Utils_Type::T_FLOAT,
      'metric' => TRUE,
      'default' => 25.4,
    ),
    'lPadding' => array(
      // Space between text and left edge of label
      'name' => 'lPadding',
      'type' => CRM_Utils_Type::T_FLOAT,
      'metric' => TRUE,
      'default' => 5.08,
    ),
    'tPadding' => array(
      // Space between text and top edge of label
      'name' => 'tPadding',
      'type' => CRM_Utils_Type::T_FLOAT,
      'metric' => TRUE,
      'default' => 5.08,
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
   * Get font names supported by the TCPDF package used to create PDF labels.
   *
   * @param string $name
   *   Group name.
   *
   * @return array
   *   array of font names
   */
  public static function getFontNames($name = 'label_format') {
    $label = new CRM_Utils_PDF_Label(self::getDefaultValues($name));
    return $label->getFontNames();
  }

  /**
   * Get font sizes supported by the TCPDF package used to create PDF labels.
   *
   * @return array
   *   array of font sizes
   */
  public static function getFontSizes() {
    $fontSizes = array();
    for ($i = 6; $i <= 60; $i++) {
      $fontSizes[$i] = ts('%1 pt', array(1 => $i));
    }

    return $fontSizes;
  }

  /**
   * Get measurement units recognized by the TCPDF package used to create PDF labels.
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
   * Get text alignment recognized by the TCPDF package used to create PDF labels.
   *
   * @return array
   *   array of alignments
   */
  public static function getTextAlignments() {
    return array(
      'R' => ts('Right'),
      'L' => ts('Left'),
      'C' => ts('Center'),
    );
  }

  /**
   * Get text alignment recognized by the TCPDF package used to create PDF labels.
   *
   * @return array
   *   array of alignments
   */
  public static function getFontStyles() {
    return array(
      '' => ts('Normal'),
      'B' => ts('Bold'),
      'I' => ts('Italic'),
    );
  }

  /**
   * Get Option Group ID for Label Formats.
   *
   * @param string $name
   *
   * @return int
   *   Group ID (null if Group ID doesn't exist)
   */
  private static function _getGid($name = 'label_format') {
    if (!isset(self::$_gid[$name]) || !self::$_gid[$name]) {
      self::$_gid[$name] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $name, 'id', 'name');
      if (!self::$_gid[$name]) {
        CRM_Core_Error::fatal(ts('Label Format Option Group not found in database.'));
      }
    }
    return self::$_gid[$name];
  }

  /**
   * Add ordering fields to Label Format list.
   *
   * @param array (reference) $list List of Label Formats
   * @param string $returnURL
   *   URL of page calling this function.
   *
   * @return array
   *   (reference)   List of Label Formats
   */
  public static function addOrder(&$list, $returnURL) {
    $filter = "option_group_id = " . self::_getGid();
    CRM_Utils_Weight::addOrder($list, 'CRM_Core_DAO_OptionValue', 'id', $returnURL, $filter);
    return $list;
  }

  /**
   * Retrieve list of Label Formats.
   *
   * @param bool $namesOnly
   *   Return simple list of names.
   * @param string $groupName
   *   Group name of the label format option group.
   *
   * @return array
   *   (reference)   label format list
   */
  public static function &getList($namesOnly = FALSE, $groupName = 'label_format') {
    static $list = array();
    if (self::_getGid($groupName)) {
      // get saved label formats from Option Value table
      $dao = new CRM_Core_DAO_OptionValue();
      $dao->option_group_id = self::_getGid($groupName);
      $dao->is_active = 1;
      $dao->orderBy('weight');
      $dao->find();
      while ($dao->fetch()) {
        if ($namesOnly) {
          $list[$groupName][$dao->name] = $dao->label;
        }
        else {
          CRM_Core_DAO::storeValues($dao, $list[$groupName][$dao->id]);
        }
      }
    }
    return $list[$groupName];
  }

  /**
   * Retrieve the default Label Format values.
   *
   * @param string $groupName
   *   Label format group name.
   *
   * @return array
   *   Name/value pairs containing the default Label Format values.
   */
  public static function &getDefaultValues($groupName = 'label_format') {
    $params = array('is_active' => 1, 'is_default' => 1);
    $defaults = array();
    if (!self::retrieve($params, $defaults, $groupName)) {
      foreach (self::$optionValueFields as $name => $field) {
        $defaults[$name] = $field['default'];
      }
      $filter = array('option_group_id' => self::_getGid($groupName));
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue', $filter);
    }
    return $defaults;
  }

  /**
   * Get Label Format from the DB.
   *
   * @param string $field
   *   Field name to search by.
   * @param int $val
   *   Field value to search for.
   *
   * @param string $groupName
   *
   * @return array
   *   (reference) associative array of name/value pairs
   */
  public static function &getLabelFormat($field, $val, $groupName = 'label_format') {
    $params = array('is_active' => 1, $field => $val);
    $labelFormat = array();
    if (self::retrieve($params, $labelFormat, $groupName)) {
      return $labelFormat;
    }
    else {
      return self::getDefaultValues($groupName);
    }
  }

  /**
   * Get Label Format by Name.
   *
   * @param int $name
   *   Label format name. Empty = get default label format.
   *
   * @return array
   *   (reference) associative array of name/value pairs
   */
  public static function &getByName($name) {
    return self::getLabelFormat('name', $name);
  }

  /**
   * Get Label Format by ID.
   *
   * @param int $id
   *   Label format id. 0 = get default label format.
   * @param string $groupName
   *   Group name.
   *
   * @return array
   *   (reference) associative array of name/value pairs
   */
  public static function &getById($id, $groupName = 'label_format') {
    return self::getLabelFormat('id', $id, $groupName);
  }

  /**
   * Get Label Format field from associative array.
   *
   * @param string $field
   *   Name of a label format field.
   * @param array (reference) $values associative array of name/value pairs containing
   *                                           label format field selections
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
   * @param string $groupName
   *
   * @return CRM_Core_DAO_OptionValue
   */
  public static function retrieve(&$params, &$values, $groupName = 'label_format') {
    $optionValue = new CRM_Core_DAO_OptionValue();
    $optionValue->copyValues($params);
    $optionValue->option_group_id = self::_getGid($groupName);
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
   * Return the name of the group for customized labels.
   */
  public static function customGroupName() {
    return ts('Custom');
  }

  /**
   * Save the Label Format in the DB.
   *
   * @param array (reference) $values associative array of name/value pairs
   * @param int $id
   *   Id of the database record (null = new record).
   * @param string $groupName
   *   Group name of the label format.
   */
  public function saveLabelFormat(&$values, $id = NULL, $groupName = 'label_format') {
    // get the Option Group ID for Label Formats (create one if it doesn't exist)
    $group_id = self::_getGid($groupName);

    // clear other default if this is the new default label format
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
      // new record
      $list = self::getList(TRUE, $groupName);
      $cnt = 1;
      while (array_key_exists("custom_$cnt", $list)) {
        $cnt++;
      }
      $values['name'] = "custom_$cnt";
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
    $this->is_active = 1;

    // serialize label format fields into a single string to store in the 'value' column of the Option Value table
    $v = json_decode($this->value, TRUE);
    foreach (self::$optionValueFields as $name => $field) {
      if (!isset($v[$name])) {
        $v[$name] = NULL;
      }
      $v[$name] = self::getValue($name, $values, $v[$name]);
    }
    $this->value = json_encode($v);

    // make sure serialized array will fit in the 'value' column
    $attribute = CRM_Core_DAO::getAttribute('CRM_Core_BAO_LabelFormat', 'value');
    if (strlen($this->value) > $attribute['maxlength']) {
      CRM_Core_Error::fatal(ts('Label Format does not fit in database.'));
    }
    $this->save();

    // fix duplicate weights
    $filter = array('option_group_id' => self::_getGid());
    CRM_Utils_Weight::correctDuplicateWeights('CRM_Core_DAO_OptionValue', $filter);
  }

  /**
   * Delete a Label Format.
   *
   * @param int $id
   *   ID of the label format to be deleted.
   * @param string $groupName
   *   Group name.
   */
  public static function del($id, $groupName) {
    if ($id) {
      $dao = new CRM_Core_DAO_OptionValue();
      $dao->id = $id;
      if ($dao->find(TRUE)) {
        if ($dao->option_group_id == self::_getGid($groupName)) {
          $filter = array('option_group_id' => self::_getGid($groupName));
          CRM_Utils_Weight::delWeight('CRM_Core_DAO_OptionValue', $id, $filter);
          $dao->delete();
          return;
        }
      }
    }
    CRM_Core_Error::fatal(ts('Invalid value passed to delete function.'));
  }

}
