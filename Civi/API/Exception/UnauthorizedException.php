<?php
namespace Civi\API\Exception;

/**
 * Class UnauthorizedException
 * @package Civi\API\Exception
 */
class UnauthorizedException extends \CRM_Core_Exception {

  /**
   * @param string $message
   *   The human friendly error message.
   * @param array $extraParams
   *   Extra params to return. eg an extra array of ids. It is not mandatory,
   *   but can help the computer using the api. Keep in mind the api consumer
   *   isn't to be trusted. eg. the database password is NOT a good extra data.
   * @param \Exception|null $previous
   *   A previous exception which caused this new exception.
   */
  public function __construct($message, $extraParams = [], ?\Exception $previous = NULL) {
    parent::__construct($message, \CRM_Core_Exception::UNAUTHORIZED, $extraParams, $previous);
  }

}
