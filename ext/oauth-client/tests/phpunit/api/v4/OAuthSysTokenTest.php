<?php

use CRM_OAuth_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Create, read, and destroy OAuth tokens.
 *
 * @group headless
 */
class api_v4_OAuthSysTokenTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  public function setUp(): void {
    parent::setUp();
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_oauth_client'));
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_oauth_systoken'));
  }

  public function tearDown(): void {
    parent::tearDown();
  }

  /**
   * Create, read, and destroy token - with full access to secrets.
   */
  public function testFullApiAccess(): void {
    $random = CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC);
    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client', 'manage OAuth client secrets']);
    $createClient = Civi\Api4\OAuthClient::create()->setValues([
      'provider' => 'test_example_1',
      'guid' => "example-id-$random" ,
      'secret' => "example-secret-$random",
    ])->execute();
    $client = $createClient->first();
    $this->assertTrue(is_numeric($client['id']));

    $usePerms(['manage OAuth client', 'manage OAuth client secrets']);
    $createToken = Civi\Api4\OAuthSysToken::create()->setValues([
      'client_id' => $client['id'],
      'access_token' => "example-access-token-$random",
      'refresh_token' => "example-refresh-token-$random",
    ])->execute();
    $token = $createToken->first();
    $this->assertTrue(is_numeric($token['id']));
    $this->assertEquals($client['id'], $token['client_id']);
    $this->assertEquals("example-access-token-$random", $token['access_token']);
    $this->assertEquals("example-refresh-token-$random", $token['refresh_token']);

    $usePerms(['manage OAuth client', 'manage OAuth client secrets']);
    $getTokens = Civi\Api4\OAuthSysToken::get()->execute();
    $this->assertEquals(1, count($getTokens));
    // ^^ Started at 0, added 1.
    $token = $getTokens->first();
    $this->assertEquals($client['id'], $token['client_id']);
    $this->assertEquals("example-access-token-$random", $token['access_token']);
    $this->assertEquals("example-refresh-token-$random", $token['refresh_token']);

    $usePerms(['manage OAuth client', 'manage OAuth client secrets']);
    $updateToken = Civi\Api4\OAuthSysToken::update()
      ->setWhere([['client_id.guid', '=', "example-id-$random"]])
      ->setValues(['access_token' => "revised-access-token-$random"])
      ->execute();

    $usePerms(['manage OAuth client', 'manage OAuth client secrets']);
    $getTokens = Civi\Api4\OAuthSysToken::get()->execute();
    $this->assertEquals(1, count($getTokens));
    $token = $getTokens->first();
    $this->assertEquals($client['id'], $token['client_id']);
    $this->assertEquals("revised-access-token-$random", $token['access_token']);
    $this->assertEquals("example-refresh-token-$random", $token['refresh_token']);
  }

  /**
   * Create, read, and destroy a token - with limited API access (cannot access token secrets).
   */
  public function testLimitedApiAccess(): void {
    $random = CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC);
    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client']);
    $createClient = Civi\Api4\OAuthClient::create()->setValues([
      'provider' => 'test_example_1',
      'guid' => "example-id-$random" ,
      'secret' => "example-secret-$random",
    ])->execute();
    $client = $createClient->first();
    $this->assertTrue(is_numeric($client['id']));

    // User has some access to tokens -- but secret fields are off limits.
    try {
      $usePerms(['manage OAuth client']);
      Civi\Api4\OAuthSysToken::create()->setValues([
        'client_id' => $client['id'],
        'access_token' => "ignored-access-token-$random",
        'refresh_token' => "ignored-refresh-token-$random",
      ])->execute();
      $this->fail('Expected exception - User should not be able to write secret values.');
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      // OK
    }

    // Tokens with secret values can still be created by system services.
    $usePerms(['manage OAuth client']);
    $createTokenFull = Civi\Api4\OAuthSysToken::create(FALSE)->setValues([
      'client_id' => $client['id'],
      'access_token' => "example-access-token-$random",
      'refresh_token' => "example-refresh-token-$random",
    ])->execute();
    $token = $createTokenFull->first();
    $this->assertTrue(is_numeric($token['id']));
    $this->assertEquals($client['id'], $token['client_id']);
    $this->assertEquals("example-access-token-$random", $token['access_token']);
    $this->assertEquals("example-refresh-token-$random", $token['refresh_token']);

    $usePerms(['manage OAuth client']);
    $getTokens = Civi\Api4\OAuthSysToken::get()->execute();
    $this->assertEquals(1, count($getTokens));
    // ^^ Started at 0, added 1.
    $token = $getTokens->first();
    $this->assertEquals($client['id'], $token['client_id']);
    $this->assertArrayNotHasKey('access_token', $token);
    $this->assertArrayNotHasKey('refresh_token', $token);

    $usePerms(['manage OAuth client']);
    try {
      Civi\Api4\OAuthSysToken::update()
        ->setWhere([['client_id.guid', '=', "example-id-$random"]])
        ->setValues(['access_token' => "revised-access-token-$random"])
        ->execute();
      $this->fail('Expected exception - User should not be able to write secret values.');
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      // OK
    }

    $usePerms(['manage OAuth client', 'manage OAuth client secrets']);
    $getTokens = Civi\Api4\OAuthSysToken::get()->execute();
    $this->assertEquals(1, count($getTokens));
    $token = $getTokens->first();
    $this->assertEquals($client['id'], $token['client_id']);
    $this->assertEquals("example-access-token-$random", $token['access_token']);
    $this->assertEquals("example-refresh-token-$random", $token['refresh_token']);
  }

  public function testGetByScope(): void {
    $random = CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC);
    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client', 'manage OAuth client secrets']);
    $createClient = Civi\Api4\OAuthClient::create()->setValues([
      'provider' => 'test_example_1',
      'guid' => "example-id-$random" ,
      'secret' => "example-secret-$random",
    ])->execute();
    $client = $createClient->first();
    $this->assertTrue(is_numeric($client['id']));

    $usePerms(['manage OAuth client', 'manage OAuth client secrets']);
    $createToken = Civi\Api4\OAuthSysToken::create()->setValues([
      'client_id' => $client['id'],
      'access_token' => "example-access-token-$random",
      'refresh_token' => "example-refresh-token-$random",
      'scopes' => ['foo', 'bar'],
    ])->execute();
    $token = $createToken->first();
    $this->assertTrue(is_numeric($token['id']));
    $this->assertEquals($client['id'], $token['client_id']);
    $this->assertEquals("example-access-token-$random", $token['access_token']);
    $this->assertEquals("example-refresh-token-$random", $token['refresh_token']);
    $this->assertEquals(['foo', 'bar'], $token['scopes']);

    $usePerms(['manage OAuth client']);
    $getTokens = Civi\Api4\OAuthSysToken::get()
      ->addWhere('client_id.provider', '=', 'test_example_1')
      ->addWhere('scopes', 'CONTAINS', 'foo')
      ->execute();
    $this->assertEquals(1, count($getTokens));
    $this->assertEquals($createToken->first()['id'], $getTokens->first()['id']);

    $usePerms(['manage OAuth client']);
    $getTokens = Civi\Api4\OAuthSysToken::get()
      ->addWhere('client_id.provider', '=', 'test_example_1')
      ->addWhere('scopes', 'CONTAINS', 'nada')
      ->execute();
    $this->assertEquals(0, count($getTokens));

    $usePerms(['manage OAuth client']);
    $getTokens = Civi\Api4\OAuthSysToken::get()
      ->addWhere('client_id.provider', '=', 'test_example_2')
      ->addWhere('scopes', 'CONTAINS', 'foo')
      ->execute();
    $this->assertEquals(0, count($getTokens));
  }

}
