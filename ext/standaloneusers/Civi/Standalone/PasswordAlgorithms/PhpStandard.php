<?php
namespace Civi\Standalone\PasswordAlgorithms;

class PhpStandard implements AlgorithmInterface {

  /**
   * @var int
   */
  public static $bcryptCost = 13;

  /**
   * Checks if a password matches the stored value
   *
   * @todo this will allow any password hashing password_verify supports.
   * We should have a mechanism for re-encrypting if it is below our current
   * standards (or rejecting if we are super strict)
   *
   * @return bool
   */
  public function checkPassword(string $plaintext, string $storedPassword): bool {
    return \password_verify($plaintext, $storedPassword);
  }

  /**
   * Creates a hashed value to store for given password.
   */
  public function hashPassword(string $plaintext): string {
    return \password_hash($plaintext, \PASSWORD_BCRYPT, [
      'cost' => self::$bcryptCost,
    ]);
  }

}
