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

    // Try and exit early if we are not in a CORS context
    if ($_SERVER["HTTP_SEC_FETCH_SITE"] == 'same-origin') {
      return;
    }
    if (!isset($_SERVER["HTTP_ORIGIN"])) {
      return;
    }

    $rule = $this->matchRule($e->args);

    // If a rule matches, set appropriate headers.
    if ($rule) {
      $this->setAllowOrigin($rule['origins']);
      if ($rule['headers']) {
        $this->setAllowHeaders($rule['headers']);
      }
      if ($rule['methods']) {
        $this->setAllowMethods($rule['methods']);
      }
      if ($rule['max_age']) {
        $this->setMaxAge($rule['max_age']);
      }

      // Respond to all OPTIONS requests with a 204.
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
   * @param  array $args path arguments
   * @return array|null a rule that can be used to construct CORS headers
   */
  public function matchRule($args) {
    $path = implode('/', $args);
    $result = \Civi\Api4\CorsRule::get(FALSE)
      ->addWhere('path', 'REVERSE LIKE', $path)
      ->addOrderBy('priority', 'DESC')
      ->setLimit(1)
      ->execute()
      ->first();
    return $result;
  }

  /**
   * Set the allowed origin(s) for this request.
   *
   * @param String $origins Comma separated list of allowed origins or '*'.
   * @return void
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin
   */
  public function setAllowOrigin($origins) {

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
   * @param String $headers Comma separated list of allowed headers or '*'
   * @return void
   * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Headers
   */
  public function setAllowHeaders($headers) {
    header('Access-Control-Allow-Headers: ' . $headers);
  }

  /**
   * Add an 'Access-Control-Allow-Methods' header.
   *
   * @param  String $methods Comma separated list of allowed methods or '*'.
   * @return void
   */
  public function setAllowMethods($methods) {

    // Wildcard methods.
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
   * @param  Int $maxAge Maximum number of seconds the results can be cached.
   * @return void
   */
  public function setMaxAge($maxAge) {
    header('Access-Control-Max-Age: ' . $maxAge);
  }

}
