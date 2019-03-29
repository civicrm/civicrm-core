<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Base Database Access Object class.
 *
 * All DAO classes should inherit from this class.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

if (!defined('DB_DSN_MODE')) {
  define('DB_DSN_MODE', 'auto');
}

require_once 'PEAR.php';
require_once 'DB/DataObject.php';

require_once 'CRM/Core/I18n.php';

/**
 * Class CRM_Core_DAO
 */
class CRM_Core_DAO extends DB_DataObject {

  /**
   * How many times has this instance been cloned.
   *
   * @var int
   */
  protected $resultCopies = 0;

  /**
   * @var null
   * @deprecated
   */
  static $_nullObject = NULL;
  /**
   * @var array
   * @deprecated
   */
  static $_nullArray = array();

  static $_dbColumnValueCache = NULL;
  const NOT_NULL = 1, IS_NULL = 2,
    DB_DAO_NOTNULL = 128,
    VALUE_SEPARATOR = "",
    BULK_INSERT_COUNT = 200,
    BULK_INSERT_HIGH_COUNT = 200,
    QUERY_FORMAT_WILDCARD = 1,
    QUERY_FORMAT_NO_QUOTES = 2,

    /**
     * Serialized string separated by and bookended with VALUE_SEPARATOR
     */
    SERIALIZE_SEPARATOR_BOOKEND = 1,
    /**
     * @deprecated format separated by VALUE_SEPARATOR
     */
    SERIALIZE_SEPARATOR_TRIMMED = 2,
    /**
     * Recommended serialization format
     */
    SERIALIZE_JSON = 3,
    /**
     * @deprecated format using php serialize()
     */
    SERIALIZE_PHP = 4,
    /**
     * Comma separated string, no quotes, no spaces
     */
    SERIALIZE_COMMA = 5;

  /**
   * Define entities that shouldn't be created or deleted when creating/ deleting
   * test objects - this prevents world regions, countries etc from being added / deleted
   * @var array
   */
  static $_testEntitiesToSkip = array();
  /**
   * The factory class for this application.
   * @var object
   */
  static $_factory = NULL;

  static $_checkedSqlFunctionsExist = FALSE;

  /**
   * https://issues.civicrm.org/jira/browse/CRM-17748
   * internal variable for DAO to hold per-query settings
   */
  protected $_options = array();

  /**
   * Class constructor.
   *
   * @return \CRM_Core_DAO
   */
  public function __construct() {
    $this->initialize();
    $this->__table = $this->getTableName();
  }

  public function __clone() {
    if (!empty($this->_DB_resultid)) {
      $this->resultCopies++;
    }
  }

  /**
   * Class destructor.
   */
  public function __destruct() {
    if ($this->resultCopies === 0) {
      $this->free();
    }
    $this->resultCopies--;
  }

  /**
   * Empty definition for virtual function.
   */
  public static function getTableName() {
    return NULL;
  }

  /**
   * Initialize the DAO object.
   *
   * @param string $dsn
   *   The database connection string.
   */
  public static function init($dsn) {
    Civi::$statics[__CLASS__]['init'] = 1;
    $options = &PEAR::getStaticProperty('DB_DataObject', 'options');
    $options['database'] = $dsn;
    if (defined('CIVICRM_DAO_DEBUG')) {
      self::DebugLevel(CIVICRM_DAO_DEBUG);
    }
    $factory = new CRM_Contact_DAO_Factory();
    CRM_Core_DAO::setFactory($factory);
    $currentModes = CRM_Utils_SQL::getSqlModes();
    if (CRM_Utils_Constant::value('CIVICRM_MYSQL_STRICT', CRM_Utils_System::isDevelopment())) {
      if (CRM_Utils_SQL::supportsFullGroupBy() && !in_array('ONLY_FULL_GROUP_BY', $currentModes) && CRM_Utils_SQL::isGroupByModeInDefault()) {
        $currentModes[] = 'ONLY_FULL_GROUP_BY';
      }
      if (!in_array('STRICT_TRANS_TABLES', $currentModes)) {
        $currentModes = array_merge(array('STRICT_TRANS_TABLES'), $currentModes);
      }
      CRM_Core_DAO::executeQuery("SET SESSION sql_mode = %1", array(1 => array(implode(',', $currentModes), 'String')));
    }
    CRM_Core_DAO::executeQuery('SET NAMES utf8');
    CRM_Core_DAO::executeQuery('SET @uniqueID = %1', array(1 => array(CRM_Utils_Request::id(), 'String')));
  }

  /**
   * @return DB_common
   */
  public static function getConnection() {
    global $_DB_DATAOBJECT;
    $dao = new CRM_Core_DAO();
    return $_DB_DATAOBJECT['CONNECTIONS'][$dao->_database_dsn_md5];
  }

  /**
   * Disables usage of the ONLY_FULL_GROUP_BY Mode if necessary
   */
  public static function disableFullGroupByMode() {
    $currentModes = CRM_Utils_SQL::getSqlModes();
    if (in_array('ONLY_FULL_GROUP_BY', $currentModes) && CRM_Utils_SQL::isGroupByModeInDefault()) {
      $key = array_search('ONLY_FULL_GROUP_BY', $currentModes);
      unset($currentModes[$key]);
      CRM_Core_DAO::executeQuery("SET SESSION sql_mode = %1", array(1 => array(implode(',', $currentModes), 'String')));
    }
  }

  /**
   * Re-enables ONLY_FULL_GROUP_BY sql_mode as necessary..
   */
  public static function reenableFullGroupByMode() {
    $currentModes = CRM_Utils_SQL::getSqlModes();
    if (!in_array('ONLY_FULL_GROUP_BY', $currentModes) && CRM_Utils_SQL::isGroupByModeInDefault()) {
      $currentModes[] = 'ONLY_FULL_GROUP_BY';
      CRM_Core_DAO::executeQuery("SET SESSION sql_mode = %1", array(1 => array(implode(',', $currentModes), 'String')));
    }
  }

  /**
   * @param string $fieldName
   * @param $fieldDef
   * @param array $params
   */
  protected function assignTestFK($fieldName, $fieldDef, $params) {
    $required = CRM_Utils_Array::value('required', $fieldDef);
    $FKClassName = CRM_Utils_Array::value('FKClassName', $fieldDef);
    $dbName = $fieldDef['name'];
    $daoName = str_replace('_BAO_', '_DAO_', get_class($this));

    // skip the FK if it is not required
    // if it's contact id we should create even if not required
    // we'll have a go @ fetching first though
    // we WILL create campaigns though for so tests with a campaign pseudoconstant will complete
    if ($FKClassName === 'CRM_Campaign_DAO_Campaign' && $daoName != $FKClassName) {
      $required = TRUE;
    }
    if (!$required && $dbName != 'contact_id') {
      $fkDAO = new $FKClassName();
      if ($fkDAO->find(TRUE)) {
        $this->$dbName = $fkDAO->id;
      }
      $fkDAO->free();
    }

    elseif (in_array($FKClassName, CRM_Core_DAO::$_testEntitiesToSkip)) {
      $depObject = new $FKClassName();
      $depObject->find(TRUE);
      $this->$dbName = $depObject->id;
      $depObject->free();
    }
    elseif ($daoName == 'CRM_Member_DAO_MembershipType' && $fieldName == 'member_of_contact_id') {
      // FIXME: the fields() metadata is not specific enough
      $depObject = CRM_Core_DAO::createTestObject($FKClassName, array('contact_type' => 'Organization'));
      $this->$dbName = $depObject->id;
      $depObject->free();
    }
    else {
      //if it is required we need to generate the dependency object first
      $depObject = CRM_Core_DAO::createTestObject($FKClassName, CRM_Utils_Array::value($dbName, $params, 1));
      $this->$dbName = $depObject->id;
      $depObject->free();
    }
  }

  /**
   * Generate and assign an arbitrary value to a field of a test object.
   *
   * @param string $fieldName
   * @param array $fieldDef
   * @param int $counter
   *   The globally-unique ID of the test object.
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
        case CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME:
          $this->$dbName = '19700101';
          if ($dbName == 'end_date') {
            // put this in the future
            $this->$dbName = '20200101';
          }
          break;

        case CRM_Utils_Type::T_TIMESTAMP:
          $this->$dbName = '19700201000000';
          break;

        case CRM_Utils_Type::T_TIME:
          CRM_Core_Error::fatal("T_TIME shouldn't be used.");
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
   * Reset the DAO object.
   *
   * DAO is kinda crappy in that there is an unwritten rule of one query per DAO.
   *
   * We attempt to get around this crappy restriction by resetting some of DAO's internal fields. Use this with caution
   */
  public function reset() {

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
   * @param string $tableName
   *
   * @return string
   */
  public static function getLocaleTableName($tableName) {
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
   * @param string $query
   *   The SQL query for execution.
   * @param bool $i18nRewrite
   *   Whether to rewrite the query.
   *
   * @return object
   *   the current DAO object after the query execution
   */
  public function query($query, $i18nRewrite = TRUE) {
    // rewrite queries that should use $dbLocale-based views for multi-language installs
    global $dbLocale, $_DB_DATAOBJECT;

    if (empty($_DB_DATAOBJECT['CONNECTIONS'][$this->_database_dsn_md5])) {
      // Will force connection to be populated per CRM-20541.
      new CRM_Core_DAO();
    }

    $conn = &$_DB_DATAOBJECT['CONNECTIONS'][$this->_database_dsn_md5];
    $orig_options = $conn->options;
    $this->_setDBOptions($this->_options);

    if ($i18nRewrite and $dbLocale) {
      $query = CRM_Core_I18n_Schema::rewriteQuery($query);
    }

    $ret = parent::query($query);

    $this->_setDBOptions($orig_options);
    return $ret;
  }

  /**
   * Static function to set the factory instance for this class.
   *
   * @param object $factory
   *   The factory application object.
   */
  public static function setFactory(&$factory) {
    self::$_factory = &$factory;
  }

  /**
   * Factory method to instantiate a new object from a table name.
   *
   * @param string $table
   * @return \DataObject|\PEAR_Error
   */
  public function factory($table = '') {
    if (!isset(self::$_factory)) {
      return parent::factory($table);
    }

    return self::$_factory->create($table);
  }

  /**
   * Initialization for all DAO objects. Since we access DB_DO programatically
   * we need to set the links manually.
   */
  public function initialize() {
    $this->_connect();
    if (empty(Civi::$statics[__CLASS__]['init'])) {
      // CRM_Core_DAO::init() must be called before CRM_Core_DAO->initialize().
      // This occurs very early in bootstrap - error handlers may not be wired up.
      echo "Inconsistent system initialization sequence. Premature access of (" . get_class($this) . ")";
      CRM_Utils_System::civiExit();
    }
  }

  /**
   * Defines the default key as 'id'.
   *
   * @return array
   */
  public function keys() {
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
   *
   * @return array
   */
  public function sequenceKey() {
    static $sequenceKeys;
    if (!isset($sequenceKeys)) {
      $sequenceKeys = array('id', TRUE);
    }
    return $sequenceKeys;
  }

  /**
   * Returns list of FK relationships.
   *
   *
   * @return array
   *   Array of CRM_Core_Reference_Interface
   */
  public static function getReferenceColumns() {
    return array();
  }

  /**
   * Returns all the column names of this table.
   *
   *
   * @return array
   */
  public static function &fields() {
    $result = NULL;
    return $result;
  }

  /**
   * Get/set an associative array of table columns
   *
   * @return array
   *   (associative)
   */
  public function table() {
    $fields = $this->fields();

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
   * Save DAO object.
   *
   * @param bool $hook
   *
   * @return CRM_Core_DAO
   */
  public function save($hook = TRUE) {
    if (!empty($this->id)) {
      $this->update();

      if ($hook) {
        $event = new \Civi\Core\DAO\Event\PostUpdate($this);
        \Civi::service('dispatcher')->dispatch("civi.dao.postUpdate", $event);
      }
    }
    else {
      $this->insert();

      if ($hook) {
        $event = new \Civi\Core\DAO\Event\PostUpdate($this);
        \Civi::service('dispatcher')->dispatch("civi.dao.postInsert", $event);
      }
    }
    $this->free();

    if ($hook) {
      CRM_Utils_Hook::postSave($this);
    }

    return $this;
  }

  /**
   * Deletes items from table which match current objects variables.
   *
   * Returns the true on success
   *
   * for example
   *
   * Designed to be extended
   *
   * $object = new mytable();
   * $object->ID=123;
   * echo $object->delete(); // builds a conditon
   *
   * $object = new mytable();
   * $object->whereAdd('age > 12');
   * $object->limit(1);
   * $object->orderBy('age DESC');
   * $object->delete(true); // dont use object vars, use the conditions, limit and order.
   *
   * @param bool $useWhere (optional) If DB_DATAOBJECT_WHEREADD_ONLY is passed in then
   *             we will build the condition only using the whereAdd's.  Default is to
   *             build the condition only using the object parameters.
   *
   *     * @return mixed Int (No. of rows affected) on success, false on failure, 0 on no data affected
   */
  public function delete($useWhere = FALSE) {
    $preEvent = new \Civi\Core\DAO\Event\PreDelete($this);
    \Civi::service('dispatcher')->dispatch("civi.dao.preDelete", $preEvent);

    $result = parent::delete($useWhere);

    $event = new \Civi\Core\DAO\Event\PostDelete($this, $result);
    \Civi::service('dispatcher')->dispatch("civi.dao.postDelete", $event);
    $this->free();

    return $result;
  }

  /**
   * @param bool $created
   */
  public function log($created = FALSE) {
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

    $dao = new CRM_Core_DAO_Log();
    $dao->entity_table = $this->getTableName();
    $dao->entity_id = $this->id;
    $dao->modified_id = $cid;
    $dao->modified_date = date("YmdHis");
    $dao->insert();
  }

  /**
   * Given an associative array of name/value pairs, extract all the values
   * that belong to this object and initialize the object with said values
   *
   * @param array $params
   *   (reference ) associative array of name/value pairs.
   * @param bool $serializeArrays
   *   Should arrays that are passed in be serialised according to the metadata.
   *   Eventually this should be always true / gone, but in the interests of caution
   *   it is being grandfathered in. In general an array is not valid on the DAO
   *   but there may be instances where this function is called & then some handling
   *   takes place on the would-be array.
   *
   * @return bool
   *   Did we copy all null values into the object
   */
  public function copyValues(&$params, $serializeArrays = FALSE) {
    $fields = $this->fields();
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
        elseif ($serializeArrays && is_array($pValue) && !empty($value['serialize'])) {
          $this->$dbName = CRM_Core_DAO::serializeField($pValue, $value['serialize']);
          $allNull = FALSE;
        }
        else {
          if (!$serializeArrays && is_array($pValue) && !empty($value['serialize'])) {
            Civi::log()->warning(ts('use copyParams to serialize arrays (' . __CLASS__ . '.' . $name . ')'), ['civi.tag' => 'deprecated']);
          }
          $maxLength = CRM_Utils_Array::value('maxlength', $value);
          if (!is_array($pValue) && $maxLength && mb_strlen($pValue) > $maxLength
            && empty($value['pseudoconstant'])
          ) {
            Civi::log()->warning(ts('A string for field $dbName has been truncated. The original string was %1', [CRM_Utils_Type::escape($pValue, 'String')]));
            // The string is too long - what to do what to do? Well losing data is generally bad so lets' truncate
            $pValue = CRM_Utils_String::ellipsify($pValue, $maxLength);
          }
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
   * @param object $object
   *   The object that we are extracting data from.
   * @param array $values
   *   (reference ) associative array of name/value pairs.
   */
  public static function storeValues(&$object, &$values) {
    $fields = $object->fields();
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
   * Create an attribute for this specific field. We only do this for strings and text
   *
   * @param array $field
   *   The field under task.
   *
   * @return array|null
   *   the attributes for the object
   */
  public static function makeAttribute($field) {
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

        $attributes = array();
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
   * Get the size and maxLength attributes for this text field.
   * (or for all text fields) in the DAO object.
   *
   * @param string $class
   *   Name of DAO class.
   * @param string $fieldName
   *   Field that i'm interested in or null if.
   *                          you want the attributes for all DAO text fields
   *
   * @return array
   *   assoc array of name => attribute pairs
   */
  public static function getAttribute($class, $fieldName = NULL) {
    $object = new $class();
    $fields = $object->fields();
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
   * Check if there is a record with the same name in the db.
   *
   * @param string $value
   *   The value of the field we are checking.
   * @param string $daoName
   *   The dao object name.
   * @param string $daoID
   *   The id of the object being updated. u can change your name.
   *                          as long as there is no conflict
   * @param string $fieldName
   *   The name of the field in the DAO.
   *
   * @param string $domainID
   *   The id of the domain.  Object exists only for the given domain.
   *
   * @return bool
   *   true if object exists
   */
  public static function objectExists($value, $daoName, $daoID, $fieldName = 'name', $domainID = NULL) {
    $object = new $daoName();
    $object->$fieldName = $value;
    if ($domainID) {
      $object->domain_id = $domainID;
    }

    if ($object->find(TRUE)) {
      return ($daoID && $object->id == $daoID) ? TRUE : FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Check if there is a given column in a specific table.
   *
   * @deprecated
   * @see CRM_Core_BAO_SchemaHandler::checkIfFieldExists
   *
   * @param string $tableName
   * @param string $columnName
   * @param bool $i18nRewrite
   *   Whether to rewrite the query on multilingual setups.
   *
   * @return bool
   *   true if exists, else false
   */
  public static function checkFieldExists($tableName, $columnName, $i18nRewrite = TRUE) {
    return CRM_Core_BAO_SchemaHandler::checkIfFieldExists($tableName, $columnName, $i18nRewrite);
  }

  /**
   * Scans all the tables using a slow query and table name.
   *
   * @return array
   */
  public static function getTableNames() {
    $dao = CRM_Core_DAO::executeQuery(
      "SELECT TABLE_NAME
       FROM information_schema.TABLES
       WHERE TABLE_SCHEMA = '" . CRM_Core_DAO::getDatabaseName() . "'
         AND TABLE_NAME LIKE 'civicrm_%'
         AND TABLE_NAME NOT LIKE 'civicrm_import_job_%'
         AND TABLE_NAME NOT LIKE '%_temp%'
      ");

    while ($dao->fetch()) {
      $values[] = $dao->TABLE_NAME;
    }
    return $values;
  }

  /**
   * @param int $maxTablesToCheck
   *
   * @return bool
   */
  public static function isDBMyISAM($maxTablesToCheck = 10) {
    return CRM_Core_DAO::singleValueQuery(
      "SELECT count(*)
       FROM information_schema.TABLES
       WHERE ENGINE = 'MyISAM'
         AND TABLE_SCHEMA = '" . CRM_Core_DAO::getDatabaseName() . "'
         AND TABLE_NAME LIKE 'civicrm_%'
         AND TABLE_NAME NOT LIKE 'civicrm_import_job_%'
         AND TABLE_NAME NOT LIKE '%_temp%'
         AND TABLE_NAME NOT LIKE 'civicrm_tmp_%'
      ");
  }

  /**
   * Get the name of the CiviCRM database.
   *
   * @return string
   */
  public static function getDatabaseName() {
    $daoObj = new CRM_Core_DAO();
    return $daoObj->database();
  }

  /**
   * Checks if a constraint exists for a specified table.
   *
   * @param string $tableName
   * @param string $constraint
   *
   * @return bool
   *   true if constraint exists, false otherwise
   */
  public static function checkConstraintExists($tableName, $constraint) {
    static $show = array();

    if (!array_key_exists($tableName, $show)) {
      $query = "SHOW CREATE TABLE $tableName";
      $dao = CRM_Core_DAO::executeQuery($query);

      if (!$dao->fetch()) {
        CRM_Core_Error::fatal();
      }

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
   *
   * @return bool
   *   true if CONSTRAINT keyword exists, false otherwise
   */
  public static function schemaRequiresRebuilding($tables = array("civicrm_contact")) {
    $show = array();
    foreach ($tables as $tableName) {
      if (!array_key_exists($tableName, $show)) {
        $query = "SHOW CREATE TABLE $tableName";
        $dao = CRM_Core_DAO::executeQuery($query);

        if (!$dao->fetch()) {
          CRM_Core_Error::fatal();
        }

        $show[$tableName] = $dao->Create_Table;
      }

      $result = preg_match("/\bCONSTRAINT\b\s/i", $show[$tableName]) ? TRUE : FALSE;
      if ($result == TRUE) {
        continue;
      }
      else {
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
   * @return bool
   *   true if in format, false otherwise
   */
  public static function checkFKConstraintInFormat($tableName, $columnName) {
    static $show = array();

    if (!array_key_exists($tableName, $show)) {
      $query = "SHOW CREATE TABLE $tableName";
      $dao = CRM_Core_DAO::executeQuery($query);

      if (!$dao->fetch()) {
        CRM_Core_Error::fatal();
      }

      $show[$tableName] = $dao->Create_Table;
    }
    $constraint = "`FK_{$tableName}_{$columnName}`";
    $pattern = "/\bCONSTRAINT\b\s+%s\s+\bFOREIGN\s+KEY\b\s/i";
    return preg_match(sprintf($pattern, $constraint), $show[$tableName]) ? TRUE : FALSE;
  }

  /**
   * Check whether a specific column in a specific table has always the same value.
   *
   * @param string $tableName
   * @param string $columnName
   * @param string $columnValue
   *
   * @return bool
   *   true if the value is always $columnValue, false otherwise
   */
  public static function checkFieldHasAlwaysValue($tableName, $columnName, $columnValue) {
    $query = "SELECT * FROM $tableName WHERE $columnName != '$columnValue'";
    $dao = CRM_Core_DAO::executeQuery($query);
    $result = $dao->fetch() ? FALSE : TRUE;
    return $result;
  }

  /**
   * Check whether a specific column in a specific table is always NULL.
   *
   * @param string $tableName
   * @param string $columnName
   *
   * @return bool
   *   true if if the value is always NULL, false otherwise
   */
  public static function checkFieldIsAlwaysNull($tableName, $columnName) {
    $query = "SELECT * FROM $tableName WHERE $columnName IS NOT NULL";
    $dao = CRM_Core_DAO::executeQuery($query);
    $result = $dao->fetch() ? FALSE : TRUE;
    return $result;
  }

  /**
   * Check if there is a given table in the database.
   *
   * @param string $tableName
   *
   * @return bool
   *   true if exists, else false
   */
  public static function checkTableExists($tableName) {
    $query = "
SHOW TABLES
LIKE %1
";
    $params = array(1 => array($tableName, 'String'));

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $result = $dao->fetch() ? TRUE : FALSE;
    return $result;
  }

  /**
   * @param $version
   *
   * @return bool
   */
  public function checkVersion($version) {
    $query = "
SELECT version
FROM   civicrm_domain
";
    $dbVersion = CRM_Core_DAO::singleValueQuery($query);
    return trim($version) == trim($dbVersion) ? TRUE : FALSE;
  }

  /**
   * Find a DAO object for the given ID and return it.
   *
   * @param int $id
   *   Id of the DAO object being searched for.
   *
   * @return CRM_Core_DAO
   *   Object of the type of the class that called this function.
   *
   * @throws Exception
   */
  public static function findById($id) {
    $object = new static();
    $object->id = $id;
    if (!$object->find(TRUE)) {
      throw new Exception("Unable to find a " . get_called_class() . " with id {$id}.");
    }
    return $object;
  }

  /**
   * Returns all results as array-encoded records.
   *
   * @return array
   */
  public function fetchAll() {
    $result = array();
    while ($this->fetch()) {
      $result[] = $this->toArray();
    }
    return $result;
  }

  /**
   * Return the results as PHP generator.
   *
   * @param string $type
   *   Whether the generator yields 'dao' objects or 'array's.
   */
  public function fetchGenerator($type = 'dao') {
    while ($this->fetch()) {
      switch ($type) {
        case 'dao':
          yield $this;
          break;

        case 'array':
          yield $this->toArray();
          break;

        default:
          throw new \RuntimeException("Invalid record type ($type)");
      }
    }
  }

  /**
   * Returns a singular value.
   *
   * @return mixed|NULL
   */
  public function fetchValue() {
    $result = $this->getDatabaseResult();
    $row = $result->fetchRow();
    $ret = NULL;
    if ($row) {
      $ret = $row[0];
    }
    $this->free();
    return $ret;
  }

  /**
   * Get all the result records as mapping between columns.
   *
   * @param string $keyColumn
   *   Ex: "name"
   * @param string $valueColumn
   *   Ex: "label"
   * @return array
   *   Ex: ["foo" => "The Foo Bar", "baz" => "The Baz Qux"]
   */
  public function fetchMap($keyColumn, $valueColumn) {
    $result = array();
    while ($this->fetch()) {
      $result[$this->{$keyColumn}] = $this->{$valueColumn};
    }
    return $result;
  }

  /**
   * Given a DAO name, a column name and a column value, find the record and GET the value of another column in that record
   *
   * @param string $daoName
   *   Name of the DAO (Example: CRM_Contact_DAO_Contact to retrieve value from a contact).
   * @param int $searchValue
   *   Value of the column you want to search by.
   * @param string $returnColumn
   *   Name of the column you want to GET the value of.
   * @param string $searchColumn
   *   Name of the column you want to search by.
   * @param bool $force
   *   Skip use of the cache.
   *
   * @return string|null
   *   Value of $returnColumn in the retrieved record
   */
  public static function getFieldValue($daoName, $searchValue, $returnColumn = 'name', $searchColumn = 'id', $force = FALSE) {
    if (
      empty($searchValue) ||
      trim(strtolower($searchValue)) == 'null'
    ) {
      // adding this here since developers forget to check for an id
      // or for the 'null' (which is a bad DAO kludge)
      // and hence we get the first value in the db
      CRM_Core_Error::fatal();
    }

    $cacheKey = "{$daoName}:{$searchValue}:{$returnColumn}:{$searchColumn}";
    if (self::$_dbColumnValueCache === NULL) {
      self::$_dbColumnValueCache = array();
    }

    if (!array_key_exists($cacheKey, self::$_dbColumnValueCache) || $force) {
      $object = new $daoName();
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
   * @param string $daoName
   *   Name of the DAO (Example: CRM_Contact_DAO_Contact to retrieve value from a contact).
   * @param int $searchValue
   *   Value of the column you want to search by.
   * @param string $setColumn
   *   Name of the column you want to SET the value of.
   * @param string $setValue
   *   SET the setColumn to this value.
   * @param string $searchColumn
   *   Name of the column you want to search by.
   *
   * @return bool
   *   true if we found and updated the object, else false
   */
  public static function setFieldValue($daoName, $searchValue, $setColumn, $setValue, $searchColumn = 'id') {
    $object = new $daoName();
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
   * Get sort string.
   *
   * @param array|object $sort either array or CRM_Utils_Sort
   * @param string $default
   *   Default sort value.
   *
   * @return string
   */
  public static function getSortString($sort, $default = NULL) {
    // check if sort is of type CRM_Utils_Sort
    if (is_a($sort, 'CRM_Utils_Sort')) {
      return $sort->orderBy();
    }

    $sortString = '';

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
   * Fetch object based on array of properties.
   *
   * @param string $daoName
   *   Name of the dao object.
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   * @param array $returnProperities
   *   An assoc array of fields that need to be returned, eg array( 'first_name', 'last_name').
   *
   * @return object
   *   an object of type referenced by daoName
   */
  public static function commonRetrieve($daoName, &$params, &$defaults, $returnProperities = NULL) {
    $object = new $daoName();
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
   * Delete the object records that are associated with this contact.
   *
   * @param string $daoName
   *   Name of the dao object.
   * @param int $contactId
   *   Id of the contact to delete.
   */
  public static function deleteEntityContact($daoName, $contactId) {
    $object = new $daoName();

    $object->entity_table = 'civicrm_contact';
    $object->entity_id = $contactId;
    $object->delete();
  }

  /**
   * Execute an unbuffered query.
   *
   * This is a wrapper around new functionality exposed with CRM-17748.
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
   * @return CRM_Core_DAO
   *   Object that points to an unbuffered result set
   */
  static public function executeUnbufferedQuery(
    $query,
    $params = array(),
    $abort = TRUE,
    $daoName = NULL,
    $freeDAO = FALSE,
    $i18nRewrite = TRUE,
    $trapException = FALSE
  ) {

    return self::executeQuery(
      $query,
      $params,
      $abort,
      $daoName,
      $freeDAO,
      $i18nRewrite,
      $trapException,
      array('result_buffering' => 0)
    );
  }

  /**
   * Execute a query.
   *
   * @param string $query
   *   Query to be executed.
   *
   * @param array $params
   * @param bool $abort
   * @param null $daoName
   * @param bool $freeDAO
   * @param bool $i18nRewrite
   * @param bool $trapException
   * @param array $options
   *
   * @return CRM_Core_DAO|object
   *   object that holds the results of the query
   *   NB - if this is defined as just returning a DAO phpstorm keeps pointing
   *   out all the properties that are not part of the DAO
   */
  public static function &executeQuery(
    $query,
    $params = array(),
    $abort = TRUE,
    $daoName = NULL,
    $freeDAO = FALSE,
    $i18nRewrite = TRUE,
    $trapException = FALSE,
    $options = array()
  ) {
    $queryStr = self::composeQuery($query, $params, $abort);

    if (!$daoName) {
      $dao = new CRM_Core_DAO();
    }
    else {
      $dao = new $daoName();
    }

    if ($trapException) {
      $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
    }

    if ($dao->isValidOption($options)) {
      $dao->setOptions($options);
    }

    $result = $dao->query($queryStr, $i18nRewrite);

    // since it is unbuffered, ($dao->N==0) is true.  This blocks the standard fetch() mechanism.
    if (CRM_Utils_Array::value('result_buffering', $options) === 0) {
      $dao->N = TRUE;
    }

    if (is_a($result, 'DB_Error')) {
      return $result;
    }

    if ($freeDAO ||
      preg_match('/^(insert|update|delete|create|drop|replace)/i', $queryStr)
    ) {
      // we typically do this for insert/update/delete statements OR if explicitly asked to
      // free the dao
    }
    return $dao;
  }

  /**
   * Wrapper to validate internal DAO options before passing to DB_mysql/DB_Common level
   *
   * @param array $options
   *
   * @return bool
   *   Provided options are valid
   */
  public function isValidOption($options) {
    $isValid = FALSE;
    $validOptions = array(
      'result_buffering',
      'persistent',
      'ssl',
      'portability',
    );

    if (empty($options)) {
      return $isValid;
    }

    foreach (array_keys($options) as $option) {
      if (!in_array($option, $validOptions)) {
        return FALSE;
      }
      $isValid = TRUE;
    }

    return $isValid;
  }

  /**
   * Execute a query and get the single result.
   *
   * @param string $query
   *   Query to be executed.
   * @param array $params
   * @param bool $abort
   * @param bool $i18nRewrite
   * @return string|null
   *   the result of the query if any
   *
   */
  public static function &singleValueQuery(
    $query,
    $params = array(),
    $abort = TRUE,
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
   * Compose the query by merging the parameters into it.
   *
   * @param string $query
   * @param array $params
   * @param bool $abort
   *
   * @return string
   * @throws Exception
   */
  public static function composeQuery($query, $params, $abort = TRUE) {
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
  public static function freeResult($ids = NULL) {
    global $_DB_DATAOBJECT;

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
        $_DB_DATAOBJECT['RESULTS'][$id]->free();
        unset($_DB_DATAOBJECT['RESULTS'][$id]);
      }

      if (isset($_DB_DATAOBJECT['RESULTFIELDS'][$id])) {
        unset($_DB_DATAOBJECT['RESULTFIELDS'][$id]);
      }
    }
  }

  /**
   * Make a shallow copy of an object and all the fields in the object.
   *
   * @param string $daoName
   *   Name of the dao.
   * @param array $criteria
   *   Array of all the fields & values.
   *   on which basis to copy
   * @param array $newData
   *   Array of all the fields & values.
   *   to be copied besides the other fields
   * @param string $fieldsFix
   *   Array of fields that you want to prefix/suffix/replace.
   * @param string $blockCopyOfDependencies
   *   Fields that you want to block from.
   *   getting copied
   *
   * @return CRM_Core_DAO
   *   the newly created copy of the object
   */
  public static function &copyGeneric($daoName, $criteria, $newData = NULL, $fieldsFix = NULL, $blockCopyOfDependencies = NULL) {
    $object = new $daoName();
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

      $newObject = new $daoName();

      $fields = $object->fields();
      if (!is_array($fieldsFix)) {
        $fieldsToPrefix = array();
        $fieldsToSuffix = array();
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
          $newObject->copyValues($newData);
        }
      }
      $newObject->save();
      if (!empty($newData['custom'])) {
        CRM_Core_BAO_CustomValueTable::store($newData['custom'], $newObject::getTableName(), $newObject->id);
      }
      CRM_Utils_Hook::post('create', CRM_Core_DAO_AllCoreTables::getBriefName($daoName), $newObject->id, $newObject);
    }

    return $newObject;
  }

  /**
   * Cascade update through related entities.
   *
   * @param string $daoName
   * @param $fromId
   * @param $toId
   * @param array $newData
   *
   * @return CRM_Core_DAO|null
   */
  public static function cascadeUpdate($daoName, $fromId, $toId, $newData = array()) {
    $object = new $daoName();
    $object->id = $fromId;

    if ($object->find(TRUE)) {
      $newObject = new $daoName();
      $newObject->id = $toId;

      if ($newObject->find(TRUE)) {
        $fields = $object->fields();
        foreach ($fields as $name => $value) {
          if ($name == 'id' || $value['name'] == 'id') {
            // copy everything but the id!
            continue;
          }

          $colName = $value['name'];
          $newObject->$colName = $object->$colName;

          if (substr($name, -5) == '_date' ||
            substr($name, -10) == '_date_time'
          ) {
            $newObject->$colName = CRM_Utils_Date::isoToMysql($newObject->$colName);
          }
        }
        foreach ($newData as $k => $v) {
          $newObject->$k = $v;
        }
        $newObject->save();
        return $newObject;
      }
    }
    return NULL;
  }

  /**
   * Given the component id, compute the contact id
   * since its used for things like send email
   *
   * @param $componentIDs
   * @param string $tableName
   * @param string $idField
   *
   * @return array
   */
  public static function getContactIDsFromComponent($componentIDs, $tableName, $idField = 'id') {
    $contactIDs = array();

    if (empty($componentIDs)) {
      return $contactIDs;
    }

    $IDs = implode(',', $componentIDs);
    $query = "
SELECT contact_id
  FROM $tableName
 WHERE $idField IN ( $IDs )
";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $contactIDs[] = $dao->contact_id;
    }
    return $contactIDs;
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param string $daoName
   *   Name of the dao object.
   * @param string $fieldIdName
   * @param int $fieldId
   * @param $details
   * @param array $returnProperities
   *   An assoc array of fields that need to be returned, eg array( 'first_name', 'last_name').
   *
   * @return object
   *   an object of type referenced by daoName
   */
  public static function commonRetrieveAll($daoName, $fieldIdName = 'id', $fieldId, &$details, $returnProperities = NULL) {
    require_once str_replace('_', DIRECTORY_SEPARATOR, $daoName) . ".php";
    $object = new $daoName();
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

  /**
   * Drop all CiviCRM tables.
   *
   * @throws \CRM_Exception
   */
  public static function dropAllTables() {

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
  public static function escapeString($string) {
    static $_dao = NULL;
    if (!$_dao) {
      // If this is an atypical case (e.g. preparing .sql file before CiviCRM
      // has been installed), then we fallback DB-less str_replace escaping, as
      // we can't use mysqli_real_escape_string, as there is no DB connection.
      // Note: In typical usage, escapeString() will only check one conditional
      // ("if !$_dao") rather than two conditionals ("if !defined(DSN)")
      if (!defined('CIVICRM_DSN')) {
        // See http://php.net/manual/en/mysqli.real-escape-string.php for the
        // list of characters mysqli_real_escape_string escapes.
        $search = array("\\", "\x00", "\n", "\r", "'", '"', "\x1a");
        $replace = array("\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z");
        return str_replace($search, $replace, $string);
      }
      $_dao = new CRM_Core_DAO();
    }
    return $_dao->escape($string);
  }

  /**
   * Escape a list of strings for use with "WHERE X IN (...)" queries.
   *
   * @param array $strings
   * @param string $default
   *   the value to use if $strings has no elements.
   * @return string
   *   eg "abc","def","ghi"
   */
  public static function escapeStrings($strings, $default = NULL) {
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
  public static function escapeWildCardString($string) {
    // CRM-9155
    // ensure we escape the single characters % and _ which are mysql wild
    // card characters and could come in via sortByCharacter
    // note that mysql does not escape these characters
    if ($string && in_array($string,
        array('%', '_', '%%', '_%')
      )
    ) {
      return '\\' . $string;
    }

    return self::escapeString($string);
  }

  /**
   * Creates a test object, including any required objects it needs via recursion
   * createOnly: only create in database, do not store or return the objects (useful for perf testing)
   * ONLY USE FOR TESTING
   *
   * @param string $daoName
   * @param array $params
   * @param int $numObjects
   * @param bool $createOnly
   *
   * @return object|array|NULL
   *   NULL if $createOnly. A single object if $numObjects==1. Otherwise, an array of multiple objects.
   */
  public static function createTestObject(
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
      'CRM_Financial_DAO_FinancialType',
      //because valid ones exist & we use pick them due to pseudoconstant can't reliably create & delete these
    );

    // Prefer to instantiate BAO's instead of DAO's (when possible)
    // so that assignTestValue()/assignTestFK() can be overloaded.
    $baoName = str_replace('_DAO_', '_BAO_', $daoName);
    if (class_exists($baoName)) {
      $daoName = $baoName;
    }

    for ($i = 0; $i < $numObjects; ++$i) {

      ++$counter;
      /** @var CRM_Core_DAO $object */
      $object = new $daoName();

      $fields = $object->fields();
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
          }
          else {
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
      return NULL;
    }
    elseif ($numObjects == 1) {
      return $objects[0];
    }
    else {
      return $objects;
    }
  }

  /**
   * Deletes the this object plus any dependent objects that are associated with it.
   * ONLY USE FOR TESTING
   *
   * @param string $daoName
   * @param array $params
   */
  public static function deleteTestObjects($daoName, $params = array()) {
    //this is a test function  also backtrace is set for the test suite it sometimes unsets itself
    // so we re-set here in case
    $config = CRM_Core_Config::singleton();
    $config->backtrace = TRUE;

    $object = new $daoName();
    $object->id = CRM_Utils_Array::value('id', $params);

    $deletions = array(); // array(array(0 => $daoName, 1 => $daoParams))
    if ($object->find(TRUE)) {

      $fields = $object->fields();
      foreach ($fields as $name => $value) {

        $dbName = $value['name'];

        $FKClassName = CRM_Utils_Array::value('FKClassName', $value);
        $required = CRM_Utils_Array::value('required', $value);
        if ($FKClassName != NULL
          && $object->$dbName
          && !in_array($FKClassName, CRM_Core_DAO::$_testEntitiesToSkip)
          && ($required || $dbName == 'contact_id')
          //I'm a bit stuck on this one - we might need to change the singleValueAlter so that the entities don't share a contact
          // to make this test process pass - line below makes pass for now
          && $dbName != 'member_of_contact_id'
        ) {
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
   * Set defaults when creating new entity.
   * (don't call this set defaults as already in use with different signature in some places)
   *
   * @param array $params
   * @param $defaults
   */
  public static function setCreateDefaults(&$params, $defaults) {
    if (!empty($params['id'])) {
      return;
    }
    foreach ($defaults as $key => $value) {
      if (!array_key_exists($key, $params) || $params[$key] === NULL) {
        $params[$key] = $value;
      }
    }
  }

  /**
   * @param string $prefix
   * @param bool $addRandomString
   * @param null $string
   *
   * @return string
   * @deprecated
   * @see CRM_Utils_SQL_TempTable
   */
  public static function createTempTableName($prefix = 'civicrm', $addRandomString = TRUE, $string = NULL) {
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
  public static function checkTriggerViewPermission($view = TRUE, $trigger = TRUE) {
    if (\Civi::settings()->get('logging_no_trigger_permission')) {
      return TRUE;
    }
    // test for create view and trigger permissions and if allowed, add the option to go multilingual
    // and logging
    // I'm not sure why we use the getStaticProperty for an error, rather than checking for DB_Error
    CRM_Core_TemporaryErrorScope::ignoreException();
    $dao = new CRM_Core_DAO();
    if ($view) {
      $result = $dao->query('CREATE OR REPLACE VIEW civicrm_domain_view AS SELECT * FROM civicrm_domain');
      if (PEAR::getStaticProperty('DB_DataObject', 'lastError') || is_a($result, 'DB_Error')) {
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
  public static function debugPrint($message = NULL, $printDAO = TRUE) {
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
   * @param string $tableName
   *   the specific table requiring a rebuild; or NULL to rebuild all tables.
   * @param bool $force
   * @deprecated
   *
   * @see CRM-9716
   */
  public static function triggerRebuild($tableName = NULL, $force = FALSE) {
    Civi::service('sql_triggers')->rebuild($tableName, $force);
  }

  /**
   * Because sql functions are sometimes lost, esp during db migration, we check here to avoid numerous support requests
   * @see http://issues.civicrm.org/jira/browse/CRM-13822
   * TODO: Alternative solutions might be
   *  * Stop using functions and find another way to strip numeric characters from phones
   *  * Give better error messages (currently a missing fn fatals with "unknown error")
   */
  public static function checkSqlFunctionsExist() {
    if (!self::$_checkedSqlFunctionsExist) {
      self::$_checkedSqlFunctionsExist = TRUE;
      $dao = CRM_Core_DAO::executeQuery("SHOW function status WHERE db = database() AND name = 'civicrm_strip_non_numeric'");
      if (!$dao->fetch()) {
        self::triggerRebuild();
      }
    }
  }

  /**
   * Wrapper function to drop triggers.
   *
   * @param string $tableName
   *   the specific table requiring a rebuild; or NULL to rebuild all tables.
   * @deprecated
   */
  public static function dropTriggers($tableName = NULL) {
    Civi::service('sql_triggers')->dropTriggers($tableName);
  }

  /**
   * @param array $info
   *   per hook_civicrm_triggerInfo.
   * @param string $onlyTableName
   *   the specific table requiring a rebuild; or NULL to rebuild all tables.
   * @deprecated
   */
  public static function createTriggers(&$info, $onlyTableName = NULL) {
    Civi::service('sql_triggers')->createTriggers($info, $onlyTableName);
  }

  /**
   * Given a list of fields, create a list of references.
   *
   * @param string $className
   *   BAO/DAO class name.
   * @return array<CRM_Core_Reference_Interface>
   */
  public static function createReferenceColumns($className) {
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
   * @return array
   *   Array of objects referencing this
   */
  public function findReferences() {
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
   * @return array
   *   each item has keys:
   *   - name: string
   *   - type: string
   *   - count: int
   *   - table: string|null SQL table name
   *   - key: string|null SQL column name
   */
  public function getReferenceCounts() {
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
   * @param string $tableName
   *   Table referred to.
   *
   * @return array
   *   structure of table and column, listing every table with a
   *   foreign key reference to $tableName, and the column where the key appears.
   */
  public static function getReferencesToTable($tableName) {
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
   * Get all references to contact table.
   *
   * This includes core tables, custom group tables, tables added by the merge
   * hook and  the entity_tag table.
   *
   * Refer to CRM-17454 for information on the danger of querying the information
   * schema to derive this.
   */
  public static function getReferencesToContactTable() {
    if (isset(\Civi::$statics[__CLASS__]) && isset(\Civi::$statics[__CLASS__]['contact_references'])) {
      return \Civi::$statics[__CLASS__]['contact_references'];
    }
    $contactReferences = [];
    $coreReferences = CRM_Core_DAO::getReferencesToTable('civicrm_contact');
    foreach ($coreReferences as $coreReference) {
      if (!is_a($coreReference, 'CRM_Core_Reference_Dynamic')) {
        $contactReferences[$coreReference->getReferenceTable()][] = $coreReference->getReferenceKey();
      }
    }
    self::appendCustomTablesExtendingContacts($contactReferences);

    // FixME for time being adding below line statically as no Foreign key constraint defined for table 'civicrm_entity_tag'
    $contactReferences['civicrm_entity_tag'][] = 'entity_id';
    \Civi::$statics[__CLASS__]['contact_references'] = $contactReferences;
    return \Civi::$statics[__CLASS__]['contact_references'];
  }

  /**
   * Add custom tables that extend contacts to the list of contact references.
   *
   * CRM_Core_BAO_CustomGroup::getAllCustomGroupsByBaseEntity seems like a safe-ish
   * function to be sure all are retrieved & we don't miss subtypes or inactive or multiples
   * - the down side is it is not cached.
   *
   * Further changes should be include tests in the CRM_Core_MergerTest class
   * to ensure that disabled, subtype, multiple etc groups are still captured.
   *
   * @param array $cidRefs
   */
  public static function appendCustomTablesExtendingContacts(&$cidRefs) {
    $customValueTables = CRM_Core_BAO_CustomGroup::getAllCustomGroupsByBaseEntity('Contact');
    $customValueTables->find();
    while ($customValueTables->fetch()) {
      $cidRefs[$customValueTables->table_name] = array('entity_id');
    }
  }

  /**
   * Lookup the value of a MySQL global configuration variable.
   *
   * @param string $name
   *   E.g. "thread_stack".
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
   * Update the fields array to also hold keys for pseudoconstant fields that relate to contained fields.
   *
   * This is relevant where we want to offer both the ID field and the label field
   * as an option, e.g. search builder.
   *
   * It is currently limited for optionGroupName & id+ name+ FK combos for purposes keeping the scope of the
   * change small, but is appropriate for other sorts of pseudoconstants.
   *
   * @param array $fields
   */
  public static function appendPseudoConstantsToFields(&$fields) {
    foreach ($fields as $field) {
      if (!empty($field['pseudoconstant'])) {
        $pseudoConstant = $field['pseudoconstant'];
        if (!empty($pseudoConstant['optionGroupName'])) {
          $fields[$pseudoConstant['optionGroupName']] = [
            'title' => CRM_Core_BAO_OptionGroup::getTitleByName($pseudoConstant['optionGroupName']),
            'name' => $pseudoConstant['optionGroupName'],
            'data_type' => CRM_Utils_Type::T_STRING,
            'is_pseudofield_for' => $field['name'],
          ];
        }
        // We restrict to id + name + FK as we are extending this a bit, but cautiously.
        elseif (
          !empty($field['FKClassName'])
          && CRM_Utils_Array::value('keyColumn', $pseudoConstant) === 'id'
          && CRM_Utils_Array::value('labelColumn', $pseudoConstant) === 'name'
        ) {
          $pseudoFieldName = str_replace('_' . $pseudoConstant['keyColumn'], '', $field['name']);
          // This if is just an extra caution when adding change.
          if (!isset($fields[$pseudoFieldName])) {
            $daoName = $field['FKClassName'];
            $fkFields = $daoName::fields();
            foreach ($fkFields as $fkField) {
              if ($fkField['name'] === $pseudoConstant['labelColumn']) {
                $fields[$pseudoFieldName] = [
                  'name' => $pseudoFieldName,
                  'is_pseudofield_for' => $field['name'],
                  'title' => $fkField['title'],
                  'data_type' => $fkField['type'],
                  'where' => $field['where'],
                ];
              }
            }
          }
        }
      }
    }
  }

  /**
   * Get options for the called BAO object's field.
   *
   * This function can be overridden by each BAO to add more logic related to context.
   * The overriding function will generally call the lower-level CRM_Core_PseudoConstant::get
   *
   * @param string $fieldName
   * @param string $context
   * @see CRM_Core_DAO::buildOptionsContext
   * @param array $props
   *   whatever is known about this bao object.
   *
   * @return array|bool
   */
  public static function buildOptions($fieldName, $context = NULL, $props = array()) {
    // If a given bao does not override this function
    $baoName = get_called_class();
    return CRM_Core_PseudoConstant::get($baoName, $fieldName, $props, $context);
  }

  /**
   * Populate option labels for this object's fields.
   *
   * @throws exception if called directly on the base class
   */
  public function getOptionLabels() {
    $fields = $this->fields();
    if ($fields === NULL) {
      throw new Exception('Cannot call getOptionLabels on CRM_Core_DAO');
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
   * @param string $context
   *
   * @throws Exception
   * @return array
   */
  public static function buildOptionsContext($context = NULL) {
    $contexts = array(
      'get' => "get: all options are returned, even if they are disabled; labels are translated.",
      'create' => "create: options are filtered appropriately for the object being created/updated; labels are translated.",
      'search' => "search: searchable options are returned; labels are translated.",
      'validate' => "validate: all options are returned, even if they are disabled; machine names are used in place of labels.",
      'abbreviate' => "abbreviate: enabled options are returned; labels are replaced with abbreviations.",
      'match' => "match: enabled options are returned using machine names as keys; labels are translated.",
    );
    // Validation: enforce uniformity of this param
    if ($context !== NULL && !isset($contexts[$context])) {
      throw new Exception("'$context' is not a valid context for buildOptions.");
    }
    return $contexts;
  }

  /**
   * @param string $fieldName
   * @return bool|array
   */
  public function getFieldSpec($fieldName) {
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
   * Get SQL where clause for SQL filter syntax input parameters.
   *
   * SQL version of api function to assign filters to the DAO based on the syntax
   * $field => array('IN' => array(4,6,9))
   * OR
   * $field => array('LIKE' => array('%me%))
   * etc
   *
   * @param string $fieldName
   *   Name of fields.
   * @param array $filter
   *   filter to be applied indexed by operator.
   * @param string $type
   *   type of field (not actually used - nor in api @todo ).
   * @param string $alias
   *   alternative field name ('as') @todo- not actually used.
   * @param bool $returnSanitisedArray
   *   Return a sanitised array instead of a clause.
   *   this is primarily so we can add filters @ the api level to the Query object based fields
   *
   * @throws Exception
   *
   * @return NULL|string|array
   *   a string is returned if $returnSanitisedArray is not set, otherwise and Array or NULL
   *   depending on whether it is supported as yet
   */
  public static function createSQLFilter($fieldName, $filter, $type = NULL, $alias = NULL, $returnSanitisedArray = FALSE) {
    foreach ($filter as $operator => $criteria) {
      if (in_array($operator, self::acceptedSQLOperators(), TRUE)) {
        switch ($operator) {
          // unary operators
          case 'IS NULL':
          case 'IS NOT NULL':
            if (!$returnSanitisedArray) {
              return (sprintf('%s %s', $fieldName, $operator));
            }
            else {
              return (sprintf('%s %s ', $fieldName, $operator));
            }
            break;

          // ternary operators
          case 'BETWEEN':
          case 'NOT BETWEEN':
            if ((empty($criteria[0]) && !in_array($criteria[0], ['0', 0]))|| (empty($criteria[1]) &&  !in_array($criteria[1], ['0', 0]))) {
              throw new Exception("invalid criteria for $operator");
            }
            if (!$returnSanitisedArray) {
              return (sprintf('%s ' . $operator . ' "%s" AND "%s"', $fieldName, CRM_Core_DAO::escapeString($criteria[0]), CRM_Core_DAO::escapeString($criteria[1])));
            }
            else {
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
              'escapeString',
            ), $criteria);
            if (!$returnSanitisedArray) {
              return (sprintf('%s %s ("%s")', $fieldName, $operator, implode('", "', $escapedCriteria)));
            }
            return $escapedCriteria;

          // binary operators

          default:
            if (!$returnSanitisedArray) {
              return (sprintf('%s %s "%s"', $fieldName, $operator, CRM_Core_DAO::escapeString($criteria)));
            }
            else {
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
    return array(
      '=',
      '<=',
      '>=',
      '>',
      '<',
      'LIKE',
      "<>",
      "!=",
      "NOT LIKE",
      'IN',
      'NOT IN',
      'BETWEEN',
      'NOT BETWEEN',
      'IS NOT NULL',
      'IS NULL',
    );
  }

  /**
   * SQL has a limit of 64 characters on various names:
   * table name, trigger name, column name ...
   *
   * For custom groups and fields we generated names from user entered input
   * which can be longer than this length, this function helps with creating
   * strings that meet various criteria.
   *
   * @param string $string
   *   The string to be shortened.
   * @param int $length
   *   The max length of the string.
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
    if (!$makeRandom) {
      return substr($string, 0, $length);
    }

    // the string is longer than the length and we need a uniq string
    // for the same tablename we need the same uniq string every time
    // hence we use md5 on the string, which is not random
    // we'll append 8 characters to the end of the tableName
    $md5string = substr(md5($string), 0, 8);
    return substr($string, 0, $length - 8) . "_{$md5string}";
  }

  /**
   * https://issues.civicrm.org/jira/browse/CRM-17748
   * Sets the internal options to be used on a query
   *
   * @param array $options
   *
   */
  public function setOptions($options) {
    if (is_array($options)) {
      $this->_options = $options;
    }
  }

  /**
   * https://issues.civicrm.org/jira/browse/CRM-17748
   * wrapper to pass internal DAO options down to DB_mysql/DB_Common level
   *
   * @param array $options
   *
   */
  protected function _setDBOptions($options) {
    global $_DB_DATAOBJECT;

    if (is_array($options) && count($options)) {
      $conn = &$_DB_DATAOBJECT['CONNECTIONS'][$this->_database_dsn_md5];
      foreach ($options as $option_name => $option_value) {
        $conn->setOption($option_name, $option_value);
      }
    }
  }

  /**
   * @deprecated
   * @param array $params
   */
  public function setApiFilter(&$params) {
  }

  /**
   * Generates acl clauses suitable for adding to WHERE or ON when doing an api.get for this entity
   *
   * Return format is in the form of fieldname => clauses starting with an operator. e.g.:
   * @code
   *   array(
   *     'location_type_id' => array('IS NOT NULL', 'IN (1,2,3)')
   *   )
   * @endcode
   *
   * Note that all array keys must be actual field names in this entity. Use subqueries to filter on other tables e.g. custom values.
   *
   * @return array
   */
  public function addSelectWhereClause() {
    $clauses = array();
    $fields = $this->fields();
    foreach ($fields as $fieldName => $field) {
      // Clause for contact-related entities like Email, Relationship, etc.
      if (strpos($fieldName, 'contact_id') === 0 && CRM_Utils_Array::value('FKClassName', $field) == 'CRM_Contact_DAO_Contact') {
        $clauses[$fieldName] = CRM_Utils_SQL::mergeSubquery('Contact');
      }
      // Clause for an entity_table/entity_id combo
      if ($fieldName == 'entity_id' && isset($fields['entity_table'])) {
        $relatedClauses = array();
        $relatedEntities = $this->buildOptions('entity_table', 'get');
        foreach ((array) $relatedEntities as $table => $ent) {
          if (!empty($ent)) {
            $ent = CRM_Core_DAO_AllCoreTables::getBriefName(CRM_Core_DAO_AllCoreTables::getClassForTable($table));
            $subquery = CRM_Utils_SQL::mergeSubquery($ent);
            if ($subquery) {
              $relatedClauses[] = "(entity_table = '$table' AND entity_id " . implode(' AND entity_id ', $subquery) . ")";
            }
            else {
              $relatedClauses[] = "(entity_table = '$table')";
            }
          }
        }
        if ($relatedClauses) {
          $clauses['id'] = 'IN (SELECT id FROM `' . $this->tableName() . '` WHERE (' . implode(') OR (', $relatedClauses) . '))';
        }
      }
    }
    CRM_Utils_Hook::selectWhereClause($this, $clauses);
    return $clauses;
  }

  /**
   * This returns the final permissioned query string for this entity
   *
   * With acls from related entities + additional clauses from hook_civicrm_selectWhereClause
   *
   * @param string $tableAlias
   * @return array
   */
  public static function getSelectWhereClause($tableAlias = NULL) {
    $bao = new static();
    if ($tableAlias === NULL) {
      $tableAlias = $bao->tableName();
    }
    $clauses = array();
    foreach ((array) $bao->addSelectWhereClause() as $field => $vals) {
      $clauses[$field] = NULL;
      if ($vals) {
        $clauses[$field] = "(`$tableAlias`.`$field` IS NULL OR (`$tableAlias`.`$field` " . implode(" AND `$tableAlias`.`$field` ", (array) $vals) . '))';
      }
    }
    return $clauses;
  }

  /**
   * ensure database name is 'safe', i.e. only contains word characters (includes underscores)
   * and dashes, and contains at least one [a-z] case insenstive.
   *
   * @param $database
   *
   * @return bool
   */
  public static function requireSafeDBName($database) {
    $matches = array();
    preg_match(
      "/^[\w\-]*[a-z]+[\w\-]*$/i",
      $database,
      $matches
    );
    if (empty($matches)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Transform an array to a serialized string for database storage.
   *
   * @param array|NULL $value
   * @param $serializationType
   * @return string|NULL
   * @throws \Exception
   */
  public static function serializeField($value, $serializationType) {
    if ($value === NULL) {
      return NULL;
    }
    switch ($serializationType) {
      case self::SERIALIZE_SEPARATOR_BOOKEND:
        return $value === array() ? '' : CRM_Utils_Array::implodePadded($value);

      case self::SERIALIZE_SEPARATOR_TRIMMED:
        return is_array($value) ? implode(self::VALUE_SEPARATOR, $value) : $value;

      case self::SERIALIZE_JSON:
        return is_array($value) ? json_encode($value) : $value;

      case self::SERIALIZE_PHP:
        return is_array($value) ? serialize($value) : $value;

      case self::SERIALIZE_COMMA:
        return is_array($value) ? implode(',', $value) : $value;

      default:
        throw new Exception('Unknown serialization method for field.');
    }
  }

  /**
   * Transform a serialized string from the database into an array.
   *
   * @param string|null $value
   * @param $serializationType
   * @return array|null
   * @throws \Exception
   */
  public static function unSerializeField($value, $serializationType) {
    if ($value === NULL) {
      return NULL;
    }
    if ($value === '') {
      return array();
    }
    switch ($serializationType) {
      case self::SERIALIZE_SEPARATOR_BOOKEND:
        return (array) CRM_Utils_Array::explodePadded($value);

      case self::SERIALIZE_SEPARATOR_TRIMMED:
        return explode(self::VALUE_SEPARATOR, trim($value));

      case self::SERIALIZE_JSON:
        return strlen($value) ? json_decode($value, TRUE) : array();

      case self::SERIALIZE_PHP:
        return strlen($value) ? unserialize($value) : array();

      case self::SERIALIZE_COMMA:
        return explode(',', trim(str_replace(', ', '', $value)));

      default:
        throw new Exception('Unknown serialization method for field.');
    }
  }

  /**
   * @return array
   */
  public static function getEntityRefFilters() {
    return [];
  }

  /**
   * Get exportable fields with pseudoconstants rendered as an extra field.
   *
   * @param string $baoClass
   *
   * @return array
   */
  public static function getExportableFieldsWithPseudoConstants($baoClass) {
    if (method_exists($baoClass, 'exportableFields')) {
      $fields = $baoClass::exportableFields();
    }
    else {
      $fields = $baoClass::export();
    }
    CRM_Core_DAO::appendPseudoConstantsToFields($fields);
    return $fields;
  }

}
