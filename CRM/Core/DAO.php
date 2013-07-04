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
 * Our base DAO class. All DAO classes should inherit from this class.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

require_once 'PEAR.php';
require_once 'DB/DataObject.php';

require_once 'CRM/Core/I18n.php';
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
  BULK_MAIL_INSERT_COUNT = 10;
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

  /**
   * Class constructor
   *
   * @return object
   * @access public
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
    $links = $this->links();
    if (empty($links)) {
      return;
    }

    $this->_connect();

    if (!isset($GLOBALS['_DB_DATAOBJECT']['LINKS'][$this->_database])) {
      $GLOBALS['_DB_DATAOBJECT']['LINKS'][$this->_database] = array();
    }

    if (!array_key_exists($this->__table, $GLOBALS['_DB_DATAOBJECT']['LINKS'][$this->_database])) {
      $GLOBALS['_DB_DATAOBJECT']['LINKS'][$this->_database][$this->__table] = $links;
    }
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
   * @access public
   *
   * @return array
   */
  function links() {
    return NULL;
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

  function table() {
    $fields = &$this->fields();

    $table = array();
    if ($fields) {
      foreach ($fields as $name => $value) {
        $table[$value['name']] = $value['type'];
        if (CRM_Utils_Array::value('required', $value)) {
          $table[$value['name']] += self::DB_DAO_NOTNULL;
        }
      }
    }

    // set the links
    $this->links();

    return $table;
  }

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
   * @param string $tableName
   *
   * @return boolean true if CONSTRAINT keyword exists, false otherwise
   */
  function schemaRequiresRebuilding($tables = array("civicrm_contact")) {
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
      CRM_Core_Error::ignoreException();
    }

    $result = $dao->query($queryStr, $i18nRewrite);

    if ($trapException) {
      CRM_Core_Error::setCallback();
    }

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
   *
   * @return string the result of the query
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
            if (isset($item[2]) &&
              $item[2]
            ) {
              $item[0] = "'%{$item[0]}%'";
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
      if (CRM_Utils_Array::value('prefix', $fieldsFix)) {
        $fieldsToPrefix = $fieldsFix['prefix'];
      }
      if (CRM_Utils_Array::value('suffix', $fieldsFix)) {
        $fieldsToSuffix = $fieldsFix['suffix'];
      }
      if (CRM_Utils_Array::value('replace', $fieldsFix)) {
        $fieldsToReplace = $fieldsFix['replace'];
      }

      foreach ($fields as $name => $value) {
        if ($name == 'id' || $value['name'] == 'id') {
          // copy everything but the id!
          continue;
        }

        $dbName = $value['name'];
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

        if (substr($name, -5) == '_date' ||
          substr($name, -10) == '_date_time'
        ) {
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
   * @param string $daoName  name of the dao object
   * @param array  $params   (reference ) an assoc array of name/value pairs
   * @param array  $defaults (reference ) an assoc array to hold the flattened values
   * @param array  $returnProperities     an assoc array of fields that need to be returned, eg array( 'first_name', 'last_name')
   *
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

  //Creates a test object, including any required objects it needs via recursion
  //createOnly: only create in database, do not store or return the objects (useful for perf testing)
  //ONLY USE FOR TESTING
  static function createTestObject(
    $daoName,
    $params = array(),
    $numObjects = 1,
    $createOnly = FALSE
  ) {
    static $counter = 0;
    CRM_Core_DAO::$_testEntitiesToSkip = array(
      'CRM_Core_DAO_Worldregion',
      'CRM_Core_DAO_StateProvince',
      'CRM_Core_DAO_Country',
      'CRM_Core_DAO_Domain',
    );

    require_once (str_replace('_', DIRECTORY_SEPARATOR, $daoName) . ".php");

    for ($i = 0; $i < $numObjects; ++$i) {

      ++$counter;
      $object   = new $daoName ( );

      $fields = &$object->fields();
      foreach ($fields as $name => $value) {
        $dbName = $value['name'];
        if($dbName == 'contact_sub_type' && empty($params['contact_sub_type'])){
          //coming up with a rule to set this is too complex let's not set it
          continue;
        }
        $FKClassName = CRM_Utils_Array::value('FKClassName', $value);
        $required = CRM_Utils_Array::value('required', $value);
        if (CRM_Utils_Array::value($dbName, $params) !== NULL && !is_array($params[$dbName])) {
          $object->$dbName = $params[$dbName];
        }

        elseif ($dbName != 'id') {
          if ($FKClassName != NULL) {
            //skip the FK if it is not required
            // if it's contact id we should create even if not required
            // we'll have a go @ fetching first though
            if (!$required && $dbName != 'contact_id') {
              $fkDAO = new $FKClassName;
              if($fkDAO->find(TRUE)){
                $object->$dbName = $fkDAO->id;
              }
              unset($fkDAO);
              continue;
            }
            if(in_array($FKClassName, CRM_Core_DAO::$_testEntitiesToSkip)){
              $depObject = new $FKClassName();
              $depObject->find(TRUE);
            } elseif ($daoName == 'CRM_Member_DAO_MembershipType' && $name == 'member_of_contact_id') {
              // FIXME: the fields() metadata is not specific enough
              $depObject = CRM_Core_DAO::createTestObject($FKClassName, array('contact_type' => 'Organization'));
            }else{
            //if it is required we need to generate the dependency object first
              $depObject = CRM_Core_DAO::createTestObject($FKClassName, CRM_Utils_Array::value($dbName, $params, 1));
            }
            $object->$dbName = $depObject->id;
            unset($depObject);

            continue;
          }
          $constant = CRM_Utils_Array::value('pseudoconstant', $value);
          if (!empty($constant)) {
            $constantValues = CRM_Utils_PseudoConstant::getConstant($constant['name']);
            if (!empty($constantValues)) {
              $constantOptions = array_keys($constantValues);
              $object->$dbName = $constantOptions[0];
            }
            continue;
          }
          $enum = CRM_Utils_Array::value('enumValues', $value);
          if (!empty($enum)) {
            $options = explode(',', $enum);
            $object->$dbName = $options[0];
            continue;
          }
          switch ($value['type']) {
            case CRM_Utils_Type::T_INT:
            case CRM_Utils_Type::T_FLOAT:
            case CRM_Utils_Type::T_MONEY:
              $object->$dbName = $counter;
              break;

            case CRM_Utils_Type::T_BOOL:
            case CRM_Utils_Type::T_BOOLEAN:
              if (isset($value['default'])) {
                $object->$dbName = $value['default'];
              }
              elseif ($value['name'] == 'is_deleted' || $value['name'] == 'is_test') {
                $object->$dbName = 0;
              }
              else {
                $object->$dbName = 1;
              }
              break;

            case CRM_Utils_Type::T_DATE:
            case CRM_Utils_Type::T_TIMESTAMP:
              $object->$dbName = '19700101';
              break;

            case CRM_Utils_Type::T_TIME:
              CRM_Core_Error::fatal('T_TIME shouldnt be used.');
              //$object->$dbName='000000';
              //break;
            case CRM_Utils_Type::T_CCNUM:
              $object->$dbName = '4111 1111 1111 1111';
              break;

            case CRM_Utils_Type::T_URL:
              $object->$dbName = 'http://www.civicrm.org';
              break;

            case CRM_Utils_Type::T_STRING:
            case CRM_Utils_Type::T_BLOB:
            case CRM_Utils_Type::T_MEDIUMBLOB:
            case CRM_Utils_Type::T_TEXT:
            case CRM_Utils_Type::T_LONGTEXT:
            case CRM_Utils_Type::T_EMAIL:
            default:
              if (isset($value['enumValues'])) {
                if (isset($value['default'])) {
                  $object->$dbName = $value['default'];
                }
                else {
                  if (is_array($value['enumValues'])) {
                    $object->$dbName = $value['enumValues'][0];
                  }
                  else {
                    $defaultValues = explode(',', $value['enumValues']);
                    $object->$dbName = $defaultValues[0];
                  }
                }
              }
              else {
                $object->$dbName = $dbName . '_' . $counter;
                $maxlength = CRM_Utils_Array::value('maxlength', $value);
                if ($maxlength > 0 && strlen($object->$dbName) > $maxlength) {
                  $object->$dbName = substr($object->$dbName, 0, $value['maxlength']);
                }
              }
          }
        }
      }
      $object->save();

      if (!$createOnly) {

        $objects[$i] = $object;

      }
      else unset($object);
    }

    if ($createOnly) {

      return;

    }
    elseif ($numObjects == 1) {  return $objects[0];}
    else return $objects;
  }

  //deletes the this object plus any dependent objects that are associated with it
  //ONLY USE FOR TESTING

  static function deleteTestObjects($daoName, $params = array(
    )) {

    $object = new $daoName ( );
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

  static function checkTriggerViewPermission($view = TRUE, $trigger = TRUE) {
    // test for create view and trigger permissions and if allowed, add the option to go multilingual
    // and logging
    // I'm not sure why we use the getStaticProperty for an error, rather than checking for DB_Error
    CRM_Core_Error::ignoreException();
    $dao = new CRM_Core_DAO();
    if ($view) {
      $dao->query('CREATE OR REPLACE VIEW civicrm_domain_view AS SELECT * FROM civicrm_domain');
      if (PEAR::getStaticProperty('DB_DataObject', 'lastError')) {
        CRM_Core_Error::setCallback();
        return FALSE;
      }
    }

    if ($trigger) {
      $result = $dao->query('CREATE TRIGGER civicrm_domain_trigger BEFORE INSERT ON civicrm_domain FOR EACH ROW BEGIN END');
      if (PEAR::getStaticProperty('DB_DataObject', 'lastError') || is_a($result, 'DB_Error')) {
        CRM_Core_Error::setCallback();
        if ($view) {
          $dao->query('DROP VIEW IF EXISTS civicrm_domain_view');
        }
        return FALSE;
      }

      $dao->query('DROP TRIGGER IF EXISTS civicrm_domain_trigger');
      if (PEAR::getStaticProperty('DB_DataObject', 'lastError')) {
        CRM_Core_Error::setCallback();
        if ($view) {
          $dao->query('DROP VIEW IF EXISTS civicrm_domain_view');
        }
        return FALSE;
      }
    }

    if ($view) {
      $dao->query('DROP VIEW IF EXISTS civicrm_domain_view');
      if (PEAR::getStaticProperty('DB_DataObject', 'lastError')) {
        CRM_Core_Error::setCallback();
        return FALSE;
      }
    }
    CRM_Core_Error::setCallback();

    return TRUE;
  }

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
    * @see CRM-9716
    */
  static function triggerRebuild($tableName = NULL) {
    $info = array();

    $logging = new CRM_Logging_Schema;
    $logging->triggerInfo($info, $tableName);

    CRM_Core_I18n_Schema::triggerInfo($info, $tableName);
    CRM_Contact_BAO_Contact::triggerInfo($info, $tableName);

    CRM_Utils_Hook::triggerInfo($info, $tableName);

    // drop all existing triggers on all tables
    $logging->dropTriggers($tableName);

    // now create the set of new triggers
    self::createTriggers($info);
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
          $triggerName = "{$tableName}_{$whenName}_{$eventName}";
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
   * Check the tables sent in, to see if there are any tables where there is a value for
   * a column
   *
   * This is typically used when we want to delete a row, but want to avoid the FK errors
   * that it might cause due to this being a required FK
   *
   * @param array an array of values (tableName, columnName)
   * @param array the parameter array with the value and type
   * @param array (reference) the tables which had an entry for this value
   *
   * @return boolean true if no value exists in all the tables
   * @static
   */
  public static function doesValueExistInTable(&$tables, $params, &$errors) {
    $errors = array();
    foreach ($tables as $table) {
      $sql = "SELECT count(*) FROM {$table['table']} WHERE {$table['column']} = %1";
      $count = self::singleValueQuery($sql, $params);
      if ($count > 0) {
        $errors[$table['table']] = $count;
      }
    }

    return (empty($errors)) ? FALSE : TRUE;
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
}

