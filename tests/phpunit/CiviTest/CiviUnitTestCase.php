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

use Civi\Payment\System;

/**
 *  Include class definitions
 */
require_once 'api/api.php';
define('API_LATEST_VERSION', 3);

/**
 *  Base class for CiviCRM unit tests
 *
 * This class supports two (mutually-exclusive) techniques for cleaning up test data. Subclasses
 * may opt for one or neither:
 *
 * 1. quickCleanup() is a helper which truncates a series of tables. Call quickCleanup()
 *    as part of setUp() and/or tearDown(). quickCleanup() is thorough - but it can
 *    be cumbersome to use (b/c you must identify the tables to cleanup) and slow to execute.
 * 2. useTransaction() executes the test inside a transaction. It's easier to use
 *    (because you don't need to identify specific tables), but it doesn't work for tests
 *    which manipulate schema or truncate data -- and could behave inconsistently
 *    for tests which specifically examine DB transactions.
 *
 *  Common functions for unit tests
 * @package CiviCRM
 */
class CiviUnitTestCase extends PHPUnit_Extensions_Database_TestCase {

  use \Civi\Test\Api3DocTrait;

  /**
   *  Database has been initialized.
   *
   * @var boolean
   */
  private static $dbInit = FALSE;

  /**
   *  Database connection.
   *
   * @var PHPUnit_Extensions_Database_DB_IDatabaseConnection
   */
  protected $_dbconn;

  /**
   * The database name.
   *
   * @var string
   */
  static protected $_dbName;

  /**
   * Track tables we have modified during a test.
   */
  protected $_tablesToTruncate = array();

  /**
   * @var array of temporary directory names
   */
  protected $tempDirs;

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
   * @var boolean DBResetRequired allows skipping DB reset
   *               in specific test case. If you still need
   *               to reset single test (method) of such case, call
   *               $this->cleanDB() in the first line of this
   *               test (method).
   */
  public $DBResetRequired = TRUE;

  /**
   * @var CRM_Core_Transaction|NULL
   */
  private $tx = NULL;

  /**
   * Class used for hooks during tests.
   *
   * This can be used to test hooks within tests. For example in the ACL_PermissionTrait:
   *
   * $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookAllResults'));
   *
   * @var CRM_Utils_Hook_UnitTests hookClass
   */
  public $hookClass = NULL;

  /**
   * @var array common values to be re-used multiple times within a class - usually to create the relevant entity
   */
  protected $_params = array();

  /**
   * @var CRM_Extension_System
   */
  protected $origExtensionSystem;

  /**
   * Array of IDs created during test setup routine.
   *
   * The cleanUpSetUpIds method can be used to clear these at the end of the test.
   *
   * @var array
   */
  public $setupIDs = array();

  /**
   * PHPUnit Mock Method to use.
   *
   * @var string
   */
  public $mockMethod = 'getMock';

  /**
   *  Constructor.
   *
   *  Because we are overriding the parent class constructor, we
   *  need to show the same arguments as exist in the constructor of
   *  PHPUnit_Framework_TestCase, since
   *  PHPUnit_Framework_TestSuite::createTest() creates a
   *  ReflectionClass of the Test class and checks the constructor
   *  of that class to decide how to set up the test.
   *
   * @param string $name
   * @param array $data
   * @param string $dataName
   */
  public function __construct($name = NULL, array$data = array(), $dataName = '') {
    parent::__construct($name, $data, $dataName);

    // we need full error reporting
    error_reporting(E_ALL & ~E_NOTICE);

    self::$_dbName = self::getDBName();

    // also load the class loader
    require_once 'CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();
    if (function_exists('_civix_phpunit_setUp')) {
      // FIXME: loosen coupling
      _civix_phpunit_setUp();
    }
    if (version_compare(PHPUnit_Runner_Version::id(), '5', '>=')) {
      $this->mockMethod = 'createMock';
    }
  }

  /**
   * Override to run the test and assert its state.
   * @return mixed
   * @throws \Exception
   * @throws \PHPUnit_Framework_IncompleteTest
   * @throws \PHPUnit_Framework_SkippedTest
   */
  protected function runTest() {
    try {
      return parent::runTest();
    }
    catch (PEAR_Exception $e) {
      // PEAR_Exception has metadata in funny places, and PHPUnit won't log it nicely
      throw new Exception(\CRM_Core_Error::formatTextException($e), $e->getCode());
    }
  }

  /**
   * @return bool
   */
  public function requireDBReset() {
    return $this->DBResetRequired;
  }

  /**
   * @return string
   */
  public static function getDBName() {
    static $dbName = NULL;
    if ($dbName === NULL) {
      require_once "DB.php";
      $dsninfo = DB::parseDSN(CIVICRM_DSN);
      $dbName = $dsninfo['database'];
    }
    return $dbName;
  }

  /**
   *  Create database connection for this instance.
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

    return $this->createDefaultDBConnection(Civi\Test::pdo(), $dbName);
  }

  /**
   *  Required implementation of abstract method.
   */
  protected function getDataSet() {
  }

  /**
   * @param bool $perClass
   * @param null $object
   * @return bool
   *   TRUE if the populate logic runs; FALSE if it is skipped
   */
  protected static function _populateDB($perClass = FALSE, &$object = NULL) {
    if (CIVICRM_UF !== 'UnitTests') {
      throw new \RuntimeException("_populateDB requires CIVICRM_UF=UnitTests");
    }

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

    Civi\Test::data()->populate();

    return TRUE;
  }

  public static function setUpBeforeClass() {
    static::_populateDB(TRUE);

    // also set this global hack
    $GLOBALS['_PEAR_ERRORSTACK_OVERRIDE_CALLBACK'] = array();
  }

  /**
   *  Common setup functions for all unit tests.
   */
  protected function setUp() {
    $session = CRM_Core_Session::singleton();
    $session->set('userID', NULL);

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

    $GLOBALS['civicrm_setting']['domain']['fatalErrorHandler'] = 'CiviUnitTestCase_fatalErrorHandler';
    $GLOBALS['civicrm_setting']['domain']['backtrace'] = 1;

    // disable any left-over test extensions
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_extension WHERE full_name LIKE "test.%"');

    // reset all the caches
    CRM_Utils_System::flushCache();

    // initialize the object once db is loaded
    \Civi::reset();
    $config = CRM_Core_Config::singleton(TRUE, TRUE); // ugh, performance

    // when running unit tests, use mockup user framework
    $this->hookClass = CRM_Utils_Hook::singleton();

    // Make sure the DB connection is setup properly
    $config->userSystem->setMySQLTimeZone();
    $env = new CRM_Utils_Check_Component_Env();
    CRM_Utils_Check::singleton()->assertValid($env->checkMysqlTime());

    // clear permissions stub to not check permissions
    $config->userPermissionClass->permissions = NULL;

    //flush component settings
    CRM_Core_Component::getEnabledComponents(TRUE);

    $_REQUEST = $_GET = $_POST = [];
    error_reporting(E_ALL);

    $this->_sethtmlGlobals();
  }

  /**
   * Read everything from the datasets directory and insert into the db.
   */
  public function loadAllFixtures() {
    $fixturesDir = __DIR__ . '/../../fixtures';

    $this->getConnection()->getConnection()->query("SET FOREIGN_KEY_CHECKS = 0;");

    $xmlFiles = glob($fixturesDir . '/*.xml');
    foreach ($xmlFiles as $xmlFixture) {
      $op = new PHPUnit_Extensions_Database_Operation_Insert();
      $dataset = $this->createXMLDataSet($xmlFixture);
      $this->_tablesToTruncate = array_merge($this->_tablesToTruncate, $dataset->getTableNames());
      $op->execute($this->_dbconn, $dataset);
    }

    $yamlFiles = glob($fixturesDir . '/*.yaml');
    foreach ($yamlFiles as $yamlFixture) {
      $op = new PHPUnit_Extensions_Database_Operation_Insert();
      $dataset = new PHPUnit_Extensions_Database_DataSet_YamlDataSet($yamlFixture);
      $this->_tablesToTruncate = array_merge($this->_tablesToTruncate, $dataset->getTableNames());
      $op->execute($this->_dbconn, $dataset);
    }

    $this->getConnection()->getConnection()->query("SET FOREIGN_KEY_CHECKS = 1;");
  }

  /**
   * Emulate a logged in user since certain functions use that.
   * value to store a record in the DB (like activity)
   * CRM-8180
   *
   * @return int
   *   Contact ID of the created user.
   */
  public function createLoggedInUser() {
    $params = array(
      'first_name' => 'Logged In',
      'last_name' => 'User ' . rand(),
      'contact_type' => 'Individual',
    );
    $contactID = $this->individualCreate($params);
    $this->callAPISuccess('UFMatch', 'create', array(
      'contact_id' => $contactID,
      'uf_name' => 'superman',
      'uf_id' => 6,
    ));

    $session = CRM_Core_Session::singleton();
    $session->set('userID', $contactID);
    return $contactID;
  }

  /**
   * Create default domain contacts for the two domains added during test class.
   * database population.
   */
  public function createDomainContacts() {
    $this->organizationCreate();
    $this->organizationCreate(array('organization_name' => 'Second Domain'));
  }

  /**
   *  Common teardown functions for all unit tests.
   */
  protected function tearDown() {
    error_reporting(E_ALL & ~E_NOTICE);
    CRM_Utils_Hook::singleton()->reset();
    if ($this->hookClass) {
      $this->hookClass->reset();
    }
    CRM_Core_Session::singleton()->reset(1);

    if ($this->tx) {
      $this->tx->rollback()->commit();
      $this->tx = NULL;

      CRM_Core_Transaction::forceRollbackIfEnabled();
      \Civi\Core\Transaction\Manager::singleton(TRUE);
    }
    else {
      CRM_Core_Transaction::forceRollbackIfEnabled();
      \Civi\Core\Transaction\Manager::singleton(TRUE);

      $tablesToTruncate = array('civicrm_contact', 'civicrm_uf_match');
      $this->quickCleanup($tablesToTruncate);
      $this->createDomainContacts();
    }

    $this->cleanTempDirs();
    $this->unsetExtensionSystem();
  }

  /**
   * Generic function to compare expected values after an api call to retrieved.
   * DB values.
   *
   * @daoName  string   DAO Name of object we're evaluating.
   * @id       int      Id of object
   * @match    array    Associative array of field name => expected value. Empty if asserting
   *                      that a DELETE occurred
   * @delete   boolean  True if we're checking that a DELETE action occurred.
   * @param $daoName
   * @param $id
   * @param $match
   * @param bool $delete
   * @throws \PHPUnit_Framework_AssertionFailedError
   */
  public function assertDBState($daoName, $id, $match, $delete = FALSE) {
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
      $fields = &$object->fields();
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

  /**
   * Request a record from the DB by seachColumn+searchValue. Success if a record is found.
   * @param string $daoName
   * @param $searchValue
   * @param $returnColumn
   * @param $searchColumn
   * @param $message
   *
   * @return null|string
   * @throws PHPUnit_Framework_AssertionFailedError
   */
  public function assertDBNotNull($daoName, $searchValue, $returnColumn, $searchColumn, $message) {
    if (empty($searchValue)) {
      $this->fail("empty value passed to assertDBNotNull");
    }
    $value = CRM_Core_DAO::getFieldValue($daoName, $searchValue, $returnColumn, $searchColumn, TRUE);
    $this->assertNotNull($value, $message);

    return $value;
  }

  /**
   * Request a record from the DB by seachColumn+searchValue. Success if returnColumn value is NULL.
   * @param string $daoName
   * @param $searchValue
   * @param $returnColumn
   * @param $searchColumn
   * @param $message
   */
  public function assertDBNull($daoName, $searchValue, $returnColumn, $searchColumn, $message) {
    $value = CRM_Core_DAO::getFieldValue($daoName, $searchValue, $returnColumn, $searchColumn, TRUE);
    $this->assertNull($value, $message);
  }

  /**
   * Request a record from the DB by id. Success if row not found.
   * @param string $daoName
   * @param int $id
   * @param null $message
   */
  public function assertDBRowNotExist($daoName, $id, $message = NULL) {
    $message = $message ? $message : "$daoName (#$id) should not exist";
    $value = CRM_Core_DAO::getFieldValue($daoName, $id, 'id', 'id', TRUE);
    $this->assertNull($value, $message);
  }

  /**
   * Request a record from the DB by id. Success if row not found.
   * @param string $daoName
   * @param int $id
   * @param null $message
   */
  public function assertDBRowExist($daoName, $id, $message = NULL) {
    $message = $message ? $message : "$daoName (#$id) should exist";
    $value = CRM_Core_DAO::getFieldValue($daoName, $id, 'id', 'id', TRUE);
    $this->assertEquals($id, $value, $message);
  }

  /**
   * Compare a single column value in a retrieved DB record to an expected value.
   * @param string $daoName
   * @param $searchValue
   * @param $returnColumn
   * @param $searchColumn
   * @param $expectedValue
   * @param $message
   */
  public function assertDBCompareValue(
    $daoName, $searchValue, $returnColumn, $searchColumn,
    $expectedValue, $message
  ) {
    $value = CRM_Core_DAO::getFieldValue($daoName, $searchValue, $returnColumn, $searchColumn, TRUE);
    $this->assertEquals($expectedValue, $value, $message);
  }

  /**
   * Compare all values in a single retrieved DB record to an array of expected values.
   * @param string $daoName
   * @param array $searchParams
   * @param $expectedValues
   */
  public function assertDBCompareValues($daoName, $searchParams, $expectedValues) {
    //get the values from db
    $dbValues = array();
    CRM_Core_DAO::commonRetrieve($daoName, $searchParams, $dbValues);

    // compare db values with expected values
    self::assertAttributesEquals($expectedValues, $dbValues);
  }

  /**
   * Assert that a SQL query returns a given value.
   *
   * The first argument is an expected value. The remaining arguments are passed
   * to CRM_Core_DAO::singleValueQuery
   *
   * Example: $this->assertSql(2, 'select count(*) from foo where foo.bar like "%1"',
   * array(1 => array("Whiz", "String")));
   * @param $expected
   * @param $query
   * @param array $params
   * @param string $message
   */
  public function assertDBQuery($expected, $query, $params = array(), $message = '') {
    if ($message) {
      $message .= ': ';
    }
    $actual = CRM_Core_DAO::singleValueQuery($query, $params);
    $this->assertEquals($expected, $actual,
      sprintf('%sexpected=[%s] actual=[%s] query=[%s]',
        $message, $expected, $actual, CRM_Core_DAO::composeQuery($query, $params, FALSE)
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
  public function assertTreeEquals($expected, $actual) {
    $e = array();
    $a = array();
    CRM_Utils_Array::flatten($expected, $e, '', ':::');
    CRM_Utils_Array::flatten($actual, $a, '', ':::');
    ksort($e);
    ksort($a);

    $this->assertEquals($e, $a);
  }

  /**
   * Assert that two numbers are approximately equal.
   *
   * @param int|float $expected
   * @param int|float $actual
   * @param int|float $tolerance
   * @param string $message
   */
  public function assertApproxEquals($expected, $actual, $tolerance, $message = NULL) {
    if ($message === NULL) {
      $message = sprintf("approx-equals: expected=[%.3f] actual=[%.3f] tolerance=[%.3f]", $expected, $actual, $tolerance);
    }
    $this->assertTrue(abs($actual - $expected) < $tolerance, $message);
  }

  /**
   * Assert attributes are equal.
   *
   * @param $expectedValues
   * @param $actualValues
   * @param string $message
   *
   * @throws PHPUnit_Framework_AssertionFailedError
   */
  public function assertAttributesEquals($expectedValues, $actualValues, $message = NULL) {
    foreach ($expectedValues as $paramName => $paramValue) {
      if (isset($actualValues[$paramName])) {
        $this->assertEquals($paramValue, $actualValues[$paramName], "Value Mismatch On $paramName - value 1 is " . print_r($paramValue, TRUE) . "  value 2 is " . print_r($actualValues[$paramName], TRUE));
      }
      else {
        $this->assertNull($expectedValues[$paramName], "Attribute '$paramName' not present in actual array and we expected it to be " . $expectedValues[$paramName]);
      }
    }
  }

  /**
   * @param $key
   * @param $list
   */
  public function assertArrayKeyExists($key, &$list) {
    $result = isset($list[$key]) ? TRUE : FALSE;
    $this->assertTrue($result, ts("%1 element exists?",
      array(1 => $key)
    ));
  }

  /**
   * @param $key
   * @param $list
   */
  public function assertArrayValueNotNull($key, &$list) {
    $this->assertArrayKeyExists($key, $list);

    $value = isset($list[$key]) ? $list[$key] : NULL;
    $this->assertTrue($value,
      ts("%1 element not null?",
        array(1 => $key)
      )
    );
  }

  /**
   * Assert the 2 arrays have the same values.
   *
   * @param array $array1
   * @param array $array2
   */
  public function assertArrayValuesEqual($array1, $array2) {
    $array1 = array_values($array1);
    $array2 = array_values($array2);
    sort($array1);
    sort($array2);
    $this->assertEquals($array1, $array2);
  }

  /**
   * @param $expected
   * @param $actual
   * @param string $message
   */
  public function assertType($expected, $actual, $message = '') {
    return $this->assertInternalType($expected, $actual, $message);
  }

  /**
   * Create a batch of external API calls which can
   * be executed concurrently.
   *
   * @code
   * $calls = $this->createExternalAPI()
   *    ->addCall('Contact', 'get', ...)
   *    ->addCall('Contact', 'get', ...)
   *    ...
   *    ->run()
   *    ->getResults();
   * @endcode
   *
   * @return \Civi\API\ExternalBatch
   * @throws PHPUnit_Framework_SkippedTestError
   */
  public function createExternalAPI() {
    global $civicrm_root;
    $defaultParams = array(
      'version' => $this->_apiversion,
      'debug' => 1,
    );

    $calls = new \Civi\API\ExternalBatch($defaultParams);

    if (!$calls->isSupported()) {
      $this->markTestSkipped('The test relies on Civi\API\ExternalBatch. This is unsupported in the local environment.');
    }

    return $calls;
  }

  /**
   * Create required data based on $this->entity & $this->params
   * This is just a way to set up the test data for delete & get functions
   * so the distinction between set
   * up & tested functions is clearer
   *
   * @return array
   *   api Result
   */
  public function createTestEntity() {
    return $entity = $this->callAPISuccess($this->entity, 'create', $this->params);
  }

  /**
   * Generic function to create Organisation, to be used in test cases
   *
   * @param array $params
   *   parameters for civicrm_contact_add api function call
   * @param int $seq
   *   sequence number if creating multiple organizations
   *
   * @return int
   *   id of Organisation created
   */
  public function organizationCreate($params = array(), $seq = 0) {
    if (!$params) {
      $params = array();
    }
    $params = array_merge($this->sampleContact('Organization', $seq), $params);
    return $this->_contactCreate($params);
  }

  /**
   * Generic function to create Individual, to be used in test cases
   *
   * @param array $params
   *   parameters for civicrm_contact_add api function call
   * @param int $seq
   *   sequence number if creating multiple individuals
   * @param bool $random
   *
   * @return int
   *   id of Individual created
   */
  public function individualCreate($params = array(), $seq = 0, $random = FALSE) {
    $params = array_merge($this->sampleContact('Individual', $seq, $random), $params);
    return $this->_contactCreate($params);
  }

  /**
   * Generic function to create Household, to be used in test cases
   *
   * @param array $params
   *   parameters for civicrm_contact_add api function call
   * @param int $seq
   *   sequence number if creating multiple households
   *
   * @return int
   *   id of Household created
   */
  public function householdCreate($params = array(), $seq = 0) {
    $params = array_merge($this->sampleContact('Household', $seq), $params);
    return $this->_contactCreate($params);
  }

  /**
   * Helper function for getting sample contact properties.
   *
   * @param string $contact_type
   *   enum contact type: Individual, Organization
   * @param int $seq
   *   sequence number for the values of this type
   *
   * @return array
   *   properties of sample contact (ie. $params for API call)
   */
  public function sampleContact($contact_type, $seq = 0, $random = FALSE) {
    $samples = array(
      'Individual' => array(
        // The number of values in each list need to be coprime numbers to not have duplicates
        'first_name' => array('Anthony', 'Joe', 'Terrence', 'Lucie', 'Albert', 'Bill', 'Kim'),
        'middle_name' => array('J.', 'M.', 'P', 'L.', 'K.', 'A.', 'B.', 'C.', 'D', 'E.', 'Z.'),
        'last_name' => array('Anderson', 'Miller', 'Smith', 'Collins', 'Peterson'),
      ),
      'Organization' => array(
        'organization_name' => array(
          'Unit Test Organization',
          'Acme',
          'Roberts and Sons',
          'Cryo Space Labs',
          'Sharper Pens',
        ),
      ),
      'Household' => array(
        'household_name' => array('Unit Test household'),
      ),
    );
    $params = array('contact_type' => $contact_type);
    foreach ($samples[$contact_type] as $key => $values) {
      $params[$key] = $values[$seq % count($values)];
      if ($random) {
        $params[$key] .= substr(sha1(rand()), 0, 5);
      }
    }
    if ($contact_type == 'Individual') {
      $params['email'] = strtolower(
        $params['first_name'] . '_' . $params['last_name'] . '@civicrm.org'
      );
      $params['prefix_id'] = 3;
      $params['suffix_id'] = 3;
    }
    return $params;
  }

  /**
   * Private helper function for calling civicrm_contact_add.
   *
   * @param array $params
   *   For civicrm_contact_add api function call.
   *
   * @throws Exception
   *
   * @return int
   *   id of Household created
   */
  private function _contactCreate($params) {
    $result = $this->callAPISuccess('contact', 'create', $params);
    if (!empty($result['is_error']) || empty($result['id'])) {
      throw new Exception('Could not create test contact, with message: ' . CRM_Utils_Array::value('error_message', $result) . "\nBacktrace:" . CRM_Utils_Array::value('trace', $result));
    }
    return $result['id'];
  }

  /**
   * Delete contact, ensuring it is not the domain contact
   *
   * @param int $contactID
   *   Contact ID to delete
   */
  public function contactDelete($contactID) {
    $domain = new CRM_Core_BAO_Domain();
    $domain->contact_id = $contactID;
    if (!$domain->find(TRUE)) {
      $this->callAPISuccess('contact', 'delete', array(
        'id' => $contactID,
        'skip_undelete' => 1,
      ));
    }
  }

  /**
   * @param int $contactTypeId
   *
   * @throws Exception
   */
  public function contactTypeDelete($contactTypeId) {
    $result = CRM_Contact_BAO_ContactType::del($contactTypeId);
    if (!$result) {
      throw new Exception('Could not delete contact type');
    }
  }

  /**
   * @param array $params
   *
   * @return mixed
   */
  public function membershipTypeCreate($params = array()) {
    CRM_Member_PseudoConstant::flush('membershipType');
    CRM_Core_Config::clearDBCache();
    $this->setupIDs['contact'] = $memberOfOrganization = $this->organizationCreate();
    $params = array_merge(array(
      'name' => 'General',
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'member_of_contact_id' => $memberOfOrganization,
      'domain_id' => 1,
      'financial_type_id' => 2,
      'is_active' => 1,
      'sequential' => 1,
      'visibility' => 'Public',
    ), $params);

    $result = $this->callAPISuccess('MembershipType', 'Create', $params);

    CRM_Member_PseudoConstant::flush('membershipType');
    CRM_Utils_Cache::singleton()->flush();

    return $result['id'];
  }

  /**
   * @param array $params
   *
   * @return mixed
   */
  public function contactMembershipCreate($params) {
    $params = array_merge(array(
      'join_date' => '2007-01-21',
      'start_date' => '2007-01-21',
      'end_date' => '2007-12-21',
      'source' => 'Payment',
      'membership_type_id' => 'General',
    ), $params);
    if (!is_numeric($params['membership_type_id'])) {
      $membershipTypes = $this->callAPISuccess('Membership', 'getoptions', array('action' => 'create', 'field' => 'membership_type_id'));
      if (!in_array($params['membership_type_id'], $membershipTypes['values'])) {
        $this->membershipTypeCreate(array('name' => $params['membership_type_id']));
      }
    }

    $result = $this->callAPISuccess('Membership', 'create', $params);
    return $result['id'];
  }

  /**
   * Delete Membership Type.
   *
   * @param array $params
   */
  public function membershipTypeDelete($params) {
    $this->callAPISuccess('MembershipType', 'Delete', $params);
  }

  /**
   * @param int $membershipID
   */
  public function membershipDelete($membershipID) {
    $deleteParams = array('id' => $membershipID);
    $result = $this->callAPISuccess('Membership', 'Delete', $deleteParams);
  }

  /**
   * @param string $name
   *
   * @return mixed
   */
  public function membershipStatusCreate($name = 'test member status') {
    $params['name'] = $name;
    $params['start_event'] = 'start_date';
    $params['end_event'] = 'end_date';
    $params['is_current_member'] = 1;
    $params['is_active'] = 1;

    $result = $this->callAPISuccess('MembershipStatus', 'Create', $params);
    CRM_Member_PseudoConstant::flush('membershipStatus');
    return $result['id'];
  }

  /**
   * @param int $membershipStatusID
   */
  public function membershipStatusDelete($membershipStatusID) {
    if (!$membershipStatusID) {
      return;
    }
    $result = $this->callAPISuccess('MembershipStatus', 'Delete', array('id' => $membershipStatusID));
  }

  public function membershipRenewalDate($durationUnit, $membershipEndDate) {
    // We only have an end_date if frequency units match, otherwise membership won't be autorenewed and dates won't be calculated.
    $renewedMembershipEndDate = new DateTime($membershipEndDate);
    switch ($durationUnit) {
      case 'year':
        $renewedMembershipEndDate->add(new DateInterval('P1Y'));
        break;

      case 'month':
        // We have to add 1 day first in case it's the end of the month, then subtract afterwards
        // eg. 2018-02-28 should renew to 2018-03-31, if we just added 1 month we'd get 2018-03-28
        $renewedMembershipEndDate->add(new DateInterval('P1D'));
        $renewedMembershipEndDate->add(new DateInterval('P1M'));
        $renewedMembershipEndDate->sub(new DateInterval('P1D'));
        break;
    }
    return $renewedMembershipEndDate->format('Y-m-d');
  }

  /**
   * @param array $params
   *
   * @return mixed
   */
  public function relationshipTypeCreate($params = array()) {
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
   * Delete Relatinship Type.
   *
   * @param int $relationshipTypeID
   */
  public function relationshipTypeDelete($relationshipTypeID) {
    $params['id'] = $relationshipTypeID;
    $check = $this->callAPISuccess('relationship_type', 'get', $params);
    if (!empty($check['count'])) {
      $this->callAPISuccess('relationship_type', 'delete', $params);
    }
  }

  /**
   * @param array $params
   *
   * @return mixed
   */
  public function paymentProcessorTypeCreate($params = NULL) {
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
   * Create test Authorize.net instance.
   *
   * @param array $params
   *
   * @return mixed
   */
  public function paymentProcessorAuthorizeNetCreate($params = array()) {
    $params = array_merge(array(
      'name' => 'Authorize',
      'domain_id' => CRM_Core_Config::domainID(),
      'payment_processor_type_id' => 'AuthNet',
      'title' => 'AuthNet',
      'is_active' => 1,
      'is_default' => 0,
      'is_test' => 1,
      'is_recur' => 1,
      'user_name' => '4y5BfuW7jm',
      'password' => '4cAmW927n8uLf5J8',
      'url_site' => 'https://test.authorize.net/gateway/transact.dll',
      'url_recur' => 'https://apitest.authorize.net/xml/v1/request.api',
      'class_name' => 'Payment_AuthorizeNet',
      'billing_mode' => 1,
    ), $params);

    $result = $this->callAPISuccess('PaymentProcessor', 'create', $params);
    return $result['id'];
  }

  /**
   * Create Participant.
   *
   * @param array $params
   *   Array of contact id and event id values.
   *
   * @return int
   *   $id of participant created
   */
  public function participantCreate($params = []) {
    if (empty($params['contact_id'])) {
      $params['contact_id'] = $this->individualCreate();
    }
    if (empty($params['event_id'])) {
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
   * Create Payment Processor.
   *
   * @return int
   *   Id Payment Processor
   */
  public function processorCreate($params = array()) {
    $processorParams = array(
      'domain_id' => 1,
      'name' => 'Dummy',
      'payment_processor_type_id' => 'Dummy',
      'financial_account_id' => 12,
      'is_test' => TRUE,
      'is_active' => 1,
      'user_name' => '',
      'url_site' => 'http://dummy.com',
      'url_recur' => 'http://dummy.com',
      'billing_mode' => 1,
      'sequential' => 1,
      'payment_instrument_id' => 'Debit Card',
    );
    $processorParams = array_merge($processorParams, $params);
    $processor = $this->callAPISuccess('PaymentProcessor', 'create', $processorParams);
    return $processor['id'];
  }

  /**
   * Create Payment Processor.
   *
   * @param array $processorParams
   *
   * @return \CRM_Core_Payment_Dummy
   *    Instance of Dummy Payment Processor
   */
  public function dummyProcessorCreate($processorParams = array()) {
    $paymentProcessorID = $this->processorCreate($processorParams);
    return System::singleton()->getById($paymentProcessorID);
  }

  /**
   * Create contribution page.
   *
   * @param array $params
   * @return array
   *   Array of contribution page
   */
  public function contributionPageCreate($params = array()) {
    $this->_pageParams = array_merge(array(
      'title' => 'Test Contribution Page',
      'financial_type_id' => 1,
      'currency' => 'USD',
      'financial_account_id' => 1,
      'is_active' => 1,
      'is_allow_other_amount' => 1,
      'min_amount' => 10,
      'max_amount' => 1000,
    ), $params);
    return $this->callAPISuccess('contribution_page', 'create', $this->_pageParams);
  }

  /**
   * Create a sample batch.
   */
  public function batchCreate() {
    $params = $this->_params;
    $params['name'] = $params['title'] = 'Batch_433397';
    $params['status_id'] = 1;
    $result = $this->callAPISuccess('batch', 'create', $params);
    return $result['id'];
  }

  /**
   * Create Tag.
   *
   * @param array $params
   * @return array
   *   result of created tag
   */
  public function tagCreate($params = array()) {
    $defaults = array(
      'name' => 'New Tag3',
      'description' => 'This is description for Our New Tag ',
      'domain_id' => '1',
    );
    $params = array_merge($defaults, $params);
    $result = $this->callAPISuccess('Tag', 'create', $params);
    return $result['values'][$result['id']];
  }

  /**
   * Delete Tag.
   *
   * @param int $tagId
   *   Id of the tag to be deleted.
   *
   * @return int
   */
  public function tagDelete($tagId) {
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
   * @param array $params
   *
   * @return bool
   */
  public function entityTagAdd($params) {
    $result = $this->callAPISuccess('entity_tag', 'create', $params);
    return TRUE;
  }

  /**
   * Create pledge.
   *
   * @param array $params
   *  Parameters.
   *
   * @return int
   *   id of created pledge
   */
  public function pledgeCreate($params) {
    $params = array_merge(array(
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
    ),
    $params);

    $result = $this->callAPISuccess('Pledge', 'create', $params);
    return $result['id'];
  }

  /**
   * Delete contribution.
   *
   * @param int $pledgeId
   */
  public function pledgeDelete($pledgeId) {
    $params = array(
      'pledge_id' => $pledgeId,
    );
    $this->callAPISuccess('Pledge', 'delete', $params);
  }

  /**
   * Create contribution.
   *
   * @param array $params
   *   Array of parameters.
   *
   * @return int
   *   id of created contribution
   */
  public function contributionCreate($params) {

    $params = array_merge(array(
      'domain_id' => 1,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'fee_amount' => 5.00,
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    ), $params);

    $result = $this->callAPISuccess('contribution', 'create', $params);
    return $result['id'];
  }

  /**
   * Delete contribution.
   *
   * @param int $contributionId
   *
   * @return array|int
   */
  public function contributionDelete($contributionId) {
    $params = array(
      'contribution_id' => $contributionId,
    );
    $result = $this->callAPISuccess('contribution', 'delete', $params);
    return $result;
  }

  /**
   * Create an Event.
   *
   * @param array $params
   *   Name-value pair for an event.
   *
   * @return array
   */
  public function eventCreate($params = array()) {
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
      'is_monetary' => 0,
      'is_active' => 1,
      'is_show_location' => 0,
    ), $params);

    return $this->callAPISuccess('Event', 'create', $params);
  }

  /**
   * Create a paid event.
   *
   * @param array $params
   *
   * @return array
   */
  protected function eventCreatePaid($params) {
    $event = $this->eventCreate($params);
    $this->priceSetID = $this->eventPriceSetCreate(55, 0, 'Radio');
    CRM_Price_BAO_PriceSet::addTo('civicrm_event', $event['id'], $this->priceSetID);
    $priceSet = CRM_Price_BAO_PriceSet::getSetDetail($this->priceSetID, TRUE, FALSE);
    $priceSet = CRM_Utils_Array::value($this->priceSetID, $priceSet);
    $this->eventFeeBlock = CRM_Utils_Array::value('fields', $priceSet);
    return $event;
  }

  /**
   * Delete event.
   *
   * @param int $id
   *   ID of the event.
   *
   * @return array|int
   */
  public function eventDelete($id) {
    $params = array(
      'event_id' => $id,
    );
    return $this->callAPISuccess('event', 'delete', $params);
  }

  /**
   * Delete participant.
   *
   * @param int $participantID
   *
   * @return array|int
   */
  public function participantDelete($participantID) {
    $params = array(
      'id' => $participantID,
    );
    $check = $this->callAPISuccess('Participant', 'get', $params);
    if ($check['count'] > 0) {
      return $this->callAPISuccess('Participant', 'delete', $params);
    }
  }

  /**
   * Create participant payment.
   *
   * @param int $participantID
   * @param int $contributionID
   * @return int
   *   $id of created payment
   */
  public function participantPaymentCreate($participantID, $contributionID = NULL) {
    //Create Participant Payment record With Values
    $params = array(
      'participant_id' => $participantID,
      'contribution_id' => $contributionID,
    );

    $result = $this->callAPISuccess('participant_payment', 'create', $params);
    return $result['id'];
  }

  /**
   * Delete participant payment.
   *
   * @param int $paymentID
   */
  public function participantPaymentDelete($paymentID) {
    $params = array(
      'id' => $paymentID,
    );
    $result = $this->callAPISuccess('participant_payment', 'delete', $params);
  }

  /**
   * Add a Location.
   *
   * @param int $contactID
   * @return int
   *   location id of created location
   */
  public function locationAdd($contactID) {
    $address = array(
      1 => array(
        'location_type' => 'New Location Type',
        'is_primary' => 1,
        'name' => 'Saint Helier St',
        'county' => 'Marin',
        'country' => 'UNITED STATES',
        'state_province' => 'Michigan',
        'supplemental_address_1' => 'Hallmark Ct',
        'supplemental_address_2' => 'Jersey Village',
        'supplemental_address_3' => 'My Town',
      ),
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
   * Delete Locations of contact.
   *
   * @param array $params
   *   Parameters.
   */
  public function locationDelete($params) {
    $this->callAPISuccess('Location', 'delete', $params);
  }

  /**
   * Add a Location Type.
   *
   * @param array $params
   * @return CRM_Core_DAO_LocationType
   *   location id of created location
   */
  public function locationTypeCreate($params = NULL) {
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
   * Delete a Location Type.
   *
   * @param int $locationTypeId
   */
  public function locationTypeDelete($locationTypeId) {
    $locationType = new CRM_Core_DAO_LocationType();
    $locationType->id = $locationTypeId;
    $locationType->delete();
  }

  /**
   * Add a Mapping.
   *
   * @param array $params
   * @return CRM_Core_DAO_Mapping
   *   Mapping id of created mapping
   */
  public function mappingCreate($params = NULL) {
    if ($params === NULL) {
      $params = array(
        'name' => 'Mapping name',
        'description' => 'Mapping description',
        // 'Export Contact' mapping.
        'mapping_type_id' => 7,
      );
    }

    $mapping = new CRM_Core_DAO_Mapping();
    $mapping->copyValues($params);
    $mapping->save();
    // clear getfields cache
    CRM_Core_PseudoConstant::flush();
    $this->callAPISuccess('mapping', 'getfields', array('version' => 3, 'cache_clear' => 1));
    return $mapping;
  }

  /**
   * Delete a Mapping
   *
   * @param int $mappingId
   */
  public function mappingDelete($mappingId) {
    $mapping = new CRM_Core_DAO_Mapping();
    $mapping->id = $mappingId;
    $mapping->delete();
  }

  /**
   * Add a Group.
   *
   * @param array $params
   * @return int
   *   groupId of created group
   */
  public function groupCreate($params = array()) {
    $params = array_merge(array(
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
    ), $params);

    $result = $this->callAPISuccess('Group', 'create', $params);
    return $result['id'];
  }

  /**
   * Prepare class for ACLs.
   */
  protected function prepareForACLs() {
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = array();
  }

  /**
   * Reset after ACLs.
   */
  protected function cleanUpAfterACLs() {
    CRM_Utils_Hook::singleton()->reset();
    $tablesToTruncate = array(
      'civicrm_acl',
      'civicrm_acl_cache',
      'civicrm_acl_entity_role',
      'civicrm_acl_contact_cache',
    );
    $this->quickCleanup($tablesToTruncate);
    $config = CRM_Core_Config::singleton();
    unset($config->userPermissionClass->permissions);
  }
  /**
   * Create a smart group.
   *
   * By default it will be a group of households.
   *
   * @param array $smartGroupParams
   * @param array $groupParams
   * @return int
   */
  public function smartGroupCreate($smartGroupParams = array(), $groupParams = array()) {
    $smartGroupParams = array_merge(array(
      'formValues' => array('contact_type' => array('IN' => array('Household'))),
    ),
    $smartGroupParams);
    $savedSearch = CRM_Contact_BAO_SavedSearch::create($smartGroupParams);

    $groupParams['saved_search_id'] = $savedSearch->id;
    return $this->groupCreate($groupParams);
  }

  /**
   * Function to add a Group.
   *
   * @params array to add group
   *
   * @param int $groupID
   * @param int $totalCount
   * @param bool $random
   * @return int
   *    groupId of created group
   */
  public function groupContactCreate($groupID, $totalCount = 10, $random = FALSE) {
    $params = array('group_id' => $groupID);
    for ($i = 1; $i <= $totalCount; $i++) {
      $contactID = $this->individualCreate(array(), 0, $random);
      if ($i == 1) {
        $params += array('contact_id' => $contactID);
      }
      else {
        $params += array("contact_id.$i" => $contactID);
      }
    }
    $result = $this->callAPISuccess('GroupContact', 'create', $params);

    return $result;
  }

  /**
   * Delete a Group.
   *
   * @param int $gid
   */
  public function groupDelete($gid) {

    $params = array(
      'id' => $gid,
    );

    $this->callAPISuccess('Group', 'delete', $params);
  }

  /**
   * Create a UFField.
   * @param array $params
   */
  public function uFFieldCreate($params = array()) {
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
   * Add a UF Join Entry.
   *
   * @param array $params
   * @return int
   *   $id of created UF Join
   */
  public function ufjoinCreate($params = NULL) {
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
   * @param array $params
   *   Optional parameters.
   * @param bool $reloadConfig
   *   While enabling CiviCampaign component, we shouldn't always forcibly
   *    reload config as this hinder hook call in test environment
   *
   * @return int
   *   Campaign ID.
   */
  public function campaignCreate($params = array(), $reloadConfig = TRUE) {
    $this->enableCiviCampaign($reloadConfig);
    $campaign = $this->callAPISuccess('campaign', 'create', array_merge(array(
      'name' => 'big_campaign',
      'title' => 'Campaign',
    ), $params));
    return $campaign['id'];
  }

  /**
   * Create Group for a contact.
   *
   * @param int $contactId
   */
  public function contactGroupCreate($contactId) {
    $params = array(
      'contact_id.1' => $contactId,
      'group_id' => 1,
    );

    $this->callAPISuccess('GroupContact', 'Create', $params);
  }

  /**
   * Delete Group for a contact.
   *
   * @param int $contactId
   */
  public function contactGroupDelete($contactId) {
    $params = array(
      'contact_id.1' => $contactId,
      'group_id' => 1,
    );
    $this->civicrm_api('GroupContact', 'Delete', $params);
  }

  /**
   * Create Activity.
   *
   * @param array $params
   * @return array|int
   */
  public function activityCreate($params = array()) {
    $params = array_merge(array(
      'subject' => 'Discussion on warm beer',
      'activity_date_time' => date('Ymd'),
      'duration_hours' => 30,
      'duration_minutes' => 20,
      'location' => 'Baker Street',
      'details' => 'Lets schedule a meeting',
      'status_id' => 1,
      'activity_type_id' => 'Meeting',
    ), $params);
    if (!isset($params['source_contact_id'])) {
      $params['source_contact_id'] = $this->individualCreate();
    }
    if (!isset($params['target_contact_id'])) {
      $params['target_contact_id'] = $this->individualCreate(array(
        'first_name' => 'Julia',
        'Last_name' => 'Anderson',
        'prefix' => 'Ms.',
        'email' => 'julia_anderson@civicrm.org',
        'contact_type' => 'Individual',
      ));
    }
    if (!isset($params['assignee_contact_id'])) {
      $params['assignee_contact_id'] = $params['target_contact_id'];
    }

    $result = $this->callAPISuccess('Activity', 'create', $params);

    $result['target_contact_id'] = $params['target_contact_id'];
    $result['assignee_contact_id'] = $params['assignee_contact_id'];
    return $result;
  }

  /**
   * Create an activity type.
   *
   * @param array $params
   *   Parameters.
   * @return array
   */
  public function activityTypeCreate($params) {
    return $this->callAPISuccess('ActivityType', 'create', $params);
  }

  /**
   * Delete activity type.
   *
   * @param int $activityTypeId
   *   Id of the activity type.
   * @return array
   */
  public function activityTypeDelete($activityTypeId) {
    $params['activity_type_id'] = $activityTypeId;
    return $this->callAPISuccess('ActivityType', 'delete', $params);
  }

  /**
   * Create custom group.
   *
   * @param array $params
   * @return array
   */
  public function customGroupCreate($params = array()) {
    $defaults = array(
      'title' => 'new custom group',
      'extends' => 'Contact',
      'domain_id' => 1,
      'style' => 'Inline',
      'is_active' => 1,
    );

    $params = array_merge($defaults, $params);

    //have a crack @ deleting it first in the hope this will prevent derailing our tests
    $this->callAPISuccess('custom_group', 'get', array(
      'title' => $params['title'],
      array('api.custom_group.delete' => 1),
    ));

    return $this->callAPISuccess('custom_group', 'create', $params);
  }

  /**
   * Existing function doesn't allow params to be over-ridden so need a new one
   * this one allows you to only pass in the params you want to change
   * @param array $params
   * @return array|int
   */
  public function CustomGroupCreateByParams($params = array()) {
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
   * Create custom group with multi fields.
   * @param array $params
   * @return array|int
   */
  public function CustomGroupMultipleCreateByParams($params = array()) {
    $defaults = array(
      'style' => 'Tab',
      'is_multiple' => 1,
    );
    $params = array_merge($defaults, $params);
    return $this->CustomGroupCreateByParams($params);
  }

  /**
   * Create custom group with multi fields.
   * @param array $params
   * @return array
   */
  public function CustomGroupMultipleCreateWithFields($params = array()) {
    // also need to pass on $params['custom_field'] if not set but not in place yet
    $ids = array();
    $customGroup = $this->CustomGroupMultipleCreateByParams($params);
    $ids['custom_group_id'] = $customGroup['id'];

    $customField = $this->customFieldCreate(array(
      'custom_group_id' => $ids['custom_group_id'],
      'label' => 'field_1' . $ids['custom_group_id'],
      'in_selector' => 1,
    ));

    $ids['custom_field_id'][] = $customField['id'];

    $customField = $this->customFieldCreate(array(
      'custom_group_id' => $ids['custom_group_id'],
      'default_value' => '',
      'label' => 'field_2' . $ids['custom_group_id'],
      'in_selector' => 1,
    ));
    $ids['custom_field_id'][] = $customField['id'];

    $customField = $this->customFieldCreate(array(
      'custom_group_id' => $ids['custom_group_id'],
      'default_value' => '',
      'label' => 'field_3' . $ids['custom_group_id'],
      'in_selector' => 1,
    ));
    $ids['custom_field_id'][] = $customField['id'];

    return $ids;
  }

  /**
   * Create a custom group with a single text custom field.  See
   * participant:testCreateWithCustom for how to use this
   *
   * @param string $function
   *   __FUNCTION__.
   * @param string $filename
   *   $file __FILE__.
   *
   * @return array
   *   ids of created objects
   */
  public function entityCustomGroupWithSingleFieldCreate($function, $filename) {
    $params = array('title' => $function);
    $entity = substr(basename($filename), 0, strlen(basename($filename)) - 8);
    $params['extends'] = $entity ? $entity : 'Contact';
    $customGroup = $this->CustomGroupCreate($params);
    $customField = $this->customFieldCreate(array('custom_group_id' => $customGroup['id'], 'label' => $function));
    CRM_Core_PseudoConstant::flush();

    return array('custom_group_id' => $customGroup['id'], 'custom_field_id' => $customField['id']);
  }

  /**
   * Create a custom group with a single text custom field, multi-select widget, with a variety of option values including upper and lower case.
   * See api_v3_SyntaxConformanceTest:testCustomDataGet for how to use this
   *
   * @param string $function
   *   __FUNCTION__.
   * @param string $filename
   *   $file __FILE__.
   *
   * @return array
   *   ids of created objects
   */
  public function entityCustomGroupWithSingleStringMultiSelectFieldCreate($function, $filename) {
    $params = array('title' => $function);
    $entity = substr(basename($filename), 0, strlen(basename($filename)) - 8);
    $params['extends'] = $entity ? $entity : 'Contact';
    $customGroup = $this->CustomGroupCreate($params);
    $customField = $this->customFieldCreate(array('custom_group_id' => $customGroup['id'], 'label' => $function, 'html_type' => 'Multi-Select', 'default_value' => 1));
    CRM_Core_PseudoConstant::flush();
    $options = [
      'defaultValue' => 'Default Value',
      'lowercasevalue' => 'Lowercase Value',
      1 => 'Integer Value',
      'NULL' => 'NULL',
    ];
    $custom_field_params = ['sequential' => 1, 'id' => $customField['id']];
    $custom_field_api_result = $this->callAPISuccess('custom_field', 'get', $custom_field_params);
    $this->assertNotEmpty($custom_field_api_result['values'][0]['option_group_id']);
    $option_group_params = ['sequential' => 1, 'id' => $custom_field_api_result['values'][0]['option_group_id']];
    $option_group_result = $this->callAPISuccess('OptionGroup', 'get', $option_group_params);
    $this->assertNotEmpty($option_group_result['values'][0]['name']);
    foreach ($options as $option_value => $option_label) {
      $option_group_params = ['option_group_id' => $option_group_result['values'][0]['name'], 'value' => $option_value, 'label' => $option_label];
      $option_value_result = $this->callAPISuccess('OptionValue', 'create', $option_group_params);
    }

    return array('custom_group_id' => $customGroup['id'], 'custom_field_id' => $customField['id'], 'custom_field_option_group_id' => $custom_field_api_result['values'][0]['option_group_id'], 'custom_field_group_options' => $options);
  }


  /**
   * Delete custom group.
   *
   * @param int $customGroupID
   *
   * @return array|int
   */
  public function customGroupDelete($customGroupID) {
    $params['id'] = $customGroupID;
    return $this->callAPISuccess('custom_group', 'delete', $params);
  }

  /**
   * Create custom field.
   *
   * @param array $params
   *   (custom_group_id) is required.
   * @return array
   */
  public function customFieldCreate($params) {
    $params = array_merge(array(
      'label' => 'Custom Field',
      'data_type' => 'String',
      'html_type' => 'Text',
      'is_searchable' => 1,
      'is_active' => 1,
      'default_value' => 'defaultValue',
    ), $params);

    $result = $this->callAPISuccess('custom_field', 'create', $params);
    // these 2 functions are called with force to flush static caches
    CRM_Core_BAO_CustomField::getTableColumnGroup($result['id'], 1);
    CRM_Core_Component::getEnabledComponents(1);
    return $result;
  }

  /**
   * Delete custom field.
   *
   * @param int $customFieldID
   *
   * @return array|int
   */
  public function customFieldDelete($customFieldID) {

    $params['id'] = $customFieldID;
    return $this->callAPISuccess('custom_field', 'delete', $params);
  }

  /**
   * Create note.
   *
   * @param int $cId
   * @return array
   */
  public function noteCreate($cId) {
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
   * Enable CiviCampaign Component.
   *
   * @param bool $reloadConfig
   *    Force relaod config or not
   */
  public function enableCiviCampaign($reloadConfig = TRUE) {
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
    if ($reloadConfig) {
      // force reload of config object
      $config = CRM_Core_Config::singleton(TRUE, TRUE);
    }
    //flush cache by calling with reset
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, TRUE, 'name', TRUE);
  }

  /**
   * Create custom field with Option Values.
   *
   * @param array $customGroup
   * @param string $name
   *   Name of custom field.
   * @param array $extraParams
   *   Additional parameters to pass through.
   *
   * @return array|int
   */
  public function customFieldOptionValueCreate($customGroup, $name, $extraParams = array()) {
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

    $params = array_merge($fieldParams, $optionGroup, $optionValue, $extraParams);

    return $this->callAPISuccess('custom_field', 'create', $params);
  }

  /**
   * @param $entities
   *
   * @return bool
   */
  public function confirmEntitiesDeleted($entities) {
    foreach ($entities as $entity) {

      $result = $this->callAPISuccess($entity, 'Get', array());
      if ($result['error'] == 1 || $result['count'] > 0) {
        // > than $entity[0] to allow a value to be passed in? e.g. domain?
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Quick clean by emptying tables created for the test.
   *
   * @param array $tablesToTruncate
   * @param bool $dropCustomValueTables
   * @throws \Exception
   */
  public function quickCleanup($tablesToTruncate, $dropCustomValueTables = FALSE) {
    if ($this->tx) {
      throw new Exception("CiviUnitTestCase: quickCleanup() is not compatible with useTransaction()");
    }
    if ($dropCustomValueTables) {
      $optionGroupResult = CRM_Core_DAO::executeQuery('SELECT option_group_id FROM civicrm_custom_field');
      while ($optionGroupResult->fetch()) {
        if (!empty($optionGroupResult->option_group_id)) {
          CRM_Core_DAO::executeQuery('DELETE FROM civicrm_option_group WHERE id = ' . $optionGroupResult->option_group_id);
        }
      }
      $tablesToTruncate[] = 'civicrm_custom_group';
      $tablesToTruncate[] = 'civicrm_custom_field';
    }

    $tablesToTruncate = array_unique(array_merge($this->_tablesToTruncate, $tablesToTruncate));

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

  /**
   * Clean up financial entities after financial tests (so we remember to get all the tables :-))
   */
  public function quickCleanUpFinancialEntities() {
    $tablesToTruncate = array(
      'civicrm_activity',
      'civicrm_activity_contact',
      'civicrm_contribution',
      'civicrm_contribution_soft',
      'civicrm_contribution_product',
      'civicrm_financial_trxn',
      'civicrm_financial_item',
      'civicrm_contribution_recur',
      'civicrm_line_item',
      'civicrm_contribution_page',
      'civicrm_payment_processor',
      'civicrm_entity_financial_trxn',
      'civicrm_membership',
      'civicrm_membership_type',
      'civicrm_membership_payment',
      'civicrm_membership_log',
      'civicrm_membership_block',
      'civicrm_event',
      'civicrm_participant',
      'civicrm_participant_payment',
      'civicrm_pledge',
      'civicrm_pledge_payment',
      'civicrm_price_set_entity',
      'civicrm_price_field_value',
      'civicrm_price_field',
    );
    $this->quickCleanup($tablesToTruncate);
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_membership_status WHERE name NOT IN('New', 'Current', 'Grace', 'Expired', 'Pending', 'Cancelled', 'Deceased')");
    $this->restoreDefaultPriceSetConfig();
    $var = TRUE;
    CRM_Member_BAO_Membership::createRelatedMemberships($var, $var, TRUE);
    $this->disableTaxAndInvoicing();
    $this->setCurrencySeparators(',');
    CRM_Core_PseudoConstant::flush('taxRates');
    System::singleton()->flushProcessors();
  }

  public function restoreDefaultPriceSetConfig() {
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_price_set WHERE name NOT IN('default_contribution_amount', 'default_membership_type_amount')");
    CRM_Core_DAO::executeQuery("UPDATE civicrm_price_set SET id = 1 WHERE name ='default_contribution_amount'");
    CRM_Core_DAO::executeQuery("INSERT INTO `civicrm_price_field` (`id`, `price_set_id`, `name`, `label`, `html_type`, `is_enter_qty`, `help_pre`, `help_post`, `weight`, `is_display_amounts`, `options_per_line`, `is_active`, `is_required`, `active_on`, `expire_on`, `javascript`, `visibility_id`) VALUES (1, 1, 'contribution_amount', 'Contribution Amount', 'Text', 0, NULL, NULL, 1, 1, 1, 1, 1, NULL, NULL, NULL, 1)");
    CRM_Core_DAO::executeQuery("INSERT INTO `civicrm_price_field_value` (`id`, `price_field_id`, `name`, `label`, `description`, `amount`, `count`, `max_value`, `weight`, `membership_type_id`, `membership_num_terms`, `is_default`, `is_active`, `financial_type_id`, `non_deductible_amount`) VALUES (1, 1, 'contribution_amount', 'Contribution Amount', NULL, '1', NULL, NULL, 1, NULL, NULL, 0, 1, 1, 0.00)");
  }
  /*
   * Function does a 'Get' on the entity & compares the fields in the Params with those returned
   * Default behaviour is to also delete the entity
   * @param array $params
   *   Params array to check against.
   * @param int $id
   *   Id of the entity concerned.
   * @param string $entity
   *   Name of entity concerned (e.g. membership).
   * @param bool $delete
   *   Should the entity be deleted as part of this check.
   * @param string $errorText
   *   Text to print on error.
   */
  /**
   * @param array $params
   * @param int $id
   * @param $entity
   * @param int $delete
   * @param string $errorText
   *
   * @throws Exception
   */
  public function getAndCheck($params, $id, $entity, $delete = 1, $errorText = '') {

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
        if ($field != $settings['name']) {
          $dateFields[] = $field;
        }
      }
      if ($type == CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME) {
        $dateTimeFields[] = $settings['name'];
        // we should identify both real names & unique names as dates
        if ($field != $settings['name']) {
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
   * Get formatted values in  the actual and expected result.
   * @param array $actual
   *   Actual calculated values.
   * @param array $expected
   *   Expected values.
   */
  public function checkArrayEquals(&$actual, &$expected) {
    self::unsetId($actual);
    self::unsetId($expected);
    $this->assertEquals($actual, $expected);
  }

  /**
   * Unset the key 'id' from the array
   * @param array $unformattedArray
   *   The array from which the 'id' has to be unset.
   */
  public static function unsetId(&$unformattedArray) {
    $formattedArray = array();
    if (array_key_exists('id', $unformattedArray)) {
      unset($unformattedArray['id']);
    }
    if (!empty($unformattedArray['values']) && is_array($unformattedArray['values'])) {
      foreach ($unformattedArray['values'] as $key => $value) {
        if (is_array($value)) {
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
   * @param array $customDirs
   *   With members:.
   *   'php_path' Set to TRUE to use the default, FALSE or "" to disable support, or a string path to use another path
   *   'template_path' Set to TRUE to use the default, FALSE or "" to disable support, or a string path to use another path
   */
  public function customDirectories($customDirs) {
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
   * Generate a temporary folder.
   *
   * @param string $prefix
   * @return string
   */
  public function createTempDir($prefix = 'test-') {
    $tempDir = CRM_Utils_File::tempdir($prefix);
    $this->tempDirs[] = $tempDir;
    return $tempDir;
  }

  public function cleanTempDirs() {
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
   * Temporarily replace the singleton extension with a different one.
   * @param \CRM_Extension_System $system
   */
  public function setExtensionSystem(CRM_Extension_System $system) {
    if ($this->origExtensionSystem == NULL) {
      $this->origExtensionSystem = CRM_Extension_System::singleton();
    }
    CRM_Extension_System::setSingleton($this->origExtensionSystem);
  }

  public function unsetExtensionSystem() {
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
  public function setMockSettingsMetaData($extras) {
    Civi::service('settings_manager')->flush();

    CRM_Utils_Hook::singleton()
      ->setHook('civicrm_alterSettingsMetaData', function (&$metadata, $domainId, $profile) use ($extras) {
        $metadata = array_merge($metadata, $extras);
      });

    $fields = $this->callAPISuccess('setting', 'getfields', array());
    foreach ($extras as $key => $spec) {
      $this->assertNotEmpty($spec['title']);
      $this->assertEquals($spec['title'], $fields['values'][$key]['title']);
    }
  }

  /**
   * @param string $name
   */
  public function financialAccountDelete($name) {
    $financialAccount = new CRM_Financial_DAO_FinancialAccount();
    $financialAccount->name = $name;
    if ($financialAccount->find(TRUE)) {
      $entityFinancialType = new CRM_Financial_DAO_EntityFinancialAccount();
      $entityFinancialType->financial_account_id = $financialAccount->id;
      $entityFinancialType->delete();
      $financialAccount->delete();
    }
  }

  /**
   * FIXME: something NULLs $GLOBALS['_HTML_QuickForm_registered_rules'] when the tests are ran all together
   * (NB unclear if this is still required)
   */
  public function _sethtmlGlobals() {
    $GLOBALS['_HTML_QuickForm_registered_rules'] = array(
      'required' => array(
        'html_quickform_rule_required',
        'HTML/QuickForm/Rule/Required.php',
      ),
      'maxlength' => array(
        'html_quickform_rule_range',
        'HTML/QuickForm/Rule/Range.php',
      ),
      'minlength' => array(
        'html_quickform_rule_range',
        'HTML/QuickForm/Rule/Range.php',
      ),
      'rangelength' => array(
        'html_quickform_rule_range',
        'HTML/QuickForm/Rule/Range.php',
      ),
      'email' => array(
        'html_quickform_rule_email',
        'HTML/QuickForm/Rule/Email.php',
      ),
      'regex' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php',
      ),
      'lettersonly' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php',
      ),
      'alphanumeric' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php',
      ),
      'numeric' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php',
      ),
      'nopunctuation' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php',
      ),
      'nonzero' => array(
        'html_quickform_rule_regex',
        'HTML/QuickForm/Rule/Regex.php',
      ),
      'callback' => array(
        'html_quickform_rule_callback',
        'HTML/QuickForm/Rule/Callback.php',
      ),
      'compare' => array(
        'html_quickform_rule_compare',
        'HTML/QuickForm/Rule/Compare.php',
      ),
    );
    // FIXME: …ditto for $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']
    $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES'] = array(
      'group' => array(
        'HTML/QuickForm/group.php',
        'HTML_QuickForm_group',
      ),
      'hidden' => array(
        'HTML/QuickForm/hidden.php',
        'HTML_QuickForm_hidden',
      ),
      'reset' => array(
        'HTML/QuickForm/reset.php',
        'HTML_QuickForm_reset',
      ),
      'checkbox' => array(
        'HTML/QuickForm/checkbox.php',
        'HTML_QuickForm_checkbox',
      ),
      'file' => array(
        'HTML/QuickForm/file.php',
        'HTML_QuickForm_file',
      ),
      'image' => array(
        'HTML/QuickForm/image.php',
        'HTML_QuickForm_image',
      ),
      'password' => array(
        'HTML/QuickForm/password.php',
        'HTML_QuickForm_password',
      ),
      'radio' => array(
        'HTML/QuickForm/radio.php',
        'HTML_QuickForm_radio',
      ),
      'button' => array(
        'HTML/QuickForm/button.php',
        'HTML_QuickForm_button',
      ),
      'submit' => array(
        'HTML/QuickForm/submit.php',
        'HTML_QuickForm_submit',
      ),
      'select' => array(
        'HTML/QuickForm/select.php',
        'HTML_QuickForm_select',
      ),
      'hiddenselect' => array(
        'HTML/QuickForm/hiddenselect.php',
        'HTML_QuickForm_hiddenselect',
      ),
      'text' => array(
        'HTML/QuickForm/text.php',
        'HTML_QuickForm_text',
      ),
      'textarea' => array(
        'HTML/QuickForm/textarea.php',
        'HTML_QuickForm_textarea',
      ),
      'fckeditor' => array(
        'HTML/QuickForm/fckeditor.php',
        'HTML_QuickForm_FCKEditor',
      ),
      'tinymce' => array(
        'HTML/QuickForm/tinymce.php',
        'HTML_QuickForm_TinyMCE',
      ),
      'dojoeditor' => array(
        'HTML/QuickForm/dojoeditor.php',
        'HTML_QuickForm_dojoeditor',
      ),
      'link' => array(
        'HTML/QuickForm/link.php',
        'HTML_QuickForm_link',
      ),
      'advcheckbox' => array(
        'HTML/QuickForm/advcheckbox.php',
        'HTML_QuickForm_advcheckbox',
      ),
      'date' => array(
        'HTML/QuickForm/date.php',
        'HTML_QuickForm_date',
      ),
      'static' => array(
        'HTML/QuickForm/static.php',
        'HTML_QuickForm_static',
      ),
      'header' => array(
        'HTML/QuickForm/header.php',
        'HTML_QuickForm_header',
      ),
      'html' => array(
        'HTML/QuickForm/html.php',
        'HTML_QuickForm_html',
      ),
      'hierselect' => array(
        'HTML/QuickForm/hierselect.php',
        'HTML_QuickForm_hierselect',
      ),
      'autocomplete' => array(
        'HTML/QuickForm/autocomplete.php',
        'HTML_QuickForm_autocomplete',
      ),
      'xbutton' => array(
        'HTML/QuickForm/xbutton.php',
        'HTML_QuickForm_xbutton',
      ),
      'advmultiselect' => array(
        'HTML/QuickForm/advmultiselect.php',
        'HTML_QuickForm_advmultiselect',
      ),
    );
  }

  /**
   * Set up an acl allowing contact to see 2 specified groups
   *  - $this->_permissionedGroup & $this->_permissionedDisabledGroup
   *
   *  You need to have pre-created these groups & created the user e.g
   *  $this->createLoggedInUser();
   *   $this->_permissionedDisabledGroup = $this->groupCreate(array('title' => 'pick-me-disabled', 'is_active' => 0, 'name' => 'pick-me-disabled'));
   *   $this->_permissionedGroup = $this->groupCreate(array('title' => 'pick-me-active', 'is_active' => 1, 'name' => 'pick-me-active'));
   *
   * @param bool $isProfile
   */
  public function setupACL($isProfile = FALSE) {
    global $_REQUEST;
    $_REQUEST = $this->_params;

    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    $optionGroupID = $this->callAPISuccessGetValue('option_group', array('return' => 'id', 'name' => 'acl_role'));
    $ov = new CRM_Core_DAO_OptionValue();
    $ov->option_group_id = $optionGroupID;
    $ov->value = 55;
    if ($ov->find(TRUE)) {
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_option_value WHERE id = {$ov->id}");
    }
    $optionValue = $this->callAPISuccess('option_value', 'create', array(
      'option_group_id' => $optionGroupID,
      'label' => 'pick me',
      'value' => 55,
    ));

    CRM_Core_DAO::executeQuery("
      TRUNCATE civicrm_acl_cache
    ");

    CRM_Core_DAO::executeQuery("
      TRUNCATE civicrm_acl_contact_cache
    ");

    CRM_Core_DAO::executeQuery("
    INSERT INTO civicrm_acl_entity_role (
    `acl_role_id`, `entity_table`, `entity_id`, `is_active`
    ) VALUES (55, 'civicrm_group', {$this->_permissionedGroup}, 1);
    ");

    if ($isProfile) {
      CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_acl (
      `name`, `entity_table`, `entity_id`, `operation`, `object_table`, `object_id`, `is_active`
      )
      VALUES (
      'view picked', 'civicrm_acl_role', 55, 'Edit', 'civicrm_uf_group', 0, 1
      );
      ");
    }
    else {
      CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_acl (
      `name`, `entity_table`, `entity_id`, `operation`, `object_table`, `object_id`, `is_active`
      )
      VALUES (
      'view picked', 'civicrm_group', $this->_permissionedGroup , 'Edit', 'civicrm_saved_search', {$this->_permissionedGroup}, 1
      );
      ");

      CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_acl (
      `name`, `entity_table`, `entity_id`, `operation`, `object_table`, `object_id`, `is_active`
      )
      VALUES (
      'view picked', 'civicrm_group',  $this->_permissionedGroup, 'Edit', 'civicrm_saved_search', {$this->_permissionedDisabledGroup}, 1
      );
      ");
    }

    $this->_loggedInUser = CRM_Core_Session::singleton()->get('userID');
    $this->callAPISuccess('group_contact', 'create', array(
      'group_id' => $this->_permissionedGroup,
      'contact_id' => $this->_loggedInUser,
    ));

    if (!$isProfile) {
      //flush cache
      CRM_ACL_BAO_Cache::resetCache();
      CRM_ACL_API::groupPermission('whatever', 9999, NULL, 'civicrm_saved_search', NULL, NULL);
    }
  }

  /**
   * Alter default price set so that the field numbers are not all 1 (hiding errors)
   */
  public function offsetDefaultPriceSet() {
    $contributionPriceSet = $this->callAPISuccess('price_set', 'getsingle', array('name' => 'default_contribution_amount'));
    $firstID = $contributionPriceSet['id'];
    $this->callAPISuccess('price_set', 'create', array(
      'id' => $contributionPriceSet['id'],
      'is_active' => 0,
      'name' => 'old',
    ));
    unset($contributionPriceSet['id']);
    $newPriceSet = $this->callAPISuccess('price_set', 'create', $contributionPriceSet);
    $priceField = $this->callAPISuccess('price_field', 'getsingle', array(
      'price_set_id' => $firstID,
      'options' => array('limit' => 1),
    ));
    unset($priceField['id']);
    $priceField['price_set_id'] = $newPriceSet['id'];
    $newPriceField = $this->callAPISuccess('price_field', 'create', $priceField);
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'getsingle', array(
      'price_set_id' => $firstID,
      'sequential' => 1,
      'options' => array('limit' => 1),
    ));

    unset($priceFieldValue['id']);
    //create some padding to use up ids
    $this->callAPISuccess('price_field_value', 'create', $priceFieldValue);
    $this->callAPISuccess('price_field_value', 'create', $priceFieldValue);
    $this->callAPISuccess('price_field_value', 'create', array_merge($priceFieldValue, array('price_field_id' => $newPriceField['id'])));
  }

  /**
   * Create an instance of the paypal processor.
   * @todo this isn't a great place to put it - but really it belongs on a class that extends
   * this parent class & we don't have a structure for that yet
   * There is another function to this effect on the PaypalPro test but it appears to be silently failing
   * & the best protection against that is the functions this class affords
   * @param array $params
   * @return int $result['id'] payment processor id
   */
  public function paymentProcessorCreate($params = array()) {
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
        'financial_account_id' => 12,
         // Credit card = 1 so can pass 'by accident'.
        'payment_instrument_id' => 'Debit Card',
      ),
      $params);
    if (!is_numeric($params['payment_processor_type_id'])) {
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

  /**
   * Set up initial recurring payment allowing subsequent IPN payments.
   *
   * @param array $recurParams (Optional)
   * @param array $contributionParams (Optional)
   */
  public function setupRecurringPaymentProcessorTransaction($recurParams = [], $contributionParams = []) {
    $contributionParams = array_merge([
        'total_amount' => '200',
        'invoice_id' => $this->_invoiceID,
        'financial_type_id' => 'Donation',
        'contribution_status_id' => 'Pending',
        'contact_id' => $this->_contactID,
        'contribution_page_id' => $this->_contributionPageID,
        'payment_processor_id' => $this->_paymentProcessorID,
        'is_test' => 0,
        'skipCleanMoney' => TRUE,
      ],
      $contributionParams
    );
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array_merge(array(
      'contact_id' => $this->_contactID,
      'amount' => 1000,
      'sequential' => 1,
      'installments' => 5,
      'frequency_unit' => 'Month',
      'frequency_interval' => 1,
      'invoice_id' => $this->_invoiceID,
      'contribution_status_id' => 2,
      'payment_processor_id' => $this->_paymentProcessorID,
      // processor provided ID - use contact ID as proxy.
      'processor_id' => $this->_contactID,
      'api.contribution.create' => $contributionParams,
    ), $recurParams));
    $this->_contributionRecurID = $contributionRecur['id'];
    $this->_contributionID = $contributionRecur['values']['0']['api.contribution.create']['id'];
  }

  /**
   * We don't have a good way to set up a recurring contribution with a membership so let's just do one then alter it
   *
   * @param array $params Optionally modify params for membership/recur (duration_unit/frequency_unit)
   */
  public function setupMembershipRecurringPaymentProcessorTransaction($params = array()) {
    $membershipParams = $recurParams = array();
    if (!empty($params['duration_unit'])) {
      $membershipParams['duration_unit'] = $params['duration_unit'];
    }
    if (!empty($params['frequency_unit'])) {
      $recurParams['frequency_unit'] = $params['frequency_unit'];
    }

    $this->ids['membership_type'] = $this->membershipTypeCreate($membershipParams);
    //create a contribution so our membership & contribution don't both have id = 1
    if ($this->callAPISuccess('Contribution', 'getcount', array()) == 0) {
      $this->contributionCreate(array(
        'contact_id' => $this->_contactID,
        'is_test' => 1,
        'financial_type_id' => 1,
        'invoice_id' => 'abcd',
        'trxn_id' => 345,
      ));
    }
    $this->setupRecurringPaymentProcessorTransaction($recurParams);

    $this->ids['membership'] = $this->callAPISuccess('membership', 'create', array(
      'contact_id' => $this->_contactID,
      'membership_type_id' => $this->ids['membership_type'],
      'contribution_recur_id' => $this->_contributionRecurID,
      'format.only_id' => TRUE,
    ));
    //CRM-15055 creates line items we don't want so get rid of them so we can set up our own line items
    CRM_Core_DAO::executeQuery("TRUNCATE civicrm_line_item");

    $this->callAPISuccess('line_item', 'create', array(
      'entity_table' => 'civicrm_membership',
      'entity_id' => $this->ids['membership'],
      'contribution_id' => $this->_contributionID,
      'label' => 'General',
      'qty' => 1,
      'unit_price' => 200,
      'line_total' => 200,
      'financial_type_id' => 1,
      'price_field_id' => $this->callAPISuccess('price_field', 'getvalue', array(
        'return' => 'id',
        'label' => 'Membership Amount',
        'options' => array('limit' => 1, 'sort' => 'id DESC'),
      )),
      'price_field_value_id' => $this->callAPISuccess('price_field_value', 'getvalue', array(
        'return' => 'id',
        'label' => 'General',
        'options' => array('limit' => 1, 'sort' => 'id DESC'),
      )),
    ));
    $this->callAPISuccess('membership_payment', 'create', array(
      'contribution_id' => $this->_contributionID,
      'membership_id' => $this->ids['membership'],
    ));
  }

  /**
   * @param $message
   *
   * @throws Exception
   */
  public function CiviUnitTestCase_fatalErrorHandler($message) {
    throw new Exception("{$message['message']}: {$message['code']}");
  }

  /**
   * Helper function to create new mailing.
   *
   * @param array $params
   *
   * @return int
   */
  public function createMailing($params = array()) {
    $params = array_merge(array(
      'subject' => 'maild' . rand(),
      'body_text' => 'bdkfhdskfhduew{domain.address}{action.optOutUrl}',
      'name' => 'mailing name' . rand(),
      'created_id' => 1,
    ), $params);

    $result = $this->callAPISuccess('Mailing', 'create', $params);
    return $result['id'];
  }

  /**
   * Helper function to delete mailing.
   * @param $id
   */
  public function deleteMailing($id) {
    $params = array(
      'id' => $id,
    );

    $this->callAPISuccess('Mailing', 'delete', $params);
  }

  /**
   * Wrap the entire test case in a transaction.
   *
   * Only subsequent DB statements will be wrapped in TX -- this cannot
   * retroactively wrap old DB statements. Therefore, it makes sense to
   * call this at the beginning of setUp().
   *
   * Note: Recall that TRUNCATE and ALTER will force-commit transactions, so
   * this option does not work with, e.g., custom-data.
   *
   * WISHLIST: Monitor SQL queries in unit-tests and generate an exception
   * if TRUNCATE or ALTER is called while using a transaction.
   *
   * @param bool $nest
   *   Whether to use nesting or reference-counting.
   */
  public function useTransaction($nest = TRUE) {
    if (!$this->tx) {
      $this->tx = new CRM_Core_Transaction($nest);
      $this->tx->rollback();
    }
  }

  /**
   * Assert the attachment exists.
   *
   * @param bool $exists
   * @param array $apiResult
   */
  protected function assertAttachmentExistence($exists, $apiResult) {
    $fileId = $apiResult['id'];
    $this->assertTrue(is_numeric($fileId));
    $this->assertEquals($exists, file_exists($apiResult['values'][$fileId]['path']));
    $this->assertDBQuery($exists ? 1 : 0, 'SELECT count(*) FROM civicrm_file WHERE id = %1', array(
      1 => array($fileId, 'Int'),
    ));
    $this->assertDBQuery($exists ? 1 : 0, 'SELECT count(*) FROM civicrm_entity_file WHERE id = %1', array(
      1 => array($fileId, 'Int'),
    ));
  }

  /**
   * Create a price set for an event.
   *
   * @param int $feeTotal
   * @param int $minAmt
   * @param string $type
   *
   * @return int
   *   Price Set ID.
   */
  protected function eventPriceSetCreate($feeTotal, $minAmt = 0, $type = 'Text') {
    // creating price set, price field
    $paramsSet['title'] = 'Price Set';
    $paramsSet['name'] = CRM_Utils_String::titleToVar('Price Set');
    $paramsSet['is_active'] = FALSE;
    $paramsSet['extends'] = 1;
    $paramsSet['min_amount'] = $minAmt;

    $priceSet = CRM_Price_BAO_PriceSet::create($paramsSet);
    $this->_ids['price_set'] = $priceSet->id;

    $paramsField = array(
      'label' => 'Price Field',
      'name' => CRM_Utils_String::titleToVar('Price Field'),
      'html_type' => $type,
      'price' => $feeTotal,
      'option_label' => array('1' => 'Price Field'),
      'option_value' => array('1' => $feeTotal),
      'option_name' => array('1' => $feeTotal),
      'option_weight' => array('1' => 1),
      'option_amount' => array('1' => 1),
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_active' => array('1' => 1),
      'price_set_id' => $this->_ids['price_set'],
      'is_enter_qty' => 1,
      'financial_type_id' => $this->getFinancialTypeId('Event Fee'),
    );
    if ($type === 'Radio') {
      $paramsField['is_enter_qty'] = 0;
      $paramsField['option_value'][2] = $paramsField['option_weight'][2] = $paramsField['option_amount'][2] = 100;
      $paramsField['option_label'][2] = $paramsField['option_name'][2] = 'hundy';
    }
    CRM_Price_BAO_PriceField::create($paramsField);
    $fields = $this->callAPISuccess('PriceField', 'get', array('price_set_id' => $this->_ids['price_set']));
    $this->_ids['price_field'] = array_keys($fields['values']);
    $fieldValues = $this->callAPISuccess('PriceFieldValue', 'get', array('price_field_id' => $this->_ids['price_field'][0]));
    $this->_ids['price_field_value'] = array_keys($fieldValues['values']);

    return $this->_ids['price_set'];
  }

  /**
   * Add a profile to a contribution page.
   *
   * @param string $name
   * @param int $contributionPageID
   * @param string $module
   */
  protected function addProfile($name, $contributionPageID, $module = 'CiviContribute') {
    $params = [
      'uf_group_id' => $name,
      'module' => $module,
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $contributionPageID,
      'weight' => 1,
    ];
    if ($module !== 'CiviContribute') {
      $params['module_data'] = [$module => []];
    }
    $this->callAPISuccess('UFJoin', 'create', $params);
  }

  /**
   * Add participant with contribution
   *
   * @return array
   */
  protected function createParticipantWithContribution() {
    // creating price set, price field
    $this->_contactId = $this->individualCreate();
    $event = $this->eventCreate();
    $this->_eventId = $event['id'];
    $eventParams = array(
      'id' => $this->_eventId,
      'financial_type_id' => 4,
      'is_monetary' => 1,
    );
    $this->callAPISuccess('event', 'create', $eventParams);
    $priceFields = $this->createPriceSet('event', $this->_eventId);
    $participantParams = array(
      'financial_type_id' => 4,
      'event_id' => $this->_eventId,
      'role_id' => 1,
      'status_id' => 14,
      'fee_currency' => 'USD',
      'contact_id' => $this->_contactId,
    );
    $participant = $this->callAPISuccess('Participant', 'create', $participantParams);
    $contributionParams = array(
      'total_amount' => 150,
      'currency' => 'USD',
      'contact_id' => $this->_contactId,
      'financial_type_id' => 4,
      'contribution_status_id' => 1,
      'partial_payment_total' => 300.00,
      'partial_amount_to_pay' => 150,
      'contribution_mode' => 'participant',
      'participant_id' => $participant['id'],
    );
    foreach ($priceFields['values'] as $key => $priceField) {
      $lineItems[1][$key] = array(
        'price_field_id' => $priceField['price_field_id'],
        'price_field_value_id' => $priceField['id'],
        'label' => $priceField['label'],
        'field_title' => $priceField['label'],
        'qty' => 1,
        'unit_price' => $priceField['amount'],
        'line_total' => $priceField['amount'],
        'financial_type_id' => $priceField['financial_type_id'],
      );
    }
    $contributionParams['line_item'] = $lineItems;
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $paymentParticipant = array(
      'participant_id' => $participant['id'],
      'contribution_id' => $contribution['id'],
    );
    $this->callAPISuccess('ParticipantPayment', 'create', $paymentParticipant);
    return array($lineItems, $contribution);
  }

  /**
   * Create price set
   *
   * @param string $component
   * @param int $componentId
   *
   * @return array
   */
  protected function createPriceSet($component = 'contribution_page', $componentId = NULL, $priceFieldOptions = array()) {
    $paramsSet['title'] = 'Price Set' . substr(sha1(rand()), 0, 7);
    $paramsSet['name'] = CRM_Utils_String::titleToVar($paramsSet['title']);
    $paramsSet['is_active'] = TRUE;
    $paramsSet['financial_type_id'] = 'Event Fee';
    $paramsSet['extends'] = 1;
    $priceSet = $this->callAPISuccess('price_set', 'create', $paramsSet);
    $priceSetId = $priceSet['id'];
    //Checking for priceset added in the table.
    $this->assertDBCompareValue('CRM_Price_BAO_PriceSet', $priceSetId, 'title',
      'id', $paramsSet['title'], 'Check DB for created priceset'
    );
    $paramsField = array_merge(array(
      'label' => 'Price Field',
      'name' => CRM_Utils_String::titleToVar('Price Field'),
      'html_type' => 'CheckBox',
      'option_label' => array('1' => 'Price Field 1', '2' => 'Price Field 2'),
      'option_value' => array('1' => 100, '2' => 200),
      'option_name' => array('1' => 'Price Field 1', '2' => 'Price Field 2'),
      'option_weight' => array('1' => 1, '2' => 2),
      'option_amount' => array('1' => 100, '2' => 200),
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_active' => array('1' => 1, '2' => 1),
      'price_set_id' => $priceSet['id'],
      'is_enter_qty' => 1,
      'financial_type_id' => $this->getFinancialTypeId('Event Fee'),
    ), $priceFieldOptions);

    $priceField = CRM_Price_BAO_PriceField::create($paramsField);
    if ($componentId) {
      CRM_Price_BAO_PriceSet::addTo('civicrm_' . $component, $componentId, $priceSetId);
    }
    return $this->callAPISuccess('PriceFieldValue', 'get', array('price_field_id' => $priceField->id));
  }

  /**
   * Replace the template with a test-oriented template designed to show all the variables.
   *
   * @param string $templateName
   */
  protected function swapMessageTemplateForTestTemplate($templateName = 'contribution_online_receipt') {
    $testTemplate = file_get_contents(__DIR__ . '/../../templates/message_templates/' . $templateName . '_html.tpl');
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_option_group og
      LEFT JOIN civicrm_option_value ov ON ov.option_group_id = og.id
      LEFT JOIN civicrm_msg_template m ON m.workflow_id = ov.id
      SET m.msg_html = '{$testTemplate}'
      WHERE og.name = 'msg_tpl_workflow_contribution'
      AND ov.name = '{$templateName}'
      AND m.is_default = 1"
    );
  }

  /**
   * Reinstate the default template.
   *
   * @param string $templateName
   */
  protected function revertTemplateToReservedTemplate($templateName = 'contribution_online_receipt') {
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_option_group og
      LEFT JOIN civicrm_option_value ov ON ov.option_group_id = og.id
      LEFT JOIN civicrm_msg_template m ON m.workflow_id = ov.id
      LEFT JOIN civicrm_msg_template m2 ON m2.workflow_id = ov.id AND m2.is_reserved = 1
      SET m.msg_html = m2.msg_html
      WHERE og.name = 'msg_tpl_workflow_contribution'
      AND ov.name = '{$templateName}'
      AND m.is_default = 1"
    );
  }

  /**
   * Flush statics relating to financial type.
   */
  protected function flushFinancialTypeStatics() {
    if (isset(\Civi::$statics['CRM_Financial_BAO_FinancialType'])) {
      unset(\Civi::$statics['CRM_Financial_BAO_FinancialType']);
    }
    if (isset(\Civi::$statics['CRM_Contribute_PseudoConstant'])) {
      unset(\Civi::$statics['CRM_Contribute_PseudoConstant']);
    }
    CRM_Contribute_PseudoConstant::flush('financialType');
    CRM_Contribute_PseudoConstant::flush('membershipType');
    // Pseudoconstants may be saved to the cache table.
    CRM_Core_DAO::executeQuery("TRUNCATE civicrm_cache");
    CRM_Financial_BAO_FinancialType::$_statusACLFt = array();
    CRM_Financial_BAO_FinancialType::$_availableFinancialTypes = NULL;
  }

  /**
   * Set the permissions to the supplied array.
   *
   * @param array $permissions
   */
  protected function setPermissions($permissions) {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = $permissions;
    $this->flushFinancialTypeStatics();
  }

  /**
   * @param array $params
   * @param $context
   */
  public function _checkFinancialRecords($params, $context) {
    $entityParams = array(
      'entity_id' => $params['id'],
      'entity_table' => 'civicrm_contribution',
    );
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('id' => $params['id']));
    $this->assertEquals($contribution['total_amount'] - $contribution['fee_amount'], $contribution['net_amount']);
    if ($context == 'pending') {
      $trxn = CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams);
      $this->assertNull($trxn, 'No Trxn to be created until IPN callback');
      return;
    }
    $trxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
    $trxnParams = array(
      'id' => $trxn['financial_trxn_id'],
    );
    if ($context != 'online' && $context != 'payLater') {
      $compareParams = array(
        'to_financial_account_id' => 6,
        'total_amount' => CRM_Utils_Array::value('total_amount', $params, 100),
        'status_id' => 1,
      );
    }
    if ($context == 'feeAmount') {
      $compareParams['fee_amount'] = 50;
    }
    elseif ($context == 'online') {
      $compareParams = array(
        'to_financial_account_id' => 12,
        'total_amount' => CRM_Utils_Array::value('total_amount', $params, 100),
        'status_id' => 1,
        'payment_instrument_id' => CRM_Utils_Array::value('payment_instrument_id', $params, 1),
      );
    }
    elseif ($context == 'payLater') {
      $compareParams = array(
        'to_financial_account_id' => 7,
        'total_amount' => CRM_Utils_Array::value('total_amount', $params, 100),
        'status_id' => 2,
      );
    }
    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $trxnParams, $compareParams);
    $entityParams = array(
      'financial_trxn_id' => $trxn['financial_trxn_id'],
      'entity_table' => 'civicrm_financial_item',
    );
    $entityTrxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
    $fitemParams = array(
      'id' => $entityTrxn['entity_id'],
    );
    $compareParams = array(
      'amount' => CRM_Utils_Array::value('total_amount', $params, 100),
      'status_id' => 1,
      'financial_account_id' => CRM_Utils_Array::value('financial_account_id', $params, 1),
    );
    if ($context == 'payLater') {
      $compareParams = array(
        'amount' => CRM_Utils_Array::value('total_amount', $params, 100),
        'status_id' => 3,
        'financial_account_id' => CRM_Utils_Array::value('financial_account_id', $params, 1),
      );
    }
    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $fitemParams, $compareParams);
    if ($context == 'feeAmount') {
      $maxParams = array(
        'entity_id' => $params['id'],
        'entity_table' => 'civicrm_contribution',
      );
      $maxTrxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($maxParams, TRUE));
      $trxnParams = array(
        'id' => $maxTrxn['financial_trxn_id'],
      );
      $compareParams = array(
        'to_financial_account_id' => 5,
        'from_financial_account_id' => 6,
        'total_amount' => 50,
        'status_id' => 1,
      );
      $trxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($params['id'], 'DESC');
      $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $trxnParams, $compareParams);
      $fitemParams = array(
        'entity_id' => $trxnId['financialTrxnId'],
        'entity_table' => 'civicrm_financial_trxn',
      );
      $compareParams = array(
        'amount' => 50,
        'status_id' => 1,
        'financial_account_id' => 5,
      );
      $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $fitemParams, $compareParams);
    }
    // This checks that empty Sales tax rows are not being created. If for any reason it needs to be removed the
    // line should be copied into all the functions that call this function & evaluated there
    // Be really careful not to remove or bypass this without ensuring stray rows do not re-appear
    // when calling completeTransaction or repeatTransaction.
    $this->callAPISuccessGetCount('FinancialItem', array('description' => 'Sales Tax', 'amount' => 0), 0);
  }

  /**
   * Return financial type id on basis of name
   *
   * @param string $name Financial type m/c name
   *
   * @return int
   */
  public function getFinancialTypeId($name) {
    return CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $name, 'id', 'name');
  }

  /**
   * Cleanup function for contents of $this->ids.
   *
   * This is a best effort cleanup to use in tear downs etc.
   *
   * It will not fail if the data has already been removed (some tests may do
   * their own cleanup).
   */
  protected function cleanUpSetUpIDs() {
    foreach ($this->setupIDs as $entity => $id) {
      try {
        civicrm_api3($entity, 'delete', array('id' => $id, 'skip_undelete' => 1));
      }
      catch (CiviCRM_API3_Exception $e) {
        // This is a best-effort cleanup function, ignore.
      }
    }
  }

  /**
   * Create Financial Type.
   *
   * @param array $params
   *
   * @return array
   */
  protected function createFinancialType($params = array()) {
    $params = array_merge($params,
      array(
        'name' => 'Financial-Type -' . substr(sha1(rand()), 0, 7),
        'is_active' => 1,
      )
    );
    return $this->callAPISuccess('FinancialType', 'create', $params);
  }

  /**
   * Create Payment Instrument.
   *
   * @param array $params
   * @param string $financialAccountName
   *
   * @return int
   */
  protected function createPaymentInstrument($params = array(), $financialAccountName = 'Donation') {
    $params = array_merge(array(
      'label' => 'Payment Instrument -' . substr(sha1(rand()), 0, 7),
      'option_group_id' => 'payment_instrument',
      'is_active' => 1,
      ),
      $params
    );
    $newPaymentInstrument = $this->callAPISuccess('OptionValue', 'create', $params)['id'];

    $relationTypeID = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Asset Account is' "));

    $financialAccountParams = [
      'entity_table' => 'civicrm_option_value',
      'entity_id' => $newPaymentInstrument,
      'account_relationship' => $relationTypeID,
      'financial_account_id' => $this->callAPISuccess('FinancialAccount', 'getValue', ['name' => $financialAccountName, 'return' => 'id']),
    ];
    CRM_Financial_BAO_FinancialTypeAccount::add($financialAccountParams);

    return CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', $params['label']);
  }

  /**
   * Enable Tax and Invoicing
   */
  protected function enableTaxAndInvoicing($params = array()) {
    // Enable component contribute setting
    $contributeSetting = array_merge($params,
      array(
        'invoicing' => 1,
        'invoice_prefix' => 'INV_',
        'credit_notes_prefix' => 'CN_',
        'due_date' => 10,
        'due_date_period' => 'days',
        'notes' => '',
        'is_email_pdf' => 1,
        'tax_term' => 'Sales Tax',
        'tax_display_settings' => 'Inclusive',
      )
    );
    return Civi::settings()->set('contribution_invoice_settings', $contributeSetting);
  }

  /**
   * Enable Tax and Invoicing
   */
  protected function disableTaxAndInvoicing($params = array()) {
    if (!empty(\Civi::$statics['CRM_Core_PseudoConstant']) && isset(\Civi::$statics['CRM_Core_PseudoConstant']['taxRates'])) {
      unset(\Civi::$statics['CRM_Core_PseudoConstant']['taxRates']);
    }
    // Enable component contribute setting
    $contributeSetting = array_merge($params,
      array(
        'invoicing' => 0,
      )
    );
    return Civi::settings()->set('contribution_invoice_settings', $contributeSetting);
  }

  /**
   * Add Sales Tax relation for financial type with financial account.
   *
   * @param int $financialTypeId
   *
   * @return obj
   */
  protected function relationForFinancialTypeWithFinancialAccount($financialTypeId) {
    $params = array(
      'name' => 'Sales tax account ' . substr(sha1(rand()), 0, 4),
      'financial_account_type_id' => key(CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name LIKE 'Liability' ")),
      'is_deductible' => 1,
      'is_tax' => 1,
      'tax_rate' => 10,
      'is_active' => 1,
    );
    $account = CRM_Financial_BAO_FinancialAccount::add($params);
    $entityParams = array(
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialTypeId,
      'account_relationship' => key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Sales Tax Account is' ")),
    );

    // set tax rate (as 10) for provided financial type ID to static variable, later used to fetch tax rates of all financial types
    \Civi::$statics['CRM_Core_PseudoConstant']['taxRates'][$financialTypeId] = 10;

    //CRM-20313: As per unique index added in civicrm_entity_financial_account table,
    //  first check if there's any record on basis of unique key (entity_table, account_relationship, entity_id)
    $dao = new CRM_Financial_DAO_EntityFinancialAccount();
    $dao->copyValues($entityParams);
    $dao->find();
    if ($dao->fetch()) {
      $entityParams['id'] = $dao->id;
    }
    $entityParams['financial_account_id'] = $account->id;

    return CRM_Financial_BAO_FinancialTypeAccount::add($entityParams);
  }

  /**
   * Create price set with contribution test for test setup.
   *
   * This could be merged with 4.5 function setup in api_v3_ContributionPageTest::setUpContributionPage
   * on parent class at some point (fn is not in 4.4).
   *
   * @param $entity
   * @param array $params
   */
  public function createPriceSetWithPage($entity = NULL, $params = array()) {
    $membershipTypeID = $this->membershipTypeCreate(array('name' => 'Special'));
    $contributionPageResult = $this->callAPISuccess('contribution_page', 'create', array(
      'title' => "Test Contribution Page",
      'financial_type_id' => 1,
      'currency' => 'NZD',
      'goal_amount' => 50,
      'is_pay_later' => 1,
      'is_monetary' => TRUE,
      'is_email_receipt' => FALSE,
    ));
    $priceSet = $this->callAPISuccess('price_set', 'create', array(
      'is_quick_config' => 0,
      'extends' => 'CiviMember',
      'financial_type_id' => 1,
      'title' => 'my Page',
    ));
    $priceSetID = $priceSet['id'];

    CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $contributionPageResult['id'], $priceSetID);
    $priceField = $this->callAPISuccess('price_field', 'create', array(
      'price_set_id' => $priceSetID,
      'label' => 'Goat Breed',
      'html_type' => 'Radio',
    ));
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
        'price_set_id' => $priceSetID,
        'price_field_id' => $priceField['id'],
        'label' => 'Long Haired Goat',
        'amount' => 20,
        'financial_type_id' => 'Donation',
        'membership_type_id' => $membershipTypeID,
        'membership_num_terms' => 1,
      )
    );
    $this->_ids['price_field_value'] = array($priceFieldValue['id']);
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
        'price_set_id' => $priceSetID,
        'price_field_id' => $priceField['id'],
        'label' => 'Shoe-eating Goat',
        'amount' => 10,
        'financial_type_id' => 'Donation',
        'membership_type_id' => $membershipTypeID,
        'membership_num_terms' => 2,
      )
    );
    $this->_ids['price_field_value'][] = $priceFieldValue['id'];

    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
        'price_set_id' => $priceSetID,
        'price_field_id' => $priceField['id'],
        'label' => 'Shoe-eating Goat',
        'amount' => 10,
        'financial_type_id' => 'Donation',
      )
    );
    $this->_ids['price_field_value']['cont'] = $priceFieldValue['id'];

    $this->_ids['price_set'] = $priceSetID;
    $this->_ids['contribution_page'] = $contributionPageResult['id'];
    $this->_ids['price_field'] = array($priceField['id']);

    $this->_ids['membership_type'] = $membershipTypeID;
  }

  /**
   * Only specified contact returned.
   * @implements CRM_Utils_Hook::aclWhereClause
   * @param $type
   * @param $tables
   * @param $whereTables
   * @param $contactID
   * @param $where
   */
  public function aclWhereMultipleContacts($type, &$tables, &$whereTables, &$contactID, &$where) {
    $where = " contact_a.id IN (" . implode(', ', $this->allowedContacts) . ")";
  }

  /**
   * @implements CRM_Utils_Hook::selectWhereClause
   *
   * @param string $entity
   * @param array $clauses
   */
  public function selectWhereClauseHook($entity, &$clauses) {
    if ($entity == 'Event') {
      $clauses['event_type_id'][] = "IN (2, 3, 4)";
    }
  }

  /**
   * An implementation of hook_civicrm_post used with all our test cases.
   *
   * @param $op
   * @param string $objectName
   * @param int $objectId
   * @param $objectRef
   */
  public function onPost($op, $objectName, $objectId, &$objectRef) {
    if ($op == 'create' && $objectName == 'Individual') {
      CRM_Core_DAO::executeQuery(
        "UPDATE civicrm_contact SET nick_name = 'munged' WHERE id = %1",
        array(
          1 => array($objectId, 'Integer'),
        )
      );
    }

    if ($op == 'edit' && $objectName == 'Participant') {
      $params = array(
        1 => array($objectId, 'Integer'),
      );
      $query = "UPDATE civicrm_participant SET source = 'Post Hook Update' WHERE id = %1";
      CRM_Core_DAO::executeQuery($query, $params);
    }
  }


  /**
   * Instantiate form object.
   *
   * We need to instantiate the form to run preprocess, which means we have to trick it about the request method.
   *
   * @param string $class
   *   Name of form class.
   *
   * @return \CRM_Core_Form
   */
  public function getFormObject($class) {
    $form = new $class();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $form->controller = new CRM_Core_Controller();
    return $form;
  }

  /**
   * Get possible thousand separators.
   *
   * @return array
   */
  public function getThousandSeparators() {
    return array(array('.'), array(','));
  }

  /**
   * Set the separators for thousands and decimal points.
   *
   * @param string $thousandSeparator
   */
  protected function setCurrencySeparators($thousandSeparator) {
    Civi::settings()->set('monetaryThousandSeparator', $thousandSeparator);
    Civi::settings()
      ->set('monetaryDecimalPoint', ($thousandSeparator === ',' ? '.' : ','));
  }

  /**
   * Format money as it would be input.
   *
   * @param string $amount
   *
   * @return string
   */
  protected function formatMoneyInput($amount) {
    return CRM_Utils_Money::format($amount, NULL, '%a');
  }


  /**
   * Get the contribution object.
   *
   * @param int $contributionID
   *
   * @return \CRM_Contribute_BAO_Contribution
   */
  protected function getContributionObject($contributionID) {
    $contributionObj = new CRM_Contribute_BAO_Contribution();
    $contributionObj->id = $contributionID;
    $contributionObj->find(TRUE);
    return $contributionObj;
  }

  /**
   * Enable multilingual.
   */
  public function enableMultilingual() {
    $this->callAPISuccess('Setting', 'create', array(
      'lcMessages' => 'en_US',
      'languageLimit' => array(
        'en_US' => 1,
      ),
    ));

    CRM_Core_I18n_Schema::makeMultilingual('en_US');

    global $dbLocale;
    $dbLocale = '_en_US';
  }

}
