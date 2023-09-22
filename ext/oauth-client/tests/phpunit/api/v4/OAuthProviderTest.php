<?php
use CRM_OAuth_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Read list of OAuth providers
 *
 * @group headless
 */
class api_v4_OAuthProviderTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

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

  /**
   * Create, read, and destroy token - with full access to secrets.
   */
  public function testGet(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];

    $examples = Civi\Api4\OAuthProvider::get()
      ->addWhere('name', 'LIKE', 'test_example%')
      ->addOrderBy('name', 'DESC')
      ->execute();
    $this->assertEquals(2, $examples->count());

    $this->assertEquals('Civi\OAuth\CiviGenericProvider', $examples->last()['class']);
    $this->assertEquals('My\Example2', $examples->first()['class']);
    $this->assertEquals('https://example.com/one/auth', $examples->last()['options']['urlAuthorize']);
    $this->assertEquals('https://example.com/two', $examples->first()['options']['urlAuthorize']);
  }

}
