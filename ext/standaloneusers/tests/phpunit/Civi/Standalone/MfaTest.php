<?php
namespace Civi\Standalone;

use Civi\Test\TransactionalInterface;
use Civi\Standalone\MFA\TOTP;
use Civi\Test\EndToEndInterface;

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
class MfaTest extends \PHPUnit\Framework\TestCase implements EndToEndInterface, TransactionalInterface {

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    \Civi\Test::e2e()
      // ->install(['authx', 'org.civicrm.search_kit', 'org.civicrm.afform', 'standaloneusers'])
      // We only run on "Standalone", so all the above is given.
      // ->installMe(__DIR__) This causes failure, so we do                 ↑
      ->apply(FALSE);
  }

  public function setUp(): void {
    parent::setUp();
    if (CIVICRM_UF !== 'Standalone') {
      $this->markTestSkipped('Test only applies on Standalone');
    }
  }

  public function testOtp() {
    $mfa = new TOTP(1);
    $seed = $mfa->generateNew();
    $this->assertMatchesRegularExpression('/^\w{16}$/', $seed);

    $code = $mfa->getCode($seed);
    $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $code);
    $this->assertTrue($mfa->verifyCode($seed, $code));

    $mfa->storeSeed(1, $seed);
    $result = $mfa->checkMFAData($code);
    $this->assertTrue($result);

    // Let's hope 000000 is not a valid code!
    $this->assertFalse($mfa->checkMFAData('000000'));
    $this->assertFalse($mfa->checkMFAData(''));
  }

}
