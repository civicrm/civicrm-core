<?php
/* vim: set shiftwidth=2 tabstop=2 softtabstop=2: */

/**
 * Class CRM_Core_BAO_MessageTemplateTest
 * @group headless
 */
class CRM_Core_BAO_MessageTemplateTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  public function testCaseActivityCopyTemplate() {
    $client_id = $this->individualCreate();
    $contact_id = $this->individualCreate();

    $tplParams = array(
      'isCaseActivity' => 1,
      'client_id' => $client_id,
      'activityTypeName' => 'Follow up',
      'activity' => array(
        'fields' => array(
          array(
            'label' => 'Case ID',
            'type' => 'String',
            'value' => '1234',
          ),
        ),
      ),
      'activitySubject' => 'Test 123',
      'idHash' => substr(sha1(CIVICRM_SITE_KEY . '1234'), 0, 7),
    );

    list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate(
      array(
        'groupName' => 'msg_tpl_workflow_case',
        'valueName' => 'case_activity',
        'contactId' => $contact_id,
        'tplParams' => $tplParams,
        'from' => 'admin@example.com',
        'toName' => 'Demo',
        'toEmail' => 'admin@example.com',
        'attachments' => NULL,
      )
    );

    $this->assertEquals('[case #' . $tplParams['idHash'] . '] Test 123', $subject);
    $this->assertContains('Your Case Role', $message);
    $this->assertContains('Case ID : 1234', $message);
  }

}
