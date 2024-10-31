<?php
namespace Civi\Standalone;

use Civi\Crypto\Exception\CryptoException;
use Civi;
use Civi\Api4\User;
use Civi\Api4\MessageTemplate;
use CRM_Standaloneusers_WorkflowMessage_PasswordReset;

/**
 * Security related functions for Standaloneusers.
 *
 * This is closely coupled with CRM_Utils_System_Standalone
 * Many functions there started life here when Standalone
 * was being resurrected.
 *
 * Some of the generic user functions have been moved back to the
 * System class so that they are more permanently available.
 *
 * Things may yet move around in the codebase - particularly if
 * alternative user extensions to Standaloneusers are developed as
 * these would then need to share an interface with the System
 * class
 */
class Security {

  /**
   * Scope identifier for password reset JWTs
   */
  const PASSWORD_RESET_SCOPE = 'pw_reset';

  /**
   * @return Security
   */
  public static function singleton() {
    if (!isset(\Civi::$statics[__METHOD__])) {
      \Civi::$statics[__METHOD__] = new Security();
    }
    return \Civi::$statics[__METHOD__];
  }

  /**
   * CRM_Core_Permission_Standalone::check() delegates here.
   *
   * @param string $permissionName
   *   The permission to check.
   *
   * @param ?int $userID
   *   The User ID (not ContactID) to check. If NULL, current logged in user.
   *
   * @return bool
   *   true if yes, else false
   */
  public function checkPermission(string $permissionName, ?int $userID = NULL) {
    if ($permissionName == \CRM_Core_Permission::ALWAYS_DENY_PERMISSION) {
      return FALSE;
    }
    if ($permissionName == \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION) {
      return TRUE;
    }

    // NULL means the current logged-in user
    $userID = $userID ?? \CRM_Utils_System::getLoggedInUfID();

    // now any falsey userid is equivalent to userID = 0 = anonymous user
    $userID = $userID ?: 0;

    if (!isset(\Civi::$statics[__METHOD__][$userID])) {

      $roleIDs = [];
      if ($userID > 0) {
        $roleIDs = \Civi\Api4\User::get(FALSE)->addWhere('id', '=', $userID)
          ->addSelect('roles')->execute()->first()['roles'];
      }

      $permissionsPerRoleApiCall = \Civi\Api4\Role::get(FALSE)
        ->addSelect('permissions')
        ->addWhere('is_active', '=', TRUE);

      if ($roleIDs) {
        $permissionsPerRoleApiCall->addClause(
          'OR',
          ['id', 'IN', $roleIDs],
          ['name', '=', 'everyone'],
        );
      }
      else {
        $permissionsPerRoleApiCall->addWhere('name', '=', 'everyone');
      }

      // Get and cache an array of permission names for this user.
      $permissions = array_unique(array_merge(...$permissionsPerRoleApiCall->execute()->column('permissions')));
      \Civi::$statics[__METHOD__][$userID] = $permissions;
    }

    // print "Does user $userID have $permissionName? " . (in_array($permissionName, \Civi::$statics[__METHOD__][$userID]) ? 'yes': 'no') . "\n";
    return in_array($permissionName, \Civi::$statics[__METHOD__][$userID]);
  }

  /**
   * High level function to encrypt password using the site-default mechanism.
   */
  public function hashPassword(string $plaintext): string {
    // For now, we just implement D7's but this should be configurable.
    // Sites should be able to move from one password hashing algo to another
    // e.g. if a vulnerability is discovered.
    $algo = new \Civi\Standalone\PasswordAlgorithms\Drupal7();
    return $algo->hashPassword($plaintext);
  }

  /**
   * Standaloneusers implementation of AuthxInterface::checkPassword
   *
   * @return int|NULL
   *   The User id, if check was successful, otherwise NULL
   * @see \Civi\Authx\Standalone
   */
  public function checkPassword(string $username, string $plaintextPassword): ?int {
    $user = \Civi\Api4\User::get(FALSE)
      ->addWhere('username', '=', $username)
      ->addWhere('is_active', '=', TRUE)
      ->addSelect('hashed_password', 'id')
      ->execute()
      ->first();

    if ($user && $this->checkHashedPassword($plaintextPassword, $user['hashed_password'])) {
      return $user['id'];
    }
    return NULL;
  }

  /**
   * Check whether a password matches a hashed version.
   * @return bool
   */
  protected function checkHashedPassword(string $plaintextPassword, string $storedHashedPassword): bool {

    if (preg_match('@^\$S\$[A-Za-z./0-9]{52}$@', $storedHashedPassword)) {
      // Looks like a default D7 password.
      $algo = new \Civi\Standalone\PasswordAlgorithms\Drupal7();
      return $algo->checkPassword($plaintextPassword, $storedHashedPassword);
    }

    if (preg_match('@^\$P\$B[a-zA-Z0-9./]{30}$@', $storedHashedPassword)) {
      Civi::log()->warning("Denying access to user whose password looks like a WordPress one because we haven't coded support for that.");
      return FALSE;
    }

    // See if we can parse it against this spec...
    // One day we might like to support this format because it allows all sorts of hashing algorithms.
    // https://github.com/P-H-C/phc-string-format/blob/master/phc-sf-spec.md
    // $<id>[$v=<version>][$<param>=<value>(,<param>=<value>)*][$<salt>[$<hash>]]
    if (!preg_match('/
      ^
      \$([a-z0-9-]{1,32})  # Match 1 algorithm identifier
      (\$v=[0-9+])?        # Match 2 optional version
      (\$[a-z0-9-]{1,32}=[a-zA-Z0-9\/+.-]*(?:,[a-z0-9-]{1,32}=[a-zA-Z0-9\/+.-]*)*)? # 3: optional parameters
      \$([a-zA-Z0-9\/+.-]+) # Match 4 salt
      \$([a-zA-Z0-9\/+]+)   # Match 5 B64 encoded hash
      $/x', $storedHashedPassword, $matches)) {

      Civi::log()->warning("Denying access to user whose stored password is not in a format we can parse.");
      return FALSE;
    }
    [, $identifier, $version, $params, $salt, $hash] = $matches;

    // Map type to algorithm name. Some common ones here, but we don't implement them all.
    $algo = [
      '1'  => 'md5',
      '5'  => 'sha256_crypt',
      '6'  => 'sha512_crypt',
      '2'  => 'bcrypt',
      '2b' => 'bcrypt',
      '2x' => 'bcrypt',
      '2y' => 'bcrypt',
    ][$identifier] ?? '';

    $version = ltrim($version, '$');
    $parsedParams = [];
    if (!empty($params)) {
      $parsedParams = [];
      foreach (explode(',', (ltrim($params, '$'))) as $kv) {
        [$k, $v] = explode('=', $kv);
        $parsedParams[$k] = $v;
      }
    }
    $params = $parsedParams;

    // salt and hash should be base64 encoded.
    $salt = base64_decode(ltrim($salt, '$'), TRUE);
    $hash = base64_decode(ltrim($hash, '$'), TRUE);

    // @todo
    // Implement a pluggable interface here to handle some of these password types or more.
    Civi::log()->warning("Denying access to user whose stored password relies on '$algo' which we have not implemented yet.");
    return FALSE;
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
  public function checkPasswordResetToken(string $token, bool $spend = TRUE): ?int {
    try {
      $decodedToken = \Civi::service('crypto.jwt')->decode($token);
    }
    catch (CryptoException $e) {
      Civi::log()->warning('Exception while decoding JWT', ['exception' => $e]);
      return NULL;
    }

    $scope = $decodedToken['scope'] ?? '';
    if ($scope != Security::PASSWORD_RESET_SCOPE) {
      Civi::log()->warning('Expected JWT password reset, got ' . $scope);
      return NULL;
    }

    if (empty($decodedToken['sub']) || substr($decodedToken['sub'], 0, 4) !== 'uid:') {
      Civi::log()->warning('Missing uid in JWT sub field');
      return NULL;
    }
    else {
      $userID = substr($decodedToken['sub'], 4);
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

  /**
   * Prepare a password reset workflow email, if configured.
   *
   * @return \CRM_Standaloneusers_WorkflowMessage_PasswordReset|null
   */
  public function preparePasswordResetWorkflow(array $user, string $token): ?CRM_Standaloneusers_WorkflowMessage_PasswordReset {
    // Find the message template
    $tplID = MessageTemplate::get(FALSE)
      ->setSelect(['id'])
      ->addWhere('workflow_name', '=', 'password_reset')
      ->addWhere('is_default', '=', TRUE)
      ->addWhere('is_reserved', '=', FALSE)
      ->addWhere('is_active', '=', TRUE)
      ->execute()->first()['id'];
    if (!$tplID) {
      // Some sites may deliberately disable this, but it's unusual, so leave a notice in the log.
      Civi::log()->notice("There is no active, default password_reset message template, which has prevented emailing a reset to {username}", ['username' => $user['username']]);
      return NULL;
    }
    if (!filter_var($user['uf_name'] ?? '', \FILTER_VALIDATE_EMAIL)) {
      Civi::log()->warning("User $user[id] has an invalid email. Failed to send password reset.");
      return NULL;
    }

    // The template_params are used in the template like {$resetUrlHtml} and {$resetUrlHtml} {$usernamePlaintext} {$usernameHtml}
    [$domainFromName, $domainFromEmail] = \CRM_Core_BAO_Domain::getNameAndEmail(TRUE);
    $workflowMessage = (new \CRM_Standaloneusers_WorkflowMessage_PasswordReset())
      ->setDataFromUser($user, $token)
      ->setFrom("\"$domainFromName\" <$domainFromEmail>");

    return $workflowMessage;
  }

}
