<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';
class CRM_Core_DAO_MessageTemplates extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_msg_template';
  /**
   * static instance to hold the field values
   *
   * @var array
   * @static
   */
  static $_fields = null;
  /**
   * static instance to hold the FK relationships
   *
   * @var string
   * @static
   */
  static $_links = null;
  /**
   * static instance to hold the values that can
   * be imported
   *
   * @var array
   * @static
   */
  static $_import = null;
  /**
   * static instance to hold the values that can
   * be exported
   *
   * @var array
   * @static
   */
  static $_export = null;
  /**
   * static value to see if we should log any modifications to
   * this table in the civicrm_log table
   *
   * @var boolean
   * @static
   */
  static $_log = false;
  /**
   * Message Template ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Descriptive title of message
   *
   * @var string
   */
  public $msg_title;
  /**
   * Subject for email message.
   *
   * @var text
   */
  public $msg_subject;
  /**
   * Text formatted message
   *
   * @var longtext
   */
  public $msg_text;
  /**
   * HTML formatted message
   *
   * @var longtext
   */
  public $msg_html;
  /**
   *
   * @var boolean
   */
  public $is_active;
  /**
   * a pseudo-FK to civicrm_option_value
   *
   * @var int unsigned
   */
  public $workflow_id;
  /**
   * is this the default message template for the workflow referenced by workflow_id?
   *
   * @var boolean
   */
  public $is_default;
  /**
   * is this the reserved message template which we ship for the workflow referenced by workflow_id?
   *
   * @var boolean
   */
  public $is_reserved;
  /**
   * FK to civicrm_option_value containing PDF Page Format.
   *
   * @var int unsigned
   */
  public $pdf_format_id;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_msg_template
   */
  function __construct()
  {
    $this->__table = 'civicrm_msg_template';
    parent::__construct();
  }
  /**
   * return foreign links
   *
   * @access public
   * @return array
   */
  function links()
  {
    if (!(self::$_links)) {
      self::$_links = array(
        'pdf_format_id' => 'civicrm_option_value:id',
      );
    }
    return self::$_links;
  }
  /**
   * returns all the column names of this table
   *
   * @access public
   * @return array
   */
  static function &fields()
  {
    if (!(self::$_fields)) {
      self::$_fields = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
        ) ,
        'msg_title' => array(
          'name' => 'msg_title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Msg Title') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'msg_subject' => array(
          'name' => 'msg_subject',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Msg Subject') ,
        ) ,
        'msg_text' => array(
          'name' => 'msg_text',
          'type' => CRM_Utils_Type::T_LONGTEXT,
          'title' => ts('Msg Text') ,
        ) ,
        'msg_html' => array(
          'name' => 'msg_html',
          'type' => CRM_Utils_Type::T_LONGTEXT,
          'title' => ts('Msg Html') ,
        ) ,
        'is_active' => array(
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Is Active') ,
          'default' => '',
        ) ,
        'workflow_id' => array(
          'name' => 'workflow_id',
          'type' => CRM_Utils_Type::T_INT,
        ) ,
        'is_default' => array(
          'name' => 'is_default',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'default' => '',
        ) ,
        'is_reserved' => array(
          'name' => 'is_reserved',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'pdf_format_id' => array(
          'name' => 'pdf_format_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Core_DAO_OptionValue',
        ) ,
      );
    }
    return self::$_fields;
  }
  /**
   * returns the names of this table
   *
   * @access public
   * @static
   * @return string
   */
  static function getTableName()
  {
    return self::$_tableName;
  }
  /**
   * returns if this table needs to be logged
   *
   * @access public
   * @return boolean
   */
  function getLog()
  {
    return self::$_log;
  }
  /**
   * returns the list of fields that can be imported
   *
   * @access public
   * return array
   * @static
   */
  static function &import($prefix = false)
  {
    if (!(self::$_import)) {
      self::$_import = array();
      $fields = self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['msg_template'] = & $fields[$name];
          } else {
            self::$_import[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_import;
  }
  /**
   * returns the list of fields that can be exported
   *
   * @access public
   * return array
   * @static
   */
  static function &export($prefix = false)
  {
    if (!(self::$_export)) {
      self::$_export = array();
      $fields = self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['msg_template'] = & $fields[$name];
          } else {
            self::$_export[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
