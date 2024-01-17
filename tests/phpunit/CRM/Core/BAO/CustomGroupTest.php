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

use Civi\Api4\CustomGroup;

/**
 * Class CRM_Core_BAO_CustomGroupTest
 * @group headless
 */
class CRM_Core_BAO_CustomGroupTest extends CiviUnitTestCase {

  /**
   * Clean up after test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->quickCleanup(['civicrm_contact', 'civicrm_campaign'], TRUE);
    parent::tearDown();
  }

  public function testGetAll(): void {
    $this->quickCleanup([], TRUE);

    $activeGroup = $this->CustomGroupCreate(['title' => 'ActiveGroup', 'weight' => 1, 'extends' => 'Household']);
    $this->customFieldCreate(['label' => 'Active', 'custom_group_id' => $activeGroup['id']]);
    $this->customFieldCreate(['label' => 'Disabled', 'is_active' => 0, 'custom_group_id' => $activeGroup['id']]);

    $inactiveGroup = $this->CustomGroupCreate(['title' => 'InactiveGroup', 'weight' => 2, 'is_active' => 0, 'extends' => 'Activity']);
    $this->customFieldCreate(['label' => 'Inactive', 'custom_group_id' => $inactiveGroup['id']]);

    $activityTypeGroup = $this->CustomGroupCreate(['title' => 'ActivityTypeGroup', 'weight' => 3, 'extends' => 'Activity', 'extends_entity_column_value' => [1, 2]]);

    $allGroups = CRM_Core_BAO_CustomGroup::getAll();
    $this->assertSame([(int) $activeGroup['id'], (int) $inactiveGroup['id'], (int) $activityTypeGroup['id']], array_keys($allGroups));

    $this->assertCount(2, $allGroups[$activeGroup['id']]['fields']);
    $this->assertCount(1, $allGroups[$inactiveGroup['id']]['fields']);
    $this->assertCount(0, $allGroups[$activityTypeGroup['id']]['fields']);

    $activeGroups = CRM_Core_BAO_CustomGroup::getAll(['is_active' => TRUE]);
    $this->assertCount(2, $activeGroups);
    $this->assertTrue($activeGroups[$activeGroup['id']]['is_active']);
    $this->assertSame($activeGroup['id'], array_keys($activeGroups)[0]);
    $activeFields = array_values($activeGroups[$activeGroup['id']]['fields']);
    $this->assertCount(1, $activeFields);
    $this->assertTrue($activeFields[0]['is_active']);
    $this->assertNull($activeFields[0]['help_pre']);

    $activityGroups = CRM_Core_BAO_CustomGroup::getAll(['extends' => 'Activity']);
    $this->assertCount(2, $activityGroups);
    $this->assertEquals($inactiveGroup['id'], array_values($activityGroups)[0]['id']);

    // When in an array, "Contact" means "Contact only" so the household group will not be returned
    $contactActivityGroups = CRM_Core_BAO_CustomGroup::getAll(['is_active' => TRUE, 'extends' => ['Contact', 'Activity']]);
    $this->assertCount(1, $contactActivityGroups);
    $this->assertEquals([$activityTypeGroup['id']], array_keys($contactActivityGroups));
    $this->assertCount(0, $contactActivityGroups[$activityTypeGroup['id']]['fields']);

    // When passed as a string, "Contact" means ["Contact", "Individual", "Household", "Organization"]
    $contactGroups = CRM_Core_BAO_CustomGroup::getAll(['is_active' => TRUE, 'extends' => 'Contact']);
    $this->assertEquals([$activeGroup['id']], array_keys($contactGroups));
    $this->assertCount(1, $contactGroups[$activeGroup['id']]['fields']);

    $this->assertCount(0, CRM_Core_BAO_CustomGroup::getAll(['extends_entity_column_value' => 3]));
    $this->assertCount(1, CRM_Core_BAO_CustomGroup::getAll(['extends_entity_column_value' => 2]));
    $this->assertCount(1, CRM_Core_BAO_CustomGroup::getAll(['extends_entity_column_value' => [2, 4]]));
    $this->assertCount(3, CRM_Core_BAO_CustomGroup::getAll(['extends_entity_column_value' => [1, NULL]]));
  }

  /**
   * Test getTree().
   */
  public function testGetTree(): void {
    $customGroup = $this->CustomGroupCreate();
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);
    $result = CRM_Core_BAO_CustomGroup::getTree('Individual', NULL, $customGroup['id']);
    $this->assertEquals('Custom Field', $result[$customGroup['id']]['fields'][$customField['id']]['label']);
  }

  /**
   * Test calling getTree with contact subtype data.
   *
   * Note that the function seems to support a range of formats so 3 are tested. Yay for
   * inconsistency.
   */
  public function testGetTreeContactSubType(): void {
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
  public function testGetTreeContactSubTypeForNameChangedContactType(): void {
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
  public function testGetTreeContactSubTypeForDisabledChangedContactType(): void {
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
  public function testGetTreetContactSubTypeForMultipleSubTypes(): void {
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
  public function testGetTreeEventSubTypeAlphabetical(): void {
    $eventType = $this->callAPISuccess('OptionValue', 'Create', ['option_group_id' => 'event_type', 'value' => '99_ish', 'name' => 'Meeting_99', 'label' => 'Meeting 99']);
    $customGroup = $this->CustomGroupCreate(['extends' => 'Event', 'extends_entity_column_value' => ['99_ish']]);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);
    $result1 = CRM_Core_BAO_CustomGroup::getTree('Event', NULL, NULL, NULL, CRM_Core_DAO::VALUE_SEPARATOR . 'meeting_99' . CRM_Core_DAO::VALUE_SEPARATOR);
    $this->assertEquals('Custom Field', $result1[$customGroup['id']]['fields'][$customField['id']]['label']);
    $result1 = CRM_Core_BAO_CustomGroup::getTree('Event', NULL, NULL, NULL, ['99_ish']);
    $this->assertEquals('Custom Field', $result1[$customGroup['id']]['fields'][$customField['id']]['label']);
    $result1 = CRM_Core_BAO_CustomGroup::getTree('Event', NULL, NULL, NULL, ['99_ISH']);
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
  public function testGetTreeCampaignSubType(): void {
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
  public function testGetTreeActivitySubType(): void {
    $customGroup = $this->CustomGroupCreate(['extends' => 'Activity', 'extends_entity_column_value' => 1]);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);
    $result = CRM_Core_BAO_CustomGroup::getTree('Activity', NULL, NULL, NULL, 1);
    $this->assertEquals('Custom Field', $result[$customGroup['id']]['fields'][$customField['id']]['label']);
  }

  /**
   * Test retrieve() with Empty Params.
   */
  public function testRetrieveEmptyParams(): void {
    $params = [];
    $customGroup = CRM_Core_BAO_CustomGroup::retrieve($params, $dafaults);
    $this->assertNull($customGroup, 'Check that no custom Group is retreived');
  }

  /**
   * Test retrieve() with Inalid Params
   */
  public function testRetrieveInvalidParams(): void {
    $params = ['id' => 99];
    $customGroup = CRM_Core_BAO_CustomGroup::retrieve($params, $dafaults);
    $this->assertNull($customGroup, 'Check that no custom Group is retreived');
  }

  /**
   * Test retrieve()
   */
  public function testRetrieve(): void {
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
   * Test getGroupDetail().
   */
  public function testGetGroupDetail(): void {
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

    $field1Params = [
      'label' => 'Test Custom Field1',
      'html_type' => 'Text',
      'data_type' => 'String',
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    ];
    $customField1 = $this->customFieldCreate($field1Params + ['custom_group_id' => $customGroupId]);
    $field1Id = $customField1['id'];

    $field2Params = [
      'label' => 'Test Custom Field2',
      'html_type' => 'Text',
      'data_type' => 'String',
      'is_required' => 0,
      'is_searchable' => 1,
      'is_active' => 1,
    ];
    $customField2 = $this->customFieldCreate($field2Params + ['custom_group_id' => $customGroupId]);
    $field2Id = $customField2['id'];

    $emptyTree = CRM_Core_BAO_CustomGroup::getGroupDetail(99);
    $this->assertCount(0, $emptyTree, 'Check that no custom Group matches id=99');

    $groupTree = CRM_Core_BAO_CustomGroup::getGroupDetail($customGroupId);
    $this->assertCount(1, $groupTree);
    $this->assertCount(2, $groupTree[$customGroupId]['fields']);
    //check values of custom group
    $this->assertAttributesEquals($groupParams, $groupTree[$customGroupId]);
    //check values of custom fields
    $this->assertAttributesEquals($field1Params, $groupTree[$customGroupId]['fields'][$field1Id]);
    $this->assertAttributesEquals($field2Params, $groupTree[$customGroupId]['fields'][$field2Id]);

    $searchableTree = CRM_Core_BAO_CustomGroup::getGroupDetail($customGroupId, TRUE);
    $this->assertCount(1, $searchableTree[$customGroupId]['fields']);
    $this->assertAttributesEquals($groupParams, $searchableTree[$customGroupId]);
    // only searchable field should be returned
    $this->assertAttributesEquals($field2Params, $searchableTree[$customGroupId]['fields'][$field2Id]);
  }

  /**
   * Test getTitle()
   */
  public function testGetTitle(): void {
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

    // Should return NULL for a group that doesn't exist
    $this->assertNull(CRM_Core_BAO_CustomGroup::getTitle($customGroupId + 99));

    //get the custom group title
    $title = CRM_Core_BAO_CustomGroup::getTitle($customGroupId);

    //check for object update
    $this->assertEquals($customGroupTitle, $title);
  }

  /**
   * Test deleteGroup.
   */
  public function testDeleteGroup(): void {
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
   *
   * @throws \Exception
   */
  public function testCreateTable(): void {
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
    $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroupId, 'title', 'id',
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testCheckCustomField(): void {
    $groupParams = [
      'title' => 'My Custom Group',
      'name' => 'my_custom_group',
      'extends' => 'Individual',
    ];

    $customGroup = $this->customGroupCreate($groupParams);
    $this->assertNotNull($customGroup['id'], 'pre-requisite group not created successfully');
    $customGroupId = $customGroup['id'];

    $customFieldLabel = 'Test Custom Field';
    $fieldParams = [
      'custom_group_id' => $customGroupId,
      'label' => $customFieldLabel,
    ];

    $customField = $this->customFieldCreate($fieldParams);
    $customField = $customField['values'][$customField['id']];

    $customFieldId = $customField['id'];

    //check the custom field type.
    $usedFor = CRM_Core_BAO_CustomGroup::checkCustomField(
      $customFieldId, ['Individual']
    );
    $this->assertEquals(FALSE, $usedFor);

    $usedFor = CRM_Core_BAO_CustomGroup::checkCustomField(
      $customFieldId, ['Contribution', 'Membership', 'Participant']
    );
    $this->assertEquals(TRUE, $usedFor);
  }

  /**
   * Test create()
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreate(): void {
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
    $customGroupID = $this->callAPISuccess('CustomGroup', 'create', $params)['id'];

    $dbCustomGroupTitle = $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroupID, 'title', 'id',
      'Database check for custom group record.'
    );
    $this->assertEquals($params['title'], $dbCustomGroupTitle);

    $dbCustomGroupTableName = $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroupID, 'table_name', 'id',
      'Database check for custom group record.'
    );
    $this->assertEquals(strtolower("civicrm_value_{$params['name']}_$customGroupID"), $dbCustomGroupTableName,
      "The table name should be suffixed with '_ID' unless specified.");
  }

  /**
   * Test create() given a table_name
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateTableName(): void {
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
    $customGroupID = $this->callAPISuccess('CustomGroup', 'create', $params)['id'];
    $group = CustomGroup::get()
      ->addWhere('id', '=', $customGroupID)
      ->addSelect('title', 'table_name')
      ->execute()->first();
    $this->assertEquals('Test_Group_2', $group['title']);
    $this->assertEquals('test_otherTableName', $group['table_name']);
  }

  /**
   * Test isGroupEmpty()
   */
  public function testIsGroupEmpty(): void {
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
   * Test getGroupTitles()
   */
  public function testGetGroupTitles(): void {
    $groupParams = [
      'title' => 'Test Group',
      'name' => 'test_custom_group',
      'is_active' => 0,
    ];

    $customGroup = $this->customGroupCreate($groupParams);

    $fieldParams = [
      'label' => 'Custom Field',
      'is_active' => 0,
      'custom_group_id' => $customGroup['id'],
    ];

    $customField = $this->customFieldCreate($fieldParams);
    $customFieldId = $customField['id'];

    $this->assertEmpty(CRM_Core_BAO_CustomGroup::getGroupTitles([$customFieldId + 99]));

    $groupTitles = CRM_Core_BAO_CustomGroup::getGroupTitles([$customFieldId]);

    $this->assertEquals('Test Group', $groupTitles[$customFieldId]['groupTitle']);
    $this->assertEquals($customGroup['id'], $groupTitles[$customFieldId]['groupID']);
    $this->assertEquals('Custom Field', $groupTitles[$customFieldId]['fieldLabel']);
    $this->assertEquals($customField['id'], $groupTitles[$customFieldId]['fieldID']);
  }

  /**
   * Test that passed dates are extracted from the url when processing custom data.
   */
  public function testExtractGetParamsReturnsDates(): void {
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

  /**
   * @return array
   */
  public function getGroupNames(): array {
    $data = [
      ['Individual', 'first_name', FALSE],
      ['Organization', 'first_name', FALSE],
      ['Contact', 'not_first_name', TRUE],
      ['Contact', 'gender_id', FALSE],
      ['Activity', 'activity_type_id', FALSE],
      ['Campaign', 'activity_type_id', TRUE],
      ['Campaign', 'campaign_type_id', FALSE],
    ];
    // Add descriptive keys to data
    $dataSet = [];
    foreach ($data as $item) {
      $dataSet[$item[0] . '.' . $item[1]] = $item;
    }
    return $dataSet;
  }

  /**
   * @param string $extends
   * @param string $name
   * @param bool $isAllowed
   * @dataProvider getGroupNames
   */
  public function testAllowedGroupNames(string $extends, string $name, bool $isAllowed) {
    $group = new CRM_Core_DAO_CustomGroup();
    $group->name = $name;
    $group->extends = $extends;
    $expectedName = $isAllowed ? $name : $name . '0';
    CRM_Core_BAO_CustomGroup::validateCustomGroupName($group);
    $this->assertEquals($expectedName, $group->name);
  }

  public function testCustomGroupExtends(): void {
    $extends = \CRM_Core_SelectValues::customGroupExtends();
    $this->assertArrayHasKey('Contribution', $extends);
    $this->assertArrayHasKey('Case', $extends);
    $this->assertArrayHasKey('Contact', $extends);
    $this->assertArrayHasKey('Individual', $extends);
    $this->assertArrayHasKey('Household', $extends);
    $this->assertArrayHasKey('Organization', $extends);
    $this->assertArrayHasKey('Participant', $extends);
    $this->assertArrayHasKey('ParticipantRole', $extends);
    $this->assertArrayHasKey('ParticipantEventName', $extends);
    $this->assertArrayHasKey('ParticipantEventType', $extends);
  }

  public function testMapTableName(): void {
    $this->assertEquals('civicrm_case', CRM_Core_BAO_CustomGroup::mapTableName('Case'));
    $this->assertEquals('civicrm_contact', CRM_Core_BAO_CustomGroup::mapTableName('Contact'));
    $this->assertEquals('civicrm_contact', CRM_Core_BAO_CustomGroup::mapTableName('Individual'));
    $this->assertEquals('civicrm_participant', CRM_Core_BAO_CustomGroup::mapTableName('Participant'));
  }

}
