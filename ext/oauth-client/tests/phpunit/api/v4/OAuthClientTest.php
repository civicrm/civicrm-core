<?php

use CRM_OAuth_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
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

  public function setUp() {
    parent::setUp();
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_oauth_client'));
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Basic sanity check - create, read, and delete a client.
   */
  public function testBasic() {
    $random = CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC);
    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client']);
    $create = Civi\Api4\OAuthClient::create()->setValues([
      'guid' => "example-id-$random" ,
      'secret' => "example-secret-$random",
    ])->execute();
    $this->assertEquals(1, $create->count());
    $client = $create->first();
    $this->assertEquals("example-id-$random", $client['guid']);
    $this->assertEquals("example-secret-$random", $client['secret']);

    $usePerms(['manage OAuth client']);
    // If we can tighten perm model: $usePerms(['manage OAuth client', 'manage OAuth client secrets']);
    $get = Civi\Api4\OAuthClient::get(0)->addWhere('guid', '=', "example-id-$random")->execute();
    $this->assertEquals(1, $get->count());
    $client = $get->first();
    $this->assertEquals("example-id-$random", $client['guid']);
    $this->assertEquals("example-secret-$random", $client['secret']);

    $usePerms(['manage OAuth client']);
    Civi\Api4\OAuthClient::delete(0)->addWhere('guid', '=', "example-id-$random")->execute();
    $get = Civi\Api4\OAuthClient::get(0)->addWhere('guid', '=', "example-id-$random")->execute();
    $this->assertEquals(0, $get->count());
  }

}
