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

namespace Civi\Schema\Traits;

use Civi\Api4\Utils\ReflectionUtils;
use Civi\Core\Resolver;

/**
 * Add validation callbacks to a field.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
trait ValidateCallbackTrait {

  /**
   * @return string
   */
  abstract public function getName();

  /**
   * @var string|null
   */
  public $validateCallback;

  /**
   * @return string|null
   */
  public function getValidateCallback() {
    return $this->validateCallback;
  }

  /**
   * @param string|null $validateCallback
   * @return $this
   */
  public function setValidateCallback($validateCallback) {
    $this->validateCallback = $validateCallback;
    return $this;
  }

  /**
   * Execute the validation callback.
   *
   * @param mixed $value
   * @return array
   *   List of error messages.
   */
  public function validateCallback($value) {
    if ($this->validateCallback === NULL) {
      return [];
    }
    $callback = Resolver::singleton()->get($this->validateCallback);
    $result = $callback($value);
    if ($result === TRUE) {
      return [];
    }

    // Some kind of error. These are the minimal fields.
    $baseError = [
      'severity' => 'error',
      'fields' => [$this->getName()],
      'name' => $this->validateCallback,
      'message' => NULL,
    ];

    if (is_array($result)) {
      // Make sure the returned error records are complete.
      foreach ($result as &$resultItem) {
        $resultItem = array_merge($baseError, $resultItem);
      }
      return $result;
    }

    if ($result === FALSE) {
      // Build an error record...
      $error = $baseError;
      $error['message'] = ts('Field did not pass validator (%1)', [1 => $this->validateCallback]);
      if (is_array($callback)) {
        // Maybe the callback has a docblock with some messaging...
        try {
          $class = is_string($callback[0]) ? $callback[0] : get_class($callback[0]);
          $clazz = new \ReflectionClass($class);
          $parsed = ReflectionUtils::getCodeDocs($clazz->getMethod($callback[1]));
          $error['message'] = $parsed['errorMessage'] ?? $parsed['description'] ?? $error['message'];
        }
        catch (\ReflectionException $e) {
          // Too bad.
        }
      }
      return [$error];
    }

    throw new \RuntimeException(sprintf("Validation callback returned invalid result (%s).", gettype($result)));
  }

}
