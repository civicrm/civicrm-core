<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * Class contains api test cases for "civicrm_note"
 * @group headless
 */
class api_v3_NoteTest extends CiviUnitTestCase {

  protected $_apiversion;
  protected $_contactID;
  protected $_params;
  protected $_noteID;
  protected $_note;

  public function setUp() {

    $this->_apiversion = 3;
    // Connect to the database.
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_contactID = $this->organizationCreate(NULL);

    $this->_params = array(
      'entity_table' => 'civicrm_contact',
      'entity_id' => $this->_contactID,
      'note' => 'Hello!!! m testing Note',
      'contact_id' => $this->_contactID,
      'modified_date' => '2011-01-31',
      'subject' => 'Test Note',
    );
    $this->_note = $this->noteCreate($this->_contactID);
    $this->_noteID = $this->_note['id'];
  }

  ///////////////// civicrm_note_get methods

  /**
   * Check retrieve note with empty parameter array.
   *
   * Error expected
   */
  public function testGetWithEmptyParams() {
    $this->callAPISuccess('note', 'get', array());
  }

  /**
   * Check retrieve note with missing parameters.
   *
   * Error expected
   */
  public function testGetWithoutEntityId() {
    $params = array(
      'entity_table' => 'civicrm_contact',
    );
    $this->callAPISuccess('note', 'get', $params);
  }

  /**
   * Check civicrm_note get.
   */
  public function testGet() {
    $entityId = $this->_noteID;
    $params = array(
      'entity_table' => 'civicrm_contact',
      'entity_id' => $entityId,
    );
    $this->callAPIAndDocument('note', 'get', $params, __FUNCTION__, __FILE__);
  }

  /**
   * Check create with empty parameter array.
   *
   * Error Expected
   */
  public function testCreateWithEmptyNoteField() {
    $this->_params['note'] = "";
    $this->callAPIFailure('note', 'create', $this->_params,
      'Mandatory key(s) missing from params array: note'
    );
  }

  /**
   * Check create with partial params.
   *
   * Error expected
   */
  public function testCreateWithoutEntityId() {
    unset($this->_params['entity_id']);
    $this->callAPIFailure('note', 'create', $this->_params,
      'Mandatory key(s) missing from params array: entity_id');
  }

  /**
   * Check create with partially empty params.
   *
   * Error expected
   */
  public function testCreateWithEmptyEntityId() {
    $this->_params['entity_id'] = "";
    $this->callAPIFailure('note', 'create', $this->_params,
      'Mandatory key(s) missing from params array: entity_id');
  }

  /**
   * Check civicrm note create.
   */
  public function testCreate() {

    $result = $this->callAPIAndDocument('note', 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$result['id']]['note'], 'Hello!!! m testing Note');
    $this->assertEquals(date('Y-m-d', strtotime($this->_params['modified_date'])), date('Y-m-d', strtotime($result['values'][$result['id']]['modified_date'])));

    $this->assertArrayHasKey('id', $result);
    $note = array(
      'id' => $result['id'],
    );
    $this->noteDelete($note);
  }

  public function testCreateWithApostropheInString() {
    $params = array(
      'entity_table' => 'civicrm_contact',
      'entity_id' => $this->_contactID,
      'note' => "Hello!!! ' testing Note",
      'contact_id' => $this->_contactID,
      'modified_date' => '2011-01-31',
      'subject' => "With a '",
      'sequential' => 1,
    );
    $result = $this->callAPISuccess('Note', 'Create', $params);
    $this->assertAPISuccess($result);
    $this->assertEquals($result['values'][0]['note'], "Hello!!! ' testing Note");
    $this->assertEquals($result['values'][0]['subject'], "With a '");
    $this->assertArrayHasKey('id', $result);

    //CleanUP
    $note = array(
      'id' => $result['id'],
    );
    $this->noteDelete($note);
  }

  /**
   * Check civicrm_note_create - tests used of default set to .
   */
  public function testCreateWithoutModifiedDate() {
    unset($this->_params['modified_date']);
    $apiResult = $this->callAPISuccess('note', 'create', $this->_params);
    $this->assertAPISuccess($apiResult);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($apiResult['values'][$apiResult['id']]['modified_date'])));
    $this->noteDelete(array(
      'id' => $apiResult['id'],
    ));
  }

  /**
   * Check update with empty parameter array.
   *
   * Please don't copy & paste this - is of marginal value
   * better to put time into the function on Syntax Conformance class that tests this
   */
  public function testUpdateWithEmptyParams() {
    $this->callAPIFailure('note', 'create', array());
  }

  /**
   * Check update with missing parameter (contact id).
   *
   * Error expected
   */
  public function testUpdateWithoutContactId() {
    $params = array(
      'entity_id' => $this->_contactID,
      'entity_table' => 'civicrm_contact',
    );
    $this->callAPIFailure('note', 'create', $params,
      'Mandatory key(s) missing from params array: note'
    );
  }

  /**
   * Check civicrm_note update.
   */
  public function testUpdate() {
    $params = array(
      'id' => $this->_noteID,
      'contact_id' => $this->_contactID,
      'note' => 'Note1',
      'subject' => 'Hello World',
    );

    // Update Note.
    $this->callAPISuccess('note', 'create', $params);
    $note = $this->callAPISuccess('Note', 'Get', array());
    $this->assertEquals($note['id'], $this->_noteID);
    $this->assertEquals($note['values'][$this->_noteID]['entity_id'], $this->_contactID);
    $this->assertEquals($note['values'][$this->_noteID]['entity_table'], 'civicrm_contact');
    $this->assertEquals('Hello World', $note['values'][$this->_noteID]['subject']);
    $this->assertEquals('Note1', $note['values'][$this->_noteID]['note']);
  }

  /**
   * Check delete with empty parameters array.
   *
   * Error expected.
   */
  public function testDeleteWithEmptyParams() {
    $this->callAPIFailure('note', 'delete', array(), 'Mandatory key(s) missing from params array: id');
  }

  /**
   * Check delete with wrong id.
   *
   * Error expected
   */
  public function testDeleteWithWrongID() {
    $params = array(
      'id' => 99999,
    );
    $this->callAPIFailure('note', 'delete', $params, 'Error while deleting Note');
  }

  /**
   * Check civicrm_note delete.
   */
  public function testDelete() {
    $additionalNote = $this->noteCreate($this->_contactID);

    $params = array(
      'id' => $additionalNote['id'],
    );

    $this->callAPIAndDocument('note', 'delete', $params, __FUNCTION__, __FILE__);
  }

}

/**
 * Test civicrm note create() using example code.
 */
function testNoteCreateExample() {
  require_once 'api/v3/examples/Note/Create.php';
  $result = Note_get_example();
  $expectedResult = Note_get_expectedresult();
  $this->assertEquals($result, $expectedResult);
}
