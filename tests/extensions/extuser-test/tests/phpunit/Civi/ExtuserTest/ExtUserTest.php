<?php
declare(strict_types = 1);

namespace Civi\ExtuserTest;

use CRM_ExtuserTest_ExtensionUtil as E;
use Civi\Test\EndToEndInterface;
use CRM_Utils_String;

/**
 * @group e2e
 */
class ExtUserTest extends \PHPUnit\Framework\TestCase implements EndToEndInterface {

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()->installMe(__DIR__)->apply();
  }

  public function setUp(): void {
    if (CIVICRM_UF !== 'Standalone') {
      $this->markTestSkipped('Functionality only applies on CiviCRM Standalone');
    }
    parent::setUp();
  }

  public function tearDown(): void {
    parent::tearDown();
  }

  public function testAuthxInitialSuccessAndUpdate(): void {
    [$username, $secret] = $this->createExternalStaffUser();
    $getUserId = fn($name) => \CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_uf_match WHERE username = %1", [
      1 => [$name, 'String'],
    ]);
    $getMailCount = fn($mail) => \CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_uf_match WHERE uf_name = %1', [
      1 => [$mail, 'String'],
    ]);

    $this->assertEquals(NULL, $getUserId($username), 'User does not exist yet.');
    $this->assertEquals(0, $getMailCount("$username@127.0.0.1"));

    $loginUserId = \_authx_uf()->checkPassword($username, $secret);
    $this->assertEquals($loginUserId, $getUserId($username), 'User is autoloaded.');
    $this->assertEquals(1, $getMailCount("$username@127.0.0.1"));

    // That succeeded. Now, what if the external data changes... will we use the new external data?

    \CRM_Utils_Time::setTime('+1 min');
    \Civi::service('extuser_list')->update($username, [
      'sketch' => hash('sha256', "new$secret"),
      'mail' => "$username@new.example.com",
      'role' => 'admin',
    ]);

    $reloginUserId = \_authx_uf()->checkPassword($username, "new$secret");
    $this->assertEquals($reloginUserId, $loginUserId, 'User is found again.');
    $this->assertEquals(1, $getMailCount("$username@new.example.com"), "New email should be added");
    $this->assertEquals(0, $getMailCount("$username@127.0.0.1"), "Old email should be removed");
  }

  public function testAuthxInitialFailure(): void {
    [$username, $secret] = $this->createExternalStaffUser();
    $getUserId = fn($name) => \CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_uf_match WHERE username = %1", [
      1 => [$name, 'String'],
    ]);

    $this->assertEmpty($getUserId($username), 'User does not exist yet.');
    $loginUserId = \_authx_uf()->checkPassword($username, 'wrong');
    $this->assertNotEmpty($getUserId($username), 'External user should be mapped.');
    $this->assertEquals(NULL, $loginUserId, 'But the password check should ultimately fail.');
  }

  /**
   * @return array|void
   */
  private function createExternalStaffUser() {
    $username = 'exttest_' . \CRM_Utils_String::createRandom(8, CRM_Utils_String::ALPHANUMERIC);
    $secret = 'exttest_' . \CRM_Utils_String::createRandom(16, CRM_Utils_String::ALPHANUMERIC);

    $userdb = \Civi::service('extuser_list');
    $userdb->save([
      "uid" => $username,
      "givenName" => "Staffer",
      "sn" => "Example",
      "mail" => "$username@127.0.0.1",
      "role" => "staff",
      "sketch" => hash("sha256", $secret),
    ]);
    $this->assertEquals('staff', $userdb->get($username)['role']);
    return [$username, $secret];
  }

}
