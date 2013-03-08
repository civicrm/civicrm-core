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
class WebTest_Campaign_SurveyUsageScenarioTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testSurveyUsageScenario() {
    $this->webtestLogin();

    // Create new group
    $title = substr(sha1(rand()), 0, 7);
    $groupName = $this->WebtestAddGroup();

    // Adding contact
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName1, "Smith", "$firstName1.smith@example.org");

    // add contact to group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // add to group
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName2, "John", "$firstName2.john@example.org");

    // add contact to group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // add to group
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Enable CiviCampaign module if necessary
    $this->enableComponents(array('CiviCampaign'));

    // add the required Drupal permission
    $this->changePermissions(array('edit-2-administer-civicampaign'));

    // Go directly to the URL of the screen that you will be testing
    $this->openCiviPage("campaign/add", "reset=1", "_qf_Campaign_upload-bottom");

    // Let's start filling the form with values.
    $this->type("title", "Campaign $title");

    // select the campaign type
    $this->select("campaign_type_id", "value=2");

    // fill in the description
    $this->type("description", "This is a test campaign");

    // include groups for the campaign
    $this->addSelection("includeGroups-f", "label=$groupName");
    $this->click("//option[@value=4]");
    $this->click("add");

    // fill the end date for campaign
    $this->webtestFillDate("end_date", "+1 year");

    // select campaign status
    $this->select("status_id", "value=2");

    // click save
    $this->click("_qf_Campaign_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertElementContainsText("crm-notification-container", "$title");

    // create a custom data set for activities -> survey
    $this->openCiviPage('admin/custom/group', "action=add&reset=1", "_qf_Group_next-bottom");
    // fill in a unique title for the custom group
    $this->type("title", "Group $title");

    // select the group this custom data set extends
    $this->select("extends[0]", "value=Activity");
    $this->waitForElementPresent("//select[@id='extends_1']");
    $this->select("//select[@id='extends_1']", "label=Survey");

    // save the custom group
    $this->click("_qf_Group_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // add a custom field to the custom group
    $this->type("label", "Field $title");

    $this->select("data_type[1]", "value=Radio");

    $this->waitForElementPresent("option_label_1");

    // create a set of options
    $this->type("option_label_1", "Option $title 1");
    $this->type("option_value_1", "1");

    $this->type("option_label_2", "Option $title 2");
    $this->type("option_value_2", "2");

    // save the custom field
    $this->click("_qf_Field_next-bottom");

    $this->waitForElementPresent("newCustomField");
    $this->assertElementContainsText("crm-notification-container", "$title");

    // create a profile for campaign
    $this->openCiviPage("admin/uf/group/add", "action=add&reset=1", "_qf_Group_next-bottom");

    // fill in a unique title for the profile
    $this->type("title", "Profile $title");

    // save the profile
    $this->click("_qf_Group_next-bottom");

    $this->waitForElementPresent("_qf_Field_next-bottom");
    $this->assertElementContainsText("crm-notification-container", "$title");

    // add a profile field for activity
    $this->select("field_name[0]", "value=Activity");
    $this->waitForElementPresent("field_name[1]");
    $this->select("field_name[1]", "label=Field $title :: Group $title");

    $this->click("_qf_Field_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText("crm-notification-container", "$title");

    // create a survey
    $this->openCiviPage("survey/add", "reset=1", "_qf_Main_upload-bottom");

    // fill in a unique title for the survey
    $this->type("title", "Survey $title");

    // select the created campaign
    $this->select("campaign_id", "label=Campaign $title");

    // select the activity type
    $this->select("activity_type_id", "label=Survey");

    // fill in reserve survey respondents
    $this->type("default_number_of_contacts", 50);

    // fill in interview survey respondents
    $this->type("max_number_of_contacts", 100);

    // release frequency
    $this->type("release_frequency", 2);

    $this->click("_qf_Main_upload-bottom");
    $this->waitForElementPresent("_qf_Questions_upload_next-bottom");
    
    // Select the profile for the survey
    $this->select("//form[@id='Questions']/div[2]/table/tbody/tr[1]/td[2]/div/div/span/select", "label=New Individual");

    // select the question created for the survey 
    $this->select("//form[@id='Questions']/div[2]/table/tbody/tr[2]/td[2]/div/div/span/select", "label=Profile $title");
    $this->click("_qf_Questions_upload_next-bottom");

    // create a set of options for Survey Responses _qf_Results_upload_done-bottom
    $this->waitForElementPresent('_qf_Results_upload_done-bottom');
    $this->type("//input[@id='option_label_1']", "Label $title 1");
    $this->type("//input[@id='option_value_1']", "1");

    $this->type("//input[@id='option_label_2']", "Label $title 2");
    $this->type("//input[@id='option_value_2']", "2");
    $this->click('_qf_Results_upload_done-bottom');
    $this->waitForElementPresent("//div[@id='search_form_survey']");
    $this->assertElementContainsText("crm-notification-container", "Results");

    // Reserve Respondents
    $this->openCiviPage("survey/search", "reset=1&op=reserve", "_qf_Search_refresh");

    // search for the respondents
    $this->select("campaign_survey_id", "label=Survey $title");

    $this->click("_qf_Search_refresh");

    $this->waitForElementPresent("Go");
    $this->click("CIVICRM_QFID_ts_all_4");
    $this->click("Go");

    $this->waitForElementPresent("_qf_Reserve_done_reserve-bottom");
    $this->click("_qf_Reserve_done_reserve-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText("crm-notification-container", "2");

    // Interview Respondents
    $this->openCiviPage("survey/search", "reset=1&op=interview", "_qf_Search_refresh");

    // search for the respondents
    $this->select("campaign_survey_id", "label=Survey $title");

    $this->click("_qf_Search_refresh");

    $this->waitForElementPresent("Go");
    $this->click("CIVICRM_QFID_ts_all_4");
    $this->click("Go");

    $this->waitForElementPresent("_qf_Interview_cancel_interview");

    $this->click("CIVICRM_QFID_1_2");
    $this->select("css=#voterRecords .odd .result select", "value=Label $title 1");
    $this->click("css=#voterRecords .odd td a");

    $this->click("CIVICRM_QFID_2_8");
    $this->select("css=#voterRecords .even .result select", "value=Label $title 2");
    $this->click("css=#voterRecords .even td a");

    $this->click("_qf_Interview_cancel_interview");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // add a contact to the group to test release respondents
    $firstName3 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName3, "James", "$firstName3.james@example.org");
    $url       = $this->getLocation();
    $id        = explode('cid=', $url);
    $sortName3 = "James, $firstName3";

    // add contact to group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");

    // add to group
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Reserve Respondents
    $this->openCiviPage("survey/search", "reset=1&op=reserve", "_qf_Search_refresh");

    // search for the respondents
    $this->select("campaign_survey_id", "label=Survey $title");

    $this->click("_qf_Search_refresh");

    $this->waitForElementPresent("Go");
    $this->click("CIVICRM_QFID_ts_all_4");
    $this->click("Go");

    $this->waitForElementPresent("_qf_Reserve_done_reserve-bottom");
    $this->click("_qf_Reserve_done_reserve-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText("crm-notification-container", "Contact(s) have been reserved");

    // Release Respondents
    $this->openCiviPage("survey/search", "reset=1&op=release", "_qf_Search_refresh");

    // search for the respondents
    $this->select("campaign_survey_id", "label=Survey $title");

    $this->click("_qf_Search_refresh");

    $this->waitForElementPresent("Go");
    $this->click("xpath=id('mark_x_$id[1]')");

    $this->waitForElementPresent("Go");
    $this->click("Go");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("_qf_Release_done-bottom");
    $this->click("_qf_Release_done-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    // wait for Access Keys div to appear at bottom of page - since this page may take a while
    $this->waitForElementPresent('access');
    $this->assertElementContainsText("crm-notification-container", "released");

    // check whether contact is available for reserving again
    $this->openCiviPage("survey/search", "reset=1&op=reserve", "_qf_Search_refresh");

    // search for the respondents
    $this->select("campaign_survey_id", "label=Survey $title");

    $this->click("_qf_Search_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("1 Result"), "Result didn't show up after saving!");
  }

  function testSurveyReportTest() {
    $this->webtestLogin();

    // Create new group
    $title = substr(sha1(rand()), 0, 7);
    $groupName = $this->WebtestAddGroup();

    // Adding contact
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName1, "Smith", "$firstName1.smith@example.org");
    $url1 = explode('cid=', $this->getLocation());
    $id1 = $url1[1];

    // add contact to group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent('group_id');

    // add to group
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName2, "John", "$firstName2.john@example.org");
    $url2 = explode('cid=', $this->getLocation());
    $id2 = $url2[1];

    // add contact to group
    // visit group tab
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent('group_id');

    // add to group
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Create custom group and add custom data fields
    $this->openCiviPage("admin/custom/group", "reset=1");

    $this->click("link=Add Set of Custom Fields");
    $this->waitForElementPresent('_qf_Group_cancel-bottom');

    $customGroup = "Custom Group $title";
    $this->type('title', "$customGroup");
    $this->select('extends[0]', "value=Contact");
    $this->click('_qf_Group_next-bottom');
    $this->waitForElementPresent('_qf_Field_cancel-bottom');
    $this->assertElementContainsText("crm-notification-container", $customGroup);

    // Add custom fields
    $field1 = "Checkbox $title";
    $this->type('label', $field1);
    $this->select('data_type[1]', "value=CheckBox");
    $this->waitForElementPresent('option_label_2');

    // add multiple choice options
    $label1 = "Check $title One";
    $value1 = 1;
    $this->type('option_label_1', $label1);
    $this->type('option_value_1', $value1);

    $label2 = "Check $title Two";
    $value2 = 2;
    $this->type('option_label_2', $label2);
    $this->type('option_value_2', $value2);

    $this->click("link=another choice");

    $label3 = "Check $title Three";
    $value3 = 3;
    $this->type('option_label_3', $label3);
    $this->type('option_value_3', $value3);

    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText("crm-notification-container", $field1);

    // Create a profile for survey
    $this->openCiviPage("admin/uf/group", "reset=1");

    $this->click("link=Add Profile");
    $this->waitForElementPresent('_qf_Group_cancel-bottom');

    $surveyProfile = "Survey Profile $title";
    $this->type('title', $surveyProfile);
    $this->click('_qf_Group_next-bottom');
    $this->waitForElementPresent('_qf_Field_cancel-bottom');
    $this->assertElementContainsText("crm-notification-container", $surveyProfile);

    // Add fields to the profile
    // Phone ( Primary )
    $this->select('field_name[0]', "value=Contact");
    $this->select('field_name[1]', "value=phone");
    $this->click('field_name[1]');
    $this->select('visibility', "value=Public Pages and Listings");
    $this->check('is_searchable');
    $this->check('in_selector');
    $this->click('_qf_Field_next_new-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_Field_cancel-bottom');
    
    // Custom Data Fields
    $this->select('field_name[0]', "value=Contact");
    $this->select('field_name[1]', "label=$field1 :: $customGroup");
    $this->click('field_name[1]');
    $this->select('visibility', "value=Public Pages and Listings");
    $this->check('is_searchable');
    $this->check('in_selector');
    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Enable CiviCampaign module if necessary
    $this->enableComponents(array('CiviCampaign'));

    // add the required Drupal permission
    $permissions = array('edit-2-administer-civicampaign');
    $this->changePermissions($permissions);

    // Create a survey
    $this->openCiviPage("survey/add", "reset=1", "_qf_Main_upload-bottom");

    // fill in a unique title for the survey
    $surveyTitle = "Survey $title";
    $this->type('title', $surveyTitle);

    // select the created campaign
    //$this->select("campaign_id", "label=Campaign $title");

    // select the activity type
    $this->select("activity_type_id", "label=Survey");

    // fill in reserve survey respondents
    $this->type("default_number_of_contacts", 50);

    // fill in interview survey respondents
    $this->type("max_number_of_contacts", 100);

    // release frequency
    $this->type("release_frequency", 2);

    $this->click("_qf_Main_upload-bottom");
    $this->waitForElementPresent("_qf_Questions_upload_next-bottom");
    
    //Select the profile for the survey
    $this->select("//form[@id='Questions']/div[2]/table/tbody/tr[1]/td[2]/div/div/span/select", "label=New Individual");

    // select the question created for the survey 
    $this->select("//form[@id='Questions']/div[2]/table/tbody/tr[2]/td[2]/div/div/span/select", "label=$surveyProfile");
    $this->click("_qf_Questions_upload_next-bottom");

    // create a set of options for Survey Responses _qf_Results_upload_done-bottom
    $this->waitForElementPresent('_qf_Results_upload_done-bottom');
    $optionLabel1 = "Label $title 1";
    $this->type("//input[@id='option_label_1']", "$optionLabel1");
    $this->type("//input[@id='option_value_1']", "1");

    $optionLabel2 = "Label $title 2";
    $this->type("//input[@id='option_label_2']", "$optionLabel2");
    $this->type("//input[@id='option_value_2']", "2");
    $this->type("//input[@id='report_title']", "Survey $title");
    $this->click('_qf_Results_upload_done-bottom');
    $this->waitForElementPresent("//div[@id='search_form_survey']");
    $this->assertTrue($this->isTextPresent("'Results' have been saved."),
      "Status message didn't show up after saving survey!"
    );

    // Reserve Respondents
    $this->openCiviPage("survey/search", "reset=1&op=reserve", '_qf_Search_refresh');

    // search for the respondents
    // select survey
    $this->select('campaign_survey_id', "label=$surveyTitle");

    // need to wait for Groups field to reload dynamically
    $this->waitForElementPresent("//select[@class='campaignGroupsSelect']/option[text()='$groupName']");

    // select group
    $this->click('campaignGroupsSelect1');
    $this->select('campaignGroupsSelect1', "label=$groupName");
    $this->click('_qf_Search_refresh');

    $this->waitForElementPresent('_qf_Search_next_print');
    $this->click("CIVICRM_QFID_ts_all_4");
    $this->click("Go");
    $this->waitForElementPresent('_qf_Reserve_done_reserve-bottom');

    $this->click('_qf_Reserve_done_reserve-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    // wait for Access Keys div to appear at bottom of page - since this page may take a while
    $this->waitForElementPresent('access');
    $this->assertElementContainsText("crm-notification-container", "2");

    $this->openCiviPage("report/survey/detail", "reset=1", '_qf_SurveyDetails_submit');

    // Select columns to be displayed
    $this->check('fields[survey_id]');
    $this->check('fields[survey_response]');
    $this->select('survey_id_value', "label=$surveyTitle");
    $this->select('status_id_value', "label=Reserved");
    $this->click('_qf_SurveyDetails_submit');
    $this->waitForElementPresent('_qf_SurveyDetails_submit_print');
    $this->assertTrue($this->isTextPresent("Is equal to Reserved"));

    // commenting out the print assertion as print dialog which appears breaks the webtest
    // as it is OS-related and cannot be handled through webtest 

    // $this->click('_qf_SurveyDetails_submit_print');
    // $this->waitForPageToLoad($this->getTimeoutMsec());

    // $this->assertTrue($this->isTextPresent("Survey Title = $surveyTitle"));
    // $this->assertTrue($this->isTextPresent("Q1 = $field1"));
    // $this->assertTrue($this->isTextPresent("$value1 | $value2 | $value3"));

    // Interview Respondents
    $this->openCiviPage("survey/search", "reset=1&op=interview", '_qf_Search_refresh');

    // search for the respondents
    // select survey
    $this->select('campaign_survey_id', "label=$surveyTitle");

    // need to wait for Groups field to reload dynamically
    $this->waitForElementPresent("//select[@class='campaignGroupsSelect']/option[text()='$groupName']");

    // select group
    $this->click('campaignGroupsSelect1');
    $this->select('campaignGroupsSelect1', "label=$groupName");
    $this->waitForElementPresent("xpath=//ul[@id='crmasmList1']/li");
    $this->click('_qf_Search_refresh');

    $this->waitForElementPresent('_qf_Search_next_print');
    $this->click("xpath=//table[@class='selector']/tbody//tr[@id='rowid{$id1}']/td[1]");
    $this->click("mark_x_{$id1}");
    $this->click("Go");
    $this->waitForElementPresent('_qf_Interview_cancel_interview');

    $this->type("field_{$id1}_phone-Primary-1", 9876543210);
    $this->click("//table[@id='voterRecords']/tbody//tr[@id='row_{$id1}']/td[5]/input[2]/../label[text()='$label1']");
    $this->click("//table[@id='voterRecords']/tbody//tr[@id='row_{$id1}']/td[5]/input[6]/../label[text()='$label2']");
    $this->select("field_{$id1}_result", $optionLabel1);
    $this->click("interview_voter_button_{$id1}");
    sleep(3);
    // Survey Report
    $this->openCiviPage("report/survey/detail", "reset=1", '_qf_SurveyDetails_submit');

    // Select columns to be displayed
    $this->check('fields[survey_id]');
    $this->check('fields[survey_response]');
    $this->select('survey_id_value', "label=$surveyTitle");
    $this->select('status_id_value', "label=Interviewed");
    $this->click('_qf_SurveyDetails_submit');
    $this->waitForElementPresent('_qf_SurveyDetails_submit_print');
    $this->assertTrue($this->isTextPresent("Is equal to Interviewed"));

    // commenting out the print assertion as print dialog which appears breaks the webtest
    // as it is OS-related and cannot be handled through webtest 

    // $this->click('_qf_SurveyDetails_submit_print');
    // $this->waitForPageToLoad($this->getTimeoutMsec());

    // $this->assertTrue($this->isTextPresent("Survey Title = $surveyTitle"));
    // $this->assertTrue($this->isTextPresent("Q1 = $field1"));
    // $this->assertTrue($this->isTextPresent("$value1"));

    // use GOTV (campaign/gotv) to mark the respondents as voted
    $this->openCiviPage("campaign/gotv", "reset=1");

    // search for the respondents
    // select survey
    $this->select('campaign_survey_id', "label=$surveyTitle");
    // need to wait for Groups field to reload dynamically
    $this->waitForElementPresent("//select[@class='campaignGroupsSelect']/option[text()='$groupName']");

    // select group
    $this->click('campaignGroupsSelect1');
    $this->select('campaignGroupsSelect1', "label=$groupName");
    $this->waitForElementPresent("xpath=//ul[@id='crmasmList1']/li");
    $this->click("xpath=//div[@id='search_form_gotv']/div[2]/table/tbody/tr[6]/td/a[text()='Search']");

    $this->waitForElementPresent("xpath=//table[@id='gotvVoterRecords']/tbody/tr/td[7]");
    $this->check("xpath=//table[@id='gotvVoterRecords']/tbody/tr/td[7]/input");

    // Check title of the activities created
    $this->openCiviPage("activity/search", "reset=1", '_qf_Search_refresh');
    $this->select('activity_survey_id', "label=$surveyTitle");
    $this->click('_qf_Search_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->verifyText("xpath=//table[@class='selector']/tbody//tr/td[5]/a[text()='Smith, $firstName1']/../../td[3]",
      preg_quote("$surveyTitle - Respondent Interview")
    );
    $this->verifyText("xpath=//table[@class='selector']/tbody//tr/td[5]/a[text()='John, $firstName2']/../../td[3]",
      preg_quote("$surveyTitle - Respondent Reservation")
    );
  }
}

