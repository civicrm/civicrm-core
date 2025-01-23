<?php
namespace Civi\Api4\Action\User;

use Civi;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Standalone\Event\LoginEvent;
use Civi\Standalone\MFA\Base as MFABase;
use Civi\Standalone\Security;

class Login extends AbstractAction {

  /**
   * Username to authenticate.
   *
   * @var string
   * @default NULL
   */
  protected ?string $username = NULL;

  /**
   * Password to authenticate.
   *
   * @var string
   * @default NULL
   */
  protected ?string $password = NULL;

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
      }
      else {
        $result['publicError'] = "MFA failed verification";
      }
    }

  }

  /**
   * Handle the initial API call which checks username and password.
   *
   * It sets $result['url'] to either the success URL,
   * or the URL of the enabled MFA.
   */
  protected function passwordCheck(Result $result) {

    $successUrl = '/civicrm';
    if (!empty($this->originalUrl) && parse_url($this->originalUrl, PHP_URL_PATH) !== '/civicrm/login') {
      // We will return to this URL on success.
      $successUrl = $this->originalUrl;
    }

    // Check user+password
    if (empty($this->username) || empty($this->password)) {
      $result['publicError'] = "Missing password/username";
      return;
    }
    $security = Security::singleton();
    $user = \Civi\Api4\User::get(FALSE)
      ->addWhere('username', '=', $this->username)
      ->addWhere('is_active', '=', TRUE)
      ->addSelect('hashed_password', 'id')
      ->execute()->first();

    // Allow flood control (etc.) by extensions.
    $event = new LoginEvent('pre_credentials_check', $user['id'] ?? NULL);
    Civi::dispatcher()->dispatch('civi.standalone.login', $event);
    if ($event->stopReason) {
      $result['url'] = '/civicrm/login?' . $event->stopReason;
    }

    $userID = $security->checkPassword($this->username, $this->password);
    if (!$userID) {
      $result['publicError'] = "Invalid credentials";
      // Allow monitoring of failed attempts.
      $event = new LoginEvent('post_credentials_check', $user['id'] ?? NULL, 'wrongUserPassword');
      Civi::dispatcher()->dispatch('civi.standalone.login', $event);
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
        // MFA enabled. Store data in a pendingLogin key on session.
        // @todo expose the 120s timeout to config?
        \CRM_Core_Session::singleton()->set('pendingLogin', [
          'userID' => $userID,
          'username' => $this->username,
          'expiry' => time() + 120,
          'successUrl' => $successUrl,
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
