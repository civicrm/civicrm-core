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
    $this->participantCreate(['status_id.name' => 'Registered']);
    $this->addLocationBlockToDomain();
    $this->individualCreate(['email' => 'new@example.org']);
    $this->getTestForm('CRM_Event_Form_SelfSvcTransfer', [
      'email' => 'new@example.org',
    ], [
      'pid' => $this->ids['Participant']['default'],
      'cs' => CRM_Contact_BAO_Contact_Utils::generateChecksum($this->ids['Contact']['individual_0']),
      'is_backoffice' => 1,
    ])->processForm();

    $this->assertMailSentContainingHeaderString('Registration Confirmation - Annual CiviCRM meet - Mr. Anthony', 0);
    $this->assertMailSentContainingString('<p>Dear Anthony,</p>    <p>Your Event Registration has been Transferred to Anthony Anderson.</p>', 1);
    $this->assertMailSentContainingString('anthony_anderson@civicrm.org', 1);
    $this->assertMailSentContainingString('123', 1);
    $this->assertMailSentContainingString('fixme.domainemail@example.org', 1);
  }

  /**
   * Test Transfer as anonymous
   *
   * @throws \CRM_Core_Exception
   */
  public function testTransferAnonymous(): void {
    CRM_Core_Session::singleton()->getStatus(TRUE);
    $event = $this->eventCreateUnpaid(['start_date' => date('Ymd', strtotime('+2 month')), 'end_date' => date('Ymd', strtotime('+2 month')), 'registration_end_date' => date('Ymd', strtotime('+1 month')), 'allow_selfcancelxfer' => 1]);
    $this->participantCreate(['status_id.name' => 'Registered', 'event_id' => $event['id'], 'contact_id' => $this->individualCreate()]);
    $this->addLocationBlockToDomain();
    $this->individualCreate(['email' => 'new2@example.org'], 'to_contact');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    $this->getTestForm('CRM_Event_Form_SelfSvcTransfer', [
      'first_name' => 'test',
      'last_name' => 'selftransfer',
      'email' => 'new2@example.org',
    ], [
      'pid' => $this->ids['Participant']['default'],
      'cs' => CRM_Contact_BAO_Contact_Utils::generateChecksum($this->ids['Contact']['individual_0']),
      'is_backoffice' => 0,
    ])->processForm();
    $this->assertEquals('Registration Transferred', CRM_Core_Session::singleton()->getStatus()[1]['title']);
  }

}
