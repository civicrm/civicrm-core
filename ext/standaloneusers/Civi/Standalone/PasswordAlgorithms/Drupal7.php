<?php
namespace Civi\Standalone\PasswordAlgorithms;

class Drupal7 implements AlgorithmInterface {

  public const ITOA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

  /**
   * @var string
   */
  public static $defaultAlgo = 'sha512';

  /**
   * @var int
   */
  public static $minHashCount = 7;

  /**
   * @var int
   */
  public static $maxHashCount = 30;

  /**
   * @var int
   */
  public static $hashLength = 55;

  /**
   * Checks if a password matches the stored value
   *
   * @return bool
   */
  public function checkPassword(string $plaintext, string $storedPassword): bool {
    return hash_equals($storedPassword, $this->_d7_password_crypt('sha512', $plaintext, $storedPassword));
  }

  /**
   * Creates a hashed value to store for given password.
   *
   * Responsible for loading any of its configurable settings.
   *
   */
  public function hashPassword(string $plaintext): string {
    return $this->_d7_password_crypt('sha512', $plaintext, $this->_d7_password_generate_salt());
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
  public function _d7_password_base64_encode($input, $count): string {
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
  public function _d7_password_generate_salt($count_log2 = NULL): string {

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
    $output .= $this->_d7_password_base64_encode(random_bytes(6), 6);
    return $output;
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
   *   An existing hash or the output of _d7_password_generate_salt().  Must be
   *   at least 12 characters (the settings and salt).
   *
   * @return string|bool
   *   A string containing the hashed password (and salt) or FALSE on failure.
   *   The return string will be truncated at DRUPAL_HASH_LENGTH characters max.
   */
  protected function _d7_password_crypt($algo, $password, $setting) {
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
    $hash = hash($algo, $salt . $password, TRUE);
    do {
      $hash = hash($algo, $hash . $password, TRUE);
    } while (--$count);

    $len = strlen($hash);
    $output = $setting . $this->_d7_password_base64_encode($hash, $len);
    // _d7_password_base64_encode() of a 16 byte MD5 will always be 22 characters.
    // _d7_password_base64_encode() of a 64 byte sha512 will always be 86 characters.
    $expected = 12 + ceil((8 * $len) / 6);
    return (strlen($output) == $expected) ? substr($output, 0, self::$hashLength) : FALSE;
  }

}
