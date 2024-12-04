<?php
namespace Civi\Api4\Action\User;

use Civi\Crypto\Exception\CryptoException;
use Civi\Api4\Generic\Result;
use CRM_Core_Exception;
use Civi;
use Civi\Api4\User;
use Civi\Api4\Generic\AbstractAction;

/**
 * This is designed to be a public API
 */
class PasswordReset extends AbstractAction {

  /**
   * Scope identifier for password reset JWTs
   */
  const PASSWORD_RESET_SCOPE = 'pw_reset';

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
      throw new CRM_Core_Exception("Invalid password");
    }

    // todo: some minimum password quality check?

    // Only valid change here is password, for a known ID.
    $userID = self::checkPasswordResetToken($this->token);
    if (!$userID) {
      throw new CRM_Core_Exception("Invalid token.");
    }

    User::update(FALSE)
      ->addWhere('id', '=', $userID)
      ->addValue('password', $this->password)
      ->execute();

    $result['success'] = 1;
    \Civi::log()->info("Changed password for user {userID} via User.PasswordReset", compact('userID'));
  }

  /**
   * Generate and store a token on the User record.
   * @internal (only public for use in SecurityTest)
   *
   * @param int $userID
   * @param int $minutes the number of minutes the token should be valid for
   *
   * @return string
   *   The token
   */
  public static function updateToken(int $userID, int $minutes = 60): string {
    // Generate a JWT that expires in 1 hour.
    // We'll store this on the User record, that way invalidating any previous token that may have been generated.
    $expires = time() + 60 * $minutes;
    $token = \Civi::service('crypto.jwt')->encode([
      'exp' => $expires,
      'sub' => "uid:$userID",
      'scope' => self::PASSWORD_RESET_SCOPE,
    ]);
    User::update(FALSE)
      ->addValue('password_reset_token', $token)
      ->addWhere('id', '=', $userID)
      ->execute();

    return $token;
  }

  /**
   * Check a password reset token matches for a User.
   *
   * @param string $token
   * @param bool $spend
   *   If TRUE, and the token matches, the token is then reset; so it can only be used once.
   *   If FALSE no changes are made.
   *
   * @return NULL|int
   *   If int, it's the UserID
   *
   */
  public static function checkPasswordResetToken(string $token, bool $spend = TRUE): ?int {
    try {
      $decodedToken = \Civi::service('crypto.jwt')->decode($token);
    }
    catch (CryptoException $e) {
      Civi::log()->warning('Exception while decoding JWT', ['exception' => $e]);
      return NULL;
    }

    $scope = $decodedToken['scope'] ?? '';
    if ($scope != self::PASSWORD_RESET_SCOPE) {
      Civi::log()->warning('Expected JWT password reset, got ' . $scope);
      return NULL;
    }

    if (empty($decodedToken['sub']) || substr($decodedToken['sub'], 0, 4) !== 'uid:') {
      Civi::log()->warning('Missing uid in JWT sub field');
      return NULL;
    }
    else {
      $userID = (int) substr($decodedToken['sub'], 4);
    }
    if (!$userID > 0) {
      // Hacker
      Civi::log()->warning("Rejected passwordResetToken with invalid userID.", compact('token', 'userID'));
      return NULL;
    }

    $matched = User::get(FALSE)
      ->addWhere('id', '=', $userID)
      ->addWhere('password_reset_token', '=', $token)
      ->addWhere('is_active', '=', 1)
      ->selectRowCount()
      ->execute()->countMatched() === 1;

    if ($matched && $spend) {
      $matched = User::update(FALSE)
        ->addWhere('id', '=', $userID)
        ->addValue('password_reset_token', NULL)
        ->execute();
    }
    Civi::log()->info(($matched ? 'Accepted' : 'Rejected') . " passwordResetToken for user $userID");
    return $matched ? $userID : NULL;
  }

}
