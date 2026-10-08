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

}
