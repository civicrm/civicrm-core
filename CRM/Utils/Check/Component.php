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

use Civi\Api4\StatusPreference;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
abstract class CRM_Utils_Check_Component {

  /**
   * Get the configured status checks.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getChecksConfig() {
    if (!isset(Civi::$statics[__FUNCTION__])) {
      Civi::$statics[__FUNCTION__] = (array) StatusPreference::get(FALSE)
        ->addWhere('domain_id', '=', 'current_domain')
        ->execute()->indexBy('name');
    }
    return Civi::$statics[__FUNCTION__];
  }

  /**
   * Should these checks be run?
   *
   * @return bool
   */
  public function isEnabled() {
    return TRUE;
  }

  /**
   * Get the names of all check functions in this class
   *
   * @return string[]
   */
  public function getAllChecks() {
    return array_filter(get_class_methods($this), function($method) {
      return $method !== 'checkAll' && str_starts_with($method, 'check');
    });
  }

  /**
   * Run all checks in this class.
   *
   * @param array $requestedChecks
   *   Optionally specify the names of specific checks requested, or leave empty to run all
   * @param bool $includeDisabled
   *   Run checks that have been explicitly disabled (default false)
   *
   * @return CRM_Utils_Check_Message[]
   *
   * @throws CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function checkAll($requestedChecks = [], $includeDisabled = FALSE) {
    $messages = [];
    foreach ($this->getAllChecks() as $method) {
      // Note that we should check if the test is disabled BEFORE running it in case it's disabled for performance.
      if ($this->isRequested($method, $requestedChecks) && ($includeDisabled || !$this->isDisabled($method))) {
        $messages = array_merge($messages, $this->$method($includeDisabled));
      }
    }
    return $messages;
  }

  /**
   * Is this check one of those requested
   *
   * @param string $method
   * @param array $requestedChecks
   * @return bool
   */
  private function isRequested($method, $requestedChecks) {
    if (!$requestedChecks) {
      return TRUE;
    }
    foreach ($requestedChecks as $name) {
      if (str_starts_with($name, $method)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Is the specified check disabled.
   *
   * @param string $method
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function isDisabled($method) {
    $checks = $this->getChecksConfig();
    if (isset($checks[$method]['is_active'])) {
      return !$checks[$method]['is_active'];
    }
    return FALSE;
  }

  /**
   * Check if file exists on given URL.
   *
   * @param string $url
   * @param float|bool $timeoutOverride
   *
   * @return bool
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function fileExists($url, $timeoutOverride = FALSE) {
    // Timeout past in maybe 0 in which case we should still permit it (0 is infinite).
    if (!$timeoutOverride && $timeoutOverride !== 0) {
      $timeoutOverride = (float) Civi::settings()->get('http_timeout');
    }
    $fileExists = FALSE;
    try {
      $guzzleClient = new GuzzleHttp\Client();
      $guzzleResponse = $guzzleClient->request('GET', $url, [
        'timeout' => $timeoutOverride,
      ]);
      $fileExists = ($guzzleResponse->getStatusCode() == 200);
    }
    catch (Exception $e) {
      // At this stage we are not checking for variants of not being able to receive it.
      // However, we might later enhance this to distinguish forbidden from a 500 error.
    }
    return $fileExists;
  }

}
