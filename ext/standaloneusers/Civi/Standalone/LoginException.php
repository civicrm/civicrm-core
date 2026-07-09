<?php
namespace Civi\Standalone;

/**
 * User visible exception when handling standalone's login.
 *
 * Messages should be printable.
 */
class LoginException extends \CRM_Core_Exception {

  public readonly string $publicError;

  public readonly mixed $userID;

  public function __construct(string $publicError, mixed $userID = NULL, $message = NULL, $error_code = 0, $errorData = [], $previous = NULL) {
    $this->publicError = $publicError;
    $this->userID = $userID;
    parent::__construct($message ?? $publicError, $error_code, $errorData, $previous);
  }

}
