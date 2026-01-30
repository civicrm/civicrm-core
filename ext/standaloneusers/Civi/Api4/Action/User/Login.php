<?php
namespace Civi\Api4\Action\User;

use Civi;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\User;
use Civi\Crypto\Exception\CryptoException;
use Civi\Standalone\Event\LoginEvent;
use Civi\Standalone\MFA\Base as MFABase;
use Civi\Standalone\Security;

class Login extends AbstractAction {

  /**
   * Username or email to authenticate.
   *
   * @var string
   * @default NULL
   */
  protected ?string $identifier = NULL;

  /**
   * Password to authenticate.
   *
   * @var string
   * @default NULL
   */
  protected ?string $password = NULL;

  /**
   * Remember me?
   *
   * Does the user want to be remembered, thereby skipping MFA for a while?
   *
   * @var bool
   * @default FALSE
   */
  protected bool $rememberMe = FALSE;

  /**
   * Previously issued JWT that authorised skipping MFA.
   *
   * @var string|null
   * @default NULL
   */
  protected ?string $rememberJWT = NULL;

  /**
   * MFA Class.
   *
   * Used when trying to complete a login via an MFA class.
   *
   * @var string
   * @default NULL
   */
  protected ?string $mfaClass = NULL;

  /**
   * MFA data.
   *
   * Used when trying to complete a login via an MFA class.
   *
   * @var mixed
   * @default NULL
   */
  protected $mfaData = NULL;

  /**
   * URL that was used to access the login page.
   *
   * @var string
   * @default NULL
   */
  protected ?string $originalUrl = NULL;

  public function _run(Result $result) {
    if (empty($this->mfaClass)) {
      // Initial call with username, password.
      return $this->passwordCheck($result);
    }
    else {
      // This call is from Javascript from an MFA class, needing to check the mfaData.
      $mfaClass = MFABase::classIsAvailable($this->mfaClass);
      if (!$mfaClass) {
        \CRM_Core_Session::singleton()->set('pendingLogin', []);
        $result['publicError'] = "MFA method not available. Please contact the site administrators.";
        return;
      }
      $pending = MFABase::getPendingLogin();
      if (!$pending) {
        // Invalid, send user back to login.
        \CRM_Core_Session::setStatus('Please try again.', 'Session expired', 'warning');
        $result['url'] = '/civicrm/login';
        return;
      }

      $mfa = new $mfaClass($pending['userID']);
      $okToLogin = $mfa->processMFAAttempt($pending, $this->mfaData);
      $event = new LoginEvent('post_mfa', $pending['userID'], $okToLogin ? NULL : 'wrongMFA');
      Civi::dispatcher()->dispatch('civi.standalone.login', $event);
      if ($okToLogin) {
        // OK!
        \CRM_Core_Session::singleton()->set('pendingLogin', []);
        $this->loginUser($pending['userID']);
        $result['url'] = $pending['successUrl'];
        $result['rememberJWT'] = $this->createRememberJWT($pending);
      }
      else {
        $result['publicError'] = "MFA failed verification";
      }
    }

  }

  protected function createRememberJWT(array $pending): string {
    $days = \Civi::settings()->get('standalone_mfa_remember') ?? 0;
    if (!$days || empty($pending['rememberMe'])) {
      // Feature disabled by admin or request.
      return '';
    }

    $hashedPass = $this->getPassHash($pending['userID']);
    if (!$hashedPass) {
      // This should not happen. But if it does, it's very fishy and we should
      // not support bypassing MFA.
      return '';
    }

    return \Civi::service('crypto.jwt')->encode([
      'iat' => time(),
      'exp' => time() + 60 * 60 * 24 * $days,
      'sub' => "user:$pending[userID]",
      'scope' => 'rememberMe',
      'hash' => $hashedPass,
    ]);
  }

  protected function getPassHash(int $userID): string {
    // Create a hash of the hashed password so that when the
    // user updates their password (even to the same password) then
    // it invalidates any previously generated rememberJWT values.
    $hashedPass = User::get(FALSE)
      ->addWhere('id', '=', $userID)
      ->addSelect('hashed_password')
      ->execute()
      ->first()['hashed_password'] ?? '';
    if (!$hashedPass) {
      // This should not happen. But if it does, it's very fishy and we should
      // not support bypassing MFA.
      return '';
    }
    $signKey = Civi::service("crypto.registry")->findKey("SIGN")['key'];
    return hash_hmac('sha256', $hashedPass, $signKey);
  }

  /**
   * Handle the initial API call which checks username and password.
   *
   * It sets $result['url'] to either the success URL,
   * or the URL of the enabled MFA.
   */
  protected function passwordCheck(Result $result) {

    $successUrl = '/civicrm/home';
    if (!empty($this->originalUrl) && parse_url($this->originalUrl, PHP_URL_PATH) !== '/civicrm/login') {
      // We will return to this URL on success.
      $successUrl = $this->originalUrl;
    }

    // clean whitespace
    $this->identifier = trim($this->identifier);

    // Check user+password
    if (empty($this->identifier) || empty($this->password)) {
      $result['publicError'] = "Missing password/username";
      return;
    }

    // Check for matching user
    $user = \Civi\Api4\User::get(FALSE)
      ->addWhere('username', '=', $this->identifier)
      ->addWhere('is_active', '=', TRUE)
      ->addSelect('username', 'id')
      ->execute()->first();

    // TODO: should login by email be behind a setting?
    // if (!$user && \Civi::settings()->get('standaloneusers_allow_login_by_email')) {
    if (!$user) {
      // Since the identifier did not match a username, try an email.
      $user = \Civi\Api4\User::get(FALSE)
        ->addWhere('uf_name', '=', $this->identifier)
        ->addWhere('is_active', '=', TRUE)
        ->addSelect('username', 'id')
        ->execute()->first();
    }

    // Allow flood control (etc.) by extensions.
    $event = new LoginEvent('pre_credentials_check', $user['id'] ?? NULL);
    Civi::dispatcher()->dispatch('civi.standalone.login', $event);

    if ($event->stopReason) {
      # note: if providing a stop reason through civi.standalone.login listener
      # you should probably provide user feedback on /civicrm/login?[mystopreason]
      $result['url'] = '/civicrm/login?' . $event->stopReason;
      return;
    }

    if (!$user) {
      // Allow monitoring of failed attempts.
      $event = new LoginEvent('post_credentials_check', NULL, 'noSuchUser');
      Civi::dispatcher()->dispatch('civi.standalone.login', $event);

      $result['publicError'] = "Invalid credentials";
      return;
    }

    $userID = Security::singleton()->checkPassword($user['username'], $this->password);
    if (!$userID) {
      // Allow monitoring of failed attempts.
      $event = new LoginEvent('post_credentials_check', $user['id'], 'wrongUserPassword');
      Civi::dispatcher()->dispatch('civi.standalone.login', $event);

      $result['publicError'] = "Invalid credentials";
      return;
    }
    // Password is ok. Do we have mfa configured?

    // Collect configured and present MFA classes.
    // This check means if an MFA extension is removed without changing config,
    // users can login without it.
    $mfaClasses = MFABase::getAvailableClasses();

    // @todo remove this line if/when we implement a user choice of MFAs.
    $mfaClasses = array_slice($mfaClasses, 0, 1);

    switch (count($mfaClasses)) {

      case 0:
        // MFA not enabled.
        $this->loginUser($userID);
        $result['url'] = $successUrl;
        return;

      case 1:
        // MFA enabled. Can it be skipped via the 'remember' mechanism?
        $days = \Civi::settings()->get('standalone_mfa_remember') ?? 0;
        if ($days && $this->rememberMe && $this->rememberJWT) {
          /** @var \Civi\Crypto\CryptoJwt $jwt */
          $jwtService = \Civi::service('crypto.jwt');
          try {
            $decoded = $jwtService->decode($this->rememberJWT);
            if ($decoded['sub'] === "user:$userID"
              && ($decoded['scope'] ?? '') === 'rememberMe'
              && ($decoded['hash'] ?? '') === $this->getPassHash($userID)
              && ((time() - $decoded['iat'] ?? 0) < $days * 60 * 60 * 24)
            ) {
              // Valid rememberJWT, allow bypassing MFA.
              $this->loginUser($userID);
              $result['url'] = $successUrl;
              return;
            }
          }
          catch (CryptoException $e) {
            // Invalid/expired JWT.
          }
        }

        // Store data in a pendingLogin key on session, valid for 2 mins.
        \CRM_Core_Session::singleton()->set('pendingLogin', [
          'userID' => $userID,
          'username' => $user['username'],
          'expiry' => time() + 120,
          'successUrl' => $successUrl,
          'rememberMe' => $this->rememberMe,
        ]);
        $mfaClass = $mfaClasses[0];
        $mfa = new $mfaClass($userID);
        // Return the URL for the MFA form.
        $result['url'] = $mfa->getFormUrl();
        break;

      default:
        // We have multiple MFAs enabled.
        // Currently unsupported and this codepath is never reached.
    }

  }

  protected function loginUser(int $userID) {
    _authx_uf()->loginSession($userID);
    $event = new LoginEvent('login_success', $userID);
    Civi::dispatcher()->dispatch('civi.standalone.login', $event);
  }

}
