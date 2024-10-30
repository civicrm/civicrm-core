<?php
namespace Civi\Standalone\PasswordAlgorithms;

interface AlgorithmInterface {

  /**
   * Checks if a password matches the stored value
   *
   * @return bool
   */
  public function checkPassword(string $plaintext, string $storedPassword): bool;

  /**
   * Creates a hashed value to store for given password.
   *
   * Responsible for loading any of its configurable settings.
   *
   */
  public function hashPassword(string $plaintext): string;

}
