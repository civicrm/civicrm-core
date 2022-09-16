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
    $assigned = \CRM_Core_Smarty::singleton()->get_template_vars();
    $this->assertArrayNotHasKey($types['Forbidden']['id'], $assigned['rows']);
    $this->assertArrayHasKey($types['Go for it']['id'], $assigned['rows']);
  }

  /**
   * @return \Civi\Api4\Generic\Result
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
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
