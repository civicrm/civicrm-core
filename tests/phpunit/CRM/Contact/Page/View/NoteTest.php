<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This code is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Test class for CRM_Contact_Page_View_Note BAO
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Contact_Page_View_NoteTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
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
    parent::tearDown();
  }

  public function testNoContactIdNote() {
    $contactId = $this->individualCreate();
    foreach ([1, 2, 3, 4, 5] as $noteID) {
      $note = new CRM_Core_DAO_Note();
      $note->entity_id = $contactId;
      $note->subject = 'Test Note ' . $noteID;
      $note->note = 'Test Note from Tests';
      $note->entity_table = 'civicrm_contact';
      if ($noteID == 5) {
        $note->contact_id = $contactId;
      }
      $note->save();
    }
    $page = new CRM_Contact_Page_View_Note();
    $page->_contactId = $contactId;
    $page->_permission = CRM_Core_PERMISSION::EDIT;
    $page->browse();
    $this->assertEquals(count($page->values), 5);
    foreach ($page->values as $note) {
      $this->assertEquals($note['entity_id'], $contactId);
      if ($note['id'] == 5) {
        $this->assertEquals($note['createdBy'], 'Mr. Anthony Anderson II');
      }
    }
  }

}
