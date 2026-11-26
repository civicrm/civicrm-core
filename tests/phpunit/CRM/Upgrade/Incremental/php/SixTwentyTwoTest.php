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
 * Tests for 6.22 upgrade logic.
 *
 * @group headless
 */
class CRM_Upgrade_Incremental_php_SixTwentyTwoTest extends CiviUnitTestCase {

  private const SETTING = 'member_use_civicrm_membership_payment_table';

  protected function setUp(): void {
    parent::setUp();
    Civi::settings()->revert(self::SETTING);
  }

  protected function tearDown(): void {
    Civi::settings()->revert(self::SETTING);
    parent::tearDown();
  }

  public function testKeepMembershipPaymentTableEnabled(): void {
    $this->assertFalse(Civi::settings()->get(self::SETTING));

    CRM_Upgrade_Incremental_php_SixTwentyTwo::keepMembershipPaymentTableEnabled();

    $this->assertTrue(Civi::settings()->hasExplicit(self::SETTING));
    $this->assertTrue(Civi::settings()->get(self::SETTING));
  }

  public function testKeepMembershipPaymentTableEnabledLeavesExplicitSetting(): void {
    Civi::settings()->set(self::SETTING, FALSE);

    CRM_Upgrade_Incremental_php_SixTwentyTwo::keepMembershipPaymentTableEnabled();

    $this->assertFalse(Civi::settings()->get(self::SETTING));
  }

}
