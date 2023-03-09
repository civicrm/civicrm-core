<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Core\Exception;

/**
 * Error when the syntax of a DB query is incorrect.
 *
 * @param string $message
 *   The human friendly error message.
 * @param string $error_code
 *   A computer friendly error code. By convention, no space (but underscore allowed).
 *   ex: mandatory_missing, duplicate, invalid_format
 * @param array $data
 *   - exception The original PEAR Exception
 */
class DBQueryException extends \CRM_Core_Exception {

  /**
   * Get a message suitable to be presented to the user.
   *
   * @return string
   */
  public function getUserMessage(): string {
    return ts('Invalid Query') . ' ' . $this->getDBErrorMessage() . $this->getErrorCodeSpecificMessage();
  }

  /**
   * Is the error message safe to show to users.
   *
   * Probably most of them are but error 1146 leaks the database name - eg.
   * 'table dmaster.civicrm_contact does not exist'.
   *
   * However, for missing fields and syntax errors the error message gives
   * useful clues we should pass on. We can add to this / tweak over time - if
   * we care to.
   *
   * @return bool
   */
  private function isErrorMessageUserSafe(): bool {
    $errors = [
      // No such field, does not leak any table information.
      1054 => TRUE,
      // Invalid schema - helpful hint as to where the error is, no leakage.
      1064 => TRUE,
      // No such table - leaks db name.
      1146 => FALSE,
    ];
    return $errors[$this->getSQLErrorCode()] ?? FALSE;
  }

  /**
   * @return int
   */
  protected function getPEARErrorCode(): int {
    return $this->getDBError()->getCode();
  }

  /**
   * @return \DB_Error
   */
  protected function getDBError(): \DB_Error {
    return $this->getErrorData()['exception'];
  }

  /**
   * Get the mysql error code.
   *
   * @see https://mariadb.com/kb/en/mariadb-error-codes/
   *
   * @return int
   */
  public function getSQLErrorCode(): int {
    $dbErrorMessage = $this->getUserInfo();
    $matches = [];
    preg_match('/\[nativecode=(\d*) /', $dbErrorMessage, $matches);
    return (int) $matches[1];
  }

  /**
   * Get the PEAR data intended to be use useful to the user.
   *
   * @return string
   */
  public function getUserInfo(): string {
    return $this->getCause()->getUserInfo();
  }

  /**
   * Get the attempted sql.
   *
   * @return string
   */
  public function getSQL(): string {
    $dbErrorMessage = $this->getUserInfo();
    $matches = [];
    preg_match('/(.*) \[nativecode=/', $dbErrorMessage, $matches);
    return $matches[1];
  }

  /**
   * Get the attempted sql.
   *
   * @return string
   */
  public function getDebugInfo(): string {
    return $this->getDBError()->getUserInfo();
  }

  /**
   * Get additional user-safe error message information.
   *
   * @return string
   */
  private function getErrorCodeSpecificMessage(): string {
    $matches = [];
    preg_match('/\[nativecode=\d* \*\* (.*)]/', $this->getUserInfo(), $matches);
    if ($this->isErrorMessageUserSafe()) {
      return ' ' . $matches[1];
    }
    // We could return additional info e.g when we log deadlocks we log
    // 'Database deadlock encountered' (1213) or 'Database lock encountered' (1205).
    return '';
  }

  /**
   * Get the DB error code converted to a test code.
   *
   * @return string
   */
  public function getDBErrorMessage(): string {
    return \DB::errorMessage($this->getPEARErrorCode());
  }

}
