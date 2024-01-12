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
 *  Class api_v3_SavedSearchTest
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_SavedSearchTest extends CiviUnitTestCase {

  protected $params;
  protected $id;
  protected $_entity;

  public function setUp(): void {
    parent::setUp();

    // The line below makes it unnecessary to do cleanup after a test,
    // because the transaction of the test will be rolled back.
    // see http://forum.civicrm.org/index.php/topic,35627.0.html
    $this->useTransaction();

    $this->_entity = 'SavedSearch';

    // I created a smart group using the CiviCRM gui. The smart group contains
    // all contacts tagged with 'company'.
    // I got the params below from the database.
    // params for saved search that returns all volunteers for the
    // default organization.
    $this->params = [
      'expires_date' => '2021-08-08',
      'form_values' => [
        // Is volunteer for
        'relation_type_id' => '6_a_b',
        'relation_target_name' => 'Default Organization',
      ],
    ];
  }

  /**
   * Create a saved search, and see whether the returned values make sense.
   */
  public function testCreateSavedSearch(): void {
    $contactID = $this->createLoggedInUser();
    $result = $this->callAPISuccess(
        $this->_entity, 'create', $this->params)['values'];
    $this->assertCount(1, $result);
    $savedSearch = reset($result);

    $this->assertEquals($contactID, $savedSearch['created_id']);
    $this->assertEquals($contactID, $savedSearch['modified_id']);
    $this->assertEquals('20210808000000', $savedSearch['expires_date']);

    $this->assertNotNull($savedSearch['id']);

    // Check whether the relation type ID is correctly returned.
    $this->assertEquals(
        $this->params['form_values']['relation_type_id'],
        $savedSearch['form_values']['relation_type_id']);
  }

  /**
   * Create a saved search, retrieve it again, and check for ID and one of
   * the field values.
   */
  public function testCreateAndGetSavedSearch(): void {
    // Arrange:
    // (create a saved search)
    $create_result = $this->callAPISuccess(
        $this->_entity, 'create', $this->params);

    // Act:
    $get_result = $this->callAPISuccess(
        $this->_entity, 'get', ['id' => $create_result['id']]);

    // Assert:
    $this->assertEquals(1, $get_result['count']);
    $this->assertNotNull($get_result['values'][$get_result['id']]['id']);

    // just check the relationship type ID of the form values.
    $this->assertEquals(
        $this->params['form_values']['relation_type_id'],
        $get_result['values'][$get_result['id']]['form_values']['relation_type_id']);
  }

  /**
   * Create a saved search, and test whether it can be used for a smart
   * group.
   */
  public function testCreateSavedSearchWithSmartGroup(): void {
    // First create a volunteer for the default organization

    [$contact_id, $params] = $this->setupContactInSmartGroup();

    $create_result = $this->callAPISuccess(
        'SavedSearch', 'create', $params);

    $created_search = CRM_Utils_Array::first($create_result['values']);
    $group_id = $created_search['api.Group.create']['id'];

    // Search for contacts in our new smart group
    $get_result = $this->callAPISuccess('Contact', 'get', ['group' => $group_id]);

    // Expect our contact to be there.
    $this->assertEquals(1, $get_result['count']);
    $this->assertEquals($contact_id, $get_result['values'][$contact_id]['id']);
  }

  /**
   * Create a saved search, and test whether it can be used for a smart
   * group. Also check that when the Group is deleted the associated saved
   * search gets deleted.
   */
  public function testSavedSearchIsDeletedWhenSmartGroupIs(): void {

    [$contact_id, $params] = $this->setupContactInSmartGroup();

    $create_result = $this->callAPISuccess($this->_entity, 'create', $params);

    $created_search = CRM_Utils_Array::first($create_result['values']);
    $group_id = $created_search['api.Group.create']['id'];

    $result = $this->callAPISuccess('Contact', 'get', ['group' => $group_id]);

    // Expect our contact to be there.
    $this->assertEquals(1, $result['count']);
    $this->assertEquals($contact_id, $result['values'][$contact_id]['id']);

    $this->callAPISuccess('Group', 'delete', ['id' => $group_id]);
    $savedSearch = $this->callAPISuccess('SavedSearch', 'get', ['id' => $created_search['id']]);
    $this->assertCount(0, $savedSearch['values']);
  }

  /**
   * @return array
   */
  protected function setupContactInSmartGroup(): array {
    $result = $this->callAPISuccess('Contact', 'create', [
      'first_name' => 'Joe',
      'last_name' => 'Schmidt',
      'contact_type' => 'Individual',
      'api.Relationship.create' => [
        'contact_id_a' => '$value.id',
        // default organization:
        'contact_id_b' => 1,
        // volunteer relationship:
        'relationship_type_id' => 6,
        'is_active' => 1,
      ],
    ]);
    $contact_id = $result['id'];

    // Now create our saved search, and chain the creation of a smart group.
    $params = $this->params;
    $params['api.Group.create'] = [
      'name' => 'my_smartgroup',
      'title' => 'my smartgroup',
      'description' => 'Volunteers for the default organization',
      'saved_search_id' => '$value.id',
      'is_active' => 1,
      'visibility' => 'User and User Admin Only',
      'is_hidden' => 0,
      'is_reserved' => 0,
    ];
    return [$contact_id, $params];
  }

}
