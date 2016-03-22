<?php
/**
 * @file
 * File for the CiviCRM APIv3 API wrapper
 *
 * @package CiviCRM_APIv3
 *
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This api exception returns more information than the default one. The aim
 * it let the api consumer know better what is exactly the error without
 * having to parse the error message.
 *
 * If you consume an api that doesn't return an error_code or the extra data
 * you need, consider improving the api and contribute.
 */
class API_Exception extends Exception {
  const UNAUTHORIZED = 'unauthorized';
  const NOT_IMPLEMENTED = 'not-found';

  private $extraParams = array();

  /**
   * Class constructor.
   *
   * @param string $message
   *   The human friendly error message.
   * @param mixed $error_code
   *   A computer friendly error code. By convention, no space (but underscore
   *   allowed) (ex: mandatory_missing, duplicate, invalid_format).
   * @param array $extraParams
   *   Extra params to return. eg an extra array of ids. It is not mandatory,
   *   but can help the computer using the api. Keep in mind the api consumer
   *   isn't to be trusted. eg. the database password is NOT a good extra data.
   * @param Exception|NULL $previous
   *   A previous exception which caused this new exception.
   */
  public function __construct($message, $error_code = 0, $extraParams = array(), Exception $previous = NULL) {
    // Using int for error code "old way") ?
    if (is_numeric($error_code)) {
      $code = $error_code;
    }
    else {
      $code = 0;
    }
    parent::__construct(ts($message), $code, $previous);
    $this->extraParams = $extraParams + array('error_code' => $error_code);
  }

  /**
   * Custom string representation of object.
   *
   * @return string
   */
  public function __toString() {
    return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
  }

  /**
   * Get extra parameters.
   *
   * @return array
   */
  public function getExtraParams() {
    return $this->extraParams;
  }

  /**
   * Get error codes.
   *
   * @return array
   */
  public function getErrorCodes() {
    return array(
      2000 => '$params was not an array',
      2001 => 'Invalid Value for Date field',
      2100 => 'String value is longer than permitted length',
      self::UNAUTHORIZED => 'Unauthorized',
      self::NOT_IMPLEMENTED => 'Entity or method is not implemented',
    );
  }

}

/**
 * This api exception returns more information than the default one. We are using it rather than
 * API_Exception from the api wrapper as the namespace is more generic
 */
class CiviCRM_API3_Exception extends Exception {
  private $extraParams = array();

  /**
   * Class constructor.
   *
   * @param string $message
   *   The human friendly error message.
   * @param mixed $error_code
   *   A computer friendly error code. By convention, no space (but underscore
   *   allowed) (ex: mandatory_missing, duplicate, invalid_format).
   * @param array $extraParams
   *   Extra params to return. eg an extra array of ids. It is not mandatory,
   *   but can help the computer using the api. Keep in mind the api consumer
   *   isn't to be trusted. eg. the database password is NOT a good extra data.
   * @param Exception|NULL $previous
   *   A previous exception which caused this new exception.
   */
  public function __construct($message, $error_code, $extraParams = array(), Exception $previous = NULL) {
    parent::__construct(ts($message));
    $this->extraParams = $extraParams + array('error_code' => $error_code);
  }

  /**
   * Custom string representation of object.
   *
   * @return string
   */
  public function __toString() {
    return __CLASS__ . ": [{$this->extraParams['error_code']}: {$this->message}\n";
  }

  /**
   * Get error code.
   *
   * @return mixed
   */
  public function getErrorCode() {
    return $this->extraParams['error_code'];
  }

  /**
   * Get extra parameters.
   *
   * @return array
   */
  public function getExtraParams() {
    return $this->extraParams;
  }

}
