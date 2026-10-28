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

use Civi\Test\FormWrapper;
use Civi\Test\HeadlessInterface;

/**
 * Test class for Relationship permissions on contact view and form
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Page_View_RelationshipPermissionTest extends CiviUnitTestCase implements HeadlessInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->apply();
  }

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_relationship']);
    CRM_Utils_Hook::singleton()->reset();
    CRM_Core_Session::singleton()->reset();
    $_REQUEST = $_GET = $_POST = [];
    parent::tearDown();
  }

  /**
   * Test that deleting a relationship via confirmed=1 in CRM_Contact_Page_View_Relationship
   * is denied if the relationship does not belong to the contact in cid.
   */
  public function testConfirmedDeleteRejectsUnrelatedContact(): void {
    $contactA = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_a');
    $contactB = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_b');
    $unrelated = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'unrelated');

    $rel = $this->createTestEntity('Relationship', [
      'contact_id_a' => $contactA['id'],
      'contact_id_b' => $contactB['id'],
      'relationship_type_id' => 1,
    ], 'rel_test');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'edit all contacts']);

    $_REQUEST = $_GET = [
      'cid' => $unrelated['id'],
      'id' => $rel['id'],
      'action' => CRM_Core_Action::DELETE,
      'confirmed' => 1,
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    $page = new CRM_Contact_Page_View_Relationship();
    $page->run();
  }

  /**
   * Test that deleting a relationship via confirmed=1 in CRM_Contact_Page_View_Relationship
   * is denied if the user lacks edit permission on either contact.
   */
  public function testConfirmedDeleteDeniedWithoutEditPermissionOnBothContacts(): void {
    $contactA = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_a2');
    $contactB = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_b2');

    $rel = $this->createTestEntity('Relationship', [
      'contact_id_a' => $contactA['id'],
      'contact_id_b' => $contactB['id'],
      'relationship_type_id' => 1,
    ], 'rel_test2');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'view all contacts']);

    $_REQUEST = $_GET = [
      'cid' => $contactA['id'],
      'id' => $rel['id'],
      'action' => CRM_Core_Action::DELETE,
      'confirmed' => 1,
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    $page = new CRM_Contact_Page_View_Relationship();
    $page->run();
  }

  /**
   * Test that CRM_Contact_Form_Relationship denies access when user
   * has edit permission on contact_id_b but not contact_id_a.
   */
  public function testRelationshipFormDeniedWithoutEditPermissionOnContactA(): void {
    $contactA = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_a3');
    $contactB = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_b3');

    $rel = $this->createTestEntity('Relationship', [
      'contact_id_a' => $contactA['id'],
      'contact_id_b' => $contactB['id'],
      'relationship_type_id' => 1,
    ], 'rel_test3');

    $this->createLoggedInUser();
    // Allow edit on contactB but only view on contactA
    $this->setPermissions(['access CiviCRM', 'view all contacts']);

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    $this->getTestForm('CRM_Contact_Form_Relationship', [], [
      'cid' => $contactB['id'],
      'id' => $rel['id'],
      'action' => CRM_Core_Action::UPDATE,
      'rtype' => 'b_a',
    ])->processForm(FormWrapper::PREPROCESSED);
  }

  /**
   * Test that CRM_Contact_Form_Relationship denies creating a permissioned relationship
   * when user lacks edit permission on the related contact.
   */
  public function testRelationshipFormCannotGrantPermissionWithoutEditAccessOnRelatedContact(): void {
    $userId = $this->createLoggedInUser();
    $targetContact = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_target');

    $this->setPermissions(['access CiviCRM', 'edit my contact', 'view all contacts']);

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    $this->getTestForm('CRM_Contact_Form_Relationship', [
      'relationship_type_id' => '1_a_b',
      'related_contact_id' => (string) $targetContact['id'],
      'is_permission_a_b' => 1,
    ], [
      'cid' => $userId,
      'action' => CRM_Core_Action::ADD,
    ])->processForm(FormWrapper::SUBMITTED);
  }

}
