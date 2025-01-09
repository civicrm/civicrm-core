<?php
namespace Civi\Standalone;

use Civi\Test\EndToEndInterface;
use Civi\Test\TransactionalInterface;
use Civi\Api4\User;
use Civi\Api4\Action\User\PasswordReset;
use Civi\Api4\Action\User\SendPasswordResetEmail;

/**
 * Test Security flows in Standalone
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
class SecurityTest extends \PHPUnit\Framework\TestCase implements EndToEndInterface, TransactionalInterface {

  protected $contactID;
  protected $userID;

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
  }

  public function tearDown():void {
    $this->deleteStuffWeMade();
    parent::tearDown();
  }

  protected function loginUser($userID) {
    $user = \Civi\Api4\User::get(FALSE)
      ->addWhere('id', '=', $userID)
      ->execute()->first();

    $contactID = civicrm_api3('UFMatch', 'get', [
      'sequential' => 1,
      'return' => ['contact_id'],
      'uf_id' => $user['id'],
    ])['values'][0]['contact_id'] ?? NULL;
    $this->assertNotNull($contactID);

    \CRM_Core_Config::singleton()->userSystem->loadUser($user['username']);
  }

  public function testCheckPassword():void {
    [$contactID, $userID, $security] = $this->createFixtureContactAndUser();

    $user = \Civi\Api4\User::get(FALSE)
      ->addWhere('id', '=', $userID)
      ->execute()->single();

    // Test that the password can be checked ok.
    $this->assertTrue((bool) $security->checkPassword($user['username'], 'secret1'));
    $this->assertFalse((bool) $security->checkPassword($user['username'], 'some other password'));
  }

  public function testPerms() {
    [$contactID, $userID, $security] = $this->createFixtureContactAndUser();
    $ufID = \CRM_Core_BAO_UFMatch::getUFId($contactID);
    $this->assertEquals($userID, $ufID);

    // Create a custom role
    $roleID = \Civi\Api4\Role::create(FALSE)
      ->setValues([
        'name' => 'demo_role',
        'label' => 'demo_role',
        'permissions' => [
            // Main control for access to the main CiviCRM backend and API. Give to trusted roles only.
          'access CiviCRM',
          'view all contacts',
          'add contacts',
          'edit all contacts',
           // 'administer CiviCRM' // Perform all tasks in the Administer CiviCRM control panel and Import Contacts
        ],
      ])->execute()->first()['id'];

    // Give our user this role only.
    \Civi\Api4\User::update(FALSE)
      ->addValue('roles:name', ['demo_role'])
      ->addWhere('id', '=', $userID)
      ->execute();

    foreach (['access CiviCRM', 'view all contacts', 'add contacts', 'edit all contacts'] as $allowed) {
      $this->assertTrue(\CRM_Core_Permission::check([$allowed], $contactID), "Should have '$allowed' permission but don't");
    }
    foreach (['administer CiviCRM', 'access uploaded files'] as $notAllowed) {
      $this->assertFalse(\CRM_Core_Permission::check([$notAllowed], $contactID), "Should NOT have '$allowed' permission but do");
    }
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
   * @return Array[int, int, \Civi\Standalone\Security]
   */
  public function createFixtureContactAndUser(): array {
    $contactID = \Civi\Api4\Contact::create(FALSE)
      ->setValues([
        'contact_type' => 'Individual',
        'display_name' => 'Admin McDemo',
      ])->execute()->first()['id'];

    $params = ['cms_name' => 'user_one', 'cms_pass' => 'secret1', 'notify' => FALSE, 'contact_id' => $contactID, 'email' => 'user_one@example.org'];
    $userID = \CRM_Core_BAO_CMSUser::create($params, 'email');
    $this->assertGreaterThan(0, $userID);
    $this->contactID = $contactID;
    $this->userID = $userID;

    $security = Security::singleton();
    return [$contactID, $userID, $security];
  }

  public function ensureStaffRoleExists() {
    $staffRole = \Civi\Api4\Role::get(FALSE)
      ->addWhere('name', '=', 'staffRole')
      ->execute()->first();
    if (!$staffRole) {
      \Civi\Api4\Role::create(FALSE)
        ->setValues([
          'name' => 'staff',
          'label' => 'General staff',
          'permissions' => [
            "access CiviCRM",
            "access Contact Dashboard",
            "view my contact",
            "edit my contact",
            "make online contributions",
            "view event info",
            "register for events",
            "authenticate with password",
          ],
        ])->execute();
    }
  }

  public static function storeFakePasswordResetToken(int $userID, int $expires): string {
    $token = \Civi::service('crypto.jwt')->encode([
      'exp' => $expires,
      'sub' => "uid:$userID",
      'scope' => PasswordReset::PASSWORD_RESET_SCOPE,
    ]);
    User::update(FALSE)
      ->addValue('password_reset_token', $token)
      ->addWhere('id', '=', $userID)
      ->execute();

    return $token;
  }

  public function testForgottenPassword() {

    /** @var Security $security */
    [$contactID, $userID, $security] = $this->createFixtureContactAndUser();

    // Create token.
    $token = PasswordReset::updateToken($userID);
    $decodedToken = \Civi::service('crypto.jwt')->decode($token);
    $this->assertEquals('uid:' . $userID, $decodedToken['sub']);
    $this->assertEquals(PasswordReset::PASSWORD_RESET_SCOPE, $decodedToken['scope']);

    // Check it works, but only once.
    $extractedUserID = PasswordReset::checkPasswordResetToken($token);
    $this->assertEquals($userID, $extractedUserID);
    $this->assertNull(PasswordReset::checkPasswordResetToken($token));

    // OK, let's change that password.
    $token = PasswordReset::updateToken($userID);

    // Attempt to change the user's password using this token to authenticate.
    $result = User::passwordReset(TRUE)
      ->setToken($token)
      ->setPassword('fingersCrossed')
      ->execute();

    $this->assertEquals(1, $result['success']);
    $user = User::get(FALSE)->addWhere('id', '=', $userID)->execute()->single();
    $this->assertTrue((bool) $security->checkPassword($user['username'], 'fingersCrossed'));

    // Should not work a 2nd time with same token.
    try {
      User::passwordReset(TRUE)
        ->setToken($token)
        ->setPassword('oooh')
        ->execute();
      $this->fail("Should not have been able to reuse token");
    }
    catch (\Exception $e) {
      $this->assertEquals('Invalid token.', $e->getMessage());
    }

    // Check the message template generation
    $token = PasswordReset::updateToken($userID);
    $workflow = SendPasswordResetEmail::preparePasswordResetWorkflow($user, $token);
    $this->assertNotNull($workflow);
    $result = $workflow->renderTemplate();

    $this->assertMatchesRegularExpression(';https?://[^/]+/civicrm/login/password.*' . $token . ';', $result['text']);
    $this->assertMatchesRegularExpression(';https?://[^/]+/civicrm/login/password.*' . $token . ';', $result['html']);
    $this->assertEquals('Password reset link for Demonstrators Anonymous', $result['subject']);

    // Fake an expired token
    $token = $this->storeFakePasswordResetToken($userID, time() - 1);
    $this->assertNull(PasswordReset::checkPasswordResetToken($token));
  }

  protected function deleteStuffWeMade() {
    User::delete(FALSE)->addWhere('username', '=', 'testuser1')->execute();
  }

}
