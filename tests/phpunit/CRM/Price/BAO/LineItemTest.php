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
class CRM_Price_BAO_LineItemTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    Civi::cache('long')->clear();
    parent::tearDown();
  }

  /**
   * A membership payment record with no matching line item should be detected.
   */
  public function testSiteHasMembershipPaymentRecordsNotReflectedInLineItems(): void {
    $this->assertFalse(CRM_Price_BAO_LineItem::siteHasMembershipPaymentRecordsNotReflectedInLineItems());

    $contactID = $this->individualCreate();
    $membershipID = $this->contactMembershipCreate(['contact_id' => $contactID]);
    $contributionID = $this->contributionCreate(['contact_id' => $contactID]);
    CRM_Core_DAO::executeQuery('INSERT INTO civicrm_membership_payment (membership_id, contribution_id) VALUES (%1, %2)', [
      1 => [$membershipID, 'Integer'],
      2 => [$contributionID, 'Integer'],
    ]);

    Civi::cache('long')->clear();
    $this->assertTrue(CRM_Price_BAO_LineItem::siteHasMembershipPaymentRecordsNotReflectedInLineItems());

    Civi::settings()->set('member_use_civicrm_membership_payment_table', FALSE);
    Civi::cache('long')->clear();
    $this->assertFalse(CRM_Price_BAO_LineItem::siteHasMembershipPaymentRecordsNotReflectedInLineItems());
  }

}
