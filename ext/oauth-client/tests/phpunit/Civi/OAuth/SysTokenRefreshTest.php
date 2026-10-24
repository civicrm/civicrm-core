<?php

namespace Civi\OAuth;

use Civi\Api4\OAuthClient;
use Civi\Api4\OAuthSysToken;
use Civi\Core\HookInterface;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SysTokenRefreshTest extends \PHPUnit\Framework\TestCase implements
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
    // The hook result is cached, so drop any list built before this class registered its provider.
    \Civi::cache('long')->delete('OAuthProvider_list');
  }

  public function hook_civicrm_oauthProviders(&$providers) {
    $providers = array_merge($providers, $this->providers);
  }

  /**
   * @param array $response
   *   The token endpoint's reply: ['status' => int, 'body' => array].
   * @param array|null $error
   *   An error already recorded on the token.
   * @param string $tag
   */
  private function createExpiredToken(array $response, ?array $error = NULL, string $tag = 'Example:1'): array {
    $this->providers['dummy'] = [
      'name' => 'dummy',
      'title' => 'Dummy Provider',
      'class' => 'Civi\OAuth\DummyProvider',
      'options' => [
        'urlAuthorize' => 'https://dummy/authorize',
        'urlAccessToken' => 'https://dummy/token',
        'urlResourceOwnerDetails' => 'https://dummy/owner',
        'scopes' => ['mail'],
        'cannedResponses' => [
          [
            'status' => $response['status'],
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($response['body']),
          ],
        ],
      ],
    ];
    \Civi::cache('long')->delete('OAuthProvider_list');

    $client = OAuthClient::create(FALSE)
      ->setValues(['provider' => 'dummy', 'guid' => 'guid-' . uniqid(), 'secret' => 'shh'])
      ->execute()
      ->single();
    return OAuthSysToken::create(FALSE)
      ->setValues([
        'client_id' => $client['id'],
        'tag' => $tag,
        'access_token' => 'old-access',
        'refresh_token' => 'old-refresh',
        'expires' => \CRM_Utils_Time::time() - 60,
        'error' => $error,
      ])
      ->execute()
      ->single();
  }

  private function reload(array $token): array {
    return OAuthSysToken::get(FALSE)
      ->addSelect('access_token', 'expires', 'error')
      ->addWhere('id', '=', $token['id'])
      ->execute()
      ->single();
  }

  private function rejection(): array {
    return [
      'status' => 400,
      'body' => ['error' => 'invalid_grant', 'error_description' => 'The refresh token has expired.'],
    ];
  }

  public function testRejectedRefreshIsLeftOutAndRecordsTheError(): void {
    $token = $this->createExpiredToken($this->rejection());

    $result = OAuthSysToken::refresh(FALSE)->addWhere('id', '=', $token['id'])->execute();

    $this->assertCount(0, $result, 'A token that did not need refreshing would come back; this one failed');
    $stored = $this->reload($token);
    $this->assertSame('old-access', $stored['access_token']);
    $this->assertSame('invalid_grant', $stored['error']['error']);
    $this->assertSame('The refresh token has expired.', $stored['error']['error_description']);
    $this->assertEqualsWithDelta(\CRM_Utils_Time::time(), $stored['error']['time'], 5);
  }

  public function testRejectedRefreshStopsMailPolling(): void {
    $this->createExpiredToken($this->rejection(), NULL, 'MailSettings:7');
    $mailSettings = ['id' => 7, 'password' => ''];

    $this->expectException(OAuthException::class);
    $this->expectExceptionMessage('The refresh token has expired.');
    \CRM_OAuth_MailSetup::alterMailStore($mailSettings);
  }

  public function testMailAccountWithoutTokenIsLeftAlone(): void {
    $mailSettings = ['id' => 8, 'password' => 'stored-secret'];

    \CRM_OAuth_MailSetup::alterMailStore($mailSettings);

    $this->assertSame('stored-secret', $mailSettings['password']);
    $this->assertArrayNotHasKey('auth', $mailSettings);
  }

  public function testSuccessfulRefreshClearsAnEarlierError(): void {
    $token = $this->createExpiredToken(
      [
        'status' => 200,
        'body' => ['access_token' => 'new-access', 'refresh_token' => 'new-refresh', 'token_type' => 'Bearer', 'expires_in' => 3600],
      ],
      ['error' => 'temporarily_unavailable', 'error_description' => NULL, 'time' => 1]
    );

    OAuthSysToken::refresh(FALSE)->addWhere('id', '=', $token['id'])->execute();

    $stored = $this->reload($token);
    $this->assertSame('new-access', $stored['access_token']);
    $this->assertGreaterThan(\CRM_Utils_Time::time(), $stored['expires']);
    $this->assertEmpty($stored['error']);
  }

}
