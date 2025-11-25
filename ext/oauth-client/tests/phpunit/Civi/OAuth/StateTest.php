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
    $this->assertEquals(123, $state['clientId']);
    $this->assertEquals('OAuthSysToken', $state['storage']);
    // PHPUnit runs in CLI... so any state we generate is attached to wildcard session.
    $this->assertEquals(OAuthState::SESSION_WILDCARD, $state['session']);

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
    $this->assertEquals(123, $state['clientId']);
    $this->assertEquals('green eggs', $state['breakfast']);

    $state['breakfast'] = 'green eggs and ham';
    \Civi::service('oauth2.state')->store($state, $stateId);

    $state2 = \Civi::service('oauth2.state')->load($stateId);
    $this->assertEquals(123, $state2['clientId']);
    $this->assertEquals('green eggs and ham', $state2['breakfast']);
  }

}
