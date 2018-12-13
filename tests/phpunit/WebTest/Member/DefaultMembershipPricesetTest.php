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
 * Class WebTest_Member_DefaultMembershipPricesetTest
 */
class WebTest_Member_DefaultMembershipPricesetTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testDefaultPricesetSelection() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    $title = substr(sha1(rand()), 0, 7);
    $setTitle = "Membership Fees - $title";
    $usedFor = 'Membership';
    $contributionType = 'Member Dues';
    $setHelp = 'Select your membership options.';
    $this->_testAddSet($setTitle, $usedFor, $contributionType, $setHelp);

    // Get the price set id ($sid) by retrieving and parsing the URL of the New Price Field form
    // which is where we are after adding Price Set.
    $sid = $this->urlArg('sid');
    $this->assertType('numeric', $sid);

    $fields = array("National Membership $title" => 'Radio');
    list($memTypeTitle1, $memTypeTitle2, $memTypeTitle3) = $this->_testAddPriceFields($fields, $validateStrings, FALSE, $title, $sid, TRUE, $contributionType);

    $fields = array("Second Membership $title" => 'CheckBox');
    list($memTypeTitle1, $memTypeTitle2, $memTypeTitle3) = $this->_testAddPriceFields($fields, $validateStrings, FALSE, $title, $sid, FALSE, $contributionType);

    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    $pageTitle = 'Contribution page for membership ' . $hash;
    $processorName = 'Dummy ' . $hash;
    $memPriceSetId = $sid;
    $membershipContributionPageId = $this->webtestAddContributionPage($hash, $rand, $pageTitle, array($processorName => 'Dummy'), TRUE, TRUE, FALSE, FALSE, FALSE, FALSE, $memPriceSetId, FALSE, NULL, NULL, TRUE, FALSE, FALSE, TRUE, FALSE, FALSE, TRUE);

    $firstName = 'John_' . substr(sha1(rand()), 0, 7);
    $lastName = 'Anderson_' . substr(sha1(rand()), 0, 7);
    $email = "{$firstName}.{$lastName}@example.com";
    $contactParams = array(
      'first_name' => $firstName,
      'last_name' => $lastName,
      'email-5' => $email,
    );
    $streetAddress = "100 Main Street";

    //adding contact for membership sign up
    $this->webtestAddContact($firstName, $lastName, $email);
    $urlElements = $this->parseURL();
    $cid = $urlElements['queryString']['cid'];
    $this->assertType('numeric', $cid);

    //senario 1
    $this->openCiviPage("contribute/transact", "reset=1&id={$membershipContributionPageId}&cid={$cid}&action=preview", "_qf_Main_upload-bottom");

    $this->_testDefaultSenarios("national_membership_{$title}-section", 2);
    $this->contactInfoFill($firstName, $lastName, $email, $contactParams, $streetAddress);
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //senario 2
    $this->openCiviPage("contribute/transact", "reset=1&id={$membershipContributionPageId}&cid={$cid}&action=preview", "_qf_Main_upload-bottom");
    // checking
    $this->checkOptions("national_membership_{$title}-section", 2);
    // senario 1
    $this->_testDefaultSenarios("national_membership_{$title}-section", 4);
    $this->_testDefaultSenarios("second_membership_{$title}-section", 2);
    $this->contactInfoFill($firstName, $lastName, $email, $contactParams, $streetAddress);
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //senario 3
    $this->openCiviPage("contribute/transact", "reset=1&id={$membershipContributionPageId}&cid={$cid}&action=preview", "_qf_Main_upload-bottom");
    // checking
    $this->checkOptions("second_membership_{$title}-section", 2);
    // senario 2

    $this->_testDefaultSenarios("national_membership_{$title}-section", 3);
    $this->contactInfoFill($firstName, $lastName, $email, $contactParams, $streetAddress);
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //senario 4
    $this->openCiviPage("contribute/transact", "reset=1&id={$membershipContributionPageId}&cid={$cid}&action=preview", "_qf_Main_upload-bottom");
    // checking senario 3
    $this->assertTrue($this->isTextPresent("You have a current Lifetime Membership which does not need to be renewed."));

    $this->_testDefaultSenarios("national_membership_{$title}-section", 1);
    $this->contactInfoFill($firstName, $lastName, $email, $contactParams, $streetAddress);
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Main_upload-bottom");
    $this->assertTrue($this->isTextPresent("You already have a lifetime membership and cannot select a membership with a shorter term."));
  }

  /**
   * @param string $firstName
   * @param string $lastName
   * @param $email
   * @param array $contactParams
   * @param $streetAddress
   */
  public function contactInfoFill($firstName, $lastName, $email, $contactParams, $streetAddress) {
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
  }

  /**
   * @param $priceSetSection
   * @param $optionNumber
   */
  public function checkOptions($priceSetSection, $optionNumber) {
    $this->assertChecked("xpath=//div[@id='priceset']/div[@class='crm-section {$priceSetSection}']/div[2]/div[{$optionNumber}]/span/input");
  }

  /**
   * @param $priceSetSection
   * @param $optionNumber
   */
  public function _testDefaultSenarios($priceSetSection, $optionNumber) {
    $this->click("xpath=//div[@id='priceset']/div[@class='crm-section {$priceSetSection}']/div[2]/div[{$optionNumber}]/span/input");
  }

  /**
   * @param $setTitle
   * @param $usedFor
   * @param null $contributionType
   * @param $setHelp
   */
  public function _testAddSet($setTitle, $usedFor, $contributionType = NULL, $setHelp) {
    $this->openCiviPage("admin/price", "reset=1&action=add", '_qf_Set_next-bottom');

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
    $this->clickLink('_qf_Set_next-bottom');
    $this->assertTrue($this->isTextPresent("Your Set '{$setTitle}' has been added. You can add fields to this set now."));
  }

  /**
   * @param $fields
   * @param $validateString
   * @param bool $dateSpecificFields
   * @param $title
   * @param int $sid
   * @param bool $defaultPriceSet
   * @param $contributionType
   *
   * @return array
   */
  public function _testAddPriceFields(&$fields, &$validateString, $dateSpecificFields = FALSE, $title, $sid, $defaultPriceSet = FALSE, $contributionType) {
    if ($defaultPriceSet) {

      $memTypeTitle1 = 'General';
      $memTypeId1 = 1;

      $memTypeTitle2 = 'Student';
      $memTypeId2 = 2;

      $memTypeTitle3 = 'Lifetime';
      $memTypeId3 = 3;
    }
    elseif (!$defaultPriceSet) {
      $memTypeParams1 = $this->webtestAddMembershipType();
      $memTypeTitle1 = $memTypeParams1['membership_type'];
      $memTypeId1 = explode('&id=', $this->getAttribute("xpath=//div[@id='membership_type']/table/tbody//tr/td/div[text()='{$memTypeTitle1}']/../../td[12]/span/a[3]@href"));
      $memTypeId1 = $memTypeId1[1];

      $memTypeParams2 = $this->webtestAddMembershipType();
      $memTypeTitle2 = $memTypeParams2['membership_type'];
      $memTypeId2 = explode('&id=', $this->getAttribute("xpath=//div[@id='membership_type']/table/tbody//tr/td/div[text()='{$memTypeTitle2}']/../../td[12]/span/a[3]@href"));
      $memTypeId2 = $memTypeId2[1];

      $memTypeParams3 = $this->webtestAddMembershipType();
      $memTypeTitle3 = $memTypeParams3['membership_type'];
      $memTypeId3 = explode('&id=', $this->getAttribute("xpath=//div[@id='membership_type']/table/tbody//tr/td/div[text()='{$memTypeTitle3}']/../../td[12]/span/a[3]@href"));
      $memTypeId3 = $memTypeId3[1];
    }

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
              'amount' => '100.00',
            ),
            2 => array(
              'label' => "$memTypeTitle2",
              'membership_type_id' => $memTypeId2,
              'amount' => '50.00',
            ),
            3 => array(
              'label' => "$memTypeTitle3",
              'membership_type_id' => $memTypeId3,
              'amount' => '1,200.00',
            ),
          );
          $this->addMultipleChoiceOptions($options, $validateStrings);
          break;

        case 'CheckBox':
          $options = array(
            1 => array(
              'label' => "$memTypeTitle1",
              'membership_type_id' => $memTypeId1,
              'amount' => '100.00',
            ),
            2 => array(
              'label' => "$memTypeTitle2",
              'membership_type_id' => $memTypeId2,
              'amount' => '50.00',
            ),
            3 => array(
              'label' => "$memTypeTitle3",
              'membership_type_id' => $memTypeId3,
              'amount' => '1,200.00',
            ),
          );
          $this->addMultipleChoiceOptions($options, $validateStrings);
          break;

        default:
          break;
      }
      $this->select("financial_type_id", "label={$contributionType}");
      $this->clickLink('_qf_Field_next_new-bottom', '_qf_Field_next-bottom');
      $this->assertTrue($this->isTextPresent("Price Field '{$label}' has been saved."));
    }
    return array($memTypeTitle1, $memTypeTitle2, $memTypeTitle3);
  }

}
