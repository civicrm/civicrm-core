<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Member_BAO_MembershipTest
 * @group headless
 */
class CRM_Member_StatusOverrideTypesTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function testIsOverriddenReturnFalseForNoStatusOverrideType() {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isOverridden = $statusOverrideTypes::isOverridden(CRM_Member_StatusOverrideTypes::NO);
    $this->assertFalse($isOverridden);
  }

  public function testIsOverriddenReturnTrueForPermanentStatusOverrideType() {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isOverridden = $statusOverrideTypes::isOverridden(CRM_Member_StatusOverrideTypes::PERMANENT);
    $this->assertTrue($isOverridden);
  }

  public function testIsOverriddenReturnTrueForUntilDateStatusOverrideType() {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isOverridden = $statusOverrideTypes::isOverridden(CRM_Member_StatusOverrideTypes::UNTIL_DATE);
    $this->assertTrue($isOverridden);
  }

  public function testIsNoReturnTrueForNoStatusOverrideType() {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isNo = $statusOverrideTypes::isNo(CRM_Member_StatusOverrideTypes::NO);
    $this->assertTrue($isNo);
  }

  public function testIsNoReturnFalseForOtherStatusOverrideType() {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isNo = $statusOverrideTypes::isNo(CRM_Member_StatusOverrideTypes::PERMANENT);
    $this->assertFalse($isNo);

    $isNo = $statusOverrideTypes::isNo(CRM_Member_StatusOverrideTypes::UNTIL_DATE);
    $this->assertFalse($isNo);
  }

  public function testisPermanentReturnTrueForPermanentStatusOverrideType() {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isPermanent = $statusOverrideTypes::isPermanent(CRM_Member_StatusOverrideTypes::PERMANENT);
    $this->assertTrue($isPermanent);
  }

  public function testisPermanentReturnFalseForOtherStatusOverrideType() {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isPermanent = $statusOverrideTypes::isPermanent(CRM_Member_StatusOverrideTypes::NO);
    $this->assertFalse($isPermanent);

    $isPermanent = $statusOverrideTypes::isPermanent(CRM_Member_StatusOverrideTypes::UNTIL_DATE);
    $this->assertFalse($isPermanent);
  }

  public function testisUntilDateReturnTrueForUntilDateStatusOverrideType() {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isUntilDate = $statusOverrideTypes::isUntilDate(CRM_Member_StatusOverrideTypes::UNTIL_DATE);
    $this->assertTrue($isUntilDate);
  }

  public function testisUntilDateReturnFalseForOtherStatusOverrideType() {
    $statusOverrideTypes = new CRM_Member_StatusOverrideTypes();
    $isUntilDate = $statusOverrideTypes::isUntilDate(CRM_Member_StatusOverrideTypes::NO);
    $this->assertFalse($isUntilDate);

    $isUntilDate = $statusOverrideTypes::isUntilDate(CRM_Member_StatusOverrideTypes::PERMANENT);
    $this->assertFalse($isUntilDate);
  }

}
