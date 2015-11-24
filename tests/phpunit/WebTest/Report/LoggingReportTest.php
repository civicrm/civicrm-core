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
 * Class WebTest_Report_LoggingReportTest
 */
class WebTest_Report_LoggingReportTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testLoggingReport() {
    $this->webtestLogin();

    //enable the logging
    $this->openCiviPage('admin/setting/misc', 'reset=1');
    $this->click("xpath=//tr[@class='crm-miscellaneous-form-block-logging']/td[2]/label[text()='Yes']");
    $this->click("_qf_Miscellaneous_next-top");
    $this->waitForPageToLoad(2 * $this->getTimeoutMsec());
    // FIXME: good to do waitForText here but enabling log is time consuming and status may fade out by the time we do the check.

    //enable CiviCase component
    $this->enableComponents("CiviCase");

    //add new contact
    $originalFirstName = $firstName = 'Anthony' . substr(sha1(rand()), 0, 7);
    $lastName = 'Anderson' . substr(sha1(rand()), 0, 7);

    $this->webtestAddContact($firstName, $lastName);
    $cid = $this->urlArg('cid');

    //add contact to group
    $this->waitForElementPresent("xpath=//li[@id='tab_group']/a");
    $this->click("xpath=//li[@id='tab_group']/a");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    $this->waitForElementPresent("group_id");
    $this->select("group_id", "label=Case Resources");
    $this->click("_qf_GroupContact_next");
    $this->waitForElementPresent("xpath=//form[@id='GroupContact']//div[@class='view-content view-contact-groups']//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[4]/a");
    $this->click("xpath=//form[@id='GroupContact']//div[@class='view-content view-contact-groups']//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[4]/a");

    // Check confirmation alert.
    $this->waitForText("xpath=//div[@class='crm-confirm-dialog ui-dialog-content ui-widget-content modal-dialog']", "Remove $firstName $lastName from Case Resources?");
    $this->click("xpath=//div[@class='ui-dialog-buttonset']//button//span[text()='Continue']");

    //tag addition
    $this->waitForElementPresent("xpath=//li[@id='tab_tag']/a");
    $this->click("xpath=//li[@id='tab_tag']/a");
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    $this->waitForElementPresent("tagtree");
    $this->click("xpath=//div[@id='tagtree']/ul//li/span/label[text()='Company']");
    $this->waitForTextPresent("Saved");
    $this->click("xpath=//div[@id='tagtree']/ul//li/span/label[text()='Government Entity']");
    $this->waitForTextPresent("Saved");
    $this->click("xpath=//div[@id='tagtree']/ul//li/span/label[text()='Company']");
    $this->waitForTextPresent("Saved");

    //add new note
    $this->waitForElementPresent("xpath=//li[@id='tab_note']/a");
    $this->click("xpath=//li[@id='tab_note']/a");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[@class='view-content']//div[@class='action-link']/a[@class='button medium-popup']");

    $this->waitForElementPresent("_qf_Note_upload-top");
    $noteSubject = "test note" . substr(sha1(rand()), 0, 7);
    $noteText = "test note text" . substr(sha1(rand()), 0, 7);
    $this->type('subject', $noteSubject);
    $this->type('note', $noteText);
    $this->click("_qf_Note_upload-top");
    $this->waitForElementPresent("xpath=//div[@id='notes']/div/table/tbody/tr/td[7]/span[1]/a[2][text()='Edit']");
    $this->click("xpath=//div[@id='notes']/div/table/tbody/tr/td[7]/span[1]/a[2][text()='Edit']");
    $this->waitForElementPresent("_qf_Note_upload-top");
    $this->type('subject', $noteSubject . "_edited");
    $this->type('note', $noteText . "_edited");
    $this->clickLink("_qf_Note_upload-top", "xpath=//div[@class='crm-results-block']/div[@id='notes']/div/table/tbody/tr//td/span[2]/ul/li[2]/a[text()='Delete']", FALSE);

    $this->click("xpath=//div[@id='notes']/div/table/tbody/tr/td[7]/span[2]/ul/li[2]/a[text()='Delete']");
    // Check confirmation alert.
    $this->waitForText("xpath=//form[@id='Note']/div[@class='view-content']/div[@class='status']", "Are you sure you want to delete the note ''?");
    $this->click("xpath=//input[@id='_qf_Note_next']");
    $this->waitForText('crm-notification-container', "Selected Note has been deleted successfully.");

    //add new relationship , disable it , delete it
    $this->waitForElementPresent("xpath=//li[@id='tab_rel']/a");
    $this->click("css=li#tab_rel a");
    $this->waitForElementPresent("link=Add Relationship");
    $this->click("link=Add Relationship");
    $this->waitForElementPresent("_qf_Relationship_cancel");
    $this->select("relationship_type_id", "label=Employee of");
    $this->select2('related_contact_id', 'Default', TRUE);
    $this->click('_qf_Relationship_upload-bottom');
    $this->waitForElementPresent("xpath=//div[@id='contact-summary-relationship-tab']/div[2]/div/table/tbody/tr/td[9]/span[2][text()='more']/ul/li[1]/a[text()='Disable']");

    $this->click("xpath=//div[@id='contact-summary-relationship-tab']/div[2]/div/table/tbody/tr/td[9]/span[2][text()='more']/ul/li[1]/a[text()='Disable']");
    $this->waitForText("xpath=//div[@class='crm-confirm-dialog ui-dialog-content ui-widget-content modal-dialog crm-ajax-container']", 'Are you sure you want to disable this relationship?');
    $this->click("xpath=//div[@class='ui-dialog-buttonset']//button//span[text()='Yes']");
    $this->waitForElementPresent("xpath=//div[@class='crm-contact-relationship-past']/div//table/tbody//tr/td[9]/span[2][text()='more']/ul/li[2]/a[text()='Delete']");
    $this->click("xpath=//div[@class='crm-contact-relationship-past']/div//table/tbody//tr/td[9]/span[2][text()='more']/ul/li[2]/a[text()='Delete']");
    $this->waitForText("xpath=//form[@id='Relationship']/div[@class='status']", "Are you sure you want to delete this Relationship?");
    $this->click("_qf_Relationship_next-bottom");
    $this->waitForElementPresent("link=Add Relationship");

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
    $this->waitForElementPresent("_qf_Activity_cancel-bottom");
    $this->click('_qf_Activity_upload-bottom');
    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr//td//span/a[text()='Edit']");
    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr//td//span/a[text()='Edit']");
    $this->waitForElementPresent("_qf_Activity_cancel-bottom");
    $this->select("status_id", "value=2");
    $this->waitForAjaxContent();
    $this->click('_qf_Activity_upload-bottom');
    $this->waitForText("crm-notification-container", "Activity has been saved.");
    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[7]/div");
    $this->verifyText("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[7]/div", 'Completed');

    //add a case
    $this->click("xpath=//li[@id='tab_case']/a");
    $this->waitForElementPresent("xpath=//form[@id='Search']//div/div//div[@class='action-link']/a");
    $this->click("xpath=//form[@id='Search']//div/div//div[@class='action-link']/a");
    $this->waitForElementPresent("_qf_Case_cancel-bottom");
    $this->type('activity_subject', "subject" . rand());
    $this->select('case_type_id', 'value=1');
    $this->click('_qf_Case_upload-bottom');
    $this->waitForElementPresent("xpath=//table[@class='caseSelector']/tbody//tr/td[9]//span/a[1][text()='Manage']");
    $this->click("xpath=//table[@class='caseSelector']/tbody//tr/td[9]//span/a[1][text()='Manage']");
    $this->waitForElementPresent("xpath=//form[@id='CaseView']/div[2]/table/tbody/tr/td[4]/a");
    $this->click("xpath=//form[@id='CaseView']/div[2]/table/tbody/tr/td[4]/a");
    $this->waitForElementPresent("_qf_Activity_cancel-bottom");
    $this->select("case_status_id", "value=2");
    $this->click("_qf_Activity_upload-top");
    $this->waitForElementPresent("_qf_CaseView_cancel-bottom");
    $this->click("_qf_CaseView_cancel-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //visit the logging contact summary report
    $this->openCiviPage('report/logging/contact/summary', 'reset=1');
    $this->waitForElementPresent('altered_contact_value');
    $this->type('altered_contact_value', $firstName);
    $this->click("_qf_LoggingSummary_submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $data = array(
      //contact data check
      array("log_type" => "Contact", "altered_contact" => "{$firstName} {$lastName}", "action" => "Update"),
      array("log_type" => "Contact", "altered_contact" => "{$firstName} {$lastName}", "action" => "Insert"),
      //relationship data check
      array(
        "log_type" => "Relationship",
        "altered_contact" => "{$firstName} {$lastName} [Employee of]",
        "action" => "Update",
      ),
      array(
        "log_type" => "Relationship",
        "altered_contact" => "{$firstName} {$lastName} [Employee of]",
        "action" => "Insert",
      ),
      array(
        "log_type" => "Relationship",
        "altered_contact" => "{$firstName} {$lastName} [Employee of]",
        "action" => "Delete",
      ),
      //group data check
      array(
        "log_type" => "Group",
        "altered_contact" => "{$firstName} {$lastName} [Case Resources]",
        "action" => "Added",
      ),
      array(
        "log_type" => "Group",
        "altered_contact" => "{$firstName} {$lastName} [Case Resources]",
        "action" => "Removed",
      ),
      //note data check
      array("log_type" => "Note", "altered_contact" => "{$firstName} {$lastName}", "action" => "Update"),
      array("log_type" => "Note", "altered_contact" => "{$firstName} {$lastName}", "action" => "Insert"),
      array("log_type" => "Note", "altered_contact" => "{$firstName} {$lastName}", "action" => "Delete"),
      //tags data check
      array("log_type" => "Tag", "altered_contact" => "{$firstName} {$lastName} [Company]", "action" => "Insert"),
      array(
        "log_type" => "Tag",
        "altered_contact" => "{$firstName} {$lastName} [Government Entity]",
        "action" => "Insert",
      ),
      array("log_type" => "Tag", "altered_contact" => "{$firstName} {$lastName} [Company]", "action" => "Delete"),
      //case data check
      array(
        "log_type" => "Case",
        "altered_contact" => "{$firstName} {$lastName} [Housing Support]",
        "action" => "Update",
      ),
      array(
        "log_type" => "Case",
        "altered_contact" => "{$firstName} {$lastName} [Housing Support]",
        "action" => "Insert",
      ),
      //case activity check
      array(
        "log_type" => "Activity",
        "altered_contact" => "{$firstName} {$lastName} [Interview]",
        "action" => "Update",
      ),
      array(
        "log_type" => "Activity",
        "altered_contact" => "{$firstName} {$lastName} [Interview]",
        "action" => "Insert",
      ),
    );
    $this->verifyReportData($data);

    //update link (logging details report check)
    $contactInfo = array();
    $contactInfo['data'] = array(
      array(
        'field' => 'Sort Name',
        'changed_from' => "{$lastName}, {$originalFirstName}",
        'changed_to' => "{$lastName}, {$firstName}",
      ),
      array(
        'field' => 'Display Name',
        'changed_from' => "{$originalFirstName} {$lastName}",
        'changed_to' => "{$firstName} {$lastName}",
      ),
      array('field' => 'First Name', 'changed_from' => $originalFirstName, 'changed_to' => $firstName),
      // array('field' => 'Email Greeting', 'changed_from' => "Dear {$originalFirstName}", 'changed_to' => "Dear {$firstName}"),
      // array('field' => 'Postal Greeting', 'changed_from' => "Dear {$originalFirstName}", 'changed_to' => "Dear {$firstName}"),
      // array('field' => 'Addressee', 'changed_from' => "{$originalFirstName} {$lastName}", 'changed_to' => "{$firstName} {$lastName}"),
    );
    $contactInfo = array_merge($contactInfo, $data[0]);

    $relationshipInfo = array();
    $relationshipInfo['data'] = array(
      array('field' => 'Relationship Is Active', 'changed_from' => 'true', 'changed_to' => 'false'),
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
    $this->openCiviPage('contact/view/delete', "reset=1&delete=1&cid=$cid");
    $this->click("_qf_Delete_done");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->openCiviPage('report/logging/contact/summary', 'reset=1');
    $this->click("_qf_LoggingSummary_submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $contactDataDelete = array(
      array(
        "log_type" => "Contact",
        "altered_contact" => "{$firstName} {$lastName}",
        "action" => "Delete (to trash)",
      ),
    );
    $this->verifyReportData($contactDataDelete);

    //disable the logging
    $this->openCiviPage('admin/setting/misc', 'reset=1');
    $this->waitForElementPresent("xpath=//tr[@class='crm-miscellaneous-form-block-logging']/td[2]/label[text()='No']");
    $this->click("xpath=//tr[@class='crm-miscellaneous-form-block-logging']/td[2]/label[text()='No']");
    $this->click("_qf_Miscellaneous_next-top");
    $this->waitForTextPresent("Changes Saved");
  }

  /**
   * @param $data
   */
  public function verifyReportData($data) {
    foreach ($data as $value) {
      // check for the row contains proper data
      $actionPath = ($value['action'] == 'Update') ? "td[1]/a[2]" : "td[1][contains(text(), '{$value['action']}')]";
      $contactCheck = ($value['action'] == 'Delete (to trash)') ? "td[4][contains(text(), '{$value['altered_contact']}')]" : "td[4]/a[contains(text(), '{$value['altered_contact']}')]/..";

      $this->assertTrue($this->isElementPresent("xpath=//table/tbody//tr/td[2][contains(text(), '{$value['log_type']}')]/../{$contactCheck}/../{$actionPath}"), "The proper record not present for (log type : {$value['log_type']}, altered contact : {$value['altered_contact']}, action as {$value['action']})");

      if ($value['action'] == 'Update') {
        $this->assertTrue(($value['action'] == $this->getText("xpath=//table/tbody//tr/td[2][contains(text(), '{$value['log_type']}')]/../td[4]/a[contains(text(), '{$value['altered_contact']}')]/../../{$actionPath}")), "The proper record action  {$value['action']} not present for (log type : {$value['log_type']}, altered contact : {$value['altered_contact']} record)");
      }
    }
  }

  /**
   * @param $dataForReportDetail
   * @param array $filters
   */
  public function detailReportCheck($dataForReportDetail, $filters = array()) {
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
        if ($type == 'text') {
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
