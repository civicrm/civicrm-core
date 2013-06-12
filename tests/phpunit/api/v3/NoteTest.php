<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

require_once 'tests/phpunit/CiviTest/CiviUnitTestCase.php';

/**
 * Class contains api test cases for "civicrm_note"
 *
 */
class api_v3_NoteTest extends CiviUnitTestCase {

  protected $_apiversion;
  protected $_contactID;
  protected $_params;
  protected $_noteID;
  protected $_note;
  public $_eNoticeCompliant = TRUE;

  function __construct() {
    parent::__construct();
  }

  function get_info() {
    return array(
      'name' => 'Note Create',
      'description' => 'Test all Note Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {

    $this->_apiversion = 3;
    //  Connect to the database
    parent::setUp();

    $this->_contactID = $this->organizationCreate(NULL);

    $this->_params = array(
      'entity_table' => 'civicrm_contact',
      'entity_id' => $this->_contactID,
      'note' => 'Hello!!! m testing Note',
      'contact_id' => $this->_contactID,
      'modified_date' => '2011-01-31',
      'subject' => 'Test Note',
      'version' => $this->_apiversion,
    );
    $this->_note = $this->noteCreate($this->_contactID);
    $this->_noteID = $this->_note['id'];
  }

  function tearDown() {
    $tablesToTruncate = array(
      'civicrm_note', 'civicrm_contact',
    );
    $this->quickCleanup($tablesToTruncate);
  }

  ///////////////// civicrm_note_get methods

  /**
   * check retrieve note with wrong params type
   * Error Expected
   */
  function testGetWithWrongParamsType() {
    $params = 'a string';
    $result = civicrm_api('note', 'get', $params);
    $this->assertAPIFailure($result,
      "In line " . __LINE__
    );
  }

  /**
   * check retrieve note with empty parameter array
   * Error expected
   */
  function testGetWithEmptyParams() {
    $params = array();
    $note = civicrm_api('note', 'get', $params);
    $this->assertEquals($note['is_error'], 1);
  }

  /**
   * check retrieve note with missing patrameters
   * Error expected
   */
  function testGetWithoutEntityId() {
    $params = array(
      'entity_table' => 'civicrm_contact',
      'version' => 3,
    );
    $note = civicrm_api('note', 'get', $params);
    $this->assertEquals($note['is_error'], 0);
  }

  /**
   * check civicrm_note_get
   */
  function testGet() {
    $entityId = $this->_noteID;
    $params = array(
      'entity_table' => 'civicrm_contact',
      'entity_id' => $entityId,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api3_note_get($params);
    $this->documentMe($this->_params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
  }


  ///////////////// civicrm_note_create methods

  /**
   * Check create with wrong parameter
   * Error expected
   */
  function testCreateWithWrongParamsType() {
    $params = 'a string';
    $result = civicrm_api('note', 'create', $params);
    $this->assertAPIFailure($result,
      "In line " . __LINE__
    );
    $this->assertEquals($result['error_message'], 'Input variable `params` is not an array');
  }

  /**
   * Check create with empty parameter array
   * Error Expected
   */
  function testCreateWithEmptyNoteField() {
    $this->_params['note'] = "";
    $result = civicrm_api('note', 'create', $this->_params);
    $this->assertAPIFailure($result);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: note');
  }

  /**
   * Check create with partial params
   * Error expected
   */
  function testCreateWithoutEntityId() {
    unset($this->_params['entity_id']);
    $result = civicrm_api('note', 'create', $this->_params);
    $this->assertAPIFailure($result);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: entity_id');
  }

  /**
   * Check create with partially empty params
   * Error expected
   */
  function testCreateWithEmptyEntityId() {
    $this->_params['entity_id'] = "";
    $result = civicrm_api('note', 'create', $this->_params);
    $this->assertAPIFailure($result);
    $this->assertEquals($result['error_message'], 'Mandatory key(s) missing from params array: entity_id');
  }

  /**
   * Check civicrm_note_create
   */
  function testCreate() {

    $result = civicrm_api('note', 'create', $this->_params);
    $this->documentMe($this->_params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$result['id']]['note'], 'Hello!!! m testing Note', 'in line ' . __LINE__);
    $this->assertEquals(date('Y-m-d', strtotime($this->_params['modified_date'])), date('Y-m-d', strtotime($result['values'][$result['id']]['modified_date'])), 'in line ' . __LINE__);

    $this->assertArrayHasKey('id', $result, 'in line ' . __LINE__);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $note = array(
      'id' => $result['id'],
      'version' => $this->_apiversion,
    );
    $this->noteDelete($note);
  }

  function testCreateWithApostropheInString() {
    $params = array(
      'entity_table' => 'civicrm_contact',
      'entity_id' => $this->_contactID,
      'note' => "Hello!!! ' testing Note",
      'contact_id' => $this->_contactID,
      'modified_date' => '2011-01-31',
      'subject' => "With a '",
      'sequential' => 1,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('Note', 'Create', $params);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $this->assertEquals($result['values'][0]['note'], "Hello!!! ' testing Note", 'in line ' . __LINE__);
    $this->assertEquals($result['values'][0]['subject'], "With a '", 'in line ' . __LINE__);
    $this->assertArrayHasKey('id', $result, 'in line ' . __LINE__);

    //CleanUP
    $note = array(
      'id' => $result['id'],
      'version' => $this->_apiversion,
    );
    $this->noteDelete($note);
  }

  /**
   * Check civicrm_note_create - tests used of default set to now
   */
  function testCreateWithoutModifiedDate() {
    unset($this->_params['modified_date']);
    $apiResult = civicrm_api('note', 'create', $this->_params);
    $this->assertAPISuccess($apiResult);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($apiResult['values'][$apiResult['id']]['modified_date'])));
    $this->noteDelete(array(
      'id' => $apiResult['id'],
        'version' => $this->_apiversion,
      ));
  }


  ///////////////// civicrm_note_update methods

  /**
   * Check update note with wrong params type
   * Error expected
   */
  function testUpdateWithWrongParamsType() {
    $params = 'a string';
    $result = civicrm_api('note', 'create', $params);
    $this->assertAPIFailure($result,
      "In line " . __LINE__
    );
  }

  /**
   * Check update with empty parameter array
   * Error expected
   */
  function testUpdateWithEmptyParams() {
    $params = array();
    $note = civicrm_api('note', 'create', $params);
    $this->assertEquals($note['is_error'], 1);
  }

  /**
   * Check update with missing parameter (contact id)
   * Error expected
   */
  function testUpdateWithoutContactId() {
    $params = array(
      'entity_id' => $this->_contactID,
      'entity_table' => 'civicrm_contact',
      'version' => $this->_apiversion,
    );
    $note = civicrm_api('note', 'create', $params);
    $this->assertEquals($note['is_error'], 1);
    $this->assertEquals($note['error_message'], 'Mandatory key(s) missing from params array: note');
  }

  /**
   * Check civicrm_note_update
   */
  function testUpdate() {
    $params = array(
      'id' => $this->_noteID,
      'contact_id' => $this->_contactID,
      'note' => 'Note1',
      'subject' => 'Hello World',
      'version' => $this->_apiversion,
    );

    //Update Note
    civicrm_api('note', 'create', $params);
    $note = civicrm_api('Note', 'Get', array('version' => 3));
    $this->assertEquals($note['id'], $this->_noteID, 'in line ' . __LINE__);
    $this->assertEquals($note['is_error'], 0, 'in line ' . __LINE__);
    $this->assertEquals($note['values'][$this->_noteID]['entity_id'], $this->_contactID, 'in line ' . __LINE__);
    $this->assertEquals($note['values'][$this->_noteID]['entity_table'], 'civicrm_contact', 'in line ' . __LINE__);
    $this->assertEquals('Hello World', $note['values'][$this->_noteID]['subject'], 'in line ' . __LINE__);
    $this->assertEquals('Note1', $note['values'][$this->_noteID]['note'], 'in line ' . __LINE__);
  }

  ///////////////// civicrm_note_delete methods

  /**
   * Check delete note with wrong params type
   * Error expected
   */
  function testDeleteWithWrongParamsType() {
    $params = 'a string';
    $result = civicrm_api('note', 'delete', $params);
    $this->assertAPIFailure($result,
      "In line " . __LINE__
    );
  }

  /**
   * Check delete with empty parametes array
   * Error expected
   */
  function testDeleteWithEmptyParams() {
    $params = array();
    $deleteNote = civicrm_api('note', 'delete', $params);
    $this->assertEquals($deleteNote['is_error'], 1);
    $this->assertEquals($deleteNote['error_message'], 'Mandatory key(s) missing from params array: version, id');
  }

  /**
   * Check delete with wrong id
   * Error expected
   */
  function testDeleteWithWrongID() {
    $params = array(
      'id' => 0,
      'version' => $this->_apiversion,
    );
    $deleteNote = civicrm_api('note', 'delete', $params);
    $this->assertEquals($deleteNote['is_error'], 1);
    $this->assertEquals($deleteNote['error_message'], 'Mandatory key(s) missing from params array: id');
  }

  /**
   * Check civicrm_note_delete
   */
  function testDelete() {
    $additionalNote = $this->noteCreate($this->_contactID);

    $params = array(
      'id' => $additionalNote['id'],
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('note', 'delete', $params);

    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
  }
}

/**
 *  Test civicrm_activity_create() using example code
 */
function testNoteCreateExample() {
  require_once 'api/v3/examples/NoteCreate.php';
  $result = UF_match_get_example();
  $expectedResult = UF_match_get_expectedresult();
  $this->assertEquals($result, $expectedResult);
}

