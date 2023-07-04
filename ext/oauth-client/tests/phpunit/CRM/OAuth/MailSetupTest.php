<?php

use CRM_OAuth_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Test helper functions in CRM_OAuth_MailSetup.
 *
 * @group headless
 */
class CRM_OAuth_MailSetupTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  public function setUp(): void {
    parent::setUp();
  }

  public function tearDown(): void {
    parent::tearDown();
  }

  public function testEvalArrayTemplate(): void {
    $vars = [
      'token' => [
        'client_id' => 10,
        'resource_owner' => ['mail' => 'foo@bar.com'],
      ],
      'client' => [
        'id' => 1,
        'provider' => 'ms-exchange',
        'guid' => 'abcd-1234-efgh-5678',
        'secret' => '8765-hgfe-4321-dcba',
        'options' => NULL,
        'is_active' => TRUE,
        'created_date' => '2020-10-29 10:11:12',
        'modified_date' => '2020-10-29 10:11:12',
      ],
      'provider' => [
        'name' => 'foozball',
        'title' => 'Foozball Association',
        'options' => [
          'urlAuthorize' => 'https://login.example.com/common/oauth2/v2.0/authorize',
          'urlAccessToken' => 'https://login.example.com/common/oauth2/v2.0/token',
          'urlResourceOwnerDetails' => 'https://resource.example.com/v9.0/me',
          'scopeSeparator' => ' ',
          'scopes' => [],
        ],
        'mailSettingsTemplate' => [
          'name' => '{{provider.title}}: {{token.resource_owner.mail}}',
          'domain' => '{{token.resource_owner.mail|getMailDomain}}',
          'localpart' => NULL,
          'return_path' => NULL,
          'protocol:name' => 'IMAP',
          'server' => 'imap.foozball.com',
          'username' => '{{token.resource_owner.mail}}',
          'password' => NULL,
          'is_ssl' => TRUE,
        ],
        'class' => 'Civi\\OAuth\\CiviGenericProvider',
      ],
    ];
    $expected = [
      'name' => 'Foozball Association: foo@bar.com',
      'domain' => 'bar.com',
      'localpart' => NULL,
      'return_path' => NULL,
      'protocol:name' => 'IMAP',
      'server' => 'imap.foozball.com',
      'username' => 'foo@bar.com',
      'password' => '',
      'is_ssl' => TRUE,
    ];
    $actual = \CRM_OAuth_MailSetup::evalArrayTemplate($vars['provider']['mailSettingsTemplate'], $vars);
    $this->assertEquals($expected, $actual);
    $this->assertTrue($actual['localpart'] === NULL);
  }

}
