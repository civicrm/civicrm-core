<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
