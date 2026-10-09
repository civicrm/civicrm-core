<?php

/**
 * Conformance of the mail params that CRM_Utils_Mail::send() hands to
 * hook_civicrm_alterMailParams().
 *
 * The spec in tests/events/hook_civicrm_alterMailParams.evch.php runs against
 * every test implementing HeadlessInterface or HookInterface, so a send that
 * carries a parameter it does not recognise fails the test that happened to
 * perform the send. These cases fail there rather than somewhere unrelated.
 *
 * @group headless
 */
class CRM_Utils_AlterMailParamsSpecTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * @return array
   */
  private function mailParams(): array {
    return [
      'from' => '"Test" <from@example.com>',
      'toName' => 'Recipient',
      'toEmail' => 'to@example.com',
      'subject' => 'Conformance',
      'text' => 'Conformance',
    ];
  }

  /**
   * Nothing reads groupName, and senders outside core set values of their own -
   * core's own ext/postbox uses 'Postbox'. A whitelist of the values core
   * happened to use made every other sender non-conforming.
   */
  public function testGroupNameValueIsNotRestricted(): void {
    $mut = new CiviMailUtils($this, TRUE);

    $params = ['groupName' => 'Postbox'] + $this->mailParams();
    $this->assertTrue(CRM_Utils_Mail::send($params));

    $mut->stop();
  }

  /**
   * abortMailSend is documented and CRM_Utils_Mail::send() honours it, so the
   * spec has to recognise it. Note this only reaches the checker when the
   * caller supplies it: extension listeners run at DEFAULT_HOOK_PRIORITY
   * (-100), after the checker at 0, so the checker never sees what they add.
   */
  public function testAbortMailSendIsRecognised(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $params = ['abortMailSend' => TRUE] + $this->mailParams();
    $this->assertFalse(CRM_Utils_Mail::send($params));
    $mut->assertMailLogEmpty();

    $mut->stop();
  }

}
