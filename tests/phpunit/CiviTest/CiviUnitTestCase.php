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
declare(strict_types = 1);
use Civi\Api4\Address;
use Civi\Api4\CiviCase;
use Civi\Api4\Contribution;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\DedupeRuleGroup;
use Civi\Api4\DedupeRule;
use Civi\Api4\ExampleData;
use Civi\Api4\FinancialAccount;
use Civi\Api4\FinancialType;
use Civi\Api4\LineItem;
use Civi\Api4\MembershipType;
use Civi\Api4\OptionGroup;
use Civi\Api4\Phone;
use Civi\Api4\PriceFieldValue;
use Civi\Api4\PriceSet;
use Civi\Api4\RelationshipType;
use Civi\Api4\UFGroup;
use Civi\Core\Transaction\Manager;
use Civi\Payment\System;
use Civi\Api4\OptionValue;
use Civi\Test\Api3TestTrait;
use Civi\Test\ContactTestTrait;
use Civi\Test\DbTestTrait;
use Civi\Test\EventTestTrait;
use Civi\Test\FormTrait;
use Civi\Test\GenericAssertionsTrait;
use Civi\Test\LocaleTestTrait;
use Civi\Test\MailingTestTrait;
use League\Csv\Reader;

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
 *
 * @package CiviCRM
 */
class CiviUnitTestCase extends PHPUnit\Framework\TestCase {

  use Api3TestTrait;
  use EventTestTrait;
  use GenericAssertionsTrait;
  use DbTestTrait;
  use ContactTestTrait;
  use MailingTestTrait;
  use LocaleTestTrait;
  use FormTrait;

  /**
   * API version in use.
   *
   * @var int
   */
  protected $_apiversion = 3;

  /**
   * Track tables we have modified during a test.
   *
   * @var array
   */
  protected $_tablesToTruncate = [];

  /**
   * @var array
   * Array of temporary directory names
   */
  protected array $tempDirs = [];

  /**
   * @var CRM_Core_Transaction
   */
  private $tx;

  /**
   * @var array
   */
  protected $originalSettings = [];

  /**
   * Array of IDs created to support the test.
   *
   * e.g
   * $this->ids = ['Contact' => ['descriptive_key' => $contactID], 'Group' => [$groupID]];
   *
   * @var array
   */
  protected $ids = [];

  /**
   * Should financials be checked after the test but before tear down.
   *
   * Ideally all tests (or at least all that call any financial api calls ) should do this but there
   * are some test data issues and some real bugs currently blocking.
   *
   * @var bool
   */
  protected $isValidateFinancialsOnPostAssert = TRUE;

  /**
   * Should location types be checked to ensure primary addresses are correctly assigned after each test.
   *
   * @var bool
   */
  protected $isLocationTypesOnPostAssert = TRUE;

  /**
   * Has the test class been verified as 'getsafe'.
   *
   * If a class is getsafe it means that where
   * callApiSuccess is called 'return' is specified or 'return' =>'id'
   * can be added by that function. This is part of getting away
   * from open-ended get calls.
   *
   * Eventually we want to not be doing these in our test classes & start
   * to work to not do them in our main code base. Note they mainly
   * cause issues for activity.get and contact.get as these are where the
   * too many joins limit is most likely to be hit.
   *
   * @var bool
   */
  protected $isGetSafe = FALSE;

  /**
   * Class used for hooks during tests.
   *
   * This can be used to test hooks within tests. For example in the ACL_PermissionTrait:
   *
   * $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereHookAllResults']);
   *
   * @var \CRM_Utils_Hook_UnitTests
   */
  public $hookClass;

  /**
   * @var array
   * Common values to be re-used multiple times within a class - usually to create the relevant entity
   */
  protected $_params = [];

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
  public $setupIDs = [];

  /**
   * Form controller being used.
   *
   * We need to re-use this for multi-part forms.
   *
   * @var \CRM_Event_Controller_Registration
   */
  protected $formController;

  /**
   * @var \CRM_Utils_AutoClean
   */
  private $frozenTime;

  /**
   * @var mixed
   */
  private $errorHandlerAtStartOfTest;

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
  public function __construct($name = NULL, array $data = [], $dataName = '') {
    parent::__construct($name, $data, $dataName);

    // we need full error reporting
    error_reporting(E_ALL & ~E_NOTICE);

    // also load the class loader
    require_once 'CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();
    if (function_exists('_civix_phpunit_setUp')) {
      // FIXME: loosen coupling
      _civix_phpunit_setUp();
    }
  }

  /**
   * @param array $formValues
   * @param string $identifier
   *
   * @return void
   */
  public function caseCreate(array $formValues, string $identifier): void {
    if (!CRM_Core_Session::getLoggedInContactID()) {
      $this->createLoggedInUser();
    }
    $this->getTestForm('CRM_Case_Form_Case',
      array_merge([
        'activity_subject' => 'Case Subject',
        'case_type_id' => 1,
        'status_id' => 1,
        'case_type' => 'housing_support',
        'subject' => 'Case Subject',
        'start_date' => date('Y-m-d'),
        'start_date_time' => date('YmdHis'),
        'medium_id' => 2,
        'activity_details' => '',
      ], $formValues),
      ['cid' => $formValues['client_id']]
    )->processForm();
    try {
      $this->ids['Case'][$identifier] = CiviCase::get(FALSE)
        ->addOrderBy('id', 'DESC')
        ->execute()->first()['id'];
    }
    catch (CRM_Core_Exception $e) {
      $this->fail('Case not retrieved');
    }
  }

  /**
   * Override to run the test and assert its state.
   *
   * @return mixed
   *
   * @throws \Throwable
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
   * Declare the environment that we wish to run in.
   *
   * TODO: The hope is to get this to align with `Civi\Test::headless()` and perhaps
   * assimilate other steps from 'setUp()'. The method is reserved while we look
   * for the right split. However, when we're a little further along on that, this
   * should be made overrideable.
   *
   * @return \Civi\Test\CiviEnvBuilder
   */
  final public static function buildEnvironment(): \Civi\Test\CiviEnvBuilder {
    // Ideally: return Civi\Test::headless();
    $b = new \Civi\Test\CiviEnvBuilder();
    $b->callback(function () {
      fprintf(STDERR, "\nInstalling %s database\n", \Civi\Test::dsn('database'));
    });
    $b->callback([\Civi\Test::data(), 'populate']);
    return $b;
  }

  public static function setUpBeforeClass(): void {
    if (CIVICRM_UF !== 'UnitTests') {
      throw new \RuntimeException('CiviUnitTestCase requires CIVICRM_UF=UnitTests');
    }

    \Civi\Test::asPreInstall([static::CLASS, 'buildEnvironment'])->apply(TRUE);

    // also set this global hack
    $GLOBALS['_PEAR_ERRORSTACK_OVERRIDE_CALLBACK'] = [];
  }

  /**
   *  Common setup functions for all unit tests.
   */
  protected function setUp(): void {
    if ($this->tx !== NULL) {
      throw new \RuntimeException("CiviUnitTestCase requires that parent::setUp() run before useTransaction()");
    }

    CRM_Core_I18n::clearLocale();
    parent::setUp();
    CRM_Core_Session::singleton()->set('userID');

    //  Use a temporary file for STDIN
    $GLOBALS['stdin'] = tmpfile();
    if ($GLOBALS['stdin'] === FALSE) {
      echo "Couldn't open temporary file\n";
      exit(1);
    }

    // "initialize" CiviCRM to avoid problems when running single tests
    // FIXME: look at it closer in second stage

    $GLOBALS['civicrm_setting']['domain']['fatalErrorHandler'] = 'CiviUnitTestCase_fatalErrorHandler';
    $GLOBALS['civicrm_setting']['domain']['backtrace'] = 1;

    // disable any left-over test extensions
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_extension WHERE full_name LIKE "test.%"');
    // reset all the caches
    CRM_Utils_System::flushCache();

    // initialize the object once db is loaded
    \Civi::$statics = [];
    // ugh, performance
    $config = CRM_Core_Config::singleton(TRUE, TRUE);

    // when running unit tests, use mockup user framework
    $this->hookClass = CRM_Utils_Hook::singleton();

    // Make sure the DB connection is setup properly
    $config->userSystem->setMySQLTimeZone();
    $env = new CRM_Utils_Check_Component_Env();
    CRM_Utils_Check::singleton()->assertValid($env->checkMysqlTime());

    // clear permissions stub to not check permissions
    $config->userPermissionClass->permissions = NULL;

    // Normally a stock install has some acls in the table even if they aren't in use.
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_acl (name, deny, entity_table, entity_id, operation, object_table, object_id, acl_table, acl_id, is_active) VALUES ('Edit All Contacts', 0, 'civicrm_acl_role', 1, 'Edit', 'civicrm_group', 0, NULL, NULL, 1)");

    //flush component settings
    CRM_Core_Component::getEnabledComponents(TRUE);

    $_REQUEST = $_GET = $_POST = [];
    error_reporting(E_ALL);

    $this->renameLabels();
    $this->ensureMySQLMode(['IGNORE_SPACE', 'ERROR_FOR_DIVISION_BY_ZERO', 'STRICT_TRANS_TABLES']);
    putenv('CIVICRM_SMARTY_DEFAULT_ESCAPE=1');
    $this->originalSettings = \Civi::settings()->all();

    // There doesn't seem to be a better way to get the current error handler.
    // We want to know it so we can compare at the end of the test to see if
    // something changed it and then inadvertently didn't restore it.
    $this->errorHandlerAtStartOfTest = set_error_handler(function($errno, $errstr, $errfile, $errline) {});
    restore_error_handler();
  }

  /**
   * Read everything from the datasets directory and insert into the db.
   *
   * @noinspection PhpUnhandledExceptionInspection*/
  public function loadAllFixtures(): void {
    $fixturesDir = __DIR__ . '/../../fixtures';

    CRM_Core_DAO::executeQuery('SET FOREIGN_KEY_CHECKS = 0;');

    $jsonFiles = glob($fixturesDir . '/*.json');
    foreach ($jsonFiles as $jsonFixture) {
      $json = json_decode(file_get_contents($jsonFixture));
      foreach ($json as $tableName => $vars) {
        if ($tableName === 'civicrm_contact') {
          CRM_Core_DAO::executeQuery('DELETE c FROM civicrm_contact c LEFT JOIN civicrm_domain d ON d.contact_id = c.id WHERE d.id IS NULL');
        }
        else {
          CRM_Core_DAO::executeQuery("TRUNCATE $tableName");
        }
        foreach ($vars as $entity) {
          $keys = $values = [];
          foreach ($entity as $key => $value) {
            $keys[] = $key;
            $values[] = is_numeric($value) ? $value : "'{$value}'";
          }
          CRM_Core_DAO::executeQuery("
            INSERT INTO $tableName (" . implode(',', $keys) . ') VALUES(' . implode(',', $values) . ')'
          );
        }

      }
    }

    CRM_Core_DAO::executeQuery('SET FOREIGN_KEY_CHECKS = 1;');
  }

  /**
   * Load the data that used to be handled by the discontinued dbunit class.
   *
   * This could do with further tidy up - the initial priority is simply to get rid of
   * the dbunity package which is no longer supported.
   *
   * @param string $fileName
   */
  protected function loadXMLDataSet($fileName) {
    CRM_Core_DAO::executeQuery('SET FOREIGN_KEY_CHECKS = 0');
    $xml = json_decode(json_encode(simplexml_load_file($fileName)), TRUE);
    foreach ($xml as $tableName => $element) {
      if (!empty($element)) {
        foreach ($element as $row) {
          $keys = $values = [];
          if (isset($row['@attributes'])) {
            foreach ($row['@attributes'] as $key => $value) {
              $keys[] = $key;
              $values[] = is_numeric($value) ? $value : "'{$value}'";
            }
          }
          elseif (!empty($row)) {
            // cos we copied it & it is inconsistent....
            foreach ($row as $key => $value) {
              $keys[] = $key;
              $values[] = is_numeric($value) ? $value : "'{$value}'";
            }
          }

          if (!empty($values)) {
            CRM_Core_DAO::executeQuery("
            INSERT INTO $tableName (" . implode(',', $keys) . ') VALUES(' . implode(',', $values) . ')'
            );
          }
        }
      }
    }
    CRM_Core_DAO::executeQuery('SET FOREIGN_KEY_CHECKS = 1');
  }

  /**
   * Create default domain contacts for the two domains added during test class.
   * database population.
   */
  public function createDomainContacts(): void {
    try {
      $this->organizationCreate(['api.Email.create' => ['email' => 'fixme.domainemail@example.org']]);
      $this->organizationCreate([
        'organization_name' => 'Second Domain',
        'api.Email.create' => ['email' => 'domainemail2@example.org'],
        'api.Address.create' => [
          'street_address' => '15 Main St',
          'location_type_id' => 1,
          'city' => 'Collinsville',
          'country_id' => 1228,
          'state_province_id' => 1003,
          'postal_code' => 6022,
        ],
      ]);
      OptionValue::replace(FALSE)->addWhere(
        'option_group_id:name', '=', 'from_email_address'
      )->setDefaults([
        'is_default' => 1,
        'name' => '"FIXME" <info@EXAMPLE.ORG>',
        'label' => '"FIXME" <info@EXAMPLE.ORG>',
      ])->setRecords([['domain_id' => 1], ['domain_id' => 2]])->execute();
    }
    catch (CRM_Core_Exception $e) {
      $this->fail('failed to re-instate domain contacts ' . $e->getMessage());
    }
  }

  /**
   *  Common teardown functions for all unit tests.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function tearDown(): void {
    $this->_apiversion = 3;
    $this->resetLabels();
    $this->frozenTime = NULL;

    error_reporting(E_ALL & ~E_NOTICE);
    $this->resetHooks();
    CRM_Core_Session::singleton()->reset(1);

    if ($this->tx) {
      $this->tx->rollback()->commit();
      $this->tx = NULL;

      CRM_Core_Transaction::forceRollbackIfEnabled();
      Manager::singleton(TRUE);
    }
    else {
      CRM_Core_Transaction::forceRollbackIfEnabled();
      Manager::singleton(TRUE);

      $tablesToTruncate = ['civicrm_contact', 'civicrm_uf_match', 'civicrm_email', 'civicrm_address', 'civicrm_acl'];
      $this->quickCleanup($tablesToTruncate);
      $this->createDomainContacts();
    }

    // If a test leaks an extraneous hold on a lock, then we want that test to fail (rather than
    // proceeding and causing spooky effects on other tests).
    $dbVer = CRM_Utils_SQL::getDatabaseVersion();
    if (!preg_match('/maria/i', $dbVer) || version_compare($dbVer, '10.5.2', '>=')) {
      // Supported by MySQL 5.7+ or MariaDB 10.5.2+. These provide some herd immunity.
      $releasedLocks = CRM_Core_DAO::singleValueQuery('SELECT RELEASE_ALL_LOCKS()');
      $this->assertEquals(0, $releasedLocks, "The test should not leave any dangling locks. Found $releasedLocks");
    }

    $this->cleanTempDirs();
    $this->unsetExtensionSystem();
    $this->assertEquals([], CRM_Core_DAO::$_nullArray);
    $this->assertEquals(NULL, CRM_Core_DAO::$_nullObject);
    // Setting large properties to NULL here ensures memory is released as each
    // test class is held in memory until the very end.
    $this->formController = NULL;
    // Ensure the destruct runs by unsetting the Mutt.
    unset($this->mut);
    if (!empty($this->ids['UFGroup'])) {
      UFGroup::delete(FALSE)->addWhere('id', 'IN', $this->ids['UFGroup'])->execute();
    }
    if (!empty($this->ids['DedupeRuleGroup'])) {
      DedupeRule::delete(FALSE)->addWhere('dedupe_rule_group_id', 'IN', $this->ids['DedupeRuleGroup'])->execute();
      DedupeRuleGroup::delete(FALSE)->addWhere('id', 'IN', $this->ids['DedupeRuleGroup'])->execute();
    }
    unset(CRM_Core_Config::singleton()->userPermissionClass->permissions);
    parent::tearDown();
  }

  /**
   * @param string $setting
   */
  protected function revertSetting(string $setting): void {
    \Civi::settings()->set($setting, $this->originalSettings[$setting]);
  }

  /**
   * CHeck that all tests that have created payments have created them with the right financial entities.
   *
   * @throws \CRM_Core_Exception
   */
  protected function assertPostConditions(): void {
    // There doesn't seem to be a better way to get the current error handler.
    $errorHandlerAtEndOfTest = set_error_handler(function($errno, $errstr, $errfile, $errline) {});
    restore_error_handler();
    if ($this->errorHandlerAtStartOfTest != $errorHandlerAtEndOfTest) {
      $this->fail('Error handler is not the same at the end of the test as when it started. Did you forget to call parent::setUp or parent::tearDown? Start: ' . print_r($this->errorHandlerAtStartOfTest, TRUE) . "\nEnd: " . print_r($errorHandlerAtEndOfTest, TRUE));
    }

    // Reset to version 3 as not all (e.g payments) work on v4
    $this->_apiversion = 3;
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviContribute');
    if ($this->isLocationTypesOnPostAssert) {
      $this->assertLocationValidity();
    }
    $this->assertCount(1, OptionGroup::get(FALSE)
      ->addWhere('name', '=', 'from_email_address')
      ->execute());
    if (!$this->isValidateFinancialsOnPostAssert) {
      return;
    }
    $this->validateAllPayments();
    $this->validateAllContributions();
  }

  /**
   * Create a batch of external API calls which can
   * be executed concurrently.
   *
   * ```
   * $calls = $this->createExternalAPI()
   *    ->addCall('Contact', 'get', ...)
   *    ->addCall('Contact', 'get', ...)
   *    ...
   *    ->run()
   *    ->getResults();
   * ```
   *
   * @return \Civi\API\ExternalBatch
   * @throws PHPUnit_Framework_SkippedTestError
   */
  public function createExternalAPI() {
    global $civicrm_root;
    $defaultParams = [
      'version' => $this->_apiversion,
      'debug' => 1,
    ];

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

  /**
   * @param array $params
   * @param string $identifer
   *
   * @return int
   */
  public function membershipTypeCreate(array $params = [], string $identifer = 'test'): int {
    CRM_Member_PseudoConstant::flush('membershipType');
    CRM_Core_Config::clearDBCache();
    $this->setupIDs['contact'] = $memberOfOrganization = $this->organizationCreate();
    $params = array_merge([
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
    ], $params);

    $result = $this->createTestEntity('MembershipType', $params, $identifer);

    CRM_Member_PseudoConstant::flush('membershipType');
    CRM_Utils_Cache::singleton()->flush();
    return (int) $result['id'];
  }

  /**
   * Create membership.
   *
   * @param array $params
   *
   * @return int
   */
  public function contactMembershipCreate(array $params): int {
    $params = array_merge([
      'join_date' => '2007-01-21',
      'start_date' => '2007-01-21',
      'end_date' => '2007-12-21',
      'source' => 'Payment',
      'membership_type_id' => 'General',
    ], $params);
    if (!is_numeric($params['membership_type_id'])) {
      $membershipTypes = $this->callAPISuccess('Membership', 'getoptions', ['action' => 'create', 'field' => 'membership_type_id']);
      if (!in_array($params['membership_type_id'], $membershipTypes['values'], TRUE)) {
        $this->membershipTypeCreate(['name' => $params['membership_type_id']]);
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
    $deleteParams = ['id' => $membershipID];
    $result = $this->callAPISuccess('Membership', 'Delete', $deleteParams);
  }

  /**
   * @param string $name
   *
   * @return int
   */
  public function membershipStatusCreate($name = 'test member status'): int {
    $params['name'] = $name;
    $params['start_event'] = 'start_date';
    $params['end_event'] = 'end_date';
    $params['is_current_member'] = 1;
    $params['is_active'] = 1;

    $result = $this->callAPISuccess('MembershipStatus', 'Create', $params);
    CRM_Member_PseudoConstant::flush('membershipStatus');
    return (int) $result['id'];
  }

  /**
   * Delete the given membership status, deleting any memberships of the status first.
   *
   * @param int $membershipStatusID
   *
   * @throws \CRM_Core_Exception
   */
  public function membershipStatusDelete(int $membershipStatusID): void {
    $this->callAPISuccess('Membership', 'get', ['status_id' => $membershipStatusID, 'api.Membership.delete' => 1]);
    $this->callAPISuccess('MembershipStatus', 'Delete', ['id' => $membershipStatusID]);
  }

  public function membershipRenewalDate($durationUnit, $membershipEndDate) {
    // We only have an end_date if frequency units match, otherwise membership won't be autorenewed and dates won't be calculated.
    $renewedMembershipEndDate = new DateTime($membershipEndDate);
    // We have to add 1 day first in case it's the end of the month, then subtract afterwards
    // eg. 2018-02-28 should renew to 2018-03-31, if we just added 1 month we'd get 2018-03-28
    $renewedMembershipEndDate->add(new DateInterval('P1D'));
    switch ($durationUnit) {
      case 'year':
        $renewedMembershipEndDate->add(new DateInterval('P1Y'));
        break;

      case 'month':
        $renewedMembershipEndDate->add(new DateInterval('P1M'));
        break;
    }
    $renewedMembershipEndDate->sub(new DateInterval('P1D'));
    return $renewedMembershipEndDate->format('Y-m-d');
  }

  /**
   * Create a relationship type.
   *
   * @param array $params
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  public function relationshipTypeCreate($params = []) {
    $params = array_merge([
      'name_a_b' => 'Relation 1 for relationship type create',
      'name_b_a' => 'Relation 2 for relationship type create',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'is_reserved' => 1,
      'is_active' => 1,
    ], $params);

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
   * @return int
   */
  public function paymentProcessorTypeCreate(array $params = []): int {
    $params = array_merge([
      'name' => 'API_Test_PP',
      'title' => 'API Test Payment Processor',
      'class_name' => 'CRM_Core_Payment_APITest',
      'billing_mode' => 'form',
      'is_recur' => 0,
      'is_reserved' => 1,
      'is_active' => 1,
    ], $params);
    $result = $this->callAPISuccess('PaymentProcessorType', 'create', $params);

    CRM_Core_PseudoConstant::flush('paymentProcessorType');

    return (int) $result['id'];
  }

  /**
   * Create Participant.
   *
   * @param array $params
   *   Array of contact id and event id values.
   *
   * @return int
   *   id of participant created
   */
  public function participantCreate(array $params = []): int {
    if (empty($params['contact_id'])) {
      $this->ids['Contact']['participant'] = $params['contact_id'] = $this->individualCreate();
    }
    if (empty($params['event_id'])) {
      $event = $this->eventCreateUnpaid(['end_date' => 20081023, 'registration_end_date' => 20081015]);
      $params['event_id'] = $event['id'];
    }
    $defaults = [
      'role_id' => 1,
      'register_date' => 20070219,
      'source' => 'Wimbledon',
      'event_level' => 'Payment',
      'debug' => 1,
    ];

    $params = array_merge($defaults, $params);
    if (empty($params['status_id']) && empty($params['status_id.name'])) {
      $params['status_id.name'] = 'Attended';
    }
    $result = $this->createTestEntity('Participant', $params);
    return $result['id'];
  }

  /**
   * Create Payment Processor.
   *
   * @return int
   *   Payment Processor ID.
   */
  public function processorCreate($params = []): int {
    $processorParams = [
      'domain_id' => 1,
      'name' => 'Dummy',
      'title' => 'Dummy',
      'payment_processor_type_id:name' => 'Dummy',
      'financial_account_id' => 12,
      'is_test' => TRUE,
      'is_active' => 1,
      'user_name' => '',
      'url_site' => 'https://dummy.com',
      'url_recur' => 'https://dummy.com',
      'billing_mode' => 1,
      'sequential' => 1,
      'payment_instrument_id:name' => 'Debit Card',
    ];
    $processorParams = array_merge($processorParams, $params);
    $processor = $this->createTestEntity('PaymentProcessor', $processorParams, 'dummy');
    return $processor['id'];
  }

  /**
   * Create Payment Processor.
   *
   * @param array $processorParams
   *
   * @return \CRM_Core_Payment_Dummy
   *   Instance of Dummy Payment Processor
   *
   * @noinspection PhpIncompatibleReturnTypeInspection
   */
  public function dummyProcessorCreate(array $processorParams = []): CRM_Core_Payment_Dummy {
    $paymentProcessorID = $this->processorCreate($processorParams);
    $this->ids['PaymentProcessor']['dummy_test'] = $paymentProcessorID;
    // For the tests we don't need a live processor, but as core ALWAYS creates a processor in live mode and one in test mode we do need to create both
    //   Otherwise we are testing a scenario that only exists in tests (and some tests fail because the live processor has not been defined).
    $processorParams['is_test'] = FALSE;
    $this->ids['PaymentProcessor']['dummy_live'] = $this->processorCreate($processorParams);
    return System::singleton()->getById($paymentProcessorID);
  }

  /**
   * Create contribution page.
   *
   * @param array $contributionPageValues
   * @param string $identifier
   *
   * @return array
   *   Array of contribution page
   */
  public function contributionPageCreate(array $contributionPageValues = [], string $identifier = 'test'): array {
    return $this->createTestEntity('ContributionPage', array_merge([
      'title' => 'Test Contribution Page',
      'financial_type_id:name' => 'Donation',
      'currency' => 'USD',
      'financial_account_id' => 1,
      'is_active' => 1,
      'is_allow_other_amount' => 1,
      'min_amount' => 10,
      'max_amount' => 1000,
    ], $contributionPageValues), $identifier);
  }

  /**
   * Create a sample batch.
   *
   * @return int
   */
  public function batchCreate(): int {
    $params = $this->_params;
    $params['name'] = $params['title'] = 'Batch_433397';
    $params['status_id'] = 1;
    $result = $this->callAPISuccess('batch', 'create', $params);
    return (int) $result['id'];
  }

  /**
   * Create Tag.
   *
   * @param array $params
   *
   * @return array
   *   result of created tag
   */
  public function tagCreate(array $params = []): array {
    $defaults = [
      'name' => 'New Tag3',
      'description' => 'This is description for Our New Tag ',
      'domain_id' => '1',
    ];
    $params = array_merge($defaults, $params);
    $result = $this->callAPISuccess('Tag', 'create', $params);
    return $result['values'][$result['id']];
  }

  /**
   * Temporarily freeze time, as perceived through `CRM_Utils_Time`.
   */
  protected function useFrozenTime(): void {
    $oldTimeFunc = getenv('TIME_FUNC');
    putenv('TIME_FUNC=frozen');
    CRM_Utils_Time::setTime(date('Y-m-d H:i:s'));
    $this->frozenTime = CRM_Utils_AutoClean::with(function () use ($oldTimeFunc) {
      putenv($oldTimeFunc === NULL ? 'TIME_FUNC' : "TIME_FUNC=$oldTimeFunc");
      CRM_Utils_Time::resetTime();
    });
  }

  /**
   * Delete Tag.
   *
   * @param int $tagID
   *   ID of the tag to be deleted.
   *
   * @return int
   */
  public function tagDelete(int $tagID): int {
    $result = $this->callAPISuccess('Tag', 'delete', [
      'tag_id' => $tagID,
    ]);
    return (int) $result['id'];
  }

  /**
   * Add entity(s) to the tag
   *
   * @param array $params
   *
   * @return true
   */
  public function entityTagAdd(array $params): bool {
    $this->callAPISuccess('EntityTag', 'create', $params);
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
  public function pledgeCreate(array $params): int {
    $params = array_merge([
      'create_date' => date('Ymd'),
      'start_date' => date('Ymd'),
      'scheduled_date' => date('Ymd'),
      'amount' => 100.00,
      'status_id' => '2',
      'financial_type_id' => '1',
      'original_installment_amount' => 20,
      'frequency_interval' => 5,
      'frequency_unit' => 'year',
      'frequency_day' => 15,
      'installments' => 5,
    ],
      $params);

    $result = $this->createTestEntity('Pledge', $params);
    return $result['id'];
  }

  /**
   * Delete pledge.
   *
   * @param int $pledgeID
   */
  public function pledgeDelete(int $pledgeID): void {
    $this->callAPISuccess('Pledge', 'delete', [
      'pledge_id' => $pledgeID,
    ]);
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
  public function contributionCreate(array $params): int {
    $params = array_merge([
      'domain_id' => 1,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'fee_amount' => 5.00,
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'source' => 'SSF',
      'contribution_status_id' => 'Completed',
    ], $params);

    return $this->callAPISuccess('Contribution', 'create', $params)['id'];
  }

  /**
   * Delete contribution.
   *
   * @param int $contributionID
   *
   * @return array|int
   */
  public function contributionDelete(int $contributionID) {
    return $this->callAPISuccess('Contribution', 'delete', [
      'contribution_id' => $contributionID,
    ]);
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
    $params = [
      'event_id' => $id,
    ];
    return $this->callAPISuccess('Event', 'delete', $params);
  }

  /**
   * Delete participant.
   *
   * @param int $participantID
   *
   * @return array|int
   */
  public function participantDelete(int $participantID) {
    $params = [
      'id' => $participantID,
    ];
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
   *
   * @return int
   *   $id of created payment
   */
  public function participantPaymentCreate($participantID, $contributionID = NULL) {
    //Create Participant Payment record With Values
    $params = [
      'participant_id' => $participantID,
      'contribution_id' => $contributionID,
    ];

    $result = $this->callAPISuccess('participant_payment', 'create', $params);
    return $result['id'];
  }

  /**
   * Delete participant payment.
   *
   * @param int $paymentID
   */
  public function participantPaymentDelete($paymentID) {
    $params = [
      'id' => $paymentID,
    ];
    $result = $this->callAPISuccess('participant_payment', 'delete', $params);
  }

  /**
   * Add a Location.
   *
   * @param int $contactID
   *
   * @return int
   *   location id of created location
   */
  public function locationAdd($contactID) {
    $address = [
      1 => [
        'location_type' => 'New Location Type',
        'is_primary' => 1,
        'name' => 'Saint Helier St',
        'county' => 'Marin',
        'country' => 'UNITED STATES',
        'state_province' => 'Michigan',
        'supplemental_address_1' => 'Hallmark Ct',
        'supplemental_address_2' => 'Jersey Village',
        'supplemental_address_3' => 'My Town',
      ],
    ];

    $params = [
      'contact_id' => $contactID,
      'address' => $address,
      'location_format' => '2.0',
      'location_type' => 'New Location Type',
    ];

    return $this->callAPISuccess('Location', 'create', $params);
  }

  /**
   * Add a Location Type.
   *
   * @param array $params
   *
   * @return int
   *   location id of created location
   */
  public function locationTypeCreate(array $params = []): int {
    $params = array_merge([
      'name' => 'New Location Type',
      'display_name' => 'New Location Type',
      'vcard_name' => 'New Location Type',
      'description' => 'Location Type for Delete',
      'is_active' => 1,
    ], $params);

    $locationType = CRM_Core_BAO_LocationType::writeRecord($params);
    $this->callAPISuccess('Phone', 'getfields', ['version' => 3, 'cache_clear' => 1]);
    return $locationType->id;
  }

  /**
   * Delete a Location Type.
   *
   * @param int $locationTypeID
   */
  public function locationTypeDelete(int $locationTypeID): void {
    $locationType = new CRM_Core_DAO_LocationType();
    $locationType->id = $locationTypeID;
    $locationType->delete();
  }

  /**
   * Add a Mapping.
   *
   * @param array $params
   *
   * @return CRM_Core_DAO_Mapping
   *   Mapping id of created mapping
   */
  public function mappingCreate($params = NULL) {
    if ($params === NULL) {
      $params = [
        'name' => 'Mapping name',
        'description' => 'Mapping description',
        // 'Export Contact' mapping.
        'mapping_type_id' => 7,
      ];
    }

    $mapping = new CRM_Core_DAO_Mapping();
    $mapping->copyValues($params);
    $mapping->save();
    // clear getfields cache
    CRM_Core_PseudoConstant::flush();
    $this->callAPISuccess('mapping', 'getfields', ['version' => 3, 'cache_clear' => 1]);
    return $mapping;
  }

  /**
   * Delete a Mapping
   *
   * @param int $mappingID
   */
  public function mappingDelete(int $mappingID): void {
    $mapping = new CRM_Core_DAO_Mapping();
    $mapping->id = $mappingID;
    $mapping->delete();
  }

  /**
   * Prepare class for ACLs.
   */
  protected function prepareForACLs(): void {
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = [];
  }

  /**
   * Reset after ACLs.
   */
  protected function cleanUpAfterACLs() {
    CRM_Utils_Hook::singleton()->reset();
    $tablesToTruncate = [
      'civicrm_acl',
      'civicrm_acl_cache',
      'civicrm_acl_entity_role',
      'civicrm_acl_contact_cache',
    ];
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
   * @param string $contactType
   *
   * @return int
   */
  public function smartGroupCreate(array $smartGroupParams = [], array $groupParams = [], string $contactType = 'Household'): int {
    $smartGroupParams = array_merge(['form_values' => ['contact_type' => ['IN' => [$contactType]]]], $smartGroupParams);
    $savedSearch = CRM_Contact_BAO_SavedSearch::create($smartGroupParams);

    $groupParams['saved_search_id'] = $savedSearch->id;
    return $this->groupCreate($groupParams);
  }

  /**
   * Create a UFField.
   *
   * @param array $params
   */
  public function uFFieldCreate($params = []) {
    $params = array_merge([
      'uf_group_id' => 1,
      'field_name' => 'first_name',
      'is_active' => 1,
      'is_required' => 1,
      'visibility' => 'Public Pages and Listings',
      'is_searchable' => '1',
      'label' => 'first_name',
      'field_type' => 'Individual',
      'weight' => 1,
    ], $params);
    $this->callAPISuccess('uf_field', 'create', $params);
  }

  /**
   * Add a UF Join Entry.
   *
   * @param array|null $params
   *
   * @return int
   *   $id of created UF Join
   */
  public function ufjoinCreate(array $params = NULL): int {
    if ($params === NULL) {
      $params = [
        'is_active' => 1,
        'module' => 'CiviEvent',
        'entity_table' => 'civicrm_event',
        'entity_id' => 3,
        'weight' => 1,
        'uf_group_id' => 1,
      ];
    }
    return $this->callAPISuccess('UFJoin', 'create', $params);
  }

  /**
   * @param array $params
   *   Optional parameters.
   *
   * @return int
   *   Campaign ID.
   */
  public function campaignCreate(array $params = []): int {
    $this->enableCiviCampaign();
    $campaign = $this->callAPISuccess('Campaign', 'create', array_merge([
      'title' => 'big campaign',
    ], $params));
    return $campaign['id'];
  }

  /**
   * Create Activity.
   *
   * @param array $params
   *
   * @return array
   */
  public function activityCreate(array $params = []): array {
    $params = array_merge([
      'subject' => 'Discussion on warm beer',
      'activity_date_time' => date('Ymd'),
      'duration' => 90,
      'location' => 'Baker Street',
      'details' => 'Lets schedule a meeting',
      'status_id' => 1,
      'activity_type_id' => 'Meeting',
    ], $params);
    if (!isset($params['source_contact_id'])) {
      $params['source_contact_id'] = $this->individualCreate();
    }
    if (!isset($params['target_contact_id'])) {
      $params['target_contact_id'] = $this->individualCreate([
        'first_name' => 'Julia',
        'last_name' => 'Anderson',
        'prefix' => 'Ms.',
        'email' => 'julia_anderson@civicrm.org',
        'contact_type' => 'Individual',
      ]);
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
   * Create custom group.
   *
   * @param array $params
   *
   * @return array
   */
  public function customGroupCreate(array $params = []): array {
    $defaults = [
      'title' => 'new custom group',
      'extends' => 'Contact',
      'domain_id' => 1,
      'style' => 'Inline',
      'is_active' => 1,
    ];

    $params = array_merge($defaults, $params);

    return $this->callAPISuccess('CustomGroup', 'create', $params);
  }

  /**
   * Existing function doesn't allow params to be over-ridden so need a new one
   * this one allows you to only pass in the params you want to change
   *
   * @param array $params
   *
   * @return array|int
   */
  public function CustomGroupCreateByParams(array $params = []) {
    $defaults = [
      'title' => 'API Custom Group',
      'extends' => 'Contact',
      'domain_id' => 1,
      'style' => 'Inline',
      'is_active' => 1,
    ];
    $params = array_merge($defaults, $params);
    return $this->callAPISuccess('CustomGroup', 'create', $params);
  }

  /**
   * Create custom group with multi fields.
   *
   * @param array $params
   *
   * @return array|int
   */
  public function CustomGroupMultipleCreateByParams(array $params = []) {
    $defaults = [
      'style' => 'Tab',
      'is_multiple' => 1,
    ];
    $params = array_merge($defaults, $params);
    return $this->CustomGroupCreateByParams($params);
  }

  /**
   * Create custom group with multi fields.
   *
   * @param array $params
   *
   * @return array
   */
  public function CustomGroupMultipleCreateWithFields($params = []) {
    // also need to pass on $params['custom_field'] if not set but not in place yet
    $ids = [];
    $customGroup = $this->CustomGroupMultipleCreateByParams($params);
    $ids['custom_group_id'] = $customGroup['id'];

    $customField = $this->customFieldCreate([
      'custom_group_id' => $ids['custom_group_id'],
      'label' => 'field_1' . $ids['custom_group_id'],
      'in_selector' => 1,
    ]);

    $ids['custom_field_id'][] = $customField['id'];

    $customField = $this->customFieldCreate([
      'custom_group_id' => $ids['custom_group_id'],
      'default_value' => '',
      'label' => 'field_2' . $ids['custom_group_id'],
      'in_selector' => 1,
    ]);
    $ids['custom_field_id'][] = $customField['id'];

    $customField = $this->customFieldCreate([
      'custom_group_id' => $ids['custom_group_id'],
      'default_value' => '',
      'label' => 'field_3' . $ids['custom_group_id'],
      'in_selector' => 1,
    ]);
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
  public function entityCustomGroupWithSingleFieldCreate($function, $filename): array {
    $params = ['title' => $function];
    $entity = substr(basename($filename), 0, strlen(basename($filename)) - 8);
    $params['extends'] = $entity ?: 'Contact';
    $customGroup = $this->customGroupCreate($params);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id'], 'label' => $function]);
    CRM_Core_PseudoConstant::flush();

    return ['custom_group_id' => $customGroup['id'], 'custom_field_id' => $customField['id']];
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
  public function entityCustomGroupWithSingleStringMultiSelectFieldCreate($function, $filename): array {
    $params = ['title' => $function];
    $entity = substr(basename($filename), 0, strlen(basename($filename)) - 8);
    $params['extends'] = $entity ?: 'Contact';
    $customGroup = $this->customGroupCreate($params);
    $customField = $this->customFieldCreate(['version' => 3, 'custom_group_id' => $customGroup['id'], 'label' => $function, 'html_type' => 'Multi-Select', 'default_value' => 1]);
    CRM_Core_PseudoConstant::flush();
    $options = [
      'defaultValue' => 'Default Value',
      'lowercasevalue' => 'Lowercase Value',
      1 => 'Integer Value',
      'NULL' => 'NULL',
    ];
    $custom_field_params = ['sequential' => 1, 'id' => $customField['id']];
    $custom_field_api_result = $this->callAPISuccess('CustomField', 'get', $custom_field_params);
    $this->assertNotEmpty($custom_field_api_result['values'][0]['option_group_id']);
    $option_group_params = ['sequential' => 1, 'id' => $custom_field_api_result['values'][0]['option_group_id']];
    $option_group_result = $this->callAPISuccess('OptionGroup', 'get', $option_group_params);
    $this->assertNotEmpty($option_group_result['values'][0]['name']);
    foreach ($options as $option_value => $option_label) {
      $option_group_params = ['option_group_id' => $option_group_result['values'][0]['name'], 'value' => $option_value, 'label' => $option_label];
      $option_value_result = $this->callAPISuccess('OptionValue', 'create', $option_group_params);
    }

    return [
      'custom_group_id' => $customGroup['id'],
      'custom_field_id' => $customField['id'],
      'custom_field_option_group_id' => $custom_field_api_result['values'][0]['option_group_id'],
      'custom_field_group_options' => $options,
    ];
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
   *
   * @return array
   */
  public function customFieldCreate($params) {
    $params = array_merge([
      'label' => 'Custom Field',
      'data_type' => 'String',
      'html_type' => 'Text',
      'is_searchable' => 1,
      'is_active' => 1,
      'default_value' => 'defaultValue',
    ], $params);

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
   * Enable CiviCampaign Component.
   */
  public function enableCiviCampaign(): void {
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
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
  public function customFieldOptionValueCreate(array $customGroup, string $name, array $extraParams = []) {
    $fieldParams = [
      'custom_group_id' => $customGroup['id'],
      'name' => 'test_custom_group',
      'label' => 'Country',
      'html_type' => 'Select',
      'data_type' => 'String',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    ];

    $optionGroup = [
      'domain_id' => 1,
      'name' => 'option_group1',
      'label' => 'option_group_label1',
    ];

    $optionValue = [
      'option_label' => ['Label1', 'Label2'],
      'option_value' => ['value1', 'value2'],
      'option_name' => [$name . '_1', $name . '_2'],
      'option_weight' => [1, 2],
      'option_status' => [1, 1],
    ];

    $params = array_merge($fieldParams, $optionGroup, $optionValue, $extraParams);

    return $this->callAPISuccess('CustomField', 'create', $params);
  }

  /**
   * Quick clean by emptying tables created for the test.
   *
   * @param array $tablesToTruncate
   * @param bool $dropCustomValueTables
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function quickCleanup(array $tablesToTruncate, bool $dropCustomValueTables = FALSE): void {
    if ($this->tx) {
      $this->fail('CiviUnitTestCase: quickCleanup() is not compatible with useTransaction()');
    }
    if ($dropCustomValueTables) {
      $this->cleanupCustomGroups();
      // Reset autoincrement too.
      $tablesToTruncate[] = 'civicrm_custom_group';
      $tablesToTruncate[] = 'civicrm_custom_field';
    }

    $tablesToTruncate = array_unique(array_merge($this->_tablesToTruncate, $tablesToTruncate));

    CRM_Core_DAO::executeQuery('SET FOREIGN_KEY_CHECKS = 0;');
    foreach ($tablesToTruncate as $table) {
      $sql = "TRUNCATE TABLE $table";
      CRM_Core_DAO::executeQuery($sql);
    }
    CRM_Core_DAO::executeQuery('SET FOREIGN_KEY_CHECKS = 1;');
  }

  /**
   * Clean up financial entities after financial tests (so we remember to get all the tables :-))
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function quickCleanUpFinancialEntities(): void {
    $tablesToTruncate = [
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
      'civicrm_payment_processor',
      'civicrm_participant_payment',
      'civicrm_payment_processor',
      'civicrm_pledge',
      'civicrm_pcp_block',
      'civicrm_pcp',
      'civicrm_pledge_block',
      'civicrm_pledge_payment',
      'civicrm_price_set_entity',
      'civicrm_price_field_value',
      'civicrm_price_field',
      'civicrm_product',
      'civicrm_premiums',
      'civicrm_premiums_product',
    ];
    $this->quickCleanup($tablesToTruncate);
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_membership_status WHERE name NOT IN('New', 'Current', 'Grace', 'Expired', 'Pending', 'Cancelled', 'Deceased')");
    $this->restoreDefaultPriceSetConfig();
    $this->disableTaxAndInvoicing();
    $this->setCurrencySeparators(',');
    try {
      FinancialType::delete(FALSE)->addWhere(
        'name', 'NOT IN', [
          'Donation',
          'Member Dues',
          'Campaign Contribution',
          'Event Fee',
        ]
      )->execute();
      FinancialAccount::delete(FALSE)->addClause(
        'OR',
        [['name', 'LIKE', 'Financial-Type -%'], ['name', 'LIKE', 'Sales tax %']]
      )->execute();
    }
    catch (CRM_Core_Exception $e) {
      $this->fail('failed to cleanup financial types ' . $e->getMessage());
    }
    $this->organizeOptionValues();
    CRM_Core_PseudoConstant::flush('taxRates');
    System::singleton()->flushProcessors();
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviMember');
    // @fixme this parameter is leaking - it should not be defined as a class static
    // but for now we just handle in tear down.
    CRM_Contribute_BAO_Query::$_contribOrSoftCredit = 'only contribs';
  }

  /**
   * Reset the price set config so results exist.
   */
  public function restoreDefaultPriceSetConfig(): void {
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_price_set WHERE name NOT IN('default_contribution_amount', 'default_membership_type_amount')");
    CRM_Core_DAO::executeQuery('UPDATE civicrm_price_set SET is_quick_config = 1, is_active = 1');
    CRM_Core_DAO::executeQuery("UPDATE civicrm_price_set SET id = 1 WHERE name ='default_contribution_amount'");
    CRM_Core_DAO::executeQuery("INSERT INTO `civicrm_price_field` (`id`, `price_set_id`, `name`, `label`, `html_type`, `is_enter_qty`, `help_pre`, `help_post`, `weight`, `is_display_amounts`, `options_per_line`, `is_active`, `is_required`, `active_on`, `expire_on`, `javascript`, `visibility_id`) VALUES (1, 1, 'contribution_amount', 'Contribution Amount', 'Text', 0, NULL, NULL, 1, 1, 1, 1, 1, NULL, NULL, NULL, 1)");
    CRM_Core_DAO::executeQuery("INSERT INTO `civicrm_price_field_value` (`id`, `price_field_id`, `name`, `label`, `description`, `amount`, `count`, `max_value`, `weight`, `membership_type_id`, `membership_num_terms`, `is_default`, `is_active`, `financial_type_id`, `non_deductible_amount`) VALUES (1, 1, 'contribution_amount', 'Contribution Amount', NULL, '1', NULL, NULL, 1, NULL, NULL, 0, 1, 1, 0.00)");
  }

  /**
   * Recreate default membership types.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function restoreMembershipTypes(): void {
    MembershipType::delete(FALSE)->addWhere('id', '>', 0)->execute();
    $this->quickCleanup(['civicrm_membership_type']);
    $this->ensureMembershipPriceSetExists();

    MembershipType::save(FALSE)
      ->setRecords(
        [
          [
            'name' => 'General',
            'description' => 'Regular annual membership.',
            'minimum_fee' => 100,
            'duration_unit' => 'year',
            'duration_interval' => 2,
            'period_type' => 'rolling',
            'relationship_type_id' => 7,
            'relationship_direction' => 'b_a',
            'visibility' => 'Public',
            'is_active' => 1,
            'weight' => 1,
          ],
          [
            'name' => 'Student',
            'description' => 'Discount membership for full-time students.',
            'minimum_fee' => 50,
            'duration_unit' => 1,
            'duration_interval' => 'year',
            'period_type' => 'rolling',
            'visibility' => 'Public',
          ],
          [
            'name' => 'Lifetime',
            'description' => 'Lifetime membership.',
            'minimum_fee' => 1200.00,
            'duration_unit' => 1,
            'duration_interval' => 'lifetime',
            'period_type' => 'rolling',
            'relationship_type_id' => 7,
            'relationship_direction' => 'b_a',
            'visibility' => 'Admin',
          ],
        ]
      )
      ->setDefaults([
        'domain_id' => 1,
        'member_of_contact_id' => 1,
        'financial_type_id' => 2,
      ]
    )->execute();
  }

  /*
   * Function does a 'Get' on the entity & compares the fields in the Params with those returned
   * Default behaviour is to also delete the entity
   * @param array $params
   *   Params array to check against.
   * @param int $id
   *   ID of the entity concerned.
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
   * @param string $entity
   * @param int $delete
   * @param string $errorText
   */
  public function getAndCheck(array $params, int $id, string $entity, int $delete = 1, string $errorText = ''): void {

    $result = $this->callAPISuccessGetSingle($entity, [
      'id' => $id,
      'return' => array_keys($params),
    ]);

    if ($delete) {
      $this->callAPISuccess($entity, 'Delete', [
        'id' => $id,
      ]);
    }
    $dateFields = $keys = $dateTimeFields = [];
    $fields = $this->callAPISuccess($entity, 'getfields', ['version' => 3, 'action' => 'get']);
    foreach ($fields['values'] as $field => $settings) {
      if (array_key_exists($field, $result)) {
        $keys[($settings['name'] ?? $field)] = $field;
      }
      else {
        $keys[($settings['name'] ?? $field)] = ($settings['name'] ?? $field);
      }
      $type = $settings['type'] ?? NULL;
      if ($type === CRM_Utils_Type::T_DATE) {
        $dateFields[] = $settings['name'];
        // we should identify both real names & unique names as dates
        if ($field !== $settings['name']) {
          $dateFields[] = $field;
        }
      }
      if ($type === CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME) {
        $dateTimeFields[] = $settings['name'];
        // we should identify both real names & unique names as dates
        if ($field !== $settings['name']) {
          $dateTimeFields[] = $field;
        }
      }
    }

    if (strtolower($entity) === 'contribution') {
      $params['receive_date'] = date('Y-m-d', strtotime($params['receive_date']));
      // this is not returned in id format
      unset($params['payment_instrument_id']);
      $params['contribution_source'] = $params['source'];
      unset($params['source']);
    }

    foreach ($params as $key => $value) {
      if ($key === 'version' || strpos($key, 'api') === 0 || (!array_key_exists($key, $keys) || !array_key_exists($keys[$key], $result))) {
        continue;
      }
      if (in_array($key, $dateFields, TRUE)) {
        $value = date('Y-m-d', strtotime($value));
        $result[$key] = date('Y-m-d', strtotime($result[$key]));
      }
      if (in_array($key, $dateTimeFields, TRUE)) {
        $value = date('Y-m-d H:i:s', strtotime($value));
        $result[$keys[$key]] = date('Y-m-d H:i:s', strtotime(($result[$keys[$key]] ?? $result[$key])));
      }
      $this->assertEquals($value, $result[$keys[$key]], $key . " GetandCheck function determines that for key {$key} value: " . print_r($value, TRUE) . " doesn't match " . print_r($result[$keys[$key]], TRUE) . $errorText);
    }
  }

  /**
   * Get formatted values in  the actual and expected result.
   *
   * @param array $actual
   *   Actual calculated values.
   * @param array $expected
   *   Expected values.
   */
  public function checkArrayEquals(array &$actual, array &$expected): void {
    $this->unsetID($actual);
    $this->unsetID($expected);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Unset the key 'id' from the array
   *
   * @param array $unformattedArray
   *   The array from which the 'id' has to be unset.
   */
  private function unsetID(array &$unformattedArray): void {
    $formattedArray = [];
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
        $formattedArray = [$value];
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
   *
   * @return string
   */
  public function createTempDir($prefix = 'test-') {
    $tempDir = CRM_Utils_File::tempdir($prefix);
    $this->tempDirs[] = $tempDir;
    return $tempDir;
  }

  public function cleanTempDirs() {
    foreach ($this->tempDirs as $tempDir) {
      if (is_dir($tempDir)) {
        CRM_Utils_File::cleanDir($tempDir, TRUE, FALSE);
      }
    }
  }

  /**
   * Temporarily replace the singleton extension with a different one.
   *
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
   *
   * @return void
   */
  public function setMockSettingsMetaData($extras) {
    CRM_Utils_Hook::singleton()
      ->setHook('civicrm_alterSettingsMetaData', function (&$metadata, $domainId, $profile) use ($extras) {
        $metadata = array_merge($metadata, $extras);
      });

    Civi::service('settings_manager')->flush();

    $fields = $this->callAPISuccess('setting', 'getfields', []);
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
   * Set up an acl allowing contact to see 2 specified groups
   *  - $this->_permissionedGroup & $this->_permissionedDisabledGroup
   *
   *  You need to have pre-created these groups & created the user e.g
   *  $this->createLoggedInUser();
   *   $this->_permissionedDisabledGroup = $this->groupCreate(array('title' =>
   * 'pick-me-disabled', 'is_active' => 0, 'name' => 'pick-me-disabled'));
   *   $this->_permissionedGroup = $this->groupCreate(array('title' =>
   * 'pick-me-active', 'is_active' => 1, 'name' => 'pick-me-active'));
   *
   * @param bool $isProfile
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function setupACL(bool $isProfile = FALSE): void {
    global $_REQUEST;
    $_REQUEST = $this->_params;

    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $optionGroupID = $this->callAPISuccessGetValue('option_group', ['return' => 'id', 'name' => 'acl_role']);
    $ov = new CRM_Core_DAO_OptionValue();
    $ov->option_group_id = $optionGroupID;
    $ov->value = 55;
    if ($ov->find(TRUE)) {
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_option_value WHERE id = {$ov->id}");
    }
    $this->callAPISuccess('option_value', 'create', [
      'option_group_id' => $optionGroupID,
      'label' => 'pick me',
      'value' => 55,
    ]);

    CRM_Core_DAO::executeQuery('
      TRUNCATE civicrm_acl_cache
    ');

    CRM_Core_DAO::executeQuery('
      TRUNCATE civicrm_acl_contact_cache
    ');

    // Setting ids is preferred.
    $permissionedGroup = $this->ids['Group']['permissioned_group'] ?? $this->_permissionedGroup;
    CRM_Core_DAO::executeQuery("
    INSERT INTO civicrm_acl_entity_role (
    `acl_role_id`, `entity_table`, `entity_id`, `is_active`
    ) VALUES (55, 'civicrm_group', $permissionedGroup, 1);
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
      'view picked', 'civicrm_group', $permissionedGroup , 'Edit', 'civicrm_group', {$this->_permissionedGroup}, 1
      );
      ");

      CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_acl (
      `name`, `entity_table`, `entity_id`, `operation`, `object_table`, `object_id`, `is_active`
      )
      VALUES (
      'view picked', 'civicrm_group',  $permissionedGroup, 'Edit', 'civicrm_group', {$this->_permissionedDisabledGroup}, 1
      );
      ");
    }

    $loggedInUser = CRM_Core_Session::singleton()->get('userID');
    $this->callAPISuccess('group_contact', 'create', [
      'group_id' => $permissionedGroup,
      'contact_id' => $loggedInUser,
    ]);

    if (!$isProfile) {
      CRM_ACL_BAO_Cache::resetCache();
    }
  }

  /**
   * Get the logged in user record.
   *
   * @return int|null
   */
  public function getLoggedInUser(): ?int {
    return CRM_Core_Session::singleton()->get('userID') ?: NULL;
  }

  /**
   * Alter default price set so that the field numbers are not all 1 (hiding errors)
   */
  public function offsetDefaultPriceSet() {
    $contributionPriceSet = $this->callAPISuccess('price_set', 'getsingle', ['name' => 'default_contribution_amount']);
    $firstID = $contributionPriceSet['id'];
    $this->callAPISuccess('price_set', 'create', [
      'id' => $contributionPriceSet['id'],
      'is_active' => 0,
      'name' => 'old',
    ]);
    unset($contributionPriceSet['id']);
    $newPriceSet = $this->callAPISuccess('price_set', 'create', $contributionPriceSet);
    $priceField = $this->callAPISuccess('price_field', 'getsingle', [
      'price_set_id' => $firstID,
      'options' => ['limit' => 1],
    ]);
    unset($priceField['id']);
    $priceField['price_set_id'] = $newPriceSet['id'];
    $newPriceField = $this->callAPISuccess('price_field', 'create', $priceField);
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'getsingle', [
      'price_set_id' => $firstID,
      'sequential' => 1,
      'options' => ['limit' => 1],
    ]);

    unset($priceFieldValue['id']);
    //create some padding to use up ids
    $this->callAPISuccess('price_field_value', 'create', $priceFieldValue);
    $this->callAPISuccess('price_field_value', 'create', $priceFieldValue);
    $this->callAPISuccess('price_field_value', 'create', array_merge($priceFieldValue, ['price_field_id' => $newPriceField['id']]));
  }

  /**
   * Create an instance of the paypal processor.
   *
   * @todo this isn't a great place to put it - but really it belongs on a class that extends
   * this parent class & we don't have a structure for that yet
   * There is another function to this effect on the PaypalPro test but it appears to be silently failing
   * & the best protection against that is the functions this class affords
   *
   * @param array $params
   * @param string $identifier
   *
   * @return int $result['id'] payment processor id
   */
  public function paymentProcessorCreate(array $params = [], string $identifier = 'test'): int {
    $params = array_merge([
      'title' => $params['name'] ?? 'demo',
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
      'payment_instrument_id:name' => 'Debit Card',
    ], $params);
    if (!is_numeric($params['payment_processor_type_id'])) {
      // really the api should handle this through getoptions but it's not exactly api call so lets just sort it
      //here
      $params['payment_processor_type_id'] = $this->callAPISuccess('payment_processor_type', 'getvalue', [
        'name' => $params['payment_processor_type_id'],
        'return' => 'id',
      ], 'integer');
    }
    $result = $this->createTestEntity('PaymentProcessor', $params, $identifier);
    return $result['id'];
  }

  /**
   * Get the rendered contents from a form.
   *
   * @param string $formName
   *
   * @return false|string
   */
  protected function getRenderedFormContents(string $formName) {
    $form = $this->getFormObject($formName);
    $form->buildForm();
    ob_start();
    $form->controller->_actions['display']->perform($form, 'display');
    return ob_get_clean();
  }

  /**
   * Set up initial recurring payment allowing subsequent IPN payments.
   *
   * @param array $recurParams (Optional)
   * @param array $contributionParams (Optional)
   * @param string $identifier
   */
  public function setupRecurringPaymentProcessorTransaction(array $recurParams = [], array $contributionParams = [], string $identifier = 'default'): void {
    if (empty($this->ids['Campaign'][$identifier]) && CRM_Core_Component::isEnabled('CiviCampaign')) {
      $this->createTestEntity('Campaign', ['title' => 'get the money', 'name' => 'money'], $identifier)['id'];
    }
    $contributionParams = array_merge([
      'total_amount' => '200',
      'invoice_id' => 'xyz',
      'financial_type_id' => 'Donation',
      'contact_id' => $this->ids['Contact']['individual_0'],
      'contribution_page_id' => $this->ids['ContributionPage'][0] ?? NULL,
      'payment_processor_id' => $this->ids['PaymentProcessor']['test'],
      'receive_date' => '2019-07-25 07:34:23',
      'skipCleanMoney' => TRUE,
      'amount_level' => 'expensive',
      'campaign_id' => $this->ids['Campaign'][$identifier] ?? NULL,
      'source' => 'Online Contribution: Page name',
    ], $contributionParams);
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array_merge([
      'contact_id' => $this->ids['Contact']['individual_0'],
      'amount' => 1000,
      'sequential' => 1,
      'installments' => 5,
      'frequency_unit' => 'Month',
      'frequency_interval' => 1,
      'contribution_status_id' => 2,
      'invoice_id' => $contributionParams['invoice_id'],
      'payment_processor_id' => $this->ids['PaymentProcessor']['test'],
      // processor provided ID - use contact ID as proxy.
      'processor_id' => $this->ids['Contact']['individual_0'],
      'api.Order.create' => $contributionParams,
    ], $recurParams))['values'][0];
    $this->ids['ContributionRecur'][$identifier] = $contributionRecur['id'];
    $this->ids['Contribution'][$identifier] = $this->ids['Contribution'][0] = $contributionRecur['api.Order.create']['id'];
  }

  /**
   * We don't have a good way to set up a recurring contribution with a membership so let's just do one then alter it
   *
   * @param array $params Optionally modify params for membership/recur (duration_unit/frequency_unit)
   * @param array $contributionParams Parameters to pass to contribution create.
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function setupMembershipRecurringPaymentProcessorTransaction(array $params = [], array $contributionParams = []): void {
    $membershipParams = $recurParams = [];
    if (!empty($params['duration_unit'])) {
      $membershipParams['duration_unit'] = $params['duration_unit'];
    }
    if (!empty($params['frequency_unit'])) {
      $recurParams['frequency_unit'] = $params['frequency_unit'];
    }

    $this->membershipTypeCreate($membershipParams);
    //create a contribution so our membership & contribution don't both have id = 1
    if ($this->callAPISuccess('Contribution', 'getcount') === 0) {
      $this->contributionCreate([
        'contact_id' => $this->ids['Contact']['individual_0'],
        'is_test' => 1,
        'financial_type_id' => 1,
        'invoice_id' => 'abcd',
        'trxn_id' => 345,
        'receive_date' => '2019-07-25 07:34:23',
      ]);
    }

    $this->setupRecurringPaymentProcessorTransaction($recurParams, array_merge($contributionParams, [
      'line_items' => [
        [
          'line_item' => [
            [
              'label' => 'General',
              'qty' => 1,
              'unit_price' => 200,
              'line_total' => 200,
              'financial_type_id' => 1,
              'membership_type_id' => $this->ids['MembershipType']['test'],
            ],
          ],
          'params' => [
            'contact_id' => $this->ids['Contact']['individual_0'],
            'membership_type_id' => $this->ids['MembershipType']['test'],
            'source' => 'Payment',
          ],
        ],
      ],
    ]));
    $this->ids['membership'] = LineItem::get()
      ->addWhere('contribution_id', '=', $this->ids['Contribution'][0])
      ->addWhere('entity_table', '=', 'civicrm_membership')
      ->addSelect('entity_id')
      ->execute()->first()['entity_id'];
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
  protected function assertAttachmentExistence(bool $exists, array $apiResult): void {
    $this->assertIsNumeric($apiResult['id']);
    $this->assertEquals($exists, file_exists($apiResult['values'][$apiResult['id']]['path']));
    $this->assertDBQuery((int) $exists, 'SELECT count(*) FROM civicrm_file WHERE id = %1', [
      1 => [$apiResult['id'], 'Int'],
    ]);
    $this->assertDBQuery((int) $exists, 'SELECT count(*) FROM civicrm_entity_file WHERE id = %1', [
      1 => [$apiResult['id'], 'Int'],
    ]);
  }

  /**
   * Assert 2 sql strings are the same, ignoring double spaces.
   *
   * @param string $expectedSQL
   * @param string $actualSQL
   * @param string $message
   */
  protected function assertLike(string $expectedSQL, string $actualSQL, string $message = 'different sql'): void {
    $expected = trim((preg_replace('/[ \r\n\t]+/', ' ', $expectedSQL)));
    $actual = trim((preg_replace('/[ \r\n\t]+/', ' ', $actualSQL)));
    $this->assertEquals($expected, $actual, $message);
  }

  /**
   * Create a price set for an event.
   *
   * @param float $feeTotal
   * @param float $minAmt
   * @param string $type
   *
   * @param array $options
   *
   * @return int
   *   Price Set ID.
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function eventPriceSetCreate(float $feeTotal, float $minAmt = 0, string $type = 'Text', array $options = [['name' => 'hundred', 'amount' => 100]]): int {
    $paramsSet['title'] = 'Price Set';
    $paramsSet['name'] = 'price_set';
    $paramsSet['is_active'] = FALSE;
    $paramsSet['extends'] = 1;
    $paramsSet['min_amount'] = $minAmt;

    $priceSetID = PriceSet::create(FALSE)->setValues($paramsSet)->execute()->first()['id'];

    $paramsField = [
      'label' => 'Price Field',
      'name' => 'price_field',
      'html_type' => $type,
      'price' => $feeTotal,
      'option_label' => ['1' => 'Price Field'],
      'option_value' => ['1' => $feeTotal],
      'option_name' => ['1' => $feeTotal],
      'option_weight' => ['1' => 1],
      'option_amount' => ['1' => 1],
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_active' => ['1' => 1],
      'price_set_id' => $priceSetID,
      'is_enter_qty' => 1,
      'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'financial_type_id', 'Event Fee'),
    ];
    if ($type === 'Radio') {
      foreach ($options as $index => $option) {
        $paramsField['is_enter_qty'] = 0;
        $optionID = $index + 2;
        $paramsField['option_value'][$optionID] = $paramsField['option_weight'][$optionID] = $paramsField['option_amount'][$optionID] = $option['amount'];
        $paramsField['option_label'][$optionID] = $paramsField['option_name'][$optionID] = $option['name'];
      }

    }
    $priceFieldID = $this->callAPISuccess('PriceField', 'create', $paramsField)['id'];
    $this->ids['PriceField']['event_' . strtolower($type)] = $priceFieldID;
    $fieldValues = $this->callAPISuccess('PriceFieldValue', 'get', ['price_field_id' => $priceFieldID])['values'];
    foreach ($fieldValues as $priceFieldValue) {
      $this->ids['PriceFieldValue'][strtolower($priceFieldValue['name'])] = $priceFieldValue['id'];
    }
    return $priceSetID;
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
   *
   * @throws \CRM_Core_Exception
   */
  protected function createPartiallyPaidParticipantOrder(): array {
    $orderParams = $this->getParticipantOrderParams();
    $orderParams['api.Payment.create'] = ['total_amount' => 150];
    return $this->callAPISuccess('Order', 'create', $orderParams);
  }

  /**
   * Create price set that includes one price field with two option values.
   *
   * @param string $component
   * @param int|null $componentID
   * @param array $priceFieldOptions
   * @param string $identifier
   *
   * @return array - the result of API3 PriceFieldValue.get for the new PriceField
   */
  protected function createPriceSet(string $component = 'contribution_page', ?int $componentID = NULL, array $priceFieldOptions = [], $identifier = 'price_set_test'): array {
    $paramsSet['title'] = 'Price Set' . $identifier;
    $paramsSet['name'] = $identifier;
    $paramsSet['is_active'] = TRUE;
    $paramsSet['financial_type_id:name'] = 'Event Fee';
    $paramsSet['extends'] = 1;
    $priceSet = $this->createTestEntity('PriceSet', $paramsSet, $identifier);
    if ($componentID) {
      CRM_Price_BAO_PriceSet::addTo('civicrm_' . $component, $componentID, $priceSet['id']);
    }
    $paramsField = array_merge([
      'label' => 'Price Field',
      'name' => 'Price_Field',
      'html_type' => 'CheckBox',
      'option_label' => ['1' => 'Price Field 1', '2' => 'Price Field 2'],
      'option_value' => ['1' => 100, '2' => 200],
      'option_name' => ['1' => 'Price Field 1', '2' => 'Price Field 2'],
      'option_weight' => ['1' => 1, '2' => 2],
      'option_amount' => ['1' => 100, '2' => 200],
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_active' => ['1' => 1, '2' => 1],
      'price_set_id' => $priceSet['id'],
      'is_enter_qty' => 1,
      'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Price_BAO_PriceSet', 'financial_type_id', 'Event Fee'),
    ], $priceFieldOptions);

    $priceField = $this->callAPISuccess('PriceField', 'create', $paramsField);
    $this->ids['PriceField'][0] = $priceField['id'];
    $this->ids['PriceFieldValue'] = array_keys((array) PriceFieldValue::get()
      ->addWhere('price_field_id', '=', $this->ids['PriceField'][0])
      ->execute()->indexBy('id'));
    return $this->callAPISuccess('PriceFieldValue', 'get', ['price_field_id' => $this->ids['PriceField'][0]]);
  }

  /**
   * Replace the template with a test-oriented template designed to show all the variables.
   *
   * @param string $templateName
   * @param string $input
   * @param string $type
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function swapMessageTemplateForInput(string $templateName, string $input, string $type = 'html'): void {
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_msg_template
      SET msg_{$type} = %1
      WHERE workflow_name = '{$templateName}'
      AND is_default = 1", [1 => [$input, 'String']]
    );
  }

  /**
   * Replace the template with a test-oriented template designed to show all the variables.
   *
   * @param string $templateName
   * @param string $type
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function swapMessageTemplateForTestTemplate($templateName = 'contribution_online_receipt', $type = 'html'): void {
    $testTemplate = file_get_contents(__DIR__ . '/../../templates/message_templates/' . $templateName . '_' . $type . '.tpl');
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_msg_template
      SET msg_{$type} = %1
      WHERE workflow_name = '{$templateName}'
      AND is_default = 1", [1 => [$testTemplate, 'String']]
    );
  }

  /**
   * Reinstate the default template.
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function revertTemplateToReservedTemplate(): void {
    CRM_Core_DAO::executeQuery('
      UPDATE civicrm_msg_template m
      INNER JOIN civicrm_msg_template m2
        ON m2.workflow_name = m.workflow_name AND m2.is_reserved = 1
        AND m.is_default = 1
      SET m.msg_html = m2.msg_html, m.msg_text = m2.msg_text
    ');
  }

  /**
   * Flush statics relating to financial type.
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function flushFinancialTypeStatics(): void {
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
  }

  /**
   * Set the permissions to the supplied array.
   *
   * @param array $permissions
   */
  protected function setPermissions(array $permissions): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = $permissions;
    $this->flushFinancialTypeStatics();
  }

  /**
   * @param array $params
   * @param $context
   */
  public function _checkFinancialRecords($params, $context) {
    $entityParams = [
      'entity_id' => $params['id'],
      'entity_table' => 'civicrm_contribution',
    ];
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', [
      'id' => $params['id'],
      'return' => ['total_amount', 'fee_amount', 'net_amount'],
    ]);
    $this->assertEquals($contribution['total_amount'] - $contribution['fee_amount'], $contribution['net_amount']);
    if ($context === 'pending') {
      $trxn = $this->retrieveEntityFinancialTrxn($entityParams);
      $this->assertNull($trxn, 'No Trxn to be created until IPN callback');
      return;
    }
    $trxn = current($this->retrieveEntityFinancialTrxn($entityParams));
    $trxnParams = [
      'id' => $trxn['financial_trxn_id'],
    ];
    if ($context !== 'online' && $context !== 'payLater') {
      $compareParams = [
        'to_financial_account_id' => 6,
        'total_amount' => (float) ($params['total_amount'] ?? 100.00),
        'status_id' => 1,
      ];
    }
    if ($context === 'feeAmount') {
      $compareParams['fee_amount'] = 50;
    }
    elseif ($context === 'online') {
      $compareParams = [
        'to_financial_account_id' => 12,
        'total_amount' => (float) ($params['total_amount'] ?? 100.00),
        'status_id' => 1,
        'payment_instrument_id' => $params['payment_instrument_id'] ?? 1,
      ];
    }
    elseif ($context === 'payLater') {
      $compareParams = [
        'to_financial_account_id' => 7,
        'total_amount' => (float) ($params['total_amount'] ?? 100.00),
        'status_id' => 2,
      ];
    }
    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $trxnParams, $compareParams);
    $entityParams = [
      'financial_trxn_id' => $trxn['financial_trxn_id'],
      'entity_table' => 'civicrm_financial_item',
    ];
    $entityTrxn = current($this->retrieveEntityFinancialTrxn($entityParams));
    $fitemParams = [
      'id' => $entityTrxn['entity_id'],
    ];
    $compareParams = [
      'amount' => (float) ($params['total_amount'] ?? 100.00),
      'status_id' => 1,
      'financial_account_id' => $params['financial_account_id'] ?? 1,
    ];
    if ($context === 'payLater') {
      $compareParams = [
        'amount' => (float) ($params['total_amount'] ?? 100.00),
        'status_id' => 3,
        'financial_account_id' => $params['financial_account_id'] ?? 1,
      ];
    }
    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $fitemParams, $compareParams);
    if ($context == 'feeAmount') {
      $maxParams = [
        'entity_id' => $params['id'],
        'entity_table' => 'civicrm_contribution',
      ];
      $maxTrxn = current($this->retrieveEntityFinancialTrxn($maxParams, TRUE));
      $trxnParams = [
        'id' => $maxTrxn['financial_trxn_id'],
      ];
      $compareParams = [
        'to_financial_account_id' => 5,
        'from_financial_account_id' => 6,
        'total_amount' => 50,
        'status_id' => 1,
      ];
      $transaction = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($params['id'], 'DESC');
      $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $trxnParams, $compareParams);
      $fitemParams = [
        'entity_id' => $transaction['financialTrxnId'],
        'entity_table' => 'civicrm_financial_trxn',
      ];
      $compareParams = [
        'amount' => 50.00,
        'status_id' => 1,
        'financial_account_id' => 5,
      ];
      $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $fitemParams, $compareParams);
    }
    // This checks that empty Sales tax rows are not being created. If for any reason it needs to be removed the
    // line should be copied into all the functions that call this function & evaluated there
    // Be really careful not to remove or bypass this without ensuring stray rows do not re-appear
    // when calling completeTransaction or repeatTransaction.
    $this->callAPISuccessGetCount('FinancialItem', ['description' => 'Sales Tax', 'amount' => 0], 0);
  }

  /**
   * Return financial type id on basis of name
   *
   * @param string $name Financial type m/c name
   *
   * @return int
   */
  public function getFinancialTypeID(string $name): int {
    return CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'financial_type_id', $name);
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
        civicrm_api3($entity, 'delete', ['id' => $id, 'skip_undelete' => 1]);
      }
      catch (CRM_Core_Exception $e) {
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
  protected function createFinancialType(array $params = []): array {
    return $this->callAPISuccess('FinancialType', 'create', array_merge($params,
      [
        'name' => 'Financial-Type - new',
        'is_active' => 1,
      ]
    ));
  }

  /**
   * Enable Tax and Invoicing
   *
   * @param array $params
   */
  protected function enableTaxAndInvoicing(array $params = []): void {
    // Enable component contribute setting
    $contributeSetting = array_merge($params,
      [
        'invoicing' => 1,
        'invoice_prefix' => 'INV_',
        'invoice_due_date' => 10,
        'invoice_due_date_period' => 'days',
        'invoice_notes' => '',
        'invoice_is_email_pdf' => 1,
        'tax_term' => 'Sales Tax',
        'tax_display_settings' => 'Inclusive',
      ]
    );
    foreach ($contributeSetting as $setting => $value) {
      Civi::settings()->set($setting, $value);
    }
  }

  /**
   * Enable Tax and Invoicing
   */
  protected function disableTaxAndInvoicing(): void {
    $accounts = $this->callAPISuccess('EntityFinancialAccount', 'get', ['account_relationship' => 'Sales Tax Account is'])['values'];
    foreach ($accounts as $account) {
      $this->callAPISuccess('EntityFinancialAccount', 'delete', ['id' => $account['id']]);
      $this->callAPISuccess('FinancialAccount', 'delete', ['id' => $account['financial_account_id']]);
    }

    if (!empty(\Civi::$statics['CRM_Core_PseudoConstant']) && isset(\Civi::$statics['CRM_Core_PseudoConstant']['taxRates'])) {
      unset(\Civi::$statics['CRM_Core_PseudoConstant']['taxRates']);
    }
    Civi::settings()->set('invoice_is_email_pdf', FALSE);
    Civi::settings()->set('invoicing', FALSE);
  }

  /**
   * Add Sales Tax Account for the financial type.
   *
   * @param int $financialTypeID
   *
   * @param array $accountParams
   *
   * @return CRM_Financial_DAO_EntityFinancialAccount
   *
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function addTaxAccountToFinancialType(int $financialTypeID, array $accountParams = []): CRM_Financial_DAO_EntityFinancialAccount {
    Civi::settings()->set('invoicing', TRUE);
    unset(\Civi::$statics['CRM_Price_BAO_PriceField']);
    $params = array_merge([
      'name' => 'Sales tax account - test - ' . $financialTypeID,
      'financial_account_type_id' => key(CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name LIKE 'Liability' ")),
      'is_deductible' => 1,
      'is_tax' => 1,
      'tax_rate' => 10,
      'is_active' => 1,
    ], $accountParams);
    $financialAccountID = FinancialAccount::create()->setValues($params)->execute()->first()['id'];
    $entityParams = [
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialTypeID,
      'account_relationship' => key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Sales Tax Account is' ")),
    ];

    // set tax rate (as 10) for provided financial type ID to static variable, later used to fetch tax rates of all financial types
    \Civi::$statics['CRM_Core_PseudoConstant']['taxRates'][$financialTypeID] = $params['tax_rate'];

    //CRM-20313: As per unique index added in civicrm_entity_financial_account table,
    //  first check if there's any record on basis of unique key (entity_table, account_relationship, entity_id)
    $dao = new CRM_Financial_DAO_EntityFinancialAccount();
    $dao->copyValues($entityParams);
    $dao->find();
    if ($dao->fetch()) {
      $entityParams['id'] = $dao->id;
    }
    $entityParams['financial_account_id'] = $financialAccountID;

    return CRM_Financial_BAO_EntityFinancialAccount::add($entityParams);
  }

  /**
   * Create price set with contribution test for test setup.
   *
   * This could be merged with 4.5 function setup in api_v3_ContributionPageTest::setUpContributionPage
   * on parent class at some point.
   */
  public function createPriceSetWithPage(): void {
    try {
      $membershipTypeID = $this->createTestEntity('MembershipType', [
        'name' => 'Special',
        'member_of_contact_id' => CRM_Core_BAO_Domain::getDomain()->contact_id,
        'financial_type_id:name' => 'Member Dues',
        'duration_unit' => 'year',
        'period_type:name' => 'rolling',
      ], 'special')['id'];
      $contributionPageID = $this->createTestEntity('ContributionPage', [
        'title' => 'Test Contribution Page',
        'financial_type_id' => 1,
        'currency' => 'NZD',
        'goal_amount' => 50,
        'is_pay_later' => 1,
        'is_monetary' => TRUE,
        'is_email_receipt' => FALSE,
      ])['id'];
      $priceSetID = $this->createTestEntity('PriceSet', [
        'is_quick_config' => 0,
        'extends' => 'CiviMember',
        'financial_type_id' => 1,
        'title' => 'my Page',
        'name' => 'member_not_quick_config',
      ])['id'];

      $this->createTestEntity('PriceSetEntity', ['entity_table' => 'civicrm_contribution_page', 'entity_id' => $contributionPageID, 'price_set_id' => $priceSetID]);
      $priceField = $this->createTestEntity('PriceField', [
        'price_set_id' => $priceSetID,
        'label' => 'Goat Breed',
        'html_type' => 'Radio',
        'name' => 'goat_breed',
      ]);
      $addOnPriceField = $this->createTestEntity('PriceField', [
        'price_set_id' => $priceSetID,
        'label' => 'Goat Addons',
        'html_type' => 'CheckBox',
        'name' => 'goat_addons',
      ], 'addon');
      $this->createTestEntity('PriceFieldValue', [
        'price_set_id' => $priceSetID,
        'price_field_id' => $priceField['id'],
        'label' => 'Long Haired Goat',
        'amount' => 20,
        'financial_type_id:name' => 'Donation',
        'membership_type_id' => $membershipTypeID,
        'membership_num_terms' => 1,
      ], 'one_term_membership');
      $this->createTestEntity('PriceFieldValue', [
        'price_set_id' => $priceSetID,
        'price_field_id' => $priceField['id'],
        'label' => 'Shoe-eating Goat',
        'amount' => 10,
        'financial_type_id:name' => 'Donation',
        'membership_type_id' => $membershipTypeID,
        'membership_num_terms' => 2,
      ], 'two_term_membership');

      $this->createTestEntity('PriceFieldValue', [
        'price_set_id' => $priceSetID,
        'price_field_id' => $priceField['id'],
        'label' => 'Shoe-eating Goat',
        'amount' => 10,
        'financial_type_id:name' => 'Donation',
      ], 'donation');
      $this->createTestEntity('MembershipBlock', [
        'entity_id' => $contributionPageID,
        'entity_table' => 'civicrm_contribution_page',
        'is_separate_payment' => FALSE,
      ]);
      $this->createTestEntity('PriceFieldValue', [
        'price_set_id' => $priceSetID,
        'price_field_id' => $addOnPriceField['id'],
        'label' => 'Straw',
        'amount' => 5,
        'financial_type_id:name' => 'Donation',
      ], 'straw');
      $this->createTestEntity('PriceFieldValue', [
        'price_set_id' => $priceSetID,
        'price_field_id' => $addOnPriceField['id'],
        'label' => 'Feed',
        'amount' => 30,
        'financial_type_id:name' => 'Donation',
      ], 'feed');
    }
    catch (CRM_Core_Exception $e) {
      $this->fail($e->getMessage());
    }
  }

  /**
   * @implements CRM_Utils_Hook::selectWhereClause
   *
   * @param string $entity
   * @param array $clauses
   */
  public function selectWhereClauseHook(string $entity, array &$clauses): void {
    if ($entity === 'Event') {
      $clauses['event_type_id'][] = 'IN (2, 3, 4)';
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
    if ($op === 'create' && $objectName === 'Individual') {
      CRM_Core_DAO::executeQuery(
        "UPDATE civicrm_contact SET nick_name = 'munged' WHERE id = %1",
        [
          1 => [$objectId, 'Integer'],
        ]
      );
    }

    if ($op === 'edit' && $objectName === 'Participant') {
      $params = [
        1 => [$objectId, 'Integer'],
      ];
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
   * @param array $formValues
   *
   * @param array $urlParameters
   *
   * @return \CRM_Core_Form|CRM_Event_Form_Registration_Register
   *
   * @noinspection PhpReturnDocTypeMismatchInspection
   * @throws \CRM_Core_Exception
   */
  public function getFormObject(string $class, array $formValues = [], array $urlParameters = []) {
    $_POST = $formValues;
    /** @var CRM_Core_Form $form */
    $form = new $class();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_REQUEST += $urlParameters;
    switch ($class) {
      case 'CRM_Event_Cart_Form_Checkout_Payment':
      case 'CRM_Event_Cart_Form_Checkout_ParticipantsAndPrices':
        $form->controller = new CRM_Event_Cart_Controller_Checkout();
        break;

      case 'CRM_Event_Form_Registration_Register':
        $form->controller = $this->formController = new CRM_Event_Controller_Registration();
        break;

      case 'CRM_Event_Form_Registration_Confirm':
      case 'CRM_Event_Form_Registration_AdditionalParticipant':
        if ($this->formController) {
          // Add to the existing form controller.
          $form->controller = $this->formController;
        }
        else {
          $form->controller = $this->formController = new CRM_Event_Controller_Registration();
        }
        break;

      case 'CRM_Contribute_Form_Contribution_Main':
        $form->controller = new CRM_Contribute_Controller_Contribution();
        break;

      case 'CRM_Contribute_Form_Contribution_Confirm':
      case 'CRM_Contribute_Form_Contribution_ThankYou':
        $form->controller = new CRM_Contribute_Controller_Contribution();
        $form->controller->setStateMachine(new CRM_Contribute_StateMachine_Contribution($form->controller));
        // The submitted values are on the Main form.
        $_SESSION['_' . $form->controller->_name . '_container']['values']['Main'] = $formValues;
        return $form;

      case 'CRM_Contact_Import_Form_DataSource':
      case 'CRM_Contact_Import_Form_MapField':
      case 'CRM_Contact_Import_Form_Preview':
        $form->controller = new CRM_Import_Controller('import contacts', ['entity' => 'Contact']);
        $form->controller->setStateMachine(new CRM_Core_StateMachine($form->controller));
        // The submitted values should be set on one or the other of the forms in the flow.
        // For test simplicity we set on all rather than figuring out which ones go where....
        $_SESSION['_' . $form->controller->_name . '_container']['values']['DataSource'] = $formValues;
        $_SESSION['_' . $form->controller->_name . '_container']['values']['MapField'] = $formValues;
        $_SESSION['_' . $form->controller->_name . '_container']['values']['Preview'] = $formValues;
        return $form;

      case 'CRM_Contribute_Import_Form_DataSource':
      case 'CRM_Contribute_Import_Form_MapField':
      case 'CRM_Contribute_Import_Form_Preview':
        if ($this->formController) {
          // Add to the existing form controller.
          $form->controller = $this->formController;
        }
        else {
          $form->controller = new CRM_Import_Controller('import contributions', ['entity' => 'Contribution']);
          $form->controller->setStateMachine(new CRM_Core_StateMachine($form->controller));
          $this->formController = $form->controller;
        }
        // The submitted values should be set on one or the other of the forms in the flow.
        // For test simplicity we set on all rather than figuring out which ones go where....
        $_SESSION['_' . $form->controller->_name . '_container']['values']['DataSource'] = $formValues;
        $_SESSION['_' . $form->controller->_name . '_container']['values']['MapField'] = $formValues;
        $_SESSION['_' . $form->controller->_name . '_container']['values']['Preview'] = $formValues;
        return $form;

      case 'CRM_Member_Import_Form_DataSource':
      case 'CRM_Member_Import_Form_MapField':
      case 'CRM_Member_Import_Form_Preview':
        $form->controller = new CRM_Import_Controller('import memberships', ['entity' => 'Membership']);
        $form->controller->setStateMachine(new CRM_Core_StateMachine($form->controller));
        // The submitted values should be set on one or the other of the forms in the flow.
        // For test simplicity we set on all rather than figuring out which ones go where....
        $_SESSION['_' . $form->controller->_name . '_container']['values']['DataSource'] = $formValues;
        $_SESSION['_' . $form->controller->_name . '_container']['values']['MapField'] = $formValues;
        $_SESSION['_' . $form->controller->_name . '_container']['values']['Preview'] = $formValues;
        return $form;

      case 'CRM_Event_Import_Form_DataSource':
      case 'CRM_Event_Import_Form_MapField':
      case 'CRM_Event_Import_Form_Preview':
        $form->controller = new CRM_Import_Controller('import participants', ['entity' => 'Participant']);
        $form->controller->setStateMachine(new CRM_Core_StateMachine($form->controller));
        // The submitted values should be set on one or the other of the forms in the flow.
        // For test simplicity we set on all rather than figuring out which ones go where....
        $_SESSION['_' . $form->controller->_name . '_container']['values']['DataSource'] = $formValues;
        $_SESSION['_' . $form->controller->_name . '_container']['values']['MapField'] = $formValues;
        $_SESSION['_' . $form->controller->_name . '_container']['values']['Preview'] = $formValues;
        return $form;

      case 'CRM_Activity_Import_Form_DataSource':
      case 'CRM_Activity_Import_Form_MapField':
      case 'CRM_Activity_Import_Form_Preview':
        $form->controller = new CRM_Import_Controller('import contributions', ['entity' => 'Activity']);
        $form->controller->setStateMachine(new CRM_Core_StateMachine($form->controller));
        // The submitted values should be set on one or the other of the forms in the flow.
        // For test simplicity we set on all rather than figuring out which ones go where....
        $_SESSION['_' . $form->controller->_name . '_container']['values']['DataSource'] = $formValues;
        $_SESSION['_' . $form->controller->_name . '_container']['values']['MapField'] = $formValues;
        $_SESSION['_' . $form->controller->_name . '_container']['values']['Preview'] = $formValues;
        return $form;

      case 'CRM_Custom_Import_Form_DataSource':
      case 'CRM_Custom_Import_Form_MapField':
      case 'CRM_Custom_Import_Form_Preview':
        $form->controller = new CRM_Import_Controller('import custom data', ['class_prefix' => 'CRM_Custom_Import']);
        $form->controller->setStateMachine(new CRM_Core_StateMachine($form->controller));
        // The submitted values should be set on one or the other of the forms in the flow.
        // For test simplicity we set on all rather than figuring out which ones go where....
        $_SESSION['_' . $form->controller->_name . '_container']['values']['DataSource'] = $formValues;
        $_SESSION['_' . $form->controller->_name . '_container']['values']['MapField'] = $formValues;
        $_SESSION['_' . $form->controller->_name . '_container']['values']['Preview'] = $formValues;
        return $form;

      case strpos($class, 'Search') !== FALSE:
        $form->controller = new CRM_Contact_Controller_Search();
        break;

      case strpos($class, '_Form_') !== FALSE:
        $form->controller = new CRM_Core_Controller_Simple($class, $form->getName());
        break;

      default:
        $form->controller = new CRM_Core_Controller();
    }

    $form->controller->setStateMachine(new CRM_Core_StateMachine($form->controller));
    $_SESSION['_' . $form->controller->_name . '_container']['values'][$form->getName()] = $formValues;
    if (isset($formValues['_qf_button_name'])) {
      $_SESSION['_' . $form->controller->_name . '_container']['_qf_button_name'] = $formValues['_qf_button_name'];
    }
    return $form;
  }

  /**
   * Instantiate form object.
   *
   * We need to instantiate the form to run preprocess, which means we have to trick it about the request method.
   *
   * @param string $class
   *   Name of form class.
   *
   * @param array $formValues
   * @param string|null $pageName
   * @param array $searchFormValues
   *   Values for the search form if the form is a task eg.
   *   for selected ids 6 & 8:
   *   [
   *      'radio_ts' => 'ts_sel',
   *      'task' => CRM_Member_Task::PDF_LETTER,
   *      'mark_x_6' => 1,
   *      'mark_x_8' => 1,
   *   ]
   *
   * @return \CRM_Core_Form
   *
   * @noinspection PhpReturnDocTypeMismatchInspection
   */
  public function getSearchFormObject(string $class, array $formValues = [], ?string $pageName = 'Search', array $searchFormValues = []) {
    $_POST = $formValues;
    /** @var CRM_Core_Form $form */
    $form = new $class();
    $pageName = $pageName ?: $form->getName();
    if (strpos($class, 'Search') !== FALSE) {
      $form->controller = new CRM_Contact_Controller_Search();
    }
    else {
      $form->controller = new CRM_Core_Controller_Simple($class, $pageName);
    }
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SESSION['_' . $form->controller->_name . '_container']['values']['Search'] = $searchFormValues;
    $form->controller->setStateMachine(new CRM_Core_StateMachine($form->controller));
    $_SESSION['_' . $form->controller->_name . '_container']['values'][$pageName] = $formValues;
    return $form;
  }

  /**
   * Get possible thousand separators.
   *
   * @return array
   */
  public function getThousandSeparators(): array {
    return [['.'], [',']];
  }

  /**
   * Get the boolean options as a provider.
   *
   * @return array
   */
  public function getBooleanDataProvider(): array {
    return [[TRUE], [FALSE]];
  }

  /**
   * Set the separators for thousands and decimal points.
   *
   * Note that this only covers some common scenarios.
   *
   * Most notably it tests our legacy way of setting currency separators.
   *
   * It is now preferred to use the locale instead. This test disables partial_locales
   * in order to test our legacy method.
   *
   * @param string $thousandSeparator
   */
  protected function setCurrencySeparators(string $thousandSeparator): void {
    Civi::settings()->set('partial_locales', FALSE);
    Civi::settings()->set('monetaryThousandSeparator', $thousandSeparator);
    Civi::settings()->set('monetaryDecimalPoint', ($thousandSeparator === ',' ? '.' : ','));
  }

  /**
   * Sets the thousand separator.
   *
   * If you use this function also set the decimal separator: setMonetaryDecimalSeparator
   *
   * @param $thousandSeparator
   */
  protected function setMonetaryThousandSeparator($thousandSeparator): void {
    Civi::settings()->set('monetaryThousandSeparator', $thousandSeparator);
  }

  /**
   * Sets the decimal separator.
   *
   * If you use this function also set the thousand separator setMonetaryDecimalPoint
   *
   * @param string $decimalPoint
   */
  protected function setMonetaryDecimalPoint(string $decimalPoint): void {
    Civi::settings()->set('monetaryDecimalPoint', $decimalPoint);
  }

  /**
   * Sets the default currency.
   *
   * @param string $currency
   */
  protected function setDefaultCurrency(string $currency): void {
    Civi::settings()->set('defaultCurrency', $currency);
  }

  /**
   * Format money as it would be input.
   *
   * @param string $amount
   *
   * @return string
   */
  protected function formatMoneyInput($amount): string {
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
   * Setup or clean up SMS tests
   *
   * @param bool $teardown
   *
   * @throws \CRM_Core_Exception
   */
  public function setupForSmsTests($teardown = FALSE) {
    require_once 'CiviTest/CiviTestSMSProvider.php';

    // Option value params for CiviTestSMSProvider
    $groupID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'sms_provider_name', 'id', 'name');
    $params = [
      'option_group_id' => $groupID,
      'label' => 'unittestSMS',
      'value' => 'unit.test.sms',
      'name' => 'CiviTestSMSProvider',
      'is_default' => 1,
      'is_active' => 1,
      'version' => 3,
    ];

    if ($teardown) {
      // Test completed, delete provider
      $providerOptionValueResult = civicrm_api3('option_value', 'get', $params);
      civicrm_api3('option_value', 'delete', ['id' => $providerOptionValueResult['id']]);
      return;
    }

    // Create an SMS provider "CiviTestSMSProvider". Civi handles "CiviTestSMSProvider" as a special case and allows it to be instantiated
    //  in CRM/Sms/Provider.php even though it is not an extension.
    return civicrm_api3('option_value', 'create', $params);
  }

  /**
   * Start capturing browser output.
   *
   * The starts the process of browser output being captured, setting any variables needed for e-notice prevention.
   */
  protected function startCapturingOutput() {
    ob_start();
    $_SERVER['HTTP_USER_AGENT'] = 'unittest';
  }

  /**
   * Stop capturing browser output and return as a csv.
   *
   * @param bool $isFirstRowHeaders
   *
   * @return \League\Csv\Reader
   *
   * @throws \League\Csv\Exception
   */
  protected function captureOutputToCSV($isFirstRowHeaders = TRUE) {
    $output = ob_get_flush();
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $output);
    rewind($stream);
    $this->assertEquals("\xEF\xBB\xBF", substr($output, 0, 3));
    $csv = Reader::createFromString($output);
    if ($isFirstRowHeaders) {
      $csv->setHeaderOffset(0);
    }
    ob_clean();
    return $csv;
  }

  /**
   * Rename various labels to not match the names.
   *
   * Doing these mimics the fact the name != the label in international installs & triggers failures in
   * code that expects it to.
   */
  protected function renameLabels() {
    $replacements = ['Pending', 'Refunded'];
    foreach ($replacements as $name) {
      CRM_Core_DAO::executeQuery("UPDATE civicrm_option_value SET label = '{$name} Label**' where label = '{$name}' AND name = '{$name}'");
    }
  }

  /**
   * Undo any label renaming.
   */
  protected function resetLabels() {
    CRM_Core_DAO::executeQuery("UPDATE civicrm_option_value SET label = REPLACE(name, ' Label**', '') WHERE label LIKE '% Label**'");
  }

  /**
   * Get parameters to set up a multi-line participant order.
   *
   * @return array
   */
  protected function getParticipantOrderParams(): array {
    $this->eventCreatePaid();
    $contactID = $this->individualCreate();
    return [
      'total_amount' => 300,
      'currency' => 'USD',
      'contact_id' => $contactID,
      'financial_type_id' => 4,
      'line_items' => [
        [
          'line_item' => [
            [
              'price_field_id' => $this->ids['PriceField']['PaidEvent'],
              'price_field_value_id' => $this->ids['PriceFieldValue']['PaidEvent_student'],
              'qty' => 1,
              'unit_price' => 100,
              'line_total' => 100,
              'entity_table' => 'civicrm_participant',
            ],
          ],
          'params' => [
            'financial_type_id' => 4,
            'event_id' => $this->getEventID('PaidEvent'),
            'role_id' => 1,
            'status_id' => 14,
            'fee_currency' => 'USD',
            'contact_id' => $this->individualCreate(),
          ],
        ],
        [
          'line_item' => [
            [
              'price_field_id' => $this->ids['PriceField']['PaidEvent'],
              'price_field_value_id' => $this->ids['PriceFieldValue']['PaidEvent_student_plus'],
              'qty' => 1,
              'unit_price' => 200,
              'line_total' => 200,
              'entity_table' => 'civicrm_participant',
            ],
          ],
          'params' => [
            'financial_type_id' => 4,
            'event_id' => $this->getEventID('PaidEvent'),
            'role_id' => 1,
            'status_id' => 14,
            'fee_currency' => 'USD',
            'contact_id' => $contactID,
          ],
        ],
      ],
    ];
  }

  /**
   * @param $payments
   *
   * @throws \CRM_Core_Exception
   */
  protected function validatePayments($payments): void {
    foreach ($payments as $payment) {
      $balance = CRM_Contribute_BAO_Contribution::getContributionBalance($payment['contribution_id']);
      if ($balance < 0 && $balance + $payment['total_amount'] === 0.0) {
        // This is an overpayment situation. there are no financial items to allocate the overpayment.
        // This is a pretty rough way at guessing which payment is the overpayment - but
        // for the test suite it should be enough.
        continue;
      }
      $items = $this->callAPISuccess('EntityFinancialTrxn', 'get', [
        'financial_trxn_id' => $payment['id'],
        'entity_table' => 'civicrm_financial_item',
        'return' => ['amount'],
      ])['values'];
      $itemTotal = 0;
      foreach ($items as $item) {
        $itemTotal += $item['amount'];
      }
      $this->assertEquals(round((float) $payment['total_amount'], 2), round($itemTotal, 2));
    }
  }

  /**
   * Validate all created payments.
   *
   * @throws \CRM_Core_Exception
   */
  protected function validateAllPayments(): void {
    $payments = $this->callAPISuccess('Payment', 'get', [
      'return' => ['total_amount', 'tax_amount'],
      'options' => ['limit' => 0],
      'version' => 3,
    ])['values'];
    $this->validatePayments($payments);
  }

  /**
   * Validate all created contributions.
   *
   * @throws \CRM_Core_Exception
   */
  protected function validateAllContributions(): void {
    $contributions = Contribution::get(FALSE)->setSelect(['total_amount', 'tax_amount'])->execute();
    foreach ($contributions as $contribution) {
      $lineItems = $this->callAPISuccess('LineItem', 'get', [
        'contribution_id' => $contribution['id'],
        'return' => ['tax_amount', 'line_total', 'entity_table', 'entity_id', 'qty'],
      ])['values'];

      $total = 0;
      $taxTotal = 0;
      $memberships = [];
      $participants = [];
      foreach ($lineItems as $lineItem) {
        $total += $lineItem['line_total'];
        $taxTotal += (float) ($lineItem['tax_amount']);
        if ($lineItem['entity_table'] === 'civicrm_membership') {
          $memberships[] = $lineItem['entity_id'];
        }
        if ($lineItem['entity_table'] === 'civicrm_participant' && $lineItem['qty'] > 0) {
          $participants[$lineItem['entity_id']] = $lineItem['entity_id'];
        }
      }
      $membershipPayments = $this->callAPISuccess('MembershipPayment', 'get', ['contribution_id' => $contribution['id'], 'return' => 'membership_id', 'version' => 3])['values'];
      $participantPayments = $this->callAPISuccess('ParticipantPayment', 'get', ['contribution_id' => $contribution['id'], 'return' => 'participant_id', 'version' => 3])['values'];
      $this->assertCount(count($memberships), $membershipPayments);
      $this->assertCount(count($participants), $participantPayments);
      foreach ($membershipPayments as $payment) {
        $this->assertContains($payment['membership_id'], $memberships);
      }
      foreach ($participantPayments as $payment) {
        $this->assertContains($payment['participant_id'], $participants);
      }
      $this->assertEquals($taxTotal, (float) ($contribution['tax_amount'] ?? 0));
      $this->assertEquals($total + $taxTotal, $contribution['total_amount']);
    }
  }

  /**
   * @return array|int
   */
  protected function createRuleGroup(): array {
    return $this->createTestEntity('DedupeRuleGroup', [
      'contact_type' => 'Individual',
      'threshold' => 8,
      'used' => 'General',
      'title' => 'TestRule',
      'is_reserved' => 0,
      'name' => 'TestRule',
    ], 'individual_general');
  }

  /**
   * Generic create test.
   *
   * @param int $version
   *
   * @throws \CRM_Core_Exception
   */
  protected function basicCreateTest(int $version): void {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
  }

  /**
   * Generic delete test.
   *
   * @param int $version
   *
   * @throws \CRM_Core_Exception
   */
  protected function basicDeleteTest(int $version): void {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $deleteParams = ['id' => $result['id']];
    $this->callAPISuccess($this->_entity, 'delete', $deleteParams);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', []);
    $this->assertEquals(0, $checkDeleted['count']);
  }

  /**
   * Create and return a case object for the given Client ID.
   *
   * Note that calling caseCreate directly is preferred.
   *
   * @param int $clientID
   * @param int|null $loggedInUser
   *   Omit or pass NULL to use the same as clientId
   * @param array $extra
   *   Optional specific parameters such as start_date
   * @param string $identifier
   *
   * @return CRM_Case_BAO_Case
   */
  public function createCase(int $clientID, ?int $loggedInUser = NULL, array $extra = [], string $identifier = 'form-created'): CRM_Case_DAO_Case {
    $extra['client_id'] = $clientID;
    $this->caseCreate($extra, $identifier);
    $case = new CRM_Case_BAO_Case();
    $case->id = $this->ids['Case'][$identifier];
    $case->find(TRUE);
    return $case;
  }

  /**
   * Validate that all location entities have exactly one primary.
   *
   * This query takes about 2 minutes on a DB with 10s of millions of contacts.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function assertLocationValidity(): void {
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM

(SELECT a1.contact_id
FROM civicrm_address a1
  LEFT JOIN civicrm_address a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE
  a1.is_primary = 1
  AND a2.id IS NOT NULL
  AND a1.contact_id IS NOT NULL
UNION
SELECT a1.contact_id
FROM civicrm_address a1
       LEFT JOIN civicrm_address a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE a1.is_primary = 0
  AND a2.id IS NULL
  AND a1.contact_id IS NOT NULL

UNION

SELECT a1.contact_id
FROM civicrm_email a1
       LEFT JOIN civicrm_email a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE
    a1.is_primary = 1
  AND a2.id IS NOT NULL
  AND a1.contact_id IS NOT NULL
UNION
SELECT a1.contact_id
FROM civicrm_email a1
       LEFT JOIN civicrm_email a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE a1.is_primary = 0
  AND a2.id IS NULL
  AND a1.contact_id IS NOT NULL

UNION

SELECT a1.contact_id
FROM civicrm_phone a1
       LEFT JOIN civicrm_phone a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE
    a1.is_primary = 1
  AND a2.id IS NOT NULL
  AND a1.contact_id IS NOT NULL
UNION
SELECT a1.contact_id
FROM civicrm_phone a1
       LEFT JOIN civicrm_phone a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE a1.is_primary = 0
  AND a2.id IS NULL
  AND a1.contact_id IS NOT NULL

UNION

SELECT a1.contact_id
FROM civicrm_im a1
       LEFT JOIN civicrm_im a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE
    a1.is_primary = 1
  AND a2.id IS NOT NULL
  AND a1.contact_id IS NOT NULL
UNION
SELECT a1.contact_id
FROM civicrm_im a1
       LEFT JOIN civicrm_im a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE a1.is_primary = 0
  AND a2.id IS NULL
  AND a1.contact_id IS NOT NULL

UNION

SELECT a1.contact_id
FROM civicrm_openid a1
       LEFT JOIN civicrm_openid a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE (a1.is_primary = 1 AND a2.id IS NOT NULL)
UNION

SELECT a1.contact_id
FROM civicrm_openid a1
       LEFT JOIN civicrm_openid a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE
    a1.is_primary = 1
  AND a2.id IS NOT NULL
  AND a1.contact_id IS NOT NULL
UNION
SELECT a1.contact_id
FROM civicrm_openid a1
       LEFT JOIN civicrm_openid a2 ON a1.id <> a2.id AND a2.is_primary = 1
  AND a1.contact_id = a2.contact_id
WHERE a1.is_primary = 0
  AND a2.id IS NULL
  AND a1.contact_id IS NOT NULL) as primary_descrepancies
    '));
  }

  /**
   * Ensure the specified mysql mode/s are activated.
   *
   * @param array $modes
   *
   * @noinspection PhpDocMissingThrowsInspection*/
  protected function ensureMySQLMode(array $modes): void {
    $currentModes = array_fill_keys(CRM_Utils_SQL::getSqlModes(), 1);
    $currentModes = array_merge($currentModes, array_fill_keys($modes, 1));
    CRM_Core_DAO::executeQuery("SET GLOBAL sql_mode = '" . implode(',', array_keys($currentModes)) . "'");
    CRM_Core_DAO::executeQuery("SET sql_mode = '" . implode(',', array_keys($currentModes)) . "'");
  }

  /**
   * Delete any extraneous relationship types.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function deleteNonDefaultRelationshipTypes(): void {
    RelationshipType::delete(FALSE)->addWhere('name_a_b', 'NOT IN', [
      'Child of',
      'Spouse of',
      'Partner of',
      'Sibling of',
      'Employee of',
      'Volunteer for',
      'Head of Household for',
      'Household Member of',
      'Case Coordinator is',
      'Supervised by',
    ])->execute();
  }

  /**
   * Delete any existing custom data groups.
   */
  protected function cleanupCustomGroups(): void {
    try {
      CustomField::get(FALSE)->setSelect(['option_group_id', 'custom_group_id'])
        ->addChain('delete_options', OptionGroup::delete()
          ->addWhere('id', '=', '$option_group_id')
        )
        ->addChain('delete_fields', CustomField::delete()
          ->addWhere('id', '=', '$id')
        )->execute();

      CustomGroup::delete(FALSE)->addWhere('id', '>', 0)->execute();
    }
    catch (CRM_Core_Exception $e) {
      $this->fail('failed to cleanup custom groups ' . $e->getMessage());
    }
  }

  /**
   * Ensure the default price set & field exist for memberships.
   */
  protected function ensureMembershipPriceSetExists(): void {
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_price_set (`id`, `name`, `title`, `extends`)
      VALUES (2, 'default_membership_type_amount', 'Membership Amount', 3)
      ON DUPLICATE KEY UPDATE `name` = 'default_membership_type_amount', title = 'Membership Amount';
    ");
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_price_field
      (`id`, `name`, `price_set_id`, `label`, `html_type`)
      VALUES (2, 1, 2, 'Membership Amount', 'Radio')
      ON DUPLICATE KEY UPDATE `name` = '1', price_set_id = 1, label =  'Membership Amount', html_type = 'Radio'
    ");
  }

  /**
   * Add an address block to the current domain.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function addLocationBlockToDomain(): void {
    $contactID = CRM_Core_BAO_Domain::getDomain()->contact_id;
    Phone::create()
      ->setValues(['phone' => 123, 'contact_id' => $contactID])
      ->execute()
      ->first()['id'];
    Address::create()->setValues([
      'street_address' => '10 Downing Street',
      'city' => 'London',
      'contact_id' => $contactID,
    ])->execute()->first();
  }

  /**
   * Get an array of tables with rows - useful for diagnosing cleanup issues.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getTablesWithData(): array {
    $data = [];
    $sql = CRM_Core_DAO::singleValueQuery("SELECT GROUP_CONCAT(
      'SELECT \"',
      table_name,
      '\" AS table_name, COUNT(*) AS row_count FROM `',
      table_schema,
      '`.`',
      table_name,
      '`' SEPARATOR ' UNION  '
    )
FROM INFORMATION_SCHEMA.TABLES
WHERE table_schema = DATABASE()");
    $result = CRM_Core_DAO::executeQuery($sql);
    while ($result->fetch()) {
      $count = (int) $result->row_count;
      if ($count > 0) {
        $data[$result->table_name] = $count;
      }
    }
    return $data;
  }

  /**
   * Retrieve entity financial trxn details.
   *
   * @deprecated - only called by tests - to be replaced with
   * $trxn = (array) EntityFinancialTrxn::get()
   *  ->addWhere('id', '=', $contributionID)
   *  ->addWhere('entity_table', '=', 'civicrm_contribution')
   *  ->addSelect('*')->execute();
   *
   * @param array $params
   *   an assoc array of name/value pairs.
   * @param bool $maxId
   *   To retrieve max id.
   *
   * @deprecated - this was used so much in tests I copied it over - but
   * it should go.... the tests just need to use the api.
   *
   * @return array
   */
  public function retrieveEntityFinancialTrxn($params, $maxId = FALSE) {
    $financialItem = new CRM_Financial_DAO_EntityFinancialTrxn();
    $financialItem->copyValues($params);
    // retrieve last entry from civicrm_entity_financial_trxn
    if ($maxId) {
      $financialItem->orderBy('id DESC');
      $financialItem->limit(1);
    }
    $financialItem->find();
    while ($financialItem->fetch()) {
      $financialItems[$financialItem->id] = [
        'id' => $financialItem->id,
        'entity_table' => $financialItem->entity_table,
        'entity_id' => $financialItem->entity_id,
        'financial_trxn_id' => $financialItem->financial_trxn_id,
        'amount' => $financialItem->amount,
      ];
    }
    if (!empty($financialItems)) {
      return $financialItems;
    }
    else {
      return NULL;
    }
  }

  /**
   * Disorganize our option values to ensure we are not relying on luck.
   *
   * For example our contributions and recurring contributions use different
   * option groups for contribution_status_id but by happy co-incidence (ahem)
   * both have 1 for completed by default. This upsets that co-incidence for more
   * robust testing. Ideally we would do this for all tests & for a range of values
   * but there is too much hard-coding to roll that out right now.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function disorganizeOptionValues(): void {
    OptionValue::update(FALSE)->setValues(['value' => 20])
      ->addWhere('name', '=', 'Completed')
      ->addWhere('option_group_id:name', '=', 'contribution_status')
      ->execute();
  }

  /**
   * This undoes the `disorganizeOptionValues` function.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function organizeOptionValues(): void {
    OptionValue::update(FALSE)->setValues(['value' => 1])
      ->addWhere('name', '=', 'Completed')
      ->addWhere('option_group_id:name', '=', 'contribution_status')
      ->execute();
  }

  /**
   * Reset any registered hooks.
   */
  protected function resetHooks(): void {
    CRM_Utils_Hook::singleton()->reset();
    if ($this->hookClass) {
      $this->hookClass->reset();
    }
  }

  /**
   * Get example data.
   *
   * @param string $entity
   * @param string $name
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getExampleData(string $entity, string $name): array {
    $data = ExampleData::get(FALSE)
      ->addSelect('data')
      ->addWhere('name', '=', 'entity/' . $entity . '/' . $name)
      ->execute()->first()['data'];
    unset($data['id']);
    return $data;
  }

}
