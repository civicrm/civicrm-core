<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Create and read session-specific OAuth tokens
 *
 * @group headless
 */
class api_v4_OAuthSessionTokenTest extends \PHPUnit\Framework\TestCase implements
    HeadlessInterface,
    HookInterface,
    TransactionalInterface {

  // these two traits together give us createLoggedInUser()
  use Civi\Test\ContactTestTrait;
  use \Civi\Test\Api3TestTrait;

  public function setUpHeadless(): \Civi\Test\CiviEnvBuilder {
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  public function setUp(): void {
    parent::setUp();
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_oauth_client'));
    $this->assertNull(CRM_Core_Session::singleton()->get('OAuthSessionTokens'));
  }

  protected function tearDown(): void {
    CRM_Core_Session::singleton()->reset();
    parent::tearDown();
  }

  private function createClient(): ?array {
    $createClient = Civi\Api4\OAuthClient::create(FALSE)->setValues(
      [
        'provider' => 'test_example_1',
        'guid' => "example-client-guid",
        'secret' => "example-secret",
      ]
    )->execute();
    $client = $createClient->first();
    $this->assertTrue(is_numeric($client['id']));
    return $client;
  }

  private function getTestTokenCreateValues($client, $secretPrefix) {
    return [
      'client_id' => $client['id'],
      'access_token' => "$secretPrefix-user-access-token",
      'refresh_token' => "$secretPrefix-user-refresh-token",
    ];
  }

  public function testAnonymousSessionCanHoldToken() {
    self::assertNull(CRM_Core_Session::getLoggedInContactID());

    $client = $this->createClient();
    $tokenCreateValues = $this->getTestTokenCreateValues($client, 'anon');

    Civi\Api4\OAuthSessionToken::create(FALSE)
      ->setValues($tokenCreateValues)
      ->execute();

    $retrievedToken = \Civi\Api4\OAuthSessionToken::get(FALSE)
      ->addWhere('client_id', '=', $client['id'])
      ->execute()
      ->first();

    $this->assertEquals($client['id'], $retrievedToken['client_id']);
    $this->assertEquals($tokenCreateValues['access_token'], $retrievedToken['access_token']);
    $this->assertEquals($tokenCreateValues['refresh_token'], $retrievedToken['refresh_token']);
  }

  public function testAnonymousSessionTokensCanBeDeleted() {
    self::assertNull(CRM_Core_Session::getLoggedInContactID());

    $client = $this->createClient();
    $tokenCreateValues = $this->getTestTokenCreateValues($client, 'anon');

    Civi\Api4\OAuthSessionToken::create(FALSE)
      ->setValues($tokenCreateValues)
      ->execute();

    \Civi\Api4\OAuthSessionToken::delete(FALSE)
      ->execute();

    $retrievedTokens = \Civi\Api4\OAuthSessionToken::get(FALSE)
      ->execute()
      ->first();

    $this->assertEmpty($retrievedTokens);
  }

  public function testLoggedInSessionCanHoldToken() {
    $this->createLoggedInUser();
    self::assertIsNumeric(CRM_Core_Session::getLoggedInContactID());

    $client = $this->createClient();
    $tokenCreateValues = $this->getTestTokenCreateValues($client, 'loggedIn');

    Civi\Api4\OAuthSessionToken::create(FALSE)
      ->setValues($tokenCreateValues)
      ->execute();

    $retrievedToken = \Civi\Api4\OAuthSessionToken::get(FALSE)
      ->addWhere('client_id', '=', $client['id'])
      ->execute()
      ->first();

    $this->assertEquals($client['id'], $retrievedToken['client_id']);
    $this->assertEquals($tokenCreateValues['access_token'], $retrievedToken['access_token']);
    $this->assertEquals($tokenCreateValues['refresh_token'], $retrievedToken['refresh_token']);
  }

  public function testLoggingOutDeletesTokens() {
    $this->createLoggedInUser();
    self::assertIsNumeric(CRM_Core_Session::getLoggedInContactID());

    $client = $this->createClient();
    $tokenCreateValues = $this->getTestTokenCreateValues($client, 'loggedIn');

    Civi\Api4\OAuthSessionToken::create(FALSE)
      ->setValues($tokenCreateValues)
      ->execute();

    // log out
    CRM_Core_Session::singleton()->reset();
    self::assertNull(CRM_Core_Session::getLoggedInContactID());

    $retrievedTokens = \Civi\Api4\OAuthSessionToken::get(FALSE)
      ->execute()
      ->first();

    $this->assertEmpty($retrievedTokens);
  }

}
