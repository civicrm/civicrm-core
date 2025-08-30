<?php

namespace Civi\OAuth;

use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class CiviConnectTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  public function testCreds() {
    [$clientId, $authToken] = \Civi::service('oauth_client.civi_connect')->getCreds();
    $this->assertMatchesRegularExpression('/^eddsa_[a-zA-Z0-9=+\/]+$/', $clientId);
    $decode = \Civi::service('crypto.jwt')->decode($authToken, 'CONNECT');
    $this->assertEquals('CiviConnect', $decode['scope']);
  }

}
