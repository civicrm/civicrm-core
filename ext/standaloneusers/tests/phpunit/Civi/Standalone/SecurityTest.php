<?php
namespace Civi\Standalone;

use CRM_Standaloneusers_ExtensionUtil as E;
use Civi\Test\EndToEndInterface;
use Civi\Test\TransactionalInterface;

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
class SecurityTest extends \PHPUnit\Framework\TestCase implements EndToEndInterface, TransactionalInterface {

  protected $originalUF;
  protected $originalUFPermission;
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
    $this->switchBackFromOurUFClasses(TRUE);
    parent::tearDown();
  }

  public function testCreateUser():void {
    [$contactID, $userID, $security] = $this->createFixtureContactAndUser();

    $user = \Civi\Api4\User::get(FALSE)
      ->addSelect('*', 'uf_match.*')
      ->addWhere('id', '=', $userID)
      ->addJoin('UFMatch AS uf_match', 'INNER', ['uf_match.uf_id', '=', 'id'])
      ->execute()->single();

    $this->assertEquals('user_one', $user['username']);
    $this->assertEquals('user_one@example.org', $user['email']);
    $this->assertStringStartsWith('$', $user['password']);

    $this->assertTrue($security->checkPassword('secret1', $user['password']));
    $this->assertFalse($security->checkPassword('some other password', $user['password']));
  }

  public function testPerms() {
    [$contactID, $userID, $security] = $this->createFixtureContactAndUser();

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

    $this->switchToOurUFClasses();
    foreach (['access CiviCRM', 'view all contacts', 'add contacts', 'edit all contacts'] as $allowed) {
      $this->assertTrue(\CRM_Core_Permission::check([$allowed], $contactID), "Should have '$allowed' permission but don't");
    }
    foreach (['administer CiviCRM', 'access uploaded files'] as $notAllowed) {
      $this->assertFalse(\CRM_Core_Permission::check([$notAllowed], $contactID), "Should NOT have '$allowed' permission but do");
    }
    $this->switchBackFromOurUFClasses();
  }

  protected function switchToOurUFClasses() {
    if (!empty($this->originalUFPermission)) {
      throw new \RuntimeException("are you calling switchToOurUFClasses twice?");
    }
    $this->originalUFPermission = \CRM_Core_Config::singleton()->userPermissionClass;
    $this->originalUF = \CRM_Core_Config::singleton()->userSystem;
    \CRM_Core_Config::singleton()->userPermissionClass = new \CRM_Core_Permission_Standalone();
    \CRM_Core_Config::singleton()->userSystem = new \CRM_Utils_System_Standalone();
  }

  protected function switchBackFromOurUFClasses($justInCase = FALSE) {
    if (!$justInCase && empty($this->originalUFPermission)) {
      throw new \RuntimeException("are you calling switchBackFromOurUFClasses() twice?");
    }
    \CRM_Core_Config::singleton()->userPermissionClass = $this->originalUFPermission;
    \CRM_Core_Config::singleton()->userSystem = $this->originalUF;
    $this->originalUFPermission = $this->originalUF = NULL;
  }

  public function createFixtureContactAndUser(): array {

    $contactID = \Civi\Api4\Contact::create(FALSE)
      ->setValues([
        'contact_type' => 'Individual',
        'display_name' => 'Admin McDemo',
      ])->execute()->first()['id'];

    $security = Security::singleton();
    $params = ['cms_name' => 'user_one', 'cms_pass' => 'secret1', 'notify' => FALSE, 'contactID' => $contactID, 'email' => 'user_one@example.org'];

    $this->switchToOurUFClasses();
    $userID = \CRM_Core_BAO_CMSUser::create($params, 'email');
    $this->switchBackFromOurUFClasses();

    $this->assertGreaterThan(0, $userID);
    $this->contactID = $contactID;
    $this->userID = $userID;

    return [$contactID, $userID, $security];
  }

}
