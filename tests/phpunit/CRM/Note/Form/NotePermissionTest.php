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

use Civi\Api4\Note;
use Civi\Test\HeadlessInterface;
use Civi\Test\FormTrait;

/**
 * Test class for Note permissions and privacy
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Note_Form_NotePermissionTest extends CiviUnitTestCase implements HeadlessInterface {

  use FormTrait;

  protected $allowedContacts = [];

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->apply();
  }

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_note', 'civicrm_contact'], TRUE);
    CRM_Utils_Hook::singleton()->reset();
    CRM_Core_Session::singleton()->reset();
    $_REQUEST = $_GET = $_POST = [];
    parent::tearDown();
  }

  public function aclWhereMultipleContacts($type, &$tables, &$params, &$filteredWhereClause): void {
    if (!empty($this->allowedContacts)) {
      $contactIds = implode(',', array_map('intval', $this->allowedContacts));
      $filteredWhereClause .= " contact_a.id IN ($contactIds) ";
    }
    else {
      $filteredWhereClause .= " 1=0 ";
    }
  }

  /**
   * Test that adding a note to an inaccessible contact via form is bounced.
   */
  public function testNoteFormAddWithoutPermissionBounces(): void {
    $c1 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_1');
    $this->createLoggedInUser();
    $this->allowedContacts = [];
    $this->setPermissions(['access CiviCRM']);
    \CRM_Utils_Hook::singleton()->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);

    $this->expectException(CRM_Core_Exception_PrematureExitException::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    $this->getTestForm('CRM_Note_Form_Note', [], [
      'action' => CRM_Core_Action::ADD,
      'entity_table' => 'civicrm_contact',
      'entity_id' => $c1['id'],
    ])->processForm();
  }

  /**
   * Test that viewing a private note by a non-author without 'view all notes' is bounced.
   */
  public function testNoteFormViewPrivateNoteNonAuthorBounces(): void {
    $authorUser = $this->createLoggedInUser();
    $c1 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_1');
    $note = $this->createTestEntity('Note', [
      'entity_table' => 'civicrm_contact',
      'entity_id' => $c1['id'],
      'contact_id' => $authorUser,
      'privacy' => 1,
      'note' => 'Private text',
    ], 'private_note');

    // Switch to another user with edit access to contact but not 'view all notes'
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'edit all contacts']);

    $this->expectException(CRM_Core_Exception_PrematureExitException::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    $this->getTestForm('CRM_Note_Form_Note', [], [
      'action' => CRM_Core_Action::VIEW,
      'id' => $note['id'],
    ])->processForm();
  }

  /**
   * Test that updating a private note by a non-author without 'view all notes' is bounced.
   */
  public function testNoteFormUpdatePrivateNoteNonAuthorBounces(): void {
    $authorUser = $this->createLoggedInUser();
    $c1 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_1');
    $note = $this->createTestEntity('Note', [
      'entity_table' => 'civicrm_contact',
      'entity_id' => $c1['id'],
      'contact_id' => $authorUser,
      'privacy' => 1,
      'note' => 'Private text',
    ], 'private_note');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'edit all contacts']);

    $this->expectException(CRM_Core_Exception_PrematureExitException::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    $this->getTestForm('CRM_Note_Form_Note', [], [
      'action' => CRM_Core_Action::UPDATE,
      'id' => $note['id'],
    ])->processForm();
  }

  /**
   * Test that deleting a private note by a non-author without 'view all notes' is bounced.
   */
  public function testNoteFormDeletePrivateNoteNonAuthorBounces(): void {
    $authorUser = $this->createLoggedInUser();
    $c1 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_1');
    $note = $this->createTestEntity('Note', [
      'entity_table' => 'civicrm_contact',
      'entity_id' => $c1['id'],
      'contact_id' => $authorUser,
      'privacy' => 1,
      'note' => 'Private text',
    ], 'private_note');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'edit all contacts']);

    $this->expectException(CRM_Core_Exception_PrematureExitException::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    $this->getTestForm('CRM_Note_Form_Note', [], [
      'action' => CRM_Core_Action::DELETE,
      'id' => $note['id'],
    ])->processForm();
  }

  /**
   * Test that editing an existing note via form preserves original author.
   */
  public function testNoteFormUpdatePreservesAuthor(): void {
    $authorUser = $this->createLoggedInUser();
    $c1 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_1');
    $note = $this->createTestEntity('Note', [
      'entity_table' => 'civicrm_contact',
      'entity_id' => $c1['id'],
      'contact_id' => $authorUser,
      'privacy' => 0,
      'note' => 'Original text',
    ], 'public_note');

    // Switch to another user with edit permissions
    $editorUser = $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'edit all contacts']);

    $this->getTestForm('CRM_Note_Form_Note', ['note' => 'Updated text', 'privacy' => 0], [
      'action' => CRM_Core_Action::UPDATE,
      'id' => $note['id'],
    ])->processForm();

    $saved = Note::get(FALSE)->addWhere('id', '=', $note['id'])->execute()->first();
    $this->assertEquals('Updated text', $saved['note']);
    $this->assertEquals($authorUser, $saved['contact_id']);
  }

  /**
   * Test that CRM_Core_BAO_Note::deleteRecord with check_permissions blocks non-author on private note.
   */
  public function testBAODeletePrivateNoteEnforcesPermission(): void {
    $authorUser = $this->createLoggedInUser();
    $c1 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_1');
    $note = $this->createTestEntity('Note', [
      'entity_table' => 'civicrm_contact',
      'entity_id' => $c1['id'],
      'contact_id' => $authorUser,
      'privacy' => 1,
      'note' => 'Private text',
    ], 'private_note');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'edit all contacts']);

    $this->expectException(\Civi\API\Exception\UnauthorizedException::class);
    $this->expectExceptionMessage('Permission denied to modify private note.');

    CRM_Core_BAO_Note::deleteRecord(['id' => $note['id'], 'check_permissions' => TRUE]);
  }

  /**
   * Test that child notes are NOT deleted if parent note deletion is unauthorized.
   */
  public function testBAODeleteCascadeAbortsIfParentForbidden(): void {
    $c1 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_1');
    $parentNote = $this->createTestEntity('Note', [
      'entity_table' => 'civicrm_contact',
      'entity_id' => $c1['id'],
      'note' => 'Parent note',
    ], 'parent_note');
    $childNote = $this->createTestEntity('Note', [
      'entity_table' => 'civicrm_note',
      'entity_id' => $parentNote['id'],
      'note' => 'Child comment',
    ], 'child_note');

    $this->createLoggedInUser();
    $this->allowedContacts = [];
    $this->setPermissions(['access CiviCRM']);
    \CRM_Utils_Hook::singleton()->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);

    $threw = FALSE;
    try {
      CRM_Core_BAO_Note::deleteRecord(['id' => $parentNote['id'], 'check_permissions' => TRUE]);
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $threw = TRUE;
    }
    $this->assertTrue($threw, 'Expected UnauthorizedException');

    // Both notes should still exist
    $parentExists = Note::get(FALSE)->addWhere('id', '=', $parentNote['id'])->execute()->first();
    $childExists = Note::get(FALSE)->addWhere('id', '=', $childNote['id'])->execute()->first();
    $this->assertNotEmpty($parentExists);
    $this->assertNotEmpty($childExists);
  }

}
