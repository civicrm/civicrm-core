<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Class CRM_Core_BAO_CustomGroupTest
 */
class CRM_Core_BAO_CustomGroupTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Test getTree().
   */
  public function testGetTree() {
    $params = array();
    $contactId = Contact::createIndividual();
    $customGrouptitle = 'My Custom Group';
    $groupParams = array(
      'title' => $customGrouptitle,
      'name' => 'my_custom_group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'is_active' => 1,
      'version' => 3,
    );

    $customGroup = Custom::createGroup($groupParams);

    $customGroupId = $customGroup->id;

    $fields = array(
      'groupId' => $customGroupId,
      'dataType' => 'String',
      'htmlType' => 'Text',
    );

    $customField = Custom::createField($params, $fields);
    $formParams = NULL;
    $getTree = CRM_Core_BAO_CustomGroup::getTree('Individual', $formParams, $customGroupId);

    $dbCustomGroupTitle = $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroupId, 'title', 'id',
      'Database check for custom group record.'
    );

    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
    Contact::delete($contactId);
    $customGroup->free();
  }

  /**
   * Test retrieve() with Empty Params
   */
  public function testRetrieveEmptyParams() {
    $params = array();
    $customGroup = CRM_Core_BAO_CustomGroup::retrieve($params, $dafaults);
    $this->assertNull($customGroup, 'Check that no custom Group is retreived');
  }

  /**
   * Test retrieve() with Inalid Params
   */
  public function testRetrieveInvalidParams() {
    $params = array('id' => 99);
    $customGroup = CRM_Core_BAO_CustomGroup::retrieve($params, $dafaults);
    $this->assertNull($customGroup, 'Check that no custom Group is retreived');
  }

  /**
   * Test retrieve()
   */
  public function testRetrieve() {
    $customGrouptitle = 'My Custom Group';
    $groupParams = array(
      'title' => $customGrouptitle,
      'name' => 'My_Custom_Group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'help_pre' => 'Custom Group Help Pre',
      'help_post' => 'Custom Group Help Post',
      'is_active' => 1,
      'collapse_display' => 1,
      'weight' => 2,
      'version' => 3,
    );

    $customGroup = Custom::createGroup($groupParams);
    $customGroupId = $customGroup->id;

    $params = array('id' => $customGroupId);
    $customGroup = CRM_Core_BAO_CustomGroup::retrieve($params, $dafaults);
    $dbCustomGroupTitle = $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroupId, 'title', 'id',
      'Database check for custom group record.'
    );

    $this->assertEquals($customGrouptitle, $dbCustomGroupTitle);
    //check retieve values
    unset($groupParams['version']);
    $this->assertAttributesEquals($groupParams, $dafaults);

    //cleanup DB by deleting customGroup
    Custom::deleteGroup($customGroup);
  }

  /**
   * Test setIsActive()
   */
  public function testSetIsActive() {
    $customGrouptitle = 'My Custom Group';
    $groupParams = array(
      'title' => $customGrouptitle,
      'name' => 'my_custom_group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'is_active' => 0,
      'version' => 3,
    );

    $customGroup = Custom::createGroup($groupParams);
    $customGroupId = $customGroup->id;

    //update is_active
    $result = CRM_Core_BAO_CustomGroup::setIsActive($customGroupId, TRUE);

    //check for object update
    $this->assertEquals(TRUE, $result);
    //check for is_active
    $this->assertDBCompareValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'is_active', 'id', 1,
      'Database check for custom group is_active field.'
    );
    //cleanup DB by deleting customGroup
    Custom::deleteGroup($customGroup);
  }

  /**
   * Test getGroupDetail() with Empty Params
   */
  public function testGetGroupDetailEmptyParams() {
    $customGroupId = array();
    $customGroup = CRM_Core_BAO_CustomGroup::getGroupDetail($customGroupId);
    $this->assertTrue(empty($customGroup), 'Check that no custom Group  details is retreived');
  }

  /**
   * Test getGroupDetail() with Inalid Params
   */
  public function testGetGroupDetailInvalidParams() {
    $customGroupId = 99;
    $customGroup = CRM_Core_BAO_CustomGroup::getGroupDetail($customGroupId);
    $this->assertTrue(empty($customGroup), 'Check that no custom Group  details is retreived');
  }

  /**
   * Test getGroupDetail()
   */
  public function testGetGroupDetail() {
    $customGrouptitle = 'My Custom Group';
    $groupParams = array(
      'title' => $customGrouptitle,
      'name' => 'My_Custom_Group',
      'extends' => 'Individual',
      'help_pre' => 'Custom Group Help Pre',
      'help_post' => 'Custom Group Help Post',
      'is_active' => 1,
      'collapse_display' => 1,
      'version' => 3,
    );

    $customGroup = Custom::createGroup($groupParams);
    $customGroupId = $customGroup->id;

    $fieldParams = array(
      'custom_group_id' => $customGroupId,
      'label' => 'Test Custom Field',
      'html_type' => 'Text',
      'data_type' => 'String',
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'version' => 3,
    );

    $customField = Custom::createField($fieldParams);
    $customFieldId = $customField->id;

    $groupTree = CRM_Core_BAO_CustomGroup::getGroupDetail($customGroupId);
    $dbCustomGroupTitle = $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroupId, 'title', 'id',
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

    //cleanup DB by deleting customGroup
    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
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
    $customGrouptitle = 'My Custom Group';
    $groupParams = array(
      'title' => $customGrouptitle,
      'name' => 'my_custom_group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'is_active' => 0,
      'version' => 3,
    );

    $customGroup = Custom::createGroup($groupParams);
    $customGroupId = $customGroup->id;

    //get the custom group title
    $title = CRM_Core_BAO_CustomGroup::getTitle($customGroupId);

    //check for object update
    $this->assertEquals($customGrouptitle, $title);

    //cleanup DB by deleting customGroup
    Custom::deleteGroup($customGroup);
  }

  /**
   * Test deleteGroup()
   */
  public function testDeleteGroup() {
    $customGrouptitle = 'My Custom Group';
    $groupParams = array(
      'title' => $customGrouptitle,
      'name' => 'my_custom_group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'is_active' => 1,
      'version' => 3,
    );

    $customGroup = Custom::createGroup($groupParams);

    $customGroupId = $customGroup->id;

    //get the custom group title
    $dbCustomGroupTitle = $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroupId, 'title', 'id',
      'Database check for custom group record.'
    );
    //check for group title
    $this->assertEquals($customGrouptitle, $dbCustomGroupTitle);

    //delete the group
    $isDelete = CRM_Core_BAO_CustomGroup::deleteGroup($customGroup);

    //check for delete
    $this->assertEquals(TRUE, $isDelete);

    //check the DB
    $this->assertDBNull('CRM_Core_DAO_CustomGroup', $customGroupId, 'title', 'id',
      'Database check for custom group record.'
    );
  }

  /**
   * Test createTable()
   */
  public function testCreateTable() {
    $customGrouptitle = 'My Custom Group';
    $groupParams = array(
      'title' => $customGrouptitle,
      'name' => 'my_custom_group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'is_active' => 1,
      'version' => 3,
    );

    $customGroupBAO = new CRM_Core_BAO_CustomGroup();
    $customGroupBAO->copyValues($groupParams);
    $customGroup = $customGroupBAO->save();
    $tableName = 'civicrm_value_test_group_' . $customGroup->id;
    $customGroup->table_name = $tableName;
    $customGroup = $customGroupBAO->save();
    $customTable = CRM_Core_BAO_CustomGroup::createTable($customGroup);
    $customGroupId = $customGroup->id;

    //check db for custom group.
    $dbCustomGroupTitle = $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroupId, 'title', 'id',
      'Database check for custom group record.'
    );
    //check for custom group table name
    $this->assertDBCompareValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'table_name', 'id',
      $tableName, 'Database check for custom group table name.'
    );

    //check for group title
    $this->assertEquals($customGrouptitle, $dbCustomGroupTitle);

    //cleanup DB by deleting customGroup
    Custom::deleteGroup($customGroup);
  }

  /**
   * Test checkCustomField()
   */
  public function testCheckCustomField() {
    $customGroupTitle = 'My Custom Group';
    $groupParams = array(
      'title' => $customGroupTitle,
      'name' => 'my_custom_group',
      'extends' => 'Individual',
      'help_pre' => 'Custom Group Help Pre',
      'help_post' => 'Custom Group Help Post',
      'is_active' => 1,
      'collapse_display' => 1,
      'version' => 3,
    );

    $customGroup = Custom::createGroup($groupParams);
    $this->assertNotNull($customGroup->id, 'pre-requisite group not created successfully');
    $customGroupId = $customGroup->id;

    $customFieldLabel = 'Test Custom Field';
    $fieldParams = array(
      'custom_group_id' => $customGroupId,
      'label' => $customFieldLabel,
      'html_type' => 'Text',
      'data_type' => 'String',
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'version' => 3,
    );

    $customField = Custom::createField($fieldParams);
    $this->assertNotNull($customField->id, 'pre-requisite field not created successfully');

    $customFieldId = $customField->id;

    //check db for custom group
    $dbCustomGroupTitle = $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroupId, 'title', 'id',
      'Database check for custom group record.'
    );
    $this->assertEquals($customGroupTitle, $dbCustomGroupTitle);

    //check db for custom field
    $dbCustomFieldLabel = $this->assertDBNotNull('CRM_Core_DAO_CustomField', $customFieldId, 'label', 'id',
      'Database check for custom field record.'
    );
    $this->assertEquals($customFieldLabel, $dbCustomFieldLabel);

    //check the custom field type.
    $params = array('Individual');
    $usedFor = CRM_Core_BAO_CustomGroup::checkCustomField($customFieldId, $params);
    $this->assertEquals(FALSE, $usedFor);

    $params = array('Contribution', 'Membership', 'Participant');
    $usedFor = CRM_Core_BAO_CustomGroup::checkCustomField($customFieldId, $params);
    $this->assertEquals(TRUE, $usedFor);

    //cleanup DB by deleting customGroup
    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
  }

  /**
   * Test getActiveGroups() with Invalid Params()
   */
  public function testGetActiveGroupsWithInvalidParams() {
    $contactId = Contact::createIndividual();
    $activeGroups = CRM_Core_BAO_CustomGroup::getActiveGroups('ABC', 'civicrm/contact/view/cd', $contactId);
    $this->assertEquals(empty($activeGroups), TRUE, 'Check that Emprt params are retreived');
  }

  public function testGetActiveGroups() {
    $contactId = Contact::createIndividual();
    $customGrouptitle = 'Test Custom Group';
    $groupParams = array(
      'title' => $customGrouptitle,
      'name' => 'test_custom_group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'weight' => 10,
      'is_active' => 1,
      'version' => 3,
    );

    $customGroup = Custom::createGroup($groupParams);
    $activeGroup = CRM_Core_BAO_CustomGroup::getActiveGroups('Individual', 'civicrm/contact/view/cd', $contactId);
    foreach ($activeGroup as $key => $value) {
      if ($value['id'] == $customGroup->id) {
        $this->assertEquals($value['path'], 'civicrm/contact/view/cd');
        $this->assertEquals($value['title'], $customGrouptitle);
        $query = 'reset=1&gid=' . $customGroup->id . '&cid=' . $contactId;
        $this->assertEquals($value['query'], $query);
      }
    }

    Custom::deleteGroup($customGroup);
    Contact::delete($contactId);
  }

  /**
   * Test create()
   */
  public function testCreate() {
    $params = array(
      'title' => 'Test_Group_1',
      'name' => 'test_group_1',
      'extends' => array(0 => 'Individual', 1 => array()),
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
      'version' => 3,
    );
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

    Custom::deleteGroup($customGroup);
  }

  /**
   * Test create() given a table_name
   */
  public function testCreateTableName() {
    $params = array(
      'title' => 'Test_Group_2',
      'name' => 'test_group_2',
      'table_name' => 'test_otherTableName',
      'extends' => array(0 => 'Individual', 1 => array()),
      'weight' => 4,
      'collapse_display' => 1,
      'style' => 'Inline',
      'help_pre' => 'This is Pre Help For Test Group 1',
      'help_post' => 'This is Post Help For Test Group 1',
      'is_active' => 1,
      'version' => 3,
    );
    $customGroup = CRM_Core_BAO_CustomGroup::create($params);

    $dbCustomGroupTitle = $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroup->id, 'title', 'id',
      'Database check for custom group record.'
    );
    $this->assertEquals($params['title'], $dbCustomGroupTitle);

    $dbCustomGroupTableName = $this->assertDBNotNull('CRM_Core_DAO_CustomGroup', $customGroup->id, 'table_name', 'id',
      'Database check for custom group record.'
    );
    $this->assertEquals($params['table_name'], $dbCustomGroupTableName);

    Custom::deleteGroup($customGroup);
  }

  /**
   * Test isGroupEmpty()
   */
  public function testIsGroupEmpty() {
    $customGrouptitle = 'Test Custom Group';
    $groupParams = array(
      'title' => $customGrouptitle,
      'name' => 'test_custom_group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'weight' => 10,
      'is_active' => 1,
      'version' => 3,
    );

    $customGroup = Custom::createGroup($groupParams);
    $customGroupId = $customGroup->id;
    $isEmptyGroup = CRM_Core_BAO_CustomGroup::isGroupEmpty($customGroupId);

    $this->assertEquals($isEmptyGroup, TRUE, 'Check that custom Group is Empty.');
    Custom::deleteGroup($customGroup);
  }

  /**
   * Test getGroupTitles() with Invalid Params()
   */
  public function testgetGroupTitlesWithInvalidParams() {
    $params = array(99);
    $groupTitles = CRM_Core_BAO_CustomGroup::getGroupTitles($params);
    $this->assertTrue(empty($groupTitles), 'Check that no titles are recieved');
  }

  /**
   * Test getGroupTitles()
   */
  public function testgetGroupTitles() {
    $customGrouptitle = 'Test Custom Group';
    $groupParams = array(
      'title' => $customGrouptitle,
      'name' => 'test_custom_group',
      'style' => 'Tab',
      'extends' => 'Individual',
      'weight' => 10,
      'is_active' => 1,
      'version' => 3,
    );

    $customGroup = Custom::createGroup($groupParams);

    $customGroupId = $customGroup->id;

    $customFieldLabel = 'Test Custom Field';
    $fieldParams = array(
      'custom_group_id' => $customGroupId,
      'label' => $customFieldLabel,
      'html_type' => 'Text',
      'data_type' => 'String',
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'version' => 3,
    );

    $customField = Custom::createField($fieldParams);
    $customFieldId = $customField->id;

    $params = array($customFieldId);

    $groupTitles = CRM_Core_BAO_CustomGroup::getGroupTitles($params);

    $this->assertEquals($groupTitles[$customFieldId]['groupTitle'], 'Test Custom Group', 'Check Group Title.');
    Custom::deleteGroup($customGroup);
  }

}
