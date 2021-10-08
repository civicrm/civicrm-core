<?php

/**
 * @group headless
 */
class CRM_Case_Form_EmailTest extends CiviCaseTestCase {

  public function testOpeningEmailForm() {
    $loggedInUserId = $this->createLoggedInUser();
    $clientId = $this->individualCreate();
    $caseObj = $this->createCase($clientId, $loggedInUserId);

    $url = "civicrm/case/email/add?reset=1&action=add&atype=3&cid={$loggedInUserId}&caseid={$caseObj->id}";

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

}
