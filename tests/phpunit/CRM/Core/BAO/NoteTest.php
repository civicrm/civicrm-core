<?php

use Civi\Api4\Contact;
use Civi\Api4\Note;

/**
 * Class CRM_Core_BAO_NoteTest
 *
 * @group headless
 */
class CRM_Core_BAO_NoteTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_note', 'civicrm_contact'], TRUE);
    parent::tearDown();
  }

  /**
   * Test getTopParent returns the top-most parent note.
   */
  public function testGetTopParent(): void {
    $contact = Contact::create(FALSE)->setValues([
      'first_name' => 'Top',
      'last_name' => 'Parent',
    ])->execute()->first();

    $parentNote = Note::create(FALSE)->setValues([
      'entity_table' => 'civicrm_contact',
      'entity_id' => $contact['id'],
      'note' => 'Parent note',
    ])->execute()->first();

    $childNote = Note::create(FALSE)->setValues([
      'entity_table' => 'civicrm_note',
      'entity_id' => $parentNote['id'],
      'note' => 'Child note',
    ])->execute()->first();

    $grandChildNote = Note::create(FALSE)->setValues([
      'entity_table' => 'civicrm_note',
      'entity_id' => $childNote['id'],
      'note' => 'Grandchild note',
    ])->execute()->first();

    // Top parent of parentNote is itself
    $topOfParent = CRM_Core_BAO_Note::getTopParent($parentNote['id']);
    $this->assertEquals($parentNote['id'], $topOfParent->id);
    $this->assertEquals('civicrm_contact', $topOfParent->entity_table);
    $this->assertEquals($contact['id'], $topOfParent->entity_id);

    // Top parent of childNote is parentNote
    $topOfChild = CRM_Core_BAO_Note::getTopParent($childNote['id']);
    $this->assertEquals($parentNote['id'], $topOfChild->id);

    // Top parent of grandChildNote is parentNote
    $topOfGrandChild = CRM_Core_BAO_Note::getTopParent($grandChildNote['id']);
    $this->assertEquals($parentNote['id'], $topOfGrandChild->id);
  }

}
