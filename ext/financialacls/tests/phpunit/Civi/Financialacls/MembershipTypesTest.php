<?php

namespace Civi\Financialacls;

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
  public function testMembershipTypesHook() {
    $types = MembershipType::save(FALSE)->setRecords([
      ['name' => 'Forbidden', 'financial_type_id:name' => 'Member Dues'],
      ['name' => 'Go for it', 'financial_type_id:name' => 'Donation'],
    ])->setDefaults(['period_type' => 'rolling', 'member_of_contact_id' => 1])->execute()->indexBy('name');
    $this->setupLoggedInUserWithLimitedFinancialTypeAccess();
    $permissionedTypes = \CRM_Member_BAO_Membership::buildMembershipTypeValues(new \CRM_Member_Form_Membership());
    $this->assertEquals([$types['Go for it']['id']], array_keys($permissionedTypes));
  }

}
