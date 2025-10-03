<?php
use CRM_OAuth_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
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
    $this->assertEquals(3, $examples->count());

    $this->assertEquals('test_example_3', $examples[0]['name']);
    $this->assertEquals('Civi\OAuth\CiviGenericProvider', $examples[0]['class']);
    $this->assertEquals('https://example.com/three/auth', $examples[0]['options']['urlAuthorize']);

    $this->assertEquals('test_example_2', $examples[1]['name']);
    $this->assertEquals('My\Example2', $examples[1]['class']);
    $this->assertEquals('https://example.com/two', $examples[1]['options']['urlAuthorize']);

    $this->assertEquals('test_example_1', $examples[2]['name']);
    $this->assertEquals('Civi\OAuth\CiviGenericProvider', $examples[2]['class']);
    $this->assertEquals('https://example.com/one/auth', $examples[2]['options']['urlAuthorize']);

  }

  /**
   * Create, read, and destroy token - with full access to secrets.
   */
  public function testGetByTag(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];

    $examples = Civi\Api4\OAuthProvider::get()
      ->addWhere('tags', 'CONTAINS', 'LaundryInstructions')
      ->addOrderBy('name', 'DESC')
      ->execute();
    $this->assertEquals(1, $examples->count());

    $this->assertEquals('My\Example2', $examples->single()['class']);
    $this->assertEquals('https://example.com/two', $examples->single()['options']['urlAuthorize']);
  }

}
