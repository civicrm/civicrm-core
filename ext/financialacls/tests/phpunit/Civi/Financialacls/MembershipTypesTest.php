<?php

namespace Civi\Financialacls;

use Civi\Api4\Generic\Result;
use Civi\Api4\MembershipType;

// I fought the Autoloader and the autoloader won.
require_once 'BaseTestClass.php';

/**
 * @group headless
 */
class MembershipTypesTest extends BaseTestClass {

  /**
   * Test buildMembershipTypes.
   */
  public function testMembershipTypesHook(): void {
    $types = $this->setUpMembershipTypesACLLimited();
    $permissionedTypes = \CRM_Member_BAO_Membership::buildMembershipTypeValues(new \CRM_Member_Form_Membership());
    $this->assertEquals([$types['Go for it']['id']], array_keys($permissionedTypes));
  }

  /**
   * Test the membership type page loads correctly.
   */
  public function testMembershipTypePage(): void {
    $page = new \CRM_Member_Page_MembershipType();
    $types = $this->setUpMembershipTypesACLLimited();
    $page->browse();
    $assigned = \CRM_Core_Smarty::singleton()->getTemplateVars();
    $this->assertArrayNotHasKey($types['Forbidden']['id'], $assigned['rows']);
    $this->assertArrayHasKey($types['Go for it']['id'], $assigned['rows']);
    $links = $assigned['rows'][$types['Go for it']['id']]['action'];
    $this->assertStringContainsString("title='Edit Membership Type' ", $links);
    $this->assertStringContainsString("title='Disable Membership Type' ", $links);
    $this->assertStringContainsString("title='Delete Membership Type' ", $links);

    // Now check that the edit & delete links are removed if we remove those permissions.
    $permissions = \CRM_Core_Config::singleton()->userPermissionClass->permissions;
    foreach ($permissions as $index => $permission) {
      if (in_array($permission, ['edit contributions of type Donation', 'delete contributions of type Donation'], TRUE)) {
        unset($permissions[$index]);
      }
    }
    $this->setPermissions($permissions);
    $page->browse();
    $assigned = \CRM_Core_Smarty::singleton()->getTemplateVars();
    $this->assertEquals('<span></span>', $assigned['rows'][$types['Go for it']['id']]['action']);
  }

  /**
   * Set up a membership scenario where the user can access one type but not the other.
   *
   * @return \Civi\Api4\Generic\Result
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function setUpMembershipTypesACLLimited(): Result {
    $types = MembershipType::save(FALSE)
      ->setRecords([
        ['name' => 'Forbidden', 'financial_type_id:name' => 'Member Dues', 'weight' => 1],
        ['name' => 'Go for it', 'financial_type_id:name' => 'Donation', 'weight' => 2],
      ])
      ->setDefaults(['period_type' => 'rolling', 'member_of_contact_id' => 1, 'duration_unit' => 'month'])
      ->execute()
      ->indexBy('name');
    $this->setupLoggedInUserWithLimitedFinancialTypeAccess();
    return $types;
  }

}
