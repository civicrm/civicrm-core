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
 * Class WebTest_Profile_BatchUpdateTest
 */
class WebTest_Profile_BatchUpdateTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testBatchUpdateWithContactSubtypes() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Add new individual using Quick Add block on the main page
    $firstName1 = "John_" . substr(sha1(rand()), 0, 7);
    $lastName1 = "Smiths_x" . substr(sha1(rand()), 0, 7);
    $Name1 = $lastName1 . ', ' . $firstName1;
    $this->webtestAddContact($firstName1, $lastName1, "$firstName1.$lastName1@example.com");

    // Add new individual using Quick Add block on the main page
    $firstName2 = "James_" . substr(sha1(rand()), 0, 7);
    $lastName2 = "Smiths_x" . substr(sha1(rand()), 0, 7);
    $Name2 = $lastName2 . ', ' . $firstName2;

    $firstName3 = "James_" . substr(sha1(rand()), 0, 7);
    $lastName3 = "Smiths_x" . substr(sha1(rand()), 0, 7);
    $Name3 = $lastName3 . ', ' . $firstName3;

    $this->webtestAddContact($firstName2, $lastName2, "$firstName2.$lastName2@example.com", "Student");
    $this->webtestAddContact($firstName3, $lastName3, "$firstName3.$lastName3@example.com", "Staff");

    $profileTitle = 'Batch Profile test_' . substr(sha1(rand()), 0, 7);
    $profileFields = array(
      array(
        'type' => 'Individual',
        'name' => 'Last Name',
        'label' => 'Last Name',
      ),
    );
    $this->addProfile($profileTitle, $profileFields);
    $this->openCiviPage('contact/search', 'reset=1', '_qf_Basic_refresh');
    $this->type('sort_name', "Smiths_x");
    $this->click('_qf_Basic_refresh');

    // Update multiple contacts
    $this->waitForElementPresent('CIVICRM_QFID_ts_all_4');
    $this->click('CIVICRM_QFID_ts_all_4');

    $this->select('task', "label=Update multiple contacts");
    $this->waitForElementPresent('_qf_PickProfile_next');
    $this->waitForElementPresent('uf_group_id');
    $this->select('uf_group_id', "label={$profileTitle}");
    $this->click('_qf_PickProfile_next');

    $this->waitForElementPresent('_qf_Batch_next');

    $this->isElementPresent("xpath=//form[@id='Batch']/div[2]/table/tbody//tr/td[text()='{$Name2}']");
    $this->isElementPresent("xpath=//form[@id='Batch']/div[2]/table/tbody//tr/td[text()='{$Name1}']");
    $this->isElementPresent("xpath=//form[@id='Batch']/div[2]/table/tbody//tr/td[text()='{$Name3}']");
    // selecting first check of profile
    $this->click("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[2]/input");

    $this->waitForElementPresent('_qf_Batch_next');
    $this->click("xpath=//table[@class='crm-copy-fields']/thead/tr/td[2]/img");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(5);
    //$this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_Batch_next');
    $this->click('_qf_Batch_next');
    $this->waitForElementPresent('_qf_Result_done');
    $this->click('_qf_Result_done');

    // Find contact and assert for contact sub type
    $this->openCiviPage('contact/search', 'reset=1', '_qf_Basic_refresh');
    $this->type('sort_name', $firstName2);
    $this->click('_qf_Basic_refresh');
    $this->waitForElementPresent("xpath=//div[@class='crm-search-results']/table/tbody//td/span/a[text()='View']");
    $this->click("xpath=//div[@class='crm-search-results']/table/tbody//td/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $xpath = "xpath=//div[@id='contact-summary']/div/div[2]/div/div/div[2]/div[@class='crm-content crm-contact_type_label']";
    $this->verifyText($xpath, preg_quote("Student"));

    $this->openCiviPage('contact/search', 'reset=1', '_qf_Basic_refresh');
    $this->type('sort_name', $firstName3);
    $this->click('_qf_Basic_refresh');
    $this->waitForElementPresent("xpath=//div[@class='crm-search-results']/table/tbody//td/span/a[text()='View']");
    $this->click("xpath=//div[@class='crm-search-results']/table/tbody//td/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $xpath = "xpath=//div[@id='contact-summary']/div/div[2]/div/div/div[2]/div[@class='crm-content crm-contact_type_label']";
    $this->verifyText($xpath, preg_quote("Staff"));
  }

  public function testBatchUpdate() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Add new individual using Quick Add block on the main page
    $firstName1 = "John_" . substr(sha1(rand()), 0, 7);
    $lastName = "Smith_" . substr(sha1(rand()), 0, 7);
    $Name1 = $lastName . ', ' . $firstName1;
    $this->webtestAddContact($firstName1, $lastName, "$firstName1.$lastName@example.com");

    // Add new individual using Quick Add block on the main page
    $firstName1 = "James_" . substr(sha1(rand()), 0, 7);
    $Name2 = $lastName . ', ' . $firstName1;
    $this->webtestAddContact($firstName1, $lastName, "$firstName1.$lastName@example.com");
    $profileTitle = 'Batch Profile test for contacts ' . substr(sha1(rand()), 0, 7);
    $profileFor = 'Contacts';
    $customDataArr = $this->_addCustomData($profileFor);
    $this->_addProfile($profileTitle, $customDataArr, $profileFor);

    //setting ckeditor as WYSIWYG
    $this->openCiviPage('admin/setting/preferences/display', 'reset=1', '_qf_Display_next-bottom');
    $this->select('editor_id', 'CKEditor');
    $this->click('_qf_Display_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Find Contact
    $this->openCiviPage('contact/search', 'reset=1', '_qf_Basic_refresh');
    $this->type('sort_name', $lastName);
    $this->click('_qf_Basic_refresh');

    // Update multiple contacts
    $this->waitForElementPresent('CIVICRM_QFID_ts_all_4');
    $this->click('CIVICRM_QFID_ts_all_4');

    $this->select('task', "label=Update multiple contacts");
    $this->waitForElementPresent('_qf_PickProfile_next');

    $this->select('uf_group_id', "label={$profileTitle}");
    $this->click('_qf_PickProfile_next');

    $this->waitForElementPresent('_qf_Batch_next');

    $this->isElementPresent("xpath=//form[@id='Batch']/div[2]/table/tbody//tr/td[text()='{$Name2}']");
    $this->isElementPresent("xpath=//form[@id='Batch']/div[2]/table/tbody//tr/td[text()='{$Name1}']");

    // selecting first check of profile
    $this->click("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[2]/table/tbody/tr/td/input[2]");

    // selecting second check of profile
    $this->click("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[3]/input[2]");
    // clicking copy values to rows of first check and verifying
    // if other check Profile Field check box are affected

    $this->click("xpath=//table[@class='crm-copy-fields']/thead/tr/td[2]/img");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(5);
    if ($this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[3]/input[2]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[3]/input[4]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[3]/input[6]") &&
      //verification for second field first row
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[3]/input[2]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[3]/input[4]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[3]/input[6]") &&
      //verification for first field second row
      $this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[2]/table/tbody/tr/td/input[2]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[2]/table/tbody/tr/td[2]/input[2]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[2]/table/tbody/tr[2]/td/input[2]")
    ) {
      $assertCheck = TRUE;
    }
    else {
      $assertCheck = FALSE;
    }

    $this->assertTrue($assertCheck, 'copy rows for field one failed');

    $this->click("xpath=//table[@class='crm-copy-fields']/thead/tr/td[3]/img");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(5);
    if ($this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[3]/input[2]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[3]/input[4]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[3]/input[6]") &&
      //verification for second field first row
      $this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[3]/input[2]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[3]/input[4]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[3]/input[6]") &&
      //verification for first field second row
      $this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[2]/table/tbody/tr/td/input[2]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[2]/table/tbody/tr/td[2]/input[2]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[2]/table/tbody/tr[2]/td/input[2]")
    ) {
      $assertCheck = TRUE;
    }
    else {
      $assertCheck = FALSE;
    }

    $this->assertTrue($assertCheck, 'copy rows for field two failed');

    $dateElementIdFirstRow = $this->getAttribute("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[4]/input/@id");
    $dateElementIdSecondRow = $this->getAttribute("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[4]/input/@id");

    $this->webtestFillDateTime($dateElementIdFirstRow, "+1 week");
    $this->click("xpath=//table[@class='crm-copy-fields']/thead/tr/td[4]/img");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(5);

    if ($this->getValue("{$dateElementIdFirstRow}_time") == $this->getValue("{$dateElementIdSecondRow}_time") && $this->getValue("{$dateElementIdFirstRow}") == $this->getValue("{$dateElementIdSecondRow}")) {
      $assertCheck = TRUE;
    }
    else {
      $assertCheck = FALSE;
    }

    $this->assertTrue($assertCheck, 'date / time coping failed');

    $richTextAreaIdOne = $this->getAttribute("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[5]/textarea/@id");
    $richTextAreaIdTwo = $this->getAttribute("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[5]/textarea/@id");

    $this->fillRichTextField($richTextAreaIdOne, 'This is Test Introductory Message', 'CKEditor');
    $this->click("xpath=//table[@class='crm-copy-fields']/thead/tr/td[5]/img");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(5);

    if ($this->getValue($richTextAreaIdOne) == $this->getValue($richTextAreaIdTwo)) {
      $assertCheck = TRUE;
    }
    else {
      $assertCheck = FALSE;
    }

    $this->assertTrue($assertCheck, 'Rich Text Area coping failed');

    // selecting first check of profile
    $this->click("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[6]/input");
    // selecting second check of profile
    $this->click("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[7]/input");
    // clicking copy values to rows of first check and verifying
    // if other radio Profile Field radio buttons are affected

    $this->click("xpath=//table[@class='crm-copy-fields']/thead/tr/td[6]/img");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(5);
    if ($this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[7]/input") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[7]/input[2]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[7]/input[3]") &&
      //verification for second field first row
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[7]/input") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[7]/input[2]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[7]/input[3]") &&
      //verification for first field second row
      $this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[6]/input") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[6]/input[2]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[6]/input[3]")
    ) {
      $assertCheck = TRUE;
    }
    else {
      $assertCheck = FALSE;
    }

    $this->assertTrue($assertCheck, 'copy rows for field one failed[radio button]');

    $this->click("xpath=//table[@class='crm-copy-fields']/thead/tr/td[7]/img");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(5);
    if ($this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[7]/input") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[7]/input[2]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr/td[7]/input[3]") &&
      //verification for second field first row
      $this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[7]/input") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[7]/input[2]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[7]/input[3]") &&
      //verification for first field second row
      $this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[6]/input") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[6]/input[2]") &&
      !$this->isChecked("xpath=//form[@id='Batch']/div[2]/table/tbody/tr[2]/td[6]/input[3]")
    ) {
      $assertCheck = TRUE;
    }
    else {
      $assertCheck = FALSE;
    }

    $this->assertTrue($assertCheck, 'copy rows for field two failed[radio button]');

    //campaign test for interview
    //enable CiviCampaign module if necessary
    $this->enableComponents("CiviCampaign");

    //Adding a survey
    $this->openCiviPage('survey/add', 'reset=1', '_qf_Main_upload-bottom');
    $surveyTitle = "BatchUpdateTest Survey" . substr(sha1(rand()), 0, 7);
    $this->type("title", $surveyTitle);
    $this->select('activity_type_id', 'label=Survey');
    $this->click('_qf_Main_upload-bottom');
    $this->waitForElementPresent('_qf_Questions_cancel-bottom');
    $this->select("//form[@id='Questions']/div[2]/table/tbody/tr[1]/td[2]/div/div/span/select", "label={$profileTitle}");
    $this->click('_qf_Questions_upload_next-bottom');
    $this->waitForElementPresent('_qf_Results_cancel-bottom');
    $this->click('CIVICRM_QFID_1_option_type');
    $this->type('option_label_1', 'option1');
    $this->type('option_value_1', 'option1');
    $this->type('option_label_2', 'option2');
    $this->type('option_value_2', 'option2');
    $this->click('_qf_Results_upload_done-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Reserve and interview respondents
    $this->openCiviPage('campaign', 'reset=1&subPage=survey');
    $this->waitForElementPresent("xpath=//table[@class='surveys dataTable no-footer']/tbody//tr/td[2]/div/a[text()='{$surveyTitle}']/../../following-sibling::td[@class=' crm-campaign-voterLinks']/span/ul/li/a");
    $this->click("xpath=//table[@class='surveys dataTable no-footer']/tbody//tr/td[2]/div/a[text()='{$surveyTitle}']/../../following-sibling::td[@class=' crm-campaign-voterLinks']/span/ul/li/a");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("xpath=//div[@id='search_form_reserve']/div");
    $this->waitForElementPresent('sort_name');
    $this->type('sort_name', $lastName);
    $this->waitForElementPresent('_qf_Search_refresh');
    $this->clickLink('_qf_Search_refresh', 'toggleSelect');
    $this->click('toggleSelect');
    $this->select('task', "value=2");
    $this->waitForElementPresent('_qf_Reserve_next_reserveToInterview-top');
    $this->clickLink('_qf_Reserve_next_reserveToInterview-top', '_qf_Interview_cancel_interview');

    $this->isElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody//tr/td[2][text()='{$Name2}']");
    $this->isElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody//tr/td[2][text()='{$Name1}']");

    //edition to be done here
    // selecting first check of profile
    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[3]/input[2]");
    // selecting second check of profile
    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[4]/input[2]");
    // clicking copy values to rows of first check and verifying
    // if other check Profile Field check box are affected

    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/thead/tr/th[3]/div/img");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(5);
    if ($this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[4]/input[2]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[4]/input[4]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[4]/input[6]") &&
      //verification for second field first row
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[4]/input[2]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[4]/input[4]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[4]/input[6]") &&
      //verification for first field second row
      $this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[3]/input[2]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[3]/input[4]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[3]/input[6]")
    ) {
      $assertCheck = TRUE;
    }
    else {
      $assertCheck = FALSE;
    }

    $this->assertTrue($assertCheck, 'copy rows for field one failed for inteview (campaign)');

    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/thead/tr/th[4]/div/img");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(5);
    if ($this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[4]/input[2]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[4]/input[4]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[4]/input[6]") &&
      //verification for second field first row
      $this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[4]/input[2]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[4]/input[4]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[4]/input[6]") &&
      //verification for first field second row
      $this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[3]/input[2]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[3]/input[4]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[3]/input[6]")
    ) {
      $assertCheck = TRUE;
    }
    else {
      $assertCheck = FALSE;
    }

    $this->assertTrue($assertCheck, 'copy rows for field two failed for inteview (campaign)');

    $dateElementIdFirstRow = $this->getAttribute("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[5]/input/@id");
    $dateElementIdSecondRow = $this->getAttribute("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[5]/input/@id");

    $this->webtestFillDateTime($dateElementIdFirstRow, "+1 week");
    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/thead/tr/th[5]/div/img");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(5);

    if ($this->getValue("{$dateElementIdFirstRow}_time") == $this->getValue("{$dateElementIdSecondRow}_time") && $this->getValue("{$dateElementIdFirstRow}") == $this->getValue("{$dateElementIdSecondRow}")) {
      $assertCheck = TRUE;
    }
    else {
      $assertCheck = FALSE;
    }

    $this->assertTrue($assertCheck, 'date / time coping failed for inteview (campaign)');

    $this->type("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[@class='note']/input", 'This is Test Introductory Message');
    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/thead/tr/th[8]/div/img");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(5);

    if ($this->getValue("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[@class='note']/input") == $this->getValue("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[@class='note']/input")) {
      $assertCheck = TRUE;
    }
    else {
      $assertCheck = FALSE;
    }

    $this->assertTrue($assertCheck, 'Note Custom field coping failed');

    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[6]/input");
    // selecting second check of profile
    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[7]/input");
    // clicking copy values to rows of first check and verifying
    // if other radio Profile Field radio buttons are affected

    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/thead/tr/th[6]/div/img");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(5);
    if ($this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[7]/input") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[7]/input[2]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[7]/input[3]") &&
      //verification for second field first row
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[7]/input") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[7]/input[2]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[7]/input[3]") &&
      //verification for first field second row
      $this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[6]/input") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[6]/input[2]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[6]/input[3]")
    ) {
      $assertCheck = TRUE;
    }
    else {
      $assertCheck = FALSE;
    }

    $this->assertTrue($assertCheck, 'copy rows for field one failed for inteview (campaign)[radio button]');

    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/thead/tr/th[7]/div/img");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(5);
    if ($this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[7]/input") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[7]/input[2]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[7]/input[3]") &&
      //verification for second field first row
      $this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[7]/input") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[7]/input[2]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[7]/input[3]") &&
      //verification for first field second row
      $this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[6]/input") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[6]/input[2]") &&
      !$this->isChecked("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[6]/input[3]")
    ) {
      $assertCheck = TRUE;
    }
    else {
      $assertCheck = FALSE;
    }

    $this->assertTrue($assertCheck, 'copy rows for field two failed for inteview (campaign)[radio button]');
    $this->clickLink("_qf_Interview_cancel_interview");

    //change the editor back to ckeditor
    $this->openCiviPage('admin/setting/preferences/display', 'reset=1', '_qf_Display_next-bottom');
    $this->select('editor_id', 'CKEditor');
    $this->click('_qf_Display_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  /**
   * @param $profileTitle
   * @param $customDataArr
   * @param $profileFor
   */
  public function _addProfile($profileTitle, $customDataArr, $profileFor) {

    $this->openCiviPage('admin/uf/group', 'reset=1');

    $this->clickLink('link=Add Profile', '_qf_Group_cancel-bottom');

    // Add membership custom data field to profile
    $this->type('title', $profileTitle);

    // Standalone form or directory
    $this->click('uf_group_type_Profile');

    $this->clickLink('_qf_Group_next-bottom');

    $this->waitForText('crm-notification-container', "Your CiviCRM Profile '{$profileTitle}' has been added. You can add fields to this profile now.");

    $this->waitForElementPresent("field_name[0]");
    foreach ($customDataArr as $customDataParams) {
      $this->select('field_name[0]', "label={$profileFor}");
      $this->select('field_name[1]', "label={$customDataParams[1]} :: {$customDataParams[0]}");
      $this->click('field_name[1]');
      $this->click('label');

      // Clicking save and new
      $this->click('_qf_Field_next_new-bottom');
      $this->waitForText('crm-notification-container', "Your CiviCRM Profile Field '{$customDataParams[1]}' has been saved to '{$profileTitle}'.");
      $this->waitForElementPresent("xpath=//select[@id='field_name_1'][@style='display: none;']");
    }
  }

  /**
   * @param $profileFor
   *
   * @return array
   */
  public function _addCustomData($profileFor) {
    $returnArray = array();
    $customGroupTitle = 'Custom_' . substr(sha1(rand()), 0, 4);

    $this->openCiviPage('admin/custom/group', 'reset=1');

    //add new custom data
    $this->clickLink("//a[@id='newCustomDataGroup']/span");

    //fill custom group title
    $this->click("title");
    $this->type("title", $customGroupTitle);

    //custom group extends
    $this->click("extends[0]");
    $this->select("extends[0]", "label={$profileFor}");
    if ($this->isElementPresent('//option')) {
      $this->click("//option[@value='']");
    }

    $this->clickLink('_qf_Group_next-bottom');

    //Is custom group created?
    $this->waitForText('crm-notification-container', "Your custom field set '{$customGroupTitle}' has been added. You can add custom fields now.");

    //for checkbox 1
    $this->waitForElementPresent("label");
    $checkLabel1 = 'Custom Check One Text_' . substr(sha1(rand()), 0, 4);
    $this->waitForAjaxContent();
    $this->type('label', $checkLabel1);
    $this->click('data_type[0]');
    $this->select('data_type[0]', "label=Alphanumeric");
    $this->select('data_type[1]', "label=CheckBox");

    // enter checkbox options
    $checkOneOptionLabel1 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_1', $checkOneOptionLabel1);
    $this->type('option_value_1', 1);
    $checkOneOptionLabel2 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_2', $checkOneOptionLabel2);
    $this->type('option_value_2', 2);
    $this->click("link=another choice");
    $checkOneOptionLabel3 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_3', $checkOneOptionLabel3);
    $this->type('option_value_3', 3);

    //setting options per line to check CRM-9938
    $this->type("options_per_line", 2);

    //clicking save
    $this->clickLink('_qf_Field_next_new-top', '_qf_Field_done-bottom', FALSE);

    //Is custom field created
    $this->waitForText('crm-notification-container', "Custom field '$checkLabel1' has been saved.");
    $this->waitForElementPresent("label");
    $returnArray[1] = array($customGroupTitle, $checkLabel1);

    // create another custom field - Integer Radio
    //for checkbox 2
    $checkLabel2 = 'Custom Check Two Text_' . substr(sha1(rand()), 0, 4);
    $this->waitForAjaxContent();
    $this->type('label', $checkLabel2);
    $this->click('data_type[0]');
    $this->select('data_type[0]', "label=Alphanumeric");
    $this->select('data_type[1]', "label=CheckBox");

    // enter checkbox options
    $checkTwoOptionLabel1 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_1', $checkTwoOptionLabel1);
    $this->type('option_value_1', 1);
    $checkTwoOptionLabel2 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_2', $checkTwoOptionLabel2);
    $this->type('option_value_2', 2);
    $this->click("link=another choice");
    $checkTwoOptionLabel3 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_3', $checkTwoOptionLabel3);
    $this->type('option_value_3', 3);

    //clicking save
    $this->clickLink('_qf_Field_next_new-top', '_qf_Field_done-bottom', FALSE);

    //Is custom field created
    $this->waitForText('crm-notification-container', "Custom field '$checkLabel2' has been saved.");
    $returnArray[2] = array($customGroupTitle, $checkLabel2);

    // create another custom field - Date
    $this->waitForElementPresent("label");
    $dateFieldLabel = 'Custom Date Field' . substr(sha1(rand()), 0, 4);
    $this->waitForAjaxContent();
    $this->type('label', $dateFieldLabel);
    $this->click('data_type[0]');
    $this->select('data_type[0]', "label=Date");
    $this->waitForElementPresent('start_date_years');

    // enter years prior to current date
    $this->type('start_date_years', 3);

    // enter years up to the end year
    $this->type('end_date_years', 3);

    // select the date and time format
    $this->select('date_format', "value=yy-mm-dd");
    $this->select('time_format', "value=2");

    //clicking save
    $this->clickLink('_qf_Field_next_new-top', '_qf_Field_done-bottom', FALSE);
    //Is custom field created
    $this->waitForText('crm-notification-container', "Custom field '$dateFieldLabel' has been saved.");
    $returnArray[3] = array($customGroupTitle, $dateFieldLabel);

    //create rich text editor field
    $this->waitForElementPresent("label");
    $richTextField = 'Custom Rich TextField_' . substr(sha1(rand()), 0, 4);
    $this->waitForAjaxContent();
    $this->type('label', $richTextField);
    $this->click('data_type[0]');
    $this->select('data_type[0]', "label=Note");
    $this->select('data_type[1]', "value=RichTextEditor");

    //clicking save
    $this->clickLink('_qf_Field_next_new-top', '_qf_Field_done-bottom', FALSE);

    //Is custom field created
    $this->waitForText('crm-notification-container', "Custom field '$richTextField' has been saved.");
    $returnArray[4] = array($customGroupTitle, $richTextField);

    //create radio button field
    //for radio 1
    $this->waitForElementPresent("label");
    $radioLabel1 = 'Custom Radio One Text_' . substr(sha1(rand()), 0, 4);
    $this->waitForAjaxContent();
    $this->type('label', $radioLabel1);
    $this->click('data_type[0]');
    $this->select('data_type[0]', "label=Alphanumeric");
    $this->select('data_type[1]', "label=Radio");

    // enter radio options
    $radioOneOptionLabel1 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_1', $radioOneOptionLabel1);
    $this->type('option_value_1', 1);
    $radioOneOptionLabel2 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_2', $radioOneOptionLabel2);
    $this->type('option_value_2', 2);
    $this->click("link=another choice");
    $radioOneOptionLabel3 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_3', $radioOneOptionLabel3);
    $this->type('option_value_3', 3);

    //clicking save
    $this->clickLink('_qf_Field_next_new-top', '_qf_Field_done-bottom', FALSE);

    //Is custom field created
    $this->waitForText('crm-notification-container', "Custom field '$radioLabel1' has been saved.");
    $returnArray[5] = array($customGroupTitle, $radioLabel1);

    // create another custom field - Alpha Radio
    //for radio 2
    $this->waitForElementPresent("label");
    $radioLabel2 = 'Custom Radio Two Text_' . substr(sha1(rand()), 0, 4);
    $this->waitForAjaxContent();
    $this->type('label', $radioLabel2);
    $this->click('data_type[0]');
    $this->select('data_type[0]', "label=Alphanumeric");
    $this->select('data_type[1]', "label=Radio");

    // enter radio options
    $radioTwoOptionLabel1 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_1', $radioTwoOptionLabel1);
    $this->type('option_value_1', 1);
    $radioTwoOptionLabel2 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_2', $radioTwoOptionLabel2);
    $this->type('option_value_2', 2);
    $this->click("link=another choice");
    $radioTwoOptionLabel3 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_3', $radioTwoOptionLabel3);
    $this->type('option_value_3', 3);

    //clicking save
    $this->clickLink('_qf_Field_done', 'newCustomField', FALSE);

    //Is custom field created
    $this->waitForText('crm-notification-container', "Custom field '$radioLabel2' has been saved.");
    $returnArray[6] = array($customGroupTitle, $radioLabel2);

    return $returnArray;
  }

}
