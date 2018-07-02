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
require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';

/**
 * Class CRM_Upgrade_Snapshot_V4p2_Price_DAO_LineItem
 */
class CRM_Upgrade_Snapshot_V4p2_Price_DAO_LineItem extends CRM_Core_DAO {
  /**
   * static instance to hold the table name
   *
   * @var string
   */
  static $_tableName = 'civicrm_line_item';
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
   * Line Item
   *
   * @var int unsigned
   */
  public $id;
  /**
   * table which has the transaction
   *
   * @var string
   */
  public $entity_table;
  /**
   * entry in table
   *
   * @var int unsigned
   */
  public $entity_id;
  /**
   * FK to price_field
   *
   * @var int unsigned
   */
  public $price_field_id;
  /**
   * descriptive label for item - from price_field_value.label
   *
   * @var string
   */
  public $label;
  /**
   * How many items ordered
   *
   * @var int unsigned
   */
  public $qty;
  /**
   * price of each item
   *
   * @var float
   */
  public $unit_price;
  /**
   * qty * unit_price
   *
   * @var float
   */
  public $line_total;
  /**
   * Participant count for field
   *
   * @var int unsigned
   */
  public $participant_count;
  /**
   * Implicit FK to civicrm_option_value
   *
   * @var int unsigned
   */
  public $price_field_value_id;

  /**
   * Class constructor.
   *
   * @return \CRM_Upgrade_Snapshot_V4p2_Price_DAO_LineItem
   */
  public function __construct() {
    $this->__table = 'civicrm_line_item';
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
        'price_field_id' => 'civicrm_price_field:id',
        'price_field_value_id' => 'civicrm_price_field_value:id',
      );
    }
    return self::$_links;
  }

  /**
   * Returns all the column names of this table.
   *
   * @return array
   */
  public static function &fields() {
    if (!(self::$_fields)) {
      self::$_fields = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
        ),
        'entity_table' => array(
          'name' => 'entity_table',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Entity Table'),
          'required' => TRUE,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ),
        'entity_id' => array(
          'name' => 'entity_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
        ),
        'price_field_id' => array(
          'name' => 'price_field_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
          'FKClassName' => 'Snapshot_v4p2_Price_DAO_Field',
        ),
        'label' => array(
          'name' => 'label',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Label'),
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'default' => 'UL',
        ),
        'qty' => array(
          'name' => 'qty',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Qty'),
          'required' => TRUE,
        ),
        'unit_price' => array(
          'name' => 'unit_price',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Unit Price'),
          'required' => TRUE,
        ),
        'line_total' => array(
          'name' => 'line_total',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Line Total'),
          'required' => TRUE,
        ),
        'participant_count' => array(
          'name' => 'participant_count',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Participant Count'),
          'default' => 'UL',
        ),
        'price_field_value_id' => array(
          'name' => 'price_field_value_id',
          'type' => CRM_Utils_Type::T_INT,
          'default' => 'UL',
          'FKClassName' => 'Snapshot_v4p2_Price_DAO_FieldValue',
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
    return self::$_tableName;
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
            self::$_import['line_item'] = &$fields[$name];
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
            self::$_export['line_item'] = &$fields[$name];
          }
          else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
