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

namespace Civi\Cors;

use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides CORS (Cross-Origin Resource Sharing) support for CiviCRM URLs.
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/cors/
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS/
 * @service cors
 */
class Cors extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'civi.invoke.auth' => ['addHeaders', 10],
    ];
  }

  /**
   * Add headers to responses for configured URLs.
   *
   * Note that we are not attempting to configure the following headers:
   *
   * - Access-Control-Expose-Headers
   * - Access-Control-Allow-Credentials
   *
   * Feel free to create an issue or submit a PR if you think we should be.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @return void
   */
  public function addHeaders($e) {
    $rule = $this->matchRule($e->args);

    // If a rule matches, set appropriate headers.
    if ($rule) {
      $this->setAllowOrigin($rule->origins);
      $this->setAllowHeaders($rule->headers);
      $this->setAllowMethods($rule->methods);
      $this->setMaxAge();

      // Respond to all OPTIONS requests with an 204.
      if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        // There is no need for any further processing.
        \CRM_Utils_System::civiExit();
      }
    }
  }

  /**
   * See if we can match path arguments against a CORS rule.
   *
   * Note: fnmatch() is 'not available on non-POSIX compliant systems except
   * Windows'. This doesn't seem like a big deal to me, but I thought it was
   * worth nothing. @see https://www.php.net/manual/en/function.fnmatch.php
   * for more details.
   *
   * @param  Array $args path arguments
   * @return Object a rule that can be used to construct CORS headers
   */
  public function matchRule($args) {
    $match = implode('/', $args);

    foreach ($this->loadRules() as $rule) {

      // phpcs:disable
      if (fnmatch($rule->pattern, $match)) {
        // phpcs:enable
        return $rule;
      }
    }

    return FALSE;
  }

  /**
   * Load CORS rules. Each rule is an array with the following keys
   * - pattern A URL pattern to match against (using fnmatch())
   * - origins Comma separated list of allowed origins or '*'.
   * - headers Comma separated list of allowed headers
   * - methods Comma separated list of allowed methods
   *
   * @return Array of CORS rules
   */
  public function loadRules() {
    $rulesJson = \Civi::settings()->get('cors_rules');
    $rules = json_decode($rulesJson);
    return $rules;
  }

  public function validateRules($rules) {
    $rules = json_decode($rules);
    if ($rules === NULL) {
      return FALSE;
    }
    if (!is_array($rules)) {
      return FALSE;
    }
    foreach ($rules as $rule) {
      if (!isset($rule->pattern)) {
        return FALSE;
      }
      if (!isset($rule->origins)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  public function validateMaxAge($maxAge) {
    return $maxAge === '' || is_numeric($maxAge);
  }

  /**
   * Set the allowed origin(s) for this request.
   *
   * @param String $origins '*' or a comma separated list of allowed origins.
   * @return void
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin
   */
  public function setAllowOrigin($origins) {

    // No origin specified.
    if (!$origins) {
      return;
    }

    // Wildcard origin.
    if ($origins === '*') {
      header('Access-Control-Allow-Origin: *');
      return;
    }

    // One or more specific origins.
    $originArray = array_map('trim', explode(',', $origins));

    // One origin.
    if (count($originArray) == 1) {
      header('Access-Control-Allow-Origin: ' . $originArray[0]);
    }
    // More than one origin (try and match one).
    else {
      $key = array_search($_SERVER["HTTP_ORIGIN"], $originArray);
      if ($key !== FALSE) {
        header('Access-Control-Allow-Origin: ' . $originArray[$key]);

        // Add a Vary header
        header('Vary: Origin', FALSE);
      }
    }
  }

  /**
   * Set the allowed headers for this request (typically authorisation headers).
   *
   * @param String $headers '*' or a comma separated list of allowed headers
   * @return void
   * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Headers
   */
  public function setAllowHeaders($headers) {
    if (!$headers) {
      return;
    }
    header('Access-Control-Allow-Headers: ' . $headers);
  }

  /**
   * Add an 'Access-Control-Allow-Methods' header.
   *
   * @param  String $methods '*' or a comma separated list of allowed methods.
   * @return void
   */
  public function setAllowMethods($methods) {
    if (!$methods) {
      return;
    }

    // Wildcard origin
    if ($methods === '*') {
      header('Access-Control-Allow-Methods: *');
      return;
    }

    $methodsArray = array_map('trim', explode(',', $methods));
    $validMethods = [
      'GET',
      'HEAD',
      'POST',
      'PUT',
      'DELETE',
      'CONNECT',
      'OPTIONS',
      'TRACE',
      'PATCH',
    ];
    $methodWhitelistedArray = array_intersect($methodsArray, $validMethods);
    $methodWhitelisted = implode(', ', $methodWhitelistedArray);
    header('Access-Control-Allow-Methods: ' . $methodWhitelisted);
  }

  /**
   * Add an 'Access-Control-Max-Age' header.
   *
   * @return void
   */
  public function setMaxAge() {

    $maxAge = \Civi::settings()->get('cors_max_age');

    if (!is_numeric($maxAge)) {
      return;
    }

    if ($maxAge) {
      header('Access-Control-Max-Age: ' . (int) $maxAge);
    }
  }

}
