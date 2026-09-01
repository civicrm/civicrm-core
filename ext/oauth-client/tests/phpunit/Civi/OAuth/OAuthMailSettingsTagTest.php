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

  private function createToken(array $client, int $mailSettingsId, int $expires, ?string $refreshToken = 'rt'): array {
    return OAuthSysToken::create(FALSE)
      ->setValues([
        'client_id' => $client['id'],
        'tag' => 'MailSettings:' . $mailSettingsId,
        'access_token' => 'at',
        'refresh_token' => $refreshToken,
        'expires' => $expires,
        'resource_owner_name' => 'bob@example.org',
      ])
      ->execute()
      ->single();
  }

  private function getTagger(): OAuthMailSettingsTag {
    return \Civi::service('oauth_client.mail_settings_tag');
  }

  private function getInitiators(int $mailSettingsId): Initiators {
    return Initiators::create(['for' => 'MailSettings', 'mail_settings_id' => $mailSettingsId]);
  }

  public function testUnconnectedAccountOffersEachEligibleClient(): void {
    $clientA = $this->createClient();
    $clientB = $this->createClient();
    $mailSettings = $this->createMailSettings();

    $initiators = $this->getInitiators($mailSettings['id']);

    $this->assertSame([], $initiators->getConnected(), 'An account with no token is not connected');
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
    $this->assertNotEmpty($connected);
    $this->assertSame('success', $connected['status_severity']);
    $this->assertStringContainsString('bob@example.org', $connected['status_message']);
    $this->assertStringContainsString('Dummy Mail Provider', $connected['status_message']);
    $this->assertStringContainsString('civicrm/admin/oauth', $connected['manage_url']);
    $this->assertSame(['password'], $initiators->getManagedFields());
  }

  public function testExpiredAccessTokenWithRefreshTokenIsStillConnected(): void {
    $client = $this->createClient();
    $mailSettings = $this->createMailSettings();
    $this->createToken($client, $mailSettings['id'], \CRM_Utils_Time::time() - 60);

    $connected = $this->getInitiators($mailSettings['id'])->getConnected();

    $this->assertSame('success', $connected['status_severity'],
      'Access tokens expire within the hour; the next poll refreshes them');
    $this->assertArrayNotHasKey('refresh_token', $this->getTagger()->getToken($mailSettings['id']));
  }

  public function testRejectedRefreshReportsTheProviderError(): void {
    $client = $this->createClient();
    $mailSettings = $this->createMailSettings();
    $token = $this->createToken($client, $mailSettings['id'], \CRM_Utils_Time::time() - 60);
    OAuthSysToken::update(FALSE)
      ->addWhere('id', '=', $token['id'])
      ->addValue('error', ['error' => 'invalid_grant', 'error_description' => 'The refresh token was revoked.', 'time' => \CRM_Utils_Time::time()])
      ->execute();

    $connected = $this->getInitiators($mailSettings['id'])->getConnected();

    $this->assertSame('danger', $connected['status_severity'],
      'A refresh token is present, but the provider has already refused it');
    $this->assertStringContainsString('The refresh token was revoked.', $connected['status_message']);
    $this->assertStringContainsString('re-connect', $connected['status_message']);
  }

  public function testRejectedClientAsksForANewSecret(): void {
    $client = $this->createClient();
    $mailSettings = $this->createMailSettings();
    $token = $this->createToken($client, $mailSettings['id'], \CRM_Utils_Time::time() - 60);
    OAuthSysToken::update(FALSE)
      ->addWhere('id', '=', $token['id'])
      ->addValue('error', ['error' => 'invalid_client', 'error_description' => 'The client secret has expired.', 'time' => \CRM_Utils_Time::time()])
      ->execute();

    $connected = $this->getInitiators($mailSettings['id'])->getConnected();

    $this->assertSame('danger', $connected['status_severity']);
    $this->assertStringContainsString('The client secret has expired.', $connected['status_message']);
    $this->assertStringContainsString('client secret is updated', $connected['status_message']);
    $this->assertStringNotContainsString('re-connect', $connected['status_message'],
      'Signing in again does not fix a rejected client');
  }

  public function testExpiredTokenReportsWarning(): void {
    $client = $this->createClient();
    $mailSettings = $this->createMailSettings();
    $this->createToken($client, $mailSettings['id'], \CRM_Utils_Time::time() - 60, NULL);

    $connected = $this->getInitiators($mailSettings['id'])->getConnected();

    $this->assertNotEmpty($connected, 'An expired token is still a connection, just a broken one');
    $this->assertSame('warning', $connected['status_severity']);
    $this->assertStringContainsString('re-connect', $connected['status_message']);
  }

  public function testTokenForAnotherAccountIsIgnored(): void {
    $client = $this->createClient();
    $mine = $this->createMailSettings();
    $theirs = $this->createMailSettings();
    $this->createToken($client, $theirs['id'], \CRM_Utils_Time::time() + 3600);

    $this->assertSame([], $this->getInitiators($mine['id'])->getConnected());
  }

  public function testMostRecentTokenWins(): void {
    $old = $this->createClient();
    $new = $this->createClient();
    $mailSettings = $this->createMailSettings();
    $this->createToken($old, $mailSettings['id'], \CRM_Utils_Time::time() + 3600);
    $this->createToken($new, $mailSettings['id'], \CRM_Utils_Time::time() + 3600);

    $connected = $this->getInitiators($mailSettings['id'])->getConnected();

    $this->assertStringContainsString('#' . $new['id'], $connected['status_message'],
      'When several tokens share the tag, the newest is the live one');
  }

  public function testReconnectRemovesOlderTokensForThatAccountOnly(): void {
    $client = $this->createClient();
    $mailSettings = $this->createMailSettings();
    $other = $this->createMailSettings();
    $stale = $this->createToken($client, $mailSettings['id'], \CRM_Utils_Time::time() - 3600);
    $untouched = $this->createToken($client, $other['id'], \CRM_Utils_Time::time() - 3600);
    $fresh = $this->createToken($client, $mailSettings['id'], \CRM_Utils_Time::time() + 3600);
    $nextUrl = 'https://example.org/landing';

    \CRM_OAuth_MailSetup::onReturn($fresh, $nextUrl);

    $remaining = OAuthSysToken::get(FALSE)->addSelect('id')->execute()->column('id');
    $this->assertNotContains($stale['id'], $remaining);
    $this->assertContains($fresh['id'], $remaining);
    $this->assertContains($untouched['id'], $remaining);
    $this->assertSame('https://example.org/landing', $nextUrl, 'Re-connecting returns to wherever it was started');
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
    $this->assertNotEmpty($connected);
    $this->assertSame('success', $connected['status_severity']);
  }

  public function testApiFieldNamesTheConnectedClient(): void {
    $client = $this->createClient();
    $connected = $this->createMailSettings();
    $plain = $this->createMailSettings();
    $this->createToken($client, $connected['id'], \CRM_Utils_Time::time() + 3600);

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
    $this->createToken($client, $connected['id'], \CRM_Utils_Time::time() + 3600);

    $ids = MailSettings::get(FALSE)
      ->addSelect('id')
      ->addWhere('oauth_client_id', '=', $client['id'])
      ->execute()
      ->column('id');

    $this->assertEquals([$connected['id']], $ids);
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
