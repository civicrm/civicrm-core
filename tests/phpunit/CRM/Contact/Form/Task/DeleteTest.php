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
  * @group headless
  */
class CRM_Contact_Form_Task_DeleteTest extends CiviUnitTestCase {

  /**
   * @var int
   * The contact we are deleting and resurrecting.
   */
  protected $deleted_contact_id;

  protected function setUp(): void {
    parent::setUp();
    $this->deleted_contact_id = $this->individualCreate([
      'first_name' => 'Delete',
      'last_name' => 'Me',
      'prefix_id' => 3,
      'suffix_id' => NULL,
    ]);
  }

  protected function tearDown(): void {
    $this->quickCleanup([
      'civicrm_contact',
    ]);

    parent::tearDown();
  }

  /**
   * Test delete to trash.
   */
  public function testDeleteToTrash() {
    $old_undelete_setting = Civi::settings()->get('contact_undelete');
    Civi::settings()->set('contact_undelete', '1');

    $form = $this->getFormObject('CRM_Contact_Form_Task_Delete');
    $form->set('cid', $this->deleted_contact_id);
    $form->preProcess();
    $form->buildQuickForm();
    $form->setDefaultValues();
    $form->postProcess();

    $query_params = [1 => [$this->deleted_contact_id, 'Integer']];
    $is_deleted = CRM_Core_DAO::singleValueQuery("SELECT is_deleted FROM civicrm_contact WHERE id = %1", $query_params);
    $this->assertEquals(1, $is_deleted);

    $session_status = CRM_Core_Session::singleton()->getStatus();
    $this->assertEquals('Mr. Delete Me has been moved to the trash.', $session_status[0]['text']);

    // put settings back
    Civi::settings()->set('contact_undelete', $old_undelete_setting);
  }

  /**
   * Test restore from trash.
   */
  public function testRestoreFromTrash() {
    // First, put in trash.
    $this->testDeleteToTrash();
    // Clear session status
    CRM_Core_Session::singleton()->getStatus(TRUE);

    $old_undelete_setting = Civi::settings()->get('contact_undelete');
    Civi::settings()->set('contact_undelete', '1');

    $form = $this->getFormObject('CRM_Contact_Form_Task_Delete');
    $form->set('cid', $this->deleted_contact_id);
    $form->set('restore', '1');
    $form->preProcess();
    $form->buildQuickForm();
    $form->setDefaultValues();
    $form->postProcess();

    $query_params = [1 => [$this->deleted_contact_id, 'Integer']];
    $is_deleted = CRM_Core_DAO::singleValueQuery("SELECT is_deleted FROM civicrm_contact WHERE id = %1", $query_params);
    $this->assertEquals(0, $is_deleted);

    $session_status = CRM_Core_Session::singleton()->getStatus();
    $this->assertEquals('Mr. Delete Me has been restored from the trash.', $session_status[0]['text']);

    // put settings back
    Civi::settings()->set('contact_undelete', $old_undelete_setting);
  }

  /**
   * Test delete permanently.
   *
   * This is different from testDeleteWithoutTrash. This is where you have
   * trash enabled and first move to trash, then delete from the trash.
   */
  public function testDeletePermanently() {
    // First, put in trash.
    $this->testDeleteToTrash();
    // Clear session status
    CRM_Core_Session::singleton()->getStatus(TRUE);

    $old_undelete_setting = Civi::settings()->get('contact_undelete');
    Civi::settings()->set('contact_undelete', '1');

    $form = $this->getFormObject('CRM_Contact_Form_Task_Delete');
    $form->set('cid', $this->deleted_contact_id);
    $form->set('skip_undelete', '1');
    $form->preProcess();
    $form->buildQuickForm();
    $form->setDefaultValues();
    $form->postProcess();

    $query_params = [1 => [$this->deleted_contact_id, 'Integer']];
    $contact_id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contact WHERE id = %1", $query_params);
    $this->assertEmpty($contact_id);

    $session_status = CRM_Core_Session::singleton()->getStatus();
    $this->assertEquals('Mr. Delete Me has been permanently deleted.', $session_status[0]['text']);

    // put settings back
    Civi::settings()->set('contact_undelete', $old_undelete_setting);
  }

  /**
   * Test delete when trash is not enabled.
   *
   * This is different from testDeletePermanently. This is where trash is
   * not enabled at all.
   */
  public function testDeleteWithoutTrash() {
    $old_undelete_setting = Civi::settings()->get('contact_undelete');
    Civi::settings()->set('contact_undelete', '0');

    $form = $this->getFormObject('CRM_Contact_Form_Task_Delete');
    $form->set('cid', $this->deleted_contact_id);
    $form->preProcess();
    $form->buildQuickForm();
    $form->setDefaultValues();
    $form->postProcess();

    $query_params = [1 => [$this->deleted_contact_id, 'Integer']];
    $contact_id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contact WHERE id = %1", $query_params);
    $this->assertEmpty($contact_id);

    // @todo This is currently buggy in the UI. It deletes properly but shows
    // the wrong message.
    //$session_status = CRM_Core_Session::singleton()->getStatus();
    //$this->assertEquals('Mr. Delete Me has been permanently deleted.', $session_status[0]['text']);

    // put settings back
    Civi::settings()->set('contact_undelete', $old_undelete_setting);
  }

}
