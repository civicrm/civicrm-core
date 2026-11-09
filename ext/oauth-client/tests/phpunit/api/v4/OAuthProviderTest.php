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

  private $providers = [];

  public function setUp(): void {
    parent::setUp();
  }

  public function tearDown(): void {
    parent::tearDown();
  }

  public function hook_civicrm_oauthProviders(&$providers) {
    $providers = array_merge($providers, $this->providers);
  }

  /**
   * Create, read, and destroy token - with full access to secrets.
   */
  public function testGet(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];

    $examples = Civi\Api4\OAuthProvider::get(FALSE)
      ->addWhere('name', 'LIKE', 'test_example%')
      ->addOrderBy('name', 'DESC')
      ->execute();
    $this->assertSame(4, $examples->count());
    [$ex4, $ex3, $ex2, $ex1] = $examples->getArrayCopy();

    $this->assertSame('test_example_4_sandbox', $ex4['name']);
    $this->assertSame('Civi\OAuth\CiviGenericProvider', $ex4['class']);
    $this->assertSame('https://sandbox.connect.civicrm.org/four/auth', $ex4['options']['urlAuthorize']);
    $this->assertTrue(isset($ex4['permissions']['meta']));

    $this->assertSame('test_example_3', $ex3['name']);
    $this->assertSame('Civi\OAuth\CiviGenericProvider', $ex3['class']);
    $this->assertSame('https://example.com/three/auth', $ex3['options']['urlAuthorize']);
    $this->assertTrue(isset($ex3['permissions']['meta']));

    $this->assertSame('test_example_2', $ex2['name']);
    $this->assertSame('My\Example2', $ex2['class']);
    $this->assertSame('https://example.com/two', $ex2['options']['urlAuthorize']);
    $this->assertTrue(isset($ex2['permissions']['meta']));

    $this->assertSame('test_example_1', $ex1['name']);
    $this->assertSame('Civi\OAuth\CiviGenericProvider', $ex1['class']);
    $this->assertSame('https://example.com/one/auth', $ex1['options']['urlAuthorize']);
    $this->assertTrue(isset($ex1['permissions']['meta']));
  }

  /**
   * @return void
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testGetWithPermissions(): void {
    $usePerms = function($ps) {
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = $ps;
    };

    $usePerms(['all CiviCRM permissions and ACLs']);
    $examples = Civi\Api4\OAuthProvider::get()
      ->addWhere('name', 'LIKE', 'test_example%')
      ->addOrderBy('name', 'DESC')
      ->execute();
    $this->assertExamples(['test_example_4_sandbox', 'test_example_3', 'test_example_2', 'test_example_1'], $examples);

    $usePerms(['access CiviCRM']);
    $examples = Civi\Api4\OAuthProvider::get()
      ->addWhere('name', 'LIKE', 'test_example%')
      ->addOrderBy('name', 'DESC')
      ->execute();
    $this->assertExamples(['test_example_2', 'test_example_1'], $examples);

    $usePerms(['administer payment processors']);
    $examples = Civi\Api4\OAuthProvider::get()
      ->addWhere('name', 'LIKE', 'test_example%')
      ->addOrderBy('name', 'DESC')
      ->execute();
    $this->assertExamples(['test_example_4_sandbox', 'test_example_3'], $examples);
  }

  protected function assertExamples(array $expected, \Civi\Api4\Generic\Result $actual) {
    $actualNames = $actual->column('name');
    $this->assertEquals($expected, $actualNames, 'Expected to receive ' . count($expected) . ' examples');
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
    $this->assertSame(1, $examples->count());

    $this->assertSame('My\Example2', $examples->single()['class']);
    $this->assertSame('https://example.com/two', $examples->single()['options']['urlAuthorize']);
  }

  public function testPkceUndeclared(): void {
    $this->providers['pkce_undeclared'] = [
      'name' => 'pkce_undeclared',
      'title' => 'PKCE Undeclared Provider',
      'options' => [
        'urlAuthorize' => 'https://pkce.example.com/auth',
        'urlAccessToken' => 'https://pkce.example.com/token',
        'urlResourceOwnerDetails' => 'https://pkce.example.com/owner',
      ],
    ];
    \Civi::cache('long')->delete('OAuthProvider_list');

    $provider = Civi\Api4\OAuthProvider::get(FALSE)
      ->addWhere('name', '=', 'pkce_undeclared')
      ->execute()
      ->single();

    $this->assertSame(['S256', 'plain', 'none'], $provider['pkceMethods']);
  }

  public function testPkceRequireS256(): void {
    $this->providers['pkce_s256_only'] = [
      'name' => 'pkce_s256_only',
      'title' => 'PKCE S256 Only Provider',
      'pkceMethods' => ['S256'],
      'options' => [
        'urlAuthorize' => 'https://pkce.example.com/auth',
        'urlAccessToken' => 'https://pkce.example.com/token',
        'urlResourceOwnerDetails' => 'https://pkce.example.com/owner',
      ],
    ];
    \Civi::cache('long')->delete('OAuthProvider_list');

    $provider = Civi\Api4\OAuthProvider::get(FALSE)
      ->addWhere('name', '=', 'pkce_s256_only')
      ->execute()
      ->single();

    $this->assertSame(['S256'], $provider['pkceMethods']);
  }

}
