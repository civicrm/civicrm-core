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

  protected $_apiversion = 3;
  protected $params;
  protected $id;
  protected $_entity;
  public $DBResetRequired = FALSE;

  public function setUp() {
    parent::setUp();

    // The line below makes it unneccessary to do cleanup after a test,
    // because the transaction of the test will be rolled back.
    // see http://forum.civicrm.org/index.php/topic,35627.0.html
    $this->useTransaction(TRUE);

    $this->_entity = 'SavedSearch';

    // I created a smart group using the CiviCRM gui. The smart group contains
    // all contacts tagged with 'company'.
    // I got the params below from the database.

    $url = CIVICRM_UF_BASEURL . "/civicrm/contact/search/advanced?reset=1";
    $serialized_url = serialize($url);

    // params for saved search that returns all volunteers for the
    // default organization.
    $this->params = array(
      'form_values' => array(
        // Is volunteer for
        'relation_type_id' => '6_a_b',
        'relation_target_name' => 'Default Organization',
      ),
    );
  }

  /**
   * Create a saved search, and see whether the returned values make sense.
   */
  public function testCreateSavedSearch() {
    // Act:
    $result = $this->callAPIAndDocument(
        $this->_entity, 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);

    // Assert:
    // getAndCheck fails, I think because form_values is an array.
    //$this->getAndCheck($this->params, $result['id'], $this->_entity);
    // Check whether the new ID is correctly returned by the API.
    $this->assertNotNull($result['values'][$result['id']]['id']);

    // Check whether the relation type ID is correctly returned.
    $this->assertEquals(
        $this->params['form_values']['relation_type_id'],
        $result['values'][$result['id']]['form_values']['relation_type_id']);
  }

  /**
   * Create a saved search, retrieve it again, and check for ID and one of
   * the field values.
   */
  public function testCreateAndGetSavedSearch() {
    // Arrange:
    // (create a saved search)
    $create_result = $this->callAPISuccess(
        $this->_entity, 'create', $this->params);

    // Act:
    $get_result = $this->callAPIAndDocument(
        $this->_entity, 'get', array('id' => $create_result['id']), __FUNCTION__, __FILE__);

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
  public function testCreateSavedSearchWithSmartGroup() {
    // First create a volunteer for the default organization

    $result = $this->callAPISuccess('Contact', 'create', array(
      'first_name' => 'Joe',
      'last_name' => 'Schmoe',
      'contact_type' => 'Individual',
      'api.Relationship.create' => array(
        'contact_id_a' => '$value.id',
        // default organization:
        'contact_id_b' => 1,
        // volunteer relationship:
        'relationship_type_id' => 6,
        'is_active' => 1,
      ),
    ));
    $contact_id = $result['id'];

    // Now create our saved search, and chain the creation of a smart group.
    $params = $this->params;
    $params['api.Group.create'] = array(
      'name' => 'my_smartgroup',
      'title' => 'my smartgroup',
      'description' => 'Volunteers for the default organization',
      'saved_search_id' => '$value.id',
      'is_active' => 1,
      'visibility' => 'User and User Admin Only',
      'is_hidden' => 0,
      'is_reserved' => 0,
    );

    $create_result = $this->callAPIAndDocument(
        $this->_entity, 'create', $params, __FUNCTION__, __FILE__);

    $created_search = CRM_Utils_Array::first($create_result['values']);
    $group_id = $created_search['api.Group.create']['id'];

    // Search for contacts in our new smart group
    $get_result = $this->callAPISuccess(
      'Contact', 'get', array('group' => $group_id), __FUNCTION__, __FILE__);

    // Expect our contact to be there.
    $this->assertEquals(1, $get_result['count']);
    $this->assertEquals($contact_id, $get_result['values'][$contact_id]['id']);
  }

  public function testDeleteSavedSearch() {
    // Create saved search, delete it again, and try to get it
    $create_result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $delete_params = array('id' => $create_result['id']);
    $this->callAPIAndDocument(
        $this->_entity, 'delete', $delete_params, __FUNCTION__, __FILE__);
    $get_result = $this->callAPISuccess($this->_entity, 'get', array());

    $this->assertEquals(0, $get_result['count']);
  }

}
