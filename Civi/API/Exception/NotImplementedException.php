<?php
namespace Civi\API\Exception;

require_once 'api/Exception.php';

/**
 * Class NotImplementedException
 * @package Civi\API\Exception
 */
class NotImplementedException extends \API_Exception {
  /**
   * @param string $message
   * @param array $extraParams
   * @param Exception $previous
   */
  public function __construct($message, $extraParams = array(), Exception $previous = NULL) {
    parent::__construct($message, \API_Exception::NOT_IMPLEMENTED, $extraParams, $previous);
  }
}
