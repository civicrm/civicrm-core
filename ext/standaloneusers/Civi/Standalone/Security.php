<?php
namespace Civi\Standalone;

use Civi\Crypto\Exception\CryptoException;
use CRM_Core_Session;
use Civi;
use Civi\Api4\User;
use Civi\Api4\MessageTemplate;
use CRM_Standaloneusers_WorkflowMessage_PasswordReset;

/**
 * This is a single home for security related functions for Civi Standalone.
 *
 * Things may yet move around in the codebase; at the time of writing this helps
 * keep core PRs to a minimum.
 *
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
    $userID ??= $this->getLoggedInUfID() ?? 0;

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
   */
  public function getUserIDFromUsername(string $username): ?int {
    return \Civi\Api4\User::get(FALSE)
      ->addWhere('username', '=', $username)
      ->execute()
      ->first()['id'] ?? NULL;
  }

  /**
   * Load an active user by username.
   *
   * @return array|bool FALSE if not found.
   */
  public function loadUserByName(string $username) {
    $user = \Civi\Api4\User::get(FALSE)
      ->addWhere('username', '=', $username)
      ->addWhere('is_active', '=', TRUE)
      ->execute()->first() ?? [];
    if ($user) {
      return $user;
    }
    return FALSE;
  }

  /**
   * Load an active user by internal user ID.
   *
   * @return array|bool FALSE if not found.
   */
  public function loadUserByID(int $userID) {
    $user = \Civi\Api4\User::get(FALSE)
      ->addWhere('id', '=', $userID)
      ->addWhere('is_active', '=', TRUE)
      ->execute()->first() ?? [];
    if ($user) {
      return $user;
    }
    return FALSE;
  }

  /**
   *
   */
  public function logoutUser() {
    // This is the same call as in CRM_Authx_Page_AJAX::logout()
    _authx_uf()->logoutSession();
  }

  /**
   * Create a user in the CMS.
   *
   * This is the (perhaps temporary location for) the implementation of CRM_Utils_System_Standalone method.
   *
   * @param array $params keys:
   *    - 'cms_name'
   *    - 'cms_pass' plaintext password
   *    - 'notify' boolean
   * @param string $emailParam
   *   Name of the $param which contains the email address.
   *
   * @return int|bool
   *   uid if user was created, false otherwise
   */
  public function createUser(&$params, $emailParam) {
    try {
      $email = $params[$emailParam];
      $userID = User::create(FALSE)
        ->addValue('username', $params['cms_name'])
        ->addValue('uf_name', $email)
        ->addValue('password', $params['cms_pass'])
        ->addValue('contact_id', $params['contact_id'] ?? NULL)
        // ->addValue('uf_id', 0) // does not work without this.
        ->execute()->single()['id'];
    }
    catch (\Exception $e) {
      \Civi::log()->warning("Failed to create user '$email': " . $e->getMessage());
      return FALSE;
    }

    // @todo This next line is what Drupal does, but it's unclear why.
    // I think it assumes we want to be logged in as this contact, and as there's no uf match, it's not in civi.
    // But I'm not sure if we are always becomming this user; I'm not sure waht calls this function.
    // CRM_Core_Config::singleton()->inCiviCRM = FALSE;

    return (int) $userID;
  }

  /**
   * Update a user's email
   *
   * This is the (perhaps temporary location for) the implementation of CRM_Utils_System_Standalone method.
   */
  public function updateCMSName($ufID, $email) {
    \Civi\Api4\User::update(FALSE)
      ->addWhere('id', '=', $ufID)
      ->addValue('uf_name', $email)
      ->execute();
  }

  /**
   * Authenticate the user against the CMS db.
   *
   * This is the (perhaps temporary location for) the implementation of CRM_Utils_System_Standalone method.
   *
   * @param string $name
   *   The user name.
   * @param string $password
   *   The password for the above user.
   * @param bool $loadCMSBootstrap
   *   Load cms bootstrap?.
   * @param string $realPath
   *   Filename of script
   *
   * @return array|bool
   *   [contactID, ufID, unique string] else false if no auth
   * @throws \CRM_Core_Exception.
   */
  public function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {

    // this comment + session lines: copied from Drupal's implementation in case it's important...
    /* Before we do any loading, let's start the session and write to it.
     * We typically call authenticate only when we need to bootstrap the CMS
     * directly via Civi and hence bypass the normal CMS auth and bootstrap
     * process typically done in CLI and cron scripts. See: CRM-12648
     */
    $session = CRM_Core_Session::singleton();
    $session->set('civicrmInitSession', TRUE);

    $user = $this->loadUserByName($name);

    if (!$this->checkPassword($password, $user['password'] ?? '')) {
      return FALSE;
    }

    $this->applyLocaleFromUser($user);

    // Note: random_int is more appropriate for cryptographical use than mt_rand
    // The long number is the max 32 bit value.
    return [$user['contact_id'], $user['id'], random_int(0, 2147483647)];
  }

  /**
   * Register the given user as the currently logged in user.
   */
  public function loginAuthenticatedUserRecord(array $user, bool $withSession) {
    global $loggedInUserId, $loggedInUser;
    $loggedInUserId = $user['id'];
    $loggedInUser = $user;

    if ($withSession) {
      $session = \CRM_Core_Session::singleton();
      $session->set('ufID', $user['id']);

      // Identify the contact
      $contactID = civicrm_api3('UFMatch', 'get', [
        'sequential' => 1,
        'return' => ['contact_id'],
        'uf_id' => $user['id'],
      ])['values'][0]['contact_id'] ?? NULL;
      // Confusingly, Civi stores it's *Contact* ID as *userID* on the session.
      $session->set('userID', $contactID);
      $this->applyLocaleFromUser($user);
    }
  }

  /**
   * This is the (perhaps temporary location for) the implementation of CRM_Utils_System_Standalone method.
   */
  public function isUserLoggedIn(): bool {
    return !empty($this->getLoggedInUfID());
  }

  /**
   * This is the (perhaps temporary location for) the implementation of CRM_Utils_System_Standalone method.
   */
  public function getLoggedInUfID(): ?int {
    $authX = new \Civi\Authx\Standalone();
    return $authX->getCurrentUserId();
  }

  /**
   * This is the (perhaps temporary location for) the implementation of CRM_Utils_System_Standalone method.
   */
  public function languageNegotiationURL($url, $addLanguagePart = TRUE, $removeLanguagePart = FALSE) {
    // @todo
    return $url;
  }

  /**
   * This is the (perhaps temporary location for) the implementation of CRM_Utils_System_Standalone method.
   * Return the CMS-specific url for its permissions page
   * @return array
   */
  public function getCMSPermissionsUrlParams() {
    return ['ufAccessURL' => '/civicrm/admin/roles'];
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
   * Check whether a password matches a hashed version.
   */
  public function checkPassword(string $plaintextPassword, string $storedHashedPassword): bool {

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

  /**
   * Applies the locale from the user record.
   *
   * @param array $user
   * @return void
   */
  private function applyLocaleFromUser(array $user) {
    $session = CRM_Core_Session::singleton();
    if (!empty($user['language'])) {
      $session->set('lcMessages', $user['language']);
    }
  }

}
