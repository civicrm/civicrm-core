<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
class WebTest_Contact_AdvancedSearchTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAdvanceSearch() {

    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //------- first create new group and tag -----

    // take group name and create group
    $groupName = 'group_' . substr(sha1(rand()), 0, 7);
    $this->WebtestAddGroup($groupName);

    // take a tag name and create tag
    include_once 'WebTest/Contact/SearchTest.php';
    $tagName = 'tag_' . substr(sha1(rand()), 0, 7);
    WebTest_Contact_SearchTest::addTag($tagName, $this);

    //---------- create detailed contact ---------

    $firstName = substr(sha1(rand()), 0, 7);
    $this->createDetailContact($firstName);

    // go to group tab and add to new group
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("_qf_GroupContact_next");
    $this->select("group_id", "$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // go to tag tab and add to new tag
    $this->click("css=li#tab_tag a");
    $this->waitForElementPresent("css=div#tagtree");
    $this->click("xpath=//ul/li/label[text()=\"$tagName\"]");
    $this->waitForElementPresent("css=.success");

    // is status message correct?
    $this->waitForTextPresent("Saved");
    $this->assertElementContainsText('crm-notification-container', "Saved");

    // go to event tab and register for event ( auto add activity and contribution )
    $this->click("css=li#tab_participant a");
    $this->waitForElementPresent("link=Add Event Registration");
    $this->click("link=Add Event Registration");
    $this->waitForElementPresent("note");
    // fall fundraiser dinner event label is variable
    $this->select("event_id", "value=1");
    $this->waitForElementPresent("receipt_text");
    $this->click("role_id[2]");
    $this->check("record_contribution");
    $this->waitForElementPresent("contribution_status_id");
    $this->select("payment_instrument_id", "Check");
    $this->type("check_number", "chqNo$firstName");
    $this->type("trxn_id", "trid$firstName");
    $this->click("_qf_Participant_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "Event registration for $firstName adv$firstName has been added");

    // go to pledge tab and add pledge
    $this->click("css=li#tab_pledge a");
    $this->waitForElementPresent("link=Add Pledge");
    $this->click("link=Add Pledge");
    $this->waitForElementPresent("contribution_page_id");
    $this->type("amount", "200");
    $this->type("installments", "5");
    $this->type("frequency_interval", "1");
    $this->select("frequency_unit", "month(s)");
    $this->click("_qf_Pledge_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertElementContainsText('crm-notification-container', "Pledge has been recorded and the payment schedule has been created.");

    // go to Membership tab and add membership
    $this->click("css=li#tab_member a");
    $this->waitForElementPresent("link=Add Membership");
    $this->click("link=Add Membership");
    $this->waitForElementPresent("send_receipt");
    //let the organisation be default (Default Organization)
    $this->select("membership_type_id[0]", "label=Default Organization");
    $this->click("membership_type_id[1]");
    $this->select("membership_type_id[1]", "Student");
    $this->type("source", "membership source$firstName");
    $this->click("_qf_Membership_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertElementContainsText('crm-notification-container', "Student membership for $firstName adv$firstName has been added");

    // go to relationship tab and add relationship
    $this->click("css=li#tab_rel a");
    $this->waitForElementPresent("link=Add Relationship");
    $this->click("link=Add Relationship");
    $this->waitForElementPresent("_qf_Relationship_cancel");
    $this->select("relationship_type_id", "Employee of");
    $this->webtestFillAutocomplete("Default"); //default organization is provided as employer contact
    $this->waitForElementPresent("details-save");
    $this->webtestFillDate("start_date", "-1 day");
    $this->webtestFillDate("end_date", "+1 day");
    $this->click("details-save");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "New relationship created.");

    //-------------- advance search --------------

    // Go directly to the URL of the screen that you will be testing (Advance Search).
    $this->openCiviPage('contact/search/advanced', 'reset=1');

    //also create a dummy name to test false
    $dummyName = substr(sha1(rand()), 0, 7);

    // search block array for adv search
    $searchBlockValues = array(
      'basic' => array('', 'addBasicSearchDetail'),
      'location' => array('state_province', 'addAddressSearchDetail'),
      'demographics' => array('civicrm_gender_Transgender_3', 'addDemographicSearchDetail'),
      'notes' => array('note', ''),
      'activity' => array('activity_status[5]', 'addActivitySearchDetail'),
      'CiviContribute' => array('contribution_currency_type', 'addContributionSearchDetail'),
      'CiviEvent' => array('participant_fee_amount_high', 'addParticipantSearchDetail'),
      'CiviMember' => array('member_end_date_high', 'addMemberSearchDetail'),
      'CiviPledge' => array('pledge_in_honor_of', 'addPledgeSearchDetail'),
      'relationship' => array("xpath=//div[@id='relationship']/table/tbody/tr//td/label[text()='Relationship Status']/../label[text()='All']", ''),
    );

    foreach ($searchBlockValues as $block => $blockValues) {
      switch ($block) {
        case 'basic':
          $this->$blockValues[1]($firstName, $groupName, $tagName);
          break;

        case 'notes':
          $this->click("$block");
          $this->waitForElementPresent("$blockValues[0]");
          $this->type("note", "this is notes by $firstName");
          break;

        case 'relationship':
          $this->click("$block");
          $this->waitForElementPresent("$blockValues[0]");
          $this->select("relation_type_id", "Employee of");
          $this->type("relation_target_name", "Default");
          break;

        default:
          $this->click("$block");
          $this->waitForElementPresent("$blockValues[0]");
          $this->$blockValues[1]($firstName);
          break;
      }

      $this->submitSearch($firstName);
    }

    //--  search with non existing value ( sort name )
    $this->type("sort_name", "$dummyName");
    $this->click("_qf_Advanced_refresh");
    $this->waitForElementPresent("css=div.messages");
    $this->assertElementContainsText('css=div.messages', "No matches found for");
  }

  /*
   * Check for CRM-9873
   */
  function testActivitySearchByTypeTest() {
    $this->open($this->sboxPath);
    $this->webtestLogin();
    $this->openCiviPage('contact/search/advanced', 'reset=1');
    $this->click("activity");
    $this->waitForElementPresent('activity_subject');
    $this->check("xpath=//div[@id='Activity']//div/label[text()='Tell a Friend']/../input");
    $this->click("_qf_Advanced_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $count = explode(" ", trim($this->getText("xpath=//div[@id='search-status']/table/tbody/tr/td")));
    $count = $count[0];
    $this->assertTrue(is_numeric($count), "The total count of search results not found");

    //pagination calculation
    $perPageRow = 50;
    if ($count > $perPageRow) {
      $cal = $count / $perPageRow;
      $mod = $count % $perPageRow;
      $j = 1;
      for ($i=1; $i<=$cal; $i++) {
        $subTotal = $i * $perPageRow;
        $lastPageSub = $subTotal + 1;

        //pagination and row count assertion
        $pagerCount = "Contact {$j} - {$subTotal} of {$count}";
        $this->verifyText("xpath=//div[@class='crm-search-results']/div[@class='crm-pager']/span[@class='crm-pager-nav']", preg_quote($pagerCount));
        $this->assertEquals($perPageRow, $this->getXpathCount("//div[@class='crm-search-results']/table/tbody/tr"));

        //go to next page
        $this->click("xpath=//div[@class='crm-search-results']/div[@class='crm-pager']/span[@class='crm-pager-nav']/a[@title='next page']");
        $this->waitForPageToLoad($this->getTimeoutMsec());
        $j = $j + $subTotal;
      }

      //pagination and row count assertion for the remaining last page
      if ($mod) {
        $pagerCount = "Contact {$lastPageSub} - {$count} of {$count}";
        $this->verifyText("xpath=//div[@class='crm-search-results']/div[@class='crm-pager']/span[@class='crm-pager-nav']", preg_quote($pagerCount));
        $this->assertEquals($mod, $this->getXpathCount("//div[@class='crm-search-results']/table/tbody/tr"));
      }
    }
  }

  //function to check match for sumbit Advance Search
  function submitSearch($firstName) {

    $this->click("_qf_Advanced_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    // verify unique name
    $this->verifyText("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]/a", preg_quote("adv$firstName, $firstName"));
    // should give 1 result only as we are searching with unique name
    $this->waitForText("xpath=//div[@id='search-status']/table/tbody/tr/td", preg_quote("1 Contact"));
    // click to edit search
    $this->click("xpath=//form[@id='Advanced']//div[2]/div/div[1]");
  }

  // function to fill auto complete
  function fillAutoComplete($text, $elementId) {
    $this->click("$elementId");
    $this->type("$elementId", "$text");
    $this->typeKeys("$elementId", "$text");
    $this->waitForElementPresent("css=div.ac_results li");
    $this->click("css=div.ac_results li");
    $this->assertContains($text, $this->getValue("$elementId"),
      "autocomplete expected $text but didn’t find it in " . $this->getValue("$elementId")
    );
  }

  // function to fill basic search detail
  function addBasicSearchDetail($firstName, $groupName, $tagName) {
    // fill partial sort name
    $this->type("sort_name", "$firstName");
    // select subtype
    $this->select("crmasmSelect0", "value=IndividualStudent");
    // select group
    $this->select("crmasmSelect1", "label=$groupName");
    // select tag
    $this->select("crmasmSelect3", "label=$tagName");
    // select prefered language
    $this->select("preferred_language", "value=en_US");
    // select privacy
    $this->select("privacy_options", "value=do_not_email");

    // select preferred communication method
    // phone
    $this->check("preferred_communication_method[1]");
    // email
    $this->check("preferred_communication_method[2]");
  }

  // function to fill address search block values in advance search
  function addAddressSearchDetail($firstName) {
    // select location type (home and main)
    $this->click("xpath=//div[@id='location']/table/tbody/tr[1]/td[1]//label[text()='Home']");
    $this->click("xpath=//div[@id='location']/table/tbody/tr[1]/td[1]//label[text()='Main']");
    // fill street address
    $this->type("street_address", "street 1 $firstName");
    // fill city
    $this->type("city", "city$firstName");
    // fill postal code range
    $this->type("postal_code_low", "100010");
    $this->type("postal_code_high", "101000");
    // select country
    $this->select("country", "United States");
    // select state-province
    $this->select("state_province", "Alaska");
  }

  // function to fill activity search block in advance search
  function addActivitySearchDetail($firstName) {
    // check activity types
    $checkActivityTypes = array("Contribution", "Event Registration", "Membership Signup");
    foreach ($checkActivityTypes as $labels) {
      $this->click("xpath=//div[@id='activity']/table/tbody/tr[1]/td[1]/div[1]//div/label[text()=\"$labels\"]");
    }
    // fill date range
    $this->select("activity_date_relative","value=0");
    $this->webtestFillDate("activity_date_low", "-1 day");
    $this->webtestFillDate("activity_date_high", "+1 day");
    $this->type("activity_subject", "Student - membership source$firstName - Status: New");
    // fill activity status

    $this->click("xpath=//div[@id='activity']/table/tbody//tr/td[2]/label[text()='Activity Status']/../label[text()='Scheduled']");
    $this->click("xpath=//div[@id='activity']/table/tbody//tr/td[2]/label[text()='Activity Status']/../label[text()='Completed']");
  }

  // function to fill demographic search details
  function addDemographicSearchDetail() {
    // fill birth date range
    $this->select("birth_date_relative","value=0");
    $this->webtestFillDate("birth_date_low", "-3 year");
    $this->webtestFillDate("birth_date_high", "now");
    // fill deceased date range
    $this->click("xpath=//div[@id='demographics']/table/tbody//tr/td/label[text()='Deceased']/../label[text()='Yes']");
    $this->select("deceased_date_relative","value=0");
    $this->webtestFillDate("deceased_date_low", "-1 month");
    $this->webtestFillDate("deceased_date_high", "+1 month");
    // fill gender (male)
    $this->check("civicrm_gender_Male_2");
  }

  //function to fill contribution search details
  function addContributionSearchDetail($firstName) {
    // fill contribution date range
    $this->select("contribution_date_relative","value=0");
    $this->webtestFillDate("contribution_date_low", "-1 day");
    $this->webtestFillDate("contribution_date_high", "+1 day");
    // fill amount range
    $this->type("contribution_amount_low", "1");
    $this->type("contribution_amount_high", "200");
    // check for completed
    $this->check("contribution_status_id[1]");
    // enter check number
    $this->select("contribution_payment_instrument_id", "Check");
    $this->type("contribution_check_number", "chqNo$firstName");
    // fill transaction id
    $this->type("contribution_transaction_id", "trid$firstName");
      // fill financial type
      $this->select("financial_type_id", "Event Fee");
    // fill currency type
    $this->select("contribution_currency_type", "USD");
  }

  // function to fill participant search details
  function addParticipantSearchDetail() {
    // fill event name
    $this->fillAutoComplete("Fall Fundraiser Dinner", "event_name");
    // fill event type
    $this->fillAutoComplete("Fundraiser", "event_type");
    // check participant status (registered)
    $this->click("xpath=//div[@id='participantForm']/table/tbody//tr/td/label[text()='Participant Status']/../div//div/label[text()='Registered']");
    // check participant role (Volunteer)
    $this->click("xpath=//div[@id='participantForm']/table/tbody//tr/td/label[text()='Participant Role']/../div//div/label[text()='Volunteer']");
    // fill participant fee level (couple)
    $this->fillAutoComplete("Couple", "participant_fee_level");
    // fill amount range
    $this->type("participant_fee_amount_low", "1");
    $this->type("participant_fee_amount_high", "150");
  }

  // function to fill member search details
  function addMemberSearchDetail($firstName) {
    // check membership type (Student)
    $this->click("xpath=//div[@id='memberForm']/table/tbody/tr[1]/td[1]/div[1]//div/label[text()='Student']");
    // check membership status (completed)
    $this->click("xpath=//div[@id='memberForm']/table/tbody/tr[1]/td[2]/div[1]//div/label[text()='New']");
    // fill member source
    $this->type("member_source", "membership source$firstName");
    // check to search primary member
    $this->click("xpath=//div[@id='memberForm']/table/tbody/tr[2]/td[2]/p/input");
    // add join date range
    $this->select("member_join_date_relative","value=0");
    $this->webtestFillDate("member_join_date_low", "-1 day");
    $this->webtestFillDate("member_join_date_high", "+1 day");
    // add start date range
    $this->select("member_start_date_relative","value=0");
    $this->webtestFillDate("member_start_date_low", "-1 day");
    $this->webtestFillDate("member_start_date_high", "+1 day");
    // add end date range
    $this->select("member_end_date_relative","value=0");
    $this->webtestFillDate("member_end_date_low", "-1 year");
    $this->webtestFillDate("member_end_date_high", "+2 year");
  }


  // function to fill member search details
  function addPledgeSearchDetail($firstName) {
    // fill pledge schedule date range
    $this->select("pledge_payment_date_relative","value=0");
    $this->webtestFillDate("pledge_payment_date_low", "-1 day");
    $this->webtestFillDate("pledge_payment_date_high", "+1 day");
    // fill Pledge payment status
    $this->click("xpath=//div[@id='pledgeForm']/table/tbody/tr[3]/td//label[text()='Completed']");
    $this->click("xpath=//div[@id='pledgeForm']/table/tbody/tr[3]/td//label[text()='Pending']");
    // fill pledge amount range
    $this->type("pledge_amount_low", "100");
    $this->type("pledge_amount_high", "300");
    // fill plegde status
    $this->click("xpath=//div[@id='pledgeForm']/table/tbody/tr[4]/td[2]//label[text()='Completed']");
    $this->click("xpath=//div[@id='pledgeForm']/table/tbody/tr[4]/td[2]//label[text()='Pending']");
    // fill pledge created date range
    $this->webtestFillDate("pledge_create_date_low", "-5 day");
    $this->webtestFillDate("pledge_create_date_high", "+5 day");
    // fill plegde start date
    $this->webtestFillDate("pledge_start_date_low", "-2 day");
    $this->webtestFillDate("pledge_start_date_high", "+2 day");
    // fill financial type
    $this->select("pledge_financial_type_id", "Donation");
  }

  // function to create contact with details (contact details, address, Constituent information ...)
  function createDetailContact($firstName = NULL) {
    if (!$firstName) {
      $firstName = substr(sha1(rand()), 0, 7);
    }

    // create contact type Individual with subtype
    // with most of values to required to search
    $Subtype = "Student";
    $this->openCiviPage('contact/add', 'reset=1&ct=Individual', '_qf_Contact_cancel');

    // --- fill few values in Contact Detail block
    $this->type("first_name", "$firstName");
    $this->type("middle_name", "mid$firstName");
    $this->type("last_name", "adv$firstName");
    $this->select("contact_sub_type", "label=- $Subtype");
    $this->type("email_1_email", "$firstName@advsearch.co.in");
    $this->type("phone_1_phone", "123456789");
    $this->type("external_identifier", "extid$firstName");

    // --- fill few value in Constituent information
    $this->click("customData1");
    $this->waitForElementPresent("custom_3_-1");

    $this->click("CIVICRM_QFID_Edu_2");
    $this->select("custom_2_-1", "label=Single");

    // --- fill few values in address

    $this->click("//form[@id='Contact']/div[2]/div[4]/div[1]");
    $this->waitForElementPresent("address_1_geo_code_2");
    $this->type("address_1_street_address", "street 1 $firstName");
    $this->type("address_1_supplemental_address_1", "street supplement 1 $firstName");
    $this->type("address_1_supplemental_address_2", "street supplement 2 $firstName");
    $this->type("address_1_city", "city$firstName");
    $this->type("address_1_postal_code", "100100");
    $this->select("address_1_country_id", "United States");
    $this->select("address_1_state_province_id", "Alaska");

    // --- fill few values in communication preferences
    $this->click("//form[@id='Contact']/div[2]/div[5]/div[1]");
    $this->waitForElementPresent("preferred_mail_format");
    $this->check("privacy[do_not_phone]");
    $this->check("privacy[do_not_mail]");
    // phone
    $this->check("preferred_communication_method[1]");
    // email
    $this->check("preferred_communication_method[2]");
    $this->select("preferred_language", "value=en_US");

    // --- fill few value in notes
    $this->click("//form[@id='Contact']/div[2]/div[6]/div[1]");
    $this->waitForElementPresent("note");
    $this->type("subject", "this is subject by $firstName");
    $this->type("note", "this is notes by $firstName");

    // --- fill few values in demographics
    $this->click("//form[@id='Contact']/div[2]/div[7]/div[1]");
    $this->waitForElementPresent("is_deceased");
    $this->click("civicrm_gender_Male_2");

    $this->webtestFillDate("birth_date", "-1 year");
    $this->click("is_deceased");
    $this->waitForElementPresent("deceased_date");
    $this->webtestFillDate("deceased_date", "now");

    // save contact
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('css=.crm-summary-display_name', "$firstName adv$firstName");
  }
}

