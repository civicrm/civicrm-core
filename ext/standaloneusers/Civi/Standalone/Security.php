<?php
namespace Civi\Standalone;

use CRM_Core_Session;

/**
 * This is a single home for security related functions for Civi Standalone.
 *
 * Things may yet move around in the codebase; at the time of writing this helps
 * keep core PRs to a minimum.
 *
 */
class Security {

  public const ITOA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

  public static $minHashCount = 7;
  public static $maxHashCount = 30;
  public static $hashLength = 55;
  public static $hashMethod = 'sha512';

  /**
   * @return static
   */
  public static function singleton() {
    if (!isset(\Civi::$statics[__METHOD__])) {
      \Civi::$statics[__METHOD__] = new static();
    }
    return \Civi::$statics[__METHOD__];
  }

  /**
   * Check whether a password matches a hashed version.
   */
  public function checkPassword(string $plaintextPassword, string $storedHashedPassword): bool {
    $type = substr($storedHashedPassword, 0, 3);
    switch ($type) {
      case '$S$':
        // A normal Drupal 7 password.
        $hash = $this->_password_crypt(static::$hashMethod, $plaintextPassword, $storedHashedPassword);
        break;

      default:
        // Invalid password
        return FALSE;
    }
    return hash_equals($storedHashedPassword, $hash);
  }

  /**
   * CRM_Core_Permission_Standalone::check() delegates here.
   *
   * @param \CRM_Core_Permission_Standalone $permissionObject
   *
   * @param string $permissionName
   *   The permission to check.
   *
   * @param int $userID
   *   It is unclear if this typehint is true: The Drupal version has a default NULL!
   *
   * @return bool
   *   true if yes, else false
   */
  public function checkPermission(\CRM_Core_Permission_Standalone $permissionObject, string $permissionName, $userID) {

    // I think null means the current logged-in user
    $userID = $userID ?? $this->getLoggedInUfID() ?? 0;

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
      ->single()['id'] ?? NULL;
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
   * @param string $mailParam
   *   Name of the $param which contains the email address.
   *
   * @return int|bool
   *   uid if user was created, false otherwise
   */
  public function createUser(&$params, $mailParam) {
    try {
      // Q. should this be in the api for User.create?
      $hashedPassword = $this->_password_crypt(static::$hashMethod, $params['cms_pass'], $this->_password_generate_salt());
      $mail = $params[$mailParam];

      $userID = \Civi\Api4\User::create(FALSE)
        ->addValue('username', $params['cms_name'])
        ->addValue('email', $mail)
        ->addValue('password', $hashedPassword)
        ->execute()->single()['id'];
    }
    catch (\Exception $e) {
      \Civi::log()->warning("Failed to create user '$mail': " . $e->getMessage());
      return FALSE;
    }

    // @todo This is what Drupal does, but it's unclear why.
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
      ->addValue('email', $email)
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

    // Note: random_int is more appropriate for cryptographical use than mt_rand
    // The long number is the max 32 bit value.
    return [$user['contact_id'], $user['id'], random_int(0, 2147483647)];
  }

  /**
   * Currently only used by CRM_Utils_System_Standalone::loadBootstrap
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
    }
  }

  /**
   * This is the (perhaps temporary location for) the implementation of CRM_Utils_System_Standalone method.
   */
  public function isUserLoggedIn(): bool {
    return !empty($this->getLoggedInUfID());
  }

  public function getCurrentLanguage() {
    // @todo
    \Civi::log()->debug('CRM_Utils_System_Standalone::getCurrentLanguage: not implemented');
    return NULL;
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
   * Since our User entity contains a FK to a contact, it's not possible for a User to exist without a contact.
   *
   * @todo review this (what if contact is deleted?)
   */
  public function synchronizeUsers() {

    $userCount = \Civi\Api4\User::get(FALSE)->selectRowCount()->execute()->countMatched();
    return [
      'contactCount' => $userCount,
      'contactMatching' => $userCount,
      'contactCreated' => 0,
    ];
  }

  /**
   * This is taken from Drupal 7.91
   *
   * Hash a password using a secure stretched hash.
   *
   * By using a salt and repeated hashing the password is "stretched". Its
   * security is increased because it becomes much more computationally costly
   * for an attacker to try to break the hash by brute-force computation of the
   * hashes of a large number of plain-text words or strings to find a match.
   *
   * @param $algo
   *   The string name of a hashing algorithm usable by hash(), like 'sha256'.
   * @param $password
   *   Plain-text password up to 512 bytes (128 to 512 UTF-8 characters) to hash.
   * @param $setting
   *   An existing hash or the output of _password_generate_salt().  Must be
   *   at least 12 characters (the settings and salt).
   *
   * @return string|bool
   *   A string containing the hashed password (and salt) or FALSE on failure.
   *   The return string will be truncated at DRUPAL_HASH_LENGTH characters max.
   */
  public function _password_crypt($algo, $password, $setting) {
    // Prevent DoS attacks by refusing to hash large passwords.
    if (strlen($password) > 512) {
      return FALSE;
    }
    // The first 12 characters of an existing hash are its setting string.
    $setting = substr($setting, 0, 12);

    if ($setting[0] != '$' || $setting[2] != '$') {
      return FALSE;
    }

    $count_log2 = strpos(self::ITOA64, $setting[3]);

    // Hashes may be imported from elsewhere, so we allow != DRUPAL_HASH_COUNT
    if ($count_log2 < self::$minHashCount || $count_log2 > self::$maxHashCount) {
      return FALSE;
    }
    $salt = substr($setting, 4, 8);
    // Hashes must have an 8 character salt.
    if (strlen($salt) != 8) {
      return FALSE;
    }

    // Convert the base 2 logarithm into an integer.
    $count = 1 << $count_log2;
    $hash = hash($algo, $password, TRUE);
    do {
      $hash = hash($algo, $hash . $password, TRUE);
    } while (--$count);

    $len = strlen($hash);
    $output = $setting . $this->_password_base64_encode($hash, $len);
    // _password_base64_encode() of a 16 byte MD5 will always be 22 characters.
    // _password_base64_encode() of a 64 byte sha512 will always be 86 characters.
    $expected = 12 + ceil((8 * $len) / 6);
    return (strlen($output) == $expected) ? substr($output, 0, self::$hashLength) : FALSE;
  }

  /**
   * This is taken from Drupal 7.91
   *
   * Generates a random base 64-encoded salt prefixed with settings for the hash.
   *
   * Proper use of salts may defeat a number of attacks, including:
   *  - The ability to try candidate passwords against multiple hashes at once.
   *  - The ability to use pre-hashed lists of candidate passwords.
   *  - The ability to determine whether two users have the same (or different)
   *    password without actually having to guess one of the passwords.
   *
   * @param $count_log2
   *   Integer that determines the number of iterations used in the hashing
   *   process. A larger value is more secure, but takes more time to complete.
   *
   * @return string
   *   A 12 character string containing the iteration count and a random salt.
   */
  public function _password_generate_salt($count_log2 = NULL): string {

    // Standalone: D7 has this stored as a CMS variable setting.
    // @todo use global setting that can be changed in civicrm.settings.php
    // For now, we just pick a value half way between our hard-coded min and max.
    if ($count_log2 === NULL) {
      $count_log2 = (int) ((static::$maxHashCount + static::$minHashCount) / 2);
    }
    $output = '$S$';
    // Ensure that $count_log2 is within set bounds.
    $count_log2 = max(static::$minHashCount, min(static::$maxHashCount, $count_log2));
    // We encode the final log2 iteration count in base 64.
    $output .= self::ITOA64[$count_log2];
    // 6 bytes is the standard salt for a portable phpass hash.
    $output .= $this->_password_base64_encode(random_bytes(6), 6);
    return $output;
  }

  /**
   * This is taken from Drupal 7.91
   *
   * Encodes bytes into printable base 64 using the *nix standard from crypt().
   *
   * @param $input
   *   The string containing bytes to encode.
   * @param $count
   *   The number of characters (bytes) to encode.
   *
   * @return string
   *   Encoded string
   */
  public function _password_base64_encode($input, $count): string {
    $output = '';
    $i = 0;
    $itoa64 = self::ITOA64;
    do {
      $value = ord($input[$i++]);
      $output .= $itoa64[$value & 0x3f];
      if ($i < $count) {
        $value |= ord($input[$i]) << 8;
      }
      $output .= $itoa64[($value >> 6) & 0x3f];
      if ($i++ >= $count) {
        break;
      }
      if ($i < $count) {
        $value |= ord($input[$i]) << 16;
      }
      $output .= $itoa64[($value >> 12) & 0x3f];
      if ($i++ >= $count) {
        break;
      }
      $output .= $itoa64[($value >> 18) & 0x3f];
    } while ($i < $count);

    return $output;
  }

}
