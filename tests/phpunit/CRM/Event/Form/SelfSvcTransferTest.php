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
   */
  public function testCancel(): void {
    $_REQUEST['pid'] = $this->participantCreate(['status_id' => 'Registered']);
    $_REQUEST['is_backoffice'] = 1;
    $this->addLocationBlockToDomain();
    $this->individualCreate(['email' => 'new@example.org']);
    $mut = new CiviMailUtils($this);
    /** @var CRM_Event_Form_SelfSvcTransfer $form*/
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

  /**
   * Test Transfer as anonymous
   */
  public function testTransferAnonymous(): void {
    CRM_Core_Session::singleton()->getStatus(TRUE);
    $event = $this->eventCreateUnpaid(['start_date' => date('Ymd', strtotime('+2 month')), 'end_date' => date('Ymd', strtotime('+2 month')), 'registration_end_date' => date('Ymd', strtotime('+1 month')), 'allow_selfcancelxfer' => 1]);
    $_REQUEST['pid'] = $this->participantCreate(['status_id' => 'Registered', 'event_id' => $event['id']]);
    $_REQUEST['cs'] = CRM_Contact_BAO_Contact_Utils::generateChecksum($this->callAPISuccess('Participant', 'getsingle', ['id' => $_REQUEST['pid']])['contact_id']);
    $_REQUEST['is_backoffice'] = 0;
    $this->addLocationBlockToDomain();
    $this->individualCreate(['email' => 'new2@example.org']);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    /** @var CRM_Event_Form_SelfSvcTransfer $form*/
    $form = $this->getFormObject('CRM_Event_Form_SelfSvcTransfer', [
      'first_name' => 'test',
      'last_name' => 'selftransfer',
      'email' => 'new2@example.org',
    ]);
    $form->buildForm();
    try {
      $form->postProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals('Registration Transferred', CRM_Core_Session::singleton()->getStatus()[1]['title']);
    }
  }

}
