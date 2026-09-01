<?php

namespace Civi\OAuth;

use Civi\Api4\MailSettings;
use Civi\Api4\OAuthClient;
use Civi\Api4\OAuthSysToken;
use Civi\Core\HookInterface;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class MailSettingsOAuthSpecProviderTest extends \PHPUnit\Framework\TestCase implements
    HeadlessInterface,
    HookInterface,
    TransactionalInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  public function setUp(): void {
    parent::setUp();
    // The hook result is cached, so drop any list built before this class registered its provider.
    \Civi::cache('long')->delete('OAuthProvider_list');
  }

  public function hook_civicrm_oauthProviders(&$providers) {
    $providers['dummymail'] = [
      'name' => 'dummymail',
      'title' => 'Dummy Mail Provider',
      'class' => 'Civi\OAuth\DummyProvider',
      'options' => ['scopes' => ['mail']],
    ];
  }

  private function createClient(): array {
    return OAuthClient::create(FALSE)
      ->setValues(['provider' => 'dummymail', 'guid' => 'guid-' . uniqid(), 'secret' => 'shh'])
      ->execute()
      ->single();
  }

  private function createMailSettings(): array {
    return MailSettings::create(FALSE)
      ->setValues([
        'name' => 'acct-' . uniqid(),
        'domain' => 'example.org',
        'protocol:name' => 'IMAP',
        'server' => 'imap.dummy',
        'username' => 'bob@example.org',
        'password' => 'stored-secret',
        'is_default' => FALSE,
      ])
      ->execute()
      ->single();
  }

  private function createToken(array $client, int $mailSettingsId): void {
    OAuthSysToken::create(FALSE)
      ->setValues([
        'client_id' => $client['id'],
        'tag' => \CRM_OAuth_MailSetup::TOKEN_TAG_PREFIX . $mailSettingsId,
        'access_token' => 'at',
        'refresh_token' => 'rt',
        'expires' => \CRM_Utils_Time::time() + 3600,
      ])
      ->execute();
  }

  public function testApiFieldNamesTheConnectedClient(): void {
    $client = $this->createClient();
    $connected = $this->createMailSettings();
    $plain = $this->createMailSettings();
    $this->createToken($client, $connected['id']);

    $rows = MailSettings::get(FALSE)
      ->addSelect('id', 'oauth_client_id')
      ->addWhere('id', 'IN', [$connected['id'], $plain['id']])
      ->execute()
      ->indexBy('id');

    $this->assertEquals($client['id'], $rows[$connected['id']]['oauth_client_id']);
    $this->assertNull($rows[$plain['id']]['oauth_client_id'],
      'An account with no token has no client');
  }

  public function testApiFieldIsFilterable(): void {
    $client = $this->createClient();
    $connected = $this->createMailSettings();
    $this->createMailSettings();
    $this->createToken($client, $connected['id']);

    $ids = MailSettings::get(FALSE)
      ->addSelect('id')
      ->addWhere('oauth_client_id', '=', $client['id'])
      ->execute()
      ->column('id');

    $this->assertEquals([$connected['id']], $ids);
  }

}
