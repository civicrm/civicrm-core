<?php

namespace Civi\OAuth;

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class AuthCodeFlowTest extends \PHPUnit\Framework\TestCase implements
    HeadlessInterface,
    HookInterface,
    TransactionalInterface {

  use \Civi\Test\ContactTestTrait;
  use \Civi\Test\Api3TestTrait;

  private $providers = [];

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

  public function hook_civicrm_oauthProviders(&$providers) {
    $providers = array_merge($providers, $this->providers);
  }

  public function makeDummyProviderThatGetsAToken(): void {
    $idTokenHeader = ['alg' => 'RS256', 'kid' => '123456789', 'typ' => 'JWT'];
    $idTokenPayload = [
      'iss' => 'https://dummy',
      'azp' => 'something',
      'aud' => 'something',
      'sub' => '987654321',
      'email' => 'test@baz.biff',
      'email_verified' => TRUE,
      'at_hash' => 'fake hash value',
      'nonce' => '111',
      'iat' => 1619151829,
      'exp' => 9999999999,
    ];
    $idToken = base64_encode(json_encode($idTokenHeader))
      . '.' . base64_encode(json_encode($idTokenPayload));

    $authServerResponse = [
      'status' => 200,
      'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
      'body' => json_encode(
        [
          'access_token' => 'example-access-token-value',
          'token_type' => 'Bearer',
          'scope' => 'foo',
          'refresh_token' => 'example-refresh-token-value',
          'created_at' => time(),
          'expires_in' => 3600,
          'id_token' => $idToken,

        ]
      ),
    ];

    $this->providers['dummy'] = [
      'name' => 'dummy',
      'title' => 'Dummy Provider',
      'class' => 'Civi\OAuth\DummyProvider',
      'options' => [
        'urlAuthorize' => 'https://dummy/authorize',
        'urlAccessToken' => 'https://dummy/token',
        'urlResourceOwnerDetails' => '{{use_id_token}}',
        'scopes' => ['foo'],
        'cannedResponses' => [$authServerResponse],
      ],
      'contactTemplate' => [
        'values' => [
          'contact_type' => 'Individual',
        ],
        'chain' => [
          'email' => [
            'Email',
            'create',
            [
              'values' => [
                'contact_id' => '$id',
                'email' => '{{token.resource_owner.email}}',
              ],
            ],
          ],
        ],
      ],
    ];

    require_once 'tests/fixtures/DummyProvider.php';
  }

  public function makeDummyProviderClient(): array {
    return \Civi\Api4\OAuthClient::create(FALSE)->setValues(
      [
        'provider' => 'dummy',
        'guid' => "example-client-guid",
        'secret' => "example-secret",
      ]
    )->execute()->single();
  }

  public function testFetchAndStoreSysToken() {
    $this->makeDummyProviderThatGetsAToken();
    $client = $this->makeDummyProviderClient();

    /** @var OAuthTokenFacade $tokenService */
    $tokenService = \Civi::service('oauth2.token');

    // This is the call that \CRM_OAuth_Page_Return::run would make upon receiving an auth code.
    $tokenRecord = $tokenService->init(
      [
        'client' => $client,
        'scope' => 'foo',
        'tag' => NULL,
        'storage' => 'OAuthSysToken',
        'grant_type' => 'authorization_code',
        'cred' => ['code' => 'example-auth-code'],
      ]
    );
    $this->assertTrue(is_numeric($tokenRecord['id']));
    $this->assertEquals($client['id'], $tokenRecord['client_id']);
    $this->assertEquals(['foo'], $tokenRecord['scopes']);
    $this->assertEquals('example-access-token-value', $tokenRecord['access_token']);
    $this->assertEquals('example-refresh-token-value', $tokenRecord['refresh_token']);
    $this->assertEquals('Bearer', $tokenRecord['token_type']);
    $this->assertEquals('test@baz.biff', $tokenRecord['resource_owner_name']);
    $this->assertEquals(
      [
        'iss' => 'https://dummy',
        'azp' => 'something',
        'aud' => 'something',
        'sub' => '987654321',
        'email' => 'test@baz.biff',
        'email_verified' => TRUE,
        'at_hash' => 'fake hash value',
        'nonce' => '111',
        'iat' => 1619151829,
        'exp' => 9999999999,
      ],
      $tokenRecord['resource_owner']);
  }

}
