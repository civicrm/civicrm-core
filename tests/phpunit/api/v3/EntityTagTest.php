<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 *  Test APIv3 civicrm_entity_tag_* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Core
 */

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_EntityTagTest extends CiviUnitTestCase {

  protected $_individualID;
  protected $_householdID;
  protected $_organizationID;
  protected $_tagID;
  protected $_apiversion = 3;
  protected $_tag;
  protected $_entity = 'entity_tag';


  function setUp() {
    parent::setUp();

    $this->_individualID = $this->individualCreate();
    $this->_tag = $this->tagCreate();
    $this->_tagID = $this->_tag['id'];
    $this->_householdID = $this->houseHoldCreate();
    $this->_organizationID = $this->organizationCreate();
  }

  function tearDown() {
    $this->quickCleanup(array('civicrm_tag', 'civicrm_entity_tag'));
  }

  function testAddEmptyParams() {
    $individualEntity = $this->callAPIFailure('entity_tag', 'create', $params = array(),
      'contact_id is a required field'
    );
  }

  function testAddWithoutTagID() {
    $params = array(
      'contact_id' => $this->_individualID,
    );
    $individualEntity = $this->callAPIFailure('entity_tag', 'create', $params,
      'tag_id is a required field'
    );
  }

  function testAddWithoutContactID() {
    $params = array(
      'tag_id' => $this->_tagID,
    );
    $individualEntity = $this->callAPIFailure('entity_tag', 'create', $params,
      'contact_id is a required field');
  }

  function testContactEntityTagCreate() {
    $params = array(
      'contact_id' => $this->_individualID,
      'tag_id' => $this->_tagID,
    );

    $result = $this->callAPIAndDocument('entity_tag', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['added'], 1);
  }

  function testAddDouble() {
    $individualId   = $this->_individualID;
    $organizationId = $this->_organizationID;
    $tagID          = $this->_tagID;
    $params         = array(
      'contact_id' => $individualId,
      'tag_id' => $tagID,
    );

    $result = $this->callAPISuccess('entity_tag', 'create', $params);

    $this->assertEquals($result['added'], 1);

    $params = array(
      'contact_id_i' => $individualId,
      'contact_id_o' => $organizationId,
      'tag_id' => $tagID,
    );

    $result = $this->callAPISuccess('entity_tag', 'create', $params);
    $this->assertEquals($result['added'], 1);
    $this->assertEquals($result['not_added'], 1);
  }

  ///////////////// civicrm_entity_tag_get methods
  function testGetNoEntityID() {
    $ContactId = $this->_individualID;
    $tagID     = $this->_tagID;
    $params    = array(
      'contact_id' => $ContactId,
      'tag_id' => $tagID,
    );

    $individualEntity = $this->callAPISuccess('entity_tag', 'create', $params);
    $this->assertEquals($individualEntity['added'], 1);
    $result = $this->callAPISuccess($this->_entity, 'get', array('sequential' => 1, 'tag_id' => $tagID));
    $this->assertEquals($ContactId, $result['values'][0]['entity_id']);
  }

  function testIndividualEntityTagGet() {
    $contactId = $this->_individualID;
    $tagID     = $this->_tagID;
    $params    = array(
      'contact_id' => $contactId,
      'tag_id' => $tagID,
    );

    $individualEntity = $this->callAPIAndDocument('entity_tag', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($individualEntity['added'], 1);

    $paramsEntity = array(
      'contact_id' => $contactId,
    );
    $entity = $this->callAPISuccess('entity_tag', 'get', $paramsEntity);
  }

  function testHouseholdEntityGet() {
    $ContactId = $this->_householdID;
    $tagID     = $this->_tagID;
    $params    = array(
      'contact_id' => $ContactId,
      'tag_id' => $tagID,
    );

    $householdEntity = $this->callAPISuccess('entity_tag', 'create', $params);
    $this->assertEquals($householdEntity['added'], 1);
  }

  function testOrganizationEntityGet() {
    $ContactId = $this->_organizationID;
    $tagID     = $this->_tagID;
    $params    = array(
      'contact_id' => $ContactId,
      'tag_id' => $tagID,
    );

    $organizationEntity = $this->callAPISuccess('entity_tag', 'create', $params);
    $this->assertEquals($organizationEntity['added'], 1);

    $paramsEntity = array('contact_id' => $ContactId);
    $entity = $this->callAPISuccess('entity_tag', 'get', $paramsEntity);
  }

  ///////////////// civicrm_entity_tag_Delete methods
  function testEntityTagDeleteNoTagId() {
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

    $result = $this->callAPIFailure('entity_tag', 'delete', $params,
      'tag_id is a required field'
    );
  }

  function testEntityTagDeleteINDHH() {
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

  function testEntityTagDeleteHH() {
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

  function testEntityTagDeleteHHORG() {
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

  ///////////////// civicrm_tag_entities_get methods

  function testCommonContactEntityTagAdd() {
    $params = array(
      'contact_id' => $this->_individualID,
      'tag_id' => $this->_tagID,
    );

    $individualEntity = $this->callAPISuccess('entity_tag', 'create', $params);
    $this->assertEquals($individualEntity['added'], 1);
  }


  function testEntityTagCommonDeleteINDHH() {
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

  function testEntityTagCommonDeleteHH() {
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

  function testEntityTagCommonDeleteHHORG() {
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

