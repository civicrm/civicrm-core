<?php
/**
 *  File for the CiviUnitTestCase class
 *
 *  (PHP 5)
 *
 * @copyright Copyright CiviCRM LLC (C) 2009
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 * @package   CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 *  Include configuration
 */
define('CIVICRM_SETTINGS_PATH', __DIR__ . '/civicrm.settings.dist.php');
define('CIVICRM_SETTINGS_LOCAL_PATH', __DIR__ . '/civicrm.settings.local.php');
 
if (file_exists(CIVICRM_SETTINGS_LOCAL_PATH)) {
 require_once CIVICRM_SETTINGS_LOCAL_PATH;
}
require_once CIVICRM_SETTINGS_PATH;
/**
 *  Include class definitions
 */
require_once 'PHPUnit/Extensions/Database/TestCase.php';
require_once 'PHPUnit/Framework/TestResult.php';
require_once 'PHPUnit/Extensions/Database/DataSet/FlatXmlDataSet.php';
require_once 'PHPUnit/Extensions/Database/DataSet/XmlDataSet.php';
require_once 'PHPUnit/Extensions/Database/DataSet/QueryDataSet.php';
require_once 'tests/phpunit/Utils.php';
require_once 'api/api.php';
require_once 'CRM/Financial/BAO/FinancialType.php';
define('API_LATEST_VERSION', 3);

/**
 *  Base class for CiviCRM unit tests
 *
 *  Common functions for unit tests
 * @package CiviCRM
 */
class CiviUnitTestCase extends PHPUnit_Extensions_Database_TestCase {

  /**
   * api version - easier to override than just a defin
   */
  protected $_apiversion = API_LATEST_VERSION;
  /**
   *  Database has been initialized
   *
   * @var boolean
   */
  private static $dbInit = FALSE;

  /**
   *  Database connection
   *
   * @var PHPUnit_Extensions_Database_DB_IDatabaseConnection
   */
  protected $_dbconn;

  /**
   * The database name
   *
   * @var string
   */
  static protected $_dbName;

  /**
   * @var array of temporary directory names
   */
  protected $tempDirs;

  /**
   * @var Utils instance
   */
  public static $utils;

  /**
   * @var boolean populateOnce allows to skip db resets in setUp
   *
   *  WARNING! USE WITH CAUTION - IT'LL RENDER DATA DEPENDENCIES
   *  BETWEEN TESTS WHEN RUN IN SUITE. SUITABLE FOR LOCAL, LIMITED
   *  "CHECK RUNS" ONLY!
   *
   *  IF POSSIBLE, USE $this->DBResetRequired = FALSE IN YOUR TEST CASE!
   *
   *  see also: http://forum.civicrm.org/index.php/topic,18065.0.html
   */
  public static $populateOnce = FALSE;

  /**
   * Allow classes to state E-notice compliance
   */
  public $_eNoticeCompliant = TRUE;

  /**
   * @var boolean DBResetRequired allows skipping DB reset
   *               in specific test case. If you still need
   *               to reset single test (method) of such case, call
   *               $this->cleanDB() in the first line of this
   *               test (method).
   */
  public $DBResetRequired = TRUE;

  /**
   *  Constructor
   *
   *  Because we are overriding the parent class constructor, we
   *  need to show the same arguments as exist in the constructor of
   *  PHPUnit_Framework_TestCase, since
   *  PHPUnit_Framework_TestSuite::createTest() creates a
   *  ReflectionClass of the Test class and checks the constructor
   *  of that class to decide how to set up the test.
   *
   * @param  string $name
   * @param  array  $data
   * @param  string $dataName
   */
  function __construct($name = NULL, array$data = array(), $dataName = '') {
    parent::__construct($name, $data, $dataName);

    // we need full error reporting
    error_reporting(E_ALL & ~E_NOTICE);

    global $civicrm_db_settings;
    self::$_dbName = $civicrm_db_settings->database;

    //  create test database
    self::$utils = new Utils($civicrm_db_settings);

    // also load the class loader
    require_once 'CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();
    if (function_exists('_civix_phpunit_setUp')) { // FIXME: loosen coupling
      _civix_phpunit_setUp();
    }
  }

  function requireDBReset() {
    return $this->DBResetRequired;
  }

  static function getDBName() {
    global $civicrm_db_settings;
    return $civicrm_db_settings->database;
  }

  /**
   *  Create database connection for this instance
   *
   *  Initialize the test database if it hasn't been initialized
   *
   * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection connection
   */
  protected function getConnection() {
    $dbName = self::$_dbName;
    if (!self::$dbInit) {
      $dbName = self::getDBName();

      //  install test database
      echo PHP_EOL . "Installing {$dbName} database" . PHP_EOL;

      static::_populateDB(FALSE, $this);

      self::$dbInit = TRUE;
    }
    return $this->createDefaultDBConnection(self::$utils->pdo, $dbName);
  }

  /**
   *  Required implementation of abstract method
   */
  protected function getDataSet() {
  }

  /**
   * @param bool $perClass
   * @param null $object
   * @return bool TRUE if the populate logic runs; FALSE if it is skipped
   */
  protected static function _populateDB($perClass = FALSE, &$object = NULL) {

    if ($perClass || $object == NULL) {
      $dbreset = TRUE;
    }
    else {
      $dbreset = $object->requireDBReset();
    }

    if (self::$populateOnce || !$dbreset) {
      return FALSE;
    }
    self::$populateOnce = NULL;

    $dbName = self::getDBName();
    $pdo = self::$utils->pdo;
    // only consider real tables and not views
    $tables = $pdo->query("SELECT table_name FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = '{$dbName}' AND TABLE_TYPE = 'BASE TABLE'");

    $truncates = array();
    $drops = array();
    foreach ($tables as $table) {
      // skip log tables
      if (substr($table['table_name'], 0, 4) == 'log_') {
        continue;
      }

      // don't change list of installed extensions
      if ($table['table_name'] == 'civicrm_extension') {
        continue;
      }

      if (substr($table['table_name'], 0, 14) == 'civicrm_value_') {
        $drops[] = 'DROP TABLE ' . $table['table_name'] . ';';
      }
      else {
        $truncates[] = 'TRUNCATE ' . $table['table_name'] . ';';
      }
    }

    $queries = array(
      "USE {$dbName};",
      "SET foreign_key_checks = 0",
      // SQL mode needs to be strict, that's our standard
      "SET SQL_MODE='STRICT_ALL_TABLES';",
      "SET global innodb_flush_log_at_trx_commit = 2;",
    );
    $queries = array_merge($queries, $truncates);
    $queries = array_merge($queries, $drops);
    foreach ($queries as $query) {
      if (self::$utils->do_query($query) === FALSE) {
        //  failed to create test database
        echo "failed to create test db.";
        exit;
      }
    }

    //  initialize test database
    $sql_file2 = dirname(dirname(dirname(dirname(__FILE__)))) . "/sql/civicrm_data.mysql";
    $sql_file3 = dirname(dirname(dirname(dirname(__FILE__)))) . "/sql/test_data.mysql";
    $sql_file4 = dirname(dirname(dirname(dirname(__FILE__)))) . "/sql/test_data_second_domain.mysql";

    $query2 = file_get_contents($sql_file2);
    $query3 = file_get_contents($sql_file3);
    $query4 = file_get_contents($sql_file4);
    if (self::$utils->do_query($query2) === FALSE) {
      echo "Cannot load civicrm_data.mysql. Aborting.";
      exit;
    }
    if (self::$utils->do_query($query3) === FALSE) {
      echo "Cannot load test_data.mysql. Aborting.";
      exit;
    }
    if (self::$utils->do_query($query4) === FALSE) {
      echo "Cannot load test_data.mysql. Aborting.";
      exit;
    }

    // done with all the loading, get transactions back
    if (self::$utils->do_query("set global innodb_flush_log_at_trx_commit = 1;") === FALSE) {
      echo "Cannot set global? Huh?";
      exit;
    }

    if (self::$utils->do_query("SET foreign_key_checks = 1") === FALSE) {
      echo "Cannot get foreign keys back? Huh?";
      exit;
    }

    unset($query, $query2, $query3);

    // Rebuild triggers
    civicrm_api('system', 'flush', array('version' => 3, 'triggers' => 1));

    return TRUE;
  }

  public static function setUpBeforeClass() {
    static::_populateDB(TRUE);

    // also set this global hack
    $GLOBALS['_PEAR_ERRORSTACK_OVERRIDE_CALLBACK'] = array();
  }

  /**
   *  Common setup functions for all unit tests
   */
  protected function setUp() {
    CRM_Utils_Hook::singleton(TRUE);
    $this->errorScope = CRM_Core_TemporaryErrorScope::useException(); // REVERT
    //  Use a temporary file for STDIN
    $GLOBALS['stdin'] = tmpfile();
    if ($GLOBALS['stdin'] === FALSE) {
      echo "Couldn't open temporary file\n";
      exit(1);
    }

    //  Get and save a connection to the database
    $this->_dbconn = $this->getConnection();

    // reload database before each test
    //        $this->_populateDB();

    // "initialize" CiviCRM to avoid problems when running single tests
    // FIXME: look at it closer in second stage

    // initialize the object once db is loaded
    CRM_Core_Config::$_mail = NULL;
    $config = CRM_Core_Config::singleton();

    // when running unit tests, use mockup user framework
    $config->setUserFramework('UnitTests');

    // also fix the fatal error handler to throw exceptions,
    // rather than exit
    $config->fatalErrorHandler = 'CiviUnitTestCase_fatalErrorHandler';

    // enable backtrace to get meaningful errors
    $config->backtrace = 1;

    // disable any left-over test extensions
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_extension WHERE full_name LIKE "test.%"');

    // reset all the caches
    CRM_Utils_System::flushCache();

    // clear permissions stub to not check permissions
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = NULL;

    //flush component settings
    CRM_Core_Component::getEnabledComponents(TRUE);

    if ($this->_eNoticeCompliant) {
      error_reporting(E_ALL);
    }
    else {
      error_reporting(E_ALL & ~E_NOTICE);
    }
  }

  /**
   * emulate a logged in user since certain functions use that
   * value to store a record in the DB (like activity)
   * CRM-8180
   */
  public function createLoggedInUser() {
    $params = array(
      'first_name' => 'Logged In',
      'last_name' => 'User ' . rand(),
      'contact_type' => 'Individual',
    );
    $contactID = $this->individualCreate($params);

    $session = CRM_Core_Session::singleton();
    $session->set('userID', $contactID);
  }

  public function cleanDB() {
    self::$populateOnce = NULL;
    $this->DBResetRequired = TRUE;

    $this->_dbconn = $this->getConnection();
    static::_populateDB();
    $this->tempDirs = array();
  }

  /**
   *  Common teardown functions for all unit tests
   */
  protected function tearDown() {
    error_reporting(E_ALL & ~E_NOTICE);
    $tablesToTruncate = array('civicrm_contact');
    $this->quickCleanup($tablesToTruncate);
    $this->cleanTempDirs();
    $this->unsetExtensionSystem();
  }

  /**
   *  FIXME: Maybe a better way to do it
   */
  function foreignKeyChecksOff() {
    global $civicrm_db_settings;

    self::$utils = new Utils($civicrm_db_settings);
    $dbName = self::getDBName();
    $query = "USE {$dbName};" . "SET foreign_key_checks = 1";
    if (self::$utils->do_query($query) === FALSE) {
      // fail happens
      echo 'Cannot set foreign_key_checks = 0';
      exit(1);
    }
    return TRUE;
  }

  function foreignKeyChecksOn() {
    // FIXME: might not be needed if previous fixme implemented
  }

  /**
   * Generic function to compare expected values after an api call to retrieved
   * DB values.
   *
   * @daoName  string   DAO Name of object we're evaluating.
   * @id       int      Id of object
   * @match    array    Associative array of field name => expected value. Empty if asserting
   *                      that a DELETE occurred
   * @delete   boolean  True if we're checking that a DELETE action occurred.
   */
  function assertDBState($daoName, $id, $match, $delete = FALSE) {
    if (empty($id)) {
      // adding this here since developers forget to check for an id
      // and hence we get the first value in the db
      $this->fail('ID not populated. Please fix your assertDBState usage!!!');
    }

    $object = new $daoName();
    $object->id = $id;
    $verifiedCount = 0;

    // If we're asserting successful record deletion, make sure object is NOT found.
    if ($delete) {
      if ($object->find(TRUE)) {
        $this->fail("Object not deleted by delete operation: $daoName, $id");
      }
      return;
    }

    // Otherwise check matches of DAO field values against expected values in $match.
    if ($object->find(TRUE)) {
      $fields = & $object->fields();
      foreach ($fields as $name => $value) {
        $dbName = $value['name'];
        if (isset($match[$name])) {
          $verifiedCount++;
          $this->assertEquals($object->$dbName, $match[$name]);
        }
        elseif (isset($match[$dbName])) {
          $verifiedCount++;
          $this->assertEquals($object->$dbName, $match[$dbName]);
        }
      }
    }
    else {
      $this->fail("Could not retrieve object: $daoName, $id");
    }
    $object->free();
    $matchSize = count($match);
    if ($verifiedCount != $matchSize) {
      $this->fail("Did not verify all fields in match array: $daoName, $id. Verified count = $verifiedCount. Match array size = $matchSize");
    }
  }

  // Request a record from the DB by seachColumn+searchValue. Success if a record is found.
  function assertDBNotNull($daoName, $searchValue, $returnColumn, $searchColumn, $message) {
    if (empty($searchValue)) {
      $this->fail("empty value passed to assertDBNotNull");
    }
    $value = CRM_Core_DAO::getFieldValue($daoName, $searchValue, $returnColumn, $searchColumn, TRUE);
    $this->assertNotNull($value, $message);

    return $value;
  }

  // Request a record from the DB by seachColumn+searchValue. Success if returnColumn value is NULL.
  function assertDBNull($daoName, $searchValue, $returnColumn, $searchColumn, $message) {
    $value = CRM_Core_DAO::getFieldValue($daoName, $searchValue, $returnColumn, $searchColumn, TRUE);
    $this->assertNull($value, $message);
  }

  // Request a record from the DB by id. Success if row not found.
  function assertDBRowNotExist($daoName, $id, $message = NULL) {
    $message = $message ? $message : "$daoName (#$id) should not exist";
    $value = CRM_Core_DAO::getFieldValue($daoName, $id, 'id', 'id', TRUE);
    $this->assertNull($value, $message);
  }

  // Request a record from the DB by id. Success if row not found.
  function assertDBRowExist($daoName, $id, $message = NULL) {
    $message = $message ? $message : "$daoName (#$id) should exist";
    $value = CRM_Core_DAO::getFieldValue($daoName, $id, 'id', 'id', TRUE);
    $this->assertEquals($id, $value, $message);
  }

  // Compare a single column value in a retrieved DB record to an expected value
  function assertDBCompareValue($daoName, $searchValue, $returnColumn, $searchColumn,
                                $expectedValue, $message
  ) {
    $value = CRM_Core_DAO::getFieldValue($daoName, $searchValue, $returnColumn, $searchColumn, TRUE);
    $this->assertEquals($value, $expectedValue, $message);
  }

  // Compare all values in a single retrieved DB record to an array of expected values
  function assertDBCompareValues($daoName, $searchParams, $expectedValues) {
    //get the values from db
    $dbValues = array();
    CRM_Core_DAO::commonRetrieve($daoName, $searchParams, $dbValues);

    // compare db values with expected values
    self::assertAttributesEquals($expectedValues, $dbValues);
  }

  /**
   * Assert that a SQL query returns a given value
   *
   * The first argument is an expected value. The remaining arguments are passed
   * to CRM_Core_DAO::singleValueQuery
   *
   * Example: $this->assertSql(2, 'select count(*) from foo where foo.bar like "%1"',
   * array(1 => array("Whiz", "String")));
   */
  function assertDBQuery($expected, $query, $params = array()) {
    $actual = CRM_Core_DAO::singleValueQuery($query, $params);
    $this->assertEquals($expected, $actual,
      sprintf('expected=[%s] actual=[%s] query=[%s]',
        $expected, $actual, CRM_Core_DAO::composeQuery($query, $params, FALSE)
      )
    );
  }

  /**
   * Assert that two array-trees are exactly equal, notwithstanding
   * the sorting of keys
   *
   * @param array $expected
   * @param array $actual
   */
  function assertTreeEquals($expected, $actual) {
    $e = array();
    $a = array();
    CRM_Utils_Array::flatten($expected, $e, '', ':::');
    CRM_Utils_Array::flatten($actual, $a, '', ':::');
    ksort($e);
    ksort($a);

    $this->assertEquals($e, $a);
  }

  /**
   * Assert that two numbers are approximately equal
   *
   * @param int|float $expected
   * @param int|float $actual
   * @param int|float $tolerance
   * @param string $message
   */
  function assertApproxEquals($expected, $actual, $tolerance, $message = NULL) {
    if ($message === NULL) {
      $message = sprintf("approx-equals: expected=[%.3f] actual=[%.3f] tolerance=[%.3f]", $expected, $actual, $tolerance);
    }
    $this->assertTrue(abs($actual - $expected) < $tolerance, $message);
  }

  function assertAttributesEquals($expectedValues, $actualValues, $message = NULL) {
    foreach ($expectedValues as $paramName => $paramValue) {
      if (isset($actualValues[$paramName])) {
        $this->assertEquals($paramValue, $actualValues[$paramName], "Value Mismatch On $paramName - value 1 is $paramValue  value 2 is {$actualValues[$paramName]}");
      }
      else {
        $this->fail("Attribute '$paramName' not present in actual array.");
      }
    }
  }

  function assertArrayKeyExists($key, &$list) {
    $result = isset($list[$key]) ? TRUE : FALSE;
    $this->assertTrue($result, ts("%1 element exists?",
      array(1 => $key)
    ));
  }

  function assertArrayValueNotNull($key, &$list) {
    $this->assertArrayKeyExists($key, $list);

    $value = isset($list[$key]) ? $list[$key] : NULL;
    $this->assertTrue($value,
      ts("%1 element not null?",
        array(1 => $key)
      )
    );
  }

  /**
   * check that api returned 'is_error' => 0
   * else provide full message
   * @param array $apiResult api result
   * @param string $prefix extra test to add to message
   */
  function assertAPISuccess($apiResult, $prefix = '') {
    if (!empty($prefix)) {
      $prefix .= ': ';
    }
    $errorMessage = empty($apiResult['error_message']) ? '' : " " . $apiResult['error_message'];

    if(!empty($apiResult['debug_information'])) {
      $errorMessage .= "\n " . print_r($apiResult['debug_information'], TRUE);
    }
    if(!empty($apiResult['trace'])){
      $errorMessage .= "\n" . print_r($apiResult['trace'], TRUE);
    }
    $this->assertEquals(0, $apiResult['is_error'], $prefix . $errorMessage);
  }

  /**
   * check that api returned 'is_error' => 1
   * else provide full message
   * @param array $apiResult api result
   * @param string $prefix extra test to add to message
   */
  function assertAPIFailure($apiResult, $prefix = '', $expectedError = NULL) {
    if (!empty($prefix)) {
      $prefix .= ': ';
    }
    if($expectedError && !empty($apiResult['is_error'])){
      $this->assertEquals($expectedError, $apiResult['error_message'], 'api error message not as expected' . $prefix );
    }
    $this->assertEquals(1, $apiResult['is_error'], "api call should have failed but it succeeded " . $prefix . (print_r($apiResult, TRUE)));
    $this->assertNotEmpty($apiResult['error_message']);
  }

  function assertType($expected, $actual, $message = '') {
    return $this->assertInternalType($expected, $actual, $message);
  }

  /**
   * check that a deleted item has been deleted
   */
  function assertAPIDeleted($entity, $id) {
    $this->callAPISuccess($entity, 'getcount', array('id' => $id), 0);
  }


  /**
   * check that api returned 'is_error' => 1
   * else provide full message
   * @param array $apiResult api result
   * @param string $prefix extra test to add to message
   */
  function assertAPIArrayComparison($result, $expected, $valuesToExclude = array(), $prefix = '') {
    $valuesToExclude = array_merge($valuesToExclude, array('debug', 'xdebug', 'sequential'));
    foreach ($valuesToExclude as $value) {
      if(isset($result[$value])) {
        unset($result[$value]);
      }
      if(isset($expected[$value])) {
        unset($expected[$value]);
      }
    }
    $this->assertEquals($result, $expected, "api result array comparison failed " . $prefix . print_r($result, TRUE) . ' was compared to ' . print_r($expected, TRUE));
  }

  /**
   * This function exists to wrap api functions
   * so we can ensure they succeed & throw exceptions without litterering the test with checks
   * @param string $entity
   * @param string $action
   * @param array $params
   * @param mixed $checkAgainst optional value to check result against, implemented for getvalue,
   *   getcount, getsingle. Note that for getvalue the type is checked rather than the value
   *   for getsingle the array is compared against an array passed in - the id is not compared (for
   *   better or worse )
   */
  function callAPISuccess($entity, $action, $params, $checkAgainst = NULL) {
    $params = array_merge(array(
        'version' => $this->_apiversion,
        'debug' => 1,
      ),
      $params
    );
    switch (strtolower($action)) {
      case 'getvalue' :
        return $this->callAPISuccessGetValue($entity, $params, $checkAgainst);
      case 'getsingle' :
        return $this->callAPISuccessGetSingle($entity, $params, $checkAgainst);
      case 'getcount' :
          return $this->callAPISuccessGetCount($entity, $params, $checkAgainst);
    }
    $result = civicrm_api($entity, $action, $params);
    $this->assertAPISuccess($result, "Failure in api call for $entity $action");
    return $result;
  }

  /**
   * This function exists to wrap api getValue function & check the result
   * so we can ensure they succeed & throw exceptions without litterering the test with checks
   * There is a type check in this
   * @param string $entity
   * @param array $params
   * @param string $type - per http://php.net/manual/en/function.gettype.php possible types
   * - boolean
   * - integer
   * - double
   * - string
   * - array
   * - object
   */
  function callAPISuccessGetValue($entity, $params, $type = NULL) {
    $params += array(
      'version' => $this->_apiversion,
      'debug' => 1,
    );
    $result = civicrm_api($entity, 'getvalue', $params);
    if($type){
      if($type == 'integer'){
        // api seems to return integers as strings
        $this->assertTrue(is_numeric($result), "expected a numeric value but got " . print_r($result, 1));
      }
      else{
        $this->assertType($type, $result, "returned result should have been of type $type but was " );
      }
    }
    return $result;
  }

  /**
   * This function exists to wrap api getsingle function & check the result
   * so we can ensure they succeed & throw exceptions without litterering the test with checks
   * @param string $entity
   * @param array $params
   * @param array $checkAgainst - array to compare result against
   * - boolean
   * - integer
   * - double
   * - string
   * - array
   * - object
   */
  function callAPISuccessGetSingle($entity, $params, $checkAgainst = NULL) {
    $params += array(
      'version' => $this->_apiversion,
      'debug' => 1,
    );
    $result = civicrm_api($entity, 'getsingle', $params);
    if(!is_array($result) || !empty($result['is_error']) || isset($result['values'])) {
      throw new Exception('Invalid getsingle result' . print_r($result, TRUE));
    }
    if($checkAgainst){
      // @todo - have gone with the fn that unsets id? should we check id?
      $this->checkArrayEquals($result, $checkAgainst);
    }
    return $result;
  }
  /**
   * This function exists to wrap api getValue function & check the result
   * so we can ensure they succeed & throw exceptions without litterering the test with checks
   * There is a type check in this
   * @param string $entity
   * @param array $params
   * @param string $type - per http://php.net/manual/en/function.gettype.php possible types
   * - boolean
   * - integer
   * - double
   * - string
   * - array
   * - object
   */
  function callAPISuccessGetCount($entity, $params, $count = NULL) {
    $params += array(
      'version' => $this->_apiversion,
      'debug' => 1,
    );
    $result = civicrm_api($entity, 'getcount', $params);
    if(!is_integer($result) || !empty($result['is_error']) || isset($result['values'])) {
      throw new Exception('Invalid getcount result : ' . print_r($result, TRUE) . " type :" . gettype($result));
    }
    if(is_int($count)){
      $this->assertEquals($count, $result, "incorect count returned from $entity getcount");
    }
    return $result;
  }
  /**
   * This function exists to wrap api functions
   * so we can ensure they succeed, generate and example & throw exceptions without litterering the test with checks
   *
   * @param string $entity
   * @param string $action
   * @param array $params
   * @param string $function - pass this in to create a generated example
   * @param string $file - pass this in to create a generated example
   */
  function callAPIAndDocument($entity, $action, $params, $function, $file, $description = "", $subfile = NULL, $actionName = NULL){
    $params['version'] = $this->_apiversion;
    $result = $this->callAPISuccess($entity, $action, $params);
    $this->documentMe($params, $result, $function, $file, $description, $subfile, $actionName);
    return $result;
  }

  /**
   * This function exists to wrap api functions
   * so we can ensure they fail where expected & throw exceptions without litterering the test with checks
   * @param string $entity
   * @param string $action
   * @param array $params
   * @param string $expectedErrorMessage error
   */
  function callAPIFailure($entity, $action, $params, $expectedErrorMessage = NULL, $extraOutput = NULL) {
    if (is_array($params)) {
      $params += array(
        'version' => $this->_apiversion,
      );
    }
    $result = civicrm_api($entity, $action, $params);
    $this->assertAPIFailure($result, "We expected a failure for $entity $action but got a success");
    return $result;
  }

  /**
   * Create required data based on $this->entity & $this->params
   * This is just a way to set up the test data for delete & get functions
   * so the distinction between set
   * up & tested functions is clearer
   *
   *  @return array api Result
   */
  public function createTestEntity(){
    return $entity = $this->callAPISuccess($this->entity, 'create', $this->params);
  }

  /**
   * Generic function to create Organisation, to be used in test cases
   *
   * @param array   parameters for civicrm_contact_add api function call
   *
   * @return int    id of Organisation created
   */
  function organizationCreate($params = array()) {
    if (!$params) {
      $params = array();
    }
    $orgParams = array(
      'organization_name' => 'Unit Test Organization',
      'contact_type' => 'Organization',
    );
    return $this->_contactCreate(array_merge($orgParams, $params));
  }

  /**
   * Generic function to create Individual, to be used in test cases
   *
   * @param array   parameters for civicrm_contact_add api function call
   *
   * @return int    id of Individual created
   */
  function individualCreate($params = array()) {
    $params = array_merge(array(
        'first_name' => 'Anthony',
        'middle_name' => 'J.',
        'last_name' => 'Anderson',
        'prefix_id' => 3,
        'suffix_id' => 3,
        'email' => 'anthony_anderson@civicrm.org',
        'contact_type' => 'Individual',
      ), $params);

    return $this->_contactCreate($params);
  }

  /**
   * Generic function to create Household, to be used in test cases
   *
   * @param array   parameters for civicrm_contact_add api function call
   *
   * @return int    id of Household created
   */
  function householdCreate($params =  array()) {
    $params = array_merge(array(
        'household_name' => 'Unit Test household',
        'contact_type' => 'Household',
      ), $params);
    return $this->_contactCreate($params);
  }

  /**
   * Private helper function for calling civicrm_contact_add
   *
   * @param $params
   *
   * @throws Exception
   * @internal param \parameters $array for civicrm_contact_add api function call
   *
   * @return int    id of Household created
   */
  private function _contactCreate($params) {
    $result = $this->callAPISuccess('contact', 'create', $params);
    if (CRM_Utils_Array::value('is_error', $result) ||
      !CRM_Utils_Array::value('id', $result)
    ) {
      throw new Exception('Could not create test contact, with message: ' . CRM_Utils_Array::value('error_message', $result) . "\nBacktrace:" . CRM_Utils_Array::value('trace', $result));
    }
    return $result['id'];
  }

  function contactDelete($contactID) {
    $params = array(
      'id' => $contactID,
      'skip_undelete' => 1,
      'debug' => 1,
    );
    $domain = new CRM_Core_BAO_Domain;
    $domain->contact_id = $contactID;
    if ($domain->find(TRUE)) {
      // we are finding tests trying to delete the domain contact in cleanup
      //since this is mainly for cleanup lets put a safeguard here
      return;
    }
    $result = $this->callAPISuccess('contact', 'delete', $params);
    return  $result;
  }

  function contactTypeDelete($contactTypeId) {
    require_once 'CRM/Contact/BAO/ContactType.php';
    $result = CRM_Contact_BAO_ContactType::del($contactTypeId);
    if (!$result) {
      throw new Exception('Could not delete contact type');
    }
  }

  function membershipTypeCreate($params = array()) {
    CRM_Member_PseudoConstant::flush('membershipType');
    CRM_Core_Config::clearDBCache();
    $params = array_merge(array(
      'name' => 'General',
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'member_of_contact_id' => 1,
      'domain_id' => 1,
      'financial_type_id' => 1,
      'is_active' => 1,
      'sequential' => 1,
      'visibility' => 1,
    ), $params);

    $result = $this->callAPISuccess('MembershipType', 'Create', $params);

    CRM_Member_PseudoConstant::flush('membershipType');
    CRM_Utils_Cache::singleton()->flush();

    return $result['id'];
  }

  function contactMembershipCreate($params) {
    $pre = array(
      'join_date' => '2007-01-21',
      'start_date' => '2007-01-21',
      'end_date' => '2007-12-21',
      'source' => 'Payment',
    );

    foreach ($pre as $key => $val) {
      if (!isset($params[$key])) {
        $params[$key] = $val;
      }
    }

    $result = $this->callAPISuccess('Membership', 'create', $params);
    return $result['id'];
  }

  /**
   * Function to delete Membership Type
   *
   * @param int $membershipTypeID
   */
  function membershipTypeDelete($params) {
    $result = $this->callAPISuccess('MembershipType', 'Delete', $params);
    return;
  }

  function membershipDelete($membershipID) {
    $deleteParams = array('id' => $membershipID);
    $result = $this->callAPISuccess('Membership', 'Delete', $deleteParams);
    return;
  }

  function membershipStatusCreate($name = 'test member status') {
    $params['name'] = $name;
    $params['start_event'] = 'start_date';
    $params['end_event'] = 'end_date';
    $params['is_current_member'] = 1;
    $params['is_active'] = 1;

    $result = $this->callAPISuccess('MembershipStatus', 'Create', $params);
    CRM_Member_PseudoConstant::flush('membershipStatus');
    return $result['id'];
  }

  function membershipStatusDelete($membershipStatusID) {
    if (!$membershipStatusID) {
      return;
    }
    $result = $this->callAPISuccess('MembershipStatus', 'Delete', array('id' => $membershipStatusID));
    return;
  }

  function relationshipTypeCreate($params = array()) {
    $params = array_merge(array(
        'name_a_b' => 'Relation 1 for relationship type create',
        'name_b_a' => 'Relation 2 for relationship type create',
        'contact_type_a' => 'Individual',
        'contact_type_b' => 'Organization',
        'is_reserved' => 1,
        'is_active' => 1,
      ),
       $params
    );

    $result = $this->callAPISuccess('relationship_type', 'create', $params);
    CRM_Core_PseudoConstant::flush('relationshipType');

    return $result['id'];
  }

  /**
   * Function to delete Relatinship Type
   *
   * @param int $relationshipTypeID
   */
  function relationshipTypeDelete($relationshipTypeID) {
    $params['id'] = $relationshipTypeID;
    $this->callAPISuccess('relationship_type', 'delete', $params);
  }

  function paymentProcessorTypeCreate($params = NULL) {
    if (is_null($params)) {
      $params = array(
        'name' => 'API_Test_PP',
        'title' => 'API Test Payment Processor',
        'class_name' => 'CRM_Core_Payment_APITest',
        'billing_mode' => 'form',
        'is_recur' => 0,
        'is_reserved' => 1,
        'is_active' => 1,
      );
    }
    $result = $this->callAPISuccess('payment_processor_type', 'create', $params);

    CRM_Core_PseudoConstant::flush('paymentProcessorType');

    return $result['id'];
  }

  /**
   * Function to create Participant
   *
   * @param array $params  array of contact id and event id values
   *
   * @return int $id of participant created
   */
  function participantCreate($params) {
    if(empty($params['contact_id'])){
      $params['contact_id'] = $this->individualCreate();
    }
    if(empty($params['event_id'])){
      $event = $this->eventCreate();
      $params['event_id'] = $event['id'];
    }
    $defaults = array(
      'status_id' => 2,
      'role_id' => 1,
      'register_date' => 20070219,
      'source' => 'Wimbeldon',
      'event_level' => 'Payment',
      'debug' => 1,
    );

    $params = array_merge($defaults, $params);
    $result = $this->callAPISuccess('Participant', 'create', $params);
    return $result['id'];
  }

  /**
   * Function to create Payment Processor
   *
   * @return object of Payment Processsor
   */
  function processorCreate() {
    $processorParams = array(
      'domain_id' => 1,
      'name' => 'Dummy',
      'payment_processor_type_id' => 10,
      'financial_account_id' => 12,
      'is_active' => 1,
      'user_name' => '',
      'url_site' => 'http://dummy.com',
      'url_recur' => 'http://dummy.com',
      'billing_mode' => 1,
    );
    $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::create($processorParams);
    return $paymentProcessor;
  }

  /**
   * Function to create contribution page
   *
   * @return object of contribution page
   */
  function contributionPageCreate($params) {
    $this->_pageParams = array(
      'title' => 'Test Contribution Page',
      'financial_type_id' => 1,
      'currency' => 'USD',
      'financial_account_id' => 1,
      'payment_processor' => $params['processor_id'],
      'is_active' => 1,
      'is_allow_other_amount' => 1,
      'min_amount' => 10,
      'max_amount' => 1000,
    );
    $contributionPage = $this->callAPISuccess('contribution_page', 'create', $this->_pageParams);
    return $contributionPage;
  }

  /**
   * Function to create Tag
   *
   * @return array result of created tag
   */
  function tagCreate($params = array()) {
    $defaults = array(
      'name' => 'New Tag3',
      'description' => 'This is description for Our New Tag ',
      'domain_id' => '1',
    );
    $params = array_merge($defaults, $params);
    $result =  $this->callAPISuccess('Tag', 'create', $params);
    return $result['values'][$result['id']];
  }

  /**
   * Function to delete Tag
   *
   * @param  int $tagId   id of the tag to be deleted
   */
  function tagDelete($tagId) {
    require_once 'api/api.php';
    $params = array(
      'tag_id' => $tagId,
    );
    $result = $this->callAPISuccess('Tag', 'delete', $params);
    return $result['id'];
  }

  /**
   * Add entity(s) to the tag
   *
   * @param  array  $params
   */
  function entityTagAdd($params) {
    $result = $this->callAPISuccess('entity_tag', 'create', $params);
    return TRUE;
  }

  /**
   * Function to create contribution
   *
   * @param int $cID      contact_id
   * @param int $cTypeID  id of financial type
   *
   * @return int id of created contribution
   */
  function pledgeCreate($cID) {
    $params = array(
      'contact_id' => $cID,
      'pledge_create_date' => date('Ymd'),
      'start_date' => date('Ymd'),
      'scheduled_date' => date('Ymd'),
      'amount' => 100.00,
      'pledge_status_id' => '2',
      'financial_type_id' => '1',
      'pledge_original_installment_amount' => 20,
      'frequency_interval' => 5,
      'frequency_unit' => 'year',
      'frequency_day' => 15,
      'installments' => 5,
    );

    $result = $this->callAPISuccess('Pledge', 'create', $params);
    return $result['id'];
  }

  /**
   * Function to delete contribution
   *
   * @param int $contributionId
   */
  function pledgeDelete($pledgeId) {
    $params = array(
      'pledge_id' => $pledgeId,
    );
    $this->callAPISuccess('Pledge', 'delete', $params);
  }

  /**
   * Function to create contribution
   *
   * @param int $cID      contact_id
   * @param int $cTypeID  id of financial type
   *
   * @return int id of created contribution
   */
  function contributionCreate($cID, $cTypeID = 1, $invoiceID = 67890, $trxnID = 12345, $paymentInstrumentID = 1, $isFee = TRUE) {
    $params = array(
      'domain_id' => 1,
      'contact_id' => $cID,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => empty($cTypeID) ? 1 : $cTypeID,
      'payment_instrument_id' => empty($paymentInstrumentID) ? 1 : $paymentInstrumentID,
      'non_deductible_amount' => 10.00,
      'trxn_id' => $trxnID,
      'invoice_id' => $invoiceID,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      // 'note'                   => 'Donating for Nobel Cause', *Fixme
    );

    if ($isFee) {
      $params['fee_amount'] = 5.00;
      $params['net_amount'] = 95.00;
    }

    $result = $this->callAPISuccess('contribution', 'create', $params);
    return $result['id'];
  }

  /**
   * Function to create online contribution
   *
   * @param int $financialType  id of financial type
   *
   * @return int id of created contribution
   */
  function onlineContributionCreate($params, $financialType, $invoiceID = 67890, $trxnID = 12345) {
    $contribParams = array(
      'contact_id' => $params['contact_id'],
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => $financialType,
      'contribution_page_id' => $params['contribution_page_id'],
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
    );
    $contribParams = array_merge($contribParams, $params);
    $result = $this->callAPISuccess('contribution', 'create', $contribParams);

    return $result['id'];
  }

  /**
   * Function to delete contribution
   *
   * @param int $contributionId
   */
  function contributionDelete($contributionId) {
    $params = array(
      'contribution_id' => $contributionId,
    );
    $result = $this->callAPISuccess('contribution', 'delete', $params);
    return $result;
  }

  /**
   * Function to create an Event
   *
   * @param array $params  name-value pair for an event
   *
   * @return array $event
   */
  function eventCreate($params = array()) {
    // if no contact was passed, make up a dummy event creator
    if (!isset($params['contact_id'])) {
      $params['contact_id'] = $this->_contactCreate(array(
        'contact_type' => 'Individual',
        'first_name' => 'Event',
        'last_name' => 'Creator',
      ));
    }

    // set defaults for missing params
    $params = array_merge(array(
      'title' => 'Annual CiviCRM meet',
      'summary' => 'If you have any CiviCRM related issues or want to track where CiviCRM is heading, Sign up now',
      'description' => 'This event is intended to give brief idea about progess of CiviCRM and giving solutions to common user issues',
      'event_type_id' => 1,
      'is_public' => 1,
      'start_date' => 20081021,
      'end_date' => 20081023,
      'is_online_registration' => 1,
      'registration_start_date' => 20080601,
      'registration_end_date' => 20081015,
      'max_participants' => 100,
      'event_full_text' => 'Sorry! We are already full',
      'is_monetory' => 0,
      'is_active' => 1,
      'is_show_location' => 0,
    ), $params);

    return $this->callAPISuccess('Event', 'create', $params);
  }

  /**
   * Function to delete event
   *
   * @param int $id  ID of the event
   */
  function eventDelete($id) {
    $params = array(
      'event_id' => $id,
    );
    return $this->callAPISuccess('event', 'delete', $params);
  }

  /**
   * Function to delete participant
   *
   * @param int $participantID
   */
  function participantDelete($participantID) {
    $params = array(
      'id' => $participantID,
    );
    return $this->callAPISuccess('Participant', 'delete', $params);
  }

  /**
   * Function to create participant payment
   *
   * @return int $id of created payment
   */
  function participantPaymentCreate($participantID, $contributionID = NULL) {
    //Create Participant Payment record With Values
    $params = array(
      'participant_id' => $participantID,
      'contribution_id' => $contributionID,
    );

    $result = $this->callAPISuccess('participant_payment', 'create', $params);
    return $result['id'];
  }

  /**
   * Function to delete participant payment
   *
   * @param int $paymentID
   */
  function participantPaymentDelete($paymentID) {
    $params = array(
      'id' => $paymentID,
    );
    $result = $this->callAPISuccess('participant_payment', 'delete', $params);
  }

  /**
   * Function to add a Location
   *
   * @return int location id of created location
   */
  function locationAdd($contactID) {
    $address = array(
      1 => array(
        'location_type' => 'New Location Type',
        'is_primary' => 1,
        'name' => 'Saint Helier St',
        'county' => 'Marin',
        'country' => 'United States',
        'state_province' => 'Michigan',
        'supplemental_address_1' => 'Hallmark Ct',
        'supplemental_address_2' => 'Jersey Village',
      )
    );

    $params = array(
      'contact_id' => $contactID,
      'address' => $address,
      'location_format' => '2.0',
      'location_type' => 'New Location Type',
    );

    $result = $this->callAPISuccess('Location', 'create', $params);
    return $result;
  }

  /**
   * Function to delete Locations of contact
   *
   * @params array $pamars parameters
   */
  function locationDelete($params) {
    $result = $this->callAPISuccess('Location', 'delete', $params);
  }

  /**
   * Function to add a Location Type
   *
   * @return int location id of created location
   */
  function locationTypeCreate($params = NULL) {
    if ($params === NULL) {
      $params = array(
        'name' => 'New Location Type',
        'vcard_name' => 'New Location Type',
        'description' => 'Location Type for Delete',
        'is_active' => 1,
      );
    }

    $locationType = new CRM_Core_DAO_LocationType();
    $locationType->copyValues($params);
    $locationType->save();
    // clear getfields cache
    CRM_Core_PseudoConstant::flush();
    $this->callAPISuccess('phone', 'getfields', array('version' => 3, 'cache_clear' => 1));
    return $locationType;
  }

  /**
   * Function to delete a Location Type
   *
   * @param int location type id
   */
  function locationTypeDelete($locationTypeId) {
    $locationType = new CRM_Core_DAO_LocationType();
    $locationType->id = $locationTypeId;
    $locationType->delete();
  }

  /**
   * Function to add a Group
   *
   * @params array to add group
   *
   * @return int groupId of created group
   *
   */
  function groupCreate($params = NULL) {
    if ($params === NULL) {
      $params = array(
        'name' => 'Test Group 1',
        'domain_id' => 1,
        'title' => 'New Test Group Created',
        'description' => 'New Test Group Created',
        'is_active' => 1,
        'visibility' => 'Public Pages',
        'group_type' => array(
          '1' => 1,
          '2' => 1,
        ),
      );
    }

    $result = $this->callAPISuccess('Group', 'create', $params);
    return $result['id'];
  }

  /**
   * Function to delete a Group
   *
   * @param int $id
   */
  function groupDelete($gid) {

    $params = array(
      'id' => $gid,
    );

    $this->callAPISuccess('Group', 'delete', $params);
  }

  /**
   * Create a UFField
   * @param array $params
   */
  function uFFieldCreate($params = array()) {
    $params = array_merge(array(
      'uf_group_id' => 1,
      'field_name' => 'first_name',
      'is_active' => 1,
      'is_required' => 1,
      'visibility' => 'Public Pages and Listings',
      'is_searchable' => '1',
      'label' => 'first_name',
      'field_type' => 'Individual',
      'weight' => 1,
    ), $params);
    $this->callAPISuccess('uf_field', 'create', $params);
  }
  /**
   * Function to add a UF Join Entry
   *
   * @return int $id of created UF Join
   */
  function ufjoinCreate($params = NULL) {
    if ($params === NULL) {
      $params = array(
        'is_active' => 1,
        'module' => 'CiviEvent',
        'entity_table' => 'civicrm_event',
        'entity_id' => 3,
        'weight' => 1,
        'uf_group_id' => 1,
      );
    }
    $result = $this->callAPISuccess('uf_join', 'create', $params);
    return $result;
  }

  /**
   * Function to delete a UF Join Entry
   *
   * @param array with missing uf_group_id
   */
  function ufjoinDelete($params = NULL) {
    if ($params === NULL) {
      $params = array(
        'is_active' => 1,
        'module' => 'CiviEvent',
        'entity_table' => 'civicrm_event',
        'entity_id' => 3,
        'weight' => 1,
        'uf_group_id' => '',
      );
    }

    crm_add_uf_join($params);
  }

  /**
   * Function to create Group for a contact
   *
   * @param int $contactId
   */
  function contactGroupCreate($contactId) {
    $params = array(
      'contact_id.1' => $contactId,
      'group_id' => 1,
    );

    $this->callAPISuccess('GroupContact', 'Create', $params);
  }

  /**
   * Function to delete Group for a contact
   *
   * @param array $params
   */
  function contactGroupDelete($contactId) {
    $params = array(
      'contact_id.1' => $contactId,
      'group_id' => 1,
    );
    civicrm_api('GroupContact', 'Delete', $params);
  }

  /**
   * Function to create Activity
   *
   * @param int $contactId
   */
  function activityCreate($params = NULL) {

    if ($params === NULL) {
      $individualSourceID = $this->individualCreate();

      $contactParams = array(
        'first_name' => 'Julia',
        'Last_name' => 'Anderson',
        'prefix' => 'Ms.',
        'email' => 'julia_anderson@civicrm.org',
        'contact_type' => 'Individual',
      );

      $individualTargetID = $this->individualCreate($contactParams);

      $params = array(
        'source_contact_id' => $individualSourceID,
        'target_contact_id' => array($individualTargetID),
        'assignee_contact_id' => array($individualTargetID),
        'subject' => 'Discussion on warm beer',
        'activity_date_time' => date('Ymd'),
        'duration_hours' => 30,
        'duration_minutes' => 20,
        'location' => 'Baker Street',
        'details' => 'Lets schedule a meeting',
        'status_id' => 1,
        'activity_name' => 'Meeting',
      );
    }

    $result = $this->callAPISuccess('Activity', 'create', $params);

    $result['target_contact_id'] = $individualTargetID;
    $result['assignee_contact_id'] = $individualTargetID;
    return $result;
  }

  /**
   * Function to create an activity type
   *
   * @params array $params parameters
   */
  function activityTypeCreate($params) {
    $result = $this->callAPISuccess('ActivityType', 'create', $params);
    return $result;
  }

  /**
   * Function to delete activity type
   *
   * @params Integer $activityTypeId id of the activity type
   */
  function activityTypeDelete($activityTypeId) {
    $params['activity_type_id'] = $activityTypeId;
    $result = $this->callAPISuccess('ActivityType', 'delete', $params);
    return $result;
  }

  /**
   * Function to create custom group
   *
   * @param string $className
   * @param string $title  name of custom group
   */
  function customGroupCreate($params = array()) {
    $defaults = array(
      'title' => 'new custom group',
      'extends' => 'Contact',
      'domain_id' => 1,
      'style' => 'Inline',
      'is_active' => 1,
    );

    $params = array_merge($defaults, $params);

    if (strlen($params['title']) > 13) {
      $params['title'] =  substr($params['title'], 0, 13);
    }

    //have a crack @ deleting it first in the hope this will prevent derailing our tests
    $check = $this->callAPISuccess('custom_group', 'get', array('title' => $params['title'], array('api.custom_group.delete' => 1)));

    return $this->callAPISuccess('custom_group', 'create', $params);
  }

  /**
   * existing function doesn't allow params to be over-ridden so need a new one
   * this one allows you to only pass in the params you want to change
   */
  function CustomGroupCreateByParams($params = array()) {
    $defaults = array(
      'title' => "API Custom Group",
      'extends' => 'Contact',
      'domain_id' => 1,
      'style' => 'Inline',
      'is_active' => 1,
    );
    $params = array_merge($defaults, $params);
    return $this->callAPISuccess('custom_group', 'create', $params);
  }

  /**
   * Create custom group with multi fields
   */
  function CustomGroupMultipleCreateByParams($params = array()) {
    $defaults = array(
      'style' => 'Tab',
      'is_multiple' => 1,
    );
    $params = array_merge($defaults, $params);
    return $this->CustomGroupCreateByParams($params);
  }

  /**
   * Create custom group with multi fields
   */
  function CustomGroupMultipleCreateWithFields($params = array()) {
    // also need to pass on $params['custom_field'] if not set but not in place yet
    $ids = array();
    $customGroup = $this->CustomGroupMultipleCreateByParams($params);
    $ids['custom_group_id'] = $customGroup['id'];

    $customField = $this->customFieldCreate(array('custom_group_id' => $ids['custom_group_id'], 'label' => 'field_1' . $ids['custom_group_id']));

    $ids['custom_field_id'][] = $customField['id'];

    $customField = $this->customFieldCreate(array('custom_group_id' => $ids['custom_group_id'], 'default_value' => '', 'label' => 'field_2' . $ids['custom_group_id']));
    $ids['custom_field_id'][] = $customField['id'];

    $customField = $this->customFieldCreate(array('custom_group_id' => $ids['custom_group_id'], 'default_value' => '', 'label' => 'field_3' . $ids['custom_group_id']));
    $ids['custom_field_id'][] = $customField['id'];

    return $ids;
  }

  /**
   * Create a custom group with a single text custom field.  See
   * participant:testCreateWithCustom for how to use this
   *
   * @param string $function __FUNCTION__
   * @param string $file __FILE__
   *
   * @return array $ids ids of created objects
   *
   */
  function entityCustomGroupWithSingleFieldCreate($function, $filename) {
    $params = array('title' => $function);
    $entity = substr(basename($filename), 0, strlen(basename($filename)) - 8);
    $params['extends'] =  $entity ? $entity : 'Contact';
    $customGroup = $this->CustomGroupCreate($params);
    $customField = $this->customFieldCreate(array('custom_group_id' => $customGroup['id'], 'label' => $function));
    CRM_Core_PseudoConstant::flush();

    return array('custom_group_id' => $customGroup['id'], 'custom_field_id' => $customField['id']);
  }

  /**
   * Function to delete custom group
   *
   * @param int    $customGroupID
   */
  function customGroupDelete($customGroupID) {
    $params['id'] = $customGroupID;
    return $this->callAPISuccess('custom_group', 'delete', $params);
  }

  /**
   * Function to create custom field
   *
   * @param array $params (custom_group_id) is required
   * @param string $name  name of custom field
   * @param int $apiversion API  version to use
   */
  function customFieldCreate($params) {
    $params = array_merge(array(
      'label' => 'Custom Field',
      'data_type' => 'String',
      'html_type' => 'Text',
      'is_searchable' => 1,
      'is_active' => 1,
      'default_value' => 'defaultValue',
    ), $params);

    $result = $this->callAPISuccess('custom_field', 'create', $params);

    if ($result['is_error'] == 0 && isset($result['id'])) {
      CRM_Core_BAO_CustomField::getTableColumnGroup($result['id'], 1);
      // force reset of enabled components to help grab custom fields
      CRM_Core_Component::getEnabledComponents(1);
      return $result;
    }
  }

  /**
   * Function to delete custom field
   *
   * @param int $customFieldID
   */
  function customFieldDelete($customFieldID) {

    $params['id'] = $customFieldID;
    return $this->callAPISuccess('custom_field', 'delete', $params);
  }

  /**
   * Function to create note
   *
   * @params array $params  name-value pair for an event
   *
   * @return array $note
   */
  function noteCreate($cId) {
    $params = array(
      'entity_table' => 'civicrm_contact',
      'entity_id' => $cId,
      'note' => 'hello I am testing Note',
      'contact_id' => $cId,
      'modified_date' => date('Ymd'),
      'subject' => 'Test Note',
    );

    return $this->callAPISuccess('Note', 'create', $params);
  }

  /**
   * Create test generated example in api/v3/examples.
   * To turn this off (e.g. on the server) set
   * define(DONT_DOCUMENT_TEST_CONFIG ,1);
   * in your settings file
   * @param array $params array as passed to civicrm_api function
   * @param array $result array as received from the civicrm_api function
   * @param string $function calling function - generally __FUNCTION__
   * @param string $filename called from file - generally __FILE__
   * @param string $description descriptive text for the example file
   * @param string $subfile name for subfile - if this is completed the example will be put in a subfolder (named by the entity)
   * @param string $action - optional action - otherwise taken from function name
   */
  function documentMe($params, $result, $function, $filename, $description = "", $subfile = NULL, $action = NULL) {
    if (defined('DONT_DOCUMENT_TEST_CONFIG')) {
      return;
    }
    $entity = substr(basename($filename), 0, strlen(basename($filename)) - 8);
    //todo - this is a bit cludgey
    if (empty($action)) {
      if (strstr($function, 'Create')) {
        $action = empty($action) ? 'create' : $action;
        $entityAction = 'Create';
      }
      elseif (strstr($function, 'GetSingle')) {
        $action = empty($action) ? 'getsingle' : $action;
        $entityAction = 'GetSingle';
      }
      elseif (strstr($function, 'GetValue')) {
        $action = empty($action) ? 'getvalue' : $action;
        $entityAction = 'GetValue';
      }
      elseif (strstr($function, 'GetCount')) {
        $action = empty($action) ? 'getcount' : $action;
        $entityAction = 'GetCount';
      }
      elseif (strstr($function, 'GetFields')) {
        $action = empty($action) ? 'getfields' : $action;
        $entityAction = 'GetFields';
      }
      elseif (strstr($function, 'Get')) {
        $action = empty($action) ? 'get' : $action;
        $entityAction = 'Get';
      }
      elseif (strstr($function, 'Delete')) {
        $action = empty($action) ? 'delete' : $action;
        $entityAction = 'Delete';
      }
      elseif (strstr($function, 'Update')) {
        $action = empty($action) ? 'update' : $action;
        $entityAction = 'Update';
      }
      elseif (strstr($function, 'Subscribe')) {
        $action = empty($action) ? 'subscribe' : $action;
        $entityAction = 'Subscribe';
      }
      elseif (strstr($function, 'Submit')) {
        $action = empty($action) ? 'submit' : $action;
        $entityAction = 'Submit';
      }
      elseif (strstr($function, 'Apply')) {
        $action = empty($action) ? 'apply' : $action;
        $entityAction = 'Apply';
      }
      elseif (strstr($function, 'Replace')) {
        $action = empty($action) ? 'replace' : $action;
        $entityAction = 'Replace';
      }
    }
    else {
      $entityAction = ucwords($action);
    }

    $this->tidyExampleResult($result);
    if(isset($params['version'])) {
      unset($params['version']);
    }
    // a cleverer person than me would do it in a single regex
    if (strstr($entity, 'UF')) {
      $fnPrefix = strtolower(preg_replace('/(?<! )(?<!^)(?<=UF)[A-Z]/', '_$0', $entity));
    }
    else {
      $fnPrefix = strtolower(preg_replace('/(?<! )(?<!^)[A-Z]/', '_$0', $entity));
    }
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('testfunction', $function);
    $function = $fnPrefix . "_" . strtolower($action);
    $smarty->assign('function', $function);
    $smarty->assign('fnPrefix', $fnPrefix);
    $smarty->assign('params', $params);
    $smarty->assign('entity', $entity);
    $smarty->assign('filename', basename($filename));
    $smarty->assign('description', $description);
    $smarty->assign('result', $result);

    $smarty->assign('action', $action);
    if (empty($subfile)) {
      if (file_exists('../tests/templates/documentFunction.tpl')) {
        $f = fopen("../api/v3/examples/$entity$entityAction.php", "w");
        fwrite($f, $smarty->fetch('../tests/templates/documentFunction.tpl'));
        fclose($f);
      }
    }
    else {
      if (file_exists('../tests/templates/documentFunction.tpl')) {
        if (!is_dir("../api/v3/examples/$entity")) {
          mkdir("../api/v3/examples/$entity");
        }
        $f = fopen("../api/v3/examples/$entity/$subfile.php", "w+b");
        fwrite($f, $smarty->fetch('../tests/templates/documentFunction.tpl'));
        fclose($f);
      }
    }
  }

  /**
   * Tidy up examples array so that fields that change often ..don't
   * and debug related fields are unset
   * @param array $params
   */
  function tidyExampleResult(&$result){
    if(!is_array($result)) {
      return;
    }
    $fieldsToChange = array(
      'hash' => '67eac7789eaee00',
      'modified_date' => '2012-11-14 16:02:35',
      'created_date' => '2013-07-28 08:49:19',
      'create_date' => '20120130621222105',
      'application_received_date' => '20130728084957',
      'in_date' => '2013-07-28 08:50:19',
      'scheduled_date' => '20130728085413',
      'approval_date' => '20130728085413',
      'pledge_start_date_high' => '20130726090416',
      'start_date' => '2013-07-29 00:00:00',
      'event_start_date' => '2013-07-29 00:00:00',
      'end_date' => '2013-08-04 00:00:00',
      'event_end_date' => '2013-08-04 00:00:00',
      'decision_date' => '20130805000000',
    );

    $keysToUnset = array('xdebug', 'undefined_fields',);
    foreach ($keysToUnset as $unwantedKey) {
      if(isset($result[$unwantedKey])) {
        unset($result[$unwantedKey]);
      }
    }
    if (isset($result['values'])) {
      if(!is_array($result['values'])) {
        return;
      }
      $resultArray = &$result['values'];
    }
    elseif(is_array($result)) {
      $resultArray = &$result;
    }
    else {
      return;
    }

    foreach ($resultArray as $index => &$values) {
        if(!is_array($values)) {
          continue;
        }
        foreach($values as $key => &$value) {
          if(substr($key, 0, 3)  == 'api' && is_array($value)) {
            if(isset($value['is_error'])) {
              // we have a std nested result format
              $this->tidyExampleResult($value);
            }
            else{
              foreach ($value as &$nestedResult) {
                // this is an alternative syntax for nested results a keyed array of results
                $this->tidyExampleResult($nestedResult);
              }
            }
          }
          if(in_array($key, $keysToUnset)) {
            unset($values[$key]);
          }
          if(array_key_exists($key, $fieldsToChange) && !empty($value)) {
            $value = $fieldsToChange[$key];
          }
        }
    }
  }

  /**
   * Function to delete note
   *
   * @params int $noteID
   *
   */
  function noteDelete($params) {
    return $this->callAPISuccess('Note', 'delete', $params);
  }

  /**
   * Function to create custom field with Option Values
   *
   * @param array    $customGroup
   * @param string $name  name of custom field
   */
  function customFieldOptionValueCreate($customGroup, $name) {
    $fieldParams = array(
      'custom_group_id' => $customGroup['id'],
      'name' => 'test_custom_group',
      'label' => 'Country',
      'html_type' => 'Select',
      'data_type' => 'String',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );

    $optionGroup = array(
      'domain_id' => 1,
      'name' => 'option_group1',
      'label' => 'option_group_label1',
    );

    $optionValue = array(
      'option_label' => array('Label1', 'Label2'),
      'option_value' => array('value1', 'value2'),
      'option_name' => array($name . '_1', $name . '_2'),
      'option_weight' => array(1, 2),
      'option_status' => 1,
    );

    $params = array_merge($fieldParams, $optionGroup, $optionValue);

    return $this->callAPISuccess('custom_field', 'create', $params);
  }

  function confirmEntitiesDeleted($entities) {
    foreach ($entities as $entity) {

      $result = $this->callAPISuccess($entity, 'Get', array());
      if ($result['error'] == 1 || $result['count'] > 0) {
        // > than $entity[0] to allow a value to be passed in? e.g. domain?
        return TRUE;
      }
    }
  }

  function quickCleanup($tablesToTruncate, $dropCustomValueTables = FALSE) {
    if ($dropCustomValueTables) {
      $tablesToTruncate[] = 'civicrm_custom_group';
      $tablesToTruncate[] = 'civicrm_custom_field';
    }

    CRM_Core_DAO::executeQuery("SET FOREIGN_KEY_CHECKS = 0;");
    foreach ($tablesToTruncate as $table) {
      $sql = "TRUNCATE TABLE $table";
      CRM_Core_DAO::executeQuery($sql);
    }
    CRM_Core_DAO::executeQuery("SET FOREIGN_KEY_CHECKS = 1;");

    if ($dropCustomValueTables) {
      $dbName = self::getDBName();
      $query = "
SELECT TABLE_NAME as tableName
FROM   INFORMATION_SCHEMA.TABLES
WHERE  TABLE_SCHEMA = '{$dbName}'
AND    ( TABLE_NAME LIKE 'civicrm_value_%' )
";

      $tableDAO = CRM_Core_DAO::executeQuery($query);
      while ($tableDAO->fetch()) {
        $sql = "DROP TABLE {$tableDAO->tableName}";
        CRM_Core_DAO::executeQuery($sql);
      }
    }
  }

  /*
   * Function does a 'Get' on the entity & compares the fields in the Params with those returned
   * Default behaviour is to also delete the entity
   * @param array $params params array to check agains
   * @param int  $id id of the entity concerned
   * @param string $entity name of entity concerned (e.g. membership)
   * @param bool $delete should the entity be deleted as part of this check
   * @param string $errorText text to print on error
   *
   */
  function getAndCheck($params, $id, $entity, $delete = 1, $errorText = '') {

    $result = $this->callAPISuccessGetSingle($entity, array(
      'id' => $id,
    ));

    if ($delete) {
      $this->callAPISuccess($entity, 'Delete', array(
        'id' => $id,
      ));
    }
    $dateFields = $keys = $dateTimeFields = array();
    $fields = $this->callAPISuccess($entity, 'getfields', array('version' => 3, 'action' => 'get'));
    foreach ($fields['values'] as $field => $settings) {
      if (array_key_exists($field, $result)) {
        $keys[CRM_Utils_Array::Value('name', $settings, $field)] = $field;
      }
      else {
        $keys[CRM_Utils_Array::Value('name', $settings, $field)] = CRM_Utils_Array::value('name', $settings, $field);
      }
      $type = CRM_Utils_Array::value('type', $settings);
      if ($type == CRM_Utils_Type::T_DATE) {
        $dateFields[] = $settings['name'];
        // we should identify both real names & unique names as dates
        if($field != $settings['name']) {
          $dateFields[] = $field;
        }
      }
      if($type == CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME) {
        $dateTimeFields[] = $settings['name'];
        // we should identify both real names & unique names as dates
        if($field != $settings['name']) {
          $dateTimeFields[] = $field;
        }
      }
    }

    if (strtolower($entity) == 'contribution') {
      $params['receive_date'] = date('Y-m-d', strtotime($params['receive_date']));
      // this is not returned in id format
      unset($params['payment_instrument_id']);
      $params['contribution_source'] = $params['source'];
      unset($params['source']);
    }

    foreach ($params as $key => $value) {
      if ($key == 'version' || substr($key, 0, 3) == 'api' || !array_key_exists($keys[$key], $result)) {
        continue;
      }
      if (in_array($key, $dateFields)) {
        $value = date('Y-m-d', strtotime($value));
        $result[$key] = date('Y-m-d', strtotime($result[$key]));
      }
      if (in_array($key, $dateTimeFields)) {
        $value = date('Y-m-d H:i:s', strtotime($value));
        $result[$keys[$key]] = date('Y-m-d H:i:s', strtotime(CRM_Utils_Array::value($keys[$key], $result, CRM_Utils_Array::value($key, $result))));
      }
      $this->assertEquals($value, $result[$keys[$key]], $key . " GetandCheck function determines that for key {$key} value: $value doesn't match " . print_r($result[$keys[$key]], TRUE) . $errorText);
    }
  }

  /**
   * Function to get formatted values in  the actual and expected result
   * @param array $actual actual calculated values
   * @param array $expected expected values
   *
   */
  function checkArrayEquals(&$actual, &$expected) {
    self::unsetId($actual);
    self::unsetId($expected);
    $this->assertEquals($actual, $expected);
  }

  /**
   * Function to unset the key 'id' from the array
   * @param array $unformattedArray The array from which the 'id' has to be unset
   *
   */
  static function unsetId(&$unformattedArray) {
    $formattedArray = array();
    if (array_key_exists('id', $unformattedArray)) {
      unset($unformattedArray['id']);
    }
    if (CRM_Utils_Array::value('values', $unformattedArray) && is_array($unformattedArray['values'])) {
      foreach ($unformattedArray['values'] as $key => $value) {
        if (is_Array($value)) {
          foreach ($value as $k => $v) {
            if ($k == 'id') {
              unset($value[$k]);
            }
          }
        }
        elseif ($key == 'id') {
          $unformattedArray[$key];
        }
        $formattedArray = array($value);
      }
      $unformattedArray['values'] = $formattedArray;
    }
  }

  /**
   *  Helper to enable/disable custom directory support
   *
   * @param array $customDirs with members:
   *   'php_path' Set to TRUE to use the default, FALSE or "" to disable support, or a string path to use another path
   *   'template_path' Set to TRUE to use the default, FALSE or "" to disable support, or a string path to use another path
   */
  function customDirectories($customDirs) {
    require_once 'CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton();

    if (empty($customDirs['php_path']) || $customDirs['php_path'] === FALSE) {
      unset($config->customPHPPathDir);
    }
    elseif ($customDirs['php_path'] === TRUE) {
      $config->customPHPPathDir = dirname(dirname(__FILE__)) . '/custom_directories/php/';
    }
    else {
      $config->customPHPPathDir = $php_path;
    }

    if (empty($customDirs['template_path']) || $customDirs['template_path'] === FALSE) {
      unset($config->customTemplateDir);
    }
    elseif ($customDirs['template_path'] === TRUE) {
      $config->customTemplateDir = dirname(dirname(__FILE__)) . '/custom_directories/templates/';
    }
    else {
      $config->customTemplateDir = $template_path;
    }
  }

  /**
   * Generate a temporary folder
   *
   * @return $string
   */
  function createTempDir($prefix = 'test-') {
    $tempDir = CRM_Utils_File::tempdir($prefix);
    $this->tempDirs[] = $tempDir;
    return $tempDir;
  }

  function cleanTempDirs() {
    if (!is_array($this->tempDirs)) {
      // fix test errors where this is not set
      return;
    }
    foreach ($this->tempDirs as $tempDir) {
      if (is_dir($tempDir)) {
        CRM_Utils_File::cleanDir($tempDir, TRUE, FALSE);
      }
    }
  }

  /**
   * Temporarily replace the singleton extension with a different one
   */
  function setExtensionSystem(CRM_Extension_System $system) {
    if ($this->origExtensionSystem == NULL) {
      $this->origExtensionSystem = CRM_Extension_System::singleton();
    }
    CRM_Extension_System::setSingleton($this->origExtensionSystem);
  }

  function unsetExtensionSystem() {
    if ($this->origExtensionSystem !== NULL) {
      CRM_Extension_System::setSingleton($this->origExtensionSystem);
      $this->origExtensionSystem = NULL;
    }
  }

  /**
   * Temporarily alter the settings-metadata to add a mock setting.
   *
   * WARNING: The setting metadata will disappear on the next cache-clear.
   *
   * @param $extras
   * @return void
   */
  function setMockSettingsMetaData($extras) {
    CRM_Core_BAO_Setting::$_cache = array();
    $this->callAPISuccess('system','flush', array());
    CRM_Core_BAO_Setting::$_cache = array();

    CRM_Utils_Hook::singleton()->setHook('civicrm_alterSettingsMetaData', function (&$metadata, $domainId, $profile) use ($extras) {
      $metadata = array_merge($metadata, $extras);
    });

    $fields = $this->callAPISuccess('setting', 'getfields', array());
    foreach ($extras as $key => $spec) {
      $this->assertNotEmpty($spec['title']);
      $this->assertEquals($spec['title'], $fields['values'][$key]['title']);
    }
  }

  function financialAccountDelete($name) {
    $financialAccount = new CRM_Financial_DAO_FinancialAccount();
    $financialAccount->name = $name;
    if($financialAccount->find(TRUE)) {
      $entityFinancialType = new CRM_Financial_DAO_EntityFinancialAccount();
      $entityFinancialType->financial_account_id = $financialAccount->id;
      $entityFinancialType->delete();
      $financialAccount->delete();
    }
  }

  /**
   * Use $ids as an instruction to do test cleanup
   */
  function deleteFromIDSArray() {
    foreach ($this->ids as $entity => $ids) {
      foreach ($ids as $id) {
        $this->callAPISuccess($entity, 'delete', array('id' => $id));
      }
    }
  }

  /**
   * FIXME: something NULLs $GLOBALS['_HTML_QuickForm_registered_rules'] when the tests are ran all together
   * (NB unclear if this is still required)
   */
  function _sethtmlGlobals() {
    $GLOBALS['_HTML_QuickForm_registered_rules'] = array(
      'required' => array(
        'html_quickform_rule_required',
        'HTML/QuickForm/Rule/Required.php'
      ),
      'maxlength' => array(
        'html_quickform_rule_range',
        'HTML/QuickForm/Rule/Range.php'
      ),
      'minlength' => array(
        'html_quickform_rule_range',
        'HTML/QuickForm/Rule/Range.php'
      ),
      'rangelength' => array(
        'html_quickform_rule_range',
        'HTML/QuickForm/Rule/Range.php'
      ),
      'email' => array(
        'html_quickform_rule_email',
        'HTML/QuickForm/Rule/Email.php'
      ),
      'regex' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ),
      'lettersonly' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ),
      'alphanumeric' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ),
      'numeric' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ),
      'nopunctuation' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ),
      'nonzero' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php'
      ),
      'callback' => array(
        'html_quickform_rule_callback',
        'HTML/QuickForm/Rule/Callback.php'
      ),
      'compare' => array(
        'html_quickform_rule_compare',
        'HTML/QuickForm/Rule/Compare.php'
      )
    );
    // FIXME: …ditto for $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']
    $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES'] = array(
      'group' => array(
        'HTML/QuickForm/group.php',
        'HTML_QuickForm_group'
      ),
      'hidden' => array(
        'HTML/QuickForm/hidden.php',
        'HTML_QuickForm_hidden'
      ),
      'reset' => array(
        'HTML/QuickForm/reset.php',
        'HTML_QuickForm_reset'
      ),
      'checkbox' => array(
        'HTML/QuickForm/checkbox.php',
        'HTML_QuickForm_checkbox'
      ),
      'file' => array(
        'HTML/QuickForm/file.php',
        'HTML_QuickForm_file'
      ),
      'image' => array(
        'HTML/QuickForm/image.php',
        'HTML_QuickForm_image'
      ),
      'password' => array(
        'HTML/QuickForm/password.php',
        'HTML_QuickForm_password'
      ),
      'radio' => array(
        'HTML/QuickForm/radio.php',
        'HTML_QuickForm_radio'
      ),
      'button' => array(
        'HTML/QuickForm/button.php',
        'HTML_QuickForm_button'
      ),
      'submit' => array(
        'HTML/QuickForm/submit.php',
        'HTML_QuickForm_submit'
      ),
      'select' => array(
        'HTML/QuickForm/select.php',
        'HTML_QuickForm_select'
      ),
      'hiddenselect' => array(
        'HTML/QuickForm/hiddenselect.php',
        'HTML_QuickForm_hiddenselect'
      ),
      'text' => array(
        'HTML/QuickForm/text.php',
        'HTML_QuickForm_text'
      ),
      'textarea' => array(
        'HTML/QuickForm/textarea.php',
        'HTML_QuickForm_textarea'
      ),
      'fckeditor' => array(
        'HTML/QuickForm/fckeditor.php',
        'HTML_QuickForm_FCKEditor'
      ),
      'tinymce' => array(
        'HTML/QuickForm/tinymce.php',
        'HTML_QuickForm_TinyMCE'
      ),
      'dojoeditor' => array(
        'HTML/QuickForm/dojoeditor.php',
        'HTML_QuickForm_dojoeditor'
      ),
      'link' => array(
        'HTML/QuickForm/link.php',
        'HTML_QuickForm_link'
      ),
      'advcheckbox' => array(
        'HTML/QuickForm/advcheckbox.php',
        'HTML_QuickForm_advcheckbox'
      ),
      'date' => array(
        'HTML/QuickForm/date.php',
        'HTML_QuickForm_date'
      ),
      'static' => array(
        'HTML/QuickForm/static.php',
        'HTML_QuickForm_static'
      ),
      'header' => array(
        'HTML/QuickForm/header.php',
        'HTML_QuickForm_header'
      ),
      'html' => array(
        'HTML/QuickForm/html.php',
        'HTML_QuickForm_html'
      ),
      'hierselect' => array(
        'HTML/QuickForm/hierselect.php',
        'HTML_QuickForm_hierselect'
      ),
      'autocomplete' => array(
        'HTML/QuickForm/autocomplete.php',
        'HTML_QuickForm_autocomplete'
      ),
      'xbutton' => array(
        'HTML/QuickForm/xbutton.php',
        'HTML_QuickForm_xbutton'
      ),
      'advmultiselect' => array(
        'HTML/QuickForm/advmultiselect.php',
        'HTML_QuickForm_advmultiselect'
      )
    );
  }

/**
 * Create an instance of the paypal processor
 * @todo this isn't a great place to put it - but really it belongs on a class that extends
 * this parent class & we don't have a structure for that yet
 * There is another function to this effect on the PaypalPro test but it appears to be silently failing
 * & the best protection agains that is the functions this class affords
 */
 function paymentProcessorCreate($params = array()) {
   $params = array_merge(array(
     'name' => 'demo',
     'domain_id' => CRM_Core_Config::domainID(),
     'payment_processor_type_id' => 'PayPal',
     'is_active' => 1,
     'is_default' => 0,
     'is_test' => 1,
     'user_name' => 'sunil._1183377782_biz_api1.webaccess.co.in',
     'password' => '1183377788',
     'signature' => 'APixCoQ-Zsaj-u3IH7mD5Do-7HUqA9loGnLSzsZga9Zr-aNmaJa3WGPH',
     'url_site' => 'https://www.sandbox.paypal.com/',
     'url_api' => 'https://api-3t.sandbox.paypal.com/',
     'url_button' => 'https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif',
     'class_name' => 'Payment_PayPalImpl',
     'billing_mode' => 3,
     'financial_type_id' => 1,
     ),
   $params);
   if(!is_numeric($params['payment_processor_type_id'])) {
     // really the api should handle this through getoptions but it's not exactly api call so lets just sort it
     //here
     $params['payment_processor_type_id'] = $this->callAPISuccess('payment_processor_type', 'getvalue', array(
       'name' => $params['payment_processor_type_id'],
       'return' => 'id',
       ), 'integer');
   }
   $result = $this->callAPISuccess('payment_processor', 'create', $params);
   return $result['id'];
 }


function CiviUnitTestCase_fatalErrorHandler($message) {
  throw new Exception("{$message['message']}: {$message['code']}");
}
}
