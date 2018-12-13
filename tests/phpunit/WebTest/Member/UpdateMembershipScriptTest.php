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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

require_once 'CiviTest/CiviSeleniumTestCase.php';

/**
 * Class WebTest_Member_UpdateMembershipScriptTest
 */
class WebTest_Member_UpdateMembershipScriptTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddMembership() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Add a new membership type
    $memTypeParams = $this->addMembershipType();

    $firstName = substr(sha1(rand()), 0, 7);
    $email = "$firstName.Anderson@example.com";
    $this->webtestAddContact($firstName, 'Anderson', $email);

    $this->waitForElementPresent('css=li#tab_member a');
    $this->click('css=li#tab_member a');
    $this->waitForElementPresent('link=Add Membership');
    $this->click('link=Add Membership');

    $this->waitForElementPresent('_qf_Membership_cancel-bottom');
    $this->select('membership_type_id[0]', "label={$memTypeParams['member_of_contact']}");
    $this->select('membership_type_id[1]', "label={$memTypeParams['membership_type']}");

    // Fill join date
    $this->webtestFillDate('join_date', "1 March 2008");

    // Override status
    $this->check('is_override');
    $this->select('status_id', "label=Current");

    // Clicking save.
    $this->click('_qf_Membership_upload');

    // Is status message correct?
    $this->waitForText('crm-notification-container', "{$memTypeParams['membership_type']} membership for $firstName Anderson has been added.");

    // click through to the membership view screen
    $this->waitForElementPresent("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]");
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    $this->webtestVerifyTabularData(
      array(
        'Membership Type' => "{$memTypeParams['membership_type']}",
        'Status' => 'Current',
        'Member Since' => 'March 1st, 2008',
        'Start date' => 'March 1st, 2008',
        'End date' => 'February 28th, 2009',
      )
    );
  }

  /**
   * @return array
   */
  public function addMembershipType() {
    $membershipTitle = substr(sha1(rand()), 0, 7);
    $membershipOrg = $membershipTitle . ' memorg';
    $this->webtestAddOrganization($membershipOrg, TRUE);

    $title = "Membership Type " . substr(sha1(rand()), 0, 7);
    $memTypeParams = array(
      'membership_type' => $title,
      'member_of_contact' => $membershipOrg,
      'financial_type' => 2,
      'relationship_type' => '4_b_a',
    );

    $this->openCiviPage('admin/member/membershipType', 'reset=1&action=browse');

    $this->click("link=Add Membership Type");
    $this->waitForElementPresent('_qf_MembershipType_cancel-bottom');

    // New membership type
    $this->type('name', $memTypeParams['membership_type']);
    $this->select2('member_of_contact_id', $membershipTitle);

    // Membership fees
    $this->type('minimum_fee', '100');
    $this->select('financial_type_id', "value={$memTypeParams['financial_type']}");

    // Duration for which the membership will be active
    $this->type('duration_interval', 1);
    $this->select('duration_unit', "label=year");

    // Membership period type
    $this->select('period_type', "value=rolling");
    $this->click('relationship_type_id', "value={$memTypeParams['relationship_type']}");

    // Clicking save
    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');
    $this->waitForText('crm-notification-container', "The membership type '$title' has been saved.");

    return $memTypeParams;
  }

}
