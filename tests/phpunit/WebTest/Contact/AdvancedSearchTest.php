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
 * Class WebTest_Contact_AdvancedSearchTest
 */
class WebTest_Contact_AdvancedSearchTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAdvanceSearch() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
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
    $this->clickAjaxLink("css=li#tab_group a", "_qf_GroupContact_next");
    $this->select("group_id", "$groupName");
    $this->clickAjaxLink("_qf_GroupContact_next");
    $this->waitForText('crm-notification-container', "Contact has been added to '$groupName'");

    // go to tag tab and add to new tag
    $this->clickAjaxLink("css=li#tab_tag a", "css=div#tagtree");
    $this->click("xpath=//ul/li/span/label[text()=\"$tagName\"]");
    $this->checkCRMStatus();

    // register for event ( auto add activity and contribution )
    $this->clickPopupLink("link=Register for Event");
    $this->waitForText('s2id_event_id', "- select event -");
    $this->select2("event_id", "Fall Fundraiser Dinner");
    $this->waitForElementPresent("receipt_text");
    $this->multiselect2("role_id", array('Volunteer'));
    // Select $100 fee
    $this->click("xpath=//input[@data-amount='100']");
    $this->check("record_contribution");
    $this->waitForElementPresent("contribution_status_id");
    $this->select("payment_instrument_id", "Check");
    $this->type("check_number", "chqNo$firstName");
    $this->type("trxn_id", "trid$firstName");
    $this->clickAjaxLink("_qf_Participant_upload-bottom", "link=Add Event Registration");
    $this->waitForText('crm-notification-container', "Event registration for $firstName adv$firstName has been added");

    // Add pledge
    $this->clickPopupLink("link=Add Pledge");
    $this->waitForElementPresent("contribution_page_id");
    $this->type("amount", "200");
    $this->type("installments", "5");
    $this->type("frequency_interval", "1");
    $this->select("frequency_unit", "month(s)");
    $this->clickAjaxLink("_qf_Pledge_upload-bottom", "link=Add Pledge");
    $this->waitForText('crm-notification-container', "Pledge has been recorded and the payment schedule has been created.");

    // Add membership
    $this->clickPopupLink("link=Add Membership", "_qf_Membership_cancel-bottom");
    //let the organisation be default (Default Organization)
    $this->select("membership_type_id[0]", "value=1");
    $this->click("membership_type_id[1]");
    $this->select("membership_type_id[1]", "Student");
    $this->type("source", "membership source$firstName");
    $this->clickAjaxLink("_qf_Membership_upload-bottom");
    $this->waitForText('crm-notification-container', "Student membership for $firstName adv$firstName has been added");

    // Add relationship
    $this->clickPopupLink("link=Add Relationship", "_qf_Relationship_cancel");
    $this->select2("relationship_type_id", "Employee of");
    $this->waitForElementPresent("xpath=//input[@id='related_contact_id'][@placeholder='- select organization -']");
    $this->select2("related_contact_id", "Default", TRUE);
    $this->waitForAjaxContent();
    $this->webtestFillDate("start_date", "-1 day");
    $this->webtestFillDate("end_date", "+1 day");
    $this->clickAjaxLink('_qf_Relationship_upload-bottom', NULL);
    $this->waitForText('crm-notification-container', "Relationship created.");

    //-------------- advance search --------------

    $this->openCiviPage('contact/search/advanced', 'reset=1');

    //also create a dummy name to test false
    $dummyName = substr(sha1(rand()), 0, 7);

    // search block array for adv search
    $searchBlockValues = array(
      'basic' => array('', 'addBasicSearchDetail'),
      'location' => array('state_province', 'addAddressSearchDetail'),
      'demographics' => array('civicrm_gender_Transgender_3', 'addDemographicSearchDetail'),
      'notes' => array('note', ''),
      'activity' => array('activity_type_id', 'addActivitySearchDetail'),
      'CiviContribute' => array('contribution_currency_type', 'addContributionSearchDetail'),
      'CiviEvent' => array('participant_fee_amount_high', 'addParticipantSearchDetail'),
      'CiviMember' => array('member_end_date_high', 'addMemberSearchDetail'),
      'CiviPledge' => array('pledge_frequency_interval', 'addPledgeSearchDetail'),
      'relationship' => array(
        "xpath=//div[@id='relationship']/table/tbody/tr//td/label[text()='Relationship Status']/../label[text()='All']",
        '',
      ),
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
    $this->clickLink("_qf_Advanced_refresh");
    $this->waitForText("xpath=//form[@id='Advanced']/div[3]/div/div", "No matches found for");
  }

  /**
   * Check for CRM-9873
   */
  public function testActivitySearchByTypeTest() {
    $this->webtestLogin();
    $this->openCiviPage('contact/search/advanced', 'reset=1');
    $this->clickAjaxLink("activity", 'activity_subject');
    $this->multiselect2("activity_type_id", array('Tell a Friend'));
    $this->clickLink("_qf_Advanced_refresh");
    $count = explode(" ", trim($this->getText("xpath=//div[@id='search-status']/table/tbody/tr/td")));
    $count = $count[0];
    $this->assertTrue(is_numeric($count), "The total count of search results not found");

    //pagination calculation
    $perPageRow = 50;
    if ($count > $perPageRow) {
      $cal = $count / $perPageRow;
      $mod = $count % $perPageRow;
      $j = 1;
      for ($i = 1; $i <= $cal; $i++) {
        $subTotal = $i * $perPageRow;
        $lastPageSub = $subTotal + 1;

        //pagination and row count assertion
        $pagerCount = "Contact {$j} - {$subTotal} of {$count}";
        $this->verifyText("xpath=//div[@class='crm-search-results']/div[@class='crm-pager']/span[@class='crm-pager-nav']", preg_quote($pagerCount));
        $this->assertEquals($perPageRow, $this->getXpathCount("//div[@class='crm-search-results']/table/tbody/tr"));

        //go to next page
        $this->click("xpath=//div[@class='crm-search-results']/div[@class='crm-pager']/span[@class='crm-pager-nav']/a[@title='next page']");
        $this->waitForElementPresent("xpath=//a[@title='first page']");
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

  /**
   * function to check match for sumbit Advance Search.
   * @param string $firstName
   */
  public function submitSearch($firstName) {
    $this->clickLink("_qf_Advanced_refresh");
    // verify unique name
    $this->waitForAjaxContent();
    $this->waitForAjaxContent();
    $this->waitForElementPresent("xpath=//div[@class='crm-search-results']/table/tbody/tr//td/a[text()='adv$firstName, $firstName']");
    // should give 1 result only as we are searching with unique name
    $this->waitForText("xpath=//div[@id='search-status']/table/tbody/tr/td", preg_quote("1 Contact"));
    // click to edit search
    $this->click("xpath=//form[@id='Advanced']//div[2]/div/div[1]");
  }

  /**
   * Check for CRM-14952
   */
  public function testStateSorting() {
    $this->webtestLogin();
    $this->openCiviPage('contact/search/advanced', 'reset=1', 'group');
    $this->select2("group", "Newsletter", TRUE);
    $this->select2("group", "Summer", TRUE);
    $this->select2("group", "Advisory", TRUE);
    $this->clickAjaxLink("location", 'country');
    $this->select2("country", "UNITED STATES", FALSE);
    $this->waitForElementPresent('state_province');
    $this->multiselect2("state_province", array(
        "Ohio",
        "New York",
        "New Mexico",
        "Connecticut",
        "Georgia",
        "New Jersey",
        "Texas",
      ));
    $this->clickLink("_qf_Advanced_refresh", "xpath=//div[@class='crm-search-results']//table/tbody/tr[1]/td[6]");

    $stateBeforeSort = $this->getText("xpath=//div[@class='crm-search-results']//table/tbody/tr[1]/td[6]");
    $this->clickAjaxLink("xpath=//div[@class='crm-search-results']//table/thead/tr//th/a[contains(text(),'State')]");
    $this->waitForElementPresent("xpath=//div[@class='crm-search-results']//table/thead/tr//th/a[contains(text(),'State')]");
    $this->clickAjaxLink("xpath=//div[@class='crm-search-results']//table/thead/tr//th/a[contains(text(), 'State')]");
    $this->waitForElementPresent("xpath=//div[@class='crm-search-results']//table/thead/tr//th/a[contains(text(), 'State')]");
    $this->assertElementNotContainsText("xpath=//div[@class='crm-search-results']//table/tbody/tr[1]/td[6]", $stateBeforeSort);
  }

  /**
   * function to fill basic search detail.
   * @param string $firstName
   * @param string $groupName
   * @param $tagName
   */
  public function addBasicSearchDetail($firstName, $groupName, $tagName) {
    // fill partial sort name
    $this->type("sort_name", "$firstName");
    // select subtype
    $this->select("contact_type", "value=Individual__Student");
    // select group
    $this->select("group", "label=$groupName");
    // select tag
    $this->select("contact_tags", "label=$tagName");
    // select preferred language
    $this->select("preferred_language", "value=en_US");
    // select privacy
    $this->select("privacy_options", "value=do_not_email");

    // select preferred communication method
    // phone
    $this->select2("preferred_communication_method", array('Phone', 'Email'), TRUE);
  }

  /**
   * function to fill address search block values in advance search.
   * @param $firstName
   */
  public function addAddressSearchDetail($firstName) {
    // select location type (home and main)
    $this->multiselect2('location_type', array('Home', 'Main'));
    // fill street address
    $this->type("street_address", "street 1 $firstName");
    // fill city
    $this->type("city", "city$firstName");
    // fill postal code range
    $this->type("postal_code_low", "100010");
    $this->type("postal_code_high", "101000");
    // select country
    $this->select("country", "UNITED STATES");
    // select state-province
    $this->waitForElementPresent('state_province');
    $this->select2("state_province", "Alaska", TRUE);
  }

  /**
   * function to fill activity search block in advance search.
   * @param $firstName
   */
  public function addActivitySearchDetail($firstName) {
    // select activity types
    $activityTypes = array("Contribution", "Event Registration", "Membership Signup");
    $this->multiselect2('activity_type_id', $activityTypes);
    // fill date range
    $this->select("activity_date_relative", "value=0");
    $this->webtestFillDate("activity_date_low", "-1 day");
    $this->webtestFillDate("activity_date_high", "+1 day");
    $this->type("activity_subject", "Student - membership source$firstName - Status: New");
    // fill activity status
    $this->multiselect2('status_id', array('Scheduled', 'Completed'));
  }

  /**
   * function to fill demographic search details.
   */
  public function addDemographicSearchDetail() {
    // fill birth date range
    $this->select("birth_date_relative", "value=0");
    $this->webtestFillDate("birth_date_low", "-3 year");
    $this->webtestFillDate("birth_date_high", "now");
    // fill deceased date range
    $this->click("xpath=//div[@id='demographics']/table/tbody//tr/td/label[text()='Deceased']/../label[text()='Yes']");
    $this->select("deceased_date_relative", "value=0");
    $this->webtestFillDate("deceased_date_low", "-1 month");
    $this->webtestFillDate("deceased_date_high", "+1 month");
    // fill gender (male)
    $this->check("civicrm_gender_Male_2");
  }

  /**
   * function to fill contribution search details.
   * @param $firstName
   */
  public function addContributionSearchDetail($firstName) {
    // fill contribution date range
    $this->select("contribution_date_relative", "value=0");
    $this->webtestFillDate("contribution_date_low", "-1 day");
    $this->webtestFillDate("contribution_date_high", "+1 day");
    // fill amount range
    $this->type("contribution_amount_low", "1");
    $this->type("contribution_amount_high", "200");
    // check for completed
    $this->multiselect2("contribution_status_id", array('Completed'));
    // enter check number
    $this->select("payment_instrument_id", "Check");
    $this->type("contribution_check_number", "chqNo$firstName");
    // fill transaction id
    $this->type("contribution_trxn_id", "trid$firstName");
    // fill financial type
    $this->select("financial_type_id", "Event Fee");
    // fill currency type
    $this->select2("contribution_currency_type", "USD");
  }

  /**
   * function to fill participant search details.
   */
  public function addParticipantSearchDetail() {
    // fill event name
    $this->select2("event_id", "Fall Fundraiser Dinner");
    // fill event type
    $this->select2("event_type_id", "Fundraiser");
    // select participant status (registered)
    $this->multiselect2('participant_status_id', array('Registered'));
    // select participant role (Volunteer)
    $this->multiselect2('participant_role_id', array('Volunteer'));
    // fill participant fee level (couple)
    $this->select2("participant_fee_id", "Couple");
    // fill amount range
    $this->type("participant_fee_amount_low", "1");
    $this->type("participant_fee_amount_high", "150");
  }

  /**
   * function to fill member search details.
   * @param $firstName
   */
  public function addMemberSearchDetail($firstName) {
    // check membership type (Student)
    $this->select2('membership_type_id', 'Student', TRUE);
    // check membership status (completed)
    $this->select2('membership_status_id', 'New', TRUE);
    // fill member source
    $this->type("member_source", "membership source$firstName");
    // check to search primary member
    $this->click("xpath=//div[@id='memberForm']/table/tbody/tr[2]/td[2]/p/input");
    // add join date range
    $this->select("member_join_date_relative", "value=0");
    $this->webtestFillDate("member_join_date_low", "-1 day");
    $this->webtestFillDate("member_join_date_high", "+1 day");
    // add start date range
    $this->select("member_start_date_relative", "value=0");
    $this->webtestFillDate("member_start_date_low", "-1 day");
    $this->webtestFillDate("member_start_date_high", "+1 day");
    // add end date range
    $this->select("member_end_date_relative", "value=0");
    $this->webtestFillDate("member_end_date_low", "-1 year");
    $this->webtestFillDate("member_end_date_high", "+2 year");
  }

  /**
   * function to fill member search details.
   * @param $firstName
   */
  public function addPledgeSearchDetail($firstName) {
    // fill pledge schedule date range
    $this->select("pledge_payment_date_relative", "value=0");
    $this->webtestFillDate("pledge_payment_date_low", "-1 day");
    $this->webtestFillDate("pledge_payment_date_high", "+1 day");
    // fill Pledge payment status
    $this->select2('pledge_status_id', 'Pending', TRUE);
    $this->select2('pledge_payment_status_id', 'Pending', TRUE);
    // fill pledge amount range
    $this->type("pledge_amount_low", "100");
    $this->type("pledge_amount_high", "300");
    // fill pledge created date range
    $this->webtestFillDate("pledge_create_date_low", "-5 day");
    $this->webtestFillDate("pledge_create_date_high", "+5 day");
    // fill plegde start date
    $this->webtestFillDate("pledge_start_date_low", "-2 day");
    $this->webtestFillDate("pledge_start_date_high", "+2 day");
    // fill financial type
    $this->select("pledge_financial_type_id", "Donation");
  }

  /**
   * function to create contact with details (contact details, address, Constituent information ...)
   * @param null $firstName
   */
  public function createDetailContact($firstName = NULL) {
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
    $this->select("contact_sub_type", "label=$Subtype");
    $this->type("email_1_email", "$firstName@advsearch.co.in");
    $this->type("phone_1_phone", "123456789");
    $this->type("external_identifier", "extid$firstName");

    // --- fill few value in Constituent information
    $this->click("customData");
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
    $this->select("address_1_country_id", "UNITED STATES");
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
    $this->click("CIVICRM_QFID_2_gender_id");

    $this->webtestFillDate("birth_date", "-1 year");
    $this->click("is_deceased");
    $this->waitForElementPresent("deceased_date");
    $this->webtestFillDate("deceased_date", "now");

    // save contact
    $this->clickLink("_qf_Contact_upload_view", 'css=.crm-summary-display_name');
    $this->assertElementContainsText('css=.crm-summary-display_name', "$firstName adv$firstName");
  }

}
