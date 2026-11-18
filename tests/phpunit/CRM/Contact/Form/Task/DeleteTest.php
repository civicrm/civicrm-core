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

  use \Civi\Test\FormTrait;

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
  public function testDeleteToTrash(): void {
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
    $this->assertSame('1', $is_deleted);

    $session_status = CRM_Core_Session::singleton()->getStatus();
    $this->assertSame('Mr. Delete Me has been moved to the trash.', $session_status[0]['text']);

    // put settings back
    Civi::settings()->set('contact_undelete', $old_undelete_setting);
  }

  /**
   * Deleting from search results is refused when the selection holds a
   * contact the user may view but not edit.
   */
  public function testDeleteFromSearchRequiresEditPermission(): void {
    $viewOnlyContactID = $this->individualCreate([], 'view_only');
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'delete contacts'];
    $editable = $this->deleted_contact_id;
    $this->hookClass->setHook('civicrm_aclWhereClause', function ($type, &$tables, &$whereTables, &$contactID, &$where) use ($editable, $viewOnlyContactID) {
      $ids = $type == CRM_Core_Permission::EDIT ? [$editable] : [$editable, $viewOnlyContactID];
      $where = 'contact_a.id IN (' . implode(',', $ids) . ')';
    });

    $this->getTestForm('CRM_Contact_Form_Search_Basic', ['radio_ts' => 'ts_all'])
      ->addSubsequentForm('CRM_Contact_Form_Task_Delete', [])
      ->processForm();
    $status = CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->assertStringContainsString('one or more of the selected contacts', $status[0]['text']);
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_contact WHERE is_deleted = 1 AND id IN (%1, %2)', [
      1 => [$editable, 'Integer'],
      2 => [$viewOnlyContactID, 'Integer'],
    ]));
  }

  /**
   * Deleting from search results works when every selected contact is editable.
   */
  public function testDeleteFromSearchWithEditPermission(): void {
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'delete contacts'];
    $editable = $this->deleted_contact_id;
    $this->hookClass->setHook('civicrm_aclWhereClause', function ($type, &$tables, &$whereTables, &$contactID, &$where) use ($editable) {
      $where = 'contact_a.id = ' . $editable;
    });

    $this->getTestForm('CRM_Contact_Form_Search_Basic', ['radio_ts' => 'ts_all'])
      ->addSubsequentForm('CRM_Contact_Form_Task_Delete', [])
      ->processForm();
    $this->assertSame(1, (int) CRM_Core_DAO::singleValueQuery('SELECT is_deleted FROM civicrm_contact WHERE id = %1', [1 => [$editable, 'Integer']]));
  }

  /**
   * Test restore from trash.
   */
  public function testRestoreFromTrash(): void {
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
    $this->assertSame('0', $is_deleted);

    $session_status = CRM_Core_Session::singleton()->getStatus();
    $this->assertSame('Mr. Delete Me has been restored from the trash.', $session_status[0]['text']);

    // put settings back
    Civi::settings()->set('contact_undelete', $old_undelete_setting);
  }

  /**
   * Test delete permanently.
   *
   * This is different from testDeleteWithoutTrash. This is where you have
   * trash enabled and first move to trash, then delete from the trash.
   */
  public function testDeletePermanently(): void {
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
    $this->assertSame('Mr. Delete Me has been permanently deleted.', $session_status[0]['text']);

    // put settings back
    Civi::settings()->set('contact_undelete', $old_undelete_setting);
  }

  /**
   * Test delete when trash is not enabled.
   *
   * This is different from testDeletePermanently. This is where trash is
   * not enabled at all.
   */
  public function testDeleteWithoutTrash(): void {
    // Clear any status messages from earlier.
    CRM_Core_Session::singleton()->getStatus(TRUE);

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

    $session_status = CRM_Core_Session::singleton()->getStatus();
    $this->assertSame('Mr. Delete Me has been permanently deleted.', $session_status[0]['text']);

    // put settings back
    Civi::settings()->set('contact_undelete', $old_undelete_setting);
  }

}
