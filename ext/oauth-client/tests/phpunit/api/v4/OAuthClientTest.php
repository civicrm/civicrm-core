<?php

use CRM_OAuth_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Create, read, and destroy OAuth clients.
 *
 * @group headless
 */
class api_v4_OAuthClientTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  public function setUp(): void {
    parent::setUp();
    $this->assertSame(0, (int) CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_oauth_client WHERE guid <> "{civi_connect}"'));
  }

  public function tearDown(): void {
    parent::tearDown();
  }

  /**
   * Basic sanity check - create, read, and delete a client.
   */
  public function testBasic(): void {
    $random = CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC);
    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client']);
    $create = Civi\Api4\OAuthClient::create()->setValues([
      'provider' => 'test_example_1',
      'guid' => "example-id-$random" ,
      'secret' => "example-secret-$random",
      'pkce_method' => 'S256',
    ])->execute();
    $this->assertSame(1, $create->count());
    $client = $create->first();
    $this->assertSame("example-id-$random", $client['guid']);
    $this->assertSame("example-secret-$random", $client['secret']);
    $this->assertSame('S256', $client['pkce_method']);

    $usePerms(['manage OAuth client']);
    // If we can tighten perm model: $usePerms(['manage OAuth client', 'manage OAuth client secrets']);
    $get = Civi\Api4\OAuthClient::get(FALSE)->addWhere('guid', '=', "example-id-$random")->execute();
    $this->assertSame(1, $get->count());
    $client = $get->first();
    $this->assertSame("example-id-$random", $client['guid']);
    $this->assertSame("example-secret-$random", $client['secret']);
    $this->assertSame('S256', $client['pkce_method']);

    $usePerms(['manage OAuth client']);
    Civi\Api4\OAuthClient::delete(FALSE)->addWhere('guid', '=', "example-id-$random")->execute();
    $get = Civi\Api4\OAuthClient::get(FALSE)->addWhere('guid', '=', "example-id-$random")->execute();
    $this->assertSame(0, $get->count());
  }

  /**
   * We may provide general 'get' access to OAuthClient for several users, but the 'secret'
   * field specifically requires 'manage OAuth client'.
   *
   * @return void
   */
  public function testRestrictedSecret(): void {
    $random = CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC);

    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    // With more perms, we can create the client...
    $usePerms(['manage OAuth client', 'administer payment processors']);
    $create = Civi\Api4\OAuthClient::create()->setValues([
      'provider' => 'test_example_3',
      'guid' => "example-id-$random" ,
      'secret' => "example-secret-$random",
    ])->execute();
    $this->assertSame(1, $create->count());
    $client = $create->first();
    $this->assertSame("example-id-$random", $client['guid']);
    $this->assertSame("example-secret-$random", $client['secret']);

    // With some perms, we can view the client... but not the secret...
    $usePerms(['administer payment processors']);
    $client = Civi\Api4\OAuthClient::get()
      ->addWhere('provider', '=', 'test_example_3')
      ->execute()
      ->single();
    $this->assertSame("example-id-$random", $client['guid']);
    $this->assertTrue(empty($client['secret']), 'Should not have read access to secret');

    // And if we don't check perms, then the secret is visible...
    $usePerms(['administer payment processors']);
    $client = Civi\Api4\OAuthClient::get(FALSE)
      ->addWhere('provider', '=', 'test_example_3')
      ->execute()
      ->single();
    $this->assertSame("example-id-$random", $client['guid']);
    $this->assertSame("example-secret-$random", $client['secret']);
  }

  public function testCreateBadProvider() {
    $random = CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC);
    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client']);
    try {
      Civi\Api4\OAuthClient::create()->setValues([
        'provider' => 'test_example_does_not_exist',
        'guid' => "example-id-$random" ,
        'secret' => "example-secret-$random",
      ])->execute();
      $this->fail("Expected exception: invalid provider");
    }
    catch (CRM_Core_Exception $e) {
      $this->assertMatchesRegularExpression(';Invalid provider;', $e->getMessage());
    }
  }

  public function testUpdateBadProvider(): void {
    $random = CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC);
    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client']);
    $created = Civi\Api4\OAuthClient::create()->setValues([
      'provider' => 'test_example_1',
      'guid' => "example-id-$random" ,
      'secret' => "example-secret-$random",
    ])->execute();

    try {
      Civi\Api4\OAuthClient::update()
        ->addWhere('id', '=', $created->first()['id'])
        ->setValues(['provider' => 'test_example_does_not_exist'])
        ->execute();
      $this->fail("Expected exception: invalid provider");
    }
    catch (CRM_Core_Exception $e) {
      $this->assertMatchesRegularExpression(';Invalid provider;', $e->getMessage());
    }

    Civi\Api4\OAuthClient::update()
      ->addWhere('id', '=', $created->first()['id'])
      ->setValues(['provider:name' => 'test_example_2'])
      ->execute();
  }

  public static function getPkceUpdateExamples(): array {
    $exs = [];

    // Note: We always start with provider==test_example_1, which only allows S256+plain.
    // The updates may (or may not) specify new value of provider.

    $exs['S256-id'] = ['ok', 'id', ['pkce_method' => 'S256']];
    $exs['S256-guid'] = ['ok', 'guid', ['pkce_method' => 'S256']];
    $exs['plain-id'] = ['ok', 'id', ['pkce_method' => 'plain']];

    $exs['none-explicit-id'] = ['fail', 'id', ['pkce_method' => 'none', 'provider' => 'test_example_1']];
    $exs['none-implicit-id'] = ['fail', 'id', ['pkce_method' => 'none']];
    $exs['none-switched-id'] = ['ok', 'id', ['pkce_method' => 'none', 'provider' => 'test_example_3']];

    $exs['none-explicit-guid'] = ['fail', 'guid', ['pkce_method' => 'none', 'provider' => 'test_example_1']];
    $exs['none-implicit-guid'] = ['fail', 'guid', ['pkce_method' => 'none']];
    $exs['none-switched-guid'] = ['ok', 'guid', ['pkce_method' => 'none', 'provider' => 'test_example_3']];

    $exs['vogon-explicit-id'] = ['fail', 'id', ['pkce_method' => 'vogonpoetry', 'provider' => 'test_example_1']];
    $exs['vogon-implicit-id'] = ['fail', 'id', ['pkce_method' => 'vogonpoetry']];
    return $exs;
  }

  /**
   * @param string $expectOutcome
   *   Either 'ok' or 'fail'
   * @param string $matchOn
   *   Either 'id' or 'guid'
   * @param array $updates
   * @dataProvider getPkceUpdateExamples
   */
  public function testUpdatePkce(string $expectOutcome, string $matchOn, array $updates) {
    $random = CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC);
    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client']);
    $created = Civi\Api4\OAuthClient::create()->setValues([
      'provider' => 'test_example_1',
      'guid' => "example-id-$random" ,
      'secret' => "example-secret-$random",
      'pkce_method' => 'auto',
    ])->execute();
    $id = $created->first()['id'];
    $this->assertTrue(is_numeric($id));

    $where = match($matchOn) {
      'id' => ['id', '=', $id],
      'guid' => ['guid', '=', "example-id-$random"],
      default => throw new \RuntimeException("Unexpected matching $matchOn"),
    };

    switch ($expectOutcome) {
      case 'ok':
        $updated = Civi\Api4\OAuthClient::update()
          ->addWhere(...$where)
          ->setValues($updates)
          ->execute();
        $this->assertSame(1, $updated->count());
        break;

      case 'fail':
        try {
          Civi\Api4\OAuthClient::update()
            ->addWhere(...$where)
            ->setValues($updates)
            ->execute();
          $this->fail("Expected exception: Invalid pkce_method");
        }
        catch (\CRM_Core_Exception $e) {
          $this->assertMatchesRegularExpression(';Invalid pkce_method;', $e->getMessage());
        }
        break;

      default:
        $this->fail('Invalid $expectOutcome=' . $expectOutcome);

    }
  }

}
