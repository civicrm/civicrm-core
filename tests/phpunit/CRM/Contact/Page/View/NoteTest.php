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

  /**
   * Test that note_date is automatically set to created_date if empty
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testCreateNoteEmptyNoteDate() {
    $contactID = $this->individualCreate();
    $noteForm = new CRM_Note_Form_Note();
    $noteForm->_action = CRM_Core_Action::ADD;
    $noteForm->controller = new CRM_Core_Controller_Simple('CRM_Note_Form_Note', ts('Contact Notes'), CRM_Core_Action::ADD);
    $noteForm->controller->setEmbedded(TRUE);
    $noteForm->set('entityTable', 'civicrm_contact');
    $noteForm->set('entityId', $contactID);
    $noteForm->preProcess();
    $noteForm->buildQuickForm();

    $submitValues = [
      'note' => 'testnote',
      'note_date' => '',
      'privacy' => '',
      'parent_id' => '',
      'subject' => 'testsubject',
    ];
    $container =& $noteForm->controller->container();
    $container['values'][$noteForm->getName()] = $submitValues;
    $noteForm->postProcess();

    $savedNote = \Civi\Api4\Note::get(FALSE)
      ->execute()
      ->first();
    // If note_date is empty it should have been set to created_date on create
    $this->assertEquals($savedNote['created_date'], $savedNote['note_date']);
  }

}
