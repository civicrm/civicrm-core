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
 * Test that the core actions for APIv3 entities comply with standard syntax+behavior.
 *
 * By default, this tests all API entities. To only test specific entities, call phpunit with
 * environment variable SYNTAX_CONFORMANCE_ENTITIES, e.g.
 *
 * env SYNTAX_CONFORMANCE_ENTITIES="Contact Event" ./scripts/phpunit api_v3_SyntaxConformanceTest
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Core
 * @group headless
 */
class api_v3_SyntaxConformanceTest extends CiviUnitTestCase {
  protected $_apiversion = 3;

  /**
   * @var array e.g. $this->deletes['CRM_Contact_DAO_Contact'][] = $contactID;
   */
  protected $deletableTestObjects;

  /**
   * This test case doesn't require DB reset.
   * @var bool
   */
  public $DBResetRequired = FALSE;

  protected $_entity;

  /**
   * Map custom group entities to civicrm components.
   * @var array
   */
  protected static $componentMap = array(
    'Contribution' => 'CiviContribute',
    'Membership' => 'CiviMember',
    'Participant' => 'CiviEvent',
    'Event' => 'CiviEvent',
    'Case' => 'CiviCase',
    'Pledge' => 'CiviPledge',
    'Grant' => 'CiviGrant',
    'Campaign' => 'CiviCampaign',
    'Survey' => 'CiviCampaign',
  );

  /**
   * Set up function.
   *
   * There are two types of missing APIs:
   * Those that are to be implemented
   * (in some future version when someone steps in -hint hint-). List the entities in toBeImplemented[ {$action} ]
   * Those that don't exist
   * and that will never exist (eg an obsoleted Entity
   * they need to be returned by the function toBeSkipped_{$action} (because it has to be a static method and therefore couldn't access a this->toBeSkipped)
   */
  public function setUp() {
    parent::setUp();
    $this->enableCiviCampaign();
    $this->toBeImplemented['get'] = array(
      // CxnApp.get exists but relies on remote data outside our control; QA w/UtilsTest::testBasicArrayGet
      'CxnApp',
      'Profile',
      'CustomValue',
      'Constant',
      'CustomSearch',
      'Extension',
      'ReportTemplate',
      'System',
      'Setting',
      'Payment',
      'Logging',
    );
    $this->toBeImplemented['create'] = array(
      'Cxn',
      'CxnApp',
      'SurveyRespondant',
      'OptionGroup',
      'MailingRecipients',
      'UFMatch',
      'CustomSearch',
      'Extension',
      'ReportTemplate',
      'System',
      'User',
      'Payment',
      'Order',
      //work fine in local
      'SavedSearch',
      'Logging',
    );
    $this->toBeImplemented['delete'] = array(
      'Cxn',
      'CxnApp',
      'MembershipPayment',
      'OptionGroup',
      'SurveyRespondant',
      'UFJoin',
      'UFMatch',
      'Extension',
      'System',
      'Payment',
      'Order',
    );
    $this->onlyIDNonZeroCount['get'] = array(
      'ActivityType',
      'Entity',
      'Domain',
      'Setting',
      'User',
    );
    $this->deprecatedAPI = array('Location', 'ActivityType', 'SurveyRespondant');
    $this->deletableTestObjects = array();
  }

  public function tearDown() {
    foreach ($this->deletableTestObjects as $entityName => $entities) {
      foreach ($entities as $entityID) {
        CRM_Core_DAO::deleteTestObjects($entityName, array('id' => $entityID));
      }
    }
  }

  /**
   * Generate list of all entities.
   *
   * @param array $skip
   *   Entities to skip.
   *
   * @return array
   */
  public static function entities($skip = array()) {
    // The order of operations in here is screwy. In the case where SYNTAX_CONFORMANCE_ENTITIES is
    // defined, we should be able to parse+return it immediately. However, some weird dependency
    // crept into the system where civicrm_api('Entity','get') must be called as part of entities()
    // (even if its return value is ignored).

    $tmp = civicrm_api('Entity', 'Get', array('version' => 3));
    if (getenv('SYNTAX_CONFORMANCE_ENTITIES')) {
      $tmp = array(
        'values' => explode(' ', getenv('SYNTAX_CONFORMANCE_ENTITIES')),
      );
    }

    if (!is_array($skip)) {
      $skip = array();
    }
    $tmp = array_diff($tmp['values'], $skip);
    $entities = array();
    foreach ($tmp as $e) {
      $entities[] = array($e);
    }
    return $entities;
  }

  /**
   * Get list of entities for get test.
   *
   * @return array
   */
  public static function entities_get() {
    // all the entities, beside the ones flagged
    return static::entities(static::toBeSkipped_get(TRUE));
  }

  /**
   * Get entities for create tests.
   *
   * @return array
   */
  public static function entities_create() {
    return static::entities(static::toBeSkipped_create(TRUE));
  }

  /**
   * @return array
   */
  public static function entities_updatesingle() {
    return static::entities(static::toBeSkipped_updatesingle(TRUE));
  }

  /**
   * @return array
   */
  public static function entities_getlimit() {
    return static::entities(static::toBeSkipped_getlimit());
  }

  /**
   * Generate list of entities that can be retrieved using SQL operator syntax.
   *
   * @return array
   */
  public static function entities_getSqlOperators() {
    return static::entities(static::toBeSkipped_getSqlOperators());
  }

  /**
   * @return array
   */
  public static function entities_delete() {
    return static::entities(static::toBeSkipped_delete(TRUE));
  }

  /**
   * @return array
   */
  public static function entities_getfields() {
    return static::entities(static::toBeSkipped_getfields(TRUE));
  }

  /**
   * @return array
   */
  public static function custom_data_entities_get() {
    return static::custom_data_entities();
  }

  /**
   * @return array
   */
  public static function custom_data_entities() {
    $entities = CRM_Core_BAO_CustomQuery::$extendsMap;
    $enabledComponents = Civi::settings()->get('enable_components');
    $customDataEntities = array();
    $invalidEntities = array('Individual', 'Organization', 'Household');
    $entitiesToFix = array('Case', 'Relationship');
    foreach ($entities as $entityName => $entity) {
      if (!in_array($entityName, $invalidEntities)
        && !in_array($entityName, $entitiesToFix)
      ) {
        if (!empty(self::$componentMap[$entityName]) && empty($enabledComponents[self::$componentMap[$entityName]])) {
          CRM_Core_BAO_ConfigSetting::enableComponent(self::$componentMap[$entityName]);
        }
        $customDataEntities[] = array($entityName);
      }
    }
    return $customDataEntities;
  }

  /**
   * Add a smattering of entities that don't normally have custom data.
   *
   * @return array
   */
  public static function custom_data_incl_non_std_entities_get() {
    return static::entities(static::toBeSkipped_custom_data_creatable(TRUE));
  }

  /**
   * Get entities to be skipped on get tests.
   *
   * @param bool $sequential
   *
   * @return array
   */
  public static function toBeSkipped_get($sequential = FALSE) {
    $entitiesWithoutGet = array(
      'MailingEventSubscribe',
      'MailingEventConfirm',
      'MailingEventResubscribe',
      'MailingEventUnsubscribe',
      'Location',
    );
    if ($sequential === TRUE) {
      return $entitiesWithoutGet;
    }
    $entities = array();
    foreach ($entitiesWithoutGet as $e) {
      $entities[] = array($e);
    }
    return $entities;
  }

  /**
   * Get entities to be skipped for get call.
   *
   * Mailing Contact Just doesn't support id. We have always insisted on finding a way to
   * support id in API but in this case the underlying tables are crying out for a restructure
   * & it just doesn't make sense.
   *
   * User doesn't support get By ID because the user id is actually the CMS user ID & is not part of
   *   CiviCRM - so can only be tested through UserTest - not SyntaxConformanceTest.
   *
   * Entity doesn't support get By ID because it simply gives the result of string Entites in CiviCRM
   *
   * @param bool $sequential
   *
   * @return array
   *   Entities that cannot be retrieved by ID
   */
  public static function toBeSkipped_getByID($sequential = FALSE) {
    return array('MailingContact', 'User', 'Attachment', 'Entity');
  }

  /**
   * @param bool $sequential
   *
   * @return array
   */
  public static function toBeSkipped_create($sequential = FALSE) {
    $entitiesWithoutCreate = array('Constant', 'Entity', 'Location', 'Profile', 'MailingRecipients');
    if ($sequential === TRUE) {
      return $entitiesWithoutCreate;
    }
    $entities = array();
    foreach ($entitiesWithoutCreate as $e) {
      $entities[] = array($e);
    }
    return $entities;
  }

  /**
   * @param bool $sequential
   *
   * @return array
   */
  public static function toBeSkipped_delete($sequential = FALSE) {
    $entitiesWithout = array(
      'MailingContact',
      'MailingEventConfirm',
      'MailingEventResubscribe',
      'MailingEventSubscribe',
      'MailingEventUnsubscribe',
      'MailingRecipients',
      'Constant',
      'Entity',
      'Location',
      'Domain',
      'Profile',
      'CustomValue',
      'Setting',
      'User',
      'Logging',
    );
    if ($sequential === TRUE) {
      return $entitiesWithout;
    }
    $entities = array();
    foreach ($entitiesWithout as $e) {
      $entities[] = array($e);
    }
    return $entities;
  }

  /**
   * @param bool $sequential
   *
   * @return array
   */
  public static function toBeSkipped_custom_data_creatable($sequential = FALSE) {
    $entitiesWithout = array(
      // Ones to fix.
      'CaseContact',
      'CustomField',
      'CustomGroup',
      'DashboardContact',
      'Domain',
      'File',
      'FinancialType',
      'LocBlock',
      'MailingEventConfirm',
      'MailingEventResubscribe',
      'MailingEventSubscribe',
      'MailingEventUnsubscribe',
      'MembershipPayment',
      'SavedSearch',
      'UFJoin',
      'UFField',
      'PriceFieldValue',
      'GroupContact',
      'EntityTag',
      'PledgePayment',
      'Relationship',

      // ones that are not real entities hence not extendable.
      'ActivityType',
      'Entity',
      'Cxn',
      'Constant',
      'Attachment',
      'CustomSearch',
      'CustomValue',
      'CxnApp',
      'Extension',
      'MailingContact',
      'User',
      'System',
      'Setting',
      'SystemLog',
      'ReportTemplate',
      'MailingRecipients',
      'SurveyRespondant',
      'Profile',
      'Payment',
      'Order',
      'MailingGroup',
      'Logging',
    );
    if ($sequential === TRUE) {
      return $entitiesWithout;
    }
    $entities = array();
    foreach ($entitiesWithout as $e) {
      $entities[] = array($e);
    }
    return $entities;
  }

  /**
   * @param bool $sequential
   *
   * @return array
   * @todo add metadata for ALL these entities
   */
  public static function toBeSkipped_getfields($sequential = FALSE) {
    $entitiesWithMetadataNotYetFixed = array('ReportTemplate', 'CustomSearch');
    if ($sequential === TRUE) {
      return $entitiesWithMetadataNotYetFixed;
    }
    $entities = array();
    foreach ($entitiesWithMetadataNotYetFixed as $e) {
      $entities[] = array($e);
    }
    return $entities;
  }

  /**
   * Generate list of entities to test for get by id functions.
   * @param bool $sequential
   * @return array
   *   Entities to be skipped
   */
  public static function toBeSkipped_automock($sequential = FALSE) {
    $entitiesWithoutGet = array(
      'MailingContact',
      'EntityTag',
      'Participant',
      'Setting',
      'SurveyRespondant',
      'MailingRecipients',
      'CustomSearch',
      'Extension',
      'ReportTemplate',
      'System',
      'Logging',
    );
    if ($sequential === TRUE) {
      return $entitiesWithoutGet;
    }
    $entities = array();
    foreach ($entitiesWithoutGet as $e) {
      $entities[] = array($e);
    }
    return $entities;
  }

  /**
   * At this stage exclude the ones that don't pass & add them as we can troubleshoot them
   * @param bool $sequential
   * @return array
   */
  public static function toBeSkipped_updatesingle($sequential = FALSE) {
    $entitiesWithout = array(
      'Attachment',
      // pseudo-entity; testUpdateSingleValueAlter doesn't introspect properly on it. Multiple magic fields
      'Mailing',
      'MailingEventUnsubscribe',
      'MailingEventSubscribe',
      'Constant',
      'Entity',
      'Location',
      'Profile',
      'CustomValue',
      'UFJoin',
      'UFField',
      'Relationship',
      'RelationshipType',
      'Note',
      'Membership',
      'Group',
      'File',
      'EntityTag',
      'CustomField',
      'CustomGroup',
      'Contribution',
      'ActivityType',
      'MailingEventConfirm',
      'Case',
      'CaseContact',
      'Contact',
      'ContactType',
      'MailingEventResubscribe',
      'UFGroup',
      'Activity',
      'Event',
      'GroupContact',
      'MembershipPayment',
      'Participant',
      'LineItem',
      'ContributionPage',
      'Phone',
      'PaymentProcessor',
      'Setting',
      'MailingContact',
      'SystemLog',
      //skip this because it doesn't make sense to update logs,
      'Logging',
    );
    if ($sequential === TRUE) {
      return $entitiesWithout;
    }
    $entities = array();
    foreach ($entitiesWithout as $e) {
      $entities[] = array(
        $e,
      );
    }
    return array('pledge');
    return $entities;
  }

  /**
   * At this stage exclude the ones that don't pass & add them as we can troubleshoot them
   */
  public static function toBeSkipped_getlimit() {
    $entitiesWithout = array(
      'Case',
      //case api has non-std mandatory fields one of (case_id, contact_id, activity_id, contact_id)
      'EntityTag',
      // non-standard api - has inappropriate mandatory fields & doesn't implement limit
      'Event',
      // failed 'check that a 5 limit returns 5' - probably is_template field is wrong or something, or could be limit doesn't work right
      'Extension',
      // can't handle creating 25
      'Note',
      // fails on 5 limit - probably a set up problem
      'Setting',
      //a bit of a pseudoapi - keys by domain
    );
    return $entitiesWithout;
  }

  /**
   * At this stage exclude the ones that don't pass & add them as we can troubleshoot them
   */
  public static function toBeSkipped_getSqlOperators() {
    $entitiesWithout = array(
      //case api has non-std mandatory fields one of (case_id, contact_id, activity_id, contact_id)
      'Case',
      // on the todo list!
      'Contact',
      // non-standard api - has inappropriate mandatory fields & doesn't implement limit
      'EntityTag',
      // can't handle creating 25
      'Extension',
      // note has a default get that isn't implemented in createTestObject -meaning you don't 'get' them
      'Note',
      //a bit of a pseudoapi - keys by domain
      'Setting',
    );
    return $entitiesWithout;
  }

  /**
   * @param $entity
   * @param $key
   *
   * @return array
   */
  public function getKnownUnworkablesUpdateSingle($entity, $key) {
    // can't update values are values for which updates don't result in the value being changed
    $knownFailures = array(
      'ActionSchedule' => array(
        'cant_update' => array(
          'group_id',
        ),
      ),
      'ActivityContact' => array(
        'cant_update' => array(
          'activity_id',
          //we have an FK on activity_id + contact_id + record id so if we don't leave this one distinct we get an FK constraint error
        ),
      ),
      'Address' => array(
        'cant_update' => array(
          //issues with country id - need to ensure same country
          'state_province_id',
          'world_region',
          //creates relationship
          'master_id',
        ),
        'cant_return' => ['street_parsing', 'skip_geocode', 'fix_address'],
      ),
      'Batch' => array(
        'cant_update' => array(
          // believe this field is defined in error
          'entity_table',
        ),
        'cant_return' => array(
          'entity_table',
        ),
      ),
      'CaseType' => array(
        'cant_update' => array(
          'definition',
        ),
      ),
      'Domain' => ['cant_update' => ['domain_version']],
      'MembershipBlock' => array(
        'cant_update' => array(
          // The fake/auto-generated values leave us unable to properly cleanup fake data
          'entity_type',
          'entity_id',
        ),
      ),
      'MailingJob' => ['cant_update' => ['parent_id']],
      'ContributionSoft' => array(
        'cant_update' => array(
          // can't be changed through api
          'pcp_id',
        ),
      ),
      'Email' => array(
        'cant_update' => array(
          // This is being legitimately manipulated to always have a valid primary - skip.
          'is_primary',
        ),
      ),
      'Navigation' => array(
        'cant_update' => array(
          // Weight is deliberately altered when this is changed - skip.
          'parent_id',
        ),
      ),
      'LocationType' => array(
        'cant_update' => array(
          // I'm on the fence about whether the test should skip or the behaviour is wrong.
          // display_name is set to match name if display_name is not provided. It would be more 'normal'
          // to only calculate a default IF id is not set - but perhaps the current behaviour is kind
          // of what someone updating the name expects..
          'name',
        ),
      ),
      'Pledge' => array(
        'cant_update' => array(
          'pledge_original_installment_amount',
          'installments',
          'original_installment_amount',
          'next_pay_date',
          // can't be changed through API,
          'amount',
        ),
        // if these are passed in they are retrieved from the wrong table
        'break_return' => array(
          'honor_contact_id',
          'cancel_date',
          'contribution_page_id',
          'financial_account_id',
          'financial_type_id',
          'currency',
        ),
        // can't be retrieved from api
        'cant_return' => array(
          //due to uniquename missing
          'honor_type_id',
          'end_date',
          'modified_date',
          'acknowledge_date',
          'start_date',
          'frequency_day',
          'currency',
          'max_reminders',
          'initial_reminder_day',
          'additional_reminder_day',
          'frequency_unit',
          'pledge_contribution_page_id',
          'pledge_status_id',
          'pledge_campaign_id',
          'pledge_financial_type_id',
        ),
      ),
      'PaymentProcessorType' => array(
        'cant_update' => array(
          'billing_mode',
        ),
        'break_return' => array(),
        'cant_return' => array(),
      ),
      'PriceFieldValue' => array(
        'cant_update' => array(
          //won't update as there is no 1 in the same price set
          'weight',
        ),
      ),
      'ReportInstance' => array(
        // View mode is part of the navigation which is not retrieved by the api.
        'cant_return' => array('view_mode'),
      ),
      'SavedSearch' => array(
        // I think the fields below are generated based on form_values.
        'cant_update' => array(
          'search_custom_id',
          'where_clause',
          'select_tables',
          'where_tables',
        ),
      ),
      'StatusPreference' => array(
        'break_return' => array(
          'ignore_severity',
        ),
      ),
      'JobLog' => array(
        // For better or worse triggers override.
        'break_return' => ['run_time'],
        'cant_update' => ['run_time'],
      ),
    );
    if (empty($knownFailures[$entity]) || empty($knownFailures[$entity][$key])) {
      return array();
    }
    return $knownFailures[$entity][$key];
  }

  /* ----- testing the _get  ----- */

  /**
   * @dataProvider toBeSkipped_get
   *   Entities that don't need a get action
   * @param $Entity
   */
  public function testNotImplemented_get($Entity) {
    $result = civicrm_api($Entity, 'Get', array('version' => 3));
    $this->assertEquals(1, $result['is_error']);
    // $this->assertContains("API ($Entity, Get) does not exist", $result['error_message']);
    $this->assertRegExp('/API (.*) does not exist/', $result['error_message']);
  }

  /**
   * @dataProvider entities
   * @param $Entity
   */
  public function testWithoutParam_get($Entity) {
    // should get php complaining that a param is missing
    try {
      $result = civicrm_api($Entity, 'Get');
      $this->fail('Expected an exception. No exception was thrown.');
    }
    // As of php7.1 a new Exception is thrown by PHP ArgumentCountError when not enough params are passed.
    catch (ArgumentCountError $e) {
      /* ok */
    }
    catch (PHPUnit_Framework_Error $e) {
      /* ok */
    }
  }

  /**
   * @dataProvider entities
   * @param $Entity
   */
  public function testGetFields($Entity) {
    if (in_array($Entity, $this->deprecatedAPI) || $Entity == 'Entity' || $Entity == 'CustomValue') {
      return;
    }

    $result = civicrm_api($Entity, 'getfields', array('version' => 3));
    $this->assertTrue(is_array($result['values']), "$Entity ::get fields doesn't return values array in line " . __LINE__);
    foreach ($result['values'] as $key => $value) {
      $this->assertTrue(is_array($value), $Entity . "::" . $key . " is not an array in line " . __LINE__);
    }
  }

  /**
   * @dataProvider entities_get
   * @param $Entity
   */
  public function testEmptyParam_get($Entity) {

    if (in_array($Entity, $this->toBeImplemented['get'])) {
      // $this->markTestIncomplete("civicrm_api3_{$Entity}_get to be implemented");
      return;
    }
    $result = civicrm_api($Entity, 'Get', array());
    $this->assertEquals(1, $result['is_error']);
    $this->assertContains("Unknown api version", $result['error_message']);
  }

  /**
   * @dataProvider entities_get
   * @param $Entity
   */
  public function testEmptyParam_getString($Entity) {

    if (in_array($Entity, $this->toBeImplemented['get'])) {
      // $this->markTestIncomplete("civicrm_api3_{$Entity}_get to be implemented");
      return;
    }
    $result = $this->callAPIFailure($Entity, 'Get', 'string');
    $this->assertEquals(2000, $result['error_code']);
    $this->assertEquals('Input variable `params` is not an array', $result['error_message']);
  }

  /**
   * @dataProvider entities_get
   * @Xdepends testEmptyParam_get // no need to test the simple if the empty doesn't work/is skipped. doesn't seem to work
   * @param $Entity
   */
  public function testSimple_get($Entity) {
    // $this->markTestSkipped("test gives core error on test server (but not on our locals). Skip until we can get server to pass");
    if (in_array($Entity, $this->toBeImplemented['get'])) {
      return;
    }
    $result = civicrm_api($Entity, 'Get', array('version' => 3));
    // @TODO: list the get that have mandatory params
    if ($result['is_error']) {
      $this->assertContains("Mandatory key(s) missing from params array", $result['error_message']);
      // either id or contact_id or entity_id is one of the field missing
      $this->assertContains("id", $result['error_message']);
    }
    else {
      $this->assertEquals(3, $result['version']);
      $this->assertArrayHasKey('count', $result);
      $this->assertArrayHasKey('values', $result);
    }
  }

  /**
   * @dataProvider custom_data_incl_non_std_entities_get
   * @param $entityName
   */
  public function testCustomDataGet($entityName) {
    if ($entityName === 'Note') {
      $this->markTestIncomplete('Note can not be processed here because of a vagary in the note api, it adds entity_table=contact to the get params when id is not present - which makes sense almost always but kills this test');
    }
    $this->quickCleanup(array('civicrm_uf_match'));
    // so subsidiary activities are created
    $this->createLoggedInUser();

    $entitiesWithNamingIssues = [
      'SmsProvider' => 'Provider',
      'AclRole' => 'EntityRole',
      'MailingEventQueue' => 'Queue',
    ];

    $usableName = !empty($entitiesWithNamingIssues[$entityName]) ? $entitiesWithNamingIssues[$entityName] : $entityName;
    $optionName = CRM_Core_DAO_AllCoreTables::getTableForClass(CRM_Core_DAO_AllCoreTables::getFullName($usableName));

    if (!isset(CRM_Core_BAO_CustomQuery::$extendsMap[$entityName])) {
      $createdValue = $this->callAPISuccess('OptionValue', 'create', [
        'option_group_id' => 'cg_extend_objects',
        'label' => $usableName,
        'value' => $usableName,
        'name' => $optionName,
      ]);
    }
    // We are not passing 'check_permissions' so the the more limited permissions *should* be
    // ignored but per CRM-17700 there is a history of custom data applying permissions when it shouldn't.
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM', 'view my contact');
    $objects = $this->getMockableBAOObjects($entityName, 1);

    // simple custom field
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, $usableName . 'Test.php');
    $customFieldName = 'custom_' . $ids['custom_field_id'];
    $params = array('id' => $objects[0]->id, 'custom_' . $ids['custom_field_id'] => "custom string");
    $result = $this->callAPISuccess($entityName, 'create', $params);
    $this->assertTrue(isset($result['id']), 'no id on ' . $entityName);
    $getParams = array('id' => $result['id'], 'return' => array($customFieldName));
    $check = $this->callAPISuccess($entityName, 'get', $getParams);
    $this->assertTrue(!empty($check['values'][$check['id']][$customFieldName]), 'Custom data not present for ' . $entityName);
    $this->assertEquals("custom string", $check['values'][$check['id']][$customFieldName], 'Custom data not present for ' . $entityName);
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);

    $ids2 = $this->entityCustomGroupWithSingleStringMultiSelectFieldCreate(__FUNCTION__, $usableName . 'Test.php');
    $customFieldNameMultiSelect = 'custom_' . $ids2['custom_field_id'];
    // String custom field, Multi-Select html type
    foreach ($ids2['custom_field_group_options'] as $option_value => $option_label) {
      $params = ['id' => $objects[0]->id, 'custom_' . $ids2['custom_field_id'] => $option_value];
      $result = $this->callAPISuccess($entityName, 'create', $params);
      $getParams = [$customFieldNameMultiSelect => $option_value, 'return' => [$customFieldNameMultiSelect]];
      $this->callAPISuccessGetCount($entityName, $getParams, 1);
    }

    // cleanup
    $this->customFieldDelete($ids2['custom_field_id']);
    $this->customGroupDelete($ids2['custom_group_id']);

    $this->callAPISuccess($entityName, 'delete', array('id' => $result['id']));
    $this->quickCleanup(array('civicrm_uf_match'));
    if (!empty($createdValue)) {
      $this->callAPISuccess('OptionValue', 'delete', ['id' => $createdValue['id']]);
    }
  }

  /**
   * @dataProvider entities_get
   * @param $Entity
   */
  public function testAcceptsOnlyID_get($Entity) {
    // big random number. fun fact: if you multiply it by pi^e, the result is another random number, but bigger ;)
    $nonExistantID = 30867307034;
    if (in_array($Entity, $this->toBeImplemented['get'])
      || in_array($Entity, $this->toBeSkipped_getByID())
    ) {
      return;
    }

    // FIXME
    // the below function returns different values and hence an early return
    // we'll fix this once beta1 is released
    //        return;

    $result = civicrm_api($Entity, 'Get', array('version' => 3, 'id' => $nonExistantID));

    if ($result['is_error']) {
      // just to get a clearer message in the log
      $this->assertEquals("only id should be enough", $result['error_message']);
    }
    if (!in_array($Entity, $this->onlyIDNonZeroCount['get'])) {
      $this->assertEquals(0, $result['count']);
    }
  }

  /**
   * Test getlist works
   * @dataProvider entities_get
   * @param $Entity
   */
  public function testGetList($Entity) {
    if (in_array($Entity, $this->toBeImplemented['get'])
      || in_array($Entity, $this->toBeSkipped_getByID())
    ) {
      return;
    }
    if (in_array($Entity, ['ActivityType', 'SurveyRespondant'])) {
      $this->markTestSkipped();
    }
    $this->callAPISuccess($Entity, 'getlist', ['label_field' => 'id']);
  }

  /**
   * Test getlist works when entity is lowercase
   * @dataProvider entities_get
   * @param $Entity
   */
  public function testGetListLowerCaseEntity($Entity) {
    if (in_array($Entity, $this->toBeImplemented['get'])
      || in_array($Entity, $this->toBeSkipped_getByID())
    ) {
      return;
    }
    if (in_array($Entity, ['ActivityType', 'SurveyRespondant'])) {
      $this->markTestSkipped();
    }
    if ($Entity == 'UFGroup') {
      $Entity = 'ufgroup';
    }
    $this->callAPISuccess($Entity, 'getlist', ['label_field' => 'id']);
  }

  /**
   * Create two entities and make sure we can fetch them individually by ID.
   *
   * @dataProvider entities_get
   *
   * limitations include the problem with avoiding loops when creating test objects -
   * hence FKs only set by createTestObject when required. e.g parent_id on campaign is not being followed through
   * Currency - only seems to support US
   * @param $entityName
   */
  public function testByID_get($entityName) {
    if (in_array($entityName, self::toBeSkipped_automock(TRUE))) {
      // $this->markTestIncomplete("civicrm_api3_{$Entity}_create to be implemented");
      return;
    }

    $baos = $this->getMockableBAOObjects($entityName);
    list($baoObj1, $baoObj2) = $baos;

    // fetch first by ID
    $result = $this->callAPISuccess($entityName, 'get', array(
      'id' => $baoObj1->id,
    ));

    $this->assertTrue(!empty($result['values'][$baoObj1->id]), 'Should find first object by id');
    $this->assertEquals($baoObj1->id, $result['values'][$baoObj1->id]['id'], 'Should find id on first object');
    $this->assertEquals(1, count($result['values']));

    // fetch second by ID
    $result = $this->callAPISuccess($entityName, 'get', array(
      'id' => $baoObj2->id,
    ));
    $this->assertTrue(!empty($result['values'][$baoObj2->id]), 'Should find second object by id');
    $this->assertEquals($baoObj2->id, $result['values'][$baoObj2->id]['id'], 'Should find id on second object');
    $this->assertEquals(1, count($result['values']));
  }

  /**
   * Ensure that the "get" operation accepts limiting the #result records.
   *
   * TODO Consider making a separate entity list ("entities_getlimit")
   * For the moment, the "entities_updatesingle" list should give a good
   * sense for which entities support createTestObject
   *
   * @dataProvider entities_getlimit
   *
   * @param string $entityName
   */
  public function testLimit($entityName) {
    // each case is array(0 => $inputtedApiOptions, 1 => $expectedResultCount)
    $cases = array();
    $cases[] = array(
      array('options' => array('limit' => NULL)),
      30,
      'check that a NULL limit returns unlimited',
    );
    $cases[] = array(
      array('options' => array('limit' => FALSE)),
      30,
      'check that a FALSE limit returns unlimited',
    );
    $cases[] = array(
      array('options' => array('limit' => 0)),
      30,
      'check that a 0 limit returns unlimited',
    );
    $cases[] = array(
      array('options' => array('limit' => 5)),
      5,
      'check that a 5 limit returns 5',
    );
    $cases[] = array(
      array(),
      25,
      'check that no limit returns 25',
    );

    $baoString = _civicrm_api3_get_BAO($entityName);
    if (empty($baoString)) {
      $this->markTestIncomplete("Entity [$entityName] cannot be mocked - no known DAO");
      return;
    }

    // make 30 test items -- 30 > 25 (the default limit)
    $ids = array();
    for ($i = 0; $i < 30; $i++) {
      $baoObj = CRM_Core_DAO::createTestObject($baoString, array('currency' => 'USD'));
      $ids[] = $baoObj->id;
      $baoObj->free();
    }

    // each case is array(0 => $inputtedApiOptions, 1 => $expectedResultCount)
    foreach ($cases as $case) {
      $this->checkLimitAgainstExpected($entityName, $case[0], $case[1], $case[2]);

      //non preferred / legacy syntax
      if (isset($case[0]['options']['limit'])) {
        $this->checkLimitAgainstExpected($entityName, array('rowCount' => $case[0]['options']['limit']), $case[1], $case[2]);
        $this->checkLimitAgainstExpected($entityName, array('option_limit' => $case[0]['options']['limit']), $case[1], $case[2]);
        $this->checkLimitAgainstExpected($entityName, array('option.limit' => $case[0]['options']['limit']), $case[1], $case[2]);
      }
    }
    foreach ($ids as $id) {
      CRM_Core_DAO::deleteTestObjects($baoString, array('id' => $id));
    }
    $baoObj->free();
  }

  /**
   * Ensure that the "get" operation accepts limiting the #result records.
   *
   * @dataProvider entities_getSqlOperators
   *
   * @param string $entityName
   */
  public function testSqlOperators($entityName) {
    $toBeIgnored = array_merge($this->toBeImplemented['get'],
      $this->deprecatedAPI,
      $this->toBeSkipped_get(TRUE),
      $this->toBeSkipped_getByID()
    );
    if (in_array($entityName, $toBeIgnored)) {
      return;
    }

    $baoString = _civicrm_api3_get_BAO($entityName);

    $entities = $this->callAPISuccess($entityName, 'get', array('options' => array('limit' => 0), 'return' => 'id'));
    $entities = array_keys($entities['values']);
    $totalEntities = count($entities);
    if ($totalEntities < 3) {
      $ids = array();
      for ($i = 0; $i < 3 - $totalEntities; $i++) {
        $baoObj = CRM_Core_DAO::createTestObject($baoString, array('currency' => 'USD'));
        $ids[] = $baoObj->id;
        $baoObj->free();
      }
      $totalEntities = 3;
    }
    $entities = $this->callAPISuccess($entityName, 'get', array('options' => array('limit' => 0)));
    $entities = array_keys($entities['values']);
    $this->assertGreaterThan(2, $totalEntities);
    $this->callAPISuccess($entityName, 'getsingle', array('id' => array('IN' => array($entities[0]))));
    $this->callAPISuccessGetCount($entityName, array('id' => array('NOT IN' => array($entities[0]))), $totalEntities - 1);
    $this->callAPISuccessGetCount($entityName, array('id' => array('>' => $entities[0])), $totalEntities - 1);
  }

  /**
   * Check that get fetches an appropriate number of results.
   *
   * @param string $entityName
   *   Name of entity to test.
   * @param array $params
   * @param int $limit
   * @param string $message
   */
  public function checkLimitAgainstExpected($entityName, $params, $limit, $message) {
    $result = $this->callAPISuccess($entityName, 'get', $params);
    if ($limit == 30) {
      $this->assertGreaterThanOrEqual($limit, $result['count'], $message);
      $this->assertGreaterThanOrEqual($limit, $result['count'], $message);
    }
    else {
      $this->assertEquals($limit, $result['count'], $message);
      $this->assertEquals($limit, count($result['values']), $message);
    }
  }

  /**
   * Create two entities and make sure we can fetch them individually by ID (e.g. using "contact_id=>2"
   * or "group_id=>4")
   *
   * @dataProvider entities_get
   *
   * limitations include the problem with avoiding loops when creating test objects -
   * hence FKs only set by createTestObject when required. e.g parent_id on campaign is not being followed through
   * Currency - only seems to support US
   * @param $entityName
   * @throws \PHPUnit_Framework_IncompleteTestError
   */
  public function testByIDAlias_get($entityName) {
    if (in_array($entityName, self::toBeSkipped_automock(TRUE))) {
      // $this->markTestIncomplete("civicrm_api3_{$Entity}_create to be implemented");
      return;
    }

    $baoString = _civicrm_api3_get_BAO($entityName);
    if (empty($baoString)) {
      $this->markTestIncomplete("Entity [$entityName] cannot be mocked - no known DAO");
      return;
    }

    $idFieldName = _civicrm_api_get_entity_name_from_camel($entityName) . '_id';

    // create entities
    $baoObj1 = CRM_Core_DAO::createTestObject($baoString, array('currency' => 'USD'));
    $this->assertTrue(is_int($baoObj1->id), 'check first id');
    $this->deletableTestObjects[$baoString][] = $baoObj1->id;
    $baoObj2 = CRM_Core_DAO::createTestObject($baoString, array('currency' => 'USD'));
    $this->assertTrue(is_int($baoObj2->id), 'check second id');
    $this->deletableTestObjects[$baoString][] = $baoObj2->id;

    // fetch first by ID
    $result = civicrm_api($entityName, 'get', array(
      'version' => 3,
      $idFieldName => $baoObj1->id,
    ));
    $this->assertAPISuccess($result);
    $this->assertTrue(!empty($result['values'][$baoObj1->id]), 'Should find first object by id');
    $this->assertEquals($baoObj1->id, $result['values'][$baoObj1->id]['id'], 'Should find id on first object');
    $this->assertEquals(1, count($result['values']));

    // fetch second by ID
    $result = civicrm_api($entityName, 'get', array(
      'version' => 3,
      $idFieldName => $baoObj2->id,
    ));
    $this->assertAPISuccess($result);
    $this->assertTrue(!empty($result['values'][$baoObj2->id]), 'Should find second object by id');
    $this->assertEquals($baoObj2->id, $result['values'][$baoObj2->id]['id'], 'Should find id on second object');
    $this->assertEquals(1, count($result['values']));
  }

  /**
   * @dataProvider entities_get
   * @param $Entity
   */
  public function testNonExistantID_get($Entity) {
    // cf testAcceptsOnlyID_get
    $nonExistantID = 30867307034;
    if (in_array($Entity, $this->toBeImplemented['get'])) {
      return;
    }

    $result = civicrm_api($Entity, 'Get', array('version' => 3, 'id' => $nonExistantID));

    // redundant with testAcceptsOnlyID_get
    if ($result['is_error']) {
      return;
    }

    $this->assertArrayHasKey('version', $result);
    $this->assertEquals(3, $result['version']);
    if (!in_array($Entity, $this->onlyIDNonZeroCount['get'])) {
      $this->assertEquals(0, $result['count']);
    }
  }

  /* ---- testing the _create ---- */

  /**
   * @dataProvider toBeSkipped_create
   * entities that don't need a create action
   * @param $Entity
   */
  public function testNotImplemented_create($Entity) {
    $result = civicrm_api($Entity, 'Create', array('version' => 3));
    $this->assertEquals(1, $result['is_error']);
    $this->assertContains(strtolower("API ($Entity, Create) does not exist"), strtolower($result['error_message']));
  }

  /**
   * @dataProvider entities
   * @expectedException CiviCRM_API3_Exception
   * @param $Entity
   */
  public function testWithoutParam_create($Entity) {
    if ($Entity === 'Setting') {
      $this->markTestSkipped('It seems OK for setting to skip here as it silently sips invalid params');
    }
    elseif ($Entity === 'Mailing') {
      $this->markTestSkipped('It seems OK for "Mailing" to skip here because you can create empty drafts');
    }
    // should create php complaining that a param is missing
    civicrm_api3($Entity, 'Create');
  }

  /**
   * @dataProvider entities_create
   *
   * Check that create doesn't work with an invalid
   * @param $Entity
   * @throws \PHPUnit_Framework_IncompleteTestError
   */
  public function testInvalidSort_get($Entity) {
    $invalidEntitys = array('ActivityType', 'Setting', 'System');
    if (in_array($Entity, $invalidEntitys)) {
      $this->markTestSkipped('It seems OK for ' . $Entity . ' to skip here as it silently sips invalid params');
    }
    $result = $this->callAPIFailure($Entity, 'get', array('options' => array('sort' => 'sleep(1)')));
  }

  /**
   * @dataProvider entities_create
   *
   * Check that create doesn't work with an invalid
   * @param $Entity
   * @throws \PHPUnit_Framework_IncompleteTestError
   */
  public function testValidSortSingleArrayById_get($Entity) {
    $invalidEntitys = array('ActivityType', 'Setting', 'System');
    $tests = array(
      'id' => '_id',
      'id desc' => '_id desc',
      'id DESC' => '_id DESC',
      'id ASC' => '_id ASC',
      'id asc' => '_id asc',
    );
    foreach ($tests as $test => $expected) {
      if (in_array($Entity, $invalidEntitys)) {
        $this->markTestSkipped('It seems OK for ' . $Entity . ' to skip here as it silently ignores passed in params');
      }
      $params = array('sort' => array($test));
      $result = _civicrm_api3_get_options_from_params($params, FALSE, $Entity, 'get');
      $lowercase_entity = _civicrm_api_get_entity_name_from_camel($Entity);
      $this->assertEquals($lowercase_entity . $expected, $result['sort']);
    }
  }

  /**
   * @dataProvider entities_create
   *
   * Check that create doesn't work with an invalid
   * @param $Entity
   * @throws \PHPUnit_Framework_IncompleteTestError
   */
  public function testInvalidID_create($Entity) {
    // turn test off for noew
    $this->markTestIncomplete("Entity [ $Entity ] cannot be mocked - no known DAO");
    return;
    if (in_array($Entity, $this->toBeImplemented['create'])) {
      // $this->markTestIncomplete("civicrm_api3_{$Entity}_create to be implemented");
      return;
    }
    $result = $this->callAPIFailure($Entity, 'Create', array('id' => 999));
  }

  /**
   * @dataProvider entities
   */
  public function testCreateWrongTypeParamTag_create() {
    $result = civicrm_api("Tag", 'Create', 'this is not a string');
    $this->assertEquals(1, $result['is_error']);
    $this->assertEquals("Input variable `params` is not an array", $result['error_message']);
  }

  /**
   * @dataProvider entities_updatesingle
   *
   * limitations include the problem with avoiding loops when creating test objects -
   * hence FKs only set by createTestObject when required. e.g parent_id on campaign is not being followed through
   * Currency - only seems to support US
   * @param $entityName
   */
  public function testCreateSingleValueAlter($entityName) {
    if (in_array($entityName, $this->toBeImplemented['create'])) {
      // $this->markTestIncomplete("civicrm_api3_{$Entity}_create to be implemented");
      return;
    }

    $baoString = _civicrm_api3_get_BAO($entityName);
    $this->assertNotEmpty($baoString, $entityName);
    $this->assertNotEmpty($entityName, $entityName);
    $fieldsGet = $fields = $this->callAPISuccess($entityName, 'getfields', array('action' => 'get', 'options' => array('get_options' => 'all')));
    if ($entityName != 'Pledge') {
      $fields = $this->callAPISuccess($entityName, 'getfields', array('action' => 'create', 'options' => array('get_options' => 'all')));
    }
    $fields = $fields['values'];
    $return = array_keys($fieldsGet['values']);
    $valuesNotToReturn = $this->getKnownUnworkablesUpdateSingle($entityName, 'break_return');
    // these can't be requested as return values
    $entityValuesThatDoNotWork = array_merge(
      $this->getKnownUnworkablesUpdateSingle($entityName, 'cant_update'),
      $this->getKnownUnworkablesUpdateSingle($entityName, 'cant_return'),
      $valuesNotToReturn
    );

    $return = array_diff($return, $valuesNotToReturn);
    $baoObj = new CRM_Core_DAO();
    $baoObj->createTestObject($baoString, array('currency' => 'USD'), 2, 0);

    $getEntities = $this->callAPISuccess($entityName, 'get', array(
      'sequential' => 1,
      'return' => $return,
      'options' => array(
        'sort' => 'id DESC',
        'limit' => 2,
      ),
    ));

    // lets use first rather than assume only one exists
    $entity = $getEntities['values'][0];
    $entity2 = $getEntities['values'][1];
    $this->deletableTestObjects[$baoString][] = $entity['id'];
    $this->deletableTestObjects[$baoString][] = $entity2['id'];
    // Skip these fields that we never really expect to update well.
    $genericFieldsToSkip = ['currency', 'id', strtolower($entityName) . '_id', 'is_primary'];
    foreach ($fields as $field => $specs) {
      $resetFKTo = NULL;
      $fieldName = $field;

      if (in_array($field, $genericFieldsToSkip)
        || in_array($field, $entityValuesThatDoNotWork)
      ) {
        //@todo id & entity_id are correct but we should fix currency & frequency_day
        continue;
      }
      $this->assertArrayHasKey('type', $specs, "the _spec function for $entityName field $field does not specify the type");
      switch ($specs['type']) {
        case CRM_Utils_Type::T_DATE:
          $entity[$fieldName] = '2012-05-20';
          break;

        case CRM_Utils_Type::T_TIMESTAMP:
        case 12:
          $entity[$fieldName] = '2012-05-20 03:05:20';
          break;

        case CRM_Utils_Type::T_STRING:
        case CRM_Utils_Type::T_BLOB:
        case CRM_Utils_Type::T_MEDIUMBLOB:
        case CRM_Utils_Type::T_TEXT:
        case CRM_Utils_Type::T_LONGTEXT:
        case CRM_Utils_Type::T_EMAIL:
          if ($fieldName == 'form_values' && $entityName == 'SavedSearch') {
            // This is a hack for the SavedSearch API.
            // It expects form_values to be an array.
            // If you want to fix this, you should definitely read this forum
            // post.
            // http://forum.civicrm.org/index.php/topic,33990.0.html
            // See also my question on the CiviCRM Stack Exchange:
            // https://civicrm.stackexchange.com/questions/3437
            $entity[$fieldName] = array('sort_name' => "SortName2");
          }
          else {
            $entity[$fieldName] = substr('New String', 0, CRM_Utils_Array::Value('maxlength', $specs, 100));
            if ($fieldName == 'email') {
              $entity[$fieldName] = strtolower($entity[$fieldName]);
            }
            // typecast with array to satisfy changes made in CRM-13160
            if ($entityName == 'MembershipType' && in_array($fieldName, array(
              'relationship_type_id',
              'relationship_direction',
            ))) {
              $entity[$fieldName] = (array) $entity[$fieldName];
            }
          }
          break;

        case CRM_Utils_Type::T_INT:
          // probably created with a 1
          if ($fieldName == 'weight') {
            $entity[$fieldName] = 2;
          }
          elseif (!empty($specs['FKClassName'])) {
            if ($specs['FKClassName'] == $baoString) {
              $entity[$fieldName] = (string) $entity2['id'];
            }
            else {
              if (!empty($entity[$fieldName])) {
                $resetFKTo = array($fieldName => $entity[$fieldName]);
              }
              $entity[$fieldName] = (string) empty($entity2[$field]) ? '' : $entity2[$field];
              //todo - there isn't always something set here - & our checking on unset values is limited
              if (empty($entity[$field])) {
                unset($entity[$field]);
              }
            }
          }
          else {
            $entity[$fieldName] = '6';
          }
          break;

        case CRM_Utils_Type::T_BOOLEAN:
          // probably created with a 1
          $entity[$fieldName] = '0';
          break;

        case CRM_Utils_Type::T_FLOAT:
        case CRM_Utils_Type::T_MONEY:
          $entity[$field] = '22.75';
          break;

        case CRM_Utils_Type::T_URL:
          $entity[$field] = 'warm.beer.com';
      }
      if (empty($specs['FKClassName']) && (!empty($specs['pseudoconstant']) || !empty($specs['options']))) {
        $options = CRM_Utils_Array::value('options', $specs, array());
        if (!$options) {
          //eg. pdf_format id doesn't ship with any
          if (isset($specs['pseudoconstant']['optionGroupName'])) {
            $optionValue = $this->callAPISuccess('option_value', 'create', array(
              'option_group_id' => $specs['pseudoconstant']['optionGroupName'],
              'label' => 'new option value',
              'sequential' => 1,
            ));
            $optionValue = $optionValue['values'];
            $keyColumn = CRM_Utils_Array::value('keyColumn', $specs['pseudoconstant'], 'value');
            $options[$optionValue[0][$keyColumn]] = 'new option value';
          }
        }
        $entity[$field] = array_rand($options);
      }
      if (!empty($specs['FKClassName']) && !empty($specs['pseudoconstant'])) {
        // in the weird situation where a field has both an fk and pseudoconstant defined,
        // e.g. campaign_id field, need to flush caches.
        // FIXME: Why doesn't creating a campaign clear caches?
        civicrm_api3($entityName, 'getfields', array('cache_clear' => 1));
      }
      $updateParams = array(
        'id' => $entity['id'],
        $field => isset($entity[$field]) ? $entity[$field] : NULL,
      );
      if (!empty($specs['serialize'])) {
        $updateParams[$field] = $entity[$field] = (array) $specs['serialize'];
      }
      if (isset($updateParams['financial_type_id']) && in_array($entityName, array('Grant'))) {
        //api has special handling on these 2 fields for backward compatibility reasons
        $entity['contribution_type_id'] = $updateParams['financial_type_id'];
      }
      if (isset($updateParams['next_sched_contribution_date']) && in_array($entityName, array('ContributionRecur'))) {
        //api has special handling on these 2 fields for backward compatibility reasons
        $entity['next_sched_contribution'] = $updateParams['next_sched_contribution_date'];
      }
      if (isset($updateParams['image'])) {
        // Image field is passed through simplifyURL function so may be different, do the same here for comparison
        $entity['image'] = CRM_Utils_String::simplifyURL($updateParams['image'], TRUE);
      }
      if (isset($updateParams['thumbnail'])) {
        // Thumbnail field is passed through simplifyURL function so may be different, do the same here for comparison
        $entity['thumbnail'] = CRM_Utils_String::simplifyURL($updateParams['thumbnail'], TRUE);
      }

      $update = $this->callAPISuccess($entityName, 'create', $updateParams);
      $checkParams = array(
        'id' => $entity['id'],
        'sequential' => 1,
        'return' => $return,
        'options' => array(
          'sort' => 'id DESC',
          'limit' => 2,
        ),
      );

      $checkEntity = $this->callAPISuccess($entityName, 'getsingle', $checkParams);
      if (!empty($specs['serialize']) && !is_array($checkEntity[$field])) {
        // Put into serialized format for comparison if 'get' has not returned serialized.
        $entity[$field] = CRM_Core_DAO::serializeField($checkEntity[$field], $specs['serialize']);
      }

      $this->assertAPIArrayComparison($entity, $checkEntity, array(), "checking if $fieldName was correctly updated\n" . print_r(array(
        'update-params' => $updateParams,
        'update-result' => $update,
        'getsingle-params' => $checkParams,
        'getsingle-result' => $checkEntity,
        'expected entity' => $entity,
      ), TRUE));
      if ($resetFKTo) {
        //reset the foreign key fields because otherwise our cleanup routine fails & some other unexpected stuff can kick in
        $entity = array_merge($entity, $resetFKTo);
        $updateParams = array_merge($updateParams, $resetFKTo);
        $this->callAPISuccess($entityName, 'create', $updateParams);
        if (isset($updateParams['financial_type_id']) && in_array($entityName, array('Grant'))) {
          //api has special handling on these 2 fields for backward compatibility reasons
          $entity['contribution_type_id'] = $updateParams['financial_type_id'];
        }
        if (isset($updateParams['next_sched_contribution_date']) && in_array($entityName, array('ContributionRecur'))) {
          //api has special handling on these 2 fields for backward compatibility reasons
          $entity['next_sched_contribution'] = $updateParams['next_sched_contribution_date'];
        }
      }
    }
    $baoObj->free();
  }

  /* ---- testing the _getFields ---- */

  /* ---- testing the _delete ---- */

  /**
   * @dataProvider toBeSkipped_delete
   * entities that don't need a delete action
   * @param $Entity
   */
  public function testNotImplemented_delete($Entity) {
    $nonExistantID = 151416349;
    $result = civicrm_api($Entity, 'Delete', array('version' => 3, 'id' => $nonExistantID));
    $this->assertEquals(1, $result['is_error']);
    $this->assertContains(strtolower("API ($Entity, Delete) does not exist"), strtolower($result['error_message']));
  }

  /**
   * @dataProvider entities
   * @param $Entity
   */
  public function testWithoutParam_delete($Entity) {
    // should delete php complaining that a param is missing
    try {
      $result = civicrm_api($Entity, 'Delete');
      $this->fail('Expected an exception. No exception was thrown.');
    }
    // As of php7.1 a new Exception is thrown by PHP ArgumentCountError when not enough params are passed.
    catch (ArgumentCountError $e) {
      /* ok */
    }
    catch (PHPUnit_Framework_Error $e) {
      /* ok */
    }
  }

  /**
   * @dataProvider entities_delete
   * @param $Entity
   */
  public function testEmptyParam_delete($Entity) {
    if (in_array($Entity, $this->toBeImplemented['delete'])) {
      // $this->markTestIncomplete("civicrm_api3_{$Entity}_delete to be implemented");
      return;
    }
    $result = civicrm_api($Entity, 'Delete', array());
    $this->assertEquals(1, $result['is_error']);
    $this->assertContains("Unknown api version", $result['error_message']);
  }

  /**
   * @dataProvider entities_delete
   * @param $Entity
   * @throws \PHPUnit_Framework_IncompleteTestError
   */
  public function testInvalidID_delete($Entity) {
    $result = $this->callAPIFailure($Entity, 'Delete', array('id' => 999999));
  }

  /**
   * @dataProvider entities
   */
  public function testDeleteWrongTypeParamTag_delete() {
    $result = civicrm_api("Tag", 'Delete', 'this is not a string');
    $this->assertEquals(1, $result['is_error']);
    $this->assertEquals("Input variable `params` is not an array", $result['error_message']);
  }

  /**
   * Create two entities and make sure delete action only deletes one!
   *
   * @dataProvider entities_delete
   *
   * limitations include the problem with avoiding loops when creating test objects -
   * hence FKs only set by createTestObject when required. e.g parent_id on campaign is not being followed through
   * Currency - only seems to support US
   * @param $entityName
   * @throws \PHPUnit_Framework_IncompleteTestError
   */
  public function testByID_delete($entityName) {
    // turn test off for noew
    $this->markTestIncomplete("Entity [$entityName] cannot be mocked - no known DAO");
    return;

    if (in_array($entityName, self::toBeSkipped_automock(TRUE))) {
      // $this->markTestIncomplete("civicrm_api3_{$Entity}_create to be implemented");
      return;
    }
    $startCount = $this->callAPISuccess($entityName, 'getcount', array());
    $createcount = 2;
    $baos = $this->getMockableBAOObjects($entityName, $createcount);
    list($baoObj1, $baoObj2) = $baos;

    // make sure exactly 2 exist
    $result = $this->callAPISuccess($entityName, 'getcount', array(),
      $createcount + $startCount
    );

    $this->callAPISuccess($entityName, 'delete', array('id' => $baoObj2->id));
    //make sure 1 less exists now
    $result = $this->callAPISuccess($entityName, 'getcount', array(),
      ($createcount + $startCount) - 1
    );

    //make sure id #1 exists
    $result = $this->callAPISuccess($entityName, 'getcount', array('id' => $baoObj1->id),
      1
    );
    //make sure id #2 desn't exist
    $result = $this->callAPISuccess($entityName, 'getcount', array('id' => $baoObj2->id),
      0
    );
  }

  /**
   * Create two entities and make sure delete action only deletes one!
   *
   * @dataProvider entities_getfields
   * @param $entity
   */
  public function testGetfieldsHasTitle($entity) {
    $entities = $this->getEntitiesSupportingCustomFields();
    if (in_array($entity, $entities)) {
      $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, $entity . 'Test.php');
    }
    $actions = $this->callAPISuccess($entity, 'getactions', array());
    foreach ($actions['values'] as $action) {
      if (substr($action, -7) == '_create' || substr($action, -4) == '_get' || substr($action, -7) == '_delete') {
        //getactions can't distinguish between contribution_page.create & contribution_page.create
        continue;
      }
      $fields = $this->callAPISuccess($entity, 'getfields', array('action' => $action));
      if (!empty($ids) && in_array($action, array('create', 'get'))) {
        $this->assertArrayHasKey('custom_' . $ids['custom_field_id'], $fields['values']);
      }

      foreach ($fields['values'] as $fieldName => $fieldSpec) {
        $this->assertArrayHasKey('title', $fieldSpec, "no title for $entity - $fieldName on action $action");
        $this->assertNotEmpty($fieldSpec['title'], "empty title for $entity - $fieldName");
      }
    }
    if (!empty($ids)) {
      $this->customFieldDelete($ids['custom_field_id']);
      $this->customGroupDelete($ids['custom_group_id']);
    }
  }

  /**
   * @return array
   */
  public function getEntitiesSupportingCustomFields() {
    $entities = self::custom_data_entities_get();
    $returnEntities = array();
    foreach ($entities as $entityArray) {
      $returnEntities[] = $entityArray[0];
    }
    return $returnEntities;
  }

  /**
   * @param string $entityName
   * @param int $count
   *
   * @return array
   */
  private function getMockableBAOObjects($entityName, $count = 2) {
    $baoString = _civicrm_api3_get_BAO($entityName);
    if (empty($baoString)) {
      $this->markTestIncomplete("Entity [$entityName] cannot be mocked - no known DAO");
      return array();
    }
    $baos = array();
    $i = 0;
    while ($i < $count) {
      // create entities
      $baoObj = CRM_Core_DAO::createTestObject($baoString, array('currency' => 'USD'));
      $this->assertTrue(is_int($baoObj->id), 'check first id');
      $this->deletableTestObjects[$baoString][] = $baoObj->id;
      $baos[] = $baoObj;
      $i++;
    }
    return $baos;
  }

  /**
   * Verify that HTML metacharacters provided as inputs appear consistently.
   * as outputs.
   *
   * At time of writing, the encoding scheme requires (for example) that an
   * event title be partially-HTML-escaped before writing to DB.  To provide
   * consistency, the API must perform extra encoding and decoding on some
   * fields.
   *
   * In this example, the event 'title' is subject to encoding, but the
   * event 'description' is not.
   */
  public function testEncodeDecodeConsistency() {
    // Create example
    $createResult = civicrm_api('Event', 'Create', array(
      'version' => 3,
      'title' => 'CiviCRM <> TheRest',
      'description' => 'TheRest <> CiviCRM',
      'event_type_id' => 1,
      'is_public' => 1,
      'start_date' => 20081021,
    ));
    $this->assertAPISuccess($createResult);
    $eventId = $createResult['id'];
    $this->assertEquals('CiviCRM <> TheRest', $createResult['values'][$eventId]['title']);
    $this->assertEquals('TheRest <> CiviCRM', $createResult['values'][$eventId]['description']);

    // Verify "get" handles decoding in result value
    $getByIdResult = civicrm_api('Event', 'Get', array(
      'version' => 3,
      'id' => $eventId,
    ));
    $this->assertAPISuccess($getByIdResult);
    $this->assertEquals('CiviCRM <> TheRest', $getByIdResult['values'][$eventId]['title']);
    $this->assertEquals('TheRest <> CiviCRM', $getByIdResult['values'][$eventId]['description']);

    // Verify "get" handles encoding in search value
    $getByTitleResult = civicrm_api('Event', 'Get', array(
      'version' => 3,
      'title' => 'CiviCRM <> TheRest',
    ));
    $this->assertAPISuccess($getByTitleResult);
    $this->assertEquals('CiviCRM <> TheRest', $getByTitleResult['values'][$eventId]['title']);
    $this->assertEquals('TheRest <> CiviCRM', $getByTitleResult['values'][$eventId]['description']);

    // Verify that "getSingle" handles decoding
    $getSingleResult = $this->callAPISuccess('Event', 'GetSingle', array(
      'id' => $eventId,
    ));

    $this->assertEquals('CiviCRM <> TheRest', $getSingleResult['title']);
    $this->assertEquals('TheRest <> CiviCRM', $getSingleResult['description']);

    // Verify that chaining handles decoding
    $chainResult = $this->callAPISuccess('Event', 'Get', array(
      'id' => $eventId,
      'api.event.get' => array(),
    ));
    $this->assertEquals('CiviCRM <> TheRest', $chainResult['values'][$eventId]['title']);
    $this->assertEquals('TheRest <> CiviCRM', $chainResult['values'][$eventId]['description']);
    $this->assertEquals('CiviCRM <> TheRest', $chainResult['values'][$eventId]['api.event.get']['values'][0]['title']);
    $this->assertEquals('TheRest <> CiviCRM', $chainResult['values'][$eventId]['api.event.get']['values'][0]['description']);

    // Verify that "setvalue" handles encoding for updates
    $setValueTitleResult = civicrm_api('Event', 'setvalue', array(
      'version' => 3,
      'id' => $eventId,
      'field' => 'title',
      'value' => 'setValueTitle: CiviCRM <> TheRest',
    ));
    $this->assertAPISuccess($setValueTitleResult);
    $this->assertEquals('setValueTitle: CiviCRM <> TheRest', $setValueTitleResult['values']['title']);
    $setValueDescriptionResult = civicrm_api('Event', 'setvalue', array(
      'version' => 3,
      'id' => $eventId,
      'field' => 'description',
      'value' => 'setValueDescription: TheRest <> CiviCRM',
    ));
    //$this->assertTrue((bool)$setValueDescriptionResult['is_error']); // not supported by setValue
    $this->assertEquals('setValueDescription: TheRest <> CiviCRM', $setValueDescriptionResult['values']['description']);
  }

  /**
   * Verify that write operations (create/update) use partial HTML-encoding
   *
   * In this example, the event 'title' is subject to encoding, but the
   * event 'description' is not.
   */
  public function testEncodeWrite() {
    // Create example
    $createResult = civicrm_api('Event', 'Create', array(
      'version' => 3,
      'title' => 'createNew: CiviCRM <> TheRest',
      'description' => 'createNew: TheRest <> CiviCRM',
      'event_type_id' => 1,
      'is_public' => 1,
      'start_date' => 20081021,
    ));
    $this->assertAPISuccess($createResult);
    $eventId = $createResult['id'];
    $this->assertDBQuery('createNew: CiviCRM &lt;&gt; TheRest', 'SELECT title FROM civicrm_event WHERE id = %1', array(
      1 => array($eventId, 'Integer'),
    ));
    $this->assertDBQuery('createNew: TheRest <> CiviCRM', 'SELECT description FROM civicrm_event WHERE id = %1', array(
      1 => array($eventId, 'Integer'),
    ));

    // Verify that "create" handles encoding for updates
    $createWithIdResult = civicrm_api('Event', 'Create', array(
      'version' => 3,
      'id' => $eventId,
      'title' => 'createWithId:  CiviCRM <> TheRest',
      'description' => 'createWithId:  TheRest <> CiviCRM',
    ));
    $this->assertAPISuccess($createWithIdResult);
    $this->assertDBQuery('createWithId:  CiviCRM &lt;&gt; TheRest', 'SELECT title FROM civicrm_event WHERE id = %1', array(
      1 => array($eventId, 'Integer'),
    ));
    $this->assertDBQuery('createWithId:  TheRest <> CiviCRM', 'SELECT description FROM civicrm_event WHERE id = %1', array(
      1 => array($eventId, 'Integer'),
    ));

    // Verify that "setvalue" handles encoding for updates
    $setValueTitleResult = civicrm_api('Event', 'setvalue', array(
      'version' => 3,
      'id' => $eventId,
      'field' => 'title',
      'value' => 'setValueTitle: CiviCRM <> TheRest',
    ));
    $this->assertAPISuccess($setValueTitleResult);
    $this->assertDBQuery('setValueTitle: CiviCRM &lt;&gt; TheRest', 'SELECT title FROM civicrm_event WHERE id = %1', array(
      1 => array($eventId, 'Integer'),
    ));
    $setValueDescriptionResult = civicrm_api('Event', 'setvalue', array(
      'version' => 3,
      'id' => $eventId,
      'field' => 'description',
      'value' => 'setValueDescription: TheRest <> CiviCRM',
    ));
    //$this->assertTrue((bool)$setValueDescriptionResult['is_error']); // not supported by setValue
    $this->assertAPISuccess($setValueDescriptionResult);
    $this->assertDBQuery('setValueDescription: TheRest <> CiviCRM', 'SELECT description FROM civicrm_event WHERE id = %1', array(
      1 => array($eventId, 'Integer'),
    ));
  }

}
