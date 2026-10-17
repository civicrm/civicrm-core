<?php

namespace Civi\Financialacls;

use Civi\Api4\Generic\Result;
use Civi\Api4\MembershipType;
use Civi\Api4\WordReplacement;

// I fought the Autoloader and the autoloader won.
require_once 'BaseTestClass.php';

/**
 * @group headless
 */
class MembershipTypesTest extends BaseTestClass {

  public function tearDown(): void {
    WordReplacement::delete(FALSE)->addWhere('id', '>', 0)->execute();
    \CRM_Core_BAO_WordReplacement::rebuild();
    \CRM_Member_Page_MembershipType::$_links = NULL;
    MembershipType::delete(FALSE)->addWhere('id', '>', 0)->execute();
    parent::tearDown();
  }

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
   * The edit & delete links are removed whatever their labels are, e.g. on a translated site.
   */
  public function testMembershipTypePageLinksWithRelabelledActions(): void {
    WordReplacement::save(FALSE)
      ->setRecords([
        ['find_word' => 'Edit', 'replace_word' => 'Bearbeiten'],
        ['find_word' => 'Disable', 'replace_word' => 'Deaktivieren'],
        ['find_word' => 'Delete', 'replace_word' => 'Löschen'],
      ])
      ->setDefaults(['match_type' => 'exactMatch', 'is_active' => TRUE])
      ->execute();
    \CRM_Core_BAO_WordReplacement::rebuild();
    \CRM_Member_Page_MembershipType::$_links = NULL;
    $types = $this->setUpMembershipTypesACLLimited();
    $this->setPermissions(array_diff(\CRM_Core_Config::singleton()->userPermissionClass->permissions, [
      'edit contributions of type Donation',
      'delete contributions of type Donation',
    ]));

    $page = new \CRM_Member_Page_MembershipType();
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
        ['title' => 'Forbidden', 'financial_type_id:name' => 'Member Dues', 'weight' => 1],
        ['title' => 'Go for it', 'financial_type_id:name' => 'Donation', 'weight' => 2],
      ])
      ->setDefaults(['period_type' => 'rolling', 'member_of_contact_id' => 1, 'duration_unit' => 'month'])
      ->execute()
      ->indexBy('title');
    $this->setupLoggedInUserWithLimitedFinancialTypeAccess();
    return $types;
  }

}
