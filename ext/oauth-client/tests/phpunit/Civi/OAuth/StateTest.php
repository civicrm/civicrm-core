<?php

namespace Civi\OAuth;

use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class StateTest extends \PHPUnit\Framework\TestCase implements
    HeadlessInterface,
    HookInterface,
    TransactionalInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  public function setUp(): void {
    parent::setUp();
  }

  public function tearDown(): void {
    \CRM_Utils_Time::resetTime();
    parent::tearDown();
  }

  public function testState(): void {
    \CRM_Utils_Time::setTime('2025-11-01 01:00:00');

    $stateId = \Civi::service('oauth2.state')->store([
      'clientId' => 123,
      'storage' => 'OAuthSysToken',
      'ttl' => 5 * 60 * 60,
    ]);
    $state = \Civi::service('oauth2.state')->load($stateId);
    $this->assertSame(123, $state['clientId']);
    $this->assertSame('OAuthSysToken', $state['storage']);
    /**
 *PHPUnit runs in CLI... so any state we generate is attached to wildcard session.
 */
    $this->assertSame(OAuthState::SESSION_WILDCARD, $state['session']);

    \CRM_Utils_Time::setTime('2025-11-02 06:01:00');
    try {
      \Civi::service('oauth2.state')->load($stateId);
      $this->fail('State should expire');
    }
    catch (OAuthException $e) {
      $this->assertMatchesRegularExpression(';Received invalid or expired state;', $e->getMessage());
    }
  }

  public function testInvalidState(): void {
    try {
      \Civi::service('oauth2.state')->load('CC_123456789012345678901234567890123456789012345678901234567890');
      $this->fail('State should expire');
    }
    catch (OAuthException $e) {
      $this->assertMatchesRegularExpression(';Received invalid or expired state;', $e->getMessage());
    }
  }

  public function testModifyState(): void {
    $stateId = \Civi::service('oauth2.state')->store([
      'clientId' => 123,
      'storage' => 'OAuthSysToken',
      'ttl' => 5 * 60 * 60,
      'breakfast' => 'green eggs',
    ]);
    $state = \Civi::service('oauth2.state')->load($stateId);
    $this->assertSame(123, $state['clientId']);
    $this->assertSame('green eggs', $state['breakfast']);

    $state['breakfast'] = 'green eggs and ham';
    \Civi::service('oauth2.state')->store($state, $stateId);

    $state2 = \Civi::service('oauth2.state')->load($stateId);
    $this->assertSame(123, $state2['clientId']);
    $this->assertSame('green eggs and ham', $state2['breakfast']);
  }

  public function testConsumedStateCannotBeReused(): void {
    $store = \Civi::service('oauth2.state');
    $id = $store->store(['clientId' => 123, 'oidc_nonce' => 'test']);
    $this->assertSame(123, $store->consume($id)['clientId']);
    $this->expectException(OAuthException::class);
    $store->consume($id);
  }

  public function testBrowserMismatchDoesNotConsumeState(): void {
    $store = new class() extends OAuthState {

      public string $session = 'browser-a';

      public function __construct() {
        $this->cache = \Civi::cache('session');
      }

      protected function getSessionId(): string {
        return $this->session;
      }

    };
    $id = $store->store(['clientId' => 123]);
    $store->session = 'browser-b';
    try {
      $store->consume($id);
      $this->fail('A different browser consumed the state.');
    }
    catch (OAuthException $e) {
      $store->session = 'browser-a';
      $this->assertSame(123, $store->consume($id)['clientId']);
    }
  }

  public function testIndependentFlows(): void {
    $store = \Civi::service('oauth2.state');
    $first = $store->store(['oidc_nonce' => 'first']);
    $second = $store->store(['oidc_nonce' => 'second']);
    $this->assertSame('second', $store->consume($second)['oidc_nonce']);
    $this->assertSame('first', $store->consume($first)['oidc_nonce']);
  }

  public function testExpiredStateCannotBeConsumed(): void {
    $store = \Civi::service('oauth2.state');
    $id = $store->store(['ttl' => -1]);
    $this->expectException(OAuthException::class);
    $store->consume($id);
  }

  public function testConsumedMarkerRejectsStaleState(): void {
    $store = \Civi::service('oauth2.state');
    $id = $store->store(['clientId' => 123]);
    $stale = $store->load($id);
    $store->consume($id);
    \Civi::cache('session')->set($id, $stale);
    $this->expectException(OAuthException::class);
    $store->consume($id);
  }

}
