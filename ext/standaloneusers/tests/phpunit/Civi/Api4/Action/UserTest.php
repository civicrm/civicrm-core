<?php
namespace Civi\Api4\Action;

use Civi\Test\EndToEndInterface;
use Civi\Test\TransactionalInterface;
use Civi\API\Request;
use Civi\Api4\User;
use Civi\Api4\Role;
use Civi\Api4\UserRole;
use Civi\Api4\Contact;
use Civi\Standalone\Security;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * Fun fact: Running E2E tests with TransactionalInterface is usually prohibitive because of the
 * split DB. However, with Standalone, there's a single DB, so it may work some of the time.
 * (It only becomes prohibitive if you actually use HTTP.)
 *
 * @group e2e
 */
class UserTest extends \PHPUnit\Framework\TestCase implements EndToEndInterface, TransactionalInterface {

  /**
   * @var int
   */
  protected $contactID;

  /**
   * @var int
   */
  protected $userID;

  /**
   * @var int
   */
  protected $adminContactID;

  /**
   * @var int
   */
  protected $adminUserID;

  /**
   * @var int
   */
  protected $nonAdminContactID;

  /**
   * @var int
   */
  protected $nonAdminUserID;

  /**
   * @var int
   */
  protected $nonAdminRoleID;

  /**
   * @var array
   */
  protected $otherUserIDs = [];

  const ADMIN_ROLE_ID = 1;

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    \Civi\Test::e2e()
      // ->install(['authx', 'org.civicrm.search_kit', 'org.civicrm.afform', 'standaloneusers'])
      // We only run on "Standalone", so all the above is given.
      // ->installMe(__DIR__) This causes failure, so we do                 ↑
      ->apply(FALSE);
  }

  public function setUp():void {
    parent::setUp();
    if (CIVICRM_UF !== 'Standalone') {
      $this->markTestSkipped('Test only applies on Standalone');
    }
    else {
      $this->createFixture();
      $this->ensureLoggedOut();
    }
  }

  /**
   * Note I thought I could use \Civi\Authx\Standalone::logoutSession()
   * but it calls session_destroy which messes up future tests.
   *
   * Not sure if there is a generic logout without session destroy.
   *
   */
  public function ensureLoggedOut() {
    global $loggedInUserId, $loggedInUser;

    if (\CRM_Utils_System::getLoggedInUfID()) {
      \CRM_Core_Session::singleton()->reset();
      $loggedInUser = $loggedInUserId = NULL;
    }
  }

  public function tearDown():void {
    $this->deleteStuffWeMade();
    parent::tearDown();
  }

  protected function loginUser($userID) {
    $security = Security::singleton();
    $user = \Civi\Api4\User::get(FALSE)
      ->addWhere('id', '=', $userID)
      ->execute()->first();

    $contactID = civicrm_api3('UFMatch', 'get', [
      'sequential' => 1,
      'return' => ['contact_id'],
      'uf_id' => $user['id'],
    ])['values'][0]['contact_id'] ?? NULL;
    $this->assertNotNull($contactID);
    /** @var \Civi\Standalone\Security $security */
    $security->loginAuthenticatedUserRecord($user, FALSE);
  }

  /**
   * Temporary debugging function
   */
  public function dumpUFMatch(string $s = '') {
    $d = \CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_uf_match;");
    print "\ndump---------- $s\n";
    foreach ($d->fetchAll() as $row) {
      print json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
    }
    print "--------------\n";
  }

  /**
   */
  protected function createFixture(): void {

    // Create admin contact and user.
    $this->adminContactID = \Civi\Api4\Contact::create(FALSE)
      ->setValues([
        'contact_type' => 'Individual',
        'display_name' => 'Admin McTest',
      ])->execute()->first()['id'];
    $params = [
      'cms_name' => 'user_one',
      'cms_pass' => 'secret1',
      'notify' => FALSE,
      'contact_id' => $this->adminContactID,
      'email' => 'user_one@example.org',
    ];
    $this->adminUserID = \CRM_Core_BAO_CMSUser::create($params, 'email');
    $this->assertGreaterThan(0, $this->adminUserID);
    $user = User::get(FALSE)->addWhere('id', '=', $this->adminUserID)->execute()->single();
    $this->assertEquals('user_one', $user['username']);
    $this->assertEquals($this->adminContactID, $user['contact_id']);
    $this->assertEquals($this->adminUserID, $user['uf_id']);
    $this->assertEquals('user_one@example.org', $user['uf_name']);
    $this->assertStringStartsWith('$', $user['hashed_password']);
    // The bundled staff role has lots of permissions including 'administer users'.
    $result = UserRole::create(FALSE)
      ->setValues([
        'user_id' => $this->adminUserID,
        'role_id.name' => 'staff',
      ])
      ->execute()->first();
    $this->assertNotEmpty($result);

    // Create low privileges contact and user.
    $this->nonAdminContactID = \Civi\Api4\Contact::create(FALSE)
      ->setValues([
        'contact_type' => 'Individual',
        'display_name' => 'Nonadmin McTest',
      ])->execute()->first()['id'];
    $params = ['cms_name' => 'nonadmin', 'cms_pass' => 'secret2', 'notify' => FALSE, 'contact_id' => $this->nonAdminContactID, 'email' => 'nonadmin@example.org'];
    $this->nonAdminUserID = \CRM_Core_BAO_CMSUser::create($params, 'email');
    $this->assertGreaterThan(0, $this->nonAdminUserID);
    $this->assertGreaterThan(0, $this->adminUserID);
    $roleID = $this->createNonadminRole();
    $result = UserRole::create(FALSE)
      ->setValues([
        'user_id' => $this->nonAdminUserID,
        'role_id' => $roleID,
      ])
      ->execute();
    $this->assertNotEmpty($result);
  }

  protected function createNonadminRole(): int {

    $this->nonAdminRoleID = \Civi\Api4\Role::save(FALSE)
      ->setMatch(['name'])
      ->setRecords([
        [
          'name' => 'nonadmin',
          'label' => ts('Non-user-admin accounts'),
          'is_active' => TRUE,
          'permissions' => [
            'access AJAX API',
            'access CiviCRM',
            'access Contact Dashboard',
            'access uploaded files',
            'add contacts',
            'view my contact',
            'view all contacts',
            'edit all contacts',
            'edit my contact',
            'delete contacts',
            'import contacts',
            'access deleted contacts',
            'merge duplicate contacts',
            'edit groups',
            'manage tags',
            'administer Tagsets',
            'view all activities',
            'delete activities',
            'add contact notes',
            'view all notes',
            'access CiviContribute',
            'delete in CiviContribute',
            'edit contributions',
            'make online contributions',
            'view my invoices',
            'access CiviEvent',
            'delete in CiviEvent',
            'edit all events',
            'edit event participants',
            'register for events',
            'view event info',
            'view event participants',
            'gotv campaign contacts',
            'interview campaign contacts',
            'manage campaign',
            'release campaign contacts',
            'reserve campaign contacts',
            'sign CiviCRM Petition',
            'access CiviGrant',
            'delete in CiviGrant',
            'edit grants',
            'access CiviMail',
            'access CiviMail subscribe/unsubscribe pages',
            'delete in CiviMail',
            'view public CiviMail content',
            'access CiviMember',
            'delete in CiviMember',
            'edit memberships',
            'access all cases and activities',
            'access my cases and activities',
            'add cases',
            'delete in CiviCase',
            'access CiviPledge',
            'delete in CiviPledge',
            'edit pledges',
            'access CiviReport',
            'access Report Criteria',
            'administer reserved reports',
            'save Report Criteria',
            'profile create',
            'profile edit',
            'profile listings',
            'profile listings and forms',
            'profile view',
            'close all manual batches',
            'close own manual batches',
            'create manual batch',
            'delete all manual batches',
            'delete own manual batches',
            'edit all manual batches',
            'edit own manual batches',
            'export all manual batches',
            'export own manual batches',
            'reopen all manual batches',
            'reopen own manual batches',
            'view all manual batches',
            'view own manual batches',
            'access all custom data',
            'access contact reference fields',
            // Standalone-defined permissions that have the same name as the cms: prefixed synthetic ones
            // 'administer users',
            'view user account',
            // The admninister CiviCRM data implicitly sets other permissions as well.
            // Such as, edit message templates and admnister dedupe rules.
            'administer CiviCRM Data',
          ],
        ],
      ])
      ->execute()->first()['id'];
    $this->assertNotEmpty($this->nonAdminRoleID);
    return $this->nonAdminRoleID;
  }

  protected function deleteStuffWeMade() {
    User::delete(FALSE)->addWhere('id', 'IN', [$this->nonAdminUserID, $this->adminUserID, ...$this->otherUserIDs])->execute();
    Contact::delete(FALSE)->addWhere('id', 'IN', [$this->nonAdminContactID, $this->adminContactID])->execute();
    Role::delete(FALSE)->addWhere('name', '=', 'nonadmin')->execute();
  }

  /*
   * Test methods
   */

  /**
   * Check non-permissioned password changing.
   */
  public function testNonPermissionedPasswordChanging() {

    $user = User::get(FALSE)
      ->addWhere('id', '=', $this->nonAdminUserID)
      ->execute()->single();
    $this->assertMatchesRegularExpression('/^[$].+[$].+/', $user['hashed_password']);

    // Update to the loaded values should NOT result in the password being changed.
    $updatedUser = User::update(FALSE)
      ->setValues($user)
      ->addWhere('id', '=', $user['id'])
      ->setReload(TRUE)
      ->execute()->first();
    $this->assertEquals($user['hashed_password'], $updatedUser['hashed_password']);

    // Ditto save
    User::save(FALSE)
      ->setRecords([$user])
      ->setReload(TRUE)
      ->execute()->first();
    $updatedUser = User::get(FALSE)->addWhere('id', '=', $user['id'])->execute()->first();
    $this->assertEquals($user['hashed_password'], $updatedUser['hashed_password']);

    // Test we can force saving a raw hashed password
    $updatedUser = User::update(FALSE)
      ->setReload(TRUE)
      ->addValue('hashed_password', '$shhh')
      ->addWhere('id', '=', $user['id'])
      ->execute()->first();
    $this->assertEquals('$shhh', $updatedUser['hashed_password']);

    // Test we can saving a new password. (This also resets the fixture's nonadmin user's password to secret2)
    $updatedUser = User::update(FALSE)
      ->setReload(TRUE)
      ->addValue('password', 'secret2')
      ->addWhere('id', '=', $user['id'])
      ->execute()->first();
    $this->assertNotEquals('$shhh', $updatedUser['hashed_password']);
  }

  /**
   * Check non-permissioned password changing.
   */
  public function testPermissionedPasswordChangingAsAdmin() {
    $this->loginUser($this->adminUserID);
    $this->assertTrue(\CRM_Core_Permission::check('cms:administer users'));

    // Check we are allowed to update another user's password if we provide our own,
    // since we have 'cms:administer users'
    $nonAdminUser = User::get(FALSE)->addWhere('id', '=', $this->nonAdminUserID)->execute()->single();
    // ...by password
    $previousHash = $nonAdminUser['hashed_password'];
    $updatedUser = User::update(TRUE)
      ->addValue('password', 'topSecret')
      ->addWhere('id', '=', $this->nonAdminUserID)
      ->setActorPassword('secret1')
      ->setReload(TRUE)
      ->execute()->first();
    $this->assertNotEquals($previousHash, $updatedUser['hashed_password'], "Expected that the password was changed, but it wasn't.");
    $previousHash = $updatedUser['hashed_password'];

    // ...but NOT by hashed_password
    try {
      $updatedUser = User::update(TRUE)
        ->addValue('hashed_password', '$someNefariousHash')
        ->addWhere('id', '=', $nonAdminUser['id'])
        ->setActorPassword('secret1')
        ->execute();
      $this->fail("Expected UnauthorizedException got none.");
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertEquals('Not allowed to change hashed_password', $e->getMessage());
    }

    // If we don't supply OUR correct password, we're not allowed to update the user's password.
    try {
      User::update(TRUE)
        ->addValue('password', 'anotherNewPassword')
        ->addWhere('id', '=', $nonAdminUser['id'])
        ->setActorPassword('wrong password')
        ->execute();
      $this->fail("Expected UnauthorizedException got none.");
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertEquals('Incorrect password', $e->getMessage());
    }

    // If we don't supply OUR password at all, we're not allowed to update the user's password.
    try {
      User::update(TRUE)
        ->addValue('password', 'anotherNewPassword')
        ->addWhere('id', '=', $nonAdminUser['id'])
        ->execute();
      $this->fail("Expected UnauthorizedException got none.");
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertEquals('Unauthorized', $e->getMessage());
    }

  }

  /**
   * Check non-permissioned password changing.
   */
  public function testPermissionedPasswordChangingAsNonAdmin() {
    $this->loginUser($this->nonAdminUserID);
    $nonAdminUser = User::get(FALSE)->addWhere('id', '=', $this->nonAdminUserID)->execute()->single();

    // We are allowed to update our own password if we provide the current one.
    $previousHash = $nonAdminUser['hashed_password'];
    $updatedUser = User::update(TRUE)
      ->setActorPassword('secret2')
      ->addValue('password', 'ourNewSecret')
      ->addWhere('id', '=', $this->nonAdminUserID)
      ->setReload(TRUE)
      ->execute()->first();
    $this->assertNotEquals($previousHash, $updatedUser['hashed_password'], "Expected that the password was changed, but it wasn't.");
    $previousHash = $updatedUser['hashed_password'];

    // If we don't supply OUR correct password, we're not allowed to update our password.
    try {
      User::update(TRUE)
        ->addValue('password', 'anotherNewPassword')
        ->addWhere('id', '=', $this->nonAdminUserID)
        ->setActorPassword('wrong password')
        ->execute();
      $this->fail("Expected UnauthorizedException got none.");
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertEquals('Incorrect password', $e->getMessage());
    }

    // If we don't supply OUR password at all, we're not allowed to update the user's password.
    try {
      User::update(TRUE)
        ->addValue('password', 'anotherNewPassword')
        ->addWhere('id', '=', $this->nonAdminUserID)
        ->execute();
      $this->fail("Expected UnauthorizedException got none.");
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertEquals('Unauthorized', $e->getMessage());
    }

    // We're not allowed to update the admin user's password, since we are not an admin.
    try {
      User::update(TRUE)
        ->addValue('password', 'anotherNewPassword')
        ->addWhere('id', '=', $this->adminUserID)
        ->setActorPassword('ourNewSecret')
        ->execute();
      $this->fail("Expected UnauthorizedException got none.");
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertEquals("User.update called without 'cms:administer users' permission and without a where clause limiting to logged-in user.", $e->getMessage());
    }

    // We're allowed to use User.save to update our account, IF we provide our password.
    $updatedUser = User::save(TRUE)
      ->setActorPassword('ourNewSecret')
      ->setRecords([
        [
          'id' => $this->nonAdminUserID,
          'password' => 'yetAnotherPassword',
        ],
      ])
      ->execute()->first();
    $updatedUser = User::get(FALSE)->addWhere('id', '=', $this->nonAdminUserID)->execute()->first();
    $this->assertNotEquals($previousHash, $updatedUser['hashed_password'], "Expected that the password was changed, but it wasn't.");
    $previousHash = $updatedUser['hashed_password'];
  }

  public function testPermissionedSaveAsNonAdmin() {

    // We can save our own record.
    $this->loginUser($this->nonAdminUserID);
    $updatedUser = User::save(TRUE)
      ->setRecords([[
        'id' => $this->nonAdminUserID,
        'username' => 'nonadmin2',
      ],
      ])
      ->execute()->first();
    $updatedUser = User::get(FALSE)->addWhere('id', '=', $this->nonAdminUserID)->execute()->first();
    $this->assertEquals('nonadmin2', $updatedUser['username']);

    // We cannot create via save
    $this->loginUser($this->nonAdminUserID);
    try {
      $updatedUser = User::save(TRUE)
        ->setRecords([[
          'username' => 'newUser',
        ],
        ])
        ->execute()->first();
      $this->fail("User::save should have thrown exception");
    }
    catch (\Exception $e) {
      $this->assertEquals("You are not permitted to change other users' accounts.", $e->getMessage());
    }

    // We cannot update other users via save
    $this->loginUser($this->nonAdminUserID);
    try {
      $updatedUser = User::save(TRUE)
        ->setRecords([
          ['id' => $this->nonAdminUserID, 'username' => 'nonadmin3'],
          ['id' => $this->adminUserID, 'username' => 'mwahaha'],
        ])
        ->execute();
      $this->fail("User::save should have thrown exception");
    }
    catch (\Exception $e) {
      $this->assertEquals("You are not permitted to change other users' accounts.", $e->getMessage());
    }
    $updatedUser = User::get(FALSE)->addWhere('id', '=', $this->nonAdminUserID)->execute()->first();
    $this->assertEquals('nonadmin2', $updatedUser['username'], "The save should NOT have updated the nonadmin user; the whole save operation should have been aborted.");
  }

  public function testPermissionedSaveAsAdmin() {

    // We can save and create records.
    $this->loginUser($this->adminUserID);
    $newUserID = User::save(TRUE)
      ->setRecords([
        ['id' => $this->adminUserID, 'username' => 'admin2'],
        ['id' => $this->nonAdminUserID, 'username' => 'nonadmin2'],
        ['username' => 'newUser'],
      ])
      ->execute()->last()['id'];

    $updatedUser = User::get(FALSE)
      ->addWhere('id', 'IN', [$this->nonAdminUserID, $this->adminUserID, $newUserID])
      ->execute()->indexBy('id')->column('username');
    $this->assertEquals([
      $this->nonAdminUserID => 'nonadmin2',
      $this->adminUserID => 'admin2',
      $newUserID => 'newUser',
    ], $updatedUser);
    $this->otherUserIDs[] = $newUserID;
  }

  public function testAdminDeletes() {
    $this->loginUser($this->adminUserID);

    // Don't let us delete our own account.
    try {
      User::delete(TRUE)
        ->addWhere('id', '=', $this->adminUserID)
        ->execute();
      $this->fail("Should not be allowed to delete logged in User");
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertEquals('ACL check failed', $e->getMessage());
    }

    // Admin should be able to delete other accounts.
    User::delete(TRUE)
      ->addWhere('id', '=', $this->nonAdminUserID)
      ->execute()->single();

    // Admin are able to delete own account if not checking permissions.
    // This is still stupid, and the test is here to acknowledge/document
    // current rather than desired behaviour.
    User::delete(FALSE)
      ->addWhere('id', '=', $this->adminUserID)
      ->execute()->single();
  }

  public function testNonAdminDeletes() {
    $this->loginUser($this->nonAdminUserID);

    // Don't let us delete any account.
    // ...try our own account
    try {
      User::delete(TRUE)
        ->addWhere('id', '=', $this->nonAdminUserID)
        ->execute();
      $this->fail("Non-admins should not be able to use User.delete API");
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertStringContainsString('Authorization failed', $e->getMessage());
    }

    // ...try another account
    try {
      User::delete(TRUE)
        ->addWhere('id', '=', $this->adminUserID)
        ->execute();
      $this->fail("Non-admins should not be able to use User.delete API");
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertStringContainsString('Authorization failed', $e->getMessage());
    }
  }

  public function testNonAdminsCantCreate() {
    $this->loginUser($this->nonAdminUserID);
    try {
      $user = User::create(TRUE)
        ->addValue('username', 'newUser2')
        ->execute()->first();
      $this->otherUserIDs[] = $user['id'];
      $this->fail("Non admins should not be allowed to use User.create");
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertStringContainsString('Authorization failed', $e->getMessage());
    }
  }

  public function testAdminsCanCreate() {
    $this->loginUser($this->adminUserID);
    $user = User::create(TRUE)
      ->addValue('username', 'newUser3')
      ->execute()->first();
    $this->assertNotEmpty($user['id']);
    $this->otherUserIDs[] = $user['id'];
  }

  public function testGetIsLimited() {

    $this->loginUser($this->nonAdminUserID);

    $user = User::get(TRUE)->execute();
    $this->assertEquals(1, $user->countFetched());
    $this->assertArrayNotHasKey('hashed_password', $user->first());
    $this->assertArrayNotHasKey('password_reset_token', $user->first());

    $user = User::get(TRUE)->addWhere('id', '=', $this->adminUserID)->execute();
    $this->assertEquals(0, $user->countFetched());

    $user = User::get(FALSE)->execute();
    $this->assertGreaterThan(1, $user->countFetched());
    $this->assertArrayHasKey('hashed_password', $user->first());
    $this->assertArrayHasKey('password_reset_token', $user->first());
  }

  /**
   * Superflous test but here for completeness' sake
   * since it would be bad if anon users could access the User API!
   */
  public function testAnonymousUserHasNoAccess() {
    $userID = \CRM_Utils_System::getLoggedInUfID();
    $this->assertEmpty($userID);
    foreach (['save', 'create', 'get', 'delete', 'update'] as $actionName) {

      try {
        User::$actionName(TRUE)->execute();
        $this->fail("User::$actionName should have thrown exception");
      }
      catch (\Exception $e) {
        $this->assertStringContainsString("Authorization failed", $e->getMessage());
      }
    }
  }

  public function testNonAdminCannotChangeRoles() {
    $this->loginUser($this->nonAdminUserID);

    // ...via UserRole.create
    $adminRoleID = Role::get(FALSE)->addWhere('name', '=', 'admin')->execute()->single()['id'];
    try {
      UserRole::create(TRUE)
        ->addValue('user_id', $this->nonAdminUserID)
        ->addValue('role_id', $adminRoleID)
        ->execute();
      $this->fail("UserRole::create should have thrown exception");
    }
    catch (\Exception $e) {
      $this->assertStringContainsString("Authorization failed", $e->getMessage());
    }

    // ...via editing own user.
    try {
      User::update(TRUE)
        ->addWhere('id', '=', $this->nonAdminUserID)
        ->addValue('roles', [$adminRoleID])
        ->execute();
      $this->fail("User::update should have thrown exception");
    }
    catch (\Exception $e) {
      $this->assertEquals("Not allowed to change roles", $e->getMessage());
    }
  }

  public function testAdminCanChangeRoles() {
    $this->loginUser($this->adminUserID);

    // ...via UserRole.create
    $adminRoleID = Role::get(FALSE)->addWhere('name', '=', 'admin')->execute()->single()['id'];
    UserRole::create(TRUE)
      ->addValue('user_id', $this->nonAdminUserID)
      ->addValue('role_id', $adminRoleID)
      ->execute()->single();

    // ...via editing a user.
    User::update(TRUE)
      ->addWhere('id', '=', $this->nonAdminUserID)
      ->addValue('roles', [$this->nonAdminRoleID])
      ->execute()->single();
  }

  /**
   * Important that roles, permissions, sessions can't be messed about with or inspected by non-admins.
   */
  public function testOtherApiAccess() {

    // Anon users should have no access
    $userID = \CRM_Utils_System::getLoggedInUfID();
    $this->assertEmpty($userID);
    $entities = ['Role', 'RolePermission', 'Session'];
    foreach ($entities as $entity) {
      try {
        Request::create($entity, 'get', ['version' => 4])->execute();
        $this->fail("$entity::get should fail for anon user.");
      }
      catch (\Exception $e) {
        $this->assertStringContainsString("Authorization failed", $e->getMessage());
      }
    }

    // Non-admins also...
    $this->loginUser($this->nonAdminUserID);
    foreach ($entities as $entity) {
      try {
        Request::create($entity, 'get', ['version' => 4])->execute();
        $this->fail("$entity::get should fail for anon user.");
      }
      catch (\Exception $e) {
        $this->assertStringContainsString("Authorization failed", $e->getMessage());
      }
    }

    // Admins should have access though.
    $this->loginUser($this->adminUserID);
    foreach ($entities as $entity) {
      // We don't test session, since there isn't one in this context.
      $count = Request::create($entity, 'get', ['version' => 4])->execute()->countFetched();
      if ($entity !== 'Session') {
        $this->assertGreaterThan(0, $count, "Admin should find 1+ $entity entities");
      }
      else {
        $this->assertEquals(0, $count, "Not expecting a session to be present in this context.");
      }
    }
  }

  public function testEveryoneRoleProtections() {
    $this->loginUser($this->adminUserID);
    $this->assertRoleCannotBeDeleted('everyone');
    $this->assertRoleUpdateFails('everyone', ['name' => 'everyone']);
    $this->assertRoleUpdateFails('everyone', ['is_active' => FALSE]);
    // Check we can change permissions
    $user = Role::update(TRUE)
      ->addWhere('name', '=', 'everyone')
      ->setValues([
        'permissions' => ['access CiviMail subscribe/unsubscribe pages'],
        'label' => 'Y’all',
      ])
      ->execute();
  }

  public function testAdminRoleProtections() {
    $this->loginUser($this->adminUserID);
    $this->assertRoleCannotBeDeleted('admin');
    $this->assertRoleUpdateFails('admin', ['name' => 'admin']);
    $this->assertRoleUpdateFails('admin', ['is_active' => FALSE]);
  }

  protected function assertRoleCannotBeDeleted($roleName) {
    try {
      $user = Role::delete(TRUE)
        ->addWhere('name', '=', $roleName)
        ->execute();
      $this->fail("We were able to delete the '$roleName' role and we should not be allowed to.");
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertEquals('ACL check failed', $e->getMessage());
    }
  }

  protected function assertRoleUpdateFails($roleName, array $updates) {
    try {
      $user = Role::update(TRUE)
        ->addWhere('name', '=', $roleName)
        ->setValues($updates)
        ->execute();
      $this->fail("We were able to update the '$roleName' role and we should not be allowed to.");
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertEquals('ACL check failed', $e->getMessage());
    }
  }

}
