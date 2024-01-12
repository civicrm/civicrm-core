<?php
namespace Civi\Api4\Action\User;

use Civi\Api4\Generic\Result;
use Civi\Standalone\Security;
use API_Exception;
use Civi\Api4\User;
use Civi\Api4\Generic\AbstractAction;

/**
 * This is designed to be a public API
 *
 * @method static setIdentifier(string $identifier)
 */
class PasswordReset extends AbstractAction {

  /**
   * Password reset token.
   *
   * @var string
   * @required
   */
  protected $token;

  /**
   * New password.
   *
   * @var string
   * @required
   */
  protected $password;

  public function _run(Result $result) {

    if (empty($this->password)) {
      throw new API_Exception("Invalid password");
    }

    // todo: some minimum password quality check?

    // Only valid change here is password, for a known ID.
    $security = Security::singleton();
    $userID = $security->checkPasswordResetToken($this->token);
    if (!$userID) {
      throw new API_Exception("Invalid token.");
    }

    User::update(FALSE)
      ->addWhere('id', '=', $userID)
      ->addValue('password', $this->password)
      ->execute();

    $result['success'] = 1;
    \Civi::log()->info("Changed password for user {userID} via User.PasswordReset", compact('userID'));
  }

}
