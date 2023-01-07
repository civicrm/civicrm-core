<?php

namespace Civi\OAuth;

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class CiviGenericProviderTest extends \PHPUnit\Framework\TestCase implements
    HeadlessInterface,
    HookInterface,
    TransactionalInterface {

  private $clients = [];
  private $providers = [];

  public function setUpHeadless() {
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  public function setUp(): void {
    $this->makeDummyProviders();
    $this->makeDummyClients();
    parent::setUp();
  }

  public function tearDown(): void {
    parent::tearDown();
  }

  public function hook_civicrm_oauthProviders(&$providers) {
    $providers = array_merge($providers, $this->providers);
  }

  public function makeDummyProviders() {
    $this->providers['no-tenancy'] = [
      'name' => 'no-tenancy',
      'title' => 'Provider without tenancy',
      'options' => [
        'urlAuthorize' => 'https://dummy/authorize',
        'urlAccessToken' => 'https://dummy/token',
        'urlResourceOwnerDetails' => '{{use_id_token}}',
        'scopes' => ['foo'],
      ],
    ];
    $this->providers['with-tenancy'] = [
      'name' => 'with-tenancy',
      'title' => 'Provider with tenancy',
      'options' => [
        'urlAuthorize' => 'https://dummy/{{tenant}}/authorize',
        'urlAccessToken' => 'https://dummy/{{tenant}}/token',
        'urlResourceOwnerDetails' => '{{use_id_token}}',
        'scopes' => ['foo'],
        'tenancy' => TRUE,
      ],
    ];
  }

  private function makeDummyClients() {
    $this->clients['no-tenancy'] = \Civi\Api4\OAuthClient::create(FALSE)->setValues(
      [
        'provider' => 'no-tenancy',
        'guid' => 'example-client-guid-no-tenancy',
        'secret' => 'example-secret',
      ]
    )->execute()->single();
    $this->clients['with-tenancy'] = \Civi\Api4\OAuthClient::create(FALSE)->setValues(
      [
        'provider' => 'with-tenancy',
        'guid' => 'example-client-guid-with-tenancy',
        'secret' => 'example-secret',
        'tenant' => '123-456-789',
      ]
    )->execute()->single();
  }

  public function testTenancyInURLs() {

    // Test no tenancy settings
    $provider = \Civi::service('oauth2.league')->createProvider($this->clients['no-tenancy']);
    $computedAuthorizationURL = $provider->getBaseAuthorizationUrl();
    $this->assertEquals($this->providers['no-tenancy']['options']['urlAuthorize'], $computedAuthorizationURL);
    $computedTokenURL = $provider->getBaseAccessTokenUrl([]);
    $this->assertEquals($this->providers['no-tenancy']['options']['urlAccessToken'], $computedTokenURL);

    // Test tenancy token rewrite
    $provider = \Civi::service('oauth2.league')->createProvider($this->clients['with-tenancy']);
    $computedAuthorizationURL = $provider->getBaseAuthorizationUrl();
    $this->assertEquals('https://dummy/123-456-789/authorize', $computedAuthorizationURL);
    $computedTokenURL = $provider->getBaseAccessTokenUrl([]);
    $this->assertEquals('https://dummy/123-456-789/token', $computedTokenURL);

  }

}
