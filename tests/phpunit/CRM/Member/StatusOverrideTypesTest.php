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
 * Class CRM_Member_BAO_MembershipTest
 * @group headless
 */
class CRM_Member_StatusOverrideTypesTest extends CiviUnitTestCase {

  public function testIsOverriddenReturnFalseForNoStatusOverrideType(): void {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isOverridden = $statusOverrideTypes::isOverridden(CRM_Member_StatusOverrideTypes::NO);
    $this->assertFalse($isOverridden);
  }

  public function testIsOverriddenReturnTrueForPermanentStatusOverrideType(): void {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isOverridden = $statusOverrideTypes::isOverridden(CRM_Member_StatusOverrideTypes::PERMANENT);
    $this->assertTrue($isOverridden);
  }

  public function testIsOverriddenReturnTrueForUntilDateStatusOverrideType(): void {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isOverridden = $statusOverrideTypes::isOverridden(CRM_Member_StatusOverrideTypes::UNTIL_DATE);
    $this->assertTrue($isOverridden);
  }

  public function testIsNoReturnTrueForNoStatusOverrideType(): void {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isNo = $statusOverrideTypes::isNo(CRM_Member_StatusOverrideTypes::NO);
    $this->assertTrue($isNo);
  }

  public function testIsNoReturnFalseForOtherStatusOverrideType(): void {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isNo = $statusOverrideTypes::isNo(CRM_Member_StatusOverrideTypes::PERMANENT);
    $this->assertFalse($isNo);

    $isNo = $statusOverrideTypes::isNo(CRM_Member_StatusOverrideTypes::UNTIL_DATE);
    $this->assertFalse($isNo);
  }

  public function testisPermanentReturnTrueForPermanentStatusOverrideType(): void {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isPermanent = $statusOverrideTypes::isPermanent(CRM_Member_StatusOverrideTypes::PERMANENT);
    $this->assertTrue($isPermanent);
  }

  public function testisPermanentReturnFalseForOtherStatusOverrideType(): void {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isPermanent = $statusOverrideTypes::isPermanent(CRM_Member_StatusOverrideTypes::NO);
    $this->assertFalse($isPermanent);

    $isPermanent = $statusOverrideTypes::isPermanent(CRM_Member_StatusOverrideTypes::UNTIL_DATE);
    $this->assertFalse($isPermanent);
  }

  public function testisUntilDateReturnTrueForUntilDateStatusOverrideType(): void {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isUntilDate = $statusOverrideTypes::isUntilDate(CRM_Member_StatusOverrideTypes::UNTIL_DATE);
    $this->assertTrue($isUntilDate);
  }

  public function testisUntilDateReturnFalseForOtherStatusOverrideType(): void {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isUntilDate = $statusOverrideTypes::isUntilDate(CRM_Member_StatusOverrideTypes::NO);
    $this->assertFalse($isUntilDate);

    $isUntilDate = $statusOverrideTypes::isUntilDate(CRM_Member_StatusOverrideTypes::PERMANENT);
    $this->assertFalse($isUntilDate);
  }

}
