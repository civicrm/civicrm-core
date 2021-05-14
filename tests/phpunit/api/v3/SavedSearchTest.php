<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | Copyright Chirojeugd-Vlaanderen vzw 2015                           |
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
 *  Class api_v3_SavedSearchTest
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_SavedSearchTest extends CiviUnitTestCase {

  protected $params;
  protected $id;
  protected $_entity;
  public $DBResetRequired = FALSE;

  public function setUp(): void {
    parent::setUp();

    // The line below makes it unneccessary to do cleanup after a test,
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
    $result = $this->callAPIAndDocument(
        $this->_entity, 'create', $this->params, __FUNCTION__, __FILE__)['values'];
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateAndGetSavedSearch(): void {
    // Arrange:
    // (create a saved search)
    $create_result = $this->callAPISuccess(
        $this->_entity, 'create', $this->params);

    // Act:
    $get_result = $this->callAPIAndDocument(
        $this->_entity, 'get', ['id' => $create_result['id']], __FUNCTION__, __FILE__);

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
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateSavedSearchWithSmartGroup(): void {
    // First create a volunteer for the default organization

    [$contact_id, $params] = $this->setupContactInSmartGroup();

    $create_result = $this->callAPIAndDocument(
        $this->_entity, 'create', $params, __FUNCTION__, __FILE__);

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
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testSavedSearchIsDeletedWhenSmartGroupIs($apiVersion): void {
    $this->_apiVersion = $apiVersion;
    // First create a volunteer for the default organization

    [$contact_id, $params] = $this->setupContactInSmartGroup();

    $create_result = $this->callAPISuccess($this->_entity, 'create', $params);

    $created_search = CRM_Utils_Array::first($create_result['values']);
    $group_id = $created_search['api.Group.create']['id'];

    $get_result = $this->callAPISuccess('Contact', 'get', ['group' => $group_id]);

    // Expect our contact to be there.
    $this->assertEquals(1, $get_result['count']);
    $this->assertEquals($contact_id, $get_result['values'][$contact_id]['id']);

    $this->callAPISuccess('Group', 'delete', ['id' => $group_id]);
    $savedSearch = $this->callAPISuccess('SavedSearch', 'get', ['id' => $created_search['id']]);
    $this->assertCount(0, $savedSearch['values']);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testDeleteSavedSearch(): void {
    // Create saved search, delete it again, and try to get it
    $create_result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $delete_params = ['id' => $create_result['id']];
    $this->callAPIAndDocument(
        $this->_entity, 'delete', $delete_params, __FUNCTION__, __FILE__);
    $get_result = $this->callAPISuccess($this->_entity, 'get', []);

    $this->assertEquals(0, $get_result['count']);
  }

  /**
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function setupContactInSmartGroup(): array {
    $result = $this->callAPISuccess('Contact', 'create', [
      'first_name' => 'Joe',
      'last_name' => 'Schmoe',
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
