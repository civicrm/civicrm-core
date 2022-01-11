<?php

/**
 * @group headless
 */
class CRM_Case_Form_EmailTest extends CiviCaseTestCase {

  public function testOpeningEmailForm(): void {
    $clientId = $this->individualCreate();
    $caseObj = $this->createCase($clientId, $this->_loggedInUser);

    $url = "civicrm/case/email/add?reset=1&action=add&atype=3&cid={$this->_loggedInUser}&caseid={$caseObj->id}";

    $_SERVER['REQUEST_URI'] = $url;
    $urlParts = explode('?', $url);
    $_GET['q'] = $urlParts[0];

    $parsed = [];
    parse_str($urlParts[1], $parsed);
    foreach ($parsed as $param => $value) {
      $_REQUEST[$param] = $value;
    }

    $item = CRM_Core_Invoke::getItem([$_GET['q']]);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    $contents = ob_get_clean();

    foreach ($parsed as $param => $dontcare) {
      unset($_REQUEST[$param]);
    }

    // Anything here could be subject to change. Just tried to pick a few that
    // might be less likely to. Really just trying to see if it opens the
    // right form and with no errors.
    $this->assertStringContainsString('name="from_email_address"', $contents);
    $this->assertStringContainsString('name="subject"', $contents);
    $this->assertStringContainsString('name="_qf_Email_upload"', $contents);
    $this->assertStringContainsString('anthony_anderson@civicrm.org', $contents);
    $this->assertStringContainsString('CRM_Case_Form_Task_Email', $contents);
  }

  public function testCaseTokenForRecipientAddedAfterOpeningForm(): void {
    $clientId = $this->individualCreate();
    $caseObj = $this->createCase($clientId, $this->_loggedInUser);

    $anotherPersonId = $this->individualCreate([], 1);
    $anotherPersonInfo = $this->callAPISuccess('Contact', 'getsingle', ['id' => $anotherPersonId]);

    $senderEmail = $this->callAPISuccess('Email', 'getsingle', ['contact_id' => $this->_loggedInUser]);

    $mut = new CiviMailUtils($this);

    // Note we start by "clicking" on the link to send an email to the client
    // but the "to" field below is where we've changed the recipient.
    $_GET['cid'] = $_REQUEST['cid'] = $clientId;
    $_GET['caseid'] = $_REQUEST['caseid'] = $caseObj->id;
    $_GET['atype'] = $_REQUEST['atype'] = 3;
    $_GET['action'] = $_REQUEST['action'] = 'add';

    $form = $this->getFormObject('CRM_Case_Form_Task_Email', [
      'to' => "{$anotherPersonId}::{$anotherPersonInfo['email']}",
      'cc_id' => '',
      'bcc_id' => '',
      'subject' => 'abc',
      // Note this is the civicrm_email.id
      'from_email_address' => $senderEmail['id'],
      'html_message' => '<p>Hello {contact.display_name}</p> <p>This is case id {case.id}</p>',
      'text_message' => '',
      'template' => '',
      'saveTemplateName' => '',
      'MAX_FILE_SIZE' => '2097152',
      'attachDesc_1' => '',
      'attachDesc_2' => '',
      'attachDesc_3' => '',
      'followup_date' => '',
      'followup_assignee_contact_id' => '',
      'followup_activity_type_id' => '',
      'followup_activity_subject' => '',
    ]);
    $form->_contactIds = [$clientId];
    $form->postProcess();

    $mut->checkMailLog([
      "Hello {$anotherPersonInfo['display_name']}",
      "This is case id {$caseObj->id}",
    ]);
    $mut->stop();
  }

}
