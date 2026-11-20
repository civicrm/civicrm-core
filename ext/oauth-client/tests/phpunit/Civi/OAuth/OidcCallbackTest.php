<?php

namespace Civi\OAuth;

use Civi\Api4\OAuthClient;
use Civi\Api4\OAuthSysToken;
use Civi\Core\HookInterface;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use Firebase\JWT\JWT;
use GuzzleHttp\Psr7\Response;

/**
 * @group headless
 */
class OidcCallbackTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  private array $returns = [];

  private array $errors = [];

  private int $tokenHooks = 0;

  private array $pendingReplay = [];

  private bool $replayRejected = FALSE;

  public function setUpHeadless() {
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  public function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../fixtures/CallbackProvider.php';
    CallbackProvider::$responses = [];
    CallbackProvider::$requests = [];
    \Civi::cache('long')->delete('OAuthProvider_list');
  }

  public function tearDown(): void {
    $_GET = $_REQUEST = [];
    CallbackProvider::$responses = [];
    CallbackProvider::$requests = [];
    parent::tearDown();
  }

  public function hook_civicrm_oauthProviders(&$providers) {
    $providers['callback-test'] = [
      'name' => 'callback-test',
      'title' => 'Callback test',
      'class' => CallbackProvider::class,
      'pkceMethods' => ['S256'],
      'options' => [
        'discovery' => [
          'issuer' => 'https://id.example.org',
          'authorization_endpoint' => 'https://id.example.org/authorize',
          'token_endpoint' => 'https://id.example.org/token',
          'jwks_uri' => 'https://id.example.org/keys',
        ],
      ],
    ];
  }

  public function hook_civicrm_oauthToken($op, $storage, &$record) {
    $this->tokenHooks++;
    if ($this->pendingReplay) {
      $query = $this->pendingReplay;
      $this->pendingReplay = [];
      try {
        $this->runPage($query);
      }
      catch (OAuthException $e) {
        $this->replayRejected = TRUE;
      }
    }
  }

  public function hook_civicrm_oauthReturn($record, &$nextUrl) {
    $this->returns[] = $record['resource_owner'];
  }

  public function hook_civicrm_oauthReturnError($error, $description, $uri, $state) {
    $this->errors[] = $error;
  }

  private function start(): array {
    $client = OAuthClient::create(FALSE)
      ->setValues(['provider' => 'callback-test', 'guid' => 'callback-client', 'secret' => 'test'])
      ->execute()->single();
    $result = OAuthClient::authorizationCode(FALSE)
      ->addWhere('id', '=', $client['id'])
      ->setStorage('OAuthSysToken')
      ->setTag('Login')
      ->setLandingUrl(NULL)
      ->execute()->first();
    parse_str(parse_url((string) $result['url'], PHP_URL_QUERY), $query);
    $this->assertNotEmpty($query['nonce']);
    $this->assertSame('S256', $query['code_challenge_method']);
    return $query;
  }

  private function runPage(array $query): void {
    $_GET = $_REQUEST = $query;
    ob_start();
    try {
      (new \CRM_OAuth_Page_Return())->run();
    }
    finally {
      ob_end_clean();
    }
  }

  /**
   * @dataProvider tokenCases
   */
  public function testCallback(string $case): void {
    $query = $this->start();
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $public = openssl_pkey_get_details($key)['rsa'];
    $claims = [
      'iss' => 'https://id.example.org',
      'sub' => 'alice',
      'aud' => 'callback-client',
      'iat' => time(),
      'exp' => time() + 300,
      'nonce' => $case === 'nonce' ? 'other' : $query['nonce'],
    ];
    if ($case === 'issuer') {
      $claims['iss'] = 'https://other.example.org';
    }
    $signer = $case === 'signature' ? openssl_pkey_new(['private_key_bits' => 2048]) : $key;
    CallbackProvider::$responses = [
      new Response(200, ['Content-Type' => 'application/json'], json_encode([
        'access_token' => 'mock-token',
        'token_type' => 'Bearer',
        'id_token' => JWT::encode($claims, $signer, 'RS256', 'test'),
      ])),
      new Response(200, [], json_encode([
        'keys' => [[
          'kid' => 'test',
          'kty' => 'RSA',
          'alg' => 'RS256',
          'n' => JWT::urlsafeB64Encode($public['n']),
          'e' => JWT::urlsafeB64Encode($public['e']),
        ],
        ],
      ])),
    ];
    $before = OAuthSysToken::get(FALSE)->execute()->count();
    $callback = ['state' => $query['state'], 'code' => 'mock-code'];
    if ($case === 'valid') {
      $this->pendingReplay = $callback;
    }
    $this->runPage($callback);
    if ($case === 'valid') {
      $this->assertTrue($this->replayRejected);
    }
    $valid = $case === 'valid';
    $this->assertSame($before + (int) $valid, OAuthSysToken::get(FALSE)->execute()->count());
    $this->assertSame($valid ? [$claims] : [], $this->returns);
    $this->assertSame($valid ? [] : ['invalid_token'], $this->errors);
    $this->assertSame((int) $valid, $this->tokenHooks);
    $requests = CallbackProvider::$requests;
    try {
      $this->runPage($callback);
      $this->fail('Replayed callback accepted.');
    }
    catch (OAuthException $e) {
      $this->assertSame($requests, CallbackProvider::$requests);
      $this->assertSame((int) $valid, $this->tokenHooks);
    }
  }

  public static function tokenCases(): array {
    return [['valid'], ['nonce'], ['issuer'], ['signature']];
  }

  public function testProviderErrorConsumesState(): void {
    $query = $this->start();
    $this->runPage(['state' => $query['state'], 'error' => 'access_denied']);
    $this->assertSame(['access_denied'], $this->errors);
    $this->assertSame(0, $this->tokenHooks);
    $this->expectException(OAuthException::class);
    $this->runPage(['state' => $query['state'], 'code' => 'late-code']);
  }

  /**
   * @dataProvider invalidStates
   */
  public function testInvalidStateStopsBeforeTokenExchange(string $case): void {
    $query = $this->start();
    $state = \Civi::service('oauth2.state')->load($query['state']);
    if ($case === 'expired') {
      $state['time'] = \CRM_Utils_Time::time() - 7200;
    }
    else {
      $state['session'] = 'another-browser';
    }
    \Civi::cache('session')->set($query['state'], $state);
    $before = OAuthSysToken::get(FALSE)->execute()->count();
    try {
      $this->runPage(['state' => $query['state'], 'code' => 'mock-code']);
      $this->fail('Invalid state accepted.');
    }
    catch (OAuthException $e) {
      $this->assertSame([], CallbackProvider::$requests);
      $this->assertSame([], $this->returns);
      $this->assertSame(0, $this->tokenHooks);
      $this->assertSame($before, OAuthSysToken::get(FALSE)->execute()->count());
    }
  }

  public static function invalidStates(): array {
    return [['expired'], ['browser']];
  }

}
