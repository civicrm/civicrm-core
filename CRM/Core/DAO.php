<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.5                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * Our base DAO class. All DAO classes should inherit from this class.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

require_once 'PEAR.php';
require_once 'DB/DataObject.php';

require_once 'CRM/Core/I18n.php';

/**
 * Class CRM_Core_DAO
 */
class CRM_Core_DAO extends DB_DataObject {

  /**
   * a null object so we can pass it as reference if / when needed
   */
  static $_nullObject = NULL;
  static $_nullArray = array();

  static $_dbColumnValueCache = NULL;
  CONST NOT_NULL = 1, IS_NULL = 2,
  DB_DAO_NOTNULL = 128,
  VALUE_SEPARATOR = "",
  BULK_INSERT_COUNT = 200,
  BULK_INSERT_HIGH_COUNT = 200,
  // special value for mail bulk inserts to avoid
  // potential duplication, assuming a smaller number reduces number of queries
  // by some factor, so some tradeoff. CRM-8678
  BULK_MAIL_INSERT_COUNT = 10,
  QUERY_FORMAT_WILDCARD = 1,
  QUERY_FORMAT_NO_QUOTES = 2;

  /*
   * Define entities that shouldn't be created or deleted when creating/ deleting
   *  test objects - this prevents world regions, countries etc from being added / deleted
   */
  static $_testEntitiesToSkip = array();
  /**
   * the factory class for this application
   * @var object
   */
  static $_factory = NULL;

  static $_checkedSqlFunctionsExist = FALSE;

  /**
   * Class constructor
   *
   * @return \CRM_Core_DAO
  @access public
   */
  function __construct() {
    $this->initialize();
    $this->__table = $this->getTableName();
  }

  /**
   * empty definition for virtual function
   */
  static function getTableName() {
    return NULL;
  }

  /**
   * initialize the DAO object
   *
   * @param string $dsn   the database connection string
   *
   * @return void
   * @access private
   * @static
   */
  static function init($dsn) {
    $options = &PEAR::getStaticProperty('DB_DataObject', 'options');
    $options['database'] = $dsn;
    if (defined('CIVICRM_DAO_DEBUG')) {
      self::DebugLevel(CIVICRM_DAO_DEBUG);
    }
  }

  /**
   * @param $fieldName
   * @param $fieldDef
   * @param $params
   *
   */
  protected function assignTestFK($fieldName, $fieldDef, $params) {
    $required = CRM_Utils_Array::value('required', $fieldDef);
    $FKClassName = CRM_Utils_Array::value('FKClassName', $fieldDef);
    $dbName = $fieldDef['name'];
    $daoName = get_class($this);

    // skip the FK if it is not required
    // if it's contact id we should create even if not required
    // we'll have a go @ fetching first though
    // we WILL create campaigns though for so tests with a campaign pseudoconstant will complete
    if ($FKClassName === 'CRM_Campaign_DAO_Campaign' && $daoName != $FKClassName) {
      $required = TRUE;
    }
    if (!$required && $dbName != 'contact_id') {
      $fkDAO = new $FKClassName;
      if ($fkDAO->find(TRUE)) {
        $this->$dbName = $fkDAO->id;
      }
      unset($fkDAO);
    }

    elseif (in_array($FKClassName, CRM_Core_DAO::$_testEntitiesToSkip)) {
      $depObject = new $FKClassName();
      $depObject->find(TRUE);
      $this->$dbName = $depObject->id;
      unset($depObject);
    }
    elseif ($daoName == 'CRM_Member_DAO_MembershipType' && $fieldName == 'member_of_contact_id') {
      // FIXME: the fields() metadata is not specific enough
      $depObject = CRM_Core_DAO::createTestObject($FKClassName, array('contact_type' => 'Organization'));
      $this->$dbName = $depObject->id;
      unset($depObject);
    }
    else {
      //if it is required we need to generate the dependency object first
      $depObject = CRM_Core_DAO::createTestObject($FKClassName, CRM_Utils_Array::value($dbName, $params, 1));
      $this->$dbName = $depObject->id;
      unset($depObject);
    }
  }

  /**
   * Generate and assign an arbitrary value to a field of a test object.
   *
   * @param string $fieldName
   * @param array $fieldDef
   * @param int $counter the globally-unique ID of the test object
   */
  protected function assignTestValue($fieldName, &$fieldDef, $counter) {
    $dbName = $fieldDef['name'];
    $daoName = get_class($this);
    $handled = FALSE;

    if (!$handled && $dbName == 'contact_sub_type') {
      //coming up with a rule to set this is too complex let's not set it
      $handled = TRUE;
    }

    // Pick an option value if needed
    if (!$handled && $fieldDef['type'] !== CRM_Utils_Type::T_BOOLEAN) {
      $options = $daoName::buildOptions($dbName, 'create');
      if ($options) {
        $this->$dbName = key($options);
        $handled = TRUE;
      }
    }

    if (!$handled) {
      switch ($fieldDef['type']) {
        case CRM_Utils_Type::T_INT:
        case CRM_Utils_Type::T_FLOAT:
        case CRM_Utils_Type::T_MONEY:
          if (isset($fieldDef['precision'])) {
            // $object->$dbName = CRM_Utils_Number::createRandomDecimal($value['precision']);
            $this->$dbName = CRM_Utils_Number::createTruncatedDecimal($counter, $fieldDef['precision']);
          }
          else {
            $this->$dbName = $counter;
          }
          break;

        case CRM_Utils_Type::T_BOOLEAN:
          if (isset($fieldDef['default'])) {
            $this->$dbName = $fieldDef['default'];
          }
          elseif ($fieldDef['name'] == 'is_deleted' || $fieldDef['name'] == 'is_test') {
            $this->$dbName = 0;
          }
          else {
            $this->$dbName = 1;
          }
          break;

        case CRM_Utils_Type::T_DATE:
        case CRM_Utils_Type::T_TIMESTAMP:
        case CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME:
          $this->$dbName = '19700101';
          if ($dbName == 'end_date') {
            // put this in the future
            $this->$dbName = '20200101';
          }
          break;

        case CRM_Utils_Type::T_TIME:
          CRM_Core_Error::fatal('T_TIME shouldnt be used.');
        //$object->$dbName='000000';
        //break;
        case CRM_Utils_Type::T_CCNUM:
          $this->$dbName = '4111 1111 1111 1111';
          break;

        case CRM_Utils_Type::T_URL:
          $this->$dbName = 'http://www.civicrm.org';
          break;

        case CRM_Utils_Type::T_STRING:
        case CRM_Utils_Type::T_BLOB:
        case CRM_Utils_Type::T_MEDIUMBLOB:
        case CRM_Utils_Type::T_TEXT:
        case CRM_Utils_Type::T_LONGTEXT:
        case CRM_Utils_Type::T_EMAIL:
        default:
          // WAS: if (isset($value['enumValues'])) {
          // TODO: see if this works with all pseudoconstants
          if (isset($fieldDef['pseudoconstant'], $fieldDef['pseudoconstant']['callback'])) {
            if (isset($fieldDef['default'])) {
              $this->$dbName = $fieldDef['default'];
            }
            else {
              $options = CRM_Core_PseudoConstant::get($daoName, $fieldName);
              if (is_array($options)) {
                $this->$dbName = $options[0];
              }
              else {
                $defaultValues = explode(',', $options);
                $this->$dbName = $defaultValues[0];
              }
            }
          }
          else {
            $this->$dbName = $dbName . '_' . $counter;
            $maxlength = CRM_Utils_Array::value('maxlength', $fieldDef);
            if ($maxlength > 0 && strlen($this->$dbName) > $maxlength) {
              $this->$dbName = substr($this->$dbName, 0, $fieldDef['maxlength']);
            }
          }
      }
    }
  }

  /**
   * reset the DAO object. DAO is kinda crappy in that there is an unwritten
   * rule of one query per DAO. We attempt to get around this crappy restricrion
   * by resetting some of DAO's internal fields. Use this with caution
   *
   * @return void
   * @access public
   *
   */
  function reset() {

    foreach (array_keys($this->table()) as $field) {
      unset($this->$field);
    }

    /**
     * reset the various DB_DAO structures manually
     */
    $this->_query = array();
    $this->whereAdd();
    $this->selectAdd();
    $this->joinAdd();
  }

  /**
   * @param $tableName
   *
   * @return string
   */
  static function getLocaleTableName($tableName) {
    global $dbLocale;
    if ($dbLocale) {
      $tables = CRM_Core_I18n_Schema::schemaStructureTables();
      if (in_array($tableName, $tables)) {
        return $tableName . $dbLocale;
      }
    }
    return $tableName;
  }

  /**
   * Execute a query by the current DAO, localizing it along the way (if needed).
   *
   * @param string $query        the SQL query for execution
   * @param bool   $i18nRewrite  whether to rewrite the query
   *
   * @return object              the current DAO object after the query execution
   */
  function query($query, $i18nRewrite = TRUE) {
    // rewrite queries that should use $dbLocale-based views for multi-language installs
    global $dbLocale;
    if ($i18nRewrite and $dbLocale) {
      $query = CRM_Core_I18n_Schema::rewriteQuery($query);
    }

    return parent::query($query);
  }

  /**
   * Static function to set the factory instance for this class.
   *
   * @param object $factory  the factory application object
   *
   * @return void
   * @access public
   * @static
   */
  static function setFactory(&$factory) {
    self::$_factory = &$factory;
  }

  /**
   * Factory method to instantiate a new object from a table name.
   *
   * @param string $table
   *
   * @return void
   * @access public
   */
  function factory($table = '') {
    if (!isset(self::$_factory)) {
      return parent::factory($table);
    }

    return self::$_factory->create($table);
  }

  /**
   * Initialization for all DAO objects. Since we access DB_DO programatically
   * we need to set the links manually.
   *
   * @return void
   * @access protected
   */
  function initialize() {
    $this->_connect();
    $this->query("SET NAMES utf8");
  }

  /**
   * Defines the default key as 'id'.
   *
   * @access protected
   *
   * @return array
   */
  function keys() {
    static $keys;
    if (!isset($keys)) {
      $keys = array('id');
    }
    return $keys;
  }

  /**
   * Tells DB_DataObject which keys use autoincrement.
   * 'id' is autoincrementing by default.
   *
   * @access protected
   *
   * @return array
   */
  function sequenceKey() {
    static $sequenceKeys;
    if (!isset($sequenceKeys)) {
      $sequenceKeys = array('id', TRUE);
    }
    return $sequenceKeys;
  }

  /**
   * returns list of FK relationships
   *
   * @static
   * @access public
   *
   * @return array of CRM_Core_Reference_Interface
   */
  static function getReferenceColumns() {
    return array();
  }

  /**
   * returns all the column names of this table
   *
   * @access public
   *
   * @return array
   */
   static function &fields() {
    $result = NULL;
    return $result;
  }

  /**
   * get/set an associative array of table columns
   *
   * @access public
   * @param  array key=>type array
   * @return array (associative)
   */
  function table() {
    $fields = &$this->fields();

    $table = array();
    if ($fields) {
      foreach ($fields as $name => $value) {
        $table[$value['name']] = $value['type'];
        if (!empty($value['required'])) {
          $table[$value['name']] += self::DB_DAO_NOTNULL;
        }
      }
    }

    return $table;
  }

  /**
   * @return $this
   */
  function save() {
    if (!empty($this->id)) {
      $this->update();
    }
    else {
      $this->insert();
    }
    $this->free();

    CRM_Utils_Hook::postSave($this);

    return $this;
  }

  /**
   * @param bool $created
   */
  function log($created = FALSE) {
    static $cid = NULL;

    if (!$this->getLog()) {
      return;
    }

    if (!$cid) {
      $session = CRM_Core_Session::singleton();
      $cid = $session->get('userID');
    }

    // return is we dont have handle to FK
    if (!$cid) {
      return;
    }

    $dao                = new CRM_Core_DAO_Log();
    $dao->entity_table  = $this->getTableName();
    $dao->entity_id     = $this->id;
    $dao->modified_id   = $cid;
    $dao->modified_date = date("YmdHis");
    $dao->insert();
  }

  /**
   * Given an associative array of name/value pairs, extract all the values
   * that belong to this object and initialize the object with said values
   *
   * @param array $params (reference ) associative array of name/value pairs
   *
   * @return boolean      did we copy all null values into the object
   * @access public
   */
  function copyValues(&$params) {
    $fields = &$this->fields();
    $allNull = TRUE;
    foreach ($fields as $name => $value) {
      $dbName = $value['name'];
      if (array_key_exists($dbName, $params)) {
        $pValue = $params[$dbName];
        $exists = TRUE;
      }
      elseif (array_key_exists($name, $params)) {
        $pValue = $params[$name];
        $exists = TRUE;
      }
      else {
        $exists = FALSE;
      }

      // if there is no value then make the variable NULL
      if ($exists) {
        if ($pValue === '') {
          $this->$dbName = 'null';
        }
        else {
          $this->$dbName = $pValue;
          $allNull = FALSE;
        }
      }
    }
    return $allNull;
  }

  /**
   * Store all the values from this object in an associative array
   * this is a destructive store, calling function is responsible
   * for keeping sanity of id's.
   *
   * @param object $object the object that we are extracting data from
   * @param array  $values (reference ) associative array of name/value pairs
   *
   * @return void
   * @access public
   * @static
   */
  static function storeValues(&$object, &$values) {
    $fields = &$object->fields();
    foreach ($fields as $name => $value) {
      $dbName = $value['name'];
      if (isset($object->$dbName) && $object->$dbName !== 'null') {
        $values[$dbName] = $object->$dbName;
        if ($name != $dbName) {
          $values[$name] = $object->$dbName;
        }
      }
    }
  }

  /**
   * create an attribute for this specific field. We only do this for strings and text
   *
   * @param array $field the field under task
   *
   * @return array|null the attributes for the object
   * @access public
   * @static
   */
  static function makeAttribute($field) {
    if ($field) {
      if (CRM_Utils_Array::value('type', $field) == CRM_Utils_Type::T_STRING) {
        $maxLength = CRM_Utils_Array::value('maxlength', $field);
        $size = CRM_Utils_Array::value('size', $field);
        if ($maxLength || $size) {
          $attributes = array();
          if ($maxLength) {
            $attributes['maxlength'] = $maxLength;
          }
          if ($size) {
            $attributes['size'] = $size;
          }
          return $attributes;
        }
      }
      elseif (CRM_Utils_Array::value('type', $field) == CRM_Utils_Type::T_TEXT) {
        $rows = CRM_Utils_Array::value('rows', $field);
        if (!isset($rows)) {
          $rows = 2;
        }
        $cols = CRM_Utils_Array::value('cols', $field);
        if (!isset($cols)) {
          $cols = 80;
        }

        $attributes         = array();
        $attributes['rows'] = $rows;
        $attributes['cols'] = $cols;
        return $attributes;
      }
      elseif (CRM_Utils_Array::value('type', $field) == CRM_Utils_Type::T_INT || CRM_Utils_Array::value('type', $field) == CRM_Utils_Type::T_FLOAT || CRM_Utils_Array::value('type', $field) == CRM_Utils_Type::T_MONEY) {
        $attributes['size'] = 6;
        $attributes['maxlength'] = 14;
        return $attributes;
      }
    }
    return NULL;
  }

  /**
   * Get the size and maxLength attributes for this text field
   * (or for all text fields) in the DAO object.
   *
   * @param string $class     name of DAO class
   * @param string $fieldName field that i'm interested in or null if
   *                          you want the attributes for all DAO text fields
   *
   * @return array assoc array of name => attribute pairs
   * @access public
   * @static
   */
  static function getAttribute($class, $fieldName = NULL) {
    $object = new $class( );
    $fields = &$object->fields();
    if ($fieldName != NULL) {
      $field = CRM_Utils_Array::value($fieldName, $fields);
      return self::makeAttribute($field);
    }
    else {
      $attributes = array();
      foreach ($fields as $name => $field) {
        $attribute = self::makeAttribute($field);
        if ($attribute) {
          $attributes[$name] = $attribute;
        }
      }

      if (!empty($attributes)) {
        return $attributes;
      }
    }
    return NULL;
  }

  /**
   * @param $type
   *
   * @throws Exception
   */
  static function transaction($type) {
    CRM_Core_Error::fatal('This function is obsolete, please use CRM_Core_Transaction');
  }

  /**
   * Check if there is a record with the same name in the db
   *
   * @param string $value     the value of the field we are checking
   * @param string $daoName   the dao object name
   * @param string $daoID     the id of the object being updated. u can change your name
   *                          as long as there is no conflict
   * @param string $fieldName the name of the field in the DAO
   *
   * @return boolean     true if object exists
   * @access public
   * @static
   */
  static function objectExists($value, $daoName, $daoID, $fieldName = 'name') {
    $object = new $daoName( );
    $object->$fieldName = $value;

    $config = CRM_Core_Config::singleton();

    if ($object->find(TRUE)) {
      return ($daoID && $object->id == $daoID) ? TRUE : FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Check if there is a given column in a specific table
   *
   * @param string $tableName
   * @param string $columnName
   * @param bool   $i18nRewrite  whether to rewrite the query on multilingual setups
   *
   * @return boolean true if exists, else false
   * @static
   */
  static function checkFieldExists($tableName, $columnName, $i18nRewrite = TRUE) {
    $query = "
SHOW COLUMNS
FROM $tableName
LIKE %1
";
    $params = array(1 => array($columnName, 'String'));
    $dao    = CRM_Core_DAO::executeQuery($query, $params, TRUE, NULL, FALSE, $i18nRewrite);
    $result = $dao->fetch() ? TRUE : FALSE;
    $dao->free();
    return $result;
  }

  /**
   * Returns the storage engine used by given table-name(optional).
   * Otherwise scans all the tables and return an array of all the
   * distinct storage engines being used.
   *
   * @param string $tableName
   *
   * @param int $maxTablesToCheck
   * @param string $fieldName
   *
   * @return array
   * @static
   */
  static function getStorageValues($tableName = NULL, $maxTablesToCheck = 10, $fieldName = 'Engine') {
    $values = array();
    $query = "SHOW TABLE STATUS LIKE %1";

    $params = array();

    if (isset($tableName)) {
      $params = array(1 => array($tableName, 'String'));
    }
    else {
      $params = array(1 => array('civicrm_%', 'String'));
    }

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $count = 0;
    while ($dao->fetch()) {
      if (isset($values[$dao->$fieldName]) ||
        // ignore import and other temp tables
        strpos($dao->Name, 'civicrm_import_job_') !== FALSE ||
        strpos($dao->Name, '_temp') !== FALSE
      ) {
        continue;
      }
      $values[$dao->$fieldName] = 1;
      $count++;
      if ($maxTablesToCheck &&
        $count >= $maxTablesToCheck
      ) {
        break;
      }
    }
    $dao->free();
    return $values;
  }

  /**
   * @param int $maxTablesToCheck
   *
   * @return bool
   */
  static function isDBMyISAM($maxTablesToCheck = 10) {
    // show error if any of the tables, use 'MyISAM' storage engine.
    $engines = self::getStorageValues(NULL, $maxTablesToCheck);
    if (array_key_exists('MyISAM', $engines)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Checks if a constraint exists for a specified table.
   *
   * @param string $tableName
   * @param string $constraint
   *
   * @return boolean true if constraint exists, false otherwise
   * @static
   */
  static function checkConstraintExists($tableName, $constraint) {
    static $show = array();

    if (!array_key_exists($tableName, $show)) {
      $query = "SHOW CREATE TABLE $tableName";
      $dao = CRM_Core_DAO::executeQuery($query);

      if (!$dao->fetch()) {
        CRM_Core_Error::fatal();
      }

      $dao->free();
      $show[$tableName] = $dao->Create_Table;
    }

    return preg_match("/\b$constraint\b/i", $show[$tableName]) ? TRUE : FALSE;
  }

  /**
   * Checks if CONSTRAINT keyword exists for a specified table.
   *
   * @param array $tables
   *
   * @throws Exception
   * @internal param string $tableName
   *
   * @return boolean true if CONSTRAINT keyword exists, false otherwise
   */
  static function schemaRequiresRebuilding($tables = array("civicrm_contact")) {
    $show = array();
    foreach($tables as $tableName){
      if (!array_key_exists($tableName, $show)) {
        $query = "SHOW CREATE TABLE $tableName";
        $dao = CRM_Core_DAO::executeQuery($query);

        if (!$dao->fetch()) {
          CRM_Core_Error::fatal();
        }

        $dao->free();
        $show[$tableName] = $dao->Create_Table;
      }

      $result = preg_match("/\bCONSTRAINT\b\s/i", $show[$tableName]) ? TRUE : FALSE;
      if($result == TRUE){
        continue;
      }
      else{
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Checks if the FK constraint name is in the format 'FK_tableName_columnName'
   * for a specified column of a table.
   *
   * @param string $tableName
   * @param string $columnName
   *
   * @return boolean true if in format, false otherwise
   * @static
   */
  static function checkFKConstraintInFormat($tableName, $columnName) {
    static $show = array();

    if (!array_key_exists($tableName, $show)) {
      $query = "SHOW CREATE TABLE $tableName";
      $dao = CRM_Core_DAO::executeQuery($query);

      if (!$dao->fetch()) {
        CRM_Core_Error::fatal();
      }

      $dao->free();
      $show[$tableName] = $dao->Create_Table;
    }
    $constraint = "`FK_{$tableName}_{$columnName}`";
    $pattern = "/\bCONSTRAINT\b\s+%s\s+\bFOREIGN\s+KEY\b\s/i";
    return preg_match(sprintf($pattern, $constraint),$show[$tableName]) ? TRUE : FALSE;
  }

  /**
   * Check whether a specific column in a specific table has always the same value
   *
   * @param string $tableName
   * @param string $columnName
   * @param string $columnValue
   *
   * @return boolean true if the value is always $columnValue, false otherwise
   * @static
   */
  static function checkFieldHasAlwaysValue($tableName, $columnName, $columnValue) {
    $query  = "SELECT * FROM $tableName WHERE $columnName != '$columnValue'";
    $dao    = CRM_Core_DAO::executeQuery($query);
    $result = $dao->fetch() ? FALSE : TRUE;
    $dao->free();
    return $result;
  }

  /**
   * Check whether a specific column in a specific table is always NULL
   *
   * @param string $tableName
   * @param string $columnName
   *
   * @return boolean true if if the value is always NULL, false otherwise
   * @static
   */
  static function checkFieldIsAlwaysNull($tableName, $columnName) {
    $query  = "SELECT * FROM $tableName WHERE $columnName IS NOT NULL";
    $dao    = CRM_Core_DAO::executeQuery($query);
    $result = $dao->fetch() ? FALSE : TRUE;
    $dao->free();
    return $result;
  }

  /**
   * Check if there is a given table in the database
   *
   * @param string $tableName
   *
   * @return boolean true if exists, else false
   * @static
   */
  static function checkTableExists($tableName) {
    $query = "
SHOW TABLES
LIKE %1
";
    $params = array(1 => array($tableName, 'String'));

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $result = $dao->fetch() ? TRUE : FALSE;
    $dao->free();
    return $result;
  }

  /**
   * @param $version
   *
   * @return bool
   */
  function checkVersion($version) {
    $query = "
SELECT version
FROM   civicrm_domain
";
    $dbVersion = CRM_Core_DAO::singleValueQuery($query);
    return trim($version) == trim($dbVersion) ? TRUE : FALSE;
  }

  /**
   * Given a DAO name, a column name and a column value, find the record and GET the value of another column in that record
   *
   * @param string  $daoName       Name of the DAO (Example: CRM_Contact_DAO_Contact to retrieve value from a contact)
   * @param int     $searchValue   Value of the column you want to search by
   * @param string  $returnColumn  Name of the column you want to GET the value of
   * @param string  $searchColumn  Name of the column you want to search by
   * @param boolean $force         Skip use of the cache
   *
   * @return string|null          Value of $returnColumn in the retrieved record
   * @static
   * @access public
   */
  static function getFieldValue($daoName, $searchValue, $returnColumn = 'name', $searchColumn = 'id', $force = FALSE) {
    if (
      empty($searchValue) ||
      trim(strtolower($searchValue)) == 'null'
    ) {
      // adding this year since developers forget to check for an id
      // or for the 'null' (which is a bad DAO kludge)
      // and hence we get the first value in the db
      CRM_Core_Error::fatal();
    }

    $cacheKey = "{$daoName}:{$searchValue}:{$returnColumn}:{$searchColumn}";
    if (self::$_dbColumnValueCache === NULL) {
      self::$_dbColumnValueCache = array();
    }

    if (!array_key_exists($cacheKey, self::$_dbColumnValueCache) || $force) {
      $object   = new $daoName( );
      $object->$searchColumn = $searchValue;
      $object->selectAdd();
      $object->selectAdd($returnColumn);

      $result = NULL;
      if ($object->find(TRUE)) {
        $result = $object->$returnColumn;
      }
      $object->free();

      self::$_dbColumnValueCache[$cacheKey] = $result;
    }
    return self::$_dbColumnValueCache[$cacheKey];
  }

  /**
   * Given a DAO name, a column name and a column value, find the record and SET the value of another column in that record
   *
   * @param string $daoName       Name of the DAO (Example: CRM_Contact_DAO_Contact to retrieve value from a contact)
   * @param int    $searchValue   Value of the column you want to search by
   * @param string $setColumn     Name of the column you want to SET the value of
   * @param string $setValue      SET the setColumn to this value
   * @param string $searchColumn  Name of the column you want to search by
   *
   * @return boolean          true if we found and updated the object, else false
   * @static
   * @access public
   */
  static function setFieldValue($daoName, $searchValue, $setColumn, $setValue, $searchColumn = 'id') {
    $object = new $daoName( );
    $object->selectAdd();
    $object->selectAdd("$searchColumn, $setColumn");
    $object->$searchColumn = $searchValue;
    $result = FALSE;
    if ($object->find(TRUE)) {
      $object->$setColumn = $setValue;
      if ($object->save()) {
        $result = TRUE;
      }
    }
    $object->free();
    return $result;
  }

  /**
   * Get sort string
   *
   * @param array|object $sort either array or CRM_Utils_Sort
   * @param string $default - default sort value
   *
   * @return string - sortString
   * @access public
   * @static
   */
  static function getSortString($sort, $default = NULL) {
    // check if sort is of type CRM_Utils_Sort
    if (is_a($sort, 'CRM_Utils_Sort')) {
      return $sort->orderBy();
    }

    // is it an array specified as $field => $sortDirection ?
    if ($sort) {
      foreach ($sort as $k => $v) {
        $sortString .= "$k $v,";
      }
      return rtrim($sortString, ',');
    }
    return $default;
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param string $daoName  name of the dao object
   * @param array  $params   (reference ) an assoc array of name/value pairs
   * @param array  $defaults (reference ) an assoc array to hold the flattened values
   * @param array  $returnProperities     an assoc array of fields that need to be returned, eg array( 'first_name', 'last_name')
   *
   * @return object an object of type referenced by daoName
   * @access public
   * @static
   */
  static function commonRetrieve($daoName, &$params, &$defaults, $returnProperities = NULL) {
    $object = new $daoName( );
    $object->copyValues($params);

    // return only specific fields if returnproperties are sent
    if (!empty($returnProperities)) {
      $object->selectAdd();
      $object->selectAdd(implode(',', $returnProperities));
    }

    if ($object->find(TRUE)) {
      self::storeValues($object, $defaults);
      return $object;
    }
    return NULL;
  }

  /**
   * Delete the object records that are associated with this contact
   *
   * @param string $daoName  name of the dao object
   * @param  int  $contactId id of the contact to delete
   *
   * @return void
   * @access public
   * @static
   */
  static function deleteEntityContact($daoName, $contactId) {
    $object = new $daoName( );

    $object->entity_table = 'civicrm_contact';
    $object->entity_id = $contactId;
    $object->delete();
  }

  /**
   * execute a query
   *
   * @param string $query query to be executed
   *
   * @param array $params
   * @param bool $abort
   * @param null $daoName
   * @param bool $freeDAO
   * @param bool $i18nRewrite
   * @param bool $trapException
   *
   * @return Object CRM_Core_DAO object that holds the results of the query
   * @static
   * @access public
   */
  static function &executeQuery(
    $query,
    $params        = array(),
    $abort         = TRUE,
    $daoName       = NULL,
    $freeDAO       = FALSE,
    $i18nRewrite   = TRUE,
    $trapException = FALSE
  ) {
    $queryStr = self::composeQuery($query, $params, $abort);
    //CRM_Core_Error::debug( 'q', $queryStr );

    if (!$daoName) {
      $dao = new CRM_Core_DAO();
    }
    else {
      $dao = new $daoName( );
    }

    if ($trapException) {
      $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
    }

    $result = $dao->query($queryStr, $i18nRewrite);

    if (is_a($result, 'DB_Error')) {
      return $result;
    }

    if ($freeDAO ||
      preg_match('/^(insert|update|delete|create|drop|replace)/i', $queryStr)
    ) {
      // we typically do this for insert/update/delete stataments OR if explicitly asked to
      // free the dao
      $dao->free();
    }
    return $dao;
  }

  /**
   * execute a query and get the single result
   *
   * @param string $query query to be executed
   * @param array $params
   * @param bool $abort
   * @param bool $i18nRewrite
   * @return string|null the result of the query if any
   *
   * @static
   * @access public
   */
  static function &singleValueQuery($query,
    $params      = array(),
    $abort       = TRUE,
    $i18nRewrite = TRUE
  ) {
    $queryStr = self::composeQuery($query, $params, $abort);

    static $_dao = NULL;

    if (!$_dao) {
      $_dao = new CRM_Core_DAO();
    }

    $_dao->query($queryStr, $i18nRewrite);

    $result = $_dao->getDatabaseResult();
    $ret = NULL;
    if ($result) {
      $row = $result->fetchRow();
      if ($row) {
        $ret = $row[0];
      }
    }
    $_dao->free();
    return $ret;
  }

  /**
   * @param $query
   * @param $params
   * @param bool $abort
   *
   * @return string
   * @throws Exception
   */
  static function composeQuery($query, &$params, $abort = TRUE) {
    $tr = array();
    foreach ($params as $key => $item) {
      if (is_numeric($key)) {
        if (CRM_Utils_Type::validate($item[0], $item[1]) !== NULL) {
          $item[0] = self::escapeString($item[0]);
          if ($item[1] == 'String' ||
            $item[1] == 'Memo' ||
            $item[1] == 'Link'
          ) {
            // Support class constants stipulating wildcard characters and/or
            // non-quoting of strings. Also support legacy code which may be
            // passing in TRUE or 1 for $item[2], which used to indicate the
            // use of wildcard characters.
            if (!empty($item[2])) {
              if ($item[2] & CRM_Core_DAO::QUERY_FORMAT_WILDCARD || $item[2] === TRUE) {
                $item[0] = "'%{$item[0]}%'";
              }
              elseif (!($item[2] & CRM_Core_DAO::QUERY_FORMAT_NO_QUOTES)) {
                $item[0] = "'{$item[0]}'";
              }
            }
            else {
              $item[0] = "'{$item[0]}'";
            }
          }

          if (($item[1] == 'Date' || $item[1] == 'Timestamp') &&
            strlen($item[0]) == 0
          ) {
            $item[0] = 'null';
          }

          $tr['%' . $key] = $item[0];
        }
        elseif ($abort) {
          CRM_Core_Error::fatal("{$item[0]} is not of type {$item[1]}");
        }
      }
    }

    return strtr($query, $tr);
  }

  /**
   * @param null $ids
   */
  static function freeResult($ids = NULL) {
    global $_DB_DATAOBJECT;

    /***
     $q = array( );
     foreach ( array_keys( $_DB_DATAOBJECT['RESULTS'] ) as $id ) {
     $q[] = $_DB_DATAOBJECT['RESULTS'][$id]->query;
     }
     CRM_Core_Error::debug( 'k', $q );
     return;
     ***/

    if (!$ids) {
      if (!$_DB_DATAOBJECT ||
        !isset($_DB_DATAOBJECT['RESULTS'])
      ) {
        return;
      }
      $ids = array_keys($_DB_DATAOBJECT['RESULTS']);
    }

    foreach ($ids as $id) {
      if (isset($_DB_DATAOBJECT['RESULTS'][$id])) {
        if (is_resource($_DB_DATAOBJECT['RESULTS'][$id]->result)) {
          mysql_free_result($_DB_DATAOBJECT['RESULTS'][$id]->result);
        }
        unset($_DB_DATAOBJECT['RESULTS'][$id]);
      }

      if (isset($_DB_DATAOBJECT['RESULTFIELDS'][$id])) {
        unset($_DB_DATAOBJECT['RESULTFIELDS'][$id]);
      }
    }
  }

  /**
   * This function is to make a shallow copy of an object
   * and all the fields in the object
   *
   * @param string $daoName                 name of the dao
   * @param array  $criteria                array of all the fields & values
   *                                        on which basis to copy
   * @param array  $newData                 array of all the fields & values
   *                                        to be copied besides the other fields
   * @param string $fieldsFix               array of fields that you want to prefix/suffix/replace
   * @param string $blockCopyOfDependencies fields that you want to block from
   *                                        getting copied
   *
   *
   * @return (reference )                   the newly created copy of the object
   * @access public
   */
  static function &copyGeneric($daoName, $criteria, $newData = NULL, $fieldsFix = NULL, $blockCopyOfDependencies = NULL) {
    $object = new $daoName( );
    if (!$newData) {
      $object->id = $criteria['id'];
    }
    else {
      foreach ($criteria as $key => $value) {
        $object->$key = $value;
      }
    }

    $object->find();
    while ($object->fetch()) {

      // all the objects except with $blockCopyOfDependencies set
      // be copied - addresses #CRM-1962

      if ($blockCopyOfDependencies && $object->$blockCopyOfDependencies) {
        break;
      }

      $newObject   = new $daoName( );

      $fields = &$object->fields();
      if (!is_array($fieldsFix)) {
        $fieldsToPrefix  = array();
        $fieldsToSuffix  = array();
        $fieldsToReplace = array();
      }
      if (!empty($fieldsFix['prefix'])) {
        $fieldsToPrefix = $fieldsFix['prefix'];
      }
      if (!empty($fieldsFix['suffix'])) {
        $fieldsToSuffix = $fieldsFix['suffix'];
      }
      if (!empty($fieldsFix['replace'])) {
        $fieldsToReplace = $fieldsFix['replace'];
      }

      foreach ($fields as $name => $value) {
        if ($name == 'id' || $value['name'] == 'id') {
          // copy everything but the id!
          continue;
        }

        $dbName = $value['name'];
        $type = CRM_Utils_Type::typeToString($value['type']);
        $newObject->$dbName = $object->$dbName;
        if (isset($fieldsToPrefix[$dbName])) {
          $newObject->$dbName = $fieldsToPrefix[$dbName] . $newObject->$dbName;
        }
        if (isset($fieldsToSuffix[$dbName])) {
          $newObject->$dbName .= $fieldsToSuffix[$dbName];
        }
        if (isset($fieldsToReplace[$dbName])) {
          $newObject->$dbName = $fieldsToReplace[$dbName];
        }

        if ($type == 'Timestamp' || $type == 'Date') {
          $newObject->$dbName = CRM_Utils_Date::isoToMysql($newObject->$dbName);
        }

        if ($newData) {
          foreach ($newData as $k => $v) {
            $newObject->$k = $v;
          }
        }
      }
      $newObject->save();
    }
    return $newObject;
  }

  /**
   * Given the component id, compute the contact id
   * since its used for things like send email
   *
   * @param $componentIDs
   * @param $tableName
   *
   * @return array
   */
  public static function &getContactIDsFromComponent(&$componentIDs, $tableName) {
    $contactIDs = array();

    if (empty($componentIDs)) {
      return $contactIDs;
    }

    $IDs = implode(',', $componentIDs);
    $query = "
SELECT contact_id
  FROM $tableName
 WHERE id IN ( $IDs )
";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $contactIDs[] = $dao->contact_id;
    }
    return $contactIDs;
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param string $daoName name of the dao object
   * @param string $fieldIdName
   * @param $fieldId
   * @param $details
   * @param array $returnProperities an assoc array of fields that need to be returned, eg array( 'first_name', 'last_name')
   *
   * @internal param array $params (reference ) an assoc array of name/value pairs
   * @internal param array $defaults (reference ) an assoc array to hold the flattened values
   * @return object an object of type referenced by daoName
   * @access public
   * @static
   */
  static function commonRetrieveAll($daoName, $fieldIdName = 'id', $fieldId, &$details, $returnProperities = NULL) {
    require_once (str_replace('_', DIRECTORY_SEPARATOR, $daoName) . ".php");
    $object = new $daoName( );
    $object->$fieldIdName = $fieldId;

    // return only specific fields if returnproperties are sent
    if (!empty($returnProperities)) {
      $object->selectAdd();
      $object->selectAdd('id');
      $object->selectAdd(implode(',', $returnProperities));
    }

    $object->find();
    while ($object->fetch()) {
      $defaults = array();
      self::storeValues($object, $defaults);
      $details[$object->id] = $defaults;
    }

    return $details;
  }

  static function dropAllTables() {

    // first drop all the custom tables we've created
    CRM_Core_BAO_CustomGroup::dropAllTables();

    // drop all multilingual views
    CRM_Core_I18n_Schema::dropAllViews();

    CRM_Utils_File::sourceSQLFile(CIVICRM_DSN,
      dirname(__FILE__) . DIRECTORY_SEPARATOR .
      '..' . DIRECTORY_SEPARATOR .
      '..' . DIRECTORY_SEPARATOR .
      'sql' . DIRECTORY_SEPARATOR .
      'civicrm_drop.mysql'
    );
  }

  /**
   * @param $string
   *
   * @return string
   */
  static function escapeString($string) {
    static $_dao = NULL;

    if (!$_dao) {
      $_dao = new CRM_Core_DAO();
    }

    return $_dao->escape($string);
  }

  /**
   * Escape a list of strings for use with "WHERE X IN (...)" queries.
   *
   * @param $strings array
   * @param $default string the value to use if $strings has no elements
   * @return string eg "abc","def","ghi"
   */
  static function escapeStrings($strings, $default = NULL) {
    static $_dao = NULL;
    if (!$_dao) {
      $_dao = new CRM_Core_DAO();
    }

    if (empty($strings)) {
      return $default;
    }

    $escapes = array_map(array($_dao, 'escape'), $strings);
    return '"' . implode('","', $escapes) . '"';
  }

  /**
   * @param $string
   *
   * @return string
   */
  static function escapeWildCardString($string) {
    // CRM-9155
    // ensure we escape the single characters % and _ which are mysql wild
    // card characters and could come in via sortByCharacter
    // note that mysql does not escape these characters
    if ($string && in_array($string,
        array('%', '_', '%%', '_%')
      )) {
      return '\\' . $string;
    }

    return self::escapeString($string);
  }

  /**
   * Creates a test object, including any required objects it needs via recursion
   * createOnly: only create in database, do not store or return the objects (useful for perf testing)
   * ONLY USE FOR TESTING
   *
   * @param $daoName
   * @param array $params
   * @param int $numObjects
   * @param bool $createOnly
   *
   * @return
   */
  static function createTestObject(
    $daoName,
    $params = array(),
    $numObjects = 1,
    $createOnly = FALSE
  ) {
    //this is a test function  also backtrace is set for the test suite it sometimes unsets itself
    // so we re-set here in case
    $config = CRM_Core_Config::singleton();
    $config->backtrace = TRUE;

    static $counter = 0;
    CRM_Core_DAO::$_testEntitiesToSkip = array(
      'CRM_Core_DAO_Worldregion',
      'CRM_Core_DAO_StateProvince',
      'CRM_Core_DAO_Country',
      'CRM_Core_DAO_Domain',
    );

    for ($i = 0; $i < $numObjects; ++$i) {

      ++$counter;
      /** @var CRM_Core_DAO $object */
      $object = new $daoName();

      $fields = & $object->fields();
      foreach ($fields as $fieldName => $fieldDef) {
        $dbName = $fieldDef['name'];
        $FKClassName = CRM_Utils_Array::value('FKClassName', $fieldDef);
        $required = CRM_Utils_Array::value('required', $fieldDef);

        if (CRM_Utils_Array::value($dbName, $params) !== NULL && !is_array($params[$dbName])) {
          $object->$dbName = $params[$dbName];
        }

        elseif ($dbName != 'id') {
          if ($FKClassName != NULL) {
            $object->assignTestFK($fieldName, $fieldDef, $params);
            continue;
          } else {
            $object->assignTestValue($fieldName, $fieldDef, $counter);
          }
        }
      }

      $object->save();

      if (!$createOnly) {
        $objects[$i] = $object;
      }
      else {
        unset($object);
      }
    }

    if ($createOnly) {
      return;
    }
    elseif ($numObjects == 1) {
      return $objects[0];
    }
    else {
      return $objects;
    }
  }

  /**
   * deletes the this object plus any dependent objects that are associated with it
   * ONLY USE FOR TESTING
   *
   * @param $daoName
   * @param array $params
   */
  static function deleteTestObjects($daoName, $params = array(
    )) {
    //this is a test function  also backtrace is set for the test suite it sometimes unsets itself
    // so we re-set here in case
    $config = CRM_Core_Config::singleton();
    $config->backtrace = TRUE;

    $object = new $daoName();
    $object->id = CRM_Utils_Array::value('id', $params);

    $deletions = array(); // array(array(0 => $daoName, 1 => $daoParams))
    if ($object->find(TRUE)) {

      $fields = &$object->fields();
      foreach ($fields as $name => $value) {

        $dbName = $value['name'];

        $FKClassName = CRM_Utils_Array::value('FKClassName', $value);
        $required = CRM_Utils_Array::value('required', $value);
        if ($FKClassName != NULL
          && $object->$dbName
          && !in_array($FKClassName, CRM_Core_DAO::$_testEntitiesToSkip)
          && ($required || $dbName == 'contact_id')) {
          $deletions[] = array($FKClassName, array('id' => $object->$dbName)); // x
        }
      }
    }

    $object->delete();

    foreach ($deletions as $deletion) {
      CRM_Core_DAO::deleteTestObjects($deletion[0], $deletion[1]);
  }
  }

  /**
   * @param string $prefix
   * @param bool $addRandomString
   * @param null $string
   *
   * @return string
   */
  static function createTempTableName($prefix = 'civicrm', $addRandomString = TRUE, $string = NULL) {
    $tableName = $prefix . "_temp";

    if ($addRandomString) {
      if ($string) {
        $tableName .= "_" . $string;
      }
      else {
        $tableName .= "_" . md5(uniqid('', TRUE));
      }
    }
    return $tableName;
  }

  /**
   * @param bool $view
   * @param bool $trigger
   *
   * @return bool
   */
  static function checkTriggerViewPermission($view = TRUE, $trigger = TRUE) {
    // test for create view and trigger permissions and if allowed, add the option to go multilingual
    // and logging
    // I'm not sure why we use the getStaticProperty for an error, rather than checking for DB_Error
    $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
    $dao = new CRM_Core_DAO();
    if ($view) {
      $dao->query('CREATE OR REPLACE VIEW civicrm_domain_view AS SELECT * FROM civicrm_domain');
      if (PEAR::getStaticProperty('DB_DataObject', 'lastError')) {
        return FALSE;
      }
    }

    if ($trigger) {
      $result = $dao->query('CREATE TRIGGER civicrm_domain_trigger BEFORE INSERT ON civicrm_domain FOR EACH ROW BEGIN END');
      if (PEAR::getStaticProperty('DB_DataObject', 'lastError') || is_a($result, 'DB_Error')) {
        if ($view) {
          $dao->query('DROP VIEW IF EXISTS civicrm_domain_view');
        }
        return FALSE;
      }

      $dao->query('DROP TRIGGER IF EXISTS civicrm_domain_trigger');
      if (PEAR::getStaticProperty('DB_DataObject', 'lastError')) {
        if ($view) {
          $dao->query('DROP VIEW IF EXISTS civicrm_domain_view');
        }
        return FALSE;
      }
    }

    if ($view) {
      $dao->query('DROP VIEW IF EXISTS civicrm_domain_view');
      if (PEAR::getStaticProperty('DB_DataObject', 'lastError')) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * @param null $message
   * @param bool $printDAO
   */
  static function debugPrint($message = NULL, $printDAO = TRUE) {
    CRM_Utils_System::xMemory("{$message}: ");

    if ($printDAO) {
      global $_DB_DATAOBJECT;
      $q = array();
      foreach (array_keys($_DB_DATAOBJECT['RESULTS']) as $id) {
        $q[] = $_DB_DATAOBJECT['RESULTS'][$id]->query;
      }
      CRM_Core_Error::debug('_DB_DATAOBJECT', $q);
    }
  }

  /**
   * Build a list of triggers via hook and add them to (err, reconcile them
   * with) the database.
   *
   * @param $tableName string the specific table requiring a rebuild; or NULL to rebuild all tables
   * @param bool $force
   *
   * @see CRM-9716
   */
  static function triggerRebuild($tableName = NULL, $force = FALSE) {
    $info = array();

    $logging = new CRM_Logging_Schema;
    $logging->triggerInfo($info, $tableName, $force);

    CRM_Core_I18n_Schema::triggerInfo($info, $tableName);
    CRM_Contact_BAO_Contact::triggerInfo($info, $tableName);

    CRM_Utils_Hook::triggerInfo($info, $tableName);

    // drop all existing triggers on all tables
    $logging->dropTriggers($tableName);

    // now create the set of new triggers
    self::createTriggers($info, $tableName);
  }

  /**
   * Because sql functions are sometimes lost, esp during db migration, we check here to avoid numerous support requests
   * @see http://issues.civicrm.org/jira/browse/CRM-13822
   * TODO: Alternative solutions might be
   *  * Stop using functions and find another way to strip numeric characters from phones
   *  * Give better error messages (currently a missing fn fatals with "unknown error")
   */
  static function checkSqlFunctionsExist() {
    if (!self::$_checkedSqlFunctionsExist) {
      self::$_checkedSqlFunctionsExist = TRUE;
      $dao = CRM_Core_DAO::executeQuery("SHOW function status WHERE db = database() AND name = 'civicrm_strip_non_numeric'");
      if (!$dao->fetch()) {
        self::triggerRebuild();
      }
    }
  }

  /**
   * Wrapper function to drop triggers
   *
   * @param $tableName string the specific table requiring a rebuild; or NULL to rebuild all tables
   */
  static function dropTriggers($tableName = NULL) {
    $info = array();

    $logging = new CRM_Logging_Schema;
    $logging->triggerInfo($info, $tableName);

    // drop all existing triggers on all tables
    $logging->dropTriggers($tableName);
  }

  /**
   * @param $info array per hook_civicrm_triggerInfo
   * @param $onlyTableName string the specific table requiring a rebuild; or NULL to rebuild all tables
   */
  static function createTriggers(&$info, $onlyTableName = NULL) {
    // Validate info array, should probably raise errors?
    if (is_array($info) == FALSE) {
      return;
    }

    $triggers = array();

    // now enumerate the tables and the events and collect the same set in a different format
    foreach ($info as $value) {

      // clean the incoming data, skip malformed entries
      // TODO: malformed entries should raise errors or get logged.
      if (isset($value['table']) == FALSE ||
        isset($value['event']) == FALSE ||
        isset($value['when']) == FALSE ||
        isset($value['sql']) == FALSE
      ) {
        continue;
      }

      if (is_string($value['table']) == TRUE) {
        $tables = array($value['table']);
      }
      else {
        $tables = $value['table'];
      }

      if (is_string($value['event']) == TRUE) {
        $events = array(strtolower($value['event']));
      }
      else {
        $events = array_map('strtolower', $value['event']);
      }

      $whenName = strtolower($value['when']);

      foreach ($tables as $tableName) {
        if (!isset($triggers[$tableName])) {
          $triggers[$tableName] = array();
        }

        foreach ($events as $eventName) {
          $template_params = array('{tableName}', '{eventName}');
          $template_values = array($tableName, $eventName);

          $sql = str_replace($template_params,
            $template_values,
            $value['sql']
          );
          $variables = str_replace($template_params,
            $template_values,
            CRM_Utils_Array::value('variables', $value)
          );

          if (!isset($triggers[$tableName][$eventName])) {
            $triggers[$tableName][$eventName] = array();
          }

          if (!isset($triggers[$tableName][$eventName][$whenName])) {
            // We're leaving out cursors, conditions, and handlers for now
            // they are kind of dangerous in this context anyway
            // better off putting them in stored procedures
            $triggers[$tableName][$eventName][$whenName] = array(
              'variables' => array(),
              'sql' => array(),
            );
          }

          if ($variables) {
            $triggers[$tableName][$eventName][$whenName]['variables'][] = $variables;
          }

          $triggers[$tableName][$eventName][$whenName]['sql'][] = $sql;
        }
      }
    }

    // now spit out the sql
    foreach ($triggers as $tableName => $tables) {
      if ($onlyTableName != NULL && $onlyTableName != $tableName) {
        continue;
      }
      foreach ($tables as $eventName => $events) {
        foreach ($events as $whenName => $parts) {
          $varString   = implode("\n", $parts['variables']);
          $sqlString   = implode("\n", $parts['sql']);
          $validName   = CRM_Core_DAO::shortenSQLName($tableName, 48, TRUE);
          $triggerName = "{$validName}_{$whenName}_{$eventName}";
          $triggerSQL  = "CREATE TRIGGER $triggerName $whenName $eventName ON $tableName FOR EACH ROW BEGIN $varString $sqlString END";

          CRM_Core_DAO::executeQuery("DROP TRIGGER IF EXISTS $triggerName");
          CRM_Core_DAO::executeQuery(
            $triggerSQL,
            array(),
            TRUE,
            NULL,
            FALSE,
            FALSE
          );
        }
      }
    }
  }

  /**
   * Given a list of fields, create a list of references.
   *
   * @param string $className BAO/DAO class name
   * @return array<CRM_Core_Reference_Interface>
   */
  static function createReferenceColumns($className) {
    $result = array();
    $fields = $className::fields();
    foreach ($fields as $field) {
      if (isset($field['pseudoconstant'], $field['pseudoconstant']['optionGroupName'])) {
        $result[] = new CRM_Core_Reference_OptionValue(
          $className::getTableName(),
          $field['name'],
          'civicrm_option_value',
          CRM_Utils_Array::value('keyColumn', $field['pseudoconstant'], 'value'),
          $field['pseudoconstant']['optionGroupName']
        );
      }
    }
    return $result;
  }

  /**
   * Find all records which refer to this entity.
   *
   * @return array of objects referencing this
   */
  function findReferences() {
    $links = self::getReferencesToTable(static::getTableName());

    $occurrences = array();
    foreach ($links as $refSpec) {
      /** @var $refSpec CRM_Core_Reference_Interface */
      $daoName = CRM_Core_DAO_AllCoreTables::getClassForTable($refSpec->getReferenceTable());
      $result = $refSpec->findReferences($this);
      if ($result) {
        while ($result->fetch()) {
          $obj = new $daoName();
          $obj->id = $result->id;
          $occurrences[] = $obj;
        }
      }
    }

    return $occurrences;
  }

  /**
   * @return array each item has keys:
   *  - name: string
   *  - type: string
   *  - count: int
   *  - table: string|null SQL table name
   *  - key: string|null SQL column name
   */
  function getReferenceCounts() {
    $links = self::getReferencesToTable(static::getTableName());

    $counts = array();
    foreach ($links as $refSpec) {
      /** @var $refSpec CRM_Core_Reference_Interface */
      $count = $refSpec->getReferenceCount($this);
      if ($count['count'] != 0) {
        $counts[] = $count;
      }
    }

    foreach (CRM_Core_Component::getEnabledComponents() as $component) {
      /** @var $component CRM_Core_Component_Info */
      $counts = array_merge($counts, $component->getReferenceCounts($this));
    }
    CRM_Utils_Hook::referenceCounts($this, $counts);

    return $counts;
  }

  /**
   * List all tables which have hard foreign keys to this table.
   *
   * For now, this returns a description of every entity_id/entity_table
   * reference.
   * TODO: filter dynamic entity references on the $tableName, based on
   * schema metadata in dynamicForeignKey which enumerates a restricted
   * set of possible entity_table's.
   *
   * @param string $tableName table referred to
   *
   * @return array structure of table and column, listing every table with a
   * foreign key reference to $tableName, and the column where the key appears.
   */
  static function getReferencesToTable($tableName) {
    $refsFound = array();
    foreach (CRM_Core_DAO_AllCoreTables::getClasses() as $daoClassName) {
      $links = $daoClassName::getReferenceColumns();
      $daoTableName = $daoClassName::getTableName();

      foreach ($links as $refSpec) {
        /** @var $refSpec CRM_Core_Reference_Interface */
        if ($refSpec->matchesTargetTable($tableName)) {
          $refsFound[] = $refSpec;
        }
      }
    }
    return $refsFound;
  }

  /**
   * Lookup the value of a MySQL global configuration variable.
   *
   * @param string $name e.g. "thread_stack"
   * @param mixed $default
   * @return mixed
   */
  public static function getGlobalSetting($name, $default = NULL) {
    // Alternatively, SELECT @@GLOBAL.thread_stack, but
    // that has been reported to fail under MySQL 5.0 for OS X
    $escapedName = self::escapeString($name);
    $dao = CRM_Core_DAO::executeQuery("SHOW VARIABLES LIKE '$escapedName'");
    if ($dao->fetch()) {
      return $dao->Value;
    }
    else {
      return $default;
    }
  }

  /**
   * Get options for the called BAO object's field.
   * This function can be overridden by each BAO to add more logic related to context.
   * The overriding function will generally call the lower-level CRM_Core_PseudoConstant::get
   *
   * @param string $fieldName
   * @param string $context : @see CRM_Core_DAO::buildOptionsContext
   * @param array $props : whatever is known about this bao object
   *
   * @return Array|bool
   */
  public static function buildOptions($fieldName, $context = NULL, $props = array()) {
    // If a given bao does not override this function
    $baoName = get_called_class();
    return CRM_Core_PseudoConstant::get($baoName, $fieldName, array(), $context);
  }

  /**
   * Populate option labels for this object's fields.
   *
   * @throws exception if called directly on the base class
   */
  public function getOptionLabels() {
    $fields = $this->fields();
    if ($fields === NULL) {
      throw new Exception ('Cannot call getOptionLabels on CRM_Core_DAO');
    }
    foreach ($fields as $field) {
      $name = CRM_Utils_Array::value('name', $field);
      if ($name && isset($this->$name)) {
        $label = CRM_Core_PseudoConstant::getLabel(get_class($this), $name, $this->$name);
        if ($label !== FALSE) {
          // Append 'label' onto the field name
          $labelName = $name . '_label';
          $this->$labelName = $label;
        }
      }
    }
  }

  /**
   * Provides documentation and validation for the buildOptions $context param
   *
   * @param String $context
   *
   * @throws Exception
   * @return array
   */
  public static function buildOptionsContext($context = NULL) {
    $contexts = array(
      'get' => "All options are returned, even if they are disabled. Labels are translated.",
      'create' => "Options are filtered appropriately for the object being created/updated. Labels are translated.",
      'search' => "Searchable options are returned. Labels are translated.",
      'validate' => "All options are returned, even if they are disabled. Machine names are used in place of labels.",
    );
    // Validation: enforce uniformity of this param
    if ($context !== NULL && !isset($contexts[$context])) {
      throw new Exception("'$context' is not a valid context for buildOptions.");
    }
    return $contexts;
  }

  /**
   * @param $fieldName
   * @return bool|array
   */
  function getFieldSpec($fieldName) {
    $fields = $this->fields();
    $fieldKeys = $this->fieldKeys();

    // Support "unique names" as well as sql names
    $fieldKey = $fieldName;
    if (empty($fields[$fieldKey])) {
      $fieldKey = CRM_Utils_Array::value($fieldName, $fieldKeys);
    }
    // If neither worked then this field doesn't exist. Return false.
    if (empty($fields[$fieldKey])) {
      return FALSE;
    }
    return $fields[$fieldKey];
  }

  /**
   * SQL version of api function to assign filters to the DAO based on the syntax
   * $field => array('IN' => array(4,6,9))
   * OR
   * $field => array('LIKE' => array('%me%))
   * etc
   *
   * @param $fieldName
   * @param $filter array filter to be applied indexed by operator
   * @param $type String type of field (not actually used - nor in api @todo )
   * @param $alias String alternative field name ('as') @todo- not actually used
   * @param bool $returnSanitisedArray return a sanitised array instead of a clause
   *  this is primarily so we can add filters @ the api level to the Query object based fields
   *
   * @throws Exception
   * @internal param string $fieldname name of fields
   * @todo a better solution would be for the query object to apply these filters based on the
   *  api supported format (but we don't want to risk breakage in alpha stage & query class is scary
   * @todo @time of writing only IN & NOT IN are supported for the array style syntax (as test is
   *  required to extend further & it may be the comments per above should be implemented. It may be
   *  preferable to not double-banger the return context next refactor of this - but keeping the attention
   *  in one place has some advantages as we try to extend this format
   *
   * @return NULL|string|array a string is returned if $returnSanitisedArray is not set, otherwise and Array or NULL
   *   depending on whether it is supported as yet
   */
  public static function createSQLFilter($fieldName, $filter, $type, $alias = NULL, $returnSanitisedArray = FALSE) {
    // http://issues.civicrm.org/jira/browse/CRM-9150 - stick with 'simple' operators for now
    // support for other syntaxes is discussed in ticket but being put off for now
    foreach ($filter as $operator => $criteria) {
      if (in_array($operator, self::acceptedSQLOperators())) {
        switch ($operator) {
          // unary operators
          case 'IS NULL':
          case 'IS NOT NULL':
            if(!$returnSanitisedArray) {
              return (sprintf('%s %s', $fieldName, $operator));
            }
            else{
              return (sprintf('%s %s ', $fieldName, $operator));
            }
            break;

          // ternary operators
          case 'BETWEEN':
          case 'NOT BETWEEN':
            if (empty($criteria[0]) || empty($criteria[1])) {
              throw new Exception("invalid criteria for $operator");
            }
            if(!$returnSanitisedArray) {
              return (sprintf('%s ' . $operator . ' "%s" AND "%s"', $fieldName, CRM_Core_DAO::escapeString($criteria[0]), CRM_Core_DAO::escapeString($criteria[1])));
            }
            else{
              return NULL;  // not yet implemented (tests required to implement)
            }
            break;

          // n-ary operators
          case 'IN':
          case 'NOT IN':
            if (empty($criteria)) {
              throw new Exception("invalid criteria for $operator");
            }
            $escapedCriteria = array_map(array(
              'CRM_Core_DAO',
              'escapeString'
            ), $criteria);
            if(!$returnSanitisedArray) {
              return (sprintf('%s %s ("%s")', $fieldName, $operator, implode('", "', $escapedCriteria)));
            }
            return $escapedCriteria;
            break;

          // binary operators

          default:
            if(!$returnSanitisedArray) {
              return(sprintf('%s %s "%s"', $fieldName, $operator, CRM_Core_DAO::escapeString($criteria)));
            }
            else{
              return NULL; // not yet implemented (tests required to implement)
            }
        }
      }
    }
  }

  /**
   * @see http://issues.civicrm.org/jira/browse/CRM-9150
   * support for other syntaxes is discussed in ticket but being put off for now
   * @return array
   */
  public static function acceptedSQLOperators() {
    return array('=', '<=', '>=', '>', '<', 'LIKE', "<>", "!=", "NOT LIKE", 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'IS NOT NULL', 'IS NULL');
  }

  /**
   * SQL has a limit of 64 characters on various names:
   * table name, trigger name, column name ...
   *
   * For custom groups and fields we generated names from user entered input
   * which can be longer than this length, this function helps with creating
   * strings that meet various criteria.
   *
   * @param string $string - the string to be shortened
   * @param int $length - the max length of the string
   *
   * @param bool $makeRandom
   *
   * @return string
   */
  public static function shortenSQLName($string, $length = 60, $makeRandom = FALSE) {
    // early return for strings that meet the requirements
    if (strlen($string) <= $length) {
      return $string;
    }

    // easy return for calls that dont need a randomized uniq string
    if (! $makeRandom) {
      return substr($string, 0, $length);
    }

    // the string is longer than the length and we need a uniq string
    // for the same tablename we need the same uniq string everytime
    // hence we use md5 on the string, which is not random
    // we'll append 8 characters to the end of the tableName
    $md5string = substr(md5($string), 0, 8);
    return substr($string, 0, $length - 8) . "_{$md5string}";
  }

  /**
   * @param $params
   */
  function setApiFilter(&$params) {}

}
