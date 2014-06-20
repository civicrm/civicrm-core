<?php

/**
 * This is an evil, evil work-around for CRM-11043. It is used to
 * temporarily change the error-handling behavior and then automatically
 * restore it -- that protocol is an improvement over the current protocol
 * (in which random bits of code will change the global error handler
 * setting and then forget to change it back).  This class and all
 * references to it should be removed in 4.3/4.4 (when we adopt
 * exception-based error handling).
 *
 * To ensure that new errors arising during execution of the current
 * function are immediately fatal, use:
 *
 * To ensure that they throw exceptions, use:
 *
 * @code
 * $errorScope = CRM_Core_TemporaryErrorScope::useException();
 * @endcode
 *
 * Note that relying on this is a code-smell: it can be
 * safe to temporarily switch to exception
 */
class CRM_Core_TemporaryErrorScope {
  static $oldFrames;

  /**
   * @return CRM_Core_TemporaryErrorScope
   */
  public static function useException() {
    return self::create(array('CRM_Core_Error', 'exceptionHandler'), 1);
  }

  /**
   * @return CRM_Core_TemporaryErrorScope
   */
  public static function ignoreException() {
    return self::create(array('CRM_Core_Error', 'nullHandler'));
  }

  /**
   * @param mixed $callback
   * @param null $modeException
   *
   * @return CRM_Core_TemporaryErrorScope
   */
  public static function create($callback, $modeException = NULL) {
    $newFrame = array(
      '_PEAR_default_error_mode' => PEAR_ERROR_CALLBACK,
      '_PEAR_default_error_options' => $callback,
      'modeException' => $modeException,
    );
    return new CRM_Core_TemporaryErrorScope($newFrame);
  }

  /**
   * @param $newFrame
   */
  public function __construct($newFrame) {
    self::$oldFrames[] = self::getActive();
    self::setActive($newFrame);
  }

  public function __destruct() {
    $oldFrame = array_pop(self::$oldFrames);
    self::setActive($oldFrame);
  }

  /**
   * Read the active error-handler settings
   */
  public static function getActive() {
    return array(
      '_PEAR_default_error_mode' => $GLOBALS['_PEAR_default_error_mode'],
      '_PEAR_default_error_options' =>$GLOBALS['_PEAR_default_error_options'],
      'modeException' => CRM_Core_Error::$modeException,
    );
  }

  /**
   * Set the active error-handler settings
   */
  public static function setActive($frame) {
    $GLOBALS['_PEAR_default_error_mode'] = $frame['_PEAR_default_error_mode'];
    $GLOBALS['_PEAR_default_error_options'] = $frame['_PEAR_default_error_options'];
    CRM_Core_Error::$modeException = $frame['modeException'];
  }
}
