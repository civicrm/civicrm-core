<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Create, read, and destroy contact-specific OAuth tokens
 *
 * A logged in user who has permission to "manage my OAuth contact tokens"
 * can create tokens associated with their own contact id, and can
 * read/update/delete those tokens if they have at least view access
 * to their own contact record.
 *
 * A user who has permission to "manage all OAuth contact tokens" can create
 * tokens associated with any contact, and can read/update/delete tokens
 * associated with any contact for whom they have at least view access.
 *
 * Users who have either of the "manage OAuth contact tokens" permissions can
 * also get basic OAuthClient information, NOT including the client's secret.
 *
 * @group headless
 */
class api_v4_OAuthContactTokenTest extends \PHPUnit\Framework\TestCase implements
    HeadlessInterface,
    HookInterface,
    TransactionalInterface {

  use Civi\Test\ContactTestTrait;
  use \Civi\Test\Api3TestTrait;

  private $hookEvents;

  protected $ids = [];

  public function setUpHeadless(): \Civi\Test\CiviEnvBuilder {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  public function setUp(): void {
    parent::setUp();
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_oauth_client'));
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_oauth_contact_token'));
  }

  public function tearDown(): void {
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

  private function createTestContactIDs(): array {
    $notLoggedInContactID = Civi\Api4\Contact::get(FALSE)
      ->setSelect(['id'])
      ->setLimit(1)
      ->execute()
      ->single()['id'];
    $loggedInContactID = $this->createLoggedInUser();
    return [$loggedInContactID, $notLoggedInContactID];
  }

  private function usePerms(array $permissions) {
    $base = ['access CiviCRM'];
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $permissions);
    if ($cid = CRM_Core_Session::singleton()->getLoggedInContactID()) {
      CRM_ACL_BAO_Cache::deleteContactCacheEntry($cid);
      CRM_Contact_BAO_Contact_Permission::cache($cid, CRM_Core_Permission::VIEW, TRUE);
    }
  }

  private function getTestTokenCreateValues($client, $contactId, $prefix) {
    return [
      'client_id' => $client['id'],
      'contact_id' => $contactId,
      'access_token' => "$prefix-user-access-token",
      'refresh_token' => "$prefix-user-refresh-token",
    ];
  }

  private function makeToken(array $values): ?array {
    return Civi\Api4\OAuthContactToken::create(FALSE)
      ->setValues($values)
      ->execute()
      ->first();
  }

  private function createOwnAndStrangerTokens(
    $client,
    $loggedInContactID,
    $notLoggedInContactID
  ): array {
    $ownTokenCreationVals = $this->getTestTokenCreateValues(
      $client, $loggedInContactID, 'own');
    $strangerTokenCreationVals = $this->getTestTokenCreateValues(
      $client, $notLoggedInContactID, 'other');
    return [
      $this->makeToken($ownTokenCreationVals),
      $this->makeToken($strangerTokenCreationVals),
    ];
  }

  public function hook_civicrm_post($op, $objectName, $objectId, &$objectRef) {
    if ($objectName === 'OAuthContactToken') {
      $this->hookEvents['post'][] = func_get_args();
    }
  }

  public function testGetClientDetails() {
    $createClient = $this->createClient();

    $this->usePerms(['manage my OAuth contact tokens']);
    $getClient = Civi\Api4\OAuthClient::get()
      ->addWhere('id', '=', $createClient['id'])
      ->execute()
      ->single();
    $this->assertEquals($createClient['guid'], $getClient['guid']);
    $this->assertEquals($createClient['provider'], $getClient['provider']);
    $this->assertArrayNotHasKey('secret', $getClient);
  }

  public function testCreate() {
    $client = $this->createClient();
    [$loggedInContactID, $notLoggedInContactID] = $this->createTestContactIDs();
    $ownTokenCreateVals = $this->getTestTokenCreateValues(
      $client, $loggedInContactID, 'own');
    $strangerTokenCreateVals = $this->getTestTokenCreateValues(
      $client, $notLoggedInContactID, 'other');

    $this->usePerms(['manage all OAuth contact tokens', 'edit all contacts']);
    $createOtherContactToken = Civi\Api4\OAuthContactToken::create()
      ->setValues($strangerTokenCreateVals)
      ->execute();
    $token = $createOtherContactToken->first();
    $tokenIDOfDifferentContact = $token['id'];
    $this->assertTrue(is_numeric($tokenIDOfDifferentContact));
    $this->assertEquals($client['id'], $token['client_id']);
    $this->assertEquals($notLoggedInContactID, $token['contact_id']);
    $this->assertEquals($strangerTokenCreateVals['access_token'], $token['access_token']);
    $this->assertEquals($strangerTokenCreateVals['refresh_token'], $token['refresh_token']);

    $this->usePerms(['manage my OAuth contact tokens', 'edit my contact']);
    $createOwnToken = Civi\Api4\OAuthContactToken::create()
      ->setValues($ownTokenCreateVals)
      ->execute();
    $token = $createOwnToken->first();
    $tokenIDOfLoggedInContact = $token['id'];
    $this->assertTrue(is_numeric($tokenIDOfLoggedInContact));
    $this->assertEquals($client['id'], $token['client_id']);
    $this->assertEquals($loggedInContactID, $token['contact_id']);
    $this->assertEquals($ownTokenCreateVals['access_token'], $token['access_token']);
    $this->assertEquals($ownTokenCreateVals['refresh_token'], $token['refresh_token']);

    $this->usePerms(['manage my OAuth contact tokens', 'edit all contacts']);
    try {
      Civi\Api4\OAuthContactToken::create()
        ->setValues($strangerTokenCreateVals)
        ->execute();
      $this->fail('Expected \Civi\API\Exception\UnauthorizedException but none was thrown');
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      // exception successfully thrown
    }
  }

  public function testRead() {
    $client = $this->createClient();
    [$loggedInContactID, $notLoggedInContactID] = $this->createTestContactIDs();
    $ownTokenCreationVals = $this->getTestTokenCreateValues(
      $client, $loggedInContactID, 'own');
    $this->createOwnAndStrangerTokens($client, $loggedInContactID, $notLoggedInContactID);

    $this->usePerms(['manage all OAuth contact tokens', 'view all contacts']);
    $getTokensWithFullAccess = Civi\Api4\OAuthContactToken::get()->execute();
    $this->assertCount(2, $getTokensWithFullAccess);

    $this->usePerms(['manage my OAuth contact tokens', 'view my contact']);
    $getTokensWithOwnAccess = Civi\Api4\OAuthContactToken::get()->execute();
    $this->assertCount(1, $getTokensWithOwnAccess);
    $token = $getTokensWithOwnAccess->first();
    $this->assertEquals($client['id'], $token['client_id']);
    $this->assertEquals($loggedInContactID, $token['contact_id']);
    $this->assertEquals($ownTokenCreationVals['access_token'], $token['access_token']);
    $this->assertEquals($ownTokenCreationVals['refresh_token'], $token['refresh_token']);

    $this->usePerms(['manage my OAuth contact tokens', 'view my contact']);
    $getTokensForWrongContact = Civi\Api4\OAuthContactToken::get()
      ->addWhere('contact_id', '=', $notLoggedInContactID)
      ->execute();
    $this->assertCount(0, $getTokensForWrongContact);

    $this->usePerms(['manage all OAuth contact tokens']);
    $getTokensWithNoContactAccess = Civi\Api4\OAuthContactToken::get()
      ->execute();
    $this->assertCount(0, $getTokensWithNoContactAccess);
  }

  public function testUpdate() {
    $client = $this->createClient();
    [$loggedInContactID, $notLoggedInContactID] = $this->createTestContactIDs();
    [
      $ownContactToken,
      $strangerContactToken,
    ] = $this->createOwnAndStrangerTokens(
      $client,
      $loggedInContactID,
      $notLoggedInContactID
    );

    $this->usePerms(['manage all OAuth contact tokens', 'edit all contacts']);
    $updateTokensWithFullAccess = Civi\Api4\OAuthContactToken::update()
      ->addWhere('contact_id', '=', $notLoggedInContactID)
      ->setValues(['access_token' => 'stranger-token-revised'])
      ->execute();
    $this->assertCount(1, $updateTokensWithFullAccess);
    $token = $updateTokensWithFullAccess->first();
    $this->assertEquals($strangerContactToken['id'], $token['id']);

    $this->usePerms(['manage my OAuth contact tokens', 'edit my contact']);
    $updateTokensWithLimitedAccess = Civi\Api4\OAuthContactToken::update()
      ->addWhere('client_id.guid', '=', $client['guid'])
      ->setValues(['access_token' => 'own-token-revised'])
      ->execute();
    $this->assertCount(1, $updateTokensWithLimitedAccess);
    $token = $updateTokensWithLimitedAccess->first();
    $this->assertEquals($ownContactToken['id'], $token['id']);

    $this->usePerms(['manage my OAuth contact tokens', 'edit my contact']);
    $getUpdatedTokensWithLimitedAccess = Civi\Api4\OAuthContactToken::get()
      ->execute();
    $this->assertCount(1, $getUpdatedTokensWithLimitedAccess);
    $token = $getUpdatedTokensWithLimitedAccess->first();
    $this->assertEquals($loggedInContactID, $token['contact_id']);
    $this->assertEquals("own-token-revised", $token['access_token']);

    $this->usePerms(['manage my OAuth contact tokens', 'view all contacts']);
    $updates = Civi\Api4\OAuthContactToken::update()
      ->addWhere('contact_id', '=', $notLoggedInContactID)
      ->setValues(['access_token' => "stranger-token-revised"])
      ->execute();
    $this->assertCount(0, $updates, 'User should not have access to update');

    $this->usePerms(['manage my OAuth contact tokens', 'view my contact']);
    $updateTokensForWrongContact = Civi\Api4\OAuthContactToken::update()
      ->addWhere('contact_id.id', '=', $notLoggedInContactID)
      // ^ sneaky way to update a different contact?
      ->setValues(['access_token' => "stranger-token-revised"])
      ->execute();
    $this->assertCount(0, $updateTokensForWrongContact);
  }

  public function testDelete() {
    $client = $this->createClient();
    [$loggedInContactID, $notLoggedInContactID] = $this->createTestContactIDs();
    $this->createOwnAndStrangerTokens($client, $loggedInContactID, $notLoggedInContactID);

    $this->usePerms(['manage my OAuth contact tokens', 'edit all contacts']);
    $deleteTokensWithLimitedAccess = Civi\Api4\OAuthContactToken::delete()
      ->setWhere([['client_id.guid', '=', $client['guid']]])
      ->execute();

    $this->usePerms(['manage my OAuth contact tokens', 'edit all contacts']);
    $getTokensWithLimitedAccess = Civi\Api4\OAuthContactToken::get()->execute();
    $this->assertCount(0, $getTokensWithLimitedAccess);

    $this->usePerms(['manage all OAuth contact tokens', 'edit all contacts']);
    $getTokensWithFullAccess = Civi\Api4\OAuthContactToken::get()->execute();
    $this->assertCount(1, $getTokensWithFullAccess);

    $this->usePerms(['manage my OAuth contact tokens', 'view all contacts']);
    $deleted = Civi\Api4\OAuthContactToken::delete()
      ->addWhere('contact_id', '=', $notLoggedInContactID)
      ->execute();
    $this->assertCount(0, $deleted);
  }

  public function testGetByScope() {
    $client = $this->createClient();

    $this->usePerms(['manage all OAuth contact tokens', 'edit all contacts']);
    $tokenCreationVals = [
      'client_id' => $client['id'],
      'contact_id' => 1,
      'access_token' => "loggedin-user-access-token",
      'refresh_token' => "loggedin-user-refresh-token",
      'scopes' => ['foo', 'bar'],
    ];
    $createToken = Civi\Api4\OAuthContactToken::create()
      ->setValues($tokenCreationVals)
      ->execute();
    $token = $createToken->first();
    $this->assertTrue(is_numeric($token['id']));
    $this->assertEquals(['foo', 'bar'], $token['scopes']);

    $this->usePerms(['manage all OAuth contact tokens', 'view all contacts']);
    $getTokens = Civi\Api4\OAuthContactToken::get()
      ->addWhere('client_id.provider', '=', $client['provider'])
      ->addWhere('scopes', 'CONTAINS', 'foo')
      ->execute();
    $this->assertCount(1, $getTokens);
    $this->assertEquals($createToken->first()['id'], $getTokens->first()['id']);

    $this->usePerms(['manage all OAuth contact tokens', 'view all contacts']);
    $getTokens = Civi\Api4\OAuthContactToken::get()
      ->addWhere('client_id.provider', '=', $client['provider'])
      ->addWhere('scopes', 'CONTAINS', 'nada')
      ->execute();
    $this->assertCount(0, $getTokens);

    $this->usePerms(['manage all OAuth contact tokens', 'view all contacts']);
    $getTokens = Civi\Api4\OAuthContactToken::get()
      ->addWhere('client_id.provider', '=', 'some-other-provider')
      ->addWhere('scopes', 'CONTAINS', 'foo')
      ->execute();
    $this->assertCount(0, $getTokens);
  }

  public function testPostHook() {
    $client = $this->createClient();
    [$loggedInContactID, $notLoggedInContactID] = $this->createTestContactIDs();
    $strangerTokenCreationVals = $this->getTestTokenCreateValues(
      $client, $loggedInContactID, 'other');

    $this->usePerms(['manage all OAuth contact tokens']);
    $this->makeToken($strangerTokenCreationVals);

    self::assertCount(1, $this->hookEvents['post']);
  }

}
