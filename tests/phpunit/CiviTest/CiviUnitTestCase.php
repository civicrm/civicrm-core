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
  public $_eNoticeCompliant = FALSE;

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

    if (!empty($GLOBALS['mysql_db'])) {
      self::$_dbName = $GLOBALS['mysql_db'];
    }
    else {
      self::$_dbName = 'civicrm_tests_dev';
    }

    //  create test database
    self::$utils = new Utils($GLOBALS['mysql_host'],
      $GLOBALS['mysql_user'],
      $GLOBALS['mysql_pass']
    );

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
    $dbName = !empty($GLOBALS['mysql_db']) ? $GLOBALS['mysql_db'] : 'civicrm_tests_dev';
    return $dbName;
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

      self::_populateDB(FALSE, $this);

      self::$dbInit = TRUE;
    }
    return $this->createDefaultDBConnection(self::$utils->pdo, $dbName);
  }

  /**
   *  Required implementation of abstract method
   */
  protected function getDataSet() {
  }

  private static function _populateDB($perClass = FALSE, &$object = NULL) {

    if ($perClass || $object == NULL) {
      $dbreset = TRUE;
    }
    else {
      $dbreset = $object->requireDBReset();
    }

    if (self::$populateOnce || !$dbreset) {
      return;
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
  }

  public static function setUpBeforeClass() {
    self::_populateDB(TRUE);

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
    $this->_populateDB();
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
    self::$utils = new Utils($GLOBALS['mysql_host'],
      $GLOBALS['mysql_user'],
      $GLOBALS['mysql_pass']
    );
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
    $this->assertEquals(0, $apiResult['is_error'], $prefix . (empty($apiResult['error_message']) ? '' : $apiResult['error_message']));
  }

  /**
   * check that api returned 'is_error' => 1
   * else provide full message
   * @param array $apiResult api result
   * @param string $prefix extra test to add to message
   */
  function assertAPIFailure($apiResult, $prefix = '') {
    if (!empty($prefix)) {
      $prefix .= ': ';
    }
    $this->assertEquals(1, $apiResult['is_error'], "api call should have failed but it succeeded " . $prefix . (print_r($apiResult, TRUE)));
  }

  function assertType($expected, $actual, $message = '') {
    return $this->assertInternalType($expected, $actual, $message);
  }
  /**

  /**
   * This function exists to wrap api functions
   * so we can ensure they succeed & throw exceptions without litterering the test with checks
   * @param string $entity
   * @param string $action
   * @param array $params
   * @param string $function - pass this in to create a generated example
   * @param string $file - pass this in to create a generated example
   */
  function callAPISuccess($entity, $action, $params) {
    $params += array(
      'version' => API_LATEST_VERSION,
      'debug' => 1,
    );
    $result = civicrm_api($entity, $action, $params);
    $this->assertAPISuccess($result, "Failure in api call for $entity $action");
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
   */
  function callAPIFailure($entity, $action, $params) {
    if (is_array($params)) {
      $params += array(
        'version' => API_LATEST_VERSION,
      );
    }
    $result = civicrm_api($entity, $action, $params);
    $this->assertAPIFailure($result, "We expected a failure for $entity $action but got a success");
    return $result;
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
      'version' => API_LATEST_VERSION,
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
  function individualCreate($params = NULL) {
    if ($params === NULL) {
      $params = array(
        'first_name' => 'Anthony',
        'middle_name' => 'J.',
        'last_name' => 'Anderson',
        'prefix_id' => 3,
        'suffix_id' => 3,
        'email' => 'anthony_anderson@civicrm.org',
        'contact_type' => 'Individual',
      );
    }
    $params['version'] = API_LATEST_VERSION;
    return $this->_contactCreate($params);
  }

  /**
   * Generic function to create Household, to be used in test cases
   *
   * @param array   parameters for civicrm_contact_add api function call
   *
   * @return int    id of Household created
   */
  function householdCreate($params = NULL) {
    if ($params === NULL) {
      $params = array(
        'household_name' => 'Unit Test household',
        'contact_type' => 'Household',
      );
    }
    $params['version'] = API_LATEST_VERSION;
    return $this->_contactCreate($params);
  }

  /**
   * Private helper function for calling civicrm_contact_add
   *
   * @param array   parameters for civicrm_contact_add api function call
   *
   * @return int    id of Household created
   */
  private function _contactCreate($params) {
    $params['version'] = API_LATEST_VERSION;
    $params['debug'] = 1;
    $result = civicrm_api('contact', 'create', $params);
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
      'version' => API_LATEST_VERSION,
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
    $result = civicrm_api('contact', 'delete', $params);
    if (CRM_Utils_Array::value('is_error', $result)) {
      throw new Exception('Could not delete contact, with message: ' . CRM_Utils_Array::value('error_message', $result) . "\nBacktrace:" . CRM_Utils_Array::value('trace', $result));
    }
    return;
  }

  function contactTypeDelete($contactTypeId) {
    require_once 'CRM/Contact/BAO/ContactType.php';
    $result = CRM_Contact_BAO_ContactType::del($contactTypeId);
    if (!$result) {
      throw new Exception('Could not delete contact type');
    }
  }

  function membershipTypeCreate($contactID, $contributionTypeID = 1, $version = 3) {
    require_once 'CRM/Member/PseudoConstant.php';
    CRM_Member_PseudoConstant::flush('membershipType');
    CRM_Core_Config::clearDBCache();
    $params = array(
      'name' => 'General',
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'member_of_contact_id' => $contactID,
      'domain_id' => 1,
      // FIXME: I know it's 1, cause it was loaded directly to the db.
      // FIXME: when we load all the data, we'll need to address this to
      // FIXME: avoid hunting numbers around.
      'financial_type_id' => $contributionTypeID,
      'is_active' => 1,
      'version' => $version,
      'sequential' => 1,
      'visibility' => 1,
    );

    $result = civicrm_api('MembershipType', 'Create', $params);
    require_once 'CRM/Member/PseudoConstant.php';
    CRM_Member_PseudoConstant::flush('membershipType');
    CRM_Utils_Cache::singleton()->flush();
    if (CRM_Utils_Array::value('is_error', $result) ||
      (!CRM_Utils_Array::value('id', $result) && !CRM_Utils_Array::value('id', $result['values'][0]))
    ) {
      throw new Exception('Could not create membership type, with message: ' . CRM_Utils_Array::value('error_message', $result));
    }

    return $result['id'];
  }

  function contactMembershipCreate($params) {
    $pre = array(
      'join_date' => '2007-01-21',
      'start_date' => '2007-01-21',
      'end_date' => '2007-12-21',
      'source' => 'Payment',
      'version' => API_LATEST_VERSION,
    );

    foreach ($pre as $key => $val) {
      if (!isset($params[$key])) {
        $params[$key] = $val;
      }
    }

    $result = civicrm_api('Membership', 'create', $params);

    if (CRM_Utils_Array::value('is_error', $result) ||
      !CRM_Utils_Array::value('id', $result)
    ) {
      if (CRM_Utils_Array::value('error_message', $result)) {
        throw new Exception('Could not create membership, with message: ' . CRM_Utils_Array::value('error_message', $result));
      }
      else {
        throw new Exception('Could not create membership' . ' - in line: ' . __LINE__);
      }
    }

    return $result['id'];
  }

  /**
   * Function to delete Membership Type
   *
   * @param int $membershipTypeID
   */
  function membershipTypeDelete($params) {
    $params['version'] = API_LATEST_VERSION;

    $result = civicrm_api('MembershipType', 'Delete', $params);
    if (CRM_Utils_Array::value('is_error', $result)) {
      throw new Exception('Could not delete membership type' . $result['error_message']);
    }
    return;
  }

  function membershipDelete($membershipID) {
    $deleteParams = array('version' => 3, 'id' => $membershipID);
    $result = civicrm_api('Membership', 'Delete', $deleteParams);
    if (CRM_Utils_Array::value('is_error', $result)) {
      throw new Exception('Could not delete membership ' . $result['error_message'] . " params were " . print_r($deleteParams, TRUE));
    }
    return;
  }

  function membershipStatusCreate($name = 'test member status') {
    $params['name'] = $name;
    $params['start_event'] = 'start_date';
    $params['end_event'] = 'end_date';
    $params['is_current_member'] = 1;
    $params['is_active'] = 1;
    $params['version'] = API_LATEST_VERSION;

    $result = civicrm_api('MembershipStatus', 'Create', $params);
    require_once 'CRM/Member/PseudoConstant.php';
    CRM_Member_PseudoConstant::flush('membershipStatus');
    if (CRM_Utils_Array::value('is_error', $result)) {
      throw new Exception("Could not create membership status: $name, Error message: " . $result['error_message']);
      exit();
    }
    return $result['id'];
  }

  function membershipStatusDelete($membershipStatusID) {
    if (!$membershipStatusID) {
      return;
    }
    $result = civicrm_api('MembershipStatus', 'Delete', array('id' => $membershipStatusID, 'version' => 3));
    if (CRM_Utils_Array::value('is_error', $result)) {
      throw new Exception('Could not delete membership status' . $result['error_message']);
    }
    return;
  }

  function relationshipTypeCreate($params = NULL) {
    if (is_null($params)) {
      $params = array(
        'name_a_b' => 'Relation 1 for relationship type create',
        'name_b_a' => 'Relation 2 for relationship type create',
        'contact_type_a' => 'Individual',
        'contact_type_b' => 'Organization',
        'is_reserved' => 1,
        'is_active' => 1,
      );
    }

    $params['version'] = API_LATEST_VERSION;

    $result = civicrm_api('relationship_type', 'create', $params);

    if (civicrm_error($params) || CRM_Utils_Array::value('is_error', $result)) {
      throw new Exception('Could not create relationship type');
    }

    require_once 'CRM/Core/PseudoConstant.php';
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
    $params['version'] = API_LATEST_VERSION;
    civicrm_api('relationship_type', 'delete', $params);

    if (civicrm_error($params)) {
      throw new Exception('Could not delete relationship type');
    }

    return;
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

    $params['version'] = API_LATEST_VERSION;

    $result = civicrm_api('payment_processor_type', 'create', $params);
    $this->assertAPISuccess($result);
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
    $defaults = array(
      'contact_id' => $params['contactID'],
      'event_id' => $params['eventID'],
      'status_id' => 2,
      'role_id' => 1,
      'register_date' => 20070219,
      'source' => 'Wimbeldon',
      'event_level' => 'Payment',
      'version' => API_LATEST_VERSION,
    );

    $params = array_merge($defaults, $params);
    $result = civicrm_api('Participant', 'create', $params);
    if (CRM_Utils_Array::value('is_error', $result)) {
      throw new Exception('Could not create participant ' . $result['error_message']);
    }
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
      'version' => 3,
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
    $contributionPage = civicrm_api('contribution_page', 'create', $this->_pageParams);
    return $contributionPage;
  }

  /**
   * Function to create Financial Type
   *
   * @return int $id of financial account created
   */
  function contributionTypeCreate() {

    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/../api/v' . API_LATEST_VERSION . '/dataset/financial_types.xml'
      )
    );

    require_once 'CRM/Contribute/PseudoConstant.php';
    $financialType = CRM_Contribute_PseudoConstant::financialType();
    CRM_Contribute_PseudoConstant::flush('financialType');
    return key($financialType);
  }

  /**
   * Function to delete financial Types
   *      * @param int $contributionTypeId
   */
  function contributionTypeDelete($contributionTypeID = NULL) {
    if ($contributionTypeID === NULL) {
      civicrm_api('Contribution', 'get',
        array(
          'version' => 3,
          'financial_type_id' => 10,
          'api.contribution.delete' => 1,
        )
      );
      civicrm_api('Contribution', 'get',
        array(
          'version' => 3,
          'financial_type_id' => 11,
          'api.contribution.delete' => 1,
        )
      );

      // we know those were loaded from /dataset/financial_types.xml
      $del = CRM_Financial_BAO_FinancialType::del(10, 1);
      $del = CRM_Financial_BAO_FinancialType::del(11, 1);
    }
    else {
      civicrm_api('Contribution', 'get', array(
        'version' => 3,
        'financial_type_id' => $contributionTypeID,
        'api.contribution.delete' => 1
      ));
      $del = CRM_Financial_BAO_FinancialType::del($contributionTypeID, 1);
    }
    if (is_array($del)) {
      $this->assertEquals(0, CRM_Utils_Array::value('is_error', $del), $del['error_message']);
    }
  }

  /**
   * Function to create Tag
   *
   * @return int tag_id of created tag
   */
  function tagCreate($params = NULL) {
    if ($params === NULL) {
      $params = array(
        'name' => 'New Tag3' . rand(),
        'description' => 'This is description for New Tag ' . rand(),
        'domain_id' => '1',
        'version' => API_LATEST_VERSION,
      );
    }

    $result = civicrm_api('Tag', 'create', $params);
    return $result;
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
      'version' => API_LATEST_VERSION,
    );
    $result = civicrm_api('Tag', 'delete', $params);
    if (CRM_Utils_Array::value('is_error', $result)) {
      throw new Exception('Could not delete tag');
    }
    return $result['id'];
  }

  /**
   * Add entity(s) to the tag
   *
   * @param  array  $params
   *
   */
  function entityTagAdd($params) {
    $params['version'] = API_LATEST_VERSION;
    $result = civicrm_api('entity_tag', 'create', $params);

    if (CRM_Utils_Array::value('is_error', $result)) {
      throw new Exception('Error while creating entity tag');
    }
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
      'version' => API_LATEST_VERSION,
    );

    $result = civicrm_api('Pledge', 'create', $params);
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
      'version' => API_LATEST_VERSION,
    );

    civicrm_api('Pledge', 'delete', $params);
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
      'version' => API_LATEST_VERSION,
      'contribution_status_id' => 1,
      // 'note'                   => 'Donating for Nobel Cause', *Fixme
    );

    if ($isFee) {
      $params['fee_amount'] = 5.00;
      $params['net_amount'] = 95.00;
    }

    $result = civicrm_api('contribution', 'create', $params);
    if (CRM_Utils_Array::value('is_error', $result) ||
      !CRM_Utils_Array::value('id', $result)
    ) {
      if (CRM_Utils_Array::value('error_message', $result)) {
        throw new Exception('Could not create contribution, with message: ' . CRM_Utils_Array::value('error_message', $result));
      }
      else {
        throw new Exception('Could not create contribution in line: ' . __LINE__);
      }
    }

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
      'version' => $this->_apiversion,
    );

    if (isset($params['contribution_status_id'])) {
      $contribParams['contribution_status_id'] = $params['contribution_status_id'];
    }
    else {
      $contribParams['contribution_status_id'] = 1;
    }
    if (isset($params['is_pay_later'])) {
      $contribParams['is_pay_later'] = 1;
    }
    if (isset($params['payment_processor'])) {
      $contribParams['payment_processor'] = $params['payment_processor'];
    }
    $result = civicrm_api('contribution', 'create', $contribParams);
    if (CRM_Utils_Array::value('is_error', $result) ||
      !CRM_Utils_Array::value('id', $result)
    ) {
      if (CRM_Utils_Array::value('error_message', $result)) {
        throw new Exception('Could not create contribution, with message: ' . CRM_Utils_Array::value('error_message', $result));
      }
      else {
        throw new Exception('Could not create contribution in line: ' . __LINE__);
      }
    }

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
      'version' => API_LATEST_VERSION,
    );
    $result = civicrm_api('contribution', 'delete', $params);

    if (CRM_Utils_Array::value('is_error', $result) ||
      !CRM_Utils_Array::value('id', $result)
    ) {
      if (CRM_Utils_Array::value('error_message', $result)) {
        throw new Exception('Could not delete contribution, with message: ' . CRM_Utils_Array::value('error_message', $result));
      }
      else {
        throw new Exception('Could not delete contribution - in line: ' . __LINE__);
      }
    }

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
        'version' => API_LATEST_VERSION
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
      'version' => API_LATEST_VERSION,
      'is_show_location' => 0,
    ), $params);

    $result = civicrm_api('Event', 'create', $params);
    if ($result['is_error'] == 1) {
      throw new Exception($result['error_message']);
    }
    return $result;
  }

  /**
   * Function to delete event
   *
   * @param int $id  ID of the event
   */
  function eventDelete($id) {
    $params = array(
      'event_id' => $id,
      'version' => API_LATEST_VERSION,
    );
    civicrm_api('event', 'delete', $params);
  }

  /**
   * Function to delete participant
   *
   * @param int $participantID
   */
  function participantDelete($participantID) {
    $params = array(
      'id' => $participantID,
      'version' => API_LATEST_VERSION,
    );
    $result = civicrm_api('Participant', 'delete', $params);

    if (CRM_Utils_Array::value('is_error', $result)) {
      throw new Exception('Could not delete participant');
    }
    return;
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
      'version' => API_LATEST_VERSION,
    );

    $result = civicrm_api('participant_payment', 'create', $params);

    if (CRM_Utils_Array::value('is_error', $result) ||
      !CRM_Utils_Array::value('id', $result)
    ) {
      if (CRM_Utils_Array::value('error_message', $result)) {
        throw new Exception('Could not delete contribution, with message: ' . CRM_Utils_Array::value('error_message', $result));
      }
      else {
        throw new Exception('Could not delete contribution - in line: ' . __LINE__);
      }
    }

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
      'version' => API_LATEST_VERSION,
    );

    $result = civicrm_api('participant_payment', 'delete', $params);

    if (CRM_Utils_Array::value('is_error', $result)) {
      if (CRM_Utils_Array::value('error_message', $result)) {
        throw new Exception('Could not delete contribution, with message: ' . CRM_Utils_Array::value('error_message', $result));
      }
      else {
        throw new Exception('Could not delete contribution - in line: ' . __LINE__);
      }
    }

    return;
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
      'version' => 2,
      'location_format' => '2.0',
      'location_type' => 'New Location Type',
    );

    $result = civicrm_api('Location', 'create', $params);

    if (civicrm_error($result)) {
      throw new Exception("Could not create location: {$result['error_message']}");
    }

    return $result;
  }

  /**
   * Function to delete Locations of contact
   *
   * @params array $pamars parameters
   */
  function locationDelete($params) {
    $params['version'] = 2;

    $result = civicrm_api('Location', 'delete', $params);
    if (civicrm_error($result)) {
      throw new Exception("Could not delete location: {$result['error_message']}");
    }

    return;
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
    civicrm_api('phone', 'getfields', array('version' => 3, 'cache_clear' => 1));
    return $locationType;
  }

  /**
   * Function to delete a Location Type
   *
   * @param int location type id
   */
  function locationTypeDelete($locationTypeId) {
    require_once 'CRM/Core/DAO/LocationType.php';
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
        'version' => API_LATEST_VERSION,
      );
    }

    $result = civicrm_api('Group', 'create', $params);
    if (CRM_Utils_Array::value('id', $result)) {
      return $result['id'];
    }
    elseif (CRM_Utils_Array::value('result', $result)) {
      return $result['result']->id;
    }
    else {
      return NULL;
    }
  }

  /**
   * Function to delete a Group
   *
   * @param int $id
   */
  function groupDelete($gid) {

    $params = array(
      'id' => $gid,
      'version' => API_LATEST_VERSION,
    );

    civicrm_api('Group', 'delete', $params);
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

    $result = crm_add_uf_join($params);

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

    civicrm_api('GroupContact', 'Create', $params);
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
      $individualSourceID = $this->individualCreate(NULL);

      $contactParams = array(
        'first_name' => 'Julia',
        'Last_name' => 'Anderson',
        'prefix' => 'Ms.',
        'email' => 'julia_anderson@civicrm.org',
        'contact_type' => 'Individual',
        'version' => API_LATEST_VERSION,
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
        'version' => API_LATEST_VERSION,
      );
    }


    $result = civicrm_api('Activity', 'create', $params);

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
    $params['version'] = API_LATEST_VERSION;
    $result = civicrm_api('ActivityType', 'create', $params);
    if (CRM_Utils_Array::value('is_error', $result) ||
      !CRM_Utils_Array::value('id', $result)
    ) {
      throw new Exception('Could not create Activity type ' . $result['error_message']);
    }
    return $result;
  }

  /**
   * Function to delete activity type
   *
   * @params Integer $activityTypeId id of the activity type
   */
  function activityTypeDelete($activityTypeId) {
    $params['activity_type_id'] = $activityTypeId;
    $params['version'] = API_LATEST_VERSION;
    $result = civicrm_api('ActivityType', 'delete', $params);
    if (!$result) {
      throw new Exception('Could not delete activity type');
    }
    return $result;
  }

  /**
   * Function to create custom group
   *
   * @param string $className
   * @param string $title  name of custom group
   */
  function customGroupCreate($extends = 'Contact', $title = 'title') {

    if (CRM_Utils_Array::value('title', $extends)) {
      $params = $extends;
      $params['title'] = strlen($params['title']) > 13 ? substr($params['title'], 0, 13) : $params['title'];
    }
    else {
      $params = array(
        'title' => strlen($title) > 13 ? substr($title, 0, 13) : $title,
        'extends' => $extends,
        'domain_id' => 1,
        'style' => 'Inline',
        'is_active' => 1,
        'version' => API_LATEST_VERSION,
      );
    }
    //have a crack @ deleting it first in the hope this will prevent derailing our tests
    $check = civicrm_api('custom_group', 'get', array_merge($params, array('api.custom_group.delete' => 1)));

    $result = civicrm_api('custom_group', 'create', $params);

    if (CRM_Utils_Array::value('is_error', $result) ||
      !CRM_Utils_Array::value('id', $result)
    ) {
      throw new Exception('Could not create Custom Group ' . print_r($params, TRUE) . $result['error_message']);
    }
    return $result;
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
      'version' => API_LATEST_VERSION,
    );
    $params = array_merge($defaults, $params);
    $result = civicrm_api('custom_group', 'create', $params);

    if (CRM_Utils_Array::value('is_error', $result) ||
      !CRM_Utils_Array::value('id', $result)
    ) {
      throw new Exception('Could not create Custom Group ' . $result['error_message']);
    }
    return $result;
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
    $result = $this->CustomGroupCreateByParams($params);

    if (CRM_Utils_Array::value('is_error', $result) ||
      !CRM_Utils_Array::value('id', $result)
    ) {
      throw new Exception('Could not create Custom Group ' . $result['error_message']);
    }
    return $result;
  }

  /**
   * Create custom group with multi fields
   */
  function CustomGroupMultipleCreateWithFields($params = array()) {
    // also need to pass on $params['custom_field'] if not set but not in place yet
    $ids = array();
    $customGroup = $this->CustomGroupMultipleCreateByParams($params);
    $ids['custom_group_id'] = $customGroup['id'];
    if (CRM_Utils_Array::value('is_error', $ids['custom_group_id']) ||
      !CRM_Utils_Array::value('id', $customGroup)
    ) {
      throw new Exception('Could not create Custom Group from CustomGroupMultipleCreateWithFields' . $customGroup['error_message']);
    }

    $customField = $this->customFieldCreate($ids['custom_group_id']);

    $ids['custom_field_id'][] = $customField['id'];
    if (CRM_Utils_Array::value('is_error', $customField) ||
      !CRM_Utils_Array::value('id', $customField)
    ) {
      throw new Exception('Could not create Custom Field ' . $ids['custom_field']['error_message']);
    }
    $customField = $this->customFieldCreate($ids['custom_group_id'], 'field_2');
    $ids['custom_field_id'][] = $customField['id'];
    if (CRM_Utils_Array::value('is_error', $customField) ||
      !CRM_Utils_Array::value('id', $customField)
    ) {
      throw new Exception('Could not create Custom Field ' . $ids['custom_field']['error_message']);
    }
    $customField = $this->customFieldCreate($ids['custom_group_id'], 'field_3');
    $ids['custom_field_id'][] = $customField['id'];
    if (CRM_Utils_Array::value('is_error', $customField) ||
      !CRM_Utils_Array::value('id', $customField)
    ) {
      throw new Exception('Could not create Custom Field ' . $ids['custom_field']['error_message']);
    }
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
    $entity = substr(basename($filename), 0, strlen(basename($filename)) - 8);
    if (empty($entity)) {
      $entity = 'Contact';
    }
    $customGroup = $this->CustomGroupCreate($entity, $function);
    $customField = $this->customFieldCreate($customGroup['id'], $function);
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
    $params['version'] = API_LATEST_VERSION;
    $result = civicrm_api('custom_group', 'delete', $params);
    if (CRM_Utils_Array::value('is_error', $result)) {
      print_r($params);
      throw new Exception('Could not delete custom group' . $result['error_message']);
    }
    return;
  }

  /**
   * Function to create custom field
   *
   * @param int    $customGroupID
   * @param string $name  name of custom field
   * @param int $apiversion API  version to use
   */
  function customFieldCreate($customGroupID, $name = "Cust Field") {

    $params = array(
      'label' => $name,
      'name' => $name,
      'custom_group_id' => $customGroupID,
      'data_type' => 'String',
      'html_type' => 'Text',
      'is_searchable' => 1,
      'is_active' => 1,
      'version' => API_LATEST_VERSION,
    );

    $result = civicrm_api('custom_field', 'create', $params);

    if ($result['is_error'] == 0 && isset($result['id'])) {
      CRM_Core_BAO_CustomField::getTableColumnGroup($result['id'], 1);
      // force reset of enabled components to help grab custom fields
      CRM_Core_Component::getEnabledComponents(1);
      return $result;
    }

    if (civicrm_error($result)
      || !(CRM_Utils_Array::value('customFieldId', $result['result']))
    ) {
      throw new Exception('Could not create Custom Field ' . $result['error_message']);
    }
  }

  /**
   * Function to delete custom field
   *
   * @param int $customFieldID
   */
  function customFieldDelete($customFieldID) {

    $params['id'] = $customFieldID;
    $params['version'] = API_LATEST_VERSION;

    $result = civicrm_api('custom_field', 'delete', $params);

    if (civicrm_error($result)) {
      throw new Exception('Could not delete custom field');
    }
    return;
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
      'version' => API_LATEST_VERSION,
    );

    $result = civicrm_api('Note', 'create', $params);

    if (CRM_Utils_Array::value('is_error', $result)) {
      if (CRM_Utils_Array::value('error_message', $result)) {
        throw new Exception('Could not delete note, with message: ' . CRM_Utils_Array::value('error_message', $result));
      }
      else {
        throw new Exception('Could not delete note - in line: ' . __LINE__);
      }
    }

    return $result;
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
      elseif (strstr($function, 'Set')) {
        $action = empty($action) ? 'set' : $action;
        $entityAction = 'Set';
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

    $fieldsToChange = array(
      'hash' => '67eac7789eaee00',
      'modified_date' => '2012-11-14 16:02:35',
    );
    //swap out keys that change too often
    foreach ($fieldsToChange as $changeKey => $changeValue) {
      if (isset($result['values']) && is_array($result['values'])) {
        foreach ($result['values'] as $key => $value) {
          if (is_array($value) && array_key_exists($changeKey, $value)) {
            $result['values'][$key][$changeKey] = $changeValue;
          }
        }
      }
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
   * Function to delete note
   *
   * @params int $noteID
   *
   */
  function noteDelete($params) {
    $params['version'] = API_LATEST_VERSION;

    $result = civicrm_api('Note', 'delete', $params);

    if (CRM_Utils_Array::value('is_error', $result)) {
      if (CRM_Utils_Array::value('error_message', $result)) {
        throw new Exception('Could not delete note, with message: ' . CRM_Utils_Array::value('error_message', $result));
      }
      else {
        throw new Exception('Could not delete note - in line: ' . __LINE__);
      }
    }

    return $result;
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
      'version' => API_LATEST_VERSION,
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

    $result = civicrm_api('custom_field', 'create', $params);

    if ($result['is_error'] == 0 && isset($result['id'])) {
      return $result;
    }
    if (civicrm_error($result)
      || !(CRM_Utils_Array::value('customFieldId', $result['result']))
    ) {
      throw new Exception('Could not create Custom Field');
    }
    return $result;
  }

  function confirmEntitiesDeleted($entities) {
    foreach ($entities as $entity) {

      $result = civicrm_api($entity, 'Get', array(
        'version' => 3,
      ));
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

    $result = civicrm_api($entity, 'GetSingle', array(
      'id' => $id,
      'version' => $this->_apiversion,
    ));

    if ($delete) {
      civicrm_api($entity, 'Delete', array(
        'id' => $id,
        'version' => $this->_apiversion,
      ));
    }
    $dateFields = $keys = array();
    $fields = civicrm_api($entity, 'getfields', array('version' => 3, 'action' => 'get'));
    foreach ($fields['values'] as $field => $settings) {
      if (array_key_exists($field, $result)) {
        $keys[CRM_Utils_Array::Value('name', $settings, $field)] = $field;
      }
      else {
        $keys[CRM_Utils_Array::Value('name', $settings, $field)] = CRM_Utils_Array::value('name', $settings, $field);
      }

      if (CRM_Utils_Array::value('type', $settings) == CRM_Utils_Type::T_DATE) {
        $dateFields[] = $field;
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
      if ($key == 'version' || substr($key, 0, 3) == 'api') {
        continue;
      }
      if (in_array($key, $dateFields)) {
        $value = date('Y-m-d', strtotime($value));
        $result[$key] = date('Y-m-d', strtotime($result[$key]));
      }
      $this->assertEquals($value, $result[$keys[$key]], $key . " GetandCheck function determines that value: $value doesn't match " . print_r($result, TRUE) . $errorText);
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
}

function CiviUnitTestCase_fatalErrorHandler($message) {
  throw new Exception("{$message['message']}: {$message['code']}");
}
