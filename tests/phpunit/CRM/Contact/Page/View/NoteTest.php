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

  public function testNoContactIDNote(): void {
    $contactID = $this->individualCreate();
    foreach ([1, 2, 3, 4, 5] as $noteID) {
      $note = new CRM_Core_DAO_Note();
      $note->entity_id = $contactID;
      $note->subject = 'Test Note ' . $noteID;
      $note->note = 'Test Note from Tests';
      $note->entity_table = 'civicrm_contact';
      if ($noteID === 5) {
        $note->contact_id = $contactID;
      }
      $note->save();
    }
    $_REQUEST['cid'] = $contactID;
    $page = new CRM_Contact_Page_View_Note();
    $page->_permission = CRM_Core_Permission::EDIT;
    $page->browse();
    $this->assertCount(5, $page->values);
    foreach ($page->values as $note) {
      $this->assertEquals($note['entity_id'], $contactID);
      if ((int) $note['id'] === 5) {
        $this->assertEquals('Mr. Anthony Anderson II', $note['createdBy']);
      }
    }
  }

}
