<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Class WebTest_Member_OfflineMembershipAddPricesetTest
 */
class WebTest_Member_OfflineMembershipAddPricesetTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddPriceSet() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    $title = substr(sha1(rand()), 0, 7);
    $setTitle = "Membership Fees - $title";
    $usedFor = 'Membership';
    $contributionType = 'Donation';
    $setHelp = 'Select your membership options.';
    $this->_testAddSet($setTitle, $usedFor, $contributionType, $setHelp);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $sid = $this->urlArg('sid');
    $this->assertType('numeric', $sid);

    $fields = array(
      "National Membership $title" => 'Radio',
      "Local Chapter $title" => 'CheckBox',
    );

    list($memTypeTitle1, $memTypeTitle2) = $this->_testAddPriceFields($fields, $validateStrings, FALSE, $title, $sid, $contributionType);

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);

    // Sign up for membership
    $firstName = 'John_' . substr(sha1(rand()), 0, 7);
    $lastName = 'Anderson_' . substr(sha1(rand()), 0, 7);
    $email = "{$firstName}.{$lastName}@example.com";
    $contactParams = array(
      'first_name' => $firstName,
      'last_name' => $lastName,
      'email-5' => $email,
    );

    // Add a contact from the quick add block
    $this->webtestAddContact($firstName, $lastName, $email);

    $this->_testSignUpOrRenewMembership($sid, $contactParams, $memTypeTitle1, $memTypeTitle2);

    // Renew this membership
    $this->_testSignUpOrRenewMembership($sid, $contactParams, $memTypeTitle1, $memTypeTitle2, $renew = TRUE);
  }

  public function testAddPriceSetWithMultipleTerms() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    $title = substr(sha1(rand()), 0, 7);
    $setTitle = "Membership Fees - $title";
    $usedFor = 'Membership';
    $contributionType = 'Member Dues';
    $setHelp = 'Select your membership options.';
    $memTypeParams1 = $this->webtestAddMembershipType();
    $memTypeTitle1 = $memTypeParams1['membership_type'];
    $memTypeId1 = explode('&id=', $this->getAttribute("xpath=//div[@id='membership_type']/table/tbody//tr/td[1]/div[text()='{$memTypeTitle1}']/../../td[12]/span/a[3]@href"));
    $memTypeId1 = $memTypeId1[1];
    $this->_testAddSet($setTitle, $usedFor, $contributionType, $setHelp);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $sid = $this->urlArg('sid');
    $this->assertType('numeric', $sid);

    $fields = array("National Membership $title", "Radio");
    $this->openCiviPage("admin/price/field", "reset=1&action=add&sid={$sid}");

    $validateStrings[] = $fields[0];
    $this->type('label', $fields[0]);
    $this->select('html_type', "value={$fields[1]}");
    $options = array(
      1 => array(
        'label' => $memTypeTitle1 . "_1",
        'membership_type_id' => $memTypeId1,
        'amount' => 50.00,
        'membership_num_terms' => 1,
      ),
      2 => array(
        'label' => $memTypeTitle1 . "_2",
        'membership_type_id' => $memTypeId1,
        'amount' => 90.00,
        'membership_num_terms' => 2,
      ),
      3 => array(
        'label' => $memTypeTitle1 . "_3",
        'membership_type_id' => $memTypeId1,
        'amount' => 120.00,
        'membership_num_terms' => 3,
      ),
    );
    $i = 2;
    foreach ($options as $index => $values) {
      $this->select("membership_type_id_{$index}", "value={$values['membership_type_id']}");
      // Because it tends to cause problems, all uses of sleep() must be justified in comments
      // Sleep should never be used for wait for anything to load from the server
      // Justification for this instance: FIXME
      sleep(1);
      $this->type("xpath=//table[@id='optionField']/tbody/tr[$i]/td[4]/input", $values['membership_num_terms']);
      $this->type("xpath=//table[@id='optionField']/tbody/tr[$i]/td[5]/input", $values['label']);
      $this->type("xpath=//table[@id='optionField']/tbody/tr[$i]/td[6]/input", $values['amount']);
      if ($i > 3) {
        $this->click('link=another choice');
      }
      $i++;
    }
    $this->waitForElementPresent('financial_type_id');
    $this->select("financial_type_id", "label={$contributionType}");
    $this->waitForElementPresent('_qf_Field_next-bottom');
    $this->click('_qf_Field_next-bottom');
    $this->waitForText('crm-notification-container', "Price Field '{$fields[0]}' has been saved.");

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);

    $firstName = 'John_' . substr(sha1(rand()), 0, 7);
    $lastName = 'Anderson_' . substr(sha1(rand()), 0, 7);
    $email = "{$firstName}.{$lastName}@example.com";

    $contactParams = array(
      'first_name' => $firstName,
      'last_name' => $lastName,
      'email-5' => $email,
    );
    $this->webtestAddContact($firstName, $lastName, $email);
    //membership with number of terms as 3
    $this->_testMultilpeTermsMembershipRegistration($sid, $contactParams, $memTypeTitle1, 3);
    //membership with number of terms as 2
    $this->_testMultilpeTermsMembershipRegistration($sid, $contactParams, $memTypeTitle1, 2);

  }

  /**
   * @param $setTitle
   * @param $usedFor
   * @param null $contributionType
   * @param $setHelp
   */
  public function _testAddSet($setTitle, $usedFor, $contributionType = NULL, $setHelp) {
    $this->openCiviPage('admin/price', 'reset=1&action=add', '_qf_Set_next-bottom');

    // Enter Priceset fields (Title, Used For ...)
    $this->type('title', $setTitle);
    if ($usedFor == 'Event') {
      $this->check('extends_1');
    }
    elseif ($usedFor == 'Contribution') {
      $this->check('extends_2');
    }
    elseif ($usedFor == 'Membership') {
      $this->click('extends_3');
      $this->waitForElementPresent('financial_type_id');
      $this->select("css=select.crm-form-select", "label={$contributionType}");
    }
    $this->type('help_pre', $setHelp);
    $this->assertChecked('is_active', 'Verify that Is Active checkbox is set.');

    $this->click('_qf_Set_next-bottom');
    $this->waitForElementPresent("xpath=//div[@id='crm-main-content-wrapper']//div[2]/a");
    $this->waitForText('crm-notification-container', "Your Set '{$setTitle}' has been added. You can add fields to this set now.");
  }

  /**
   * @param $fields
   * @param $validateString
   * @param bool $dateSpecificFields
   * @param $title
   * @param int $sid
   * @param $contributionType
   *
   * @return array
   */
  public function _testAddPriceFields(&$fields, &$validateString, $dateSpecificFields = FALSE, $title, $sid, $contributionType) {
    $memTypeParams1 = $this->webtestAddMembershipType();
    $memTypeTitle1 = $memTypeParams1['membership_type'];

    $memTypeId1 = explode('&id=', $this->getAttribute("xpath=//div[@id='membership_type']/table/tbody//tr/td[1]/div[text()='{$memTypeTitle1}']/../../td[12]/span/a[3]@href"));
    $memTypeId1 = $memTypeId1[1];

    $memTypeParams2 = $this->webtestAddMembershipType();
    $memTypeTitle2 = $memTypeParams2['membership_type'];
    $memTypeId2 = explode('&id=', $this->getAttribute("xpath=//div[@id='membership_type']/table/tbody//tr/td[1]/div[text()='{$memTypeTitle2}']/../../td[12]/span/a[3]@href"));
    $memTypeId2 = $memTypeId2[1];

    $this->openCiviPage("admin/price/field", "reset=1&action=add&sid={$sid}");

    foreach ($fields as $label => $type) {
      $validateStrings[] = $label;

      $this->type('label', $label);
      $this->select('html_type', "value={$type}");

      switch ($type) {
        case 'Radio':
          $options = array(
            1 => array(
              'label' => "$memTypeTitle1",
              'membership_type_id' => $memTypeId1,
              'amount' => 100.00,
            ),
            2 => array(
              'label' => "$memTypeTitle2",
              'membership_type_id' => $memTypeId2,
              'amount' => 50.00,
            ),
          );
          $this->addMultipleChoiceOptions($options, $validateStrings);
          break;

        case 'CheckBox':
          $options = array(
            1 => array(
              'label' => "$memTypeTitle1",
              'membership_type_id' => $memTypeId1,
              'amount' => 100.00,
            ),
            2 => array(
              'label' => "$memTypeTitle2",
              'membership_type_id' => $memTypeId2,
              'amount' => 50.00,
            ),
          );
          $this->addMultipleChoiceOptions($options, $validateStrings);
          break;

        default:
          break;
      }
      $this->select("financial_type_id", "label={$contributionType}");
      $this->click('_qf_Field_next_new-bottom');
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->waitForText('crm-notification-container', "Price Field '{$label}' has been saved.");
    }
    return array($memTypeTitle1, $memTypeTitle2);
  }

  /**
   * @param $validateStrings
   * @param int $sid
   */
  public function _testVerifyPriceSet($validateStrings, $sid) {
    // verify Price Set at Preview page
    // start at Manage Price Sets listing
    $this->openCiviPage('admin/price', 'reset=1');

    // Use the price set id ($sid) to pick the correct row
    $this->click("css=tr#price_set-{$sid} a[title='Preview Price Set']");

    // Look for Register button
    $this->waitForElementPresent('_qf_Preview_cancel-bottom');

    // Check for expected price set field strings
    $this->assertStringsPresent($validateStrings);
  }

  /**
   * @param int $sid
   * @param array $contactParams
   * @param $memTypeTitle1
   * @param $memTypeTitle2
   * @param bool $renew
   */
  public function _testSignUpOrRenewMembership($sid, $contactParams, $memTypeTitle1, $memTypeTitle2, $renew = FALSE) {
    //build the membership dates.
    require_once 'CRM/Core/Config.php';
    require_once 'CRM/Utils/Array.php';
    require_once 'CRM/Utils/Date.php';
    $currentYear = date('Y');
    $currentMonth = date('m');
    $previousDay = date('d') - 1;
    $endYear = ($renew) ? $currentYear + 2 : $currentYear + 1;
    $joinDate = date('Y-m-d', mktime(0, 0, 0, $currentMonth, date('d'), $currentYear));
    $startDate = date('Y-m-d', mktime(0, 0, 0, $currentMonth, date('d'), $currentYear));
    $endDate = date('Y-m-d', mktime(0, 0, 0, $currentMonth, $previousDay, $endYear));
    foreach (array(
               'joinDate',
               'startDate',
               'endDate',
             ) as $date) {
      $$date = CRM_Utils_Date::customFormat($$date, $this->webtestGetSetting('dateformatFull'));
    }

    if (!$renew) {

      $this->click('css=li#tab_member a');
      $this->waitForElementPresent('link=Add Membership');
      if ($this->isTextPresent("No memberships have been recorded for this contact.")) {
        $this->waitForTextPresent('No memberships have been recorded for this contact.');
      }

      $this->clickAjaxLink('link=Add Membership');
      $this->waitForElementPresent('_qf_Membership_cancel-bottom');

      $this->select('price_set_id', "value={$sid}");
      $this->waitForElementPresent('pricesetTotal');

      $this->click("xpath=//div[@id='priceset']/div[2]/div[2]/div/span/input");
      $this->click("xpath=//div[@id='priceset']/div[3]/div[2]/div[2]/span/input");

      $this->type('source', 'Offline membership Sign Up Test Text');
      $this->click('_qf_Membership_upload-bottom');
    }
    else {
      $this->click("xpath=//div[@id='memberships']/div//table/tbody//tr/td[text()='{$memTypeTitle1}']/../td[9]/span[2][text()='Renew...']/ul/li/a[text()='Renew']");
      $this->waitForElementPresent('_qf_MembershipRenewal_cancel-bottom');
      $this->waitForAjaxContent();
      $this->click('_qf_MembershipRenewal_upload-bottom');

      $this->waitForElementPresent("xpath=//div[@id='memberships']//table/tbody/tr");
      $this->click("xpath=//div[@id='memberships']/div//table/tbody//tr/td[text()='{$memTypeTitle2}']/../td[9]/span[2][text()='Renew...']/ul/li/a[text()='Renew']");
      $this->waitForElementPresent('_qf_MembershipRenewal_cancel-bottom');
      $this->waitForAjaxContent();
      $this->click('_qf_MembershipRenewal_upload-bottom');
    }
    $this->waitForElementPresent("xpath=//div[@id='memberships']//table/tbody//tr/td[text()='{$memTypeTitle1}']/../td[9]/span/a[text()='View']");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[@id='memberships']//table/tbody//tr/td[text()='{$memTypeTitle1}']/../td[9]/span/a[text()='View']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button[3]");

    //View Membership Record
    $verifyData = array(
      'Membership Type' => "{$memTypeTitle1}",
      'Status' => 'New',
      'Member Since' => $joinDate,
      'Start date' => $startDate,
      'End date' => $endDate,
    );
    $this->webtestVerifyTabularData($verifyData);

    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button[3]/span[2]");
    $this->waitForElementPresent("xpath=//div[@id='memberships']//table/tbody//tr/td[text()='{$memTypeTitle2}']/../td[9]/span/a[text()='View']");
    $this->click("xpath=//div[@id='memberships']//table/tbody//tr/td[text()='{$memTypeTitle2}']/../td[9]/span/a[text()='View']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button[3]");

    //View Membership Record
    $verifyData = array(
      'Membership Type' => "{$memTypeTitle2}",
      'Status' => 'New',
      'Member Since' => $joinDate,
      'Start date' => $startDate,
      'End date' => $endDate,
    );
    $this->webtestVerifyTabularData($verifyData);

    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button[3]/span[2]");
    $this->waitForElementPresent("xpath=//div[@id='memberships']//table/tbody/tr");
  }

  /**
   * @param int $sid
   * @param array $contactParams
   * @param $memTypeTitle1
   * @param $term
   */
  public function _testMultilpeTermsMembershipRegistration($sid, $contactParams, $memTypeTitle1, $term) {
    //build the membership dates.
    require_once 'CRM/Core/Config.php';
    require_once 'CRM/Utils/Array.php';
    require_once 'CRM/Utils/Date.php';
    $currentYear = date('Y');
    $currentMonth = date('m');
    $previousDay = date('d') - 1;
    $endYear = ($term == 3) ? $currentYear + 3 : (($term == 2) ? $currentYear + 2 : $currentYear + 1);
    $joinDate = date('Y-m-d', mktime(0, 0, 0, $currentMonth, date('d'), $currentYear));
    $startDate = date('Y-m-d', mktime(0, 0, 0, $currentMonth, date('d'), $currentYear));
    $endDate = date('Y-m-d', mktime(0, 0, 0, $currentMonth, $previousDay, $endYear));
    foreach (array(
               'joinDate',
               'startDate',
               'endDate',
             ) as $date) {
      $$date = CRM_Utils_Date::customFormat($$date, $this->webtestGetSetting('dateformatFull'));
    }

    $this->click('css=li#tab_member a');
    $this->waitForElementPresent('link=Add Membership');
    if ($this->isTextPresent("No memberships have been recorded for this contact.")) {
      $this->waitForTextPresent('No memberships have been recorded for this contact.');
    }

    $this->clickAjaxLink('link=Add Membership');
    $this->waitForElementPresent('_qf_Membership_cancel-bottom');

    $this->select('price_set_id', "value={$sid}");
    $this->waitForElementPresent('pricesetTotal');

    $i = ($term == 3) ? 3 : (($term == 2) ? 2 : 1);
    $this->waitForElementPresent("xpath=//div[@id='priceset']/div[2]/div[2]/div[$i]/span/input");
    $this->click("xpath=//div[@id='priceset']/div[2]/div[2]/div[$i]/span/input");
    $amount = $this->getText("xpath=//div[@id='priceset']/div[2]/div[2]/div[$i]/span/label/span[@class='crm-price-amount-amount']");

    $this->type('source', 'Offline membership Sign Up Test Text');
    $this->waitForElementPresent('recordContribution');
    $this->click('_qf_Membership_upload-bottom');

    $this->waitForElementPresent("xpath=//table[@class='display dataTable no-footer']/tbody//tr/td[4][text()='{$endDate}']/../td[9]/span[1]/a[1]");
    $this->click("xpath=//table[@class='display dataTable no-footer']/tbody//tr/td[4][text()='{$endDate}']/../td[9]/span[1]/a[1]");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button[3]/span[2]");
    //View Membership Record
    $verifyData = array(
      'Membership Type' => "$memTypeTitle1",
      'Status' => 'New',
      'Member Since' => $joinDate,
      'Start date' => $startDate,
      'End date' => $endDate,
    );
    $this->webtestVerifyTabularData($verifyData);

    //check if the membership amount is correct
    $this->waitForElementPresent("xpath=//form[@id='MembershipView']/div[2]/div/div[@class='crm-accordion-wrapper']/div[2]/table/tbody/tr/td/a");
    $this->assertElementContainsText("xpath=//form[@id='MembershipView']/div[2]/div/div[@class='crm-accordion-wrapper']/div[2]/table/tbody/tr/td/a", $amount);
    $this->click("_qf_MembershipView_cancel-bottom");
  }

}
