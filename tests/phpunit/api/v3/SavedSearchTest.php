<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.6                                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 *  Class api_v3_SavedSearchTest
 *
 * @package CiviCRM_APIv3
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

    $this->params = array(
      'form_values' => 'a:36:{s:5:"qfKey";s:37:"4b50e233dcbe77cced4bd8fd8df90567_9974";s:8:"entryURL";'
      . $serialized_url . 's:12:"hidden_basic";s:1:"1";s:12:"contact_type";a:0:{}s:5:"group";a:0:{}s:10:"group_type";a:0:{}s:21:"group_search_selected";s:5:"group";s:9:"sort_name";s:0:"";s:5:"email";s:0:"";s:14:"contact_source";s:0:"";s:9:"job_title";s:0:"";s:10:"contact_id";s:0:"";s:19:"external_identifier";s:0:"";s:7:"uf_user";s:0:"";s:10:"tag_search";s:0:"";s:11:"uf_group_id";s:0:"";s:14:"component_mode";s:1:"1";s:8:"operator";s:3:"AND";s:25:"display_relationship_type";s:0:"";s:15:"privacy_options";a:0:{}s:16:"privacy_operator";s:2:"OR";s:14:"privacy_toggle";s:1:"1";s:13:"email_on_hold";a:1:{s:7:"on_hold";s:0:"";}s:30:"preferred_communication_method";a:5:{i:1;s:0:"";i:2;s:0:"";i:3;s:0:"";i:4;s:0:"";i:5;s:0:"";}s:18:"preferred_language";s:0:"";s:13:"phone_numeric";s:0:"";s:22:"phone_location_type_id";s:0:"";s:19:"phone_phone_type_id";s:0:"";s:4:"task";s:2:"13";s:8:"radio_ts";s:6:"ts_sel";s:12:"toggleSelect";s:1:"1";s:10:"mark_x_165";s:1:"1";s:10:"mark_x_155";s:1:"1";s:10:"mark_x_124";s:1:"1";s:9:"mark_x_35";s:1:"1";s:12:"contact_tags";a:1:{i:2;i:1;}}',
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
    // Get the entity with the id returned in $result['id'], and check whether
    // the parameters are set correctly:
    $this->getAndCheck($this->params, $result['id'], $this->_entity);

    // Check whether the new ID is correctly returned by the API.
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  public function testGetSavedSearch() {
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
    $this->assertEquals(
        $this->params['form_values'], $get_result['values'][$get_result['id']]['form_values']);
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
