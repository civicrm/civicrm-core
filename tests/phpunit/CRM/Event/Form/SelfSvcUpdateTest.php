<?php

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_SelfSvcUpdateTest extends CiviUnitTestCase {

  /**
   * Test cancellation.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testForm(): void {
    $_REQUEST['pid'] = $this->participantCreate(['status_id' => 'Registered']);
    $_REQUEST['is_backoffice'] = 1;
    $this->addLocationBlockToDomain();
    $this->individualCreate(['email' => 'new@example.org']);
    $mut = new CiviMailUtils($this);
    /* @var CRM_Event_Form_SelfSvcUpdate $form*/
    $form = $this->getFormObject('CRM_Event_Form_SelfSvcUpdate', [
      'email' => 'new@example.org',
      'action' => 2,
    ]);
    $form->buildForm();
    $form->postProcess();
    $mut->checkAllMailLog([
      'Your Event Registration has been cancelled',
      'Annual CiviCRM meet',
      'Registration Date: February 19th, 2007',
      'Please contact us at 123 or send email to fixme.domainemail@example.org',
      'October 21st, 2008 12:00 AM-October 23rd, 2008 12:00 AM',
    ]);
    $emails = $mut->getAllMessages();
    $this->assertStringContainsString('', $emails[0]);
  }

}
