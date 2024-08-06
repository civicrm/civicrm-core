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

/**
 * Exception thrown during tests where live code would exit.
 *
 * This is when the code would exit in live mode.
 *
 * @param string $message
 *   The human friendly error message.
 * @param string $error_code
 *   A computer friendly error code. By convention, no space (but underscore allowed).
 *   ex: mandatory_missing, duplicate, invalid_format
 * @param array $data
 *   Extra params to return. eg an extra array of ids. It is not mandatory, but can help the computer using the api.
 * Keep in mind the api consumer isn't to be trusted. eg. the database password is NOT a good extra data.
 */
class CRM_Core_Exception_PrematureExitException extends RuntimeException {

  /**
   * Contextual data.
   *
   * @var array
   */
  public $errorData;

  /**
   * Construct the exception. Note: The message is NOT binary safe.
   *
   * @link https://php.net/manual/en/exception.construct.php
   *
   * @param string $message [optional] The Exception message to throw.
   * @param array $errorData
   * @param int $error_code
   * @param Throwable|null $previous [optional] The previous throwable used for the exception chaining.
   */
  public function __construct($message = "", $errorData = [], $error_code = 0, ?Throwable $previous = NULL) {
    parent::__construct($message, $error_code, $previous);
    $this->errorData = $errorData + ['error_code' => $error_code];
  }

}
