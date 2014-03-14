<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';


/**
 *  Test APIv3 civicrm_sytanc conformance* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Core
 */

class api_v3_SyntaxConformanceTest extends CiviUnitTestCase {
  protected $_apiversion = 3;

  /**
   * @var array e.g. $this->deletes['CRM_Contact_DAO_Contact'][] = $contactID;
   */
  protected $deletableTestObjects;

  /** This test case doesn't require DB reset */
  public $DBResetRequired = FALSE;

  /* they are two types of missing APIs:
       - Those that are to be implemented
         (in some future version when someone steps in -hint hint-). List the entities in toBeImplemented[ {$action} ]
       Those that don't exist
         and that will never exist (eg an obsoleted Entity
         they need to be returned by the function toBeSkipped_{$action} (because it has to be a static method and therefore couldn't access a this->toBeSkipped)
    */
  function setUp() {
    parent::setUp();
    $this->enableCiviCampaign();
    $this->toBeImplemented['get'] = array('Profile', 'CustomValue', 'Constant', 'CustomSearch', 'Extension', 'ReportTemplate', 'System', 'Setting');
    $this->toBeImplemented['create'] = array('SurveyRespondant', 'OptionGroup', 'MailingRecipients', 'UFMatch', 'LocationType', 'CustomSearch', 'Extension', 'ReportTemplate', 'System');
    $this->toBeImplemented['delete'] = array('MembershipPayment', 'OptionGroup', 'SurveyRespondant', 'UFJoin', 'UFMatch', 'Extension', 'LocationType', 'System');
    $this->onlyIDNonZeroCount['get'] = array('ActivityType', 'Entity', 'Domain','Setting');
    $this->deprecatedAPI = array('Location', 'ActivityType', 'SurveyRespondant');
    $this->deletableTestObjects = array();
  }

  function tearDown() {
    foreach ($this->deletableTestObjects as $entityName => $entities) {
      foreach ($entities as $entityID) {
        CRM_Core_DAO::deleteTestObjects($entityName, array('id' => $entityID));
      }
    }
  }

  public static function entities($skip = NULL) {
    // To only test specific entities, call phpunit with SYNTAX_CONFORMANCE_ENTITIES="TheEntityName"
    // or uncomment this line:
    //return array(array ('Tag'), array ('Activity')  );

    if (getenv('SYNTAX_CONFORMANCE_ENTITIES')) {
      $result = array();
      foreach (explode(' ', getenv('SYNTAX_CONFORMANCE_ENTITIES')) as $entity) {
        $result[] = array($entity);
      }
      return $result;
    }

    $tmp = civicrm_api('Entity', 'Get', array('version' => 3));
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

  public static function entities_get() {
    // all the entities, beside the ones flagged
    return static::entities(static::toBeSkipped_get(TRUE));
  }

  public static function entities_create() {
    return static::entities(static::toBeSkipped_create(TRUE));
  }

  public static function entities_updatesingle() {
    return static::entities(static::toBeSkipped_updatesingle(TRUE));
  }

  public static function entities_delete() {
    return static::entities(static::toBeSkipped_delete(TRUE));
  }

  public static function toBeSkipped_get($sequential = FALSE) {
    $entitiesWithoutGet = array('MailingEventSubscribe', 'MailingEventConfirm', 'MailingEventResubscribe', 'MailingEventUnsubscribe', 'MailingGroup', 'Location');
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
   * Mailing Contact Just doesn't support id. We have always insisted on finding a way to
   * support id in API but in this case the underlying tables are crying out for a restructue
   * & it just doesn't make sense
   * @param unknown_type $sequential
   * @return multitype:string |multitype:multitype:string
   */
  public static function toBeSkipped_getByID($sequential = FALSE) {
    return array('MailingContact');
  }

  public static function toBeSkipped_create($sequential = FALSE) {
    $entitiesWithoutCreate = array('MailingGroup', 'Constant', 'Entity', 'Location', 'Profile', 'MailingRecipients');
    if ($sequential === TRUE) {
      return $entitiesWithoutCreate;
    }
    $entities = array();
    foreach ($entitiesWithoutCreate as $e) {
      $entities[] = array($e);
    }
    return $entities;
  }

  public static function toBeSkipped_delete($sequential = FALSE) {
    $entitiesWithout = array('MailingGroup', 'Constant', 'Entity', 'Location', 'Domain', 'Profile', 'CustomValue');
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
 * Generate list of entities to test for get by id functions
 * @param boolean $sequential
 * @return multitype:string |multitype:multitype:string
 */
  public static function toBeSkipped_automock($sequential = FALSE) {
    $entitiesWithoutGet = array('MailingContact', 'EntityTag', 'Participant', 'ParticipantPayment', 'Setting', 'SurveyRespondant', 'MailingRecipients',  'CustomSearch', 'Extension', 'ReportTemplate', 'System');
    if ($sequential === TRUE) {
      return $entitiesWithoutGet;
    }
    $entities = array();
    foreach ($entitiesWithoutGet as $e) {
      $entities[] = array($e);
    }
    return $entities;
  }


  /*
  * At this stage exclude the ones that don't pass & add them as we can troubleshoot them
  */

  public static function toBeSkipped_updatesingle($sequential = FALSE) {
    $entitiesWithout = array(
      'Mailing',
      'MailingGroup',
      'MailingJob',
      'Address',
      'MailingEventUnsubscribe',
      'MailingEventSubscribe',
      'Constant',
      'Entity',
      'Location',
      'Domain',
      'Profile',
      'CustomValue',
      'SurveyRespondant',
      'Tag',
      'UFMatch',
      'UFJoin',
      'UFField',
      'OptionValue',
      'Relationship',
      'RelationshipType',
      'ParticipantStatusType',
      'Note',
      'OptionGroup',
      'Membership',
      'MembershipType',
      'MembershipStatus',
      'Group',
      'GroupOrganization',
      'GroupNesting',
      'Job',
      'File',
      'EntityTag',
      'CustomField',
      'CustomGroup',
      'Contribution',
      'ContributionRecur',
      'ActivityType',
      'MailingEventConfirm',
      'Case',
      'Contact',
      'ContactType',
      'MailingEventResubscribe',
      'UFGroup',
      'Activity',
      'Email',
      'Event',
      'GroupContact',
      'MembershipPayment',
      'Participant',
      'ParticipantPayment',
      'LineItem',
      'PriceSet',
      'PriceField',
      'PriceFieldValue',
      'PledgePayment',
      'ContributionPage',
      'Phone',
      'PaymentProcessor',
      'MailSettings',
      'Setting',
      'MailingContact',
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

  public function getKnownUnworkablesUpdateSingle($entity, $key){
    // can't update values are values for which updates don't result in the value being changed
    $knownFailures = array(
      'ActionSchedule' => array(
        'cant_update' => array(
          'group_id',
        ),
      ),
      'Address' => array(
        'cant_update' => array(
          'state_province_id', //issues with country id - need to ensure same country
          'master_id',//creates relationship
        ),
        'cant_return' => array(
        )
      ),
      'Batch' => array(
        'cant_update' => array(
          'entity_table', // believe this field is defined in error
        ),
        'cant_return' => array(
          'entity_table',
        )
      ),
      'Pledge' => array(
        'cant_update' => array(
          'pledge_original_installment_amount',
          'installments',
          'original_installment_amount',
          'next_pay_date',
          'amount' // can't be changed through API
        ),
        'break_return' => array(// if these are passed in they are retrieved from the wrong table
          'honor_contact_id',
          'cancel_date',
          'contribution_page_id',
          'financial_account_id',
          'financial_type_id',
          'currency'
        ),
        'cant_return' => array(// can't be retrieved from api
          'honor_type_id', //due to uniquename missing
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
        )
      ),
      'PaymentProcessorType' => array(
        'cant_update' => array(
          'billing_mode',
        ),
        'break_return' => array(
        ),
        'cant_return' => array(
        ),
      ),
    );
    if(empty($knownFailures[$entity]) || empty($knownFailures[$entity][$key])){
      return array();
    }
    return $knownFailures[$entity][$key];
  }

  /** testing the _get **/

  /**
   * @dataProvider toBeSkipped_get
   entities that don't need a get action
   */
  public function testNotImplemented_get($Entity) {
    $result = civicrm_api($Entity, 'Get', array('version' => 3));
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
    $this->assertContains("API ($Entity, Get) does not exist", $result['error_message']);
  }

  /**
   * @dataProvider entities
   * @expectedException PHPUnit_Framework_Error
   */
  public function testWithoutParam_get($Entity) {
    // should get php complaining that a param is missing
    $result = civicrm_api($Entity, 'Get');
  }

  /**
   * @dataProvider entities
   */
  public function testGetFields($Entity) {
    if (in_array($Entity, $this->deprecatedAPI) || $Entity == 'Entity' || $Entity == 'CustomValue' || $Entity == 'MailingGroup') {
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
   */
  public function testEmptyParam_get($Entity) {

    if (in_array($Entity, $this->toBeImplemented['get'])) {
      // $this->markTestIncomplete("civicrm_api3_{$Entity}_get to be implemented");
      return;
    }
    $result = civicrm_api($Entity, 'Get', array());
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
    $this->assertContains("Mandatory key(s) missing from params array", $result['error_message']);
  }
  /**
   * @dataProvider entities_get
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
   * @dataProvider entities_get
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
   * Create two entities and make sure we can fetch them individually by ID
   *
   * @dataProvider entities_get
   *
   * limitations include the problem with avoiding loops when creating test objects -
   * hence FKs only set by createTestObject when required. e.g parent_id on campaign is not being followed through
   * Currency - only seems to support US
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
   * Create two entities and make sure we can fetch them individually by ID (e.g. using "contact_id=>2"
   * or "group_id=>4")
   *
   * @dataProvider entities_get
   *
   * limitations include the problem with avoiding loops when creating test objects -
   * hence FKs only set by createTestObject when required. e.g parent_id on campaign is not being followed through
   * Currency - only seems to support US
   */
  public function testByIDAlias_get($entityName) {
    if (in_array($entityName, self::toBeSkipped_automock(TRUE))) {
      // $this->markTestIncomplete("civicrm_api3_{$Entity}_create to be implemented");
      return;
    }

    $baoString = _civicrm_api3_get_DAO($entityName);
    if (empty($baoString)) {
      $this->markTestIncomplete("Entity [$entityName] cannot be mocked - no known DAO");
      return;
    }

    $idFieldName = _civicrm_api_get_entity_name_from_camel($entityName) . '_id';

    // create entities
    $baoObj1 = CRM_Core_DAO::createTestObject($baoString, array('currency' => 'USD'));
    $this->assertTrue(is_integer($baoObj1->id), 'check first id');
    $this->deletableTestObjects[$baoString][] = $baoObj1->id;
    $baoObj2 = CRM_Core_DAO::createTestObject($baoString, array('currency' => 'USD'));
    $this->assertTrue(is_integer($baoObj2->id), 'check second id');
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

  /** testing the _create **/

  /**
   * @dataProvider toBeSkipped_create
   entities that don't need a create action
   */
  public function testNotImplemented_create($Entity) {
    $result = civicrm_api($Entity, 'Create', array('version' => 3));
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
    $this->assertContains("API ($Entity, Create) does not exist", $result['error_message']);
  }

  /**
   * @dataProvider entities
   * @expectedException PHPUnit_Framework_Error
   */
  public function testWithoutParam_create($Entity) {
    // should create php complaining that a param is missing
    $result = civicrm_api($Entity, 'Create');
  }

  /**
   * @dataProvider entities_create
   */
  public function testEmptyParam_create($Entity) {
    $this->markTestIncomplete("fixing this test to test the api functions fails on numberous tests
      which will either create a completely blank entity (batch, participant status) or
      have a damn good crack at it (e.g mailing job). Marking this as incomplete beats false success");
    //
    return;
    if (in_array($Entity, $this->toBeImplemented['create'])) {
      // $this->markTestIncomplete("civicrm_api3_{$Entity}_create to be implemented");
      return;
    }
    $result = $this->callAPIFailure($Entity, 'Create', array());
    $this->assertContains("Mandatory key(s) missing from params array", $result['error_message']);
  }

  /**
   * @dataProvider entities_create
   *
   * Check that create doesn't work with an invalid
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
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals("Input variable `params` is not an array", $result['error_message']);
  }

  /**
   * @dataProvider entities_updatesingle
   *
   * limitations include the problem with avoiding loops when creating test objects -
   * hence FKs only set by createTestObject when required. e.g parent_id on campaign is not being followed through
   * Currency - only seems to support US
   */
  public function testCreateSingleValueAlter($entityName) {
    if (in_array($entityName, $this->toBeImplemented['create'])) {
      // $this->markTestIncomplete("civicrm_api3_{$Entity}_create to be implemented");
      return;
    }

    $baoString = _civicrm_api3_get_DAO($entityName);
    $this->assertNotEmpty($baoString, $entityName);
    $this->assertNotEmpty($entityName, $entityName);
    $fieldsget = $fields = $this->callAPISuccess($entityName, 'getfields', array('action' => 'get'));
    if($entityName != 'Pledge'){
      $fields = $this->callAPISuccess($entityName, 'getfields', array('action' => 'create'));
    }
    $fields = $fields['values'];
    $return = array_keys($fieldsget['values']);
    $valuesNotToReturn = $this->getKnownUnworkablesUpdateSingle($entityName, 'break_return');
      // these can't be requested as return values
    $entityValuesThatDontWork = array_merge(
        $this->getKnownUnworkablesUpdateSingle($entityName, 'cant_update'),
        $this->getKnownUnworkablesUpdateSingle($entityName, 'cant_return'),
        $valuesNotToReturn
      );

    $return = array_diff($return,$valuesNotToReturn);
    $baoObj = new CRM_Core_DAO();
    $baoObj->createTestObject($baoString, array('currency' => 'USD'), 2, 0);
    $getentities = $this->callAPISuccess($entityName, 'get', array(
      'sequential' => 1,
      'return' => $return,
      'options' => array(
        'sort' => 'id DESC',
        'limit' => 2,
      ),
    ));
    // lets use first rather than assume only one exists
    $entity = $getentities['values'][0];
    $entity2 = $getentities['values'][1];
    foreach ($fields as $field => $specs) {
      $fieldName = $field;
      if (!empty($specs['uniquename'])) {
        $fieldName = $specs['uniquename'];
      }
      if ($field == 'currency' || $field == 'id' || $field == strtolower($entityName) . '_id'
      || in_array($field,$entityValuesThatDontWork)) {
        //@todo id & entity_id are correct but we should fix currency & frequency_day
        continue;
      }
      switch ($specs['type']) {
        case CRM_Utils_Type::T_DATE:
        case CRM_Utils_Type::T_TIMESTAMP:
          $entity[$fieldName] = '2012-05-20';
          break;
        //case CRM_Utils_Type::T_DATETIME:

        case 12:
          $entity[$fieldName] = '2012-05-20 03:05:20';
          break;

        case CRM_Utils_Type::T_STRING:
        case CRM_Utils_Type::T_BLOB:
        case CRM_Utils_Type::T_MEDIUMBLOB:
        case CRM_Utils_Type::T_TEXT:
        case CRM_Utils_Type::T_LONGTEXT:
        case CRM_Utils_Type::T_EMAIL:
          $entity[$fieldName] = substr('New String',0, CRM_Utils_Array::Value('maxlength',$specs,100));
          break;

        case CRM_Utils_Type::T_INT:
          // probably created with a 1
          $entity[$fieldName] = '6';
          if (!empty($specs['FKClassName'])) {
            if($specs['FKClassName'] == $baoString){
              $entity[$fieldName] = (string) $entity2['id'];
            }
            else{
              $uniqueName = CRM_Utils_Array::value('uniqueName', $specs);
              $entity[$fieldName] = (string) empty($entity2[$field]) ? CRM_Utils_Array::value($uniqueName, $entity2) : $entity2[$field];
             //todo - there isn't always something set here - & our checking on unset values is limited
              if (empty($entity[$field])) {
                unset($entity[$field]);
              }
            }
          }
          break;

        case CRM_Utils_Type::T_BOOLEAN:
          // probably created with a 1
          $entity[$fieldName] = '0';
          break;

        case CRM_Utils_Type::T_FLOAT:
        case CRM_Utils_Type::T_MONEY:
          $entity[$field] = '222';
          break;

        case CRM_Utils_Type::T_URL:
          $entity[$field] = 'warm.beer.com';
      }
      if (!empty($specs['pseudoconstant'])) {
        $options = $this->callAPISuccess($entityName, 'getoptions', array('context' => 'create', 'field' => $field));
        if (empty($options['values'])) {
        }
        $entity[$field] = array_rand($options['values']);
      }
      $updateParams = array(
        'id' => $entity['id'],
        $field => isset($entity[$field]) ? $entity[$field] : NULL,
      );

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
      $this->assertAPIArrayComparison($entity, $checkEntity, array(), "checking if $fieldName was correctly updaetd\n" . print_r(array('update-params' => $updateParams, 'update-result' => $update, 'getsingle-params' => $checkParams, 'getsingle-result' => $checkEntity, 'expected entity' => $entity), TRUE));
    }
    $baoObj->deleteTestObjects($baoString);
    $baoObj->free();
  }

  /** testing the _getFields **/

  /** testing the _delete **/

  /**
   * @dataProvider toBeSkipped_delete
   entities that don't need a delete action
   */
  public function testNotImplemented_delete($Entity) {
    $nonExistantID = 151416349;
    $result = civicrm_api($Entity, 'Delete', array('version' => 3, 'id' => $nonExistantID));
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
    $this->assertContains("API ($Entity, Delete) does not exist", $result['error_message']);
  }

  /**
   * @dataProvider entities
   * @expectedException PHPUnit_Framework_Error
   */
  public function testWithoutParam_delete($Entity) {
    // should delete php complaining that a param is missing
    $result = civicrm_api($Entity, 'Delete');
  }

  /**
   * @dataProvider entities_delete
   */
  public function testEmptyParam_delete($Entity) {
    if (in_array($Entity, $this->toBeImplemented['delete'])) {
      // $this->markTestIncomplete("civicrm_api3_{$Entity}_delete to be implemented");
      return;
    }
    $result = civicrm_api($Entity, 'Delete', array());
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
    $this->assertContains("Mandatory key(s) missing from params array", $result['error_message']);
  }
  /**
   * @dataProvider entities_delete
   */
  public function testInvalidID_delete($Entity) {
    // turn test off for now
    $this->markTestIncomplete("Entity [ $Entity ] cannot be mocked - no known DAO");
    return;
    if (in_array($Entity, $this->toBeImplemented['delete'])) {
      // $this->markTestIncomplete("civicrm_api3_{$Entity}_delete to be implemented");
      return;
    }
    $result = $this->callAPIFailure($Entity, 'Delete', array('id' => 999));
  }
  /**
   * @dataProvider entities
   */
  public function testDeleteWrongTypeParamTag_delete() {
    $result = civicrm_api("Tag", 'Delete', 'this is not a string');
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
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
      ($createcount + $startCount) -1
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
 * @param entityName
 */private function getMockableBAOObjects($entityName, $count = 2) {
    $baoString = _civicrm_api3_get_DAO($entityName);
    if (empty($baoString)) {
      $this->markTestIncomplete("Entity [$entityName] cannot be mocked - no known DAO");
      return;
    }
    $baos = array();
    $i = 0;
    while($i < $count) {
    // create entities
      $baoObj = CRM_Core_DAO::createTestObject($baoString, array('currency' => 'USD'));
      $this->assertTrue(is_integer($baoObj->id), 'check first id');
      $this->deletableTestObjects[$baoString][] = $baoObj->id;
      $baos[] = $baoObj;
      $i ++;
    }
    return $baos;
  }


  /**
   * Verify that HTML metacharacters provided as inputs appear consistently
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
      'api.event.get' => array(
      ),
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
    $this->assertTrue((bool)$setValueDescriptionResult['is_error']); // not supported by setValue
    //$this->assertEquals('setValueDescription: TheRest <> CiviCRM', $setValueDescriptionResult['values']['description']);
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
      1 => array($eventId, 'Integer')
    ));
    $this->assertDBQuery('createNew: TheRest <> CiviCRM', 'SELECT description FROM civicrm_event WHERE id = %1', array(
      1 => array($eventId, 'Integer')
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
      1 => array($eventId, 'Integer')
    ));
    $this->assertDBQuery('createWithId:  TheRest <> CiviCRM', 'SELECT description FROM civicrm_event WHERE id = %1', array(
      1 => array($eventId, 'Integer')
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
      1 => array($eventId, 'Integer')
    ));
    $setValueDescriptionResult = civicrm_api('Event', 'setvalue', array(
      'version' => 3,
      'id' => $eventId,
      'field' => 'description',
      'value' => 'setValueDescription: TheRest <> CiviCRM',
    ));
    $this->assertTrue((bool)$setValueDescriptionResult['is_error']); // not supported by setValue
    //$this->assertAPISuccess($setValueDescriptionResult);
    //$this->assertDBQuery('setValueDescription: TheRest <> CiviCRM', 'SELECT description FROM civicrm_event WHERE id = %1', array(
    //  1 => array($eventId, 'Integer')
    //));
  }

}
