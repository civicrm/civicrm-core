<?php

use CRM_OAuth_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
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
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_oauth_client'));
  }

  public function tearDown(): void {
    parent::tearDown();
  }

  /**
   * Generate the URL to request an authorization code from a provider.
   */
  public function testAuthorizationCode(): void {
    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client']);
    $client = $this->createClient();

    $usePerms(['manage OAuth client']);
    $result = Civi\Api4\OAuthClient::authorizationCode()->addWhere('id', '=', $client['id'])->execute();
    $this->assertEquals(1, $result->count());
    foreach ($result as $ac) {
      $url = parse_url($ac['url']);
      $this->assertEquals('example.com', $url['host']);
      $this->assertEquals('/one/auth', $url['path']);
      \parse_str($url['query'], $actualQuery);
      $this->assertEquals('code', $actualQuery['response_type']);
      $this->assertMatchesRegularExpression(';^[cs]_[a-zA-Z0-9]+$;', $actualQuery['state']);
      $this->assertEquals('scope-1-foo,scope-1-bar', $actualQuery['scope']);
      // ? // $this->assertEquals('auto', $actualQuery['approval_prompt']);
      $this->assertEquals('example-id', $actualQuery['client_id']);
      $this->assertMatchesRegularExpression(';civicrm/oauth-client/return;', $actualQuery['redirect_uri']);
    }
  }

  private function createClient(): array {
    $create = Civi\Api4\OAuthClient::create()->setValues([
      'provider' => 'test_example_1',
      'guid' => "example-id",
      'secret' => "example-secret",
    ])->execute();
    $this->assertEquals(1, $create->count());
    $client = $create->first();
    $this->assertTrue(!empty($client['id']));
    return $client;
  }

}
