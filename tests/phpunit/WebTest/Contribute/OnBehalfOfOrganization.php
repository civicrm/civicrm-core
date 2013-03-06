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
class WebTest_Contribute_OnBehalfOfOrganization extends CiviSeleniumTestCase {
  protected $pageno = '';
  protected function setUp() {
    parent::setUp();
  }

  function testOnBehalfOfOrganization() {

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

    // create new individual
    $firstName     = 'John_' . substr(sha1(rand()), 0, 7);
    $lastName      = 'Anderson_' . substr(sha1(rand()), 0, 7);
    $email         = "{$firstName}.{$lastName}@example.com";
    $contactParams = array(
      'first_name' => $firstName,
      'last_name' => $lastName,
      'email-5' => $email,
    );
    $streetAddress = "100 Main Street";

    //adding contact for membership sign up
    $this->webtestAddContact($firstName, $lastName, $email);
    $urlElements = $this->parseURL();
    print_r($urlElements);
    $cid = $urlElements['queryString']['cid'];
    $this->assertType('numeric', $cid);

    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
    $processorType = 'Dummy';
    $pageTitle = substr(sha1(rand()), 0, 7);
    $rand = 100;
    $hash = substr(sha1(rand()), 0, 7);
    $amountSection = TRUE;
    $payLater = TRUE;
    $onBehalf = 'optional';
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = FALSE;
    $memPriceSetId = NULL;
    $friend = TRUE;
    $profilePreId = NULL;
    $profilePostId = NULL;
    $premiums = FALSE;
    $widget = FALSE;
    $pcp = FALSE;
    $honoreeSection = FALSE;
    $isAddPaymentProcessor = TRUE;
    $isPcpApprovalNeeded = FALSE;
    $isSeparatePayment = FALSE;

    // create a new online contribution page
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $pageTitle,
      array($processorName => $processorType),
      $amountSection,
      $payLater,
      $onBehalf,
      $pledges,
      $recurring,
      $memberships,
      $memPriceSetId,
      $friend,
      $profilePreId,
      $profilePostId,
      $premiums,
      $widget,
      $pcp,
      $isAddPaymentProcessor,
      $isPcpApprovalNeeded,
      $isSeparatePayment,
      $honoreeSection
    );

    //logout
    $this->open($this->sboxPath . "civicrm/logout?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    //$this->_testAnomoyousOganization($pageId, $cid, $pageTitle);
    $this->open($this->sboxPath . "civicrm/logout?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->_testUserWithOneRelationship($pageId, $cid, $pageTitle);
    $this->open($this->sboxPath . "civicrm/logout?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->_testUserWithMoreThanOneRelationship($pageId, $cid, $pageTitle);
  }

  function testOnBehalfOfOrganizationWithMembershipData() {
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

    // create new individual
    $this->open($this->sboxPath . "civicrm/profile/edit?reset=1&gid=4");
    $firstName     = 'John_x_' . substr(sha1(rand()), 0, 7);
    $lastName      = 'Anderson_c_' . substr(sha1(rand()), 0, 7);

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Edit_next");
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->click("_qf_Edit_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("profilewrap4");

    // Is status message correct?                                                                                                                                                                           
    $this->assertTextPresent("Thank you. Your information has been saved.", "Save successful status message didn't show up after saving profile to update testUserName!");

    //custom data
    // Go directly to the URL of the screen that you will be testing (New Custom Group).
    $this->open($this->sboxPath . "civicrm/admin/custom/group?action=add&reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //fill custom group title
    $customGroupTitle = 'custom_' . substr(sha1(rand()), 0, 7);
    $this->click("title");
    $this->type("title", $customGroupTitle);

    //custom group extends
    $this->click("extends[0]");
    $this->select("extends[0]", "value=Membership");
    //$this->click("//option[@value='Contact']");
    $this->click("_qf_Group_next-bottom");
    $this->waitForElementPresent("_qf_Field_cancel-bottom");

    //Is custom group created?
    $this->assertTrue($this->isTextPresent("Your custom field set '{$customGroupTitle}' has been added. You can add custom fields now."));

    //add custom field - alphanumeric checkbox
    $checkboxFieldLabel = 'custom_field' . substr(sha1(rand()), 0, 4);
    $this->click("label");
    $this->type("label", $checkboxFieldLabel);
    $this->click("data_type[1]");
    $this->select("data_type[1]", "value=CheckBox");
    $this->click("//option[@value='CheckBox']");
    $checkboxOptionLabel1 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_1", $checkboxOptionLabel1);
    $this->type("option_value_1", "1");
    $checkboxOptionLabel2 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_2", $checkboxOptionLabel2);
    $this->type("option_value_2", "2");
    $this->click("link=another choice");
    $checkboxOptionLabel3 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_3", $checkboxOptionLabel3);
    $this->type("option_value_3", "3");

    //enter options per line
    $this->type("options_per_line", "2");

    //enter pre help message
    $this->type("help_pre", "this is field pre help");

    //enter post help message
    $this->type("help_post", "this field post help");

    //Is searchable?
    $this->click("is_searchable");

    //clicking save
    $this->click("_qf_Field_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Is custom field created?
    $this->assertTrue($this->isTextPresent("Your custom field '$checkboxFieldLabel' has been saved."));

    //create another custom field - Integer Radio
    $this->click("//a[@id='newCustomField']/span");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("data_type[0]");
    $this->select("data_type[0]", "value=1");
    $this->click("//option[@value='1']");
    $this->click("data_type[1]");
    $this->select("data_type[1]", "value=Radio");
    $this->click("//option[@value='Radio']");

    $radioFieldLabel = 'custom_field' . substr(sha1(rand()), 0, 4);
    $this->type("label", $radioFieldLabel);
    $radioOptionLabel1 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_1", $radioOptionLabel1);
    $this->type("option_value_1", "1");
    $radioOptionLabel2 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_2", $radioOptionLabel2);
    $this->type("option_value_2", "2");
    $this->click("link=another choice");
    $radioOptionLabel3 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_3", $radioOptionLabel3);
    $this->type("option_value_3", "3");

    //select options per line
    $this->type("options_per_line", "3");

    //enter pre help msg
    $this->type("help_pre", "this is field pre help");

    //enter post help msg
    $this->type("help_post", "this is field post help");

    //Is searchable?
    $this->click("is_searchable");

    //clicking save
    $this->click("_qf_Field_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Is custom field created
    $this->assertTrue($this->isTextPresent("Your custom field '$radioFieldLabel' has been saved."));
    
    //add the above custom data to the On Behalf of Profile
    $this->open($this->sboxPath . "civicrm/admin/uf/group?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("link=Reserved Profiles");

    $this->click("xpath=//div[@id='reserved-profiles']/div/div/table/tbody//tr/td[1][text()='On Behalf Of Organization']/../td[5]/span/a[text()='Fields']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click("link=Add Field");
    $this->waitForElementPresent('_qf_Field_next-bottom');
    $this->select('field_name[0]', 'value=Membership');
    $label = $checkboxFieldLabel.' :: '. $customGroupTitle;
    $this->select('field_name[1]', "label=$label");
    $this->click('field_name[1]');
    $this->click('_qf_Field_next_new-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->select('field_name[0]', 'value=Membership');
    $label = $radioFieldLabel.' :: '. $customGroupTitle;
    $this->select('field_name[1]', "label=$label");
    $this->click('field_name[1]');
    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("Your CiviCRM Profile Field '{$radioFieldLabel}' has been saved to 'On Behalf Of Organization'."));

    //create organisation
    $orgName = "Org WebAccess ". substr(sha1(rand()), 0, 7);
    $orgEmail = "org". substr(sha1(rand()), 0, 7) . "@web.com";
    $this->webtestAddOrganization($orgName, $orgEmail);

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("css=li#tab_member a");

    $this->waitForElementPresent('link=Add Membership');
    $this->click('link=Add Membership');

    $this->waitForElementPresent('_qf_Membership_cancel-bottom');

    // fill in Membership Organization and Type                                                
    $this->select('membership_type_id[0]', "value=1");
    $this->select('membership_type_id[1]', "value=1");

    // fill in Source 
    $sourceText = 'On behalf Membership Webtest';
    $this->type('source', $sourceText);

    $this->waitForElementPresent("css=div#{$customGroupTitle} div.crm-accordion-header");
    $this->click("css=div#{$customGroupTitle} div.crm-accordion-header");
    //$this->waitForElementPresent('_qf_Membership_cancel-bottom111');

    // select newly created processor                       
    $xpath = "xpath=//label[text() = '{$checkboxOptionLabel1}']/preceding-sibling::input[1]";
    $this->assertTrue($this->isTextPresent($checkboxOptionLabel1));
    $this->check($xpath);
    
    $xpath = "xpath=//label[text() = '{$checkboxOptionLabel3}']/preceding-sibling::input[1]";
    $this->assertTrue($this->isTextPresent($checkboxOptionLabel3));
    $this->check($xpath);

    $xpath = "xpath=//label[text() = '{$radioOptionLabel1}']/preceding-sibling::input[1]";
    $this->assertTrue($this->isTextPresent($radioOptionLabel1));
    $this->check($xpath);

    $this->waitForElementPresent('_qf_Membership_cancel-bottom');
    $this->click('_qf_Membership_upload-bottom');


    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("css=li#tab_rel a");

    $this->waitForElementPresent('link=Add Relationship');
    $this->click('link=Add Relationship');

    $this->waitForElementPresent('relationship_type_id');
    $this->click("relationship_type_id");
    $this->select("relationship_type_id", "label=Employer of");
    // search organization           
    $this->type('contact_1', $firstName);
    $this->click("contact_1");
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");
    $this->assertContains($firstName, $this->getValue('contact_1'), "autocomplete expected $firstName but didn’t find it in " . $this->getValue('contact_1'));

    // give permission                                                
    $this->click("is_permission_a_b");
    $this->click("is_permission_b_a");

    // save relationship                     
    $this->click("details-save");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    
    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
    $processorType = 'Dummy';
    $pageTitle = substr(sha1(rand()), 0, 7);
    $rand = 100;
    $hash = substr(sha1(rand()), 0, 7);
    $amountSection = TRUE;
    $payLater = TRUE;
    $onBehalf = TRUE;
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = TRUE;
    $memPriceSetId = NULL;
    $friend = TRUE;
    $profilePreId = NULL;
    $profilePostId = NULL;
    $premiums = FALSE;
    $widget = FALSE;
    $pcp = FALSE;
    $honoreeSection = FALSE;
    $isAddPaymentProcessor = TRUE;
    $isPcpApprovalNeeded = FALSE;
    $isSeparatePayment = FALSE;

    // create a new online contribution page
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $pageTitle,
      array($processorName => $processorType),
      $amountSection,
      $payLater,
      $onBehalf,
      $pledges,
      $recurring,
      $memberships,
      $memPriceSetId,
      $friend,
      $profilePreId,
      $profilePostId,
      $premiums,
      $widget,
      $pcp,
      $isAddPaymentProcessor,
      $isPcpApprovalNeeded,
      $isSeparatePayment,
      $honoreeSection
    );
    
    //check for formRule
    //scenario 1 : add membership data in pre / post profile and check for formRule
    //add new profile 
    $this->open($this->sboxPath . "civicrm/admin/uf/group?reset=1");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('link=Add Profile');

    $profileTitle = "test profile" . substr(sha1(rand()), 0, 7);
    // Add membership custom data field to profile
    $this->waitForElementPresent('_qf_Group_cancel-bottom');
    $this->type('title', $profileTitle);
    $this->click('_qf_Group_next-bottom');

    $this->waitForElementPresent('_qf_Field_cancel-bottom');
    $this->assertTrue($this->isTextPresent("Your CiviCRM Profile '{$profileTitle}' has been added. You can add fields to this profile now."));

    $this->select('field_name[0]', "value=Membership");
    $this->select('field_name[1]', "label={$checkboxFieldLabel} :: {$customGroupTitle}");
    $this->click('field_name[1]');
    $this->click('label');

    // Clicking save and new
    $this->click('_qf_Field_next_new-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("Your CiviCRM Profile Field '{$checkboxFieldLabel}' has been saved to '{$profileTitle}'."));
    
    $this->open($this->sboxPath . "civicrm/admin/contribute/custom?reset=1&action=update&id={$pageId}"); 
    $this->waitForElementPresent('_qf_Custom_next-bottom');
    $this->select('custom_pre_id', "label={$profileTitle}");
    $this->click('_qf_Custom_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    sleep(3);
    $this->assertTrue($this->isTextPresent('You should move the membership related fields in the "On Behalf" profile for this Contribution Page'), "Form rule didn't showed up while incorrectly configuring membership fields profile for 'on behalf of' contribution page");
    
    $this->select('custom_pre_id', "- select -");
    $this->select('custom_post_id', "label={$profileTitle}");
    $this->click('_qf_Custom_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    sleep(3);
    $this->assertTrue($this->isTextPresent('You should move the membership related fields in the "On Behalf" profile for this Contribution Page'), "Form rule didn't showed up while incorrectly configuring membership fields profile for 'on behalf of' contribution page");
        
    //scenario 2 : disable 'on behalf of', add membership data in pre / post profile 
    //then try to add 'on behalf of' and check for formRule
    //disable 'on behalf of'
    $this->open($this->sboxPath . "civicrm/admin/contribute/settings?reset=1&action=update&id={$pageId}"); 
    $this->waitForElementPresent('_qf_Settings_next-bottom');
    $this->uncheck('is_organization');
    $this->click('_qf_Settings_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    
    //set a membership field profile for this contribution page
    $this->click('css=li#tab_custom a');
    $this->waitForElementPresent('_qf_Custom_next-bottom');
    $this->select('custom_pre_id', "label={$profileTitle}");
    $this->click('_qf_Custom_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //now visit the title settings page and configure the profile as on behalf of
    $this->click('css=li#tab_settings a');
    $this->waitForElementPresent('_qf_Settings_next-bottom');
    $this->check('is_organization');
    $this->click('_qf_Settings_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    sleep(3);    
    $this->assertTrue($this->isTextPresent("You should move the membership related fields configured in 'Includes Profile (top of page)' to the 'On Behalf' profile for this Contribution Page"), "Form rule 'You should move the membership related fields configured in 'Includes Profile (top of page)' to the 'On Behalf' profile for this Contribution Page' didn't showed up");
    
    //logout
    $this->open($this->sboxPath . "civicrm/logout?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  function testOnBehalfOfOrganizationWithOrgData()
  {

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

    $this->open($this->sboxPath . "civicrm/profile/edit?reset=1&gid=4");
    $firstName     = 'John_x_' . substr(sha1(rand()), 0, 7);
    $lastName      = 'Anderson_c_' . substr(sha1(rand()), 0, 7);

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Edit_next");
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->click("_qf_Edit_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("profilewrap4");

    $urlElements = $this->parseURL();
    print_r($urlElements);
    $cid = $urlElements['queryString']['id'];
    $this->assertType('numeric', $cid);
    // Is status message correct?                                                                                                                                                                          
    $this->assertTextPresent("Thank you. Your information has been saved.", "Save successful status message didn't show up after saving profile to update testUserName!");

    
    //add org fields to profile
    $this->open($this->sboxPath . "civicrm/admin/uf/group?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("link=Reserved Profiles");

    $this->click("xpath=//div[@id='reserved-profiles']/div/div/table/tbody//tr/td[1][text()='On Behalf Of Organization']/../td[5]/span/a[text()='Fields']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click("link=Add Field");
    $this->waitForElementPresent('_qf_Field_next-bottom');
    
    $this->select('field_name[0]', 'value=Organization');
    $this->select('field_name[1]', 'label=Legal Identifier');
    $this->click('field_name[1]');
    $this->click('_qf_Field_next_new-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->select('field_name[0]', 'value=Organization');
    $this->select('field_name[1]', 'label=Legal Name');
    $this->click('field_name[1]');
    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    

    //create organisation
    $orgName = "Org WebAccess ". substr(sha1(rand()), 0, 7);
    $orgEmail = "org". substr(sha1(rand()), 0, 7) . "@web.com";
    $this->webtestAddOrganization($orgName, $orgEmail);

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("css=li#tab_rel a");

    $this->waitForElementPresent('link=Add Relationship');
    $this->click('link=Add Relationship');

    $this->waitForElementPresent('relationship_type_id');
    $this->click("relationship_type_id");
    $this->select("relationship_type_id", "label=Employer of");
    // search organization                                                                                                                                                                                  
    $this->type('contact_1', $firstName);
    $this->click("contact_1");
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");
    $this->assertContains($firstName, $this->getValue('contact_1'), "autocomplete expected $firstName but didn’t find it in " . $this->getValue('contact_1'));

    // give permission                                                                                                                                                                                      
    $this->click("is_permission_a_b");
    $this->click("is_permission_b_a");

    // save relationship                                                                                                                                                                                    
    $this->waitForElementPresent("details-save");
    $this->click("details-save");
    $this->waitForElementPresent("Relationships");

    $pageTitle = substr(sha1(rand()), 0, 7);
    $rand = 100;
    $hash = substr(sha1(rand()), 0, 7);
    $amountSection = TRUE;
    $payLater = TRUE;
    $onBehalf = TRUE;
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = TRUE;
    $memPriceSetId = NULL;
    $friend = TRUE;
    $profilePreId = NULL;
    $profilePostId = NULL;
    $premiums = FALSE;
    $widget = FALSE;
    $pcp = FALSE;
    $honoreeSection = FALSE;
    $isAddPaymentProcessor = FALSE;
    $isPcpApprovalNeeded = FALSE;
    $isSeparatePayment = FALSE;

    // create a new online contribution page
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $pageTitle,
      null,
      $amountSection,
      $payLater,
      $onBehalf,
      $pledges,
      $recurring,
      $memberships,
      $memPriceSetId,
      $friend,
      $profilePreId,
      $profilePostId,
      $premiums,
      $widget,
      $pcp,
      $isAddPaymentProcessor,
      $isPcpApprovalNeeded,
      $isSeparatePayment,
      $honoreeSection
    );

     $this->_testOrganization($pageId, $cid, $pageTitle);  
  }


  function _testOrganization($pageId, $cid, $pageTitle) {
    //Open Live Contribution Page
    $this->open($this->sboxPath . "civicrm/contribute/transact?reset=1&id=" . $pageId);

    $this->waitForElementPresent("_qf_Main_upload-bottom");

    $this->waitForElementPresent("onbehalf_state_province-3");

    $this->waitForElementPresent("onbehalf_phone-3-1");
    $this->type("onbehalf_phone-3-1", 9999999999);
    $this->waitForElementPresent("onbehalf_email-3");
    $this->type("onbehalf_email-3", "org@example.com");
    $this->type("onbehalf_street_address-3", "Test Street Address");
    $this->type("onbehalf_city-3", "Test City");
    $this->type("onbehalf_postal_code-3", substr(sha1(rand()), 0, 6));
    $this->click("onbehalf_country-3");
    $this->select("onbehalf_country-3", "label=United States");
    $this->click("onbehalf_state_province-3");
    $this->select("onbehalf_state_province-3", "label=Alabama");

    $this->waitForElementPresent("_qf_Main_upload-bottom");
    $this->click("_qf_Main_upload-bottom");

    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("_qf_Confirm_next-bottom");

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

  }

  function _testAnomoyousOganization($pageId, $cid, $pageTitle) {
    //Open Live Contribution Page
    $this->open($this->sboxPath . "civicrm/contribute/transact?reset=1&id=" . $pageId);

    $this->waitForElementPresent("_qf_Main_upload-bottom");

    $this->click('CIVICRM_QFID_0_8');
    $this->type('css=div.other_amount-section input', 60);

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName  = 'An' . substr(sha1(rand()), 0, 7);
    $orgName   = 'org_11_' . substr(sha1(rand()), 0, 7);
    $this->type("email-5", $firstName . "@example.com");

    // enable onbehalforganization block
    $this->click("is_for_organization");
    $this->waitForElementPresent("onbehalf_state_province-3");

    // onbehalforganization info
    $this->type("onbehalf_organization_name", $orgName);
    $this->type("onbehalf_phone-3-1", 9999999999);
    $this->type("onbehalf_email-3", "{$orgName}@example.com");
    $this->type("onbehalf_street_address-3", "Test Street Address");
    $this->type("onbehalf_city-3", "Test City");
    $this->type("onbehalf_postal_code-3", substr(sha1(rand()), 0, 6));
    $this->click("onbehalf_country-3");
    $this->select("onbehalf_country-3", "label=United States");
    $this->click("onbehalf_state_province-3");
    $this->select("onbehalf_state_province-3", "label=Alabama");


    // Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");

    //Billing Info
    $this->type("billing_first_name", $firstName . 'billing');
    $this->type("billing_last_name", $lastName . 'billing');
    $this->type("billing_street_address-5", "0121 Mount Highschool.");
    $this->type(" billing_city-5", "Shangai");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");
    $this->click("_qf_Main_upload-bottom");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Confirm_next-bottom");

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //login to check contribution
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Contribution
    $this->open($this->sboxPath . "civicrm/contribute/search?reset=1");
    $this->type("sort_name", $orgName);
    $this->click("_qf_Search_refresh");

    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->click("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");

    // verify contrb created
    $expected = array(
      1 => $orgName,
      2 => 'Donation',
      10 => $pageTitle,
    );
    foreach ($expected as $value => $label) {
      $this->verifyText("xpath=id( 'ContributionView' )/div[2]/table[1]/tbody/tr[$value]/td[2]", preg_quote($label));
    }
  }

  function _testUserWithOneRelationship($pageId, $cid, $pageTitle) {
    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Create new group
    $groupName = $this->WebtestAddGroup();
    $this->open($this->sboxPath . "civicrm/group?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Search_refresh");
    $groupId = $this->getText("xpath=//table[@id='crm-group-selector']/tbody//tr/td[text()='{$groupName}']/../td[2]");

    $this->open($this->sboxPath . "civicrm/contact/view?reset=1&cid={$cid}");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click('link=Edit');
    $this->waitForElementPresent('_qf_Contact_cancel-bottom');
    $this->click('addressBlock');
    $this->waitForElementPresent('link=Another Address');

    //Billing Info
    $this->select('address_1_location_type_id', 'label=Billing');
    $this->type('address_1_street_address', '0121 Mount Highschool.');
    $this->type('address_1_city', "Shangai");
    $this->type('address_1_postal_code', "94129");
    $this->select('address_1_country_id', "value=1228");
    $this->select('address_1_state_province_id', "value=1004");
    $this->click('_qf_Contact_upload_view-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // add contact to group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // add to group
    $this->select("group_id", "label={$groupName}");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->open($this->sboxPath . "civicrm/admin/custom/group?action=add&reset=1");
    $this->waitForElementPresent("_qf_Group_next-bottom");

    // fill in a unique title for the custom group
    $groupTitle = "Custom Group" . substr(sha1(rand()), 0, 7);
    $this->type("title", $groupTitle);

    // select the group this custom data set extends
    $this->select("extends[0]", "value=Contribution");
    $this->waitForElementPresent("extends[1]");

    // save the custom group
    $this->click("_qf_Group_next-bottom");
    $this->waitForElementPresent("_qf_Field_next_new-bottom");
    $this->assertTrue($this->isTextPresent("Your custom field set '$groupTitle' has been added. You can add custom fields now."));

    // add a custom field to the custom group
    $fieldTitle = "Custom Field " . substr(sha1(rand()), 0, 7);
    $this->type("label", $fieldTitle);

    $this->select("data_type[1]", "value=Text");
    $this->click('_qf_Field_next-bottom');

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("Your custom field '$fieldTitle' has been saved."));
    $url = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']/div[2]/table/tbody//tr/td[1][text()='$fieldTitle']/../td[8]/span/a@href"));
    $fieldId = $url[1];

    // Enable CiviCampaign module if necessary
    $this->enableComponents("CiviCampaign");

    // add the required Drupal permission
    $permission = array('edit-2-administer-civicampaign');
    $this->changePermissions($permission);

    // Go directly to the URL of the screen that you will be add campaign
    $this->open($this->sboxPath . "civicrm/campaign/add?reset=1");

    // As mentioned before, waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.
    $this->waitForElementPresent("_qf_Campaign_upload-bottom");

    // Let's start filling the form with values.
    $title = 'Campaign ' . substr(sha1(rand()), 0, 7);
    $this->type("title", $title);

    // select the campaign type
    $this->select("campaign_type_id", "value=2");

    // fill in the description
    $this->type("description", "This is a test campaign");

    // include groups for the campaign
    $this->addSelection("includeGroups-f", "label={$groupName}");
    $this->click("//option[@value={$groupId}]");
    $this->click("add");

    // fill the end date for campaign
    $this->webtestFillDate("end_date", "+1 year");

    // select campaign status
    $this->select("status_id", "value=2");

    // click save
    $this->click("_qf_Campaign_upload-bottom");
    $this->waitForElementPresent("xpath=//div[@id='campaigns_wrapper']//table[@id='campaigns']/tbody//tr/td[3][text()='{$title}']");
    $this->assertTrue($this->isTextPresent("Campaign {$title} has been saved."), "Status message didn't show up after saving!");

    $this->open($this->sboxPath . "civicrm/admin/uf/group?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("link=Reserved Profiles");

    $this->click("xpath=//div[@id='reserved-profiles']/div/div/table/tbody//tr/td[1][text()='On Behalf Of Organization']/../td[5]/span/a[text()='Fields']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click("link=Add Field");
    $this->waitForElementPresent('_qf_Field_next-bottom');
    $this->select('field_name[0]', 'value=Contribution');
    $this->select('field_name[1]', 'label=Campaign');
    $this->click('field_name[1]');
    $this->click('_qf_Field_next_new-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->select('field_name[0]', 'value=Contribution');
    $this->select('field_name[1]', "label=$fieldTitle :: $groupTitle");
    $this->click('field_name[1]');
    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("Your CiviCRM Profile Field '{$fieldTitle}' has been saved to 'On Behalf Of Organization'."));

    // Open Page to create Organization
    $this->open($this->sboxPath . "civicrm/contact/add?reset=1&ct=Organization");
    $this->waitForElementPresent("_qf_Contact_upload_view-bottom");
    $orgName1 = 'org1_' . substr(sha1(rand()), 0, 7);

    // Type Organization name
    $this->type("organization_name", $orgName1);

    // Type Organizatio email for main
    $this->type("email_1_email", "{$orgName1}@example.com");
    $this->select("email_1_location_type_id", "value=3");

    // type phone no for main
    $this->type("phone_1_phone", 9999999999);
    $this->select("phone_1_location_type_id", "value=3");

    //address section
    $this->click("addressBlock");
    $this->waitForElementPresent("address_1_street_address");

    //fill in address 1 for main
    $this->select("address_1_location_type_id", "value=3");
    $this->type("address_1_street_address", "{$orgName1} street address");
    $this->type("address_1_city", "{$orgName1} city");
    $this->type("address_1_postal_code", substr(sha1(rand()), 0, 4));
    $this->assertTrue($this->isTextPresent("- select - United States"));
    $this->select("address_1_state_province_id", "value=1019");
    $this->type("address_1_geo_code_1", "1234");
    $this->type("address_1_geo_code_2", "5678");

    // Save the Organization
    $this->click("_qf_Contact_upload_view-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // open contact
    $this->open($this->sboxPath . "civicrm/contact/view/rel?cid={$cid}&action=add&reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // select relationship type
    $this->click("relationship_type_id");
    $this->select("relationship_type_id", "value=4_a_b");

    // search organization
    $this->type('contact_1', $orgName1);
    $this->click("contact_1");
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");
    $this->assertContains($orgName1, $this->getValue('contact_1'), "autocomplete expected $orgName1 but didn’t find it in " . $this->getValue('contact_1'));

    $this->waitForElementPresent("add_current_employer");
    $this->click("add_current_employer");

    // give permission
    $this->click("is_permission_a_b");
    $this->click("is_permission_b_a");

    // save relationship
    $this->waitForElementPresent("details-save");
    $this->click("details-save");

    //Open Live Contribution Page
    $this->open($this->sboxPath . "civicrm/contribute/transact?reset=1&id=" . $pageId . "&cid=" . $cid);
    $this->waitForElementPresent("onbehalf_state_province-3");
    $this->click('CIVICRM_QFID_amount_other_radio_4');
    $this->type('amount_other', 60);
    $this->click('onbehalf_contribution_campaign_id');
    $this->select('onbehalf_contribution_campaign_id', "label={$title}");
    $this->type("onbehalf_custom_{$fieldId}", 'Test Subject');

    // Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");

    //Billing Info
    $this->type("billing_street_address-5", "0121 Mount Highschool.");
    $this->type(" billing_city-5", "Shangai");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");

    $this->click("_qf_Main_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Find Contribution
    $this->open($this->sboxPath . "civicrm/contribute/search?reset=1");
    $this->type("sort_name", $orgName1);
    $this->click("_qf_Search_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->click("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");

    // verify contrb created
    $expected = array(
      1 => $orgName1,
      2 => 'Donation',
      10 => $title,
      11 => $pageTitle,
    );
    foreach ($expected as $value => $label) {
      $this->verifyText("xpath=id( 'ContributionView' )/div[2]/table[1]/tbody/tr[$value]/td[2]", preg_quote($label));
    }


    $this->open($this->sboxPath . "civicrm/admin/uf/group?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("link=Reserved Profiles");

    $this->click("xpath=//div[@id='reserved-profiles']/div/div/table/tbody//tr/td[1][text()='On Behalf Of Organization']/../td[5]/span/a[text()='Fields']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click("xpath=//div[@id='field_page']/div[3]/table/tbody//tr/td[1][text()='Campaign']/../td[9]/span[2][text()='more ']/ul/li[2]/a[text()='Delete']");
    $this->waitForElementPresent('_qf_Field_next-bottom');

    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent('Selected Profile Field has been deleted.'), "Status message didn't show up after saving!");

    $this->click("xpath=//div[@id='field_page']/div[3]/table/tbody//tr/td[1][text()='{$fieldTitle}']/../td[9]/span[2][text()='more ']/ul/li[2]/a[text()='Delete']");
    $this->waitForElementPresent('_qf_Field_next-bottom');

    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent('Selected Profile Field has been deleted.'), "Status message didn't show up after saving!");
  }

  function _testUserWithMoreThanOneRelationship($pageId, $cid, $pageTitle) {
    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Create new group
    $groupName = $this->WebtestAddGroup();
    $this->open($this->sboxPath . "civicrm/group?reset=1");
    $this->waitForElementPresent('_qf_Search_refresh');
    $this->click('_qf_Search_refresh');
    $this->waitForElementPresent("xpath=//div[@id='group']/div[3]/table/tbody//tr/td[text()='{$groupName}']/../td[2]");
    $groupId = $this->getText("xpath=//div[@id='group']/div[3]/table/tbody//tr/td[text()='{$groupName}']/../td[2]");

    $this->open($this->sboxPath . "civicrm/contact/view?reset=1&cid={$cid}");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click('link=Edit');
    $this->waitForElementPresent('_qf_Contact_cancel-bottom');
    $this->click('addressBlock');
    $this->waitForElementPresent('link=Another Address');

    //Billing Info
    $this->select('address_1_location_type_id', 'label=Billing');
    $this->type('address_1_street_address', '0121 Mount Highschool.');
    $this->type('address_1_city', "Shangai");
    $this->type('address_1_postal_code', "94129");
    $this->select('address_1_country_id', "value=1228");
    $this->select('address_1_state_province_id', "value=1004");
    $this->click('_qf_Contact_upload_view-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // add contact to group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // add to group
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->open($this->sboxPath . "civicrm/admin/custom/group?action=add&reset=1");
    $this->waitForElementPresent("_qf_Group_next-bottom");

    // fill in a unique title for the c$groupIdustom group
    $groupTitle = "Members Custom Group" . substr(sha1(rand()), 0, 7);
    $this->type("title", $groupTitle);

    // select the group this custom data set extends
    $this->select("extends[0]", "value=Membership");
    $this->waitForElementPresent("extends[1]");

    // save the custom group
    $this->click("_qf_Group_next-bottom");

    $this->waitForElementPresent("_qf_Field_next_new-bottom");
    $this->assertTrue($this->isTextPresent("Your custom field set '$groupTitle' has been added. You can add custom fields now."));

    // add a custom field to the custom group
    $fieldTitle = "Member Custom Field " . substr(sha1(rand()), 0, 7);
    $this->type("label", $fieldTitle);

    $this->select("data_type[1]", "value=Text");
    $this->click('_qf_Field_next-bottom');

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("Your custom field '$fieldTitle' has been saved."));
    $url = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']/div[2]/table/tbody//tr/td[1]/span[text()='$fieldTitle']/../td[8]/span/a@href"));
    $fieldId = $url[1];

    // Enable CiviCampaign module if necessary
    $this->enableComponents("CiviCampaign");

    // add the required Drupal permission
    $permission = array('edit-2-administer-civicampaign');
    $this->changePermissions($permission);

    // Go directly to the URL of the screen that you will be add campaign
    $this->open($this->sboxPath . "civicrm/campaign/add?reset=1");

    // As mentioned before, waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.
    $this->waitForElementPresent("_qf_Campaign_upload-bottom");

    // Let's start filling the form with values.
    $title = 'Campaign ' . substr(sha1(rand()), 0, 7);
    $this->type("title", $title);

    // select the campaign type
    $this->select("campaign_type_id", "value=2");

    // fill in the description
    $this->type("description", "This is a test campaign");

    // include groups for the campaign
    $this->addSelection("includeGroups-f", "label={$groupName}");
    $this->click("//option[@value={$groupId}]");
    $this->click("add");

    // fill the end date for campaign
    $this->webtestFillDate("end_date", "+1 year");

    // select campaign status
    $this->select("status_id", "value=2");

    // click save
    $this->click("_qf_Campaign_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("Campaign {$title} has been saved."), "Status message didn't show up after saving!");

    $this->open($this->sboxPath . "civicrm/admin/uf/group?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("link=Reserved Profiles");
    $this->click("xpath=//div[@id='reserved-profiles']/div/div/table/tbody//tr/td[1][text()='On Behalf Of Organization']/../td[5]/span/a[text()='Fields']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click("link=Add Field");
    $this->waitForElementPresent('_qf_Field_next-bottom');
    $this->select('field_name[0]', 'value=Membership');
    $this->select('field_name[1]', 'label=Campaign');
    $this->click('field_name[1]');
    $this->click('_qf_Field_next_new-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_Field_cancel-bottom');

    $this->select('field_name[0]', 'value=Membership');
    $this->select('field_name[1]', "label=$fieldTitle :: $groupTitle");
    $this->click('field_name[1]');
    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("Your CiviCRM Profile Field '{$fieldTitle}' has been saved to 'On Behalf Of Organization'."),
      "Status message didn't show up after saving!"
    );

    // Open Page to create Organization 1
    $this->open($this->sboxPath . "civicrm/contact/add?reset=1&ct=Organization");
    $this->waitForElementPresent("_qf_Contact_upload_view-bottom");
    $orgName1 = 'org1_' . substr(sha1(rand()), 0, 7);

    // Type Organization name
    $this->type("organization_name", $orgName1);

    // Type Organizatio email for main
    $this->type("email_1_email", "{$orgName1}@example.com");
    $this->select("email_1_location_type_id", "value=3");

    // type phone no for main
    $this->type("phone_1_phone", substr(sha1(rand()), 0, 4));
    $this->select("phone_1_location_type_id", "value=3");

    //address section
    $this->click("addressBlock");
    $this->waitForElementPresent("address_1_street_address");

    //fill in address 1 for main
    $this->select("address_1_location_type_id", "value=3");
    $this->type("address_1_street_address", "{$orgName1} street address");
    $this->type("address_1_city", "{$orgName1} city");
    $this->type("address_1_postal_code", "9999999999");
    $this->assertTrue($this->isTextPresent("- select - United States"));
    $this->select("address_1_state_province_id", "value=1019");
    $this->type("address_1_geo_code_1", "1234");
    $this->type("address_1_geo_code_2", "5678");

    // Save the Organization
    $this->click("_qf_Contact_upload_view-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // create second orzanization
    $this->open($this->sboxPath . "civicrm/contact/add?reset=1&ct=Organization");
    $this->waitForElementPresent("_qf_Contact_upload_view-bottom");
    $orgName2 = 'org2_' . substr(sha1(rand()), 0, 7);

    // Type Organization name
    $this->type("organization_name", $orgName2);

    // Type Organizatio email for main
    $this->type("email_1_email", "{$orgName2}@example.com");
    $this->select("email_1_location_type_id", "value=3");

    // type phone no for main
    $this->type("phone_1_phone", substr(sha1(rand()), 0, 4));
    $this->select("phone_1_location_type_id", "value=3");

    //address section
    $this->click("addressBlock");
    $this->waitForElementPresent("address_1_street_address");

    //fill in address 1 for main
    $this->select("address_1_location_type_id", "value=3");
    $this->type("address_1_street_address", "{$orgName2} street address");
    $this->type("address_1_city", "{$orgName2} city");
    $this->type("address_1_postal_code", "7777777777");
    $this->assertTrue($this->isTextPresent("- select - United States"));
    $this->select("address_1_state_province_id", "value=1019");
    $this->type("address_1_geo_code_1", "1224");
    $this->type("address_1_geo_code_2", "5628");

    // Save the Organization
    $this->click("_qf_Contact_upload_view-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());


    // create Membership type
    $title1 = "Membership Type" . substr(sha1(rand()), 0, 7);
    $this->open($this->sboxPath . "civicrm/admin/member/membershipType?reset=1&action=browse");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click("link=Add Membership Type");
    $this->waitForElementPresent('_qf_MembershipType_cancel-bottom');

    $this->type('name', $title1);
    $this->type('member_org', $orgName1);
    $this->click('_qf_MembershipType_refresh');
    $this->waitForElementPresent("xpath=//div[@id='membership_type_form']/fieldset/table[2]/tbody/tr[2]/td[2]");

    $this->type('minimum_fee', '50');

        $this->select( 'financial_type_id', 'value=2' );

    $this->type('duration_interval', 1);
    $this->select('duration_unit', "label=year");

    $this->select('period_type', "label=fixed");
    $this->waitForElementPresent('fixed_period_rollover_day[d]');

    $this->select('fixed_period_start_day[M]', 'value=4');
    $this->select('fixed_period_rollover_day[M]', 'value=1');

    $this->select('relationship_type_id', 'value=4_b_a');

    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');
    $this->assertTrue($this->isTextPresent("The membership type '$title1' has been saved."));
    $typeUrl = explode('&id=', $this->getAttribute("xpath=//div[@id='membership_type']/div[2]/table/tbody//tr/td[1][text()='{$title1}']/../td[10]/span/a[3]@href"));
    $typeId = $typeUrl[1];

    // open contact
    $this->open($this->sboxPath . "civicrm/contact/view/rel?cid={$cid}&action=add&reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // select relationship type
    $this->click("relationship_type_id");
    $this->select("relationship_type_id", "value=4_a_b");

    // search organization
    $this->type('contact_1', $orgName1);
    $this->click("contact_1");
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");
    $this->assertContains($orgName1, $this->getValue('contact_1'), "autocomplete expected $orgName1 but didn’t find it in " . $this->getValue('contact_1'));

    // give permission
    $this->click("is_permission_a_b");
    $this->click("is_permission_b_a");

    // save relationship
    $this->click("details-save");

    // open contact
    $this->open($this->sboxPath . "civicrm/contact/view/rel?cid={$cid}&action=add&reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // select relationship type
    $this->click("relationship_type_id");
    $this->select("relationship_type_id", "value=4_a_b");

    // search organization
    $this->type('contact_1', $orgName2);
    $this->click("contact_1");
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");
    $this->assertContains($orgName2, $this->getValue('contact_1'), "autocomplete expected $orgName2 but didn’t find it in " . $this->getValue('contact_1'));

    // give permission
    $this->click("is_permission_a_b");
    $this->click("is_permission_b_a");

    // save relationship
    $this->click("details-save");

    // set membership type
    $this->open($this->sboxPath . "civicrm/admin/contribute/membership?reset=1&action=update&id=" . $pageId);
    $this->waitForElementPresent("_qf_MembershipBlock_upload_done-bottom");
    $this->click("member_is_active");
    $this->click("membership_type[{$typeId}]");
    $this->click("xpath=//div[@id='memberFields']//table[@class='report']/tbody//tr/td[1]/label[text()='{$title1}']/../../td[2]/input");
    $this->click('_qf_MembershipBlock_upload_done-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Open Live Membership Page
    $this->open($this->sboxPath . "civicrm/contribute/transact?reset=1&id=" . $pageId . "&cid=" . $cid);
    $this->waitForElementPresent("_qf_Main_upload-bottom");
    $this->click('CIVICRM_QFID_amount_other_radio_4');
    $this->type('amount_other', 60);
    $this->click('onbehalf_organization_name');
    $this->type('onbehalf_organization_name', $orgName1);
    $this->typeKeys('onbehalf_organization_name', $orgName1);
    $this->click("onbehalf_organization_name");
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");
    sleep(5);
    $this->click('onbehalf_member_campaign_id');
    $this->select('onbehalf_member_campaign_id', "label={$title}");
    $this->type("onbehalf_custom_{$fieldId}", 'Test Subject');

    $this->assertContains($orgName1, $this->getValue('onbehalf_organization_name'), "autocomplete expected $orgName1 but didn’t find it in " . $this->getValue('onbehalf_organization_name'));

    // Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");

    $this->click("_qf_Main_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Find Membership for organization
    $this->open($this->sboxPath . "civicrm/member/search?reset=1");
    $this->type("sort_name", $orgName1);
    $this->click("_qf_Search_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("xpath=//div[@id='memberSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->click("xpath=//div[@id='memberSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    //verify contrb created
    $expected = array(
      1 => $orgName1,
      2 => $title1,
      3 => 'New',
    );
    foreach ($expected as $value => $label) {
      $this->verifyText("xpath=//form[@id='MembershipView']/div[2]/div/table/tbody/tr[$value]/td[2]", preg_quote($label));
    }

    // find membership for contact in relationship
    $this->open($this->sboxPath . "civicrm/contact/view?reset=1&force=1&cid={$cid}");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("css=li#tab_member a");
    $this->waitForElementPresent("xpath=//div[@id='memberships']/div/table//tbody//tr/td[1][text()='{$title1}']");
    $this->click("xpath=//div[@id='memberships']/div/table//tbody//tr/td[1][text()='{$title1}']/../td[7]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //verify contrb created
    $expected = array(
      3 => $title1,
      4 => 'New',
    );
    foreach ($expected as $value => $label) {
      $this->verifyText("xpath=//form[@id='MembershipView']/div[2]/div/table/tbody/tr[$value]/td[2]", preg_quote($label));
    }

    $this->open($this->sboxPath . "civicrm/admin/uf/group?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("link=Reserved Profiles");
    $this->click("xpath=//div[@id='reserved-profiles']/div/div/table/tbody//tr/td[1][text()='On Behalf Of Organization']/../td[5]/span/a[text()='Fields']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click("xpath=//div[@id='field_page']/div[3]/table/tbody//tr/td[1][text()='Campaign']/../td[9]/span[2][text()='more ']/ul/li[2]/a[text()='Delete']");
    $this->waitForElementPresent('_qf_Field_next-bottom');

    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent('Selected Profile Field has been deleted.'),
      "Status message didn't show up after saving!"
    );

    $this->click("xpath=//div[@id='field_page']/div[3]/table/tbody//tr/td[1][text()='{$fieldTitle}']/../td[9]/span[2][text()='more ']/ul/li[2]/a[text()='Delete']");
    $this->waitForElementPresent('_qf_Field_next-bottom');

    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent('Selected Profile Field has been deleted.'),
      "Status message didn't show up after saving!"
    );

    $this->open($this->sboxPath . "civicrm/contact/view?reset=1&cid={$cid}");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("css=li#tab_rel a");

    $this->waitForElementPresent("xpath=//div[@id='current-relationships']/div/table/tbody//tr/td[2]/a[text()='{$orgName1}']");
    $this->click("xpath=//div[@id='current-relationships']/div/table/tbody//tr/td[2]/a[text()='{$orgName1}']/../../td[9]/span[2][text()='more ']/ul/li[2]/a[text()='Delete']");

    // Check confirmation alert.
    $this->assertTrue((bool)preg_match("/^Are you sure you want to delete this relationship?/",
        $this->getConfirmation()
      ));
    $this->chooseOkOnNextConfirmation();
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent('Selected relationship has been deleted successfully.'),
      "Status message didn't show up after saving!"
    );
  }


  function testOnBehalfOfOrganizationWithImage() {
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
    
    $this->open($this->sboxPath . "civicrm/profile/edit?reset=1&gid=4");
    $firstName     = 'John_x_' . substr(sha1(rand()), 0, 7);
    $lastName      = 'Anderson_c_' . substr(sha1(rand()), 0, 7);
    
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Edit_next");
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->click("_qf_Edit_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("profilewrap4");
    
    $urlElements = $this->parseURL();
    $cid = $urlElements['queryString']['id'];
    $this->assertType('numeric', $cid);
    // Is status message correct?                                                                                                                                                                          
    $this->assertTextPresent("Thank you. Your information has been saved.", "Save successful status message didn't show up after saving profile to update testUserName!");

    $this->open($this->sboxPath . "civicrm/admin/uf/group?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("link=Reserved Profiles");
    
    $this->click("xpath=//div[@id='reserved-profiles']/div/div/table/tbody//tr/td[1][text()='On Behalf Of Organization']/../td[5]/span/a[text()='Fields']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
      
    $this->click("link=Add Field");
    $this->waitForElementPresent('_qf_Field_next-bottom');

    $this->select('field_name[0]', 'value=Contact');
    $this->select('field_name[1]', 'label=Image Url');
    $this->click('field_name[1]');
    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
    $processorType = 'Dummy';
    $pageTitle = substr(sha1(rand()), 0, 7);
    $rand = 100;
    $hash = substr(sha1(rand()), 0, 7);
    $amountSection = TRUE;
    $payLater = TRUE;
    $onBehalf = TRUE;
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = FALSE;
    $memPriceSetId = NULL;
    $friend = TRUE;
    $profilePreId = NULL;
    $profilePostId = NULL;
    $premiums = FALSE;
    $widget = FALSE;
    $pcp = FALSE;
    $honoreeSection = FALSE;
    $isAddPaymentProcessor = TRUE;
    $isPcpApprovalNeeded = FALSE;
    $isSeparatePayment = FALSE;

    // create a new online contribution page
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $pageTitle,
      array($processorName => $processorType),
      $amountSection,
      $payLater,
      $onBehalf,
      $pledges,
      $recurring,
      $memberships,
      $memPriceSetId,
      $friend,
      $profilePreId,
      $profilePostId,
      $premiums,
      $widget,
      $pcp,
      $isAddPaymentProcessor,
      $isPcpApprovalNeeded,
      $isSeparatePayment,
      $honoreeSection
    );

    $this->_testOrganizationWithImageUpload($pageId, $cid, $pageTitle);  
  
    $this->open($this->sboxPath . "civicrm/admin/uf/group?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("link=Reserved Profiles");
      
    $this->click("xpath=//div[@id='reserved-profiles']/div/div/table/tbody//tr/td[1][text()='On Behalf Of Organization']/../td[5]/span/a[text()='Fields']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("xpath=//table[@id='option11']/tbody//tr/td/span[text()='Image Url']/../following-sibling::td[8]/span[2]/ul/li[2]/a");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "Selected Profile Field has been deleted.");
  }

  function _testOrganizationWithImageUpload($pageId, $cid, $pageTitle) {
    //Open Live Contribution Page
    $this->open($this->sboxPath . "civicrm/contribute/transact?reset=1&id=" . $pageId);

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName  = 'An' . substr(sha1(rand()), 0, 7);
    $orgName   = 'org_11_' . substr(sha1(rand()), 0, 7);
    $this->type("email-5", $firstName . "@example.com");

    // onbehalforganization info
    $this->type("onbehalf_organization_name", $orgName);
    $this->type("onbehalf_phone-3-1", 9999999999);
    $this->type("onbehalf_email-3", "{$orgName}@example.com");
    $this->type("onbehalf_street_address-3", "Test Street Address");
    $this->type("onbehalf_city-3", "Test City");
    $this->type("onbehalf_postal_code-3", substr(sha1(rand()), 0, 6));
    $this->click("onbehalf_country-3");
    $this->select("onbehalf_country-3", "label=United States");
    $this->click("onbehalf_state_province-3");
    $this->select("onbehalf_state_province-3", "label=Alabama");

    // check for upload field.
    $this->waitForElementPresent("onbehalf_image_URL");

    //header("Content-Type: image/png");
    $im = imagecreate(110, 20)
      or die("Cannot Initialize new GD image stream");
    $background_color = imagecolorallocate($im, 0, 0, 0);
    $text_color = imagecolorallocate($im, 233, 14, 91);
    imagestring($im, 1, 5, 5,  "On Behalf-Org Logo", $text_color);
    imagepng($im,"/tmp/file.png");
      
    $imagePath = "/tmp/file.png";
    $this->webtestAttachFile('onbehalf_image_URL', $imagePath);
    unlink($imagePath);

    // Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");

    //Billing Info
    $this->type("billing_first_name", $firstName . 'billing');
    $this->type("billing_last_name", $lastName . 'billing');
    $this->type("billing_street_address-5", "0121 Mount Highschool.");
    $this->type(" billing_city-5", "Shangai");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");
    $this->click("_qf_Main_upload-bottom");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Confirm_next-bottom");

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Type search name in autocomplete.
    $this->click('sort_name_navigation');
    $this->type('css=input#sort_name_navigation', $orgName);
    $this->typeKeys('css=input#sort_name_navigation', $orgName);

    // Wait for result list.
    $this->waitForElementPresent("css=div.ac_results-inner li");
      
    // Visit organization page.
    $this->click("css=div.ac_results-inner li");
    $this->waitForPageToLoad($this->getTimeoutMsec());
      
    //check whether the image is present
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='crm-contact-thumbnail']/div/a/img"));
  }
}

