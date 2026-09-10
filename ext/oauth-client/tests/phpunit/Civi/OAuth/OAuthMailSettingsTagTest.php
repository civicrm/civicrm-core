<?php

namespace Civi\OAuth;

use Civi\Api4\MailSettings;
use Civi\Api4\OAuthClient;
use Civi\Api4\OAuthSysToken;
use Civi\Connect\Initiators;
use Civi\Core\HookInterface;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class OAuthMailSettingsTagTest extends \PHPUnit\Framework\TestCase implements
    HeadlessInterface,
    HookInterface,
    TransactionalInterface {

  private array $providers = [];

  public function setUpHeadless() {
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  public function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../fixtures/DummyProvider.php';
    $this->providers['dummymail'] = [
      'name' => 'dummymail',
      'title' => 'Dummy Mail Provider',
      'class' => 'Civi\OAuth\DummyProvider',
      'options' => [
        'urlAuthorize' => 'https://dummy/authorize',
        'urlAccessToken' => 'https://dummy/token',
        'urlResourceOwnerDetails' => 'https://dummy/owner',
        'scopes' => ['mail'],
        // Deliberately empty: any attempt to call the provider fails the test.
        'cannedResponses' => [],
      ],
      'mailSettingsTemplate' => [
        'name' => '{{token.resource_owner.email}}',
        'protocol:name' => 'IMAP',
        'server' => 'imap.dummy',
        'is_ssl' => TRUE,
      ],
    ];
    // The hook result is cached, so drop any list built before this class registered its provider.
    \Civi::cache('long')->delete('OAuthProvider_list');
  }

  public function hook_civicrm_oauthProviders(&$providers) {
    $providers = array_merge($providers, $this->providers);
  }

  private function createClient(string $provider = 'dummymail'): array {
    return OAuthClient::create(FALSE)
      ->setValues(['provider' => $provider, 'guid' => 'guid-' . uniqid(), 'secret' => 'shh'])
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

  private function createToken(array $client, int $mailSettingsId, int $expires): array {
    return OAuthSysToken::create(FALSE)
      ->setValues([
        'client_id' => $client['id'],
        'tag' => 'MailSettings:' . $mailSettingsId,
        'access_token' => 'at',
        'refresh_token' => 'rt',
        'expires' => $expires,
        'resource_owner_name' => 'bob@example.org',
      ])
      ->execute()
      ->single();
  }

  private function getInitiators(int $mailSettingsId): Initiators {
    return Initiators::create(['for' => 'MailSettings', 'mail_settings_id' => $mailSettingsId]);
  }

  public function testUnconnectedAccountOffersEachEligibleClient(): void {
    $clientA = $this->createClient();
    $clientB = $this->createClient();
    $mailSettings = $this->createMailSettings();

    $initiators = $this->getInitiators($mailSettings['id']);

    $this->assertNull($initiators->getConnected(), 'An account with no token is not connected');
    $this->assertSame([], $initiators->getManagedFields());
    $this->assertEquals(
      ['oauth_' . $clientA['id'], 'oauth_' . $clientB['id']],
      array_keys($initiators->available)
    );
  }

  public function testProviderWithoutMailTemplateIsNotOffered(): void {
    unset($this->providers['dummymail']['mailSettingsTemplate']);
    \Civi::cache('long')->delete('OAuthProvider_list');
    $this->createClient();
    $mailSettings = $this->createMailSettings();

    $this->assertSame([], $this->getInitiators($mailSettings['id'])->available);
  }

  public function testDisabledClientIsNotOffered(): void {
    $client = $this->createClient();
    OAuthClient::update(FALSE)->addWhere('id', '=', $client['id'])->setValues(['is_active' => FALSE])->execute();
    $mailSettings = $this->createMailSettings();

    $this->assertSame([], $this->getInitiators($mailSettings['id'])->available);
  }

  public function testConnectedAccountReportsOwningClientOnly(): void {
    $owner = $this->createClient();
    $other = $this->createClient();
    $mailSettings = $this->createMailSettings();
    $this->createToken($owner, $mailSettings['id'], \CRM_Utils_Time::time() + 3600);

    $initiators = $this->getInitiators($mailSettings['id']);

    $this->assertEquals(['oauth_' . $owner['id']], array_keys($initiators->available),
      'Once connected, only the client which owns the token is offered');
    $this->assertArrayNotHasKey('oauth_' . $other['id'], $initiators->available);

    $connected = $initiators->getConnected();
    $this->assertNotNull($connected);
    $this->assertSame('success', $connected['status_severity']);
    $this->assertStringContainsString('bob@example.org', $connected['status_message']);
    $this->assertStringContainsString('Dummy Mail Provider', $connected['status_message']);
    $this->assertStringContainsString('civicrm/admin/oauth', $connected['manage_url']);
    $this->assertSame(['password'], $initiators->getManagedFields());
  }

  public function testExpiredTokenReportsWarning(): void {
    $client = $this->createClient();
    $mailSettings = $this->createMailSettings();
    $this->createToken($client, $mailSettings['id'], \CRM_Utils_Time::time() - 60);

    $connected = $this->getInitiators($mailSettings['id'])->getConnected();

    $this->assertNotNull($connected, 'An expired token is still a connection, just a broken one');
    $this->assertSame('warning', $connected['status_severity']);
    $this->assertStringContainsString('re-connect', $connected['status_message']);
  }

  public function testTokenForAnotherAccountIsIgnored(): void {
    $client = $this->createClient();
    $mine = $this->createMailSettings();
    $theirs = $this->createMailSettings();
    $this->createToken($client, $theirs['id'], \CRM_Utils_Time::time() + 3600);

    $this->assertNull($this->getInitiators($mine['id'])->getConnected());
  }

  public function testMostRecentTokenWins(): void {
    $old = $this->createClient();
    $new = $this->createClient();
    $mailSettings = $this->createMailSettings();
    $this->createToken($old, $mailSettings['id'], \CRM_Utils_Time::time() + 3600);
    $this->createToken($new, $mailSettings['id'], \CRM_Utils_Time::time() + 3600);

    $connected = $this->getInitiators($mailSettings['id'])->getConnected();

    $this->assertStringContainsString('#' . $new['id'], $connected['status_message'],
      'Re-authorizing leaves the older token behind; the newest is the live one');
  }

  /**
   * Reading the status must not contact the provider: the form is rendered on
   * every page-view, and OAuthSysToken::refresh() makes a live HTTP call.
   */
  public function testReadingStatusDoesNotCallTheProvider(): void {
    $client = $this->createClient();
    $mailSettings = $this->createMailSettings();
    // Expiry inside Refresh::$threshold, so refresh() would go to the network.
    $this->createToken($client, $mailSettings['id'], \CRM_Utils_Time::time() + 5);

    $connected = $this->getInitiators($mailSettings['id'])->getConnected();

    // DummyProvider has no canned responses, so an HTTP attempt throws rather than returning.
    $this->assertNotNull($connected);
    $this->assertSame('success', $connected['status_severity']);
  }

  public function testCheckTokenTag(): void {
    $tagger = \Civi::service('oauth_client.mail_settings_tag');

    $this->assertSame(123, $tagger->checkTokenTag('MailSettings:123'));
    $this->assertNull($tagger->checkTokenTag(OAuthMailSettingsTag::SETUP_TAG),
      'The in-flight setup tag does not name a record');
    $this->assertNull($tagger->checkTokenTag('MailSettings:'));
    $this->assertNull($tagger->checkTokenTag('MailSettings:abc'));
    $this->assertNull($tagger->checkTokenTag('PaymentProcessor:123'));
    $this->assertNull($tagger->checkTokenTag(''));
  }

}
