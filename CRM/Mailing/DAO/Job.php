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
class CRM_Mailing_DAO_Job extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_mailing_job';
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
   *
   * @var int unsigned
   */
  public $id;
  /**
   * The ID of the mailing this Job will send.
   *
   * @var int unsigned
   */
  public $mailing_id;
  /**
   * date on which this job was scheduled.
   *
   * @var datetime
   */
  public $scheduled_date;
  /**
   * date on which this job was started.
   *
   * @var datetime
   */
  public $start_date;
  /**
   * date on which this job ended.
   *
   * @var datetime
   */
  public $end_date;
  /**
   * The state of this job
   *
   * @var enum('Scheduled', 'Running', 'Complete', 'Paused', 'Canceled')
   */
  public $status;
  /**
   * Is this job for a test mail?
   *
   * @var boolean
   */
  public $is_test;
  /**
   * Type of mailling job: null | child
   *
   * @var string
   */
  public $job_type;
  /**
   * Parent job id
   *
   * @var int unsigned
   */
  public $parent_id;
  /**
   * Offset of the child job
   *
   * @var int
   */
  public $job_offset;
  /**
   * Queue size limit for each child job
   *
   * @var int
   */
  public $job_limit;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_mailing_job
   */
  function __construct()
  {
    $this->__table = 'civicrm_mailing_job';
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
        'mailing_id' => 'civicrm_mailing:id',
        'parent_id' => 'civicrm_mailing_job:id',
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
        'mailing_id' => array(
          'name' => 'mailing_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
          'FKClassName' => 'CRM_Mailing_DAO_Mailing',
        ) ,
        'scheduled_date' => array(
          'name' => 'scheduled_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Scheduled Date') ,
        ) ,
        'start_date' => array(
          'name' => 'start_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Start Date') ,
        ) ,
        'end_date' => array(
          'name' => 'end_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('End Date') ,
        ) ,
        'status' => array(
          'name' => 'status',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('Status') ,
          'enumValues' => 'Scheduled, Running, Complete, Paused, Canceled',
        ) ,
        'is_test' => array(
          'name' => 'is_test',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'job_type' => array(
          'name' => 'job_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Job Type') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'parent_id' => array(
          'name' => 'parent_id',
          'type' => CRM_Utils_Type::T_INT,
          'default' => 'UL',
          'FKClassName' => 'CRM_Mailing_DAO_Job',
        ) ,
        'job_offset' => array(
          'name' => 'job_offset',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Job Offset') ,
        ) ,
        'job_limit' => array(
          'name' => 'job_limit',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Job Limit') ,
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
            self::$_import['mailing_job'] = & $fields[$name];
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
            self::$_export['mailing_job'] = & $fields[$name];
          } else {
            self::$_export[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
  /**
   * returns an array containing the enum fields of the civicrm_mailing_job table
   *
   * @return array (reference)  the array of enum fields
   */
  static function &getEnums()
  {
    static $enums = array(
      'status',
    );
    return $enums;
  }
  /**
   * returns a ts()-translated enum value for display purposes
   *
   * @param string $field  the enum field in question
   * @param string $value  the enum value up for translation
   *
   * @return string  the display value of the enum
   */
  static function tsEnum($field, $value)
  {
    static $translations = null;
    if (!$translations) {
      $translations = array(
        'status' => array(
          'Scheduled' => ts('Scheduled') ,
          'Running' => ts('Running') ,
          'Complete' => ts('Complete') ,
          'Paused' => ts('Paused') ,
          'Canceled' => ts('Canceled') ,
        ) ,
      );
    }
    return $translations[$field][$value];
  }
  /**
   * adds $value['foo_display'] for each $value['foo'] enum from civicrm_mailing_job
   *
   * @param array $values (reference)  the array up for enhancing
   * @return void
   */
  static function addDisplayEnums(&$values)
  {
    $enumFields = & CRM_Mailing_DAO_Job::getEnums();
    foreach($enumFields as $enum) {
      if (isset($values[$enum])) {
        $values[$enum . '_display'] = CRM_Mailing_DAO_Job::tsEnum($enum, $values[$enum]);
      }
    }
  }
}
