<?php
namespace Civi\Api4\Action\User;

use Civi;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Standalone\Security;

class Login extends AbstractAction {

  /**
   * Username to authenticate.
   *
   * @default NULL
   * @var string
   */
  protected ?string $username = NULL;

  /**
   * Password to authenticate.
   *
   * @default NULL
   * @var string
   */
  protected ?string $password = NULL;

  /**
   * MFA Class.
   *
   * Used when trying to complete a login via an MFA class.
   *
   * @default NULL
   * @var string
   */
  protected ?string $mfaClass = NULL;

  public function _run(Result $result) {
    if (empty($this->mfaClass)) {
      return $this->passwordCheck($result);
    }
    else {
      // return $this->mfaCheck($result);
    }
  }

  protected function passwordCheck(Result $result) {
    // Check user+password
    if (empty($this->username) || empty($this->password)) {
      throw new \API_Exception("Missing password/username");
    }
    $security = Security::singleton();
    $user = $security->loadUserByName($this->username);
    if (!$security->checkPassword($this->password, $user['hashed_password'] ?? '')) {
      throw new \API_Exception("Invalid credentials");
    }
    // Password is ok. Do we have mfa configured?

    // Collect configured and present MFA classes.
    // This check means if an MFA extension is removed without changing config,
    // users can login without it.
    $mfaClasses = [];
    foreach (explode(',', Civi::settings()->get('standalone_mfa_enabled')) as $mfaClass) {
      if (is_subclass_of($mfaClass, 'Civi\\Standalone\\MFA\\MFAInterface') && class_exists($mfaClass)) {
        $mfaClasses[] = $mfaClass;
      }
    }

    // @todo remove this line if/when we implement a user choice of MFAs.
    $mfaClasses = array_slice($mfaClasses, 0, 1);

    switch (count($mfaClasses)) {

      case 0:
        // MFA not enabled.
        $this->loginUser($user['id']);
        $result['url'] = '/civicrm';
        return;

      case 1:
        // MFA enabled.
        $mfaClass = $mfaClasses[0];
        $mfa = new $mfaClass($user['id']);
        $result['url'] = $mfa->getFormUrl();
        break;

      default:
        // We have multiple MFAs enabled.
        // Currently unsupported and this codepath is never reached.
    }

  }

}
