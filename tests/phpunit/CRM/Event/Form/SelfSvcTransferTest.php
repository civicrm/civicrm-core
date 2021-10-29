<?php

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_SelfSvcTransferTest extends CiviUnitTestCase {

  /**
   * Test cancellation.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testCancel(): void {
    $_REQUEST['pid'] = $this->participantCreate(['status_id' => 'Registered']);
    $_REQUEST['is_backoffice'] = 1;
    $this->addLocationBlockToDomain();
    $this->individualCreate(['email' => 'new@example.org']);
    $mut = new CiviMailUtils($this);
    /* @var CRM_Event_Form_SelfSvcTransfer $form*/
    $form = $this->getFormObject('CRM_Event_Form_SelfSvcTransfer', [
      'email' => 'new@example.org',
    ]);
    $form->buildForm();
    $form->postProcess();
    $emails = $mut->getAllMessages();
    $this->assertStringContainsString('Registration Confirmation - Annual CiviCRM meet - Mr. Anthony', $emails[0]);
    $this->assertStringContainsString('<p>Dear Anthony,</p>    <p>Your Event Registration has been Transferred to Anthony Anderson.</p>', $emails[1]);
    $this->assertStringContainsString('anthony_anderson@civicrm.org', $emails[1]);
    $this->assertStringContainsString('123', $emails[1]);
    $this->assertStringContainsString('fixme.domainemail@example.org', $emails[1]);
  }

}
