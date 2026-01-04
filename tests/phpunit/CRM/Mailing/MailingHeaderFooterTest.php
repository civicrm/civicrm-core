<?php
/**
 * Test that email headers and footers work as expected.
 *
 * @package CiviCRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\OptionValue;

/**
 * Class CRM_Mailing_MailingHeaderFooterTest
 * @group headless
 */
class CRM_Mailing_MailingHeaderFooterTest extends CiviUnitTestCase {

  /**
   * The group ID.
   *
   * @var int
   */
  protected $groupID;

  public function setUp(): void {
    parent::setUp();
    $this->groupID = $this->groupCreate();
  }

  /**
   * Clean up after tests.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function tearDown(): void {
    $this->quickCleanup(['civicrm_group']);
    OptionValue::delete(FALSE)->addWhere('name', '=', 'CiviTestSMSProvider')->execute();
    parent::tearDown();
  }

  /**
   * Test that a new email has the default header and footer.
   */
  public function testNewEmailHasDefaultHeaderFooter() : void {
    $mailing = $this->callAPISuccess('Mailing', 'create', [
      'sequential' => 1,
      'name' => 'mailing name',
      'created_id' => 1,
      'groups' => ['include' => [$this->groupID]],
      'scheduled_date' => 'now',
      'subject' => 'My really interesting subject',
      'body_text' => 'Hello world',
    ]);

    $defaultHeader = CRM_Mailing_PseudoConstant::defaultComponent('Header', '');
    $defaultFooter = CRM_Mailing_PseudoConstant::defaultComponent('Footer', '');
    $this->assertEquals($mailing['values'][0]['header_id'], $defaultHeader);
    $this->assertEquals($mailing['values'][0]['footer_id'], $defaultFooter);
  }

  /**
   * Test that a new email has the default header and footer.
   */
  public function testNewEmailCustomHeaderFooter() : void {
    $mailingComponentHeader = $this->createTestEntity('MailingComponent', [
      'name' => 'Custom Header',
      'component_type' => 'Header',
      'is_active' => 1,
      'is_default' => 0,
    ]);
    $mailingComponentFooter = $this->createTestEntity('MailingComponent', [
      'name' => 'Custom Footer',
      'component_type' => 'Footer',
      'is_active' => 1,
      'is_default' => 0,
    ]);

    $mailing = $this->callAPISuccess('Mailing', 'create', [
      'sequential' => 1,
      'name' => 'mailing name',
      'created_id' => 1,
      'groups' => ['include' => [$this->groupID]],
      'scheduled_date' => 'now',
      'header_id' => $mailingComponentHeader['name'],
      'footer_id' => $mailingComponentFooter['name'],
      'subject' => 'My really interesting subject',
      'body_text' => 'Hello world',
    ]);

    $this->assertEquals($mailing['values'][0]['header_id'], $mailingComponentHeader['id']);
    $this->assertEquals($mailing['values'][0]['footer_id'], $mailingComponentFooter['id']);
  }

  /**
   * Test that a new sms has no header and footer
   */
  public function testNewSMSNoHeaderFooter() : void {
    $smsProvider = civicrm_api3('SmsProvider', 'create', [
      'name' => 'CiviTestSMSProvider',
      'api_type' => 1,
      'username' => 1,
      'password' => 1,
      'api_url' => 1,
      'api_params' => 'a=1',
      'is_default' => 1,
      'is_active' => 1,
      'domain_id' => 1,
    ])['id'];

    $mailing = $this->callAPISuccess('Mailing', 'create', [
      'sequential' => 1,
      'name' => 'mailing name',
      'created_id' => 1,
      'groups' => ['include' => [$this->groupID]],
      'scheduled_date' => 'now',
      'subject' => 'My really interesting subject',
      'body_text' => 'Hello world',
      'sms_provider_id' => $smsProvider,
    ]);

    $this->assertEquals($mailing['values'][0]['header_id'], NULL);
    $this->assertEquals($mailing['values'][0]['footer_id'], NULL);
  }

}
