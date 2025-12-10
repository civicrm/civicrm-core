<?php

use CRM_OAuth_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Test the "grant" methods (authorizationCode, clientCredential, etc).
 *
 * @group headless
 */
class api_v4_OAuthClientGrantTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  public function setUp(): void {
    parent::setUp();
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_oauth_client WHERE guid <> "{civi_connect}"'));
  }

  public function tearDown(): void {
    parent::tearDown();
  }

  /**
   * Generate the URL to request an authorization code from a provider.
   * @param ?string $scopeToUse
   * @dataProvider scopeProvider
   */
  public function testAuthorizationCode(?string $scopeToUse): void {
    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client']);
    $client = $this->createClient('test_example_1');

    $usePerms(['manage OAuth client']);
    // This `if` block could be optimized a little but then it wouldn't be
    // checking that setScopes is chainable. Is that important? :shrug: But it's
    // what led to this addition.
    if ($scopeToUse) {
      $result = Civi\Api4\OAuthClient::authorizationCode()->addWhere('id', '=', $client['id'])->setScopes($scopeToUse)->execute();
    }
    else {
      $result = Civi\Api4\OAuthClient::authorizationCode()->addWhere('id', '=', $client['id'])->execute();
    }
    $this->assertEquals(1, $result->count());
    foreach ($result as $ac) {
      $this->assertEquals('query', $ac['response_mode']);
      $url = parse_url($ac['url']);
      $this->assertEquals('example.com', $url['host']);
      $this->assertEquals('/one/auth', $url['path']);
      \parse_str($url['query'], $actualQuery);
      $this->assertEquals('code', $actualQuery['response_type']);
      $this->assertMatchesRegularExpression(';^CC_[a-zA-Z0-9]+$;', $actualQuery['state']);
      $this->assertEquals($scopeToUse ?: 'scope-1-foo,scope-1-bar', $actualQuery['scope']);
      // ? // $this->assertEquals('auto', $actualQuery['approval_prompt']);
      $this->assertEquals('example-id', $actualQuery['client_id']);
      $this->assertTrue(empty($actualQuery['response_mode']), 'response_mode should be empty');
      $this->assertMatchesRegularExpression(';civicrm/oauth-client/return;', $actualQuery['redirect_uri']);
    }

    try {
      Civi\Api4\OAuthClient::authorizationCode()
        ->addWhere('id', '=', $client['id'])
        ->setResponseMode('web_message')
        ->execute();
      $this->fail('test_example_1 should not support response_mode=web_message');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertMatchesRegularExpression(';Unsupported response mode: web_message;', $e->getMessage());
    }
  }

  /**
   * Generate the URL to request an authorization code from a provider.
   */
  public function testAuthorizationCode_webMessage(): void {
    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client', 'administer payment processors']);
    $client = $this->createClient('test_example_3');

    $usePerms(['manage OAuth client', 'administer payment processors']);
    $result = Civi\Api4\OAuthClient::authorizationCode()
      ->addWhere('id', '=', $client['id'])
      ->setResponseMode('web_message')
      ->execute();
    $this->assertEquals(1, $result->count());
    foreach ($result as $ac) {
      $this->assertEquals('web_message', $ac['response_mode']);

      $url = parse_url($ac['url']);
      $this->assertEquals('example.com', $url['host']);
      $this->assertEquals('/three/auth', $url['path']);
      \parse_str($url['query'], $actualQuery);
      $this->assertEquals('code', $actualQuery['response_type']);
      $this->assertMatchesRegularExpression(';^CC_[a-zA-Z0-9]+$;', $actualQuery['state']);
      $this->assertEquals('scope-3-foo,scope-3-bar', $actualQuery['scope']);
      // ? // $this->assertEquals('auto', $actualQuery['approval_prompt']);
      $this->assertEquals('example-id', $actualQuery['client_id']);
      $this->assertEquals('web_message', $actualQuery['response_mode']);
      $this->assertMatchesRegularExpression(';civicrm/oauth-client/return;', $actualQuery['redirect_uri']);
      $this->assertEquals($actualQuery['redirect_uri'], $ac['continue_url']);
    }
  }

  /**
   * Generate the URL to request an authorization code from a provider.
   */
  public function testAuthorizationCode_prohibited(): void {
    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client', 'administer payment processors']);
    $client = $this->createClient('test_example_3');
    $usePerms(['access CiviContribute']);

    // With dropped privileges, we can see the client...
    $get = Civi\Api4\OAuthClient::get()
      ->addWhere('id', '=', $client['id'])
      ->execute()
      ->single();
    $this->assertEquals('test_example_3', $get['provider']);

    // But we cannot add new tokens... with any of the standard grant-types...

    try {
      Civi\Api4\OAuthClient::authorizationCode()
        ->addWhere('id', '=', $client['id'])
        ->execute();
      $this->fail('test_example_3 should require a higher privilege (administer payment processors)');
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertMatchesRegularExpression(';Insufficient.*authorizationCode.*test_example_3;', $e->getMessage());
    }

    try {
      Civi\Api4\OAuthClient::clientCredential()
        ->addWhere('id', '=', $client['id'])
        ->execute();
      $this->fail('test_example_3 should require a higher privilege (administer payment processors)');
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertMatchesRegularExpression(';Insufficient.*clientCredential.*test_example_3;', $e->getMessage());
    }

    try {
      Civi\Api4\OAuthClient::userPassword()
        ->addWhere('id', '=', $client['id'])
        ->execute();
      $this->fail('test_example_3 should require a higher privilege (administer payment processors)');
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertMatchesRegularExpression(';Insufficient.*userPassword.*test_example_3;', $e->getMessage());
    }
  }

  private function createClient(string $provider): array {
    $create = Civi\Api4\OAuthClient::create()->setValues([
      'provider' => $provider,
      'guid' => "example-id",
      'secret' => "example-secret",
    ])->execute();
    $this->assertEquals(1, $create->count());
    $client = $create->first();
    $this->assertTrue(!empty($client['id']));
    return $client;
  }

  public static function scopeProvider(): array {
    return [[NULL], ['scope-1-bar']];
  }

}
