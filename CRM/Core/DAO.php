<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Base Database Access Object class.
 *
 * All DAO classes should inherit from this class.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Core\DAO\Event\PostUpdate;
use Civi\Core\DAO\Event\PreUpdate;

if (!defined('DB_DSN_MODE')) {
  define('DB_DSN_MODE', 'auto');
}

require_once 'PEAR.php';
require_once 'DB/DataObject.php';

/**
 * Class CRM_Core_DAO
 */
class CRM_Core_DAO extends DB_DataObject {

  /**
   * Primary key field(s).
   *
   * @var string[]
   */
  public static $_primaryKey = ['id'];

  /**
   * @return string
   */
  protected function getFirstPrimaryKey(): string {
    // Historically it was always 'id'. It is now the case that some entities (import entities)
    // have a single key that is NOT 'id'. However, for entities that have multiple
    // keys (which we support in codegen if not many other places) we return 'id'
    // simply because that is what we historically did & we don't want to 'just change'
    // it & break those extensions without doing the work to create an alternative.
    return count($this->keys()) > 1 ? 'id' : $this->keys()[0];
  }

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
  public static $_nullObject = NULL;

  /**
   * Icon associated with this entity.
   *
   * @var string
   */
  public static $_icon = NULL;

  /**
   * Field to show when displaying a record.
   *
   * @var string
   */
  public static $_labelField = NULL;

  /**
   * @var array
   * @deprecated
   */
  public static $_nullArray = [];

  public static $_dbColumnValueCache = NULL;
  const NOT_NULL = 1, IS_NULL = 2,
    DB_DAO_NOTNULL = 128,
    VALUE_SEPARATOR = "",
    BULK_INSERT_COUNT = 200,
    BULK_INSERT_HIGH_COUNT = 200,
    QUERY_FORMAT_WILDCARD = 1,
    QUERY_FORMAT_NO_QUOTES = 2,

    /**
     * No serialization.
     */
    SERIALIZE_NONE = 0,
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
    SERIALIZE_COMMA = 5,
    /**
     * @deprecated
     *
     * Comma separated, spaces trimmed, key=value optional
     *
     * This was added to handle a wonky/legacy field, `civicrm_product.options`.
     * If you're adding new fields, then use SERIALIZE_JSON instead. JSON is more
     * standardized and has fewer quirks.
     */
    SERIALIZE_COMMA_KEY_VALUE = 6;

  /**
   * Define entities that shouldn't be created or deleted when creating/ deleting
   * test objects - this prevents world regions, countries etc from being added / deleted
   * @var array
   */
  public static $_testEntitiesToSkip = [];

  /**
   * https://issues.civicrm.org/jira/browse/CRM-17748
   * internal variable for DAO to hold per-query settings
   * @var array
   */
  protected $_options = [];

  /**
   * Class constructor.
   *
   * @return static
   */
  public function __construct() {
    $this->initialize();
    if (is_subclass_of($this, 'CRM_Core_DAO')) {
      $this->__table = $this::getLocaleTableName();
    }
  }

  /**
   * Returns localized title of this entity.
   *
   * @return string
   */
  public static function getEntityTitle() {
    $className = static::class;
    CRM_Core_Error::deprecatedWarning("$className needs to be regenerated. Missing getEntityTitle method.");
    return CRM_Core_DAO_AllCoreTables::getEntityNameForClass($className);
  }

  public static function getLabelField(): ?string {
    return static::$_labelField;
  }

  /**
   * Returns user-friendly description of this entity.
   *
   * @return string|null
   */
  public static function getEntityDescription() {
    return NULL;
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
   * Returns the name of this table
   *
   * Name is not localized, which is generally fine because localization happens when executing the query:
   * @see \CRM_Core_I18n_Schema::rewriteQuery()
   *
   * To get the localized name of this table,
   * @see self::getLocaleTableName()
   *
   * @return string
   */
  public static function getTableName() {
    return static::$_tableName ?? NULL;
  }

  /**
   * Returns if this table needs to be logged
   *
   * @return bool
   */
  public function getLog() {
    return static::$_log ?? FALSE;
  }

  /**
   * Set the sql maximum execution time value.
   *
   * Note the preferred way to access this is via
   * `$autoClean = CRM_Utils_AutoClean::swapMaxExecutionTime(800);`
   *
   * It can then be reverted with
   * `$autoClean->cleanup()`
   * Note that the auto clean will do the clean up itself on `__destruct`
   * but formally doing it makes it clear that it is being done and, importantly,
   * avoids the situation where someone just calls
   * `CRM_Utils_AutoClean::swapMaxExecutionTime(800);`
   * without assigning it to a variable (because `__destruct` is implicitly called)
   *
   * https://mariadb.com/kb/en/aborting-statements/
   */
  public static function setMaxExecutionTime(int $time): int {
    $version = CRM_Utils_SQL::getDatabaseVersion();
    $originalTimeLimit = self::getMaxExecutionTime();
    if (stripos($version, 'mariadb') !== FALSE) {
      // MariaDB variable has a certain name, and value is in seconds.
      $sql = "SET SESSION MAX_STATEMENT_TIME={$time}";
    }
    else {
      // MySQL variable has a different name, and value is in milliseconds.
      $sql = "SET SESSION MAX_EXECUTION_TIME=" . ($time * 1000);
    }
    try {
      CRM_Core_DAO::executeQuery($sql);
    }
    catch (CRM_Core_Exception $e) {
      \Civi::log()->warning('failed to adjust maximum query execution time {sql}', [
        'sql' => $sql,
        'exception' => $e,
      ]);
    }
    finally {
      return $originalTimeLimit;
    }
  }

  /**
   * Get the mysql / mariaDB maximum execution time variable.
   *
   * https://mariadb.com/kb/en/aborting-statements/
   *
   * @return int
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function getMaxExecutionTime(): int {
    $version = CRM_Utils_SQL::getDatabaseVersion();
    if (stripos($version, 'mariadb') !== FALSE) {
      $originalSql = 'SHOW VARIABLES LIKE "MAX_STATEMENT_TIME"';
      $variableDao = CRM_Core_DAO::executeQuery($originalSql);
      $variableDao->fetch();
      return (int) $variableDao->Value;
    }
    else {
      $originalSql = 'SHOW VARIABLES LIKE "MAX_EXECUTION_TIME"';
      $variableDao = CRM_Core_DAO::executeQuery($originalSql);
      $variableDao->fetch();
      return ((int) $variableDao->Value) / 1000;
    }

  }

  /**
   * Returns the list of fields that can be imported
   *
   * @param bool $prefix
   * @return array
   */
  public static function import($prefix = FALSE) {
    return CRM_Core_DAO_AllCoreTables::getImports(static::class, substr(static::getTableName(), 8), $prefix);
  }

  /**
   * Returns the list of fields that can be exported
   *
   * @param bool $prefix
   * @return array
   */
  public static function export($prefix = FALSE) {
    return CRM_Core_DAO_AllCoreTables::getExports(static::class, substr(static::getTableName(), 8), $prefix);
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
    $dsn = CRM_Utils_SQL::autoSwitchDSN($dsn);
    $options['database'] = $dsn;
    $options['quote_identifiers'] = TRUE;
    if (CRM_Utils_SQL::isSSLDSN($dsn)) {
      // There are two different options arrays.
      $other_options = &PEAR::getStaticProperty('DB', 'options');
      $other_options['ssl'] = TRUE;
    }
    if (defined('CIVICRM_DAO_DEBUG')) {
      self::DebugLevel(CIVICRM_DAO_DEBUG);
    }
    CRM_Core_DAO::executeQuery('SET NAMES utf8mb4');
    CRM_Core_DAO::executeQuery('SET @uniqueID = %1', [1 => [CRM_Utils_Request::id(), 'String']]);
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
      CRM_Core_DAO::executeQuery("SET SESSION sql_mode = %1", [1 => [implode(',', $currentModes), 'String']]);
    }
  }

  /**
   * Re-enables ONLY_FULL_GROUP_BY sql_mode as necessary..
   */
  public static function reenableFullGroupByMode() {
    $currentModes = CRM_Utils_SQL::getSqlModes();
    if (!in_array('ONLY_FULL_GROUP_BY', $currentModes) && CRM_Utils_SQL::isGroupByModeInDefault()) {
      $currentModes[] = 'ONLY_FULL_GROUP_BY';
      CRM_Core_DAO::executeQuery("SET SESSION sql_mode = %1", [1 => [implode(',', $currentModes), 'String']]);
    }
  }

  /**
   * @param string $fieldName
   * @param $fieldDef
   * @param array $params
   */
  protected function assignTestFK($fieldName, $fieldDef, $params) {
    $required = $fieldDef['required'] ?? NULL;
    $FKClassName = $fieldDef['FKClassName'] ?? NULL;
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
    }

    elseif (in_array($FKClassName, CRM_Core_DAO::$_testEntitiesToSkip)) {
      $depObject = new $FKClassName();
      $depObject->find(TRUE);
      $this->$dbName = $depObject->id;
    }
    elseif ($daoName == 'CRM_Member_DAO_MembershipType' && $fieldName == 'member_of_contact_id') {
      // FIXME: the fields() metadata is not specific enough
      $depObject = CRM_Core_DAO::createTestObject($FKClassName, ['contact_type' => 'Organization']);
      $this->$dbName = $depObject->id;
    }
    else {
      //if it is required we need to generate the dependency object first
      $depObject = CRM_Core_DAO::createTestObject($FKClassName, $params[$dbName] ?? 1);
      $this->$dbName = $depObject->id;
    }
  }

  /**
   * Generate and assign an arbitrary value to a field of a test object.
   *
   * @param string $fieldName
   * @param array $fieldDef
   * @param int $counter
   *   The globally-unique ID of the test object.
   *
   * @throws \CRM_Core_Exception
   */
  protected function assignTestValue($fieldName, &$fieldDef, $counter) {
    $dbName = $fieldDef['name'];
    $daoName = get_class($this);
    $handled = FALSE;

    if (in_array($dbName, ['contact_sub_type', 'email_greeting_id', 'postal_greeting_id', 'addressee_id'], TRUE)) {
      //coming up with a rule to set these is too complex - skip
      return;
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
          throw new CRM_Core_Exception('T_TIME shouldn\'t be used.');

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
                $this->$dbName = $options[0] ?? NULL;
              }
              else {
                $defaultValues = explode(',', $options);
                $this->$dbName = $defaultValues[0];
              }
            }
          }
          else {
            $this->$dbName = $dbName . '_' . $counter;
            $maxlength = $fieldDef['maxlength'] ?? NULL;
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
    $this->_query = [];
    $this->whereAdd();
    $this->selectAdd();
    $this->joinAdd();
  }

  /**
   * Get localized name of this table, if applicable.
   *
   * If this is a multi-language installation and the table has localized columns,
   * will return table name with language string appended, which points to a sql view.
   * Otherwise, this returns the same output as
   * @see self::getTableName()
   *
   * @param string|null $tableName
   *  Unnecessary deprecated param
   *
   * @return string
   */
  public static function getLocaleTableName($tableName = NULL) {
    $tableName ??= static::getTableName();
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
    if (CIVICRM_UF === 'UnitTests' && CRM_Utils_Time::isOverridden()) {
      $query = CRM_Utils_Time::rewriteQuery($query);
    }

    $ret = parent::query($query);

    $this->_setDBOptions($orig_options);
    return $ret;
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
   * Returns primary keys (usually ['id'])
   *
   * @return string[]
   */
  public function keys() {
    return static::$_primaryKey;
  }

  /**
   * Tells DB_DataObject which keys use autoincrement.
   * 'id' is autoincrementing by default.
   *
   * FIXME: this should return all autoincrement keys not just the first.
   *
   * @return array
   */
  public function sequenceKey() {
    return [$this->getFirstPrimaryKey(), TRUE];
  }

  /**
   * Returns list of FK relationships.
   *
   * @return CRM_Core_Reference_Basic[]
   */
  public static function getReferenceColumns() {
    if (!isset(Civi::$statics[static::class]['links'])) {
      $links = static::createReferenceColumns(static::class);
      // Add references based on field metadata
      foreach (static::fields() as $field) {
        if (!empty($field['FKClassName'])) {
          $links[] = new CRM_Core_Reference_Basic(
            static::getTableName(),
            $field['name'],
            CRM_Core_DAO_AllCoreTables::getTableForClass($field['FKClassName']),
            $field['FKColumnName'] ?? 'id'
          );
        }
        if (!empty($field['DFKEntityColumn'])) {
          $links[] = new CRM_Core_Reference_Dynamic(
            static::getTableName(),
            $field['name'],
            NULL,
            $field['FKColumnName'] ?? 'id',
            $field['DFKEntityColumn']
          );
        }
      }
      CRM_Core_DAO_AllCoreTables::invoke(static::class, 'links_callback', $links);
      Civi::$statics[static::class]['links'] = $links;
    }
    return Civi::$statics[static::class]['links'];
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
   * Returns all usable fields, indexed by name.
   *
   * This function differs from fields() in that it indexes by name rather than unique_name.
   *
   * It excludes fields not added yet by pending upgrades.
   * This avoids problems with trying to SELECT a field that exists in code but has not yet been added to the db.
   *
   * @param bool $checkPermissions
   *   Filter by field permissions.
   * @return array
   */
  public static function getSupportedFields($checkPermissions = FALSE) {
    $fields = array_column((array) static::fields(), NULL, 'name');

    // Exclude fields yet not added by pending upgrades
    $dbVer = \CRM_Core_BAO_Domain::version();
    $daoExt = static::getExtensionName();
    if ($fields && $daoExt === 'civicrm' && version_compare($dbVer, \CRM_Utils_System::version()) < 0) {
      $fields = array_filter($fields, function($field) use ($dbVer) {
        $add = $field['add'] ?? '1.0.0';
        if (substr_count($add, '.') < 2) {
          $add .= '.alpha1';
        }
        return version_compare($dbVer, $add, '>=');
      });
    }

    // Exclude fields the user does not have permission for
    if ($checkPermissions) {
      $fields = array_filter($fields, function($field) {
        return empty($field['permission']) || CRM_Core_Permission::check($field['permission']);
      });
    }

    return $fields;
  }

  /**
   * Get name of extension in which this DAO is defined.
   * @return string|null
   */
  public static function getExtensionName(): ?string {
    return defined(static::class . '::EXT') ? constant(static::class . '::EXT') : NULL;
  }

  /**
   * Format field values according to fields() metadata.
   *
   * When fetching results from a query, every field is returned as a string.
   * This function automatically converts them to the correct data type.
   *
   * @param array $fieldValues
   * @return void
   */
  public static function formatFieldValues(array &$fieldValues) {
    $fields = array_column((array) static::fields(), NULL, 'name');
    foreach ($fieldValues as $fieldName => $fieldValue) {
      $fieldSpec = $fields[$fieldName] ?? NULL;
      $fieldValues[$fieldName] = self::formatFieldValue($fieldValue, $fieldSpec);
    }
  }

  /**
   * Format a value according to field metadata.
   *
   * @param string|null $value
   * @param array|null $fieldSpec
   * @return mixed
   */
  protected static function formatFieldValue($value, ?array $fieldSpec) {
    // DAO queries return `null` db values as empty string
    if ($value === '' && empty($fieldSpec['required'])) {
      return NULL;
    }
    if (!isset($value) || !isset($fieldSpec)) {
      return $value;
    }
    $dataType = $fieldSpec['type'] ?? NULL;
    if ($dataType === CRM_Utils_Type::T_INT) {
      return (int) $value;
    }
    if ($dataType === CRM_Utils_Type::T_BOOLEAN) {
      return (bool) $value;
    }
    if (!empty($fieldSpec['serialize'])) {
      return self::unSerializeField($value, $fieldSpec['serialize']);
    }
    return $value;
  }

  /**
   * Get/set an associative array of table columns
   *
   * @return array
   *   (associative)
   */
  public function table() {
    $fields = $this->fields();

    $table = [];
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
    $eventID = uniqid();
    $primaryField = $this->getFirstPrimaryKey();
    if (!empty($this->$primaryField)) {
      if ($hook) {
        $preEvent = new PreUpdate($this);
        $preEvent->eventID = $eventID;
        \Civi::dispatcher()->dispatch('civi.dao.preUpdate', $preEvent);
      }

      $result = $this->update();

      if ($hook) {
        $event = new PostUpdate($this, $result);
        $event->eventID = $eventID;
        \Civi::dispatcher()->dispatch('civi.dao.postUpdate', $event);
      }
      $this->clearDbColumnValueCache();
    }
    else {
      if ($hook) {
        $preEvent = new PreUpdate($this);
        $preEvent->eventID = $eventID;
        \Civi::dispatcher()->dispatch("civi.dao.preInsert", $preEvent);
      }

      $result = $this->insert();

      if ($hook) {
        $event = new PostUpdate($this, $result);
        $event->eventID = $eventID;
        \Civi::dispatcher()->dispatch("civi.dao.postInsert", $event);
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
   * @return int|false
   *   Int (No. of rows affected) on success, false on failure, 0 on no data affected
   */
  public function delete($useWhere = FALSE) {
    $preEvent = new \Civi\Core\DAO\Event\PreDelete($this);
    \Civi::dispatcher()->dispatch("civi.dao.preDelete", $preEvent);

    $result = parent::delete($useWhere);

    $event = new \Civi\Core\DAO\Event\PostDelete($this, $result);
    \Civi::dispatcher()->dispatch("civi.dao.postDelete", $event);
    $this->free();

    $this->clearDbColumnValueCache();

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
   *   Array of name/value pairs to save.
   *
   * @return bool
   *   Did we copy all null values into the object
   */
  public function copyValues($params) {
    $allNull = TRUE;
    $primaryKey = $this->getFirstPrimaryKey();
    foreach ($this->fields() as $uniqueName => $field) {
      $dbName = $field['name'];
      if (is_array($params) && array_key_exists($dbName, $params)) {
        $value = $params[$dbName];
        $exists = TRUE;
      }
      elseif (is_array($params) && array_key_exists($uniqueName, $params)) {
        $value = $params[$uniqueName];
        $exists = TRUE;
      }
      else {
        $exists = FALSE;
      }

      // if there is no value then make the variable NULL
      if ($exists) {
        if ($value === '') {
          if ($dbName === $primaryKey && $field['type'] === CRM_Utils_Type::T_INT) {
            // See also \Civi\Api4\Utils\FormattingUtil::formatWriteParams().
            // The string 'null' is used in pear::db to "unset" values, whereas
            // it skips over fields where the param is real null. However
            // "unsetting" a primary key doesn't make sense - you can't convert
            // an existing record to a "new" one. And then having string 'null'
            // in the dao object can confuse later code, in particular save()
            // which then calls the update hook instead of the create hook.
            $this->$dbName = NULL;
          }
          else {
            $this->$dbName = 'null';
          }
        }
        elseif (is_array($value) && !empty($field['serialize'])) {
          if (!empty($field['pseudoconstant'])) {
            // Pseudoconstant implies 1-1 option matching; duplicates would not make sense
            $value = array_unique($value);
          }
          $this->$dbName = CRM_Core_DAO::serializeField($value, $field['serialize']);
          $allNull = FALSE;
        }
        // When a single value was entered for a serialized field, it's probably due to sloppy coding.
        // Folks, always use an array to pass in values for fields containing array data.
        // Meanwhile, I'll convert it for you. You're welcome.
        elseif (is_numeric($value) && !empty($field['serialize'])) {
          $this->$dbName = CRM_Core_DAO::serializeField((array) $value, $field['serialize']);
          $allNull = FALSE;
        }
        else {
          $maxLength = $field['maxlength'] ?? NULL;
          if (!is_array($value) && $maxLength && mb_strlen($value ?? '') > $maxLength && empty($field['pseudoconstant'])) {
            // No ts() since this is a sysadmin-y string not seen by general users.
            Civi::log()->warning('A string for field {dbName} has been truncated. The original string was {value}.', ['dbName' => $dbName, 'value' => $value]);
            // The string is too long - what to do what to do? Well losing data is generally bad so let's truncate
            $value = CRM_Utils_String::ellipsify($value, $maxLength);
          }
          $this->$dbName = $value;
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
      if (($field['type'] ?? NULL) == CRM_Utils_Type::T_STRING) {
        $maxLength = $field['maxlength'] ?? NULL;
        $size = $field['size'] ?? NULL;
        if ($maxLength || $size) {
          $attributes = [];
          if ($maxLength) {
            $attributes['maxlength'] = $maxLength;
          }
          if ($size) {
            $attributes['size'] = $size;
          }
          return $attributes;
        }
      }
      elseif (($field['type'] ?? NULL) == CRM_Utils_Type::T_TEXT) {
        $rows = $field['rows'] ?? NULL;
        if (!isset($rows)) {
          $rows = 2;
        }
        $cols = $field['cols'] ?? NULL;
        if (!isset($cols)) {
          $cols = 80;
        }

        $attributes = [];
        $attributes['rows'] = $rows;
        $attributes['cols'] = $cols;
        return $attributes;
      }
      elseif (($field['type'] ?? NULL) == CRM_Utils_Type::T_INT || ($field['type'] ?? NULL) == CRM_Utils_Type::T_FLOAT || ($field['type'] ?? NULL) == CRM_Utils_Type::T_MONEY) {
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
      $field = $fields[$fieldName] ?? NULL;
      return self::makeAttribute($field);
    }
    else {
      $attributes = [];
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
   * Create or update a record from supplied params.
   *
   * If 'id' is supplied, an existing record will be updated
   * Otherwise a new record will be created.
   *
   * @param array $record
   *
   * @return static
   * @throws \CRM_Core_Exception
   */
  public static function writeRecord(array $record): CRM_Core_DAO {
    // Todo: Support composite primary keys
    $idField = static::$_primaryKey[0];
    $op = empty($record[$idField]) ? 'create' : 'edit';
    $className = CRM_Core_DAO_AllCoreTables::getCanonicalClassName(static::class);
    if ($className === 'CRM_Core_DAO') {
      throw new CRM_Core_Exception('Function writeRecord must be called on a subclass of CRM_Core_DAO');
    }
    $entityName = CRM_Core_DAO_AllCoreTables::getEntityNameForClass($className);

    // For legacy reasons, empty values would sometimes be passed around as the string 'null'.
    // The DAO treats 'null' the same as '', and an empty string makes a lot more sense!
    // For the sake of hooks, normalize these values.
    $record = array_map(function ($value) {
      return $value === 'null' ? '' : $value;
    }, $record);

    \CRM_Utils_Hook::pre($op, $entityName, $record[$idField] ?? NULL, $record);

    // Fill defaults after pre hook to accept any hook modifications
    self::setDefaultsFromCallback($entityName, $record);
    $fields = static::getSupportedFields();
    $instance = new static();
    // Ensure fields exist before attempting to write to them
    $values = array_intersect_key($record, $fields);
    $instance->copyValues($values);
    if (empty($values[$idField]) && array_key_exists('name', $fields) && empty($values['name'])) {
      $instance->makeNameFromLabel();
    }
    $instance->save();

    if (!empty($record['custom']) && is_array($record['custom'])) {
      CRM_Core_BAO_CustomValueTable::store($record['custom'], static::getTableName(), $instance->$idField, $op);
    }

    \CRM_Utils_Hook::post($op, $entityName, $instance->$idField, $instance, $record);

    return $instance;
  }

  /**
   * Bulk save multiple records
   *
   * @param array[] $records
   * @return static[]
   * @throws CRM_Core_Exception
   */
  public static function writeRecords(array $records): array {
    $results = [];
    foreach ($records as $record) {
      $results[] = static::writeRecord($record);
    }
    return $results;
  }

  /**
   * Delete a record from supplied params.
   *
   * @param array $record
   *   'id' is required.
   * @return static
   * @throws CRM_Core_Exception
   */
  public static function deleteRecord(array $record) {
    // Todo: Support composite primary keys
    $idField = static::$_primaryKey[0];
    $className = CRM_Core_DAO_AllCoreTables::getCanonicalClassName(static::class);
    if ($className === 'CRM_Core_DAO') {
      throw new CRM_Core_Exception('Function deleteRecord must be called on a subclass of CRM_Core_DAO');
    }
    $entityName = CRM_Core_DAO_AllCoreTables::getEntityNameForClass($className);
    if (empty($record[$idField])) {
      throw new CRM_Core_Exception("Cannot delete {$entityName} with no $idField.");
    }
    CRM_Utils_Type::validate($record[$idField], 'Positive');

    CRM_Utils_Hook::pre('delete', $entityName, $record[$idField], $record);
    $instance = new $className();
    $instance->$idField = $record[$idField];
    // Load complete object for the sake of hook_civicrm_post, below
    $instance->find(TRUE);
    if (!$instance || !$instance->delete()) {
      throw new CRM_Core_Exception("Could not delete {$entityName} $idField {$record[$idField]}");
    }
    // For other operations this hook is passed an incomplete object and hook listeners can load if needed.
    // But that's not possible with delete because it's gone from the database by the time this hook is called.
    // So in this case the object has been pre-loaded so hook listeners have access to the complete record.
    CRM_Utils_Hook::post('delete', $entityName, $record[$idField], $instance, $record);

    return $instance;
  }

  /**
   * Bulk delete multiple records.
   *
   * @param array[] $records
   * @return static[]
   * @throws CRM_Core_Exception
   */
  public static function deleteRecords(array $records) {
    $results = [];
    foreach ($records as $record) {
      $results[] = static::deleteRecord($record);
    }
    return $results;
  }

  /**
   * Set default values for fields based on callback functions
   *
   * @param string $entityName
   *   The entity name
   * @param array &$record
   *   The record array to set default values for
   * @return void
   */
  private static function setDefaultsFromCallback(string $entityName, array &$record): void {
    $entity = Civi::entity($entityName);
    $idField = $entity->getMeta('primary_key');
    // Only fill values for create operations
    if (!empty($record[$idField])) {
      return;
    }
    foreach ($entity->getFields() as $fieldName => $field) {
      if (!empty($field['default_fallback'])) {
        $field += ['default_callback' => [__CLASS__, 'getDefaultFallbackValues']];
      }
      // Check if value is empty using `strlen()` to avoid php quirk of '0' == false.
      if (!empty($field['default_callback']) && !strlen((string) ($record[$fieldName] ?? ''))) {
        $record[$fieldName] = \Civi\Core\Resolver::singleton()->call($field['default_callback'], [$record, $entityName, $fieldName, $field]);
      }
    }
  }

  /**
   * Callback for `default_fallback` field values
   *
   * @param array $record
   * @param string $entityName
   * @param string $fieldName
   * @param array $field
   * @return mixed
   */
  public static function getDefaultFallbackValues(array $record, string $entityName, string $fieldName, array $field) {
    foreach ($field['default_fallback'] as $defaultFieldName) {
      if (strlen((string) ($record[$defaultFieldName] ?? ''))) {
        return $record[$defaultFieldName];
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
      return $daoID && $object->id == $daoID;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Gets the names of all enabled schema tables.
   *
   * - Includes tables from core, components & enabled extensions.
   * - Excludes log tables, temp tables, and missing/disabled extensions.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public static function getTableNames(): array {
    // CRM_Core_DAO_AllCoreTables returns all tables with a dao (core + extensions)
    $daoTables = array_column(CRM_Core_DAO_AllCoreTables::getEntities(), 'table');

    // Include custom value tables
    $customTables = array_column(CRM_Core_BAO_CustomGroup::getAll(), 'table_name');

    return array_merge($daoTables, $customTables);
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
         AND TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME LIKE 'civicrm_%'
         AND TABLE_NAME NOT LIKE 'civicrm_tmp_%'
      ");
  }

  /**
   * Get the name of the CiviCRM database.
   *
   * @deprecated use mysql DATABASE() within the query.
   *
   * @return string
   */
  public static function getDatabaseName(): string {
    return (new CRM_Core_DAO())->database();
  }

  /**
   * Checks if a constraint exists for a specified table.
   *
   * @param string $tableName
   * @param string $constraint
   *
   * @return bool
   *   true if constraint exists, false otherwise
   *
   * @throws \CRM_Core_Exception
   */
  public static function checkConstraintExists($tableName, $constraint) {
    static $show = [];

    if (!array_key_exists($tableName, $show)) {
      $query = "SHOW CREATE TABLE $tableName";
      $dao = CRM_Core_DAO::executeQuery($query, [], TRUE, NULL, FALSE, FALSE);

      if (!$dao->fetch()) {
        throw new CRM_Core_Exception('query failed');
      }

      $show[$tableName] = $dao->Create_Table;
    }

    return (bool) preg_match("/\b$constraint\b/i", $show[$tableName]);
  }

  /**
   * Checks if CONSTRAINT keyword exists for a specified table.
   *
   * @deprecated in 5.72 will be removed in 5.85
   */
  public static function schemaRequiresRebuilding($tables = ["civicrm_contact"]) {
    CRM_Core_Error::deprecatedFunctionWarning('No alternative');
    $show = [];
    foreach ($tables as $tableName) {
      if (!array_key_exists($tableName, $show)) {
        $query = "SHOW CREATE TABLE $tableName";
        $dao = CRM_Core_DAO::executeQuery($query, [], TRUE, NULL, FALSE, FALSE);

        if (!$dao->fetch()) {
          throw new CRM_Core_Exception('Show create table failed.');
        }

        $show[$tableName] = $dao->Create_Table;
      }

      $result = (bool) preg_match("/\bCONSTRAINT\b\s/i", $show[$tableName]);
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
   * @deprecated in 5.72 will be removed in 5.85
   */
  public static function checkFKConstraintInFormat($tableName, $columnName) {
    CRM_Core_Error::deprecatedFunctionWarning('No alternative');
    static $show = [];

    if (!array_key_exists($tableName, $show)) {
      $query = "SHOW CREATE TABLE $tableName";
      $dao = CRM_Core_DAO::executeQuery($query);

      if (!$dao->fetch()) {
        throw new CRM_Core_Exception('query failed');
      }

      $show[$tableName] = $dao->Create_Table;
    }
    $constraint = "`FK_{$tableName}_{$columnName}`";
    $pattern = "/\bCONSTRAINT\b\s+%s\s+\bFOREIGN\s+KEY\b\s/i";
    return (bool) preg_match(sprintf($pattern, $constraint), $show[$tableName]);
  }

  /**
   * Check whether a specific column in a specific table has always the same value.
   *
   * @deprecated in 5.72 will be removed in 5.85
   */
  public static function checkFieldHasAlwaysValue($tableName, $columnName, $columnValue) {
    CRM_Core_Error::deprecatedFunctionWarning('APIv4');
    $query = "SELECT * FROM $tableName WHERE $columnName != '$columnValue'";
    $dao = CRM_Core_DAO::executeQuery($query);
    $result = $dao->fetch() ? FALSE : TRUE;
    return $result;
  }

  /**
   * Check whether a specific column in a specific table is always NULL.
   *
   * @deprecated in 5.72 will be removed in 5.85
   */
  public static function checkFieldIsAlwaysNull($tableName, $columnName) {
    CRM_Core_Error::deprecatedFunctionWarning('APIv4');
    $query = "SELECT * FROM $tableName WHERE $columnName IS NOT NULL";
    $dao = CRM_Core_DAO::executeQuery($query);
    $result = $dao->fetch() ? FALSE : TRUE;
    return $result;
  }

  /**
   * Checks if this DAO's table ought to exist.
   *
   * If there are pending DB updates, this function compares the CiviCRM version of the table to the current schema version.
   *
   * @return bool
   * @throws CRM_Core_Exception
   */
  public static function tableHasBeenAdded(): bool {
    if (CRM_Utils_System::version() === CRM_Core_BAO_Domain::version()) {
      return TRUE;
    }
    $daoExt = static::getExtensionName();
    if ($daoExt !== 'civicrm') {
      // FIXME: Check extension tables
      return TRUE;
    }
    $daoVersion = static::getTableAddVersion();
    return !(version_compare(CRM_Core_BAO_Domain::version(), $daoVersion, '<'));
  }

  /**
   * @return string
   *   Version in which table was added
   */
  protected static function getTableAddVersion(): string {
    return defined(static::class . '::TABLE_ADDED') ? constant(static::class . '::TABLE_ADDED') : '1.0';
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
    $params = [1 => [$tableName, 'String']];

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    return (bool) $dao->fetch();
  }

  /**
   * Check if a given table has data.
   *
   * @param string $tableName
   * @return bool
   *   TRUE if $tableName has at least one record.
   */
  public static function checkTableHasData($tableName) {
    $c = CRM_Core_DAO::singleValueQuery(sprintf('SELECT count(*) c FROM `%s`', $tableName));
    return $c > 0;
  }

  /**
   * Find a DAO object for the given ID and return it.
   *
   * @param int $id
   *   Id of the DAO object being searched for.
   *
   * @return static
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
  public function fetchAll($k = FALSE, $v = FALSE, $method = FALSE) {
    $result = [];
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
    $result = [];
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
   * @return string|int|null
   *   Value of $returnColumn in the retrieved record
   *
   * @throws \CRM_Core_Exception
   */
  public static function getFieldValue($daoName, $searchValue, $returnColumn = 'name', $searchColumn = 'id', $force = FALSE) {
    if (
      empty($searchValue) ||
      trim(strtolower($searchValue)) == 'null'
    ) {
      // adding this here since developers forget to check for an id
      // or for the 'null' (which is a bad DAO kludge)
      // and hence we get the first value in the db
      throw new CRM_Core_Exception('getFieldValue failed');
    }

    self::$_dbColumnValueCache ??= [];

    while (str_contains($daoName, '_BAO_')) {
      $daoName = get_parent_class($daoName);
    }

    if ($force ||
      empty(self::$_dbColumnValueCache[$daoName][$searchColumn][$searchValue]) ||
      !array_key_exists($returnColumn, self::$_dbColumnValueCache[$daoName][$searchColumn][$searchValue])
    ) {
      $object = new $daoName();
      $object->$searchColumn = $searchValue;
      $object->selectAdd();
      $object->selectAdd($returnColumn);

      $result = NULL;
      if ($object->find(TRUE)) {
        $result = $object->$returnColumn;
      }

      self::$_dbColumnValueCache[$daoName][$searchColumn][$searchValue][$returnColumn] = $result;
    }
    return self::$_dbColumnValueCache[$daoName][$searchColumn][$searchValue][$returnColumn];
  }

  /**
   * Fetch a single field value from the database.
   *
   * Uses static caching and applies formatting.
   *
   * @param string $returnColumn
   * @param string|int $searchValue
   * @param string $searchColumn
   * @return array|bool|int|string|null
   *   Returned value will be formatted according to data type.
   * @throws CRM_Core_Exception
   */
  public static function getDbVal(string $returnColumn, $searchValue, string $searchColumn = 'id') {
    $fieldSpec = static::getSupportedFields()[$returnColumn] ?? NULL;
    $value = $fieldSpec ? self::getFieldValue(static::class, $searchValue, $returnColumn, $searchColumn) : NULL;
    return self::formatFieldValue($value, $fieldSpec);
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
   * Unused function.
   * @deprecated in 5.72 will be removed in 5.85
   */
  public static function getSortString($sort, $default = NULL) {
    CRM_Core_Error::deprecatedFunctionWarning('No alternative');
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
   * @internal - extensions should always use the api
   *
   * @param string $daoName
   *   Name of the dao class.
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference) an assoc array to hold the flattened values.
   * @param array $returnProperities
   *   An assoc array of fields that need to be returned, e.g. ['first_name', 'last_name'].
   *
   * @return static|null
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
   * Unused function.
   *
   * @deprecated in 5.47 will be removed in 5.80
   */
  public static function deleteEntityContact($daoName, $contactId) {
    CRM_Core_Error::deprecatedFunctionWarning('APIv4');
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
  public static function executeUnbufferedQuery(
    $query,
    $params = [],
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
      ['result_buffering' => 0]
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
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function &executeQuery(
    $query,
    $params = [],
    $abort = TRUE,
    $daoName = NULL,
    $freeDAO = FALSE,
    $i18nRewrite = TRUE,
    $trapException = FALSE,
    $options = []
  ) {
    $queryStr = self::composeQuery($query, $params, $abort);

    if (!$daoName) {
      $dao = new CRM_Core_DAO();
    }
    else {
      $dao = new $daoName();
    }

    if ($dao->isValidOption($options)) {
      $dao->setOptions($options);
    }

    $result = $dao->query($queryStr, $i18nRewrite);

    // since it is unbuffered, ($dao->N==0) is true.  This blocks the standard fetch() mechanism.
    if (($options['result_buffering'] ?? NULL) === 0) {
      $dao->N = TRUE;
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
    $validOptions = [
      'result_buffering',
      'persistent',
      'ssl',
      'portability',
    ];

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
   *
   * @return string|null
   *   the result of the query if any
   *
   * @throws \CRM_Core_Exception
   */
  public static function &singleValueQuery(
    $query,
    $params = [],
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
   * @throws CRM_Core_Exception
   */
  public static function composeQuery($query, $params = [], $abort = TRUE) {
    $tr = [];
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
          throw new CRM_Core_Exception("{$item[0]} is not of type {$item[1]}");
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
   * @param bool $blockCopyofCustomValues
   *   Case when you don't want to copy the custom values set in a
   *   template as it will override/ignore the submitted custom values
   *
   * @return CRM_Core_DAO|bool
   *   the newly created copy of the object. False if none created.
   */
  public static function copyGeneric($daoName, $criteria, $newData = NULL, $fieldsFix = NULL, $blockCopyOfDependencies = NULL, $blockCopyofCustomValues = FALSE) {
    $object = new $daoName();
    $newObject = FALSE;
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
      $fieldsToPrefix = [];
      $fieldsToSuffix = [];
      $fieldsToReplace = [];
      if (!empty($fieldsFix['prefix'])) {
        $fieldsToPrefix = $fieldsFix['prefix'];
      }
      if (!empty($fieldsFix['suffix'])) {
        $fieldsToSuffix = $fieldsFix['suffix'];
      }
      if (!empty($fieldsFix['replace'])) {
        $fieldsToReplace = $fieldsFix['replace'];
      }

      $localizableFields = FALSE;
      foreach ($fields as $name => $value) {
        if ($name === 'id' || $value['name'] === 'id') {
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

        if ($type === 'Timestamp' || $type === 'Date') {
          $newObject->$dbName = CRM_Utils_Date::isoToMysql($newObject->$dbName);
        }

        if (!empty($value['localizable'])) {
          $localizableFields = TRUE;
        }

        if ($newData) {
          $newObject->copyValues($newData);
        }
      }
      if (!empty($fields['name'])) {
        $newObject->makeNameFromLabel();
      }
      $newObject->save();

      // ensure we copy all localized fields as well
      if (CRM_Core_I18n::isMultilingual() && $localizableFields) {
        global $dbLocale;
        $locales = CRM_Core_I18n::getMultilingual();
        $curLocale = CRM_Core_I18n::getLocale();
        // loop on other locales
        foreach ($locales as $locale) {
          if ($locale != $curLocale) {
            // setLocale doesn't seems to be reliable to set dbLocale and we only need to change the db locale
            $dbLocale = '_' . $locale;
            $newObject->copyLocalizable($object->id, $newObject->id, $fieldsToPrefix, $fieldsToSuffix, $fieldsToReplace);
          }
        }
        // restore dbLocale to starting value
        $dbLocale = '_' . $curLocale;
      }

      if (!$blockCopyofCustomValues) {
        $newObject->copyCustomFields($object->id, $newObject->id);
      }
      CRM_Utils_Hook::post('create', CRM_Core_DAO_AllCoreTables::getEntityNameForClass($daoName), $newObject->id, $newObject);
    }

    return $newObject;
  }

  /**
   * Method that copies localizable fields from an old entity to a new one.
   *
   * Fixes bug dev/core#2479,
   * where non current locale fields are copied from current locale losing translation when copying
   *
   * @param int $entityID
   * @param int $newEntityID
   * @param array $fieldsToPrefix
   * @param array $fieldsToSuffix
   * @param array $fieldsToReplace
   */
  protected function copyLocalizable($entityID, $newEntityID, $fieldsToPrefix, $fieldsToSuffix, $fieldsToReplace) {
    $entity = get_class($this);
    $object = new $entity();
    $object->id = $entityID;
    $object->find();

    $newObject = new $entity();
    $newObject->id = $newEntityID;

    $newObject->find();

    if ($object->fetch() && $newObject->fetch()) {

      $fields = $object->fields();
      foreach ($fields as $name => $value) {

        if ($name == 'id' || $value['name'] == 'id') {
          // copy everything but the id!
          continue;
        }

        // only copy localizable fields
        if (!$value['localizable']) {
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

      }
      $newObject->save();

    }
  }

  /**
   * Method that copies custom fields values from an old entity to a new one.
   *
   * Fixes bug CRM-19302,
   * where if a custom field of File type was present, left both events using the same file,
   * breaking download URL's for the old event.
   *
   * @todo the goal here is to clean this up so that it works for any entity. Copy Generic already DOES some custom field stuff
   * but it seems to be bypassed & perhaps less good than this (or this just duplicates it...)
   *
   * @param int $entityID
   * @param int $newEntityID
   * @param string $parentOperation
   */
  public function copyCustomFields($entityID, $newEntityID, $parentOperation = NULL) {
    $entity = CRM_Core_DAO_AllCoreTables::getEntityNameForClass(get_class($this));
    $tableName = CRM_Core_DAO_AllCoreTables::getTableForClass(get_class($this));
    // Obtain custom values for the old entity.
    $customParams = $htmlType = [];
    $customValues = CRM_Core_BAO_CustomValueTable::getEntityValues($entityID, $entity);

    // If custom values present, we copy them
    if (!empty($customValues)) {
      // Get Field ID's and identify File type attributes, to handle file copying.
      $fieldIds = implode(', ', array_keys($customValues));
      $sql = "SELECT id FROM civicrm_custom_field WHERE html_type = 'File' AND id IN ( {$fieldIds} )";
      $result = CRM_Core_DAO::executeQuery($sql);

      // Build array of File type fields
      while ($result->fetch()) {
        $htmlType[] = $result->id;
      }

      // Build params array of custom values
      foreach ($customValues as $field => $value) {
        if ($value !== NULL) {
          // Handle File type attributes
          if (in_array($field, $htmlType)) {
            $fileValues = CRM_Core_BAO_File::path($value, $entityID);
            $customParams["custom_{$field}_-1"] = [
              'name' => CRM_Utils_File::duplicate($fileValues[0]),
              'type' => $fileValues[1],
            ];
          }
          // Handle other types
          else {
            $customParams["custom_{$field}_-1"] = $value;
          }
        }
      }

      // Save Custom Fields for new Entity.
      CRM_Core_BAO_CustomValueTable::postProcess($customParams, $tableName, $newEntityID, $entity, $parentOperation ?? 'create');
    }

    // copy activity attachments ( if any )
    CRM_Core_BAO_File::copyEntityFile($tableName, $entityID, $tableName, $newEntityID);
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
  public static function cascadeUpdate($daoName, $fromId, $toId, $newData = []) {
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
    $contactIDs = [];

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
  public static function commonRetrieveAll($daoName, $fieldIdName, $fieldId, &$details, $returnProperities = NULL) {
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
      $defaults = [];
      self::storeValues($object, $defaults);
      $details[$object->id] = $defaults;
    }

    return $details;
  }

  /**
   * Drop all CiviCRM tables.
   *
   * @throws \CRM_Core_Exception
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
    if ($string === NULL) {
      return '';
    }
    if (isset($GLOBALS['CIVICRM_SQL_ESCAPER'])) {
      return call_user_func($GLOBALS['CIVICRM_SQL_ESCAPER'], $string);
    }
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
        $search = ["\\", "\x00", "\n", "\r", "'", '"', "\x1a"];
        $replace = ["\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z"];
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

    $escapes = array_map([$_dao, 'escape'], $strings);
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
        ['%', '_', '%%', '_%']
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
    $params = [],
    $numObjects = 1,
    $createOnly = FALSE
  ) {
    //this is a test function  also backtrace is set for the test suite it sometimes unsets itself
    // so we re-set here in case
    $config = CRM_Core_Config::singleton();
    $config->backtrace = TRUE;

    static $counter = 0;
    CRM_Core_DAO::$_testEntitiesToSkip = [
      'CRM_Core_DAO_Worldregion',
      'CRM_Core_DAO_StateProvince',
      'CRM_Core_DAO_Country',
      'CRM_Core_DAO_Domain',
      'CRM_Financial_DAO_FinancialType',
      //because valid ones exist & we use pick them due to pseudoconstant can't reliably create & delete these
    ];

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
        $FKClassName = $fieldDef['FKClassName'] ?? NULL;

        if (isset($params[$dbName]) && !is_array($params[$dbName])) {
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
  public static function deleteTestObjects($daoName, $params = []) {
    //this is a test function  also backtrace is set for the test suite it sometimes unsets itself
    // so we re-set here in case
    $config = CRM_Core_Config::singleton();
    $config->backtrace = TRUE;

    $object = new $daoName();
    $object->id = $params['id'] ?? NULL;

    // array(array(0 => $daoName, 1 => $daoParams))
    $deletions = [];
    if ($object->find(TRUE)) {

      $fields = $object->fields();
      foreach ($fields as $name => $value) {

        $dbName = $value['name'];

        $FKClassName = $value['FKClassName'] ?? NULL;
        $required = $value['required'] ?? NULL;
        if ($FKClassName != NULL
          && $object->$dbName
          && !in_array($FKClassName, CRM_Core_DAO::$_testEntitiesToSkip)
          && ($required || $dbName == 'contact_id')
          //I'm a bit stuck on this one - we might need to change the singleValueAlter so that the entities don't share a contact
          // to make this test process pass - line below makes pass for now
          && $dbName != 'member_of_contact_id'
        ) {
          // x
          $deletions[] = [$FKClassName, ['id' => $object->$dbName]];
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
   * @param bool $view
   * @param bool $trigger
   *
   * @return bool
   */
  public static function checkTriggerViewPermission($view = TRUE, $trigger = TRUE) {
    if (\Civi::settings()->get('logging_no_trigger_permission')) {
      return TRUE;
    }
    // test for create view and trigger permissions and if allowed, add the option to go multilingual and logging
    $dao = new CRM_Core_DAO();
    try {
      if ($view) {
        $dao->query('CREATE OR REPLACE VIEW civicrm_domain_view AS SELECT * FROM civicrm_domain');
        $dao->query('DROP VIEW IF EXISTS civicrm_domain_view');
      }

      if ($trigger) {
        $dao->query('CREATE TRIGGER civicrm_domain_trigger BEFORE INSERT ON civicrm_domain FOR EACH ROW BEGIN END');
        $dao->query('DROP TRIGGER IF EXISTS civicrm_domain_trigger');
      }
    }
    catch (Exception $e) {
      return FALSE;
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
      $q = [];
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
    $result = [];
    $fields = $className::fields();
    foreach ($fields as $field) {
      if (isset($field['pseudoconstant'], $field['pseudoconstant']['optionGroupName'])) {
        $result[] = new CRM_Core_Reference_OptionValue(
          $className::getTableName(),
          $field['name'],
          'civicrm_option_value',
          $field['pseudoconstant']['keyColumn'] ?? 'value',
          $field['pseudoconstant']['optionGroupName']
        );
      }
    }
    return $result;
  }

  /**
   * Find all records which refer to this entity.
   *
   * @return CRM_Core_DAO[]
   */
  public function findReferences() {
    $links = self::getReferencesToTable(static::getTableName());

    $occurrences = [];
    foreach ($links as $refSpec) {
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
   * @return array{name: string, type: string, count: int, table: string|null, key: string|null}[]
   *   each item has keys:
   *   - name: string
   *   - type: string
   *   - count: int
   *   - table: string|null SQL table name
   *   - key: string|null SQL column name
   */
  public function getReferenceCounts() {
    $links = self::getReferencesToTable(static::getTableName());

    $counts = [];
    foreach ($links as $refSpec) {
      $count = $refSpec->getReferenceCount($this);
      if (!empty($count['count'])) {
        $counts[] = $count;
      }
    }

    foreach (CRM_Core_Component::getEnabledComponents() as $component) {
      $counts = array_merge($counts, $component->getReferenceCounts($this));
    }
    CRM_Utils_Hook::referenceCounts($this, $counts);

    return $counts;
  }

  /**
   * List all tables which have either:
   * - hard foreign keys to this table, or
   * - a dynamic foreign key that includes this table as a possible target.
   *
   * @param string $tableName
   *   Table referred to.
   *
   * @return CRM_Core_Reference_Interface[]
   *   structure of table and column, listing every table with a
   *   foreign key reference to $tableName, and the column where the key appears.
   */
  public static function getReferencesToTable($tableName) {
    $refsFound = [];
    foreach (CRM_Core_DAO_AllCoreTables::getClasses() as $daoClassName) {
      $links = $daoClassName::getReferenceColumns();

      foreach ($links as $refSpec) {
        /** @var CRM_Core_Reference_Interface $refSpec */
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
   *
   * @throws \CRM_Core_Exception
   */
  public static function getReferencesToContactTable() {
    $contactReferences = [];
    $coreReferences = CRM_Core_DAO::getReferencesToTable('civicrm_contact');
    foreach ($coreReferences as $coreReference) {
      if (
        // Exclude option values
        !is_a($coreReference, 'CRM_Core_Reference_Dynamic') &&
        // Exclude references to other columns
        $coreReference->getTargetKey() === 'id'
      ) {
        $contactReferences[$coreReference->getReferenceTable()][] = $coreReference->getReferenceKey();
      }
    }
    self::appendCustomTablesExtendingContacts($contactReferences);
    self::appendCustomContactReferenceFields($contactReferences);
    return $contactReferences;
  }

  /**
   * Get all dynamic references to the given table.
   *
   * @param string $tableName
   *
   * @return array
   */
  public static function getDynamicReferencesToTable($tableName) {
    if (!isset(\Civi::$statics[__CLASS__]['contact_references_dynamic'][$tableName])) {
      \Civi::$statics[__CLASS__]['contact_references_dynamic'][$tableName] = [];
      $coreReferences = CRM_Core_DAO::getReferencesToTable($tableName);
      foreach ($coreReferences as $coreReference) {
        if ($coreReference instanceof \CRM_Core_Reference_Dynamic) {
          \Civi::$statics[__CLASS__]['contact_references_dynamic'][$tableName][$coreReference->getReferenceTable()][] = [$coreReference->getReferenceKey(), $coreReference->getTypeColumn()];
        }
      }
    }
    return \Civi::$statics[__CLASS__]['contact_references_dynamic'][$tableName];
  }

  /**
   * Add custom tables that extend contacts to the list of contact references.
   *
   * @internal
   * Includes all contact custom groups including inactive, multiple & subtypes.
   *
   * @param array $cidRefs
   */
  public static function appendCustomTablesExtendingContacts(&$cidRefs) {
    $customGroups = CRM_Core_BAO_CustomGroup::getAll(['extends' => 'Contact']);
    foreach ($customGroups as $customGroup) {
      $cidRefs[$customGroup['table_name']][] = 'entity_id';
    }
  }

  /**
   * Add custom ContactReference fields to the list of contact references.
   *
   * @internal
   * Includes both ContactReference and EntityReference type fields.
   * Includes active and inactive fields/groups
   *
   * @param array $cidRefs
   */
  public static function appendCustomContactReferenceFields(&$cidRefs) {
    $contactTypes = array_merge(['Contact'], CRM_Contact_BAO_ContactType::basicTypes(TRUE));
    foreach (CRM_Core_BAO_CustomGroup::getAll() as $customGroup) {
      foreach ($customGroup['fields'] as $field) {
        if (
          $field['data_type'] === 'ContactReference' ||
          in_array($field['fk_entity'], $contactTypes, TRUE)
        ) {
          $cidRefs[$customGroup['table_name']][] = $field['column_name'];
        }
      }
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
    foreach ($fields as $fieldUniqueName => $field) {
      if (!empty($field['pseudoconstant'])) {
        $pseudoConstant = $field['pseudoconstant'];
        if (!empty($pseudoConstant['optionGroupName'])) {
          $fields[$pseudoConstant['optionGroupName']] = [
            'title' => CRM_Core_BAO_OptionGroup::getTitleByName($pseudoConstant['optionGroupName']),
            'name' => $pseudoConstant['optionGroupName'],
            'data_type' => CRM_Utils_Type::T_STRING,
            'is_pseudofield_for' => $fieldUniqueName,
          ];
        }
        // We restrict to id + name + FK as we are extending this a bit, but cautiously.
        elseif (
          !empty($field['FKClassName'])
          && ($pseudoConstant['keyColumn'] ?? NULL) === 'id'
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
   * Legacy field options getter.
   *
   * @deprecated in favor of `Civi::entity()->getOptions()`
   *
   * Overriding this function is no longer recommended as a way to customize options.
   * Instead, option lists can be customized by either:
   * 1. Using a pseudoconstant callback
   * 2. Implementing hook_civicrm_fieldOptions
   *
   * @param string $fieldName
   * @param string $context
   * @see CRM_Core_DAO::buildOptionsContext
   * @param array $values
   *   Raw field values; whatever is known about this bao object.
   *
   * Note: $values can contain unsanitized input and should be handled with care by CRM_Core_PseudoConstant::get
   *
   * @return array|bool
   */
  public static function buildOptions($fieldName, $context = NULL, $values = []) {
    $entityName = CRM_Core_DAO_AllCoreTables::getEntityNameForClass(get_called_class());
    $entity = Civi::entity($entityName);
    $legacyFieldName = $fieldName;
    // Legacy handling for custom field names in `custom_123` format
    if (str_starts_with($fieldName, 'custom_') && is_numeric($fieldName[7] ?? '')) {
      $fieldName = CRM_Core_BAO_CustomField::getLongNameFromShortName($fieldName) ?? $fieldName;
    }
    // Legacy handling for field "unique name"
    elseif (!$entity->getField($fieldName)) {
      $uniqueNames = static::fieldKeys();
      $fieldName = array_search($fieldName, $uniqueNames) ?: $fieldName;
    }
    // Legacy handling for hook-based fields from `fields_callback`
    if (!$entity->getField($fieldName)) {
      return CRM_Core_PseudoConstant::get(static::class, $legacyFieldName, [], $context);
    }
    $checkPermissions = (bool) ($values['check_permissions'] ?? ($context == 'create' || $context == 'search'));
    $includeDisabled = ($context == 'validate' || $context == 'get');
    $options = $entity->getOptions($fieldName, $values, $includeDisabled, $checkPermissions);
    return $options ? CRM_Core_PseudoConstant::formatArrayOptions($context, $options) : $options;
  }

  /**
   * Populate option labels for this object's fields.
   *
   * @deprecated
   * @throws exception if called directly on the base class
   */
  public function getOptionLabels() {
    CRM_Core_Error::deprecatedFunctionWarning('Civi::entity()->getOptions');
    $fields = $this->fields();
    if ($fields === NULL) {
      throw new Exception('Cannot call getOptionLabels on CRM_Core_DAO');
    }
    foreach ($fields as $field) {
      $name = $field['name'] ?? NULL;
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
   * @throws CRM_Core_Exception
   * @return array
   */
  public static function buildOptionsContext($context = NULL) {
    $contexts = [
      'get' => "get: all options are returned, even if they are disabled; labels are translated.",
      'create' => "create: options are filtered appropriately for the object being created/updated; labels are translated.",
      'search' => "search: searchable options are returned; labels are translated.",
      'validate' => "validate: all options are returned, even if they are disabled; machine names are used in place of labels.",
      'abbreviate' => "abbreviate: enabled options are returned; labels are replaced with abbreviations.",
      'match' => "match: enabled options are returned using machine names as keys; labels are translated.",
    ];
    // Validation: enforce uniformity of this param
    if ($context !== NULL && !isset($contexts[$context])) {
      throw new CRM_Core_Exception("'$context' is not a valid context for buildOptions.");
    }
    return $contexts;
  }

  /**
   * @param string $fieldName
   * @return bool|array
   */
  public function getFieldSpec($fieldName) {
    $fields = $this->fields();

    // Support "unique names" as well as sql names
    $fieldKey = $fieldName;
    if (empty($fields[$fieldKey])) {
      $fieldKeys = $this->fieldKeys();
      $fieldKey = $fieldKeys[$fieldName] ?? NULL;
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
      $emojiFilter = CRM_Utils_SQL::handleEmojiInQuery($criteria);
      if ($emojiFilter === '0 = 1') {
        return $emojiFilter;
      }

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
              // not yet implemented (tests required to implement)
              return NULL;
            }
            break;

          // n-ary operators
          case 'IN':
          case 'NOT IN':
            if (empty($criteria)) {
              throw new Exception("invalid criteria for $operator");
            }
            $escapedCriteria = array_map([
              'CRM_Core_DAO',
              'escapeString',
            ], $criteria);
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
              // not yet implemented (tests required to implement)
              return NULL;
            }
        }
      }
    }
  }

  /**
   * @see http://issues.civicrm.org/jira/browse/CRM-9150
   * support for other syntaxes is discussed in ticket but being put off for now
   * @return string[]
   */
  public static function acceptedSQLOperators() {
    return [
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
    ];
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
   * ```
   *   [
   *     // Each string in the array will get joined with AND
   *     'location_type_id' => ['IS NOT NULL', 'IN (1,2,3)'],
   *     // Each sub-array in the array will get joined with OR, field names must be enclosed in curly braces
   *     'privacy' => [
   *                    ['= 0', '= 1 AND {contact_id} = 456'],
   *                  ],
   *   ]
   * ```
   *
   * Note that all array keys must be actual field names in this entity. Use subqueries to filter on other tables e.g. custom values.
   * The query strings MAY reference other fields in this entity; they must be enclosed in {curly_braces}.
   *
   * @param string|null $entityName
   *   Name of the entity being queried (for normal BAO files implementing this method, this variable is redundant
   *   as there is a 1-1 relationship between most entities and most BAOs. However the variable is passed in to support
   *   dynamic entities such as ECK).
   * @param int|null $userId
   *   Contact id of the current user.
   *   This param is more aspirational than functional for now. Someday the API may support checking permissions
   *   for contacts other than the current user, but at present this is always NULL which defaults to the current user.
   * @param array $conditions
   *   Contains field/value pairs gleaned from the WHERE clause or ON clause
   *   (depending on how the entity was added to the query).
   *   Can be used for optimization/deduping of clauses.
   * @return array
   */
  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    $clauses = [];
    $fields = $this::getSupportedFields();
    foreach ($fields as $fieldName => $field) {
      // Clause for contact-related entities like Email, Relationship, etc.
      if (str_starts_with($fieldName, 'contact_id') && ($field['FKClassName'] ?? NULL) === 'CRM_Contact_DAO_Contact') {
        $contactClause = CRM_Utils_SQL::mergeSubquery('Contact');
        if (!empty($contactClause)) {
          $clauses[$fieldName] = $contactClause;
        }
      }
      // Clause for an entity_table/entity_id combo
      if ($fieldName === 'entity_id' && isset($fields['entity_table'])) {
        $relatedClauses = self::getDynamicFkAclClauses('entity_table', 'entity_id', $conditions['entity_table'] ?? NULL);
        if ($relatedClauses) {
          // Nested array will be joined with OR
          $clauses['entity_table'] = [$relatedClauses];
        }
      }
    }
    CRM_Utils_Hook::selectWhereClause($entityName ?? $this, $clauses);
    return $clauses;
  }

  /**
   * Get an array of ACL clauses for a dynamic FK (entity_id/entity_table combo)
   *
   * @param string $entityTableField
   * @param string $entityIdField
   * @param mixed|NULL $entityTableValues
   * @return array
   */
  protected static function getDynamicFkAclClauses(string $entityTableField, string $entityIdField, $entityTableValues = NULL): array {
    // If entity_table is specified in the WHERE clause, use that instead of the entity_table pseudoconstant
    if ($entityTableValues && is_string($entityTableValues) || is_array($entityTableValues)) {
      // Ideally we would validate table names against the entity_table pseudoconstant,
      // but some entities have missing/incomplete metadata and it's better to generate an ACL
      // clause for what we have than no ACL clause at all, so validate against all known tables.
      $allTableNames = CRM_Core_DAO_AllCoreTables::tables();
      $relatedEntities = array_intersect_key(array_flip((array) $entityTableValues), $allTableNames);
    }
    // No valid entity_table in WHERE clause so build an ACL case for every enabled entity type
    if (empty($relatedEntities)) {
      $relatedEntities = static::buildOptions($entityTableField, 'create');
    }
    // Hmm, this entity is missing entity_table pseudoconstant. We really should fix that.
    if (!$relatedEntities) {
      return [];
    }
    $relatedClauses = [];
    foreach ($relatedEntities as $table => $ent) {
      // Ensure $ent is the machine name of the entity not a translated title
      $ent = CRM_Core_DAO_AllCoreTables::getEntityNameForTable($table);
      // Skip if entity doesn't exist. This shouldn't happen, but better safe than sorry.
      if (!$ent) {
        continue;
      }
      // Prevent infinite recursion
      $subquery = $table === static::getTableName() ? NULL : CRM_Utils_SQL::mergeSubquery($ent);
      if ($subquery) {
        foreach ($subquery as $index => $condition) {
          // Join OR clauses
          if (is_array($condition)) {
            $subquery[$index] = "(({{$entityIdField}} " . implode(") OR ({{$entityIdField}} ", $condition) . '))';
          }
          else {
            $subquery[$index] = "{{$entityIdField}} $condition";
          }
        }
        $relatedClauses[] = "= '$table' AND " . implode(" AND ", $subquery);
      }
      // If it's the only value with no conditions, don't need to add it
      elseif (!$entityTableValues || count($relatedEntities) > 1) {
        $relatedClauses[] = "= '$table'";
      }
    }
    return $relatedClauses;
  }

  /**
   * This returns the final permissioned query string for this entity
   *
   * With acls from related entities + additional clauses from hook_civicrm_selectWhereClause
   *
   * @param string|null $tableAlias
   * @param string|null $entityName
   * @param array $conditions
   *   Values from WHERE or ON clause
   * @param int|null $userId
   *
   * @return string[]
   */
  final public static function getSelectWhereClause(?string $tableAlias = NULL, ?string $entityName = NULL, array $conditions = [], ?int $userId = NULL) {
    $bao = new static();
    $tableAlias ??= $bao->tableName();
    $entityName ??= CRM_Core_DAO_AllCoreTables::getEntityNameForClass(get_class($bao));
    $finalClauses = [];
    $fields = static::getSupportedFields();
    $selectWhereClauses = $bao->addSelectWhereClause($entityName, $userId, $conditions);
    foreach ($selectWhereClauses as $fieldName => $fieldClauses) {
      $finalClauses[$fieldName] = NULL;
      if ($fieldClauses) {
        if (!is_array($fieldClauses)) {
          CRM_Core_Error::deprecatedWarning('Expected array of selectWhereClauses for ' . $bao->tableName() . '.' . $fieldName . ', instead got ' . json_encode($fieldClauses));
          $fieldClauses = (array) $fieldClauses;
        }
        $formattedClauses = [];
        foreach (CRM_Utils_SQL::prefixFieldNames($fieldClauses, array_keys($fields), $tableAlias) as $subClause) {
          // Arrays of arrays get joined with OR (similar to CRM_Core_Permission::check)
          if (is_array($subClause)) {
            $formattedClauses[] = "(`$tableAlias`.`$fieldName` " . implode(" OR `$tableAlias`.`$fieldName` ", $subClause) . ')';
          }
          else {
            $formattedClauses[] = "(`$tableAlias`.`$fieldName` " . $subClause . ')';
          }
        }
        $finalClauses[$fieldName] = '(' . implode(' AND ', $formattedClauses) . ')';
        if (empty($fields[$fieldName]['required'])) {
          $finalClauses[$fieldName] = "(`$tableAlias`.`$fieldName` IS NULL OR {$finalClauses[$fieldName]})";
        }
      }
    }
    return $finalClauses;
  }

  /**
   * ensure database name is 'safe', i.e. only contains word characters (includes underscores)
   * and dashes, and contains at least one [a-z] case insensitive.
   *
   * @param $database
   *
   * @return bool
   */
  public static function requireSafeDBName($database) {
    $matches = [];
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
   * @param array|null $value
   * @param int $serializationType
   * @return string|null
   *
   * @throws \Exception
   */
  public static function serializeField($value, $serializationType) {
    if ($value === NULL) {
      return NULL;
    }
    switch ($serializationType) {
      case self::SERIALIZE_SEPARATOR_BOOKEND:
        return ($value === [] || $value === '') ? '' : CRM_Utils_Array::implodePadded($value);

      case self::SERIALIZE_SEPARATOR_TRIMMED:
        return is_array($value) ? implode(self::VALUE_SEPARATOR, $value) : $value;

      case self::SERIALIZE_JSON:
        return is_array($value) ? json_encode($value) : $value;

      case self::SERIALIZE_PHP:
        return is_array($value) ? serialize($value) : $value;

      case self::SERIALIZE_COMMA:
        return is_array($value) ? implode(',', $value) : $value;

      case self::SERIALIZE_COMMA_KEY_VALUE:
        return is_array($value) ? CRM_Utils_CommaKV::serialize($value) : $value;

      default:
        throw new Exception('Unknown serialization method for field.');
    }
  }

  /**
   * Transform a serialized string from the database into an array.
   *
   * @param string|null $value
   * @param $serializationType
   *
   * @return array|null
   * @throws CRM_Core_Exception
   */
  public static function unSerializeField($value, $serializationType) {
    if ($value === NULL) {
      return NULL;
    }
    if ($value === '') {
      return [];
    }
    switch ($serializationType) {
      case self::SERIALIZE_SEPARATOR_BOOKEND:
        return (array) CRM_Utils_Array::explodePadded($value);

      case self::SERIALIZE_SEPARATOR_TRIMMED:
        return explode(self::VALUE_SEPARATOR, trim($value));

      case self::SERIALIZE_JSON:
        return strlen($value) ? json_decode($value, TRUE) : [];

      case self::SERIALIZE_PHP:
        return strlen($value) ? CRM_Utils_String::unserialize($value) : [];

      case self::SERIALIZE_COMMA:
        return explode(',', trim(str_replace(', ', '', $value)));

      case self::SERIALIZE_COMMA_KEY_VALUE:
        return CRM_Utils_CommaKV::unserialize($value);

      default:
        throw new CRM_Core_Exception('Unknown serialization method for field.');
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

  /**
   * Remove item from static cache during update/delete operations
   */
  private function clearDbColumnValueCache() {
    $daoName = get_class($this);
    while (str_contains($daoName, '_BAO_')) {
      $daoName = get_parent_class($daoName);
    }
    if (isset($this->id)) {
      unset(self::$_dbColumnValueCache[$daoName]['id'][$this->id]);
    }
    if (isset($this->name)) {
      unset(self::$_dbColumnValueCache[$daoName]['name'][$this->name]);
    }
  }

  /**
   * Return a mapping from field-name to the corresponding key (as used in fields()).
   *
   * @return array
   *   [string $name => string $uniqueName]
   */
  public static function fieldKeys() {
    $fields = static::fields();
    return array_combine(array_column($fields, 'name'), array_keys($fields));
  }

  /**
   * Returns system paths related to this entity (as defined in the xml schema)
   *
   * @return array
   */
  public static function getEntityPaths() {
    return static::$_paths ?? [];
  }

  /**
   * Overridable function to get icon for a particular entity.
   *
   * Example: `CRM_Contact_BAO_Contact::getEntityIcon('Contact', 123)`
   *
   * @param string $entityName
   *   Short name of the entity. This may seem redundant because the entity name can usually be inferred
   *   from the BAO class being called, but not always. Some virtual entities share a BAO class.
   * @param int|null $entityId
   *   Id of the entity.
   * @throws CRM_Core_Exception
   */
  public static function getEntityIcon(string $entityName, ?int $entityId = NULL): ?string {
    // By default, just return the icon representing this entity. If there's more complex lookup to do,
    // the BAO for this entity should override this method.
    return static::$_icon;
  }

  /**
   * When creating a record without a supplied name,
   * create a unique, clean name derived from the label.
   *
   * Note: this function does nothing unless a unique index exists for "name" column.
   */
  private function makeNameFromLabel(): void {
    $indexNameWith = NULL;
    // Look for a unique index which includes the "name" field
    if (method_exists($this, 'indices')) {
      foreach ($this->indices(FALSE) as $index) {
        if (!empty($index['unique']) && in_array('name', $index['field'], TRUE)) {
          $indexNameWith = $index['field'];
        }
      }
    }
    if (!$indexNameWith) {
      // No unique index on "name", do nothing
      return;
    }
    $labelField = $this::getLabelField();
    $label = $this->$labelField ?? NULL;
    if (!$label && $label !== '0') {
      // No label supplied, do nothing
      return;
    }

    // Strip unsafe characters and trim to max length, allowing room for a
    // unique suffix composed of an underscore + 4 alphanumeric chars,
    // supporting up to 36^4=1,679,616 unique names for any given value of
    // $label. Half that amount could be considered the working limit, as
    // much above that the time to find a non-existent suffix becomes
    // unacceptable.
    $maxSuffixLen = 5;
    $maxLen = static::getSupportedFields()['name']['maxlength'] ?? 255;
    $name = CRM_Utils_String::munge($label, '_', $maxLen - $maxSuffixLen);

    // Define an arbitrary limit on how many guesses we will perform before
    // throwing an exception. This would occur only in some unanticipated use
    // case.
    $max_guesses = 36 ^ ($maxSuffixLen - 1);

    $guesses_per_loop = 5;
    $guess_count = 0;

    do {
      // Make an initial attempt to guess a unique name by searching for
      // 5 candidates (the original $name plus $name with 4 random suffixes).
      // If all of these happen to exist in the table, we'll keep trying,
      // doubling the number of guesses each time through the loop.
      for ($i = 0; $i < $guesses_per_loop; $i++, $guess_count++) {
        $suffix = $guess_count == 0 ? '' :
          '_' . CRM_Utils_String::createRandom($maxSuffixLen - 1, 'abcdefghijklmnopqrstuvwxyz0123456789');
        $candidates[$i] = $name . $suffix;
      }

      $sql = new CRM_Utils_SQL_Select($this::getTableName());
      $sql->select(['id', 'LOWER(name) name_lc']);
      $sql->where('name IN (@candidates)', ['@candidates' => $candidates]);

      // Narrow the search by specifying the value of any additional fields
      // that may be part of the index.
      foreach (array_diff($indexNameWith, ['name']) as $field) {
        $sql->where("`$field` = @val", ['@val' => $this->$field]);
      }
      $query = $sql->toSQL();

      // Search the table for our candidates using case-sensitivity determined
      // by the collation of the name column -- case-insensitive by default.
      // Array $existing_lc will contains all the candidates found in the table,
      // converted to lower-case.
      $existing_lc = self::executeQuery($query)->fetchMap('id', 'name_lc');

      if (count($existing_lc) < $guesses_per_loop) {
        // Not all of our candidates were found in the table, so we'll search
        // for the first element of $candidates that wasn't found. This search
        // is performed case-insensitive to ensure that the selected candidate
        // is unique with both ci and cs collation of the name column. If the
        // original (unsuffixed) value of $name doesn't exist in the table, then
        // that value will be our selected candidate.
        foreach ($candidates as $c) {
          if (!in_array(strtolower($c), $existing_lc)) {
            $this->name = $c;
            return;
          }
        }
      }
      else {
        // All candidates were found in the table. Try harder next time.
        $guesses_per_loop = min(1000, $guesses_per_loop * 2);

        if ($guess_count > $max_guesses) {
          throw new CRM_Core_Exception("CRM_Core_DAO::makeNameFromLabel failed to generate a unique name for label $label.");
        }
      }
    } while (1);
  }

  /**
   * Check if component is enabled for this DAO class
   * @deprecated
   * @return bool
   */
  public static function isComponentEnabled(): bool {
    $entityName = CRM_Core_DAO_AllCoreTables::getEntityNameForClass(static::class);
    if (!$entityName) {
      return FALSE;
    }
    return \Civi\Api4\Utils\CoreUtil::entityExists($entityName);
  }

  /**
   * Given an incomplete record, attempt to fill missing field values from the database
   */
  public static function fillValues(array $existingValues, $fieldsToRetrieve): array {
    $entityFields = static::getSupportedFields();
    $idField = static::$_primaryKey[0];
    // Ensure primary key is set
    $existingValues += [$idField => NULL];
    // It's hard to look things up without an ID! Check for another unique field to use:
    if (!$existingValues[$idField] && is_callable([static::class, 'indices'])) {
      foreach (static::indices(FALSE) as $index) {
        if (!empty($index['unique']) && count($index['field']) === 1 && !empty($existingValues[$index['field'][0]])) {
          $idField = $index['field'][0];
        }
      }
    }
    $idValue = $existingValues[$idField] ?? NULL;
    foreach ($fieldsToRetrieve as $fieldName) {
      $fieldMeta = $entityFields[$fieldName] ?? ['type' => NULL];
      if (!array_key_exists($fieldName, $existingValues)) {
        $existingValues[$fieldName] = NULL;
        if ($idValue) {
          $existingValues[$fieldName] = self::getFieldValue(static::class, $idValue, $fieldName, $idField);
        }
      }
      if (isset($existingValues[$fieldName])) {
        if (!empty($fieldMeta['serialize']) && !is_array($existingValues[$fieldName])) {
          $existingValues[$fieldName] = self::unSerializeField($existingValues[$fieldName], $fieldMeta['serialize']);
        }
        elseif ($fieldMeta['type'] === CRM_Utils_Type::T_BOOLEAN) {
          $existingValues[$fieldName] = (bool) $existingValues[$fieldName];
        }
        elseif ($fieldMeta['type'] === CRM_Utils_Type::T_INT) {
          $existingValues[$fieldName] = (int) $existingValues[$fieldName];
        }
        elseif ($fieldMeta['type'] === CRM_Utils_Type::T_FLOAT) {
          $existingValues[$fieldName] = (float) $existingValues[$fieldName];
        }
      }
    }
    return $existingValues;
  }

}
