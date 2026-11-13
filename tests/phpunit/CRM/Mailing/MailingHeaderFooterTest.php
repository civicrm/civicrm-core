<?php
/**
 * Test that email headers and footers work as expected.
 *
 * @package CiviCRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\MailingComponent;
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
    $this->quickCleanup(['civicrm_group', 'civicrm_sms_provider', 'civicrm_mailing', 'civicrm_mailing_group', 'civicrm_mailing_job']);
    if (!empty($this->ids['MailingComponent'])) {
      MailingComponent::delete(FALSE)->addWhere('id', 'IN', $this->ids['MailingComponent'])->execute();
    }
    OptionValue::delete(FALSE)->addWhere('name', '=', 'CiviTestSMSProvider')->execute();
    parent::tearDown();
  }

  /**
   * Test that a new email has the default header and footer.
   */
  public function testNewEmailHasDefaultHeaderFooter() : void {
    $mailing = $this->callApiV3Success('Mailing', 'create', [
      'sequential' => 1,
      'name' => 'mailing name',
      'created_id' => CRM_Core_BAO_Domain::getDomain()->contact_id,
      'groups' => ['include' => [$this->groupID]],
      'scheduled_date' => 'now',
      'subject' => 'My really interesting subject',
      'body_text' => 'Hello world',
    ]);

    $defaultHeader = CRM_Mailing_PseudoConstant::defaultComponent('Header', '');
    $defaultFooter = CRM_Mailing_PseudoConstant::defaultComponent('Footer', '');
    $this->assertSame($defaultHeader, $mailing['values'][0]['header_id']);
    $this->assertSame($defaultFooter, $mailing['values'][0]['footer_id']);
  }

  /**
   * Test that a new email has the default header and footer.
   */
  public function testNewEmailCustomHeaderFooter() : void {
    $mailingComponentHeader = $this->createTestEntity('MailingComponent', [
      'name' => 'Custom Header',
      'component_type' => 'Header',
      'is_active' => TRUE,
      'is_default' => FALSE,
    ], 'header');
    $mailingComponentFooter = $this->createTestEntity('MailingComponent', [
      'name' => 'Custom Footer',
      'component_type' => 'Footer',
      'is_active' => TRUE,
      'is_default' => FALSE,
    ], 'footer');

    $mailing = $this->callApiV3Success('Mailing', 'create', [
      'sequential' => 1,
      'name' => 'mailing name',
      'created_id' => CRM_Core_BAO_Domain::getDomain()->contact_id,
      'groups' => ['include' => [$this->groupID]],
      'scheduled_date' => 'now',
      'header_id' => $mailingComponentHeader['name'],
      'footer_id' => $mailingComponentFooter['name'],
      'subject' => 'My really interesting subject',
      'body_text' => 'Hello world',
    ]);

    $this->assertSame((string) $mailingComponentHeader['id'], $mailing['values'][0]['header_id']);
    $this->assertSame((string) $mailingComponentFooter['id'], $mailing['values'][0]['footer_id']);
  }

  /**
   * Test that a new sms has no header and footer
   */
  public function testNewSMSNoHeaderFooter() : void {
    $smsProvider = $this->createTestEntity('SmsProvider', [
      'name' => 'CiviTestSMSProvider',
      'api_type:name' => 'http',
      'username' => 1,
      'password' => '1',
      'api_url' => 1,
      'api_params' => 'a=1',
      'is_default' => TRUE,
      'is_active' => TRUE,
      'domain_id' => CRM_Core_Config::domainID(),
    ])['id'];

    $mailing = $this->callApiV3Success('Mailing', 'create', [
      'sequential' => 1,
      'name' => 'mailing name',
      'created_id' => CRM_Core_BAO_Domain::getDomain()->contact_id,
      'groups' => ['include' => [$this->groupID]],
      'scheduled_date' => 'now',
      'subject' => 'My really interesting subject',
      'body_text' => 'Hello world',
      'sms_provider_id' => $smsProvider,
    ]);

    $this->assertSame('', $mailing['values'][0]['header_id']);
    $this->assertSame('', $mailing['values'][0]['footer_id']);
  }

}
