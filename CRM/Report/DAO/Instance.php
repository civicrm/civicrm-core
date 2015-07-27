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
class CRM_Report_DAO_Instance extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_report_instance';
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
   * Report Instance ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Which Domain is this instance for
   *
   * @var int unsigned
   */
  public $domain_id;
  /**
   * Report Instance Title.
   *
   * @var string
   */
  public $title;
  /**
   * FK to civicrm_option_value for the report template
   *
   * @var string
   */
  public $report_id;
  /**
   * when combined with report_id/template uniquely identifies the instance
   *
   * @var string
   */
  public $name;
  /**
   * arguments that are passed in the url when invoking the instance
   *
   * @var string
   */
  public $args;
  /**
   * Report Instance description.
   *
   * @var string
   */
  public $description;
  /**
   * permission required to be able to run this instance
   *
   * @var string
   */
  public $permission;
  /**
   * role required to be able to run this instance
   *
   * @var string
   */
  public $grouprole;
  /**
   * Submitted form values for this report
   *
   * @var text
   */
  public $form_values;
  /**
   * Is this entry active?
   *
   * @var boolean
   */
  public $is_active;
  /**
   * Subject of email
   *
   * @var string
   */
  public $email_subject;
  /**
   * comma-separated list of email addresses to send the report to
   *
   * @var text
   */
  public $email_to;
  /**
   * comma-separated list of email addresses to send the report to
   *
   * @var text
   */
  public $email_cc;
  /**
   * comma-separated list of email addresses to send the report to
   *
   * @var text
   */
  public $header;
  /**
   * comma-separated list of email addresses to send the report to
   *
   * @var text
   */
  public $footer;
  /**
   * FK to navigation ID
   *
   * @var int unsigned
   */
  public $navigation_id;
  /**
   * FK to instance ID drilldown to
   *
   * @var int unsigned
   */
  public $drilldown_id;
  /**
   *
   * @var boolean
   */
  public $is_reserved;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_report_instance
   */
  function __construct()
  {
    $this->__table = 'civicrm_report_instance';
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
        'domain_id' => 'civicrm_domain:id',
        'navigation_id' => 'civicrm_navigation:id',
        'drilldown_id' => 'civicrm_report_instance:id',
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
        'domain_id' => array(
          'name' => 'domain_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
          'FKClassName' => 'CRM_Core_DAO_Domain',
        ) ,
        'title' => array(
          'name' => 'title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Report Instance Title') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'report_id' => array(
          'name' => 'report_id',
          'type' => CRM_Utils_Type::T_STRING,
          'required' => true,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'name' => array(
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Name') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'args' => array(
          'name' => 'args',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Args') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'description' => array(
          'name' => 'description',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Description') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'permission' => array(
          'name' => 'permission',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Permission') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'grouprole' => array(
          'name' => 'grouprole',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Grouprole') ,
          'maxlength' => 1024,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'form_values' => array(
          'name' => 'form_values',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Submitted Form Values') ,
          'import' => true,
          'where' => 'civicrm_report_instance.form_values',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'is_active' => array(
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'email_subject' => array(
          'name' => 'email_subject',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Email Subject') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'email_to' => array(
          'name' => 'email_to',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Email To') ,
        ) ,
        'email_cc' => array(
          'name' => 'email_cc',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Email Cc') ,
        ) ,
        'header' => array(
          'name' => 'header',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Header') ,
          'rows' => 4,
          'cols' => 60,
        ) ,
        'footer' => array(
          'name' => 'footer',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Footer') ,
          'rows' => 4,
          'cols' => 60,
        ) ,
        'navigation_id' => array(
          'name' => 'navigation_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Navigation ID') ,
          'import' => true,
          'where' => 'civicrm_report_instance.navigation_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
          'FKClassName' => 'CRM_Core_DAO_Navigation',
        ) ,
        'drilldown_id' => array(
          'name' => 'drilldown_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Drilldown Report ID') ,
          'import' => true,
          'where' => 'civicrm_report_instance.drilldown_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
          'FKClassName' => 'CRM_Report_DAO_Instance',
        ) ,
        'is_reserved' => array(
          'name' => 'is_reserved',
          'type' => CRM_Utils_Type::T_BOOLEAN,
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
            self::$_import['report_instance'] = & $fields[$name];
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
            self::$_export['report_instance'] = & $fields[$name];
          } else {
            self::$_export[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
