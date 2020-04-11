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
 * Test class for CRM_Contact_BAO_Group BAO
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Contact_BAO_GroupTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   */
  protected function tearDown() {
    $this->quickCleanup(['civicrm_mapping_field', 'civicrm_mapping', 'civicrm_group', 'civicrm_saved_search']);
  }

  /**
   * Test case for add( ).
   */
  public function testAddSimple() {

    $checkParams = $params = [
      'title' => 'Group Uno',
      'description' => 'Group One',
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
    ];

    $group = CRM_Contact_BAO_Group::create($params);

    $this->assertDBCompareValues(
      'CRM_Contact_DAO_Group',
      ['id' => $group->id],
      $checkParams
    );
  }

  /**
   * Test case to ensure child group is present in the hierarchy
   *  if it has multiple parent groups and not all are disabled.
   */
  public function testGroupHirearchy() {
    // Use-case :
    // 1. Create two parent group A and B and disable B
    // 2. Create a child group C
    // 3. Ensure that Group C is present in the group hierarchy
    $params = [
      'name' => uniqid(),
      'title' => 'Parent Group A',
      'description' => 'Parent Group One',
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
    ];
    $group1 = CRM_Contact_BAO_Group::create($params);

    $params = array_merge($params, [
      'name' => uniqid(),
      'title' => 'Parent Group B',
      'description' => 'Parent Group Two',
      // disable
      'is_active' => 0,
    ]);
    $group2 = CRM_Contact_BAO_Group::create($params);

    $params = array_merge($params, [
      'name' => uniqid(),
      'title' => 'Child Group C',
      'description' => 'Child Group C',
      'parents' => [
        $group1->id => 1,
        $group2->id => 1,
      ],
    ]);
    $group3 = CRM_Contact_BAO_Group::create($params);

    $params = [
      $group1->id => 1,
      $group3->id => 1,
    ];
    $groupsHierarchy = CRM_Contact_BAO_Group::getGroupsHierarchy($params, NULL, '&nbsp;&nbsp;', TRUE);
    // check if child group is present in the tree with formatted group title prepended with spacer '&nbsp;&nbsp;'
    $this->assertEquals('&nbsp;&nbsp;Child Group C', $groupsHierarchy[$group3->id]);

    // Disable parent group A and ensure that child group C is not present as both of its parent groups are disabled
    $group1->is_active = 0;
    $group1->save();
    $groupsHierarchy = CRM_Contact_BAO_Group::getGroupsHierarchy($params, NULL, '&nbsp;&nbsp;', TRUE);
    $this->assertFalse(array_key_exists($group3->id, $groupsHierarchy));
  }

  /**
   * Test adding a smart group.
   */
  public function testAddSmart() {

    $checkParams = $params = [
      'title' => 'Group Dos',
      'description' => 'Group Two',
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
      'formValues' => ['sort_name' => 'Adams'],
    ];

    $group = CRM_Contact_BAO_Group::createSmartGroup($params);

    unset($checkParams['formValues']);
    $this->assertDBCompareValues(
      'CRM_Contact_DAO_Group',
      ['id' => $group->id],
      $checkParams
    );
  }

  /**
   * Load all sql data sets & return an array of saved searches.
   *
   * @return array
   */
  public function dataProviderSavedSearch() {

    $this->loadSavedSearches();
    $results = CRM_Core_DAO::singleValueQuery('SELECT GROUP_CONCAT(id) FROM civicrm_group WHERE saved_search_id IS NOT NULL');
    return [explode(',', $results)];
  }

  /**
   * Load saved search sql files into the DB.
   */
  public function loadSavedSearches() {
    foreach (glob(dirname(__FILE__) . "/SavedSearchDataSets/*.sql") as $file) {
      CRM_Utils_File::sourceSQLFile(NULL, $file);
    }
  }

  /**
   * Check we can load smart groups based on config from 'real DBs' without fatal errors.
   *
   * Note that we are only testing lack of errors at this stage
   * @todo - for some reason the data was getting truncated from the group table using dataprovider - would be preferable to get that working
   * //@notdataProvider dataProviderSavedSearch
   * //@notparam integer $groupID
   *
   * To add to this dataset do
   *
   *  SET @groupID = x;
   *  SELECT mapping_id FROM civicrm_group g LEFT JOIN civicrm_saved_search s ON saved_search_id = s.id WHERE g.id = @groupID INTO @mappingID;
   * SELECT * FROM civicrm_mapping WHERE id = @mappingID;
   * SELECT * FROM civicrm_mapping_field WHERE mapping_id = @mappingID;
   * SELECT * FROM civicrm_saved_search WHERE mapping_id = @mappingID;
   * SELECT g.* FROM civicrm_saved_search s LEFT JOIN civicrm_group g ON g.saved_search_id =  s.id WHERE  mapping_id = @mappingID;
   *
   *  Copy the output to a single sql file and place in the SavedSearchDataSets folder - use the group number as the prefix.
   *  Try to keep as much of the real world irregular glory as you can! Don't change the table ids to be number 1 as this can hide errors
   */
  public function testGroupData() {
    $groups = $this->dataProviderSavedSearch();
    foreach ($groups[0] as $groupID) {
      $group = new CRM_Contact_BAO_Group();
      $group->id = $groupID;
      $group->find(TRUE);

      CRM_Contact_BAO_GroupContactCache::load($group, TRUE);
    }
  }

  /**
   * Ensure that when updating a group with a linked organisation record even tho that record's id doesn't match the group id no db error is produced
   */
  public function testGroupUpdateWithOrganization() {
    $params = [
      'name' => uniqid(),
      'title' => 'Group A',
      'description' => 'Group One',
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
    ];
    $group1 = CRM_Contact_BAO_Group::create($params);

    $domain1 = $this->callAPISuccess('Domain', 'get', ['id' => 1]);
    $params2 = [
      'name' => uniqid(),
      'title' => 'Group B',
      'description' => 'Group Two',
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
      'organization_id' => $domain1['values'][1]['contact_id'],
    ];
    $group2 = CRM_Contact_BAO_Group::create($params2);

    $domain2 = $this->callAPISuccess('Domain', 'get', ['id' => 2]);
    $params3 = [
      'name' => uniqid(),
      'title' => 'Group C',
      'description' => 'Group Three',
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
      'organization_id' => $domain2['values'][2]['contact_id'],
    ];
    $group3 = CRM_Contact_BAO_Group::create($params3);
    $params2['id'] = $group2->id;
    $testUpdate = CRM_Contact_BAO_Group::create($params2);
  }

  /**
   * Ensure that when hidden smart group is created, wildcard string value is not ignored
   */
  public function testHiddenSmartGroup() {
    $customGroup = $this->customGroupCreate();
    $fields = [
      'label' => 'testFld',
      'data_type' => 'String',
      'html_type' => 'Text',
      'custom_group_id' => $customGroup['id'],
    ];
    $customFieldID = CRM_Core_BAO_CustomField::create($fields)->id;

    $contactID = $this->individualCreate(['custom_' . $customFieldID => 'abc']);

    $hiddenSmartParams = [
      'group_type' => ['2' => 1],
      'form_values' => ['custom_' . $customFieldID => ['LIKE' => '%a%']],
      'saved_search_id' => NULL,
      'search_custom_id' => NULL,
      'search_context' => 'advanced',
    ];
    list($smartGroupID, $savedSearchID) = CRM_Contact_BAO_Group::createHiddenSmartGroup($hiddenSmartParams);

    $mailingID = $this->callAPISuccess('Mailing', 'create', [])['id'];
    $this->callAPISuccess('MailingGroup', 'create', [
      'mailing_id' => $mailingID,
      'group_type' => 'Include',
      'entity_table' => 'civicrm_group',
      'entity_id' => $smartGroupID,
    ]);

    CRM_Mailing_BAO_Mailing::getRecipients($mailingID);
    $recipients = $this->callAPISuccess('MailingRecipients', 'get', ['mailing_id' => $mailingID]);
    $this->assertEquals(1, $recipients['count'], 'Check recipient count');
  }

  /**
   * Test updating a group with just description and check the recent items
   * list has the right title.
   */
  public function testGroupUpdateDescription() {
    // Create a group. Copied from $this->testAddSimple().
    // Note we need $checkParams because the function call changes $params.
    $checkParams = $params = [
      'title' => 'Group Uno',
      'description' => 'Group One',
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
    ];
    $group = CRM_Contact_BAO_Group::create($params);

    // Update the group with just id and description.
    $newParams = [
      'id' => $group->id,
      'description' => 'The first group',
    ];
    CRM_Contact_BAO_Group::create($newParams);

    // Check it against original array, except description.
    $result = $this->callAPISuccess('Group', 'getsingle', ['id' => $group->id]);
    foreach ($checkParams as $key => $value) {
      if ($key === 'description') {
        $this->assertEquals($newParams[$key], $result[$key], "$key doesn't match");
      }
      else {
        $this->assertEquals($checkParams[$key], $result[$key], "$key doesn't match");
      }
    }

    // Check recent items list.
    $recentItems = CRM_Utils_Recent::get();
    $this->assertEquals($checkParams['title'], $recentItems[0]['title']);
  }

}
