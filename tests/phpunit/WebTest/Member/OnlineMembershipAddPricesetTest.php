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
 * Class WebTest_Member_OnlineMembershipAddPricesetTest
 */
class WebTest_Member_OnlineMembershipAddPricesetTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddPriceSet() {
    // add the required permission
    $permissions = array('edit-1-make-online-contributions');
    $this->changePermissions($permissions);

    // Log in as normal user
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
    //var_dump($validateStrings);

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);

    $contributionPageTitle = "Contribution Page $title";
    $pageId = $this->webtestAddContributionPage(NULL, NULL, $contributionPageTitle, array('Test Processor' => 'Dummy'),
      TRUE, FALSE, FALSE, FALSE, FALSE, TRUE, $sid, FALSE, 1, NULL
    );

    // Sign up for membership
    $firstName = 'John_' . substr(sha1(rand()), 0, 7);
    $lastName = 'Anderson_' . substr(sha1(rand()), 0, 7);
    $email = "{$firstName}.{$lastName}@example.com";

    $contactParams = array(
      'first_name' => $firstName,
      'last_name' => $lastName,
      'email-5' => $email,
    );
    $this->_testSignUpOrRenewMembership($pageId, $contactParams, $memTypeTitle1, $memTypeTitle2);

    // Renew this membership
    $this->_testSignUpOrRenewMembership($pageId, $contactParams, $memTypeTitle1, $memTypeTitle2, $renew = TRUE);
  }

  public function testAddPriceSetWithMultipleTerms() {
    // add the required permission
    $permissions = array('edit-1-make-online-contributions');
    $this->changePermissions($permissions);

    // Log in as normal user
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
    $this->openCiviPage('admin/price/field', "reset=1&action=add&sid={$sid}");

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
      $this->waitForAjaxContent();
      $this->waitForElementPresent("xpath=//table[@id='optionField']/tbody/tr[$i]/td[4]/input");
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
    $this->clickLink('_qf_Field_next-bottom');
    $this->waitForText('crm-notification-container', "Price Field '{$fields[0]}' has been saved.");

    // load the Price Set Preview and check for expected values
    $this->_testVerifyPriceSet($validateStrings, $sid);

    $contributionPageTitle = "Contribution Page $title";
    $pageId = $this->webtestAddContributionPage(NULL, NULL, $contributionPageTitle, array('Test Processor' => 'Dummy'),
      TRUE, FALSE, FALSE, FALSE, FALSE, TRUE, $sid, FALSE, 1, NULL
    );

    // Sign up for membership
    $firstName = 'John_' . substr(sha1(rand()), 0, 7);
    $lastName = 'Anderson_' . substr(sha1(rand()), 0, 7);
    $email = "{$firstName}.{$lastName}@example.com";

    $contactParams = array(
      'first_name' => $firstName,
      'last_name' => $lastName,
      'email-5' => $email,
    );
    //membership with number of terms as 2
    $this->_testMultilpeTermsMembershipRegistration($pageId, $contactParams, $memTypeTitle1, 2);
    //membership with number of terms as 3 which will renew the above membership
    $this->_testMultilpeTermsMembershipRegistration($pageId, $contactParams, $memTypeTitle1, 3, TRUE);

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
      $this->check('extends[1]');
    }
    elseif ($usedFor == 'Contribution') {
      $this->check('extends[2]');
    }
    elseif ($usedFor == 'Membership') {
      $this->click('extends[3]');
      $this->waitForElementPresent('financial_type_id');
      $this->select("css=select.crm-form-select", "label={$contributionType}");
    }

    $this->type('help_pre', $setHelp);

    $this->assertChecked('is_active', 'Verify that Is Active checkbox is set.');
    $this->waitForElementPresent('_qf_Set_next-bottom');
    $this->click('_qf_Set_next-bottom');
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

    $this->openCiviPage('admin/price/field', "reset=1&action=add&sid={$sid}");

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
      $this->clickLink('_qf_Field_next_new-bottom', '_qf_Field_next-bottom');
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
    $this->clickLink("css=tr#price_set-{$sid} a[title='Preview Price Set']", '_qf_Preview_cancel-bottom');

    // Check for expected price set field strings
    $this->assertStringsPresent($validateStrings);
  }

  /**
   * @param int $pageId
   * @param array $contactParams
   * @param $memTypeTitle1
   * @param $memTypeTitle2
   * @param bool $renew
   */
  public function _testSignUpOrRenewMembership($pageId, $contactParams, $memTypeTitle1, $memTypeTitle2, $renew = FALSE) {
    $this->webtestLogout();

    $this->openCiviPage('contribute/transact', "reset=1&id=$pageId", '_qf_Main_upload-bottom');

    //build the membership dates.
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

    $this->click("xpath=//div[@id='priceset']/div[2]/div[2]/div/span/input");
    $this->click("xpath=//div[@id='priceset']/div[3]/div[2]/div[2]/span/input");

    $this->type('email-5', $contactParams['email-5']);
    $this->type('first_name', $contactParams['first_name']);
    $this->type('last_name', $contactParams['last_name']);

    $streetAddress = "100 Main Street";
    $this->type("street_address-1", $streetAddress);
    $this->type("city-1", "San Francisco");
    $this->type("postal_code-1", "94117");
    $this->select("country-1", "value=1228");
    $this->select("state_province-1", "value=1001");

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");

    //Billing Info
    $this->type("billing_first_name", $contactParams['first_name'] . "billing");
    $this->type("billing_last_name", $contactParams['last_name'] . "billing");
    $this->type("billing_street_address-5", "15 Main St.");
    $this->type(" billing_city-5", "San Jose");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //login to check membership

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $this->openCiviPage('member/search', 'reset=1', 'member_end_date_high');

    $this->type("sort_name", "{$contactParams['first_name']} {$contactParams['last_name']}");
    $this->click("_qf_Search_refresh");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-container', '2 Results');

    $this->waitForElementPresent("xpath=//div[@id='memberSearch']/table/tbody/tr");
    $this->click("xpath=//div[@id='memberSearch']/table/tbody//tr/td[4][text()='{$memTypeTitle1}']/../td[11]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    //View Membership Record
    $verifyData = array(
      'Membership Type' => "$memTypeTitle1",
      'Status' => 'New',
      'Member Since' => $joinDate,
      'Start date' => $startDate,
      'End date' => $endDate,
    );
    foreach ($verifyData as $label => $value) {
      $this->verifyText("xpath=//form[@id='MembershipView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }

    $this->clickLink('_qf_MembershipView_cancel-bottom', "xpath=//div[@id='memberSearch']/table/tbody/tr[2]", FALSE);
    $this->click("xpath=//div[@id='memberSearch']/table/tbody//tr/td[4][text()='{$memTypeTitle2}']/../td[11]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    //View Membership Record
    $verifyData = array(
      'Membership Type' => "$memTypeTitle2",
      'Status' => 'New',
      'Member Since' => $joinDate,
      'Start date' => $startDate,
      'End date' => $endDate,
    );
    foreach ($verifyData as $label => $value) {
      $this->verifyText("xpath=//form[@id='MembershipView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }
  }

  /**
   * @param int $pageId
   * @param array $contactParams
   * @param $memTypeTitle1
   * @param $term
   * @param bool $renew
   */
  public function _testMultilpeTermsMembershipRegistration($pageId, $contactParams, $memTypeTitle1, $term, $renew = FALSE) {
    if ($renew) {
      $this->openCiviPage('member/search', 'reset=1', 'member_end_date_high');
      $this->type("sort_name", "{$contactParams['first_name']} {$contactParams['last_name']}");
      $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='memberSearch']/table/tbody/tr");
      $this->click("xpath=//div[@id='memberSearch']/table/tbody//tr/td[4][text()='{$memTypeTitle1}']/../td[11]/span/a[text()='View']");
      $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");
      $year = CRM_Utils_Date::processDate($this->getText("xpath=//form[@id='MembershipView']//table/tbody/tr/td[text()='End date']/following-sibling::td"));
      $prevYear = substr($year, 0, 4);
    }

    $this->webtestLogout();

    $this->openCiviPage('contribute/transact', "reset=1&id=$pageId", '_qf_Main_upload-bottom');

    //build the membership dates.
    $currentYear = date('Y');
    $currentMonth = date('m');
    $previousDay = date('d') - 1;
    $endYear = ($term == 3) ? $currentYear + 3 : (($term == 2) ? $currentYear + 2 : $currentYear + 1);
    $endYear = ($renew) ? $endYear + ($prevYear - $currentYear) : $endYear;

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
    $i = ($term == 3) ? 3 : (($term == 2) ? 2 : 1);
    $this->waitForElementPresent("xpath=//div[@id='priceset']/div[2]/div[2]/div[$i]/span/input");
    $this->click("xpath=//div[@id='priceset']/div[2]/div[2]/div[$i]/span/input");
    $amount = $this->getText("xpath=//div[@id='priceset']/div[2]/div[2]/div[$i]/span/label/span[@class='crm-price-amount-amount']");

    $this->type('email-5', $contactParams['email-5']);
    $this->type('first_name', $contactParams['first_name']);
    $this->type('last_name', $contactParams['last_name']);

    $streetAddress = "100 Main Street";
    $this->type("street_address-1", $streetAddress);
    $this->type("city-1", "San Francisco");
    $this->type("postal_code-1", "94117");
    $this->select("country-1", "value=1228");
    $this->select("state_province-1", "value=1001");

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");

    //Billing Info
    $this->type("billing_first_name", $contactParams['first_name'] . "billing");
    $this->type("billing_last_name", $contactParams['last_name'] . "billing");
    $this->type("billing_street_address-5", "15 Main St.");
    $this->type(" billing_city-5", "San Jose");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //login to check membership

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $this->openCiviPage('member/search', 'reset=1', 'member_end_date_high');

    $this->type("sort_name", "{$contactParams['first_name']} {$contactParams['last_name']}");
    $this->click("_qf_Search_refresh");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-container', '1 Result ');

    $this->waitForElementPresent("xpath=//div[@id='memberSearch']/table/tbody/tr");
    $this->click("xpath=//div[@id='memberSearch']/table/tbody//tr/td[4][text()='{$memTypeTitle1}']/../td[11]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    //View Membership Record
    $verifyData = array(
      'Membership Type' => "$memTypeTitle1",
      'Status' => 'New',
      'Member Since' => $joinDate,
      'Start date' => $startDate,
      'End date' => $endDate,
    );
    foreach ($verifyData as $label => $value) {
      $this->verifyText("xpath=//form[@id='MembershipView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }
    //check if the membership amount is correct
    $this->waitForElementPresent("xpath=//form[@id='MembershipView']/div[2]/div/div[2]/div[2]/table/tbody/tr/td[1]/a");
    $this->assertElementContainsText("xpath=//form[@id='MembershipView']/div[2]/div/div[2]/div[2]/table/tbody/tr/td[1]/a", $amount);
  }

}
