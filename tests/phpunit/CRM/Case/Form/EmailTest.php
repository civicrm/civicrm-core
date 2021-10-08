<?php

/**
 * @group headless
 */
class CRM_Case_Form_EmailTest extends CiviCaseTestCase {

  public function testOpeningEmailForm() {
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
    $contents = ob_get_contents();
    ob_end_clean();

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

  public function testCaseTokenForRecipientWithoutRoleInCase() {
    $clientId = $this->individualCreate();
    $caseObj = $this->createCase($clientId, $this->_loggedInUser);

    $anotherPersonId = $this->individualCreate([], 1);
    $anotherPersonInfo = $this->callAPISuccess('Contact', 'getsingle', ['id' => $anotherPersonId]);

    // I think this might be a longstanding bug that it was intended to be
    // civicrm_contact.id but it actually wants civicrm_email.id as the
    // from_email_address below. See CRM_Utils_Mail::formatFromAddress() where
    // the docblock says contact id but that's not what it looks up.
    $senderEmail = $this->callAPISuccess('Email', 'getsingle', ['contact_id' => $this->_loggedInUser]);

    $mut = new CiviMailUtils($this);

    $form = $this->getFormObject('CRM_Case_Form_Task_Email', [
      'to' => "{$anotherPersonId}::{$anotherPersonInfo['email']}",
      'cc_id' => '',
      'bcc_id' => '',
      'subject' => 'abc',
      // see note above
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
    $form->_contactIds = [$anotherPersonId];
    $form->postProcess();

    $mut->checkMailLog([
      "Hello {$anotherPersonInfo['display_name']}",
      "This is case id {$caseObj->id}",
    ]);
    $mut->stop();
  }

}
