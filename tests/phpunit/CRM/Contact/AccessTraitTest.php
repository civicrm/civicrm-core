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
use Civi\Test\ACLPermissionTrait;
use Civi\Test\HeadlessInterface;

/**
 * Test class for CRM_Contact_AccessTrait, CRM_Core_OwnershipAccessTrait, and CustomValueTable
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_AccessTraitTest extends CiviUnitTestCase implements HeadlessInterface {

  use ACLPermissionTrait;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->apply();
  }

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_email', 'civicrm_note', 'civicrm_contact'], TRUE);
    CRM_Utils_Hook::singleton()->reset();
    CRM_Core_Session::singleton()->reset();
    $_REQUEST = $_GET = $_POST = [];
    parent::tearDown();
  }

  /**
   * Test that deleting a belonging via BAO with check_permissions enforces contact access.
   */
  public function testDeleteBelongingEnforcesContactAccess(): void {
    $c1 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_1');
    $email = $this->createTestEntity('Email', [
      'contact_id' => $c1['id'],
      'email' => 'test@example.com',
    ], 'email_1');

    $this->createLoggedInUser();
    $this->allowedContacts = [0];
    $this->setPermissions(['access CiviCRM']);
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);

    $this->expectException(\Civi\API\Exception\UnauthorizedException::class);
    $this->expectExceptionMessage('Permission denied to modify contact record');

    CRM_Core_BAO_Email::deleteRecord(['id' => $email['id'], 'check_permissions' => TRUE]);
  }

  /**
   * Test that creating a belonging via BAO with check_permissions enforces contact access.
   */
  public function testCreateBelongingEnforcesContactAccess(): void {
    $c1 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_1');

    $this->createLoggedInUser();
    $this->allowedContacts = [0];
    $this->setPermissions(['access CiviCRM']);
    $this->hookClass->setHook('civicrm_aclWhereClause', [$this, 'aclWhereMultipleContacts']);

    $this->expectException(\Civi\API\Exception\UnauthorizedException::class);
    $this->expectExceptionMessage('Permission denied to modify contact record');

    CRM_Core_BAO_Email::writeRecord([
      'contact_id' => $c1['id'],
      'email' => 'test@example.com',
      'check_permissions' => TRUE,
    ]);
  }

  /**
   * Test that creating an owned record (Note) with a different owner is blocked for non-admins.
   */
  public function testOwnershipCreateEnforcesOwnerContactId(): void {
    $user1 = $this->createLoggedInUser();
    $user2 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_2');
    $targetContact = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'target_contact');

    $this->setPermissions(['access CiviCRM', 'edit all contacts']);

    // Test BAO create with check_permissions
    $threw = FALSE;
    try {
      $params = [
        'entity_table' => 'civicrm_contact',
        'entity_id' => $targetContact['id'],
        'contact_id' => $user2['id'],
        'note' => 'Unauthorized owner note BAO',
        'check_permissions' => TRUE,
      ];
      CRM_Core_BAO_Note::add($params);
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $threw = TRUE;
      $this->assertStringContainsString('Cannot change the contact_id of a new Note.', $e->getMessage());
    }
    $this->assertTrue($threw, 'Expected UnauthorizedException from BAO add');

    // Test APIv4 create with checkPermissions
    $threw = FALSE;
    try {
      Note::create(TRUE)
        ->setValues([
          'entity_table' => 'civicrm_contact',
          'entity_id' => $targetContact['id'],
          'contact_id' => $user2['id'],
          'note' => 'Unauthorized owner note APIv4',
        ])
        ->execute();
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $threw = TRUE;
    }
    $this->assertTrue($threw, 'Expected UnauthorizedException from APIv4 create');
  }

  /**
   * Test that CustomValueTable::create does not update another entity's custom value row.
   */
  public function testCustomValueTableCreateCrossEntityMismatch(): void {
    $group = $this->createTestEntity('CustomGroup', [
      'title' => 'Multi Group ' . substr(sha1(CRM_Utils_String::createRandom(8, CRM_Utils_String::ALPHANUMERIC)), 0, 7),
      'extends' => 'Individual',
      'is_multiple' => TRUE,
    ], 'multi_group');

    $field = $this->createTestEntity('CustomField', [
      'custom_group_id' => $group['id'],
      'label' => 'Text Field',
      'data_type' => 'String',
      'html_type' => 'Text',
    ], 'multi_field');

    $c1 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_1');
    $c2 = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_2');

    $fieldName = 'custom_' . $field['id'];
    $val1Params = [
      'entityID' => $c1['id'],
      $fieldName => 'Original 1',
    ];
    CRM_Core_BAO_CustomValueTable::setValues($val1Params);
    $val2Params = [
      'entityID' => $c2['id'],
      $fieldName => 'Original 2',
    ];
    CRM_Core_BAO_CustomValueTable::setValues($val2Params);

    $row2Id = (int) CRM_Core_DAO::singleValueQuery("SELECT id FROM {$group['table_name']} WHERE entity_id = %1", [
      1 => [$c2['id'], 'Integer'],
    ]);
    $this->assertGreaterThan(0, $row2Id);

    // Malicious update: targeting contact 1, but passing contact 2's custom value row ID
    $params = [
      'entityID' => $c1['id'],
      $fieldName . '_' . $row2Id => 'Hacked 2',
    ];

    CRM_Core_BAO_CustomValueTable::setValues($params);

    // Verify contact 2's row was NOT modified
    $savedVal2 = CRM_Core_DAO::singleValueQuery("SELECT {$field['column_name']} FROM {$group['table_name']} WHERE id = %1", [
      1 => [$row2Id, 'Integer'],
    ]);

    $this->assertEquals('Original 2', $savedVal2);
  }

}
