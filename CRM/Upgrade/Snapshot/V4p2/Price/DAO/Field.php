<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';

/**
 * Class CRM_Upgrade_Snapshot_V4p2_Price_DAO_Field
 */
class CRM_Upgrade_Snapshot_V4p2_Price_DAO_Field extends CRM_Core_DAO {
  /**
   * static instance to hold the table name
   *
   * @var string
   */
  static $_tableName = 'civicrm_price_field';
  /**
   * static instance to hold the field values
   *
   * @var array
   */
  static $_fields = NULL;
  /**
   * static instance to hold the FK relationships
   *
   * @var string
   */
  static $_links = NULL;
  /**
   * static instance to hold the values that can
   * be imported
   *
   * @var array
   */
  static $_import = NULL;
  /**
   * static instance to hold the values that can
   * be exported
   *
   * @var array
   */
  static $_export = NULL;
  /**
   * static value to see if we should log any modifications to
   * this table in the civicrm_log table
   *
   * @var boolean
   */
  static $_log = TRUE;
  /**
   * Price Field
   *
   * @var int unsigned
   */
  public $id;
  /**
   * FK to civicrm_price_set
   *
   * @var int unsigned
   */
  public $price_set_id;
  /**
   * Variable name/programmatic handle for this field.
   *
   * @var string
   */
  public $name;
  /**
   * Text for form field label (also friendly name for administering this field).
   *
   * @var string
   */
  public $label;
  /**
   *
   * @var enum('Text', 'Select', 'Radio', 'CheckBox')
   */
  public $html_type;
  /**
   * Enter a quantity for this field?
   *
   * @var boolean
   */
  public $is_enter_qty;
  /**
   * Description and/or help text to display before this field.
   *
   * @var text
   */
  public $help_pre;
  /**
   * Description and/or help text to display after this field.
   *
   * @var text
   */
  public $help_post;
  /**
   * Order in which the fields should appear
   *
   * @var int
   */
  public $weight;
  /**
   * Should the price be displayed next to the label for each option?
   *
   * @var boolean
   */
  public $is_display_amounts;
  /**
   * number of options per line for checkbox and radio
   *
   * @var int unsigned
   */
  public $options_per_line;
  /**
   * Is this price field active
   *
   * @var boolean
   */
  public $is_active;
  /**
   * Is this price field required (value must be > 1)
   *
   * @var boolean
   */
  public $is_required;
  /**
   * If non-zero, do not show this field before the date specified
   *
   * @var datetime
   */
  public $active_on;
  /**
   * If non-zero, do not show this field after the date specified
   *
   * @var datetime
   */
  public $expire_on;
  /**
   * Optional scripting attributes for field
   *
   * @var string
   */
  public $javascript;
  /**
   * Implicit FK to civicrm_option_group with name = \'visibility\'
   *
   * @var int unsigned
   */
  public $visibility_id;

  /**
   * Class constructor.
   *
   * @return \CRM_Upgrade_Snapshot_V4p2_Price_DAO_Field
   */
  public function __construct() {
    $this->__table = 'civicrm_price_field';
    parent::__construct();
  }

  /**
   * Return foreign links.
   *
   * @return array
   */
  public function links() {
    if (!(self::$_links)) {
      self::$_links = array(
        'price_set_id' => 'civicrm_price_set:id',
      );
    }
    return self::$_links;
  }

  /**
   * Returns all the column names of this table.
   *
   * @return array
   */
  static function &fields() {
    if (!(self::$_fields)) {
      self::$_fields = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
        ),
        'price_set_id' => array(
          'name' => 'price_set_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
          'FKClassName' => 'Snapshot_v4p2_Price_DAO_Set',
        ),
        'name' => array(
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Name'),
          'required' => TRUE,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ),
        'label' => array(
          'name' => 'label',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Label'),
          'required' => TRUE,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ),
        'html_type' => array(
          'name' => 'html_type',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('Html Type'),
          'required' => TRUE,
          'enumValues' => 'Text, Select, Radio, CheckBox',
        ),
        'is_enter_qty' => array(
          'name' => 'is_enter_qty',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ),
        'help_pre' => array(
          'name' => 'help_pre',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Help Pre'),
          'rows' => 4,
          'cols' => 80,
        ),
        'help_post' => array(
          'name' => 'help_post',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Help Post'),
          'rows' => 4,
          'cols' => 80,
        ),
        'weight' => array(
          'name' => 'weight',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Weight'),
          'default' => '',
        ),
        'is_display_amounts' => array(
          'name' => 'is_display_amounts',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'default' => '',
        ),
        'options_per_line' => array(
          'name' => 'options_per_line',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Options Per Line'),
          'default' => '',
        ),
        'is_active' => array(
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'default' => '',
        ),
        'is_required' => array(
          'name' => 'is_required',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'default' => '',
        ),
        'active_on' => array(
          'name' => 'active_on',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Active On'),
          'default' => 'UL',
        ),
        'expire_on' => array(
          'name' => 'expire_on',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Expire On'),
          'default' => 'UL',
        ),
        'javascript' => array(
          'name' => 'javascript',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Javascript'),
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ),
        'visibility_id' => array(
          'name' => 'visibility_id',
          'type' => CRM_Utils_Type::T_INT,
          'default' => '',
        ),
      );
    }
    return self::$_fields;
  }

  /**
   * returns the names of this table.
   *
   * @return string
   */
  public static function getTableName() {
    return CRM_Core_DAO::getLocaleTableName(self::$_tableName);
  }

  /**
   * returns if this table needs to be logged.
   *
   * @return boolean
   */
  public function getLog() {
    return self::$_log;
  }

  /**
   * returns the list of fields that can be imported.
   *
   * @param bool $prefix
   *
   * @return array
   */
  static function &import($prefix = FALSE) {
    if (!(self::$_import)) {
      self::$_import = array();
      $fields = self::fields();
      foreach ($fields as $name => $field) {
        if (!empty($field['import'])) {
          if ($prefix) {
            self::$_import['price_field'] = &$fields[$name];
          }
          else {
            self::$_import[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_import;
  }

  /**
   * Returns the list of fields that can be exported.
   *
   * @param bool $prefix
   *
   * @return array
   */
  static function &export($prefix = FALSE) {
    if (!(self::$_export)) {
      self::$_export = array();
      $fields = self::fields();
      foreach ($fields as $name => $field) {
        if (!empty($field['export'])) {
          if ($prefix) {
            self::$_export['price_field'] = &$fields[$name];
          }
          else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }

  /**
   * returns an array containing the enum fields of the civicrm_price_field table.
   *
   * @return array
   *   (reference)  the array of enum fields
   */
  static function &getEnums() {
    static $enums = array(
      'html_type',
    );
    return $enums;
  }

  /**
   * returns a ts()-translated enum value for display purposes
   *
   * @param string $field
   *   The enum field in question.
   * @param string $value
   *   The enum value up for translation.
   *
   * @return string
   *   the display value of the enum
   */
  public static function tsEnum($field, $value) {
    static $translations = NULL;
    if (!$translations) {
      $translations = array(
        'html_type' => array(
          'Text' => ts('Text'),
          'Select' => ts('Select'),
          'Radio' => ts('Radio'),
          'CheckBox' => ts('CheckBox'),
        ),
      );
    }
    return $translations[$field][$value];
  }

  /**
   * adds $value['foo_display'] for each $value['foo'] enum from civicrm_price_field
   *
   * @param array $values
   *   (reference) the array up for enhancing.
   */
  public static function addDisplayEnums(&$values) {
    $enumFields = &Snapshot_v4p2_Price_DAO_Field::getEnums();
    foreach ($enumFields as $enum) {
      if (isset($values[$enum])) {
        $values[$enum . '_display'] = Snapshot_v4p2_Price_DAO_Field::tsEnum($enum, $values[$enum]);
      }
    }
  }
}
