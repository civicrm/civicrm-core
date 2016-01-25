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
 *  Test APIv3 civicrm_entity_tag_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Core
 */

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class api_v3_EntityTagTest.
 */
class api_v3_EntityTagTest extends CiviUnitTestCase {

  /**
   * @var int
   */
  protected $_individualID;
  protected $_householdID;
  protected $_organizationID;
  protected $_tagID;
  protected $_apiversion = 3;
  protected $_tag;
  protected $_entity = 'entity_tag';

  /**
   * Basic parameters for create.
   *
   * @var array
   */
  protected $_params = array();

  /**
   * Set up for test.
   */
  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_individualID = $this->individualCreate();
    $this->_tag = $this->tagCreate();
    $this->_tagID = $this->_tag['id'];
    $this->_householdID = $this->houseHoldCreate();
    $this->_organizationID = $this->organizationCreate();
    $this->_params = array(
      'contact_id' => $this->_individualID,
      'tag_id' => $this->_tagID,
    );
  }

  /**
   * Test required parameters.
   *
   * These failure tests are low value and may not be worth putting in v4.
   */
  public function testFailureTests() {
    $this->callAPIFailure('entity_tag', 'create', array('contact_id' => $this->_individualID),
      'tag_id is a required field'
    );
    $this->callAPIFailure('entity_tag', 'create', array('tag_id' => $this->_tagID),
      'contact_id is a required field'
    );
  }

  /**
   * Test basic create.
   */
  public function testContactEntityTagCreate() {
    $result = $this->callAPISuccess('entity_tag', 'create', $this->_params);
    $this->assertEquals($result['added'], 1);
  }

  /**
   * Test multiple add functionality.
   *
   * This needs review for api v4 as it makes for a very non standard api.
   */
  public function testAddDouble() {

    $result = $this->callAPISuccess('entity_tag', 'create', $this->_params);
    $this->assertEquals($result['added'], 1);

    $params = array(
      'contact_id_i' => $this->_individualID,
      'contact_id_o' => $this->_organizationID,
      'tag_id' => $this->_tagID,
    );

    $result = $this->callAPISuccess('entity_tag', 'create', $params);
    $this->assertEquals($result['added'], 1);
    $this->assertEquals($result['not_added'], 1);
  }

  /**
   * Test that get works without an entity.
   */
  public function testGetNoEntityID() {
    $this->callAPISuccess('entity_tag', 'create', $this->_params);
    $result = $this->callAPISuccess($this->_entity, 'get', array('sequential' => 1, 'tag_id' => $this->_tagID));
    $this->assertEquals($this->_individualID, $result['values'][0]['entity_id']);
  }

  /**
   * Basic get functionality test.
   */
  public function testIndividualEntityTagGet() {
    $individualEntity = $this->callAPISuccess('entity_tag', 'create', $this->_params);
    $this->assertEquals($individualEntity['added'], 1);

    $paramsEntity = array(
      'contact_id' => $this->_individualID,
    );
    $this->callAPIAndDocument('entity_tag', 'get', $paramsEntity, __FUNCTION__, __FILE__);
  }

  /**
   * Test tag can be added to a household.
   */
  public function testHouseholdEntityCreate() {
    $params = array(
      'contact_id' => $this->_householdID,
      'tag_id' => $this->_tagID,
    );

    $householdEntity = $this->callAPISuccess('entity_tag', 'create', $params);
    $this->assertEquals($householdEntity['added'], 1);
  }

  /**
   * Test tag can be added to an organization.
   */
  public function testOrganizationEntityGet() {

    $params = array(
      'contact_id' => $this->_organizationID,
      'tag_id' => $this->_tagID,
    );

    $organizationEntity = $this->callAPISuccess('entity_tag', 'create', $params);
    $this->assertEquals($organizationEntity['added'], 1);

    $this->callAPISuccess('entity_tag', 'getsingle', array('contact_id' => $this->_organizationID));
  }

  /**
   * Civicrm_entity_tag_Delete methods.
   */
  public function testEntityTagDeleteNoTagId() {
    $entityTagParams = array(
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    );
    $this->entityTagAdd($entityTagParams);

    $params = array(
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
    );

    $result = $this->callAPISuccess('entity_tag', 'delete', $params);

    $this->assertEquals($result['not_removed'], 0);
    $this->assertEquals($result['removed'], 2);
    $this->assertEquals($result['total_count'], 2);
  }

  public function testEntityTagDeleteINDHH() {
    $entityTagParams = array(
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    );
    $this->entityTagAdd($entityTagParams);

    $params = array(
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    );

    $result = $this->callAPISuccess('entity_tag', 'delete', $params);

    $this->assertEquals($result['removed'], 2);
  }

  public function testEntityTagDeleteHH() {
    $entityTagParams = array(
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    );
    $this->entityTagAdd($entityTagParams);

    $params = array(
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    );

    $result = $this->callAPIAndDocument('entity_tag', 'delete', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['removed'], 1);
  }

  public function testEntityTagDeleteHHORG() {
    $entityTagParams = array(
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    );
    $this->entityTagAdd($entityTagParams);

    $params = array(
      'contact_id_h' => $this->_householdID,
      'contact_id_o' => $this->_organizationID,
      'tag_id' => $this->_tagID,
    );

    $result = $this->callAPISuccess('entity_tag', 'delete', $params);
    $this->assertEquals($result['removed'], 1);
    $this->assertEquals($result['not_removed'], 1);
  }

  public function testEntityTagCommonDeleteINDHH() {
    $entityTagParams = array(
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    );
    $this->entityTagAdd($entityTagParams);

    $params = array(
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    );

    $result = $this->callAPISuccess('entity_tag', 'delete', $params);
    $this->assertEquals($result['removed'], 2);
  }

  public function testEntityTagCommonDeleteHH() {
    $entityTagParams = array(
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    );
    $this->entityTagAdd($entityTagParams);

    $params = array(
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    );

    $result = $this->callAPISuccess('entity_tag', 'delete', $params);
    $this->assertEquals($result['removed'], 1);
  }

  public function testEntityTagCommonDeleteHHORG() {
    $entityTagParams = array(
      'contact_id_i' => $this->_individualID,
      'contact_id_h' => $this->_householdID,
      'tag_id' => $this->_tagID,
    );
    $this->entityTagAdd($entityTagParams);

    $params = array(
      'contact_id_h' => $this->_householdID,
      'contact_id_o' => $this->_organizationID,
      'tag_id' => $this->_tagID,
    );

    $result = $this->callAPISuccess('entity_tag', 'delete', $params);
    $this->assertEquals($result['removed'], 1);
    $this->assertEquals($result['not_removed'], 1);
  }

}
