<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

use Civi\Api4\StatusPreference;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
abstract class CRM_Utils_Check_Component {

  /**
   * @var array
   */
  public $checksConfig = [];

  /**
   * Get the configured status checks.
   *
   * @return array
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getChecksConfig() {
    if (empty($this->checksConfig)) {
      $this->checksConfig = Civi::cache('checks')->get('checksConfig', []);
      if (empty($this->checksConfig)) {
        $this->checksConfig = StatusPreference::get()->setCheckPermissions(FALSE)->execute()->indexBy('name');
      }
    }
    return $this->checksConfig;
  }

  /**
   * @param array $checksConfig
   */
  public function setChecksConfig(array $checksConfig) {
    $this->checksConfig = $checksConfig;
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
   * Run all checks in this class.
   *
   * @return array
   *   [CRM_Utils_Check_Message]
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function checkAll() {
    $messages = [];
    foreach (get_class_methods($this) as $method) {
      // Note that we should check if the test is disabled BEFORE running it in case it's disabled for performance.
      if ($method !== 'checkAll' && strpos($method, 'check') === 0 && !$this->isDisabled($method)) {
        $messages = array_merge($messages, $this->$method());
      }
    }
    return $messages;
  }

  /**
   * Is the specified check disabled.
   *
   * @param string $method
   *
   * @return bool
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function isDisabled($method) {
    try {
      $checks = $this->getChecksConfig();
      if (!empty($checks[$method])) {
        return (bool) empty($checks[$method]['is_active']);
      }
    }
    catch (PEAR_Exception $e) {
      // if we're hitting this, DB migration to 5.19 probably hasn't run yet, so
      // is_active doesn't exist. Ignore this error so the status check (which
      // might warn about missing migrations!) still renders.
      // TODO: remove at some point after 5.19
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
      $guzzleResponse = $guzzleClient->request('GET', $url, array(
        'timeout' => $timeoutOverride,
      ));
      $fileExists = ($guzzleResponse->getStatusCode() == 200);
    }
    catch (Exception $e) {
      // At this stage we are not checking for variants of not being able to receive it.
      // However, we might later enhance this to distinguish forbidden from a 500 error.
    }
    return $fileExists;
  }

}
