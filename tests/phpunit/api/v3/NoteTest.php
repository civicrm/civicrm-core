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
 * Class contains api test cases for "civicrm_note"
 * @group headless
 */
class api_v3_NoteTest extends CiviUnitTestCase {

  protected $_contactID;
  protected $_params;
  protected $_noteID;
  protected $_note;

  public function setUp() {

    // Connect to the database.
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_contactID = $this->organizationCreate(NULL);

    $this->_params = [
      'entity_table' => 'civicrm_contact',
      'entity_id' => $this->_contactID,
      'note' => 'Hello!!! m testing Note',
      'contact_id' => $this->_contactID,
      'modified_date' => '2011-01-31',
      'subject' => 'Test Note',
    ];
    $this->_note = $this->noteCreate($this->_contactID);
    $this->_noteID = $this->_note['id'];
  }

  ///////////////// civicrm_note_get methods

  /**
   * Check retrieve note with empty parameter array.
   *
   * Error expected
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetWithEmptyParams($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess('note', 'get', []);
  }

  /**
   * Check retrieve note with missing parameters.
   *
   * Error expected
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetWithoutEntityId($version) {
    $this->_apiversion = $version;
    $params = [
      'entity_table' => 'civicrm_contact',
    ];
    $this->callAPISuccess('note', 'get', $params);
  }

  /**
   * Check civicrm_note get.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGet($version) {
    $this->_apiversion = $version;
    $entityId = $this->_noteID;
    $params = [
      'entity_table' => 'civicrm_contact',
      'entity_id' => $entityId,
    ];
    $this->callAPIAndDocument('note', 'get', $params, __FUNCTION__, __FILE__);
  }

  /**
   * Check create with empty parameter array.
   *
   * Error Expected
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateWithEmptyNoteField($version) {
    $this->_apiversion = $version;
    $this->_params['note'] = "";
    $this->callAPIFailure('note', 'create', $this->_params,
      'missing'
    );
  }

  /**
   * Check create with partial params.
   *
   * Error expected
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateWithoutEntityId($version) {
    $this->_apiversion = $version;
    unset($this->_params['entity_id']);
    $this->callAPIFailure('note', 'create', $this->_params,
      'entity_id');
  }

  /**
   * Check create with partially empty params.
   *
   * Error expected
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateWithEmptyEntityId($version) {
    $this->_apiversion = $version;
    $this->_params['entity_id'] = "";
    $this->callAPIFailure('note', 'create', $this->_params,
      'entity_id');
  }

  /**
   * Check civicrm note create.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreate($version) {
    $this->_apiversion = $version;

    $result = $this->callAPIAndDocument('note', 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$result['id']]['note'], 'Hello!!! m testing Note');
    $this->assertEquals(date('Y-m-d', strtotime($this->_params['modified_date'])), date('Y-m-d', strtotime($result['values'][$result['id']]['modified_date'])));

    $this->assertArrayHasKey('id', $result);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateWithApostropheInString($version) {
    $this->_apiversion = $version;
    $params = [
      'entity_table' => 'civicrm_contact',
      'entity_id' => $this->_contactID,
      'note' => "Hello!!! ' testing Note",
      'contact_id' => $this->_contactID,
      'modified_date' => '2011-01-31',
      'subject' => "With a '",
      'sequential' => 1,
    ];
    $result = $this->callAPISuccess('Note', 'Create', $params);
    $this->assertAPISuccess($result);
    $this->assertEquals($result['values'][0]['note'], "Hello!!! ' testing Note");
    $this->assertEquals($result['values'][0]['subject'], "With a '");
    $this->assertArrayHasKey('id', $result);
  }

  /**
   * Check civicrm_note_create - tests used of default set to .
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateWithoutModifiedDate($version) {
    $this->_apiversion = $version;
    unset($this->_params['modified_date']);
    $note = $this->callAPISuccess('note', 'create', $this->_params);
    $apiResult = $this->callAPISuccess('note', 'get', ['id' => $note['id']]);
    $this->assertAPISuccess($apiResult);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($apiResult['values'][$apiResult['id']]['modified_date'])));
  }

  /**
   * Check update with empty parameter array.
   *
   * Please don't copy & paste this - is of marginal value
   * better to put time into the function on Syntax Conformance class that tests this
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testUpdateWithEmptyParams($version) {
    $this->_apiversion = $version;
    $this->callAPIFailure('note', 'create', []);
  }

  /**
   * Check update with missing parameter (contact id).
   *
   * Error expected
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testUpdateWithoutContactId($version) {
    $this->_apiversion = $version;
    $params = [
      'entity_id' => $this->_contactID,
      'entity_table' => 'civicrm_contact',
    ];
    $this->callAPIFailure('note', 'create', $params,
      'missing'
    );
  }

  /**
   * Check civicrm_note update.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testUpdate($version) {
    $this->_apiversion = $version;
    $params = [
      'id' => $this->_noteID,
      'contact_id' => $this->_contactID,
      'note' => 'Note1',
      'subject' => 'Hello World',
    ];

    // Update Note.
    $this->callAPISuccess('note', 'create', $params);
    $note = $this->callAPISuccess('Note', 'Get', []);
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
    $this->callAPIFailure('note', 'delete', [], 'Mandatory key(s) missing from params array: id');
  }

  /**
   * Check delete with wrong id.
   *
   * Error expected
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteWithWrongID($version) {
    $this->_apiversion = $version;
    $params = [
      'id' => 99999,
    ];
    $this->callAPIFailure('note', 'delete', $params, 'Note');
  }

  /**
   * Check civicrm_note delete.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDelete($version) {
    $this->_apiversion = $version;
    $additionalNote = $this->noteCreate($this->_contactID);

    $params = [
      'id' => $additionalNote['id'],
    ];

    $this->callAPIAndDocument('note', 'delete', $params, __FUNCTION__, __FILE__);
  }

  public function testNoteJoin() {
    $org = $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Organization',
      'organization_name' => 'Org123',
      'api.Note.create' => [
        'note' => 'Hello join',
      ],
    ]);
    // Fetch contact info via join
    $result = $this->callAPISuccessGetSingle('Note', [
      'return' => ["entity_id.organization_name", "note"],
      'entity_id' => $org['id'],
      'entity_table' => "civicrm_contact",
    ]);
    $this->assertEquals('Org123', $result['entity_id.organization_name']);
    $this->assertEquals('Hello join', $result['note']);
    // This should return no results by restricting contact_type
    $result = $this->callAPISuccess('Note', 'get', [
      'return' => ["entity_id.organization_name"],
      'entity_id' => $org['id'],
      'entity_table' => "civicrm_contact",
      'entity_id.contact_type' => "Individual",
    ]);
    $this->assertEquals(0, $result['count']);
  }

}

/**
 * Test civicrm note create() using example code.
 */
function testNoteCreateExample() {
  require_once 'api/v3/examples/Note/Create.ex.php';
  $result = Note_get_example();
  $expectedResult = Note_get_expectedresult();
  $this->assertEquals($result, $expectedResult);
}
