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
 * Class CRM_Core_BAO_CustomGroupTest
 * @group headless
 */
class CRM_Core_BAO_CustomGroupTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Test getTree().
   */
  public function testGetTree() {
    $customGroup = $this->CustomGroupCreate();
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);
    $result = CRM_Core_BAO_CustomGroup::getTree('Individual', NULL, $customGroup['id']);
    $this->assertEquals('Custom Field', $result[$customGroup['id']]['fields'][$customField['id']]['label']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test calling getTree with contact subtype data.
   *
   * Note that the function seems to support a range of formats so 3 are tested. Yay for
   * inconsistency.
   */
  public function testGetTreeContactSubType() {
    $contactType = $this->callAPISuccess('ContactType', 'create', ['name' => 'Big Bank', 'label' => 'biggee', 'parent_id' => 'Organization']);
    $customGroup = $this->CustomGroupCreate(['extends' => 'Organization', 'extends_entity_column_value' => ['Big_Bank']]);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);
    $result1 = CRM_Core_BAO_CustomGroup::getTree('Organization', NULL, NULL, NULL, ['Big_Bank']);
    $this->assertEquals('Custom Field', $result1[$customGroup['id']]['fields'][$customField['id']]['label']);
    $result = CRM_Core_BAO_CustomGroup::getTree('Organization', NULL, NULL, NULL, CRM_Core_DAO::VALUE_SEPARATOR . 'Big_Bank' . CRM_Core_DAO::VALUE_SEPARATOR);
    $this->assertEquals($result1, $result);
    $result = CRM_Core_BAO_CustomGroup::getTree('Organization', NULL, NULL, NULL, 'Big_Bank');
    $this->assertEquals($result1, $result);
    try {
      CRM_Core_BAO_CustomGroup::getTree('Organization', NULL, NULL, NULL, ['Small Kind Bank']);
    }
    catch (CRM_Core_Exception $e) {
      $this->customGroupDelete($customGroup['id']);
      $this->callAPISuccess('ContactType', 'delete', ['id' => $contactType['id']]);
      return;
    }
    $this->fail('There is no such thing as a small kind bank');
  }

  /**
   * Test calling getTree for a custom field extending a renamed contact type.
   */
  public function testGetTreeContactSubTypeForNameChangedContactType() {
    $contactType = $this->callAPISuccess('ContactType', 'create', ['name' => 'Big Bank', 'label' => 'biggee', 'parent_id' => 'Organization']);
    CRM_Core_DAO::executeQuery('UPDATE civicrm_contact_type SET label = "boo" WHERE name = "Organization"');
    $customGroup = $this->CustomGroupCreate(['extends' => 'Organization', 'extends_entity_column_value' => ['Big_Bank']]);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);
    $result1 = CRM_Core_BAO_CustomGroup::getTree('Organization', NULL, NULL, NULL, ['Big_Bank']);
    $this->assertEquals('Custom Field', $result1[$customGroup['id']]['fields'][$customField['id']]['label']);
    $this->customGroupDelete($customGroup['id']);
    $this->callAPISuccess('ContactType', 'delete', ['id' => $contactType['id']]);
  }

  /**
   * Test calling getTree for a custom field extending a disabled contact type.
   */
  public function testGetTreeContactSubTypeForDisabledChangedContactType() {
    $contactType = $this->callAPISuccess('ContactType', 'create', ['name' => 'Big Bank', 'label' => 'biggee', 'parent_id' => 'Organization']);
    $customGroup = $this->CustomGroupCreate(['extends' => 'Organization', 'extends_entity_column_value' => ['Big_Bank']]);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);
    $this->callAPISuccess('ContactType', 'create', ['id' => $contactType['id'], 'is_active' => 0]);
    $result1 = CRM_Core_BAO_CustomGroup::getTree('Organization', NULL, NULL, NULL, ['Big_Bank']);
    $this->assertEquals('Custom Field', $result1[$customGroup['id']]['fields'][$customField['id']]['label']);
    $this->customGroupDelete($customGroup['id']);
    $this->callAPISuccess('ContactType', 'delete', ['id' => $contactType['id']]);
  }

  /**
   * Test calling GetTree for a custom field extending multiple subTypes.
   */
  public function testGetTreetContactSubTypeForMultipleSubTypes() {
    $contactType1 = $this->callAPISuccess('ContactType', 'create', ['name' => 'Big Bank', 'label' => 'biggee', 'parent_id' => 'Organization']);
    $contactType2 = $this->callAPISuccess('ContactType', 'create', ['name' => 'Small Bank', 'label' => 'smallee', 'parent_id' => 'Organization']);
    $customGroup = $this->CustomGroupCreate(['extends' => 'Organization', 'extends_entity_column_value' => ['Big_Bank', 'Small_Bank']]);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);
    $result1 = CRM_Core_BAO_CustomGroup::getTree('Organization', NULL, NULL, NULL, CRM_Core_DAO::VALUE_SEPARATOR . 'Big_Bank' . CRM_Core_DAO::VALUE_SEPARATOR . 'Small_Bank' . CRM_Core_DAO::VALUE_SEPARATOR);
    $this->assertEquals('Custom Field', $result1[$customGroup['id']]['fields'][$customField['id']]['label']);
    $this->customGroupDelete($customGroup['id']);
    $this->callAPISuccess('ContactType', 'delete', ['id' => $contactType1['id']]);
    $this->callAPISuccess('ContactType', 'delete', ['id' => $contactType2['id']]);
  }

  /**
   * Test calling GetTree for a custom field that extends a non numerical Event Type.
   */
  public function testGetTreeEventSubTypeAlphabetical() {
    $eventType = $this->callAPISuccess('OptionValue', 'Create', ['option_group_id' => 'event_type', 'value' => 'meeting', 'label' => 'Meeting']);
    $customGroup = $this->CustomGroupCreate(['extends' => 'Event', 'extends_entity_column_value' => ['Meeting']]);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);
    $result1 = CRM_Core_BAO_CustomGroup::getTree('Event', NULL, NULL, NULL, CRM_Core_DAO::VALUE_SEPARATOR . 'meeting' . CRM_Core_DAO::VALUE_SEPARATOR);
    $this->assertEquals('Custom Field', $result1[$customGroup['id']]['fields'][$customField['id']]['label']);
    $this->customGroupDelete($customGroup['id']);
    $this->callAPISuccess('OptionValue', 'delete', ['id' => $eventType['id']]);
  }

  /**
   * Test calling getTree with contact subtype data.
   *
   * Note that the function seems to support a range of formats so 3 are tested. Yay for
   * inconsistency.
   */
  public function testGetTreeCampaignSubType() {
    $sep = CRM_Core_DAO::VALUE_SEPARATOR;
    $this->campaignCreate();
    $this->campaignCreate();
    $customGroup = $this->CustomGroupCreate([
      'extends' => 'Campaign',
      'extends_entity_column_value' => "{$sep}1{$sep}2{$sep}",
    ]);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);
    $result1 = CRM_Core_BAO_CustomGroup::getTree('Campaign', NULL, NULL, NULL, '12');
    $this->assertEquals('Custom Field', $result1[$customGroup['id']]['fields'][$customField['id']]['label']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test calling getTree with contact subtype data.
   */
  public function testGetTreeActivitySubType() {
    $customGroup = $this->CustomGroupCreate(['extends' => 'Activity', 'extends_entity_column_value' => 1]);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);
    $result = CRM_Core_BAO_CustomGroup::getTree('Activity', NULL, NULL, NULL, 1);
    $this->assertEquals('Custom Field', $result[$customGroup['id']]['fields'][$customField['id']]['label']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test retrieve() with Empty Params.
   */
  public function testRetrieveEmptyParams() {
    $params = [];
    $customGroup = CRM_Core_BAO_CustomGroup::retrieve($params, $dafaults);
    $this->assertNull($customGroup, 'Check that no custom Group is retreived');
  }

  /**
   * Test retrieve() with Inalid Params
   */
  public function testRetrieveInvalidParams() {
    $params = ['id' => 99];
    $customGroup = CRM_Core_BAO_CustomGroup::retrieve($params, $dafaults);
    $this->assertNull($customGroup, 'Check that no custom Group is retreived');
  }

  /**
   * Test retrieve()
   */
  public function testRetrieve() {
    $customGroupTitle = 'Custom Group';
    $groupParams = [
      'title' => $customGroupTitle,
      'name' => 'My_Custom_Group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'help_pre' => 'Custom Group Help Pre',
      'help_post' => 'Custom Group Help Post',
      'is_active' => 1,
      'collapse_display' => 1,
      'weight' => 2,
    ];

    $customGroup = $this->customGroupCreate($groupParams);

    $this->getAndCheck($groupParams, $customGroup['id'], 'CustomGroup');
  }

  /**
   * Test setIsActive()
   */
  public function testSetIsActive() {
    $customGroupTitle = 'My Custom Group';
    $groupParams = [
      'title' => $customGroupTitle,
      'name' => 'my_custom_group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'is_active' => 0,
    ];

    $customGroup = $this->customGroupCreate($groupParams);
    $customGroupId = $customGroup['id'];

    //update is_active
    $result = CRM_Core_BAO_CustomGroup::setIsActive($customGroupId, TRUE);

    //check for object update
    $this->assertEquals(TRUE, $result);
    //check for is_active
    $this->assertDBCompareValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'is_active', 'id', 1,
      'Database check for custom group is_active field.'
    );

    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test getGroupDetail() with Empty Params
   */
  public function testGetGroupDetailEmptyParams() {
    $customGroupId = [];
    $customGroup = CRM_Core_BAO_CustomGroup::getGroupDetail($customGroupId);
    $this->assertTrue(empty($customGroup), 'Check that no custom Group  details is retreived');
  }

  /**
   * Test getGroupDetail with Invalid Params.
   */
  public function testGetGroupDetailInvalidParams() {
    $customGroupId = 99;
    $customGroup = CRM_Core_BAO_CustomGroup::getGroupDetail($customGroupId);
    $this->assertTrue(empty($customGroup), 'Check that no custom Group  details is retreived');
  }

  /**
   * Test getGroupDetail().
   */
  public function testGetGroupDetail() {
    $customGroupTitle = 'My Custom Group';
    $groupParams = [
      'title' => $customGroupTitle,
      'name' => 'My_Custom_Group',
      'extends' => 'Individual',
      'help_pre' => 'Custom Group Help Pre',
      'help_post' => 'Custom Group Help Post',
      'is_active' => 1,
      'collapse_display' => 1,
    ];

    $customGroup = $this->customGroupCreate($groupParams);
    $customGroupId = $customGroup['id'];

    $fieldParams = [
      'custom_group_id' => $customGroupId,
      'label' => 'Test Custom Field',
      'html_type' => 'Text',
      'data_type' => 'String',
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    ];

    $customField = $this->customFieldCreate($fieldParams);
    $customFieldId = $customField['id'];

    $groupTree = CRM_Core_BAO_CustomGroup::getGroupDetail($customGroupId);
    $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroupId, 'title', 'id',
      'Database check for custom group record.'
    );
    //check retieve values of custom group
    unset($groupParams['is_active']);
    unset($groupParams['title']);
    unset($groupParams['version']);
    $this->assertAttributesEquals($groupParams, $groupTree[$customGroupId]);

    //check retieve values of custom field
    unset($fieldParams['is_active']);
    unset($fieldParams['custom_group_id']);
    unset($fieldParams['version']);
    $this->assertAttributesEquals($fieldParams, $groupTree[$customGroupId]['fields'][$customFieldId], " in line " . __LINE__);

    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test getTitle() with Invalid Params()
   */
  public function testGetTitleWithInvalidParams() {
    $params = 99;
    $customGroupTitle = CRM_Core_BAO_CustomGroup::getTitle($params);

    $this->assertNull($customGroupTitle, 'Check that no custom Group Title is retreived');
  }

  /**
   * Test getTitle()
   */
  public function testGetTitle() {
    $customGroupTitle = 'Custom Group';
    $groupParams = [
      'title' => $customGroupTitle,
      'name' => 'my_custom_group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'is_active' => 0,
    ];

    $customGroup = $this->customGroupCreate($groupParams);
    $customGroupId = $customGroup['id'];

    //get the custom group title
    $title = CRM_Core_BAO_CustomGroup::getTitle($customGroupId);

    //check for object update
    $this->assertEquals($customGroupTitle, $title);

    $this->customGroupDelete($customGroupId);
  }

  /**
   * Test deleteGroup.
   */
  public function testDeleteGroup() {
    $customGroupTitle = 'My Custom Group';
    $groupParams = [
      'title' => $customGroupTitle,
      'name' => 'my_custom_group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'is_active' => 1,
    ];

    $customGroup = $this->customGroupCreate($groupParams);
    $groupObject = new CRM_Core_BAO_CustomGroup();
    $groupObject->id = $customGroup['id'];
    $groupObject->find(TRUE);

    $isDelete = CRM_Core_BAO_CustomGroup::deleteGroup($groupObject);

    // Check it worked!
    $this->assertEquals(TRUE, $isDelete);
    $this->assertDBNull('CRM_Core_DAO_CustomGroup', $customGroup['id'], 'title', 'id',
      'Database check for custom group record.'
    );
  }

  /**
   * Test createTable()
   */
  public function testCreateTable() {
    $groupParams = [
      'title' => 'My Custom Group',
      'name' => 'my_custom_group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'is_active' => 1,
      'version' => 3,
    ];

    $customGroupBAO = new CRM_Core_BAO_CustomGroup();
    $customGroupBAO->copyValues($groupParams);
    $customGroup = $customGroupBAO->save();
    $tableName = 'civicrm_value_test_group_' . $customGroup->id;
    $customGroup->table_name = $tableName;
    $customGroup = $customGroupBAO->save();
    CRM_Core_BAO_CustomGroup::createTable($customGroup);
    $customGroupId = $customGroup->id;

    //check db for custom group.
    $dbCustomGroupTitle = $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroupId, 'title', 'id',
      'Database check for custom group record.'
    );
    //check for custom group table name
    $this->assertDBCompareValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'table_name', 'id',
      $tableName, 'Database check for custom group table name.'
    );

    $this->customGroupDelete($customGroup->id);
  }

  /**
   * Test checkCustomField()
   */
  public function testCheckCustomField() {
    $groupParams = [
      'title' => 'My Custom Group',
      'name' => 'my_custom_group',
      'extends' => 'Individual',
      'help_pre' => 'Custom Group Help Pre',
      'help_post' => 'Custom Group Help Post',
      'is_active' => 1,
      'collapse_display' => 1,
    ];

    $customGroup = $this->customGroupCreate($groupParams);
    $this->assertNotNull($customGroup['id'], 'pre-requisite group not created successfully');
    $customGroupId = $customGroup['id'];

    $customFieldLabel = 'Test Custom Field';
    $fieldParams = [
      'custom_group_id' => $customGroupId,
      'label' => $customFieldLabel,
      'html_type' => 'Text',
      'data_type' => 'String',
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    ];

    $customField = $this->customFieldCreate($fieldParams);
    $customField = $customField['values'][$customField['id']];

    $customFieldId = $customField['id'];

    //check db for custom field
    $dbCustomFieldLabel = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customFieldId, 'label', 'id',
      'Database check for custom field record.'
    );
    $this->assertEquals($customFieldLabel, $dbCustomFieldLabel);

    //check the custom field type.
    $params = ['Individual'];
    $usedFor = CRM_Core_BAO_CustomGroup::checkCustomField($customFieldId, $params);
    $this->assertEquals(FALSE, $usedFor);

    $params = ['Contribution', 'Membership', 'Participant'];
    $usedFor = CRM_Core_BAO_CustomGroup::checkCustomField($customFieldId, $params);
    $this->assertEquals(TRUE, $usedFor);

    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test getActiveGroups() with Invalid Params()
   */
  public function testGetActiveGroupsWithInvalidParams() {
    $contactId = $this->individualCreate();
    $activeGroups = CRM_Core_BAO_CustomGroup::getActiveGroups('ABC', 'civicrm/contact/view/cd', $contactId);
    $this->assertEquals(empty($activeGroups), TRUE, 'Check that Emprt params are retreived');
  }

  public function testGetActiveGroups() {
    $contactId = $this->individualCreate();
    $customGroupTitle = 'Custom Group';
    $groupParams = [
      'title' => $customGroupTitle,
      'name' => 'test_custom_group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'weight' => 10,
      'is_active' => 1,
    ];

    $customGroup = $this->customGroupCreate($groupParams);
    $activeGroup = CRM_Core_BAO_CustomGroup::getActiveGroups('Individual', 'civicrm/contact/view/cd', $contactId);
    foreach ($activeGroup as $key => $value) {
      if ($value['id'] == $customGroup['id']) {
        $this->assertEquals($value['path'], 'civicrm/contact/view/cd');
        $this->assertEquals($value['title'], $customGroupTitle);
        $query = 'reset=1&gid=' . $customGroup['id'] . '&cid=' . $contactId;
        $this->assertEquals($value['query'], $query);
      }
    }

    $this->customGroupDelete($customGroup['id']);
    $this->contactDelete($contactId);
  }

  /**
   * Test create()
   */
  public function testCreate() {
    $params = [
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'extends' => [0 => 'Individual', 1 => []],
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
      'version' => 3,
    ];
    $customGroup = CRM_Core_BAO_CustomGroup::create($params);

    $dbCustomGroupTitle = $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroup->id, 'title', 'id',
      'Database check for custom group record.'
    );
    $this->assertEquals($params['title'], $dbCustomGroupTitle);

    $dbCustomGroupTableName = $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroup->id, 'table_name', 'id',
      'Database check for custom group record.'
    );
    $this->assertEquals(strtolower("civicrm_value_{$params['name']}_{$customGroup->id}"), $dbCustomGroupTableName,
      "The table name should be suffixed with '_ID' unless specified.");

    $this->customGroupDelete($customGroup->id);
  }

  /**
   * Test create() given a table_name
   */
  public function testCreateTableName() {
    $params = [
      'title' => 'Test_Group_2',
      'name' => 'test_group_2',
      'table_name' => 'test_otherTableName',
      'extends' => [0 => 'Individual', 1 => []],
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
    ];
    $customGroup = CRM_Core_BAO_CustomGroup::create($params);

    $dbCustomGroupTitle = $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroup->id, 'title', 'id',
      'Database check for custom group record.'
    );
    $this->assertEquals($params['title'], $dbCustomGroupTitle);

    $dbCustomGroupTableName = $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroup->id, 'table_name', 'id',
      'Database check for custom group record.'
    );
    $this->assertEquals($params['table_name'], $dbCustomGroupTableName);

    $this->customGroupDelete($customGroup->id);
  }

  /**
   * Test isGroupEmpty()
   */
  public function testIsGroupEmpty() {
    $customGroupTitle = 'Test Custom Group';
    $groupParams = [
      'title' => $customGroupTitle,
      'name' => 'test_custom_group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'weight' => 10,
      'is_active' => 1,
    ];

    $customGroup = $this->customGroupCreate($groupParams);
    $customGroupId = $customGroup['id'];
    $isEmptyGroup = CRM_Core_BAO_CustomGroup::isGroupEmpty($customGroupId);

    $this->assertEquals($isEmptyGroup, TRUE, 'Check that custom Group is Empty.');
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test getGroupTitles() with Invalid Params()
   */
  public function testGetGroupTitlesWithInvalidParams() {
    $params = [99];
    $groupTitles = CRM_Core_BAO_CustomGroup::getGroupTitles($params);
    $this->assertTrue(empty($groupTitles), 'Check that no titles are received');
  }

  /**
   * Test getGroupTitles()
   */
  public function testGetGroupTitles() {
    $groupParams = [
      'title' => 'Test Group',
      'name' => 'test_custom_group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'weight' => 10,
      'is_active' => 1,
    ];

    $customGroup = $this->customGroupCreate($groupParams);

    $fieldParams = [
      'label' => 'Custom Field',
      'html_type' => 'Text',
      'data_type' => 'String',
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'custom_group_id' => $customGroup['id'],
    ];

    $customField = $this->customFieldCreate($fieldParams);
    $customFieldId = $customField['id'];

    $params = [$customFieldId];

    $groupTitles = CRM_Core_BAO_CustomGroup::getGroupTitles($params);

    $this->assertEquals($groupTitles[$customFieldId]['groupTitle'], 'Test Group', 'Check Group Title.');
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test that passed dates are extracted from the url when processing custom data.
   */
  public function testExtractGetParamsReturnsDates() {
    // Create a custom group to contain the custom field.
    $groupParams = [
      'title' => 'My Custom Group',
      'name' => 'my_custom_group',
      'extends' => 'Individual',
      'is_active' => 1,
      'collapse_display' => 1,
    ];
    $customGroup = $this->customGroupCreate($groupParams);
    $customGroupId = $customGroup['id'];

    // Create teh custom field.
    $fieldParams = [
      'custom_group_id' => $customGroupId,
      'label' => 'My Custom Date Field',
      'html_type' => 'Select Date',
      'data_type' => 'Date',
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'default_value' => '',
    ];
    $customField = $this->customFieldCreate($fieldParams);
    $customFieldId = $customField['id'];

    // Create a form object. CRM_Core_BAO_CustomGroup::extractGetParams() will
    // need this, along with the REQUEST_METHOD and controller too.
    $form = new CRM_Contribute_Form_Contribution();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $form->controller = new CRM_Core_Controller();

    // Set the value in $_GET, then extract query string params with
    $fieldName = 'custom_' . $customFieldId;
    $_GET[$fieldName] = '2017-06-13';
    $extractedGetParams = CRM_Core_BAO_CustomGroup::extractGetParams($form, 'Individual');

    $this->assertEquals($extractedGetParams[$fieldName], '2017-06-13');
  }

}
