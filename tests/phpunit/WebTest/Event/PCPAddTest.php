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
 * Class WebTest_Event_PCPAddTest
 */
class WebTest_Event_PCPAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testPCPAdd() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    //give permissions to anonymous user
    $permission = array(
      'edit-1-profile-listings-and-forms',
      'edit-1-access-all-custom-data',
      'edit-1-register-for-events',
      'edit-1-make-online-contributions',
    );
    $this->changePermissions($permission);

    // Log in as normal user
    $this->webtestLogin();

    // set domain values
    $domainNameValue = 'civicrm organization ';
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $middleName = 'Mid' . substr(sha1(rand()), 0, 7);
    $email = substr(sha1(rand()), 0, 7) . '@example.org';
    $this->openCiviPage("admin/domain", "action=update&reset=1", '_qf_Domain_cancel-bottom');
    $this->type('name', $domainNameValue);
    $this->type('email_name', $firstName);
    $this->type('email_address', $email);

    $this->click('_qf_Domain_next_view-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // a random 7-char string and an even number to make this pass unique
    $conHash = substr(sha1(rand()), 0, 7);
    $conRand = $contributionAmount = 1000;
    $contributionPageTitle = 'Contribution page for pcp' . $conHash;
    $conProcessorType = 'Dummy';
    $conAmountSection = TRUE;
    $conPayLater = TRUE;
    $conOnBehalf = FALSE;
    $conPledges = FALSE;
    $conRecurring = FALSE;
    $conMemberships = FALSE;
    $conMemPriceSetId = NULL;
    $conFriend = FALSE;
    $conProfilePreId = NULL;
    $conProfilePostId = NULL;
    $conPremiums = FALSE;
    $conWidget = FALSE;
    $conPcp = FALSE;
    $conIsAprovalNeeded = TRUE;

    // Use default payment processor
    $processorName = 'Test Processor';

    //create contribution page for event pcp with campaign type as contribution
    $contributionPageId = $this->webtestAddContributionPage($conHash,
      $conRand,
      $contributionPageTitle,
      array($processorName => $conProcessorType),
      $conAmountSection,
      $conPayLater,
      $conOnBehalf,
      $conPledges,
      $conRecurring,
      $conMemberships,
      $conMemPriceSetId,
      $conFriend,
      $conProfilePreId,
      $conProfilePostId,
      $conPremiums,
      $conWidget,
      $conPcp,
      TRUE,
      $conIsAprovalNeeded
    );

    //event add for contribute campaign type
    $campaignType = 'contribute';
    $this->_testAddEventForPCP($processorName, $campaignType, $contributionPageId, $firstName, $lastName, $middleName, $email);

    //event add for contribute campaign type
    $campaignType = 'event';
    $firstName = 'Pa' . substr(sha1(rand()), 0, 4);
    $lastName = 'Cn' . substr(sha1(rand()), 0, 7);
    $middleName = 'PCid' . substr(sha1(rand()), 0, 7);
    $email = substr(sha1(rand()), 0, 7) . '@example.org';
    $this->_testAddEventForPCP($processorName, $campaignType, NULL, $firstName, $lastName, $middleName, $email);
  }

  /**
   * @param string $processorName
   * @param $campaignType
   * @param int $contributionPageId
   * @param string $firstName
   * @param string $lastName
   * @param string $middleName
   * @param $email
   */
  public function _testAddEventForPCP($processorName, $campaignType, $contributionPageId = NULL, $firstName, $lastName, $middleName, $email) {

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this conference.";
    $this->_testAddEventInfo($eventTitle, $eventDescription);

    $streetAddress = "100 Main Street";
    $this->_testAddLocation($streetAddress);

    $this->_testAddFees(FALSE, FALSE, $processorName);

    // intro text for registration page
    $registerIntro = "Fill in all the fields below and click Continue.";
    $multipleRegistrations = TRUE;
    $this->_testAddOnlineRegistration($registerIntro, $multipleRegistrations);

    $pageId = $this->_testEventPcpAdd($campaignType, $contributionPageId);
    $this->_testOnlineRegistration($eventTitle, $pageId, $firstName, $lastName, $middleName, $email, '', $campaignType, TRUE);
  }

  /**
   * @param $eventTitle
   * @param $eventDescription
   */
  public function _testAddEventInfo($eventTitle, $eventDescription) {
    $this->waitForElementPresent("_qf_EventInfo_upload-bottom");

    $this->select("event_type_id", "value=1");

    // Attendee role s/b selected now.
    $this->select("default_role_id", "value=1");

    // Enter Event Title, Summary and Description
    $this->type("title", $eventTitle);
    $this->type("summary", "This is a great conference. Sign up now!");

    // Type description in ckEditor (fieldname, text to type, editor)
    $this->fillRichTextField("description", $eventDescription, 'CKEditor');

    // Choose Start and End dates.
    // Using helper webtestFillDate function.
    $this->webtestFillDateTime("start_date", "+1 week");
    $this->webtestFillDateTime("end_date", "+1 week 1 day 8 hours ");

    $this->type("max_participants", "50");
    $this->click("is_map");
    $this->click("_qf_EventInfo_upload-bottom");
  }

  /**
   * @param $streetAddress
   */
  public function _testAddLocation($streetAddress) {
    // Wait for Location tab form to load
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Location_upload-bottom");

    // Fill in address fields
    $streetAddress = "100 Main Street";
    $this->type("address_1_street_address", $streetAddress);
    $this->type("address_1_city", "San Francisco");
    $this->type("address_1_postal_code", "94117");
    $this->select("address_1_state_province_id", "value=1004");
    $this->type("email_1_email", "info@civicrm.org");

    $this->click("_qf_Location_upload-bottom");

    // Wait for "saved" status msg
    $this->waitForElementPresent("_qf_Location_upload-bottom");
    $this->waitForTextPresent("'Event Location' information has been saved.");
  }

  /**
   * @param bool $discount
   * @param bool $priceSet
   * @param string $processorName
   */
  public function _testAddFees($discount = FALSE, $priceSet = FALSE, $processorName = "PP Pro") {
    // Go to Fees tab
    $this->click("link=Fees");
    $this->waitForElementPresent("_qf_Fee_upload-bottom");
    $this->click("CIVICRM_QFID_1_is_monetary");
    $this->select2('payment_processor', $processorName, TRUE);
    if ($priceSet) {
      // get one - TBD
    }
    else {
      $this->select("financial_type_id", "label=Donation");
      $this->type("label_1", "Member");
      $this->type("value_1", "250.00");
      $this->type("label_2", "Non-member");
      $this->type("value_2", "325.00");
      //set default
      $this->click("xpath=//table[@id='map-field-table']/tbody/tr[2]/td[3]/input");
    }

    if ($discount) {
      // enter early bird discounts TBD
    }

    $this->click("_qf_Fee_upload-bottom");

    // Wait for "saved" status msg
    $this->waitForElementPresent("_qf_Fee_upload-bottom");
    $this->waitForTextPresent("'Fees' information has been saved.");
  }

  /**
   * @param $registerIntro
   * @param bool $multipleRegistrations
   */
  public function _testAddOnlineRegistration($registerIntro, $multipleRegistrations = FALSE) {
    // Go to Online Registration tab
    $this->click("link=Online Registration");
    $this->waitForElementPresent("_qf_Registration_upload-bottom");

    $this->check("is_online_registration");

    $this->assertChecked("is_online_registration");
    if ($multipleRegistrations) {
      $this->check("is_multiple_registrations");
      $this->assertChecked("is_multiple_registrations");
    }

    $this->click('intro_text');
    $this->fillRichTextField('intro_text', $registerIntro, 'CKEditor', TRUE);

    // enable confirmation email
    $this->click("CIVICRM_QFID_1_is_email_confirm");
    $this->type("confirm_from_name", "Jane Doe");
    $this->type("confirm_from_email", "jane.doe@example.org");

    $this->click("_qf_Registration_upload-bottom");
    $this->waitForElementPresent("_qf_Registration_upload-bottom");
    $this->waitForTextPresent("'Online Registration' information has been saved.");
  }

  /**
   * @param $eventTitle
   * @param int $pageId
   * @param string $firstName
   * @param string $lastName
   * @param string $middleName
   * @param $email
   * @param int $numberRegistrations
   * @param $campaignType
   * @param bool $anonymous
   */
  public function _testOnlineRegistration($eventTitle, $pageId, $firstName, $lastName, $middleName, $email, $numberRegistrations = 1, $campaignType, $anonymous = TRUE) {
    $hash = substr(sha1(rand()), 0, 7);
    $contributionAmount = 600;

    // registering online
    if ($anonymous) {
      $this->webtestLogout();
    }

    //participant registeration
    $firstNameParticipants = 'Jane' . substr(sha1(rand()), 0, 7);
    $lastNameParticipants = 'Smith' . substr(sha1(rand()), 0, 7);
    $emailParticipants = 'jane' . substr(sha1(rand()), 0, 7) . "@example.org";

    $registerUrl = "civicrm/event/register?id={$pageId}&reset=1";
    $this->open($this->sboxPath . $registerUrl);

    $this->type("first_name", "{$firstNameParticipants}");
    $this->type("last_name", "{$lastNameParticipants}");
    $this->select("additional_participants", "value=" . $numberRegistrations);
    $this->type("email-Primary", $emailParticipants);
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");
    $this->type("billing_first_name", "{$firstNameParticipants}");
    $this->type("billing_last_name", "{$lastNameParticipants}");
    $this->type("billing_street_address-5", "15 Main St.");
    $this->type(" billing_city-5", "San Jose");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");

    $this->click("_qf_Register_upload-bottom");

    if ($numberRegistrations > 1) {
      for ($i = 1; $i <= $numberRegistrations; $i++) {
        $this->waitForPageToLoad($this->getTimeoutMsec());
        // Look for Skip button
        $this->waitForElementPresent("_qf_Participant_{$i}_next_skip-Array");
        $this->type("email-Primary", "{$firstName}" . substr(sha1(rand()), 0, 7) . "@example.org");
        $this->click("_qf_Participant_{$i}_next");
      }
    }

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Confirm_next-bottom");
    $confirmStrings = array("Event Fee(s)", "Billing Name and Address", "Credit Card Information");
    $this->assertStringsPresent($confirmStrings);
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $thankStrings = array("Thank You for Registering", "Event Total", "Transaction Date");
    $this->assertStringsPresent($thankStrings);

    //pcp creation via different user
    $this->openCiviPage('contribute/campaign', "action=add&reset=1&pageId={$pageId}&component=event", "_qf_PCPAccount_next-bottom");

    $cmsUserName = 'CmsUser' . substr(sha1(rand()), 0, 7);

    $this->type("cms_name", $cmsUserName);
    $this->click("checkavailability");
    $this->waitForTextPresent('This username is currently available');
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->type("email-Primary", $email);
    $this->click("_qf_PCPAccount_next-bottom");
    $this->waitForElementPresent("_qf_Campaign_upload-bottom");

    $pcpTitle = 'PCPTitle' . substr(sha1(rand()), 0, 7);
    $this->type("pcp_title", $pcpTitle);
    $this->type("pcp_intro_text", "Welcome Text $hash");
    $this->type("goal_amount", $contributionAmount);
    $this->click("_qf_Campaign_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //admin pcp approval
    //login to check contribution

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $this->openCiviPage('admin/pcp', 'reset=1&page_type=event', "_qf_PCP_refresh");
    $this->select('status_id', 'value=1');
    $this->click("_qf_PCP_refresh");
    $this->waitForElementPresent("_qf_PCP_refresh");
    $id = explode('id=', $this->getAttribute("xpath=//div[@id='option11_wrapper']/table[@id='option11']/tbody//tr/td/a[text()='$pcpTitle']@href"));
    $pcpUrl = "civicrm/pcp/info?reset=1&id=$id[1]";
    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody//tr/td/a[text()='$pcpTitle']/../../td[7]/span[1]/a[2][text()='Approve']");

    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->webtestLogout();

    $this->open($this->sboxPath . $pcpUrl);
    $this->waitForElementPresent("xpath=//div[@class='pcp-donate']/a");
    $this->click("xpath=//div[@class='pcp-donate']/a");
    $emailElement = "";
    if ($campaignType == 'contribute') {
      $this->waitForElementPresent("_qf_Main_upload-bottom");
      $emailElement = "email-5";
    }
    elseif ($campaignType == 'event') {
      $this->waitForElementPresent('_qf_Register_upload-bottom');
      $emailElement = "email-Primary";
    }

    if ($campaignType == 'contribute') {
      $this->type("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", "$contributionAmount");
      $feeLevel = NULL;
    }
    elseif ($campaignType == 'event') {
      $contributionAmount = '250.00';
    }

    $firstNameDonar = 'Andrew' . substr(sha1(rand()), 0, 7);
    $lastNameDonar = 'Roger' . substr(sha1(rand()), 0, 7);
    $middleNameDonar = 'Nicholas' . substr(sha1(rand()), 0, 7);

    if ($this->isElementPresent("first_name")) {
      $this->type('first_name', $firstNameDonar);
    }

    if ($this->isElementPresent("last_name")) {
      $this->type('last_name', $lastNameDonar);
    }
    $this->type("{$emailElement}", $firstNameDonar . "@example.com");
    $this->webtestAddCreditCardDetails();
    $this->webtestAddBillingDetails($firstNameDonar, $middleNameDonar, $lastNameDonar);

    if ($campaignType == 'contribute') {
      $this->click("_qf_Main_upload-bottom");
    }
    elseif ($campaignType == 'event') {
      $this->click('_qf_Register_upload-bottom');
    }

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");

    if ($campaignType == 'contribute') {
      $this->waitForTextPresent("Your transaction has been processed successfully");
    }
    elseif ($campaignType == 'event') {
      $this->waitForTextPresent("Thank You for Registering");
    }

    //login to check contribution
    $this->webtestLogin();

    if ($campaignType == 'event') {
      $this->_testParticipantSearchEventName($eventTitle, $lastNameDonar, $firstNameDonar, $firstName, $lastName, $contributionAmount);
    }
    elseif ($campaignType == 'contribute') {
      $this->_testSearchTest($firstNameDonar, $lastNameDonar, $firstName, $lastName, $contributionAmount);
    }
  }

  /**
   * @param $campaignType
   * @param int $contributionPageId
   *
   * @return null
   */
  public function _testEventPcpAdd($campaignType, $contributionPageId) {
    $hash = substr(sha1(rand()), 0, 7);
    $isPcpApprovalNeeded = TRUE;

    // fill in step 9 (Enable Personal Campaign Pages)
    $this->click('link=Personal Campaigns');
    $this->waitForElementPresent('pcp_active');
    $this->click('pcp_active');
    $this->waitForElementPresent('_qf_Event_upload-bottom');

    $this->select('target_entity_type', "value={$campaignType}");

    if ($campaignType == 'contribute' && !empty($contributionPageId)) {

      $this->select('target_entity_id', "value={$contributionPageId}");

    }

    if (!$isPcpApprovalNeeded) {

      $this->click('is_approval_needed');

    }
    $this->type('notify_email', "$hash@example.name");
    $this->select('supporter_profile_id', 'value=2');
    $this->type('tellfriend_limit', 7);
    $this->type('link_text', "'Create Personal Campaign Page' link text $hash");

    $this->click('_qf_Event_upload-bottom');
    $this->waitForElementPresent('_qf_Event_upload-bottom');
    $text = "'Personal Campaigns' information has been saved.";
    $this->waitForText('crm-notification-container', $text);

    // parse URL to grab the contribution page id
    return $this->urlArg('id');
  }

  /**
   * @param string $eventName
   * @param string $lastNameDonar
   * @param string $firstNameDonar
   * @param string $firstNameCreator
   * @param string $lastNameCreator
   * @param $amount
   */
  public function _testParticipantSearchEventName($eventName, $lastNameDonar, $firstNameDonar, $firstNameCreator, $lastNameCreator, $amount) {
    $sortName = $lastNameDonar . ', ' . $firstNameDonar;
    $this->openCiviPage("event/search", "reset=1");

    $this->select2("event_id", $eventName);

    $this->clickLink("_qf_Search_refresh");

    $this->clickLink("xpath=//div[@id='participantSearch']/table/tbody/tr[1]/td[@class='crm-participant-sort_name']/a[text()='{$sortName}']/../../td[11]/span/a[text()='View']", "xpath=//table[@class='selector row-highlight']/tbody/tr/td[8]/span/a[text()='View']", FALSE);
    $this->clickLink("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='View']", "_qf_ParticipantView_cancel-bottom", FALSE);

    $this->webtestVerifyTabularData(
      array(
        'From' => "{$firstNameDonar} {$lastNameDonar}",
        'Total Amount' => $amount,
        'Contribution Status' => 'Completed',
      )
    );
    $softCreditor = "{$firstNameCreator} {$lastNameCreator}";
    $this->verifyText("xpath=//div[@id='PCPView']/div[2]//table[@class='crm-info-panel']/tbody/tr[2]/td[2]", preg_quote($softCreditor));
  }

  /**
   * @param string $firstName
   * @param $lastName
   * @param $pcpCreatorFirstName
   * @param $pcpCreatorLastName
   * @param $amount
   */
  public function _testSearchTest($firstName, $lastName, $pcpCreatorFirstName, $pcpCreatorLastName, $amount) {
    $sortName = "$pcpCreatorLastName, $pcpCreatorFirstName";
    $displayName = "$firstName $lastName";

    // visit contact search page
    $this->openCiviPage("contact/search", "reset=1");

    // fill name as first_name
    $this->type("css=.crm-basic-criteria-form-block input#sort_name", $pcpCreatorFirstName);

    // click to search
    $this->clickLink("_qf_Basic_refresh");

    $this->click("xpath=//div[@class='crm-search-results']//table/tbody//tr/td[3]/a[text()='{$sortName}']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click("css=li#tab_contribute a");
    $this->waitForElementPresent("xpath=//form[@id='Search']/div[@class='view-content']/table[2]/tbody/tr[@id='rowid']/td/a[text()='$displayName']");
    $this->click("xpath=//form[@id='Search']/div[@class='view-content']/table[2]/tbody/tr[@id='rowid']/td[8]/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    // as per changes made in CRM-15407
    $feeAmount = 1.50;
    $amount = $amount - $feeAmount;

    $this->webtestVerifyTabularData(
      array(
        'From' => "{$firstName} {$lastName}",
        'Net Amount' => $amount,
        'Contribution Status' => 'Completed',
      )
    );
    $softCreditor = "{$pcpCreatorFirstName} {$pcpCreatorLastName}";
    $this->verifyText("xpath=//div[@id='PCPView']/div[2]//table[@class='crm-info-panel']/tbody/tr[2]/td[2]", preg_quote($softCreditor));
  }

}
