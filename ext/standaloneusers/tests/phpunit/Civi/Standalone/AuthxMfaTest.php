<?php
namespace Civi\Standalone;

use Civi\Authx\AuthxException;
use Civi\Authx\CheckCredential;
use Civi\Authx\CheckCredentialEvent;
use Civi\Authx\IgnoreCredentialException;
use Civi\Test\EndToEndInterface;
use Civi\Test\EntityTrait;
use Civi\Test\TransactionalInterface;

/**
 * HTTP Basic credentials when Standalone MFA is enabled.
 *
 * @group e2e
 */
class AuthxMfaTest extends \PHPUnit\Framework\TestCase implements EndToEndInterface, TransactionalInterface {

  use EntityTrait;

  private array $originalSettings = [];

  /**
   * Basic credentials that reached the slot right before the password check.
   *
   * @var array
   */
  private array $passwordCheckReached = [];

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    \Civi\Test::e2e()->apply(FALSE);
  }

  public function setUp(): void {
    parent::setUp();
    if (CIVICRM_UF !== 'Standalone') {
      $this->markTestSkipped('Test only applies on Standalone');
    }
    foreach (['standalone_mfa_enabled', 'authx_header_cred', 'authx_login_cred'] as $name) {
      $this->originalSettings[$name] = \Civi::settings()->get($name);
    }
    $contact = $this->createTestEntity('Contact', ['contact_type' => 'Individual', 'display_name' => 'Authx Mfa']);
    $this->createTestEntity('User', [
      'username' => 'authx_mfa_user',
      'password' => 'secret1',
      'contact_id' => $contact['id'],
      'uf_name' => 'authx_mfa_user@example.org',
      'roles:name' => ['admin'],
    ]);
    // Same priority as the MFA listener, registered later, so it runs right after it.
    \Civi::dispatcher()->addListener('civi.authx.checkCredential', [$this, 'recordPasswordCheckReached'], CheckCredential::PRIORITY_BASIC_USER + 1);
    \Civi::settings()->set('authx_header_cred', ['jwt', 'api_key']);
    \Civi::settings()->set('authx_login_cred', ['jwt', 'pass']);
  }

  public function tearDown(): void {
    \Civi::dispatcher()->removeListener('civi.authx.checkCredential', [$this, 'recordPasswordCheckReached']);
    foreach ($this->originalSettings as $name => $value) {
      \Civi::settings()->set($name, $value);
    }
    parent::tearDown();
  }

  public function testPasswordLoginWorksWithoutMfa(): void {
    \Civi::settings()->set('standalone_mfa_enabled', []);
    $principal = $this->validate('login', 'authx_mfa_user:secret1');
    $this->assertSame('pass', $principal['credType']);
    $this->assertCount(1, $this->passwordCheckReached);
  }

  public function testPasswordIsNeverCheckedWithMfa(): void {
    \Civi::settings()->set('standalone_mfa_enabled', ['TOTP']);
    try {
      $this->validate('login', 'authx_mfa_user:secret1');
      $this->fail('Basic credential was accepted with MFA enabled');
    }
    catch (AuthxException $e) {
      $this->assertSame([], $this->passwordCheckReached);
    }
  }

  public function testPasswordLoginIsRejectedWithMfa(): void {
    \Civi::settings()->set('standalone_mfa_enabled', ['TOTP']);
    $this->expectException(AuthxException::class);
    $this->expectExceptionMessage('Basic credentials not supported on systems with MFA enabled.');
    $this->validate('login', 'authx_mfa_user:secret1');
  }

  public function testWrongPasswordGetsTheSameRejectionWithMfa(): void {
    \Civi::settings()->set('standalone_mfa_enabled', ['TOTP']);
    $this->expectException(AuthxException::class);
    $this->expectExceptionMessage('Basic credentials not supported on systems with MFA enabled.');
    $this->validate('login', 'authx_mfa_user:wrong');
  }

  public function testPasswordHeaderIsRejectedWithMfaWhenHeaderAllowsPasswords(): void {
    \Civi::settings()->set('standalone_mfa_enabled', ['TOTP']);
    \Civi::settings()->set('authx_header_cred', ['jwt', 'api_key', 'pass']);
    $this->expectException(AuthxException::class);
    $this->expectExceptionMessage('Basic credentials not supported on systems with MFA enabled.');
    $this->validate('header', 'authx_mfa_user:secret1');
  }

  /**
   * A Basic header meant for something else (a webhook secret, a proxy
   * password) is ignored when the header flow does not accept passwords.
   */
  public function testForeignBasicHeaderIsIgnoredWithMfa(): void {
    \Civi::settings()->set('standalone_mfa_enabled', ['TOTP']);
    $this->expectException(IgnoreCredentialException::class);
    $this->validate('header', 'webhook:not-a-civicrm-password');
  }

  public function recordPasswordCheckReached(CheckCredentialEvent $check): void {
    if ($check->credFormat === 'Basic') {
      $this->passwordCheckReached[] = $check->credValue;
    }
  }

  private function validate(string $flow, string $userPass): array {
    $auth = new \Civi\Authx\Authenticator();
    $auth->setRejectMode('exception');
    return $auth->validate([
      'flow' => $flow,
      'cred' => 'Basic ' . base64_encode($userPass),
    ]);
  }

}
