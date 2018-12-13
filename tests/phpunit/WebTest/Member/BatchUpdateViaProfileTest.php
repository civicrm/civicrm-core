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
 * Class WebTest_Member_BatchUpdateViaProfileTest
 */
class WebTest_Member_BatchUpdateViaProfileTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testMemberAdd() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Create a membership type to use for this test (defaults for this helper function are rolling 1 year membership)
    $memTypeParams = $this->webtestAddMembershipType();

    $endDate = date('F jS, Y', strtotime("+1 year +1 month -1 day"));

    // Add new individual using Quick Add block on the main page
    $firstName1 = "John_" . substr(sha1(rand()), 0, 7);
    $lastName = "Smith_" . substr(sha1(rand()), 0, 7);
    $Name1 = $lastName . ', ' . $firstName1;
    $this->webtestAddContact($firstName1, $lastName, "$firstName1.$lastName@example.com");

    // Add membership for this individual
    $this->_addMembership($memTypeParams);
    // Is status message correct?
    $this->waitForText('crm-notification-container', "membership for $firstName1 $lastName has been added");

    // click through to the membership view screen
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    // Verify End date
    $verifyData = array(
      'Membership Type' => $memTypeParams['membership_type'],
      'Status' => 'New',
      'End date' => $endDate,
    );
    $this->webtestVerifyTabularData($verifyData);

    // Add new individual using Quick Add block on the main page
    $firstName2 = "John_" . substr(sha1(rand()), 0, 7);
    $Name2 = $lastName . ', ' . $firstName2;
    $this->webtestAddContact($firstName2, $lastName, "$firstName2.$lastName@example.com");

    // Add membership for this individual
    $this->_addMembership($memTypeParams);
    // Is status message correct?

    $this->waitForText('crm-notification-container', "membership for $firstName2 $lastName has been added.");

    // click through to the membership view screen
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    // Verify End date
    $verifyData = array(
      'Membership Type' => $memTypeParams['membership_type'],
      'Status' => 'New',
      'End date' => $endDate,
    );
    $this->webtestVerifyTabularData($verifyData);

    $profileTitle = 'Profile_' . substr(sha1(rand()), 0, 4);
    $customDataParams = $this->_addCustomData();
    $this->_addProfile($profileTitle, $customDataParams);

    // Find Members
    $this->openCiviPage("member/search", "reset=1", '_qf_Search_refresh');

    $this->type('sort_name', $lastName);
    $this->click('_qf_Search_refresh');

    // Update multiple memberships
    $this->waitForElementPresent("xpath=//div[@id='search-status']/table/tbody/tr[2]/td[2]/input");
    $this->click("xpath=//div[@id='search-status']/table/tbody/tr[2]/td[2]/input");
    //$this->click('CIVICRM_QFID_ts_all_10');
    $this->select('task', "label=Update multiple memberships");
    $this->waitForElementPresent('_qf_PickProfile_back-bottom');
    $this->waitForElementPresent('uf_group_id');
    $this->select('uf_group_id', "label={$profileTitle}");
    $this->click('_qf_PickProfile_next-bottom');

    $this->waitForElementPresent('_qf_Batch_back-bottom');
    $this->type("xpath=//form[@id='Batch']/div[2]/table/tbody//tr/td[text()='{$Name1}']/../td[3]/input", "This is test custom data text1");
    $this->select("xpath=//form[@id='Batch']/div[2]/table/tbody//tr/td[text()='{$Name1}']/../td[4]/select", "label=Current");

    $this->type("xpath=//form[@id='Batch']/div[2]/table/tbody//tr/td[text()='{$Name2}']/../td[3]/input", "This is test custom data text2");
    $this->select("xpath=//form[@id='Batch']/div[2]/table/tbody//tr/td[text()='{$Name2}']/../td[4]/select", "label=Grace");

    $this->click('_qf_Batch_next-bottom');
    $this->waitForElementPresent('_qf_Result_done');
    $this->click('_qf_Result_done');

    // View Membership
    $this->waitForElementPresent("xpath=//div[@id='memberSearch']/table/tbody//tr/td[3]/a[text()='{$Name1}']/../../td[11]/span[1]/a[1][text()='View']");
    $this->click("xpath=//div[@id='memberSearch']/table/tbody//tr/td[3]/a[text()='{$Name1}']/../../td[11]/span[1]/a[1][text()='View']");
    $this->waitForElementPresent('_qf_MembershipView_cancel-bottom');

    // Verify End date
    $verifyData = array(
      'Membership Type' => $memTypeParams['membership_type'],
      'Status' => 'Current',
      'End date' => $endDate,
    );
    $this->webtestVerifyTabularData($verifyData);

    $this->click('_qf_MembershipView_cancel-bottom');

    // View Membership
    $this->click("xpath=//div[@id='memberSearch']/table/tbody//tr/td[3]/a[text()='{$Name2}']/../../td[11]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_MembershipView_cancel-bottom');

    // Verify End date
    $verifyData = array(
      'Membership Type' => $memTypeParams['membership_type'],
      'Status' => 'Grace',
      'End date' => $endDate,
    );
    $this->webtestVerifyTabularData($verifyData);
  }

  /**
   * @param array $memTypeParams
   */
  public function _addMembership($memTypeParams) {
    // click through to the membership view screen
    $this->click("css=li#tab_member a");
    $this->waitForElementPresent("link=Add Membership");
    $this->click("link=Add Membership");
    $this->waitForElementPresent("_qf_Membership_cancel-bottom");

    // fill in Membership Organization and Type
    $this->select("membership_type_id[0]", "label={$memTypeParams['member_of_contact']}");
    // Wait for membership type select to reload
    $this->waitForTextPresent($memTypeParams['membership_type']);
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(3);
    $this->select("membership_type_id[1]", "label={$memTypeParams['membership_type']}");

    $sourceText = "Membership ContactAddTest Webtest";
    // fill in Source
    $this->type("source", $sourceText);

    // Let Join Date stay default

    // fill in Start Date
    $this->webtestFillDate('start_date');

    // Clicking save.
    $this->click("_qf_Membership_upload");

    // page was loaded
    $this->waitForTextPresent($sourceText);
  }

  /**
   * @param $profileTitle
   * @param array $customDataParams
   */
  public function _addProfile($profileTitle, $customDataParams) {

    $this->openCiviPage("admin/uf/group", "reset=1");

    $this->click('link=Add Profile');
    // Add membership custom data field to profile
    $this->waitForElementPresent('_qf_Group_cancel-bottom');
    $this->type('title', $profileTitle);
    $this->click('uf_group_type_Profile');
    $this->clickLink('_qf_Group_next-bottom');

    $this->waitForText('crm-notification-container', "Your CiviCRM Profile '{$profileTitle}' has been added. You can add fields to this profile now.");
    $gid = $this->urlArg('gid');

    $this->openCiviPage('admin/uf/group/field/add', array(
        'action' => 'add',
        'reset' => 1,
        'gid' => $gid,
      ), 'field_name[0]');

    $this->select('field_name[0]', "value=Membership");
    $this->select('field_name[1]', "label={$customDataParams[0]} :: {$customDataParams[1]}");
    $this->click('field_name[1]');
    $this->click('label');

    // Clicking save and new
    $this->click('_qf_Field_next_new-bottom');
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile Field '{$customDataParams[0]}' has been saved to '{$profileTitle}'.");

    // Add membership status field to profile - CRM-8618
    $this->select('field_name[0]', "value=Membership");
    $this->select('field_name[1]', "label=Membership Status");
    $this->click('field_name[1]');
    $this->click('label');
    // Clicking save
    $this->click('_qf_Field_next-bottom');
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile Field 'Membership Status' has been saved to '{$profileTitle}'.");
  }

  /**
   * @return array
   */
  public function _addCustomData() {
    $customGroupTitle = 'Custom_' . substr(sha1(rand()), 0, 4);

    $this->openCiviPage('admin/custom/group', 'reset=1');

    //add new custom data
    $this->click("//a[@id='newCustomDataGroup']/span");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //fill custom group title
    $this->click("title");
    $this->type("title", $customGroupTitle);

    //custom group extends
    $this->click("extends[0]");
    $this->select("extends[0]", "value=Membership");
    $this->click("//option[@value='Membership']");
    $this->click('_qf_Group_next-bottom');
    $this->waitForElementPresent('_qf_Field_cancel-bottom');

    //Is custom group created?
    $this->waitForText('crm-notification-container', "Your custom field set '{$customGroupTitle}' has been added. You can add custom fields now.");

    $textFieldLabel = 'Custom Field Text_' . substr(sha1(rand()), 0, 4);
    $this->type('label', $textFieldLabel);

    //enter pre help msg
    $this->type('help_pre', "this is field pre help");

    //enter post help msg
    $this->type('help_post', "this is field post help");

    //Is searchable?
    $this->click('is_searchable');

    //clicking save
    $this->click('_qf_Field_done-bottom');

    //Is custom field created
    $this->waitForText('crm-notification-container', "Custom field '$textFieldLabel' has been saved.");

    return array($textFieldLabel, $customGroupTitle);
  }

}
