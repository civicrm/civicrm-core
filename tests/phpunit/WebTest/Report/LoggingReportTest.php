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
class WebTest_Report_LoggingReportTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testLoggingReport() {
    $this->webtestLogin();

    //enable the logging
    $this->openCiviPage('admin/setting/misc', 'reset=1');
    $this->click("xpath=//tr[@class='crm-miscellaneous-form-block-logging']/td[2]/label[text()='Yes']");
    $this->click("_qf_Miscellaneous_next-top");
    $this->waitForTextPresent("Changes Saved");

    //enable CiviCase component
    $this->enableComponents("CiviCase");

    //add new contact
    $orginalFirstName = $firstName = 'Anthony' . substr(sha1(rand()), 0, 7);
    $lastName  = 'Anderson' . substr(sha1(rand()), 0, 7);

    $this->webtestAddContact($firstName, $lastName);
    $cid = explode('&cid=', $this->getLocation());

    //add contact to group
    $this->waitForElementPresent("xpath=//li[@id='tab_group']/a");
    $this->click("xpath=//li[@id='tab_group']/a");
    sleep(3);
    $this->select("group_id", "label=Case Resources");
    $this->click("_qf_GroupContact_next");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("xpath=//form[@id='GroupContact']//div[@class='view-content']//div[@class='dataTables_wrapper']/table/tbody/tr/td[4]/a");
    $this->click("xpath=//form[@id='GroupContact']//div[@class='view-content']//div[@class='dataTables_wrapper']/table/tbody/tr/td[4]/a");

    // Check confirmation alert.
    $this->assertTrue((bool)preg_match("/^Are you sure you want to remove/",
        $this->getConfirmation()
      ));
    $this->chooseOkOnNextConfirmation();
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //tag addition
    $this->waitForElementPresent("xpath=//li[@id='tab_tag']/a");
    $this->click("xpath=//li[@id='tab_tag']/a");
    sleep(3);
    $this->click("xpath=//div[@id='tagtree']/ul//li/label[text()='Company']/../input");
    $this->waitForTextPresent("Saved");
    $this->click("xpath=//div[@id='tagtree']/ul//li/label[text()='Government Entity']/../input");
    $this->waitForTextPresent("Saved");
    $this->click("xpath=//div[@id='tagtree']/ul//li/label[text()='Company']/../input");
    $this->waitForTextPresent("Saved");

    //add new note
    $this->waitForElementPresent("xpath=//li[@id='tab_note']/a");
    $this->click("xpath=//li[@id='tab_note']/a");
    sleep(3);
    $this->click("xpath=//div[@id='Notes']//div[@class='action-link']/a");

    $this->waitForElementPresent("_qf_Note_upload-top");
    $noteSubject = "test note" . substr(sha1(rand()), 0, 7);
    $noteText = "test note text" . substr(sha1(rand()), 0, 7);
    $this->type('subject', $noteSubject);
    $this->type('note', $noteText);
    $this->click("_qf_Note_upload-top");
    $this->waitForElementPresent("xpath=//div[@id='notes']//a[text()='Edit']");
    $this->click("xpath=//div[@id='notes']//a[text()='Edit']");

    $this->waitForElementPresent("_qf_Note_upload-top");
    $this->type('subject', $noteSubject . "_edited");
    $this->type('note', $noteText . "_edited");
    $this->click("_qf_Note_upload-top");

    $this->waitForElementPresent("xpath=//div[@class='crm-results-block']/div[@id='notes']/div/table/tbody/tr//td/span[2]/ul/li[2]/a[text()='Delete']");
    $this->click("xpath=//div[@class='crm-results-block']/div[@id='notes']/div/table/tbody/tr//td/span[2]/ul/li[2]/a[text()='Delete']");
    // Check confirmation alert.
    $this->assertTrue((bool)preg_match("/^Are you sure you want to delete this note/",
        $this->getConfirmation()
      ));
    $this->chooseOkOnNextConfirmation();
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //add new relationship , disable it , delete it
    $this->waitForElementPresent("xpath=//li[@id='tab_rel']/a");
    $this->click("xpath=//li[@id='tab_rel']/a");
    sleep(3);
    $this->click("xpath=//div[@id='Relationships']//div[@class='action-link']/a");
    $this->waitForElementPresent("_qf_Relationship_refresh");
    $this->select("relationship_type_id", "label=Employee of");
    $this->webtestFillAutocomplete("Default Organization");
    $this->waitForElementPresent("quick-save");
    $this->click("quick-save");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("xpath=//div[@id='current-relationships']//a[text()='Disable']");
    $this->click("xpath=//div[@id='current-relationships']//a[text()='Disable']");
    $this->assertTrue((bool)preg_match("/^Are you sure you want to disable this relationship/",
      $this->getConfirmation()
    ));
    $this->chooseOkOnNextConfirmation();
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent(" xpath=//div[@id='inactive-relationships']//a[text()='Delete']");
    $this->click("xpath=//div[@id='inactive-relationships']//a[text()='Delete']");
    $this->assertTrue((bool)preg_match("/^Are you sure you want to delete this relationship/",
      $this->getConfirmation()
    ));
    $this->chooseOkOnNextConfirmation();
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //update existing contact
    $this->click("xpath=//ul[@id='actions']/li[2]/a");
    $this->waitForElementPresent("_qf_Contact_upload_view-top");
    $firstName = "{$firstName}_edited";
    $this->type("first_name", $firstName);
    $this->click("_qf_Contact_upload_view-top");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //add an activity
    $this->click("xpath=//li[@id='tab_activity']/a");
    $this->waitForElementPresent("other_activity");
    $this->select("other_activity", "label=Interview");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('_qf_Activity_upload-bottom');
    $this->waitForElementPresent("xpath=//table[@id='contact-activity-selector-activity']/tbody/tr/td[9]/span/a[2]");
    $this->click("xpath=//table[@id='contact-activity-selector-activity']/tbody/tr/td[9]/span/a[2]");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->select("status_id","value=2");
    $this->click('_qf_Activity_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //add a case
    $this->click("xpath=//li[@id='tab_case']/a");
    $this->waitForElementPresent("xpath=//div[@id='Cases']//div[@class='action-link']/a");
    $this->click("xpath=//div[@id='Cases']//div[@class='action-link']/a");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->type('activity_subject',"subject".rand());
    $this->select('case_type_id','value=1');
    $this->click('_qf_Case_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("xpath=//form[@id='CaseView']/div[2]/table/tbody/tr/td[4]/a");
    $this->waitForElementPresent("_qf_Activity_cancel-bottom");
    $this->select("case_status_id","value=2");
    $this->click("_qf_Activity_upload-top");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //visit the logging contact summary report
    $this->openCiviPage('report/logging/contact/summary', 'reset=1');
    $this->type('altered_contact_value', $firstName);
    $this->click("_qf_LoggingSummary_submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $data = array(
      //contact data check
      array("log_type" => "Contact", "altered_contact" => "{$firstName} {$lastName}", "action" => "Update"),
      array("log_type" => "Contact", "altered_contact" => "{$firstName} {$lastName}", "action" => "Insert"),
      //relationship data check
      array("log_type" => "Relationship", "altered_contact" => "{$firstName} {$lastName} [Employee of]", "action" => "Update"),
      array("log_type" => "Relationship", "altered_contact" => "{$firstName} {$lastName} [Employee of]", "action" => "Insert"),
      array("log_type" => "Relationship", "altered_contact" => "{$firstName} {$lastName} [Employee of]", "action" => "Delete"),
      //group data check
      array("log_type" => "Group", "altered_contact" => "{$firstName} {$lastName} [Case Resources]", "action" => "Added"),
      array("log_type" => "Group", "altered_contact" => "{$firstName} {$lastName} [Case Resources]", "action" => "Removed"),
      //note data check
      array("log_type" => "Note", "altered_contact" => "{$firstName} {$lastName}", "action" => "Update"),
      array("log_type" => "Note", "altered_contact" => "{$firstName} {$lastName}", "action" => "Insert"),
      array("log_type" => "Note", "altered_contact" => "{$firstName} {$lastName}", "action" => "Delete"),
      //tags data check
      array("log_type" => "Tag", "altered_contact" => "{$firstName} {$lastName} [Company]", "action" => "Insert"),
      array("log_type" => "Tag", "altered_contact" => "{$firstName} {$lastName} [Government Entity]", "action" => "Insert"),
      array("log_type" => "Tag", "altered_contact" => "{$firstName} {$lastName} [Company]", "action" => "Delete"),
      //case data check
      array("log_type" => "Case", "altered_contact" => "{$firstName} {$lastName} [Housing Support]", "action" => "Update"),
      array("log_type" => "Case", "altered_contact" => "{$firstName} {$lastName} [Housing Support]", "action" => "Insert"),
      //case activity check
      array("log_type" => "Activity", "altered_contact" => "{$firstName} {$lastName} [Interview]", "action" => "Update"),
      array("log_type" => "Activity", "altered_contact" => "{$firstName} {$lastName} [Interview]", "action" => "Insert"),
    );
    $this->verifyReportData($data);

    //update link (logging details report check)
    $contactInfo = array();
    $contactInfo['data'] = array(
      array('field' => 'Sort Name', 'changed_from' => "{$lastName}, {$orginalFirstName}", 'changed_to' => "{$lastName}, {$firstName}"),
      array('field' => 'Display Name', 'changed_from' => "{$orginalFirstName} {$lastName}", 'changed_to' => "{$firstName} {$lastName}"),
      array('field' => 'First Name', 'changed_from' => $orginalFirstName, 'changed_to' => $firstName),
      // array('field' => 'Email Greeting', 'changed_from' => "Dear {$orginalFirstName}", 'changed_to' => "Dear {$firstName}"),
      // array('field' => 'Postal Greeting', 'changed_from' => "Dear {$orginalFirstName}", 'changed_to' => "Dear {$firstName}"),
      // array('field' => 'Addressee', 'changed_from' => "{$orginalFirstName} {$lastName}", 'changed_to' => "{$firstName} {$lastName}"),
    );
    $contactInfo = array_merge($contactInfo, $data[0]);

    $relationshipInfo = array();
    $relationshipInfo['data'] = array(
       array('field' => 'Relationship Is Active', 'changed_from' => 'true', 'changed_to' => 'false')
    );
    $relationshipInfo = array_merge($relationshipInfo, $data[2]);

    $noteInfo = array();
    $noteInfo['data'] = array(
      array('field' => 'Note', 'changed_from' => $noteText, 'changed_to' => "{$noteText}_edited"),
      array('field' => 'Subject', 'changed_from' => $noteSubject, 'changed_to' => "{$noteSubject}_edited"),
    );
    $noteInfo = array_merge($noteInfo, $data[7]);

    $caseInfo = array();
    $caseInfo['data'] = array(
      array('field' => 'Case Status Id', 'changed_from' => 'Ongoing', 'changed_to' => "Resolved"),
    );
    $caseInfo = array_merge($caseInfo, $data[13]);

    $activityInfo = array();
    $activityInfo['data'] = array(
       array('field' => 'Activity Status Id', 'changed_from' => 'Scheduled', 'changed_to' => 'Completed'),
     );
    $activityInfo = array_merge($activityInfo, $data[15]);

    $dataForReportDetail = array($contactInfo, $relationshipInfo, $noteInfo, $caseInfo, $activityInfo);
    $filters = array(
      'text' => array('altered_contact_value' => "{$firstName} {$lastName}"),
    );
    $this->detailReportCheck($dataForReportDetail, $filters);

    //delete contact check
    $this->openCiviPage('contact/view/delete', "reset=1&delete=1&cid={$cid[1]}");
    $this->click("_qf_Delete_done");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->openCiviPage('report/logging/contact/summary', 'reset=1');
    $this->click("_qf_LoggingSummary_submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $contactDataDelete = array(array("log_type" => "Contact", "altered_contact" => "{$firstName} {$lastName}", "action" => "Delete (to trash)"));
    $this->verifyReportData($contactDataDelete);

    //disable the logging
    $this->openCiviPage('admin/setting/misc', 'reset=1');
    $this->click("xpath=//tr[@class='crm-miscellaneous-form-block-logging']/td[2]/label[text()='No']");
    $this->click("_qf_Miscellaneous_next-top");
    $this->waitForTextPresent("Changes Saved");
  }

  function verifyReportData($data) {
    foreach ($data as $value) {
      // check for the row contains proper data
      $actionPath = ($value['action'] == 'Update') ? "td[1]/a[2]" : "td[1][contains(text(), '{$value['action']}')]";
      $contactCheck = ($value['action'] == 'Delete (to trash)') ? "td[4][contains(text(), '{$value['altered_contact']}')]" : "td[4]/a[contains(text(), '{$value['altered_contact']}')]/..";

      $this->assertTrue($this->isElementPresent("xpath=//table/tbody//tr/td[2][contains(text(), '{$value['log_type']}')]/../{$contactCheck}/../{$actionPath}"), "The proper record not present for (log type : {$value['log_type']}, altered contact : {$value['altered_contact']}, action as {$value['action']})");

      if ($value['action'] == 'Update') {
        $this->assertTrue( ($value['action'] == $this->getText("xpath=//table/tbody//tr/td[2][contains(text(), '{$value['log_type']}')]/../td[4]/a[contains(text(), '{$value['altered_contact']}')]/../../{$actionPath}")), "The proper record action  {$value['action']} not present for (log type : {$value['log_type']}, altered contact : {$value['altered_contact']} record)");
      }
    }
  }

  function detailReportCheck($dataForReportDetail, $filters = array()) {
    foreach ($dataForReportDetail as $value) {
      $this->waitForElementPresent("xpath=//table/tbody//tr/td[2][contains(text(), '{$value['log_type']}')]/../td[4]/a[contains(text(), '{$value['altered_contact']}')]/../../td[1]/a[2]");
      $this->click("xpath=//table/tbody//tr/td[2][contains(text(), '{$value['log_type']}')]/../td[4]/a[contains(text(), '{$value['altered_contact']}')]/../../td[1]/a[2]");
      $this->waitForPageToLoad($this->getTimeoutMsec());

      foreach ($value['data'] as $key => $data) {
        $rowCount = $this->getXpathCount("//table[@class='report-layout display']/tbody/tr");
        for ($i = 1; $i <= $rowCount; $i++) {
          $field = $data['field'];
          if ($this->isElementPresent("xpath=//form[@id='LoggingDetail']//table/tbody/tr[{$i}]/td[@class='crm-report-field'][text()='$field']")) {
            $this->verifyText("xpath=//form[@id='LoggingDetail']//table/tbody/tr[{$i}]/td[@class='crm-report-field']", preg_quote($data['field']));
            $this->verifyText("xpath=//form[@id='LoggingDetail']//table/tbody/tr[{$i}]/td[@class='crm-report-from']", preg_quote($data['changed_from']));
            $this->verifyText("xpath=//form[@id='LoggingDetail']//table/tbody/tr[{$i}]/td[@class='crm-report-to']", preg_quote($data['changed_to']));
          }
        }
      }

    //visit the logging contact summary report
    $this->openCiviPage('report/logging/contact/summary', 'reset=1');
    foreach ($filters as $type => $filter) {
      if ($type == 'text' ) {
        foreach ($filter as $filterName => $filterValue) {
          $this->type($filterName, $filterValue);
        }
      }
    }
    $this->click("_qf_LoggingSummary_submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    }
  }
}
