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
class WebTest_Contact_SearchBuilderTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }


  function testSearchBuilderOptions() {
    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();

    $groupName = $this->WebtestAddGroup();

    // Open the search builder
    $this->openCiviPage('contact/search/builder', 'reset=1');

    $this->enterValues(1, 1, 'Contacts', 'Group(s)', NULL, '=', array($groupName));
    $this->enterValues(1, 2, 'Contacts', 'Country', NULL, '=', array('United States'));
    $this->enterValues(1, 3, 'Individual', 'Gender', NULL, '=', array('Male'));
    $this->click('_qf_Builder_refresh');
    $this->waitForPageToLoad();

    // We should get no results. But check the options are all still set
    $this->waitForTextPresent('No matches found');
    foreach (array($groupName, 'United States', 'Male') as $i => $label) {
      $this->waitForElementPresent("//span[@id='crm_search_value_1_$i']/select/option[2]");
      $this->assertSelectedLabel("//span[@id='crm_search_value_1_$i']/select", $label);
    }
  }

  function testSearchBuilderRLIKE() {
    $this->webtestLogin();

    // Adding contact
    // We're using Quick Add block on the main page for this.
    $firstName = substr(sha1(rand()), 0, 7);
    $this->createDetailContact($firstName);

    $sortName = "adv$firstName, $firstName";
    $displayName = "$firstName adv$firstName";

    $this->_searchBuilder("Postal Code", "100[0-9]", $sortName, "RLIKE");
  }

  // function to create contact with details (contact details, address, Constituent information ...)
  function createDetailContact($firstName = NULL) {

    if (!$firstName) {
      $firstName = substr(sha1(rand()), 0, 7);
    }

    // create contact type Individual with subtype
    // with most of values to required to search
    $Subtype = "Student";
    $this->openCiviPage('contact/add', array('reset' => 1, 'ct' => 'Individual'), '_qf_Contact_cancel');

    // --- fill few values in Contact Detail block
    $this->type("first_name", "$firstName");
    $this->type("middle_name", "mid$firstName");
    $this->type("last_name", "adv$firstName");
    $this->select("contact_sub_type", "label=- $Subtype");
    $this->type("email_1_email", "$firstName@advsearch.co.in");
    $this->type("phone_1_phone", "123456789");
    $this->type("external_identifier", "extid$firstName");

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

    // save contact
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("$firstName adv$firstName"));
  }

  function testSearchBuilderContacts(){
    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();

    //Individual
    $firstName = substr(sha1(rand()), 0, 7);
    $streetName = "street $firstName";
    $sortName = "adv$firstName, $firstName";
    $this->_createContact('Individual', $firstName, "$firstName@advsearch.co.in", $streetName);
    // search using search builder and advanced search
    $this->_searchBuilder('Street Address', $streetName, $sortName, '=', '1');
    $this->_advancedSearch($streetName, $sortName, 'Individual', '1', 'street_address');

    //Organization
    $orgName = substr(sha1(rand()), 0, 7)."org";
    $orgEmail = "ab".rand()."@{$orgName}.com";
    $this->_createContact('Organization', $orgName, $orgEmail,"street $orgName");
    // search using search builder and advanced search
    $this->_searchBuilder('Email',$orgEmail, $orgName,'=','1');
    $this->_advancedSearch($orgEmail, $orgName, 'Organization','1','email');

    //Household
    $householdName = "household".substr(sha1(rand()), 0, 7);
    $householdEmail = "h1".rand()."@{$householdName}.com";
    $this->_createContact('Household', $householdName, $householdEmail,"street $householdName");
    // search using search builder and advanced search
    $this->_searchBuilder('Email',$householdEmail, $householdName,'=','1');
    $this->_advancedSearch($householdEmail, $householdName, 'Household','1','email');

    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    // searching contacts whose email is not set
    $firstName1 = "00a1".substr(sha1(rand()), 0, 7);
    $this->type("first_name", $firstName1);
    $this->type("last_name", "01adv$firstName1");
    // save contact
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    $firstName2 = "00a2".substr(sha1(rand()), 0, 7);
    $this->type("first_name", $firstName2);
    $this->type("last_name", "02adv$firstName2");
    // save contact
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    $firstName3 = "00a3".substr(sha1(rand()), 0, 7);
    $this->type("first_name", $firstName3);
    $this->type("last_name", "03adv$firstName3");
    // save contact
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->_searchBuilder('Email',NULL,NULL,'IS NULL');
    $this->click("xpath=//div[@class='crm-search-results']/div[4]/a[2]");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $names = array( 1 => $firstName1,
                    2 => $firstName2,
                    3 => $firstName3,
                    );
    foreach($names as $key => $value){
      $this->assertTrue($this->isTextPresent($value));
    }
    //searching contacts whose phone field is empty
    $this->_searchBuilder('Phone',NULL,NULL,'IS EMPTY');
    foreach($names as $key => $value){
      $this->assertTrue($this->isTextPresent($value));
    }
    //searching contacts whose phone field is not empty
    $this->_searchBuilder('Phone',NULL,$firstName,'IS NOT EMPTY');
    $this->click("xpath=//div[@class='crm-search-results']/div[4]/a[2]");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent($firstName));

    $firstName4 = "AB".substr(sha1(rand()), 0, 7);
    $postalCode = rand();
    $this->_createContact('Individual', $firstName4,"$firstName4@advsearch.co.in",NULL, $postalCode);
    $firstName5 = "CD".substr(sha1(rand()), 0, 7);
    $this->_createContact('Individual', $firstName5,"$firstName5@advsearch.co.in",NULL, $postalCode);
    $firstName6 = "EF".substr(sha1(rand()), 0, 7);
    $this->_createContact('Organization', $firstName6,"$firstName6@advsearch.co.in",NULL, $postalCode);
    $firstName7 = "GH".substr(sha1(rand()), 0, 7);
    $this->_createContact('Household', $firstName7,"$firstName7@advsearch.co.in",NULL, $postalCode);

    // check if the resultset of search builder and advanced search match for the postal code
    $this->_searchBuilder('Postal Code',$postalCode,NULL,'LIKE','4');
    $this->_advancedSearch($postalCode,NULL,NULL,'4','postal_code');

    $firstName8 = "abcc".substr(sha1(rand()), 0, 7);
    $this->_createContact('Individual', $firstName8,"$firstName8@advsearch.co.in",NULL);
    $this->_searchBuilder('Note(s): Body and Subject', "this is subject by $firstName8", $firstName8, 'LIKE');
    $this->_searchBuilder('Note(s): Body and Subject', "this is notes by $firstName8", $firstName8, 'LIKE');
    $this->_searchBuilder('Note(s): Subject only', "this is subject by $firstName8", $firstName8, 'LIKE');
    $this->_searchBuilder('Note(s): Body only', "this is notes by $firstName8", $firstName8, 'LIKE');
    $this->_advancedSearch( "this is notes by $firstName8", $firstName8, NULL, NULL, 'note_body', 'notes');
    $this->_advancedSearch( "this is subject by $firstName8", $firstName8, NULL, NULL, 'note_subject', 'notes');
    $this->_advancedSearch( "this is notes by $firstName8", $firstName8, NULL, NULL, 'note_both', 'notes');
    $this->_advancedSearch( "this is subject by $firstName8", $firstName8, NULL, NULL, 'note_both', 'notes');
  }

  function _searchBuilder($field, $fieldValue = NULL, $name = NULL, $op = '=', $count = NULL) {
    // search builder using contacts(not using contactType)
    $this->openCiviPage("contact/search/builder", "reset=1");
    $this->enterValues(1, 1, 'Contacts', $field, NULL, $op, "$fieldValue");
    $this->click("id=_qf_Builder_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    if (($op == '=' || $op == 'LIKE') && $fieldValue) {
      $this->assertElementContainsText('css=.crm-search-results > table.row-highlight', "$fieldValue");
    }
    if ($name) {
      $this->assertElementContainsText('css=.crm-search-results > table.row-highlight', "$name");
    }
    if ($count) {
      $this->assertElementContainsText('search-status', "$count Contact");
    }
  }

  /**
   * Enter form values in a Search Builder row
   */
  function enterValues($set, $row, $entity, $field, $loc, $op, $value = '') {
    if ($set > 1 && $row == 1) {
      $this->click('addBlock');
    }
    if ($row > 1) {
      $this->click("addMore_{$set}");
    }
    // In the DOM rows are 0 indexed and sets are 1 indexed, so normalizing
    $row--;

    $this->waitForElementPresent("mapper_{$set}_{$row}_0");
    $this->select("mapper_{$set}_{$row}_0", "label=$entity");
    $this->select("mapper_{$set}_{$row}_1", "label=$field");
    if ($loc) {
      $this->select("mapper_{$set}_{$row}_2", "label=$loc");
    }
    $this->select("operator_{$set}_{$row}", "label=$op");
    if (is_array($value)) {
      $this->waitForElementPresent("css=#crm_search_value_{$set}_{$row} select option + option");
      foreach ($value as $val) {
        if ($op != 'IN') {
          $select = 'select'; 
        }
        else {
          $select = 'addSelection';           
        }
        $this->$select("css=#crm_search_value_{$set}_{$row} select", "label=$val");
      }
    }
    elseif ($value && substr($value, 0, 5) == 'date:') {
      $this->webtestFillDate("value_{$set}_{$row}", trim(substr($value, 5)));
    }
    elseif ($value) {
      $this->type("value_{$set}_{$row}", $value);
    }
  }

  function _advancedSearch($fieldValue = NULL, $name = NULL, $contactType = NULL, $count = NULL, $field){
    //advanced search by selecting the contactType
    $this->openCiviPage("contact/search/advanced", "reset=1");
    if (isset($contactType)){
      $this->select("id=crmasmSelect0", "value=$contactType");
    }
    if (substr($field, 0, 5) == 'note_') {
      $this->click("notes");
      $this->waitForElementPresent("xpath=//div[@id='notes-search']/table/tbody/tr/td[2]/input[3]");
      if ($field == 'note_body') {
        $this->click("CIVICRM_QFID_2_note_option");
      }
      elseif ($field == 'note_subject') {
        $this->click("CIVICRM_QFID_3_note_option");
      }
      else {
        $this->click("CIVICRM_QFID_6_note_option");
      }
      $this->type("note",$fieldValue );
    }
    else {
      $this->click("location");
      $this->waitForElementPresent("xpath=//div[@id='location']/table/tbody/tr[2]/td/table/tbody/tr[4]/td[2]/select");
      if ($contactType == 'Individual') {
        $this->type("$field",$fieldValue );
      }
      else {
        $this->type("$field",$fieldValue);
      }
    }
    $this->click("_qf_Advanced_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //the search result should be same as the one that we got in search builder
    if ($fieldValue) {
      $this->assertElementContainsText('Advanced', "$fieldValue");
    }
    if ($name) {
      $this->assertElementContainsText('css=.crm-search-results > table.row-highlight', "$name");
    }
    if ($count) {
      $this->assertElementContainsText('search-status', "$count Contact");
    }
  }

  function _createContact($contactType, $name, $email, $streetName = NULL, $postalCode = NULL){
    $this->openCiviPage('contact/add', array('reset' => 1, 'ct' => $contactType), '_qf_Contact_cancel');

    if ($contactType == 'Individual'){
      $this->type("first_name", "$name");
      $this->type("last_name", "adv$name");
      $name = "$name adv$name";
    } elseif ($contactType == 'Organization') {
      $this->type("organization_name",$name);
    } else {
      $this->type("household_name",$name);
    }
    $this->click("//form[@id='Contact']/div[2]/div[4]/div[1]");
    $this->waitForElementPresent("address_1_geo_code_2");
    $this->type("email_1_email",$email);
    $this->type("phone_1_phone","9876543210");
    $this->type("address_1_street_address", $streetName);
    $this->select("address_1_country_id", "United States");
    $this->select("address_1_state_province_id", "Alaska");
    $this->type("address_1_postal_code",$postalCode);

    $this->click("//form[@id='Contact']/div[2]/div[6]/div[1]");
    $this->waitForElementPresent("note");
    $this->type("subject", "this is subject by $name");
    $this->type("note", "this is notes by $name");

    // save contact
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("$name has been created."));
  }

  /*
   * Webtest for CRM-12148
   *
   */
  function testSearchBuilderfinancialType() {
    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();
    
    // add financial type
    $financialTypeName1 = 'Financial Type' . substr(sha1(rand()), 0, 5);;
    $financialTypeName2 = 'Financial Type' . substr(sha1(rand()), 0, 5);;
    $financialType = array(
      'name' => $financialTypeName1,
      'is_reserved' => FALSE,
      'is_deductible' => FALSE,
    );
    $this->addeditFinancialType($financialType);
    $this->select('account_relationship', 'label=Income Account is');
    $this->select('financial_account_id', 'label=Discounts');
    $this->click('_qf_FinancialTypeAccount_next-botttom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $financialType['name'] = $financialTypeName2;
    $this->addeditFinancialType($financialType);
    $this->select('account_relationship', 'label=Income Account is');
    $this->select('financial_account_id', 'label=Discounts');
    $this->click('_qf_FinancialTypeAccount_next-botttom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    //create 6 contribution
    $this->openCiviPage("contribute/add", "reset=1&action=add&context=standalone", "_qf_Contribution_upload");
    for ($i = 1; $i <= 6; $i++) {
      if ($i % 2 == 0) {
        $financialType = $financialTypeName1;
      }
      else {
        $financialType = $financialTypeName2;
      }
      // create new contact using dialog
      $firstName = substr(sha1(rand()), 0, 7);
      $this->webtestNewDialogContact($firstName, 'Contributor', $firstName . '@example.com');
      $this->select('financial_type_id', $financialType);
      $this->type('total_amount', 100 * $i);
      $this->click('_qf_Contribution_upload_new');
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->waitForElementPresent('_qf_Contribution_upload_new');
    }
    $this->openCiviPage("contact/search/builder", "reset=1", "_qf_Builder_refresh");
      
    $this->enterValues(1, 1, 'Contribution', 'Financial Type', NULL, '=', array($financialTypeName1));
    $this->click('_qf_Builder_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    
    $this->assertTrue($this->isTextPresent('3 Contacts'), 'Missing text: ' . '3 Contacts');
      
    $this->click("xpath=//div[@class='crm-accordion-header crm-master-accordion-header']");
    $this->enterValues(1, 1, 'Contribution', 'Financial Type', NULL, '=', array($financialTypeName2));
    $this->click('_qf_Builder_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent('3 Contacts'), 'Missing text: ' . '3 Contacts');
      
    $this->click("xpath=//div[@class='crm-accordion-header crm-master-accordion-header']");
    $this->enterValues(1, 1, 'Contribution', 'Financial Type', NULL, 'IN', array($financialTypeName1, $financialTypeName2));
    $this->click('_qf_Builder_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent('6 Contacts'), 'Missing text: ' . '6 Contacts');
  }
}
