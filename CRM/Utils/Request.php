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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class for managing a http request
 */
class CRM_Utils_Request {

  /**
   * Get a unique ID for the request.
   *
   * This unique ID is assigned to mysql when the connection is opened and is
   * available in PHP.
   *
   * The intent is that it is available for logging purposes and for triggers.
   *
   * The resulting string is 17 characters long. This consists of 13 characters of uniqid
   * and 4 more random characters.
   *
   * Uniqid is unique to the microsecond - to make it more unique we add 4 more characters
   * but stop short of the full 23 character string that a prefix would generate.
   *
   * It is intended that this string will be saved to log tables so striking a balance between
   * uniqueness and length is important. Note that I did check & lining up with byte values
   * (e.g 16 characters) does not confer any benefits. Using a CHAR field rather than VARCHAR
   * may improve speed, if indexed.
   *
   * @return string
   */
  public static function id() {
    if (!isset(\Civi::$statics[__CLASS__]['id'])) {
      \Civi::$statics[__CLASS__]['id'] = uniqid() . CRM_Utils_String::createRandom(4, CRM_Utils_String::ALPHANUMERIC);
    }
    return \Civi::$statics[__CLASS__]['id'];
  }

  /**
   * Retrieve a value from the request (GET/POST/REQUEST)
   *
   * @param string $name
   *   Name of the variable to be retrieved.
   * @param string $type
   *   Type of the variable (see CRM_Utils_Type for details).
   * @param object $store
   *   Session scope where variable is stored.
   * @param bool $abort
   *   TRUE, if the variable is required.
   * @param mixed $default
   *   Default value of the variable if not present.
   * @param string $method
   *   Where to look for the variable - 'GET', 'POST' or 'REQUEST'.
   *
   * @return mixed
   *   The value of the variable
   *
   * @throws \CRM_Core_Exception
   */
  public static function retrieve($name, $type, $store = NULL, $abort = FALSE, $default = NULL, $method = 'REQUEST') {

    switch ($method) {
      case 'GET':
        $value = self::getValue($name, $_GET);
        break;

      case 'POST':
        $value = self::getValue($name, $_POST);
        break;

      default:
        $value = self::getValue($name, $_REQUEST);
        break;
    }

    if (isset($value)) {
      $value = CRM_Utils_Type::validate($value, $type, $abort, $name);
    }

    if (!isset($value) && $store) {
      $value = $store->get($name);
    }

    if (!isset($value) && $abort) {
      throw new CRM_Core_Exception(ts('Could not find valid value for %1', [1 => $name]));
    }

    if (!isset($value) && $default) {
      $value = $default;
    }

    // minor hack for action
    if ($name === 'action') {
      if (!is_numeric($value) && is_string($value)) {
        $value = CRM_Core_Action::resolve($value);
      }
    }

    if (isset($value) && $store) {
      $store->set($name, $value);
    }

    return $value;
  }

  /**
   * @param string $name
   *   Name of the variable to be retrieved.
   *
   * @param array $method - '$_GET', '$_POST' or '$_REQUEST'.
   *
   * @return mixed
   *   The value of the variable
   */
  protected static function getValue($name, $method) {
    if (isset($method[$name])) {
      return $method[$name];
    }
    // CRM-18384 - decode incorrect keys generated when &amp; is present in url
    foreach (($method ?? []) as $key => $value) {
      if (str_contains($key, 'amp;')) {
        $method[str_replace('amp;', '', $key)] = $method[$key];
        if (isset($method[$name])) {
          return $method[$name];
        }
        else {
          continue;
        }
      }
    }
    return NULL;
  }

  /**
   * @deprecated
   *
   * We should use a function that checks url values.
   *
   * This is a replacement for $_REQUEST which includes $_GET/$_POST
   * but excludes $_COOKIE / $_ENV / $_SERVER.
   *
   * @return array
   */
  public static function exportValues() {
    // For more discussion of default $_REQUEST handling, see:
    // http://www.php.net/manual/en/reserved.variables.request.php
    // http://www.php.net/manual/en/ini.core.php#ini.request-order
    // http://www.php.net/manual/en/ini.core.php#ini.variables-order

    $result = [];
    if ($_GET) {
      $result = array_merge($result, $_GET);
    }
    if ($_POST) {
      $result = array_merge($result, $_POST);
    }
    // Any JSON array merges here, including the lists some webhooks send; malformed bodies are left to the caller.
    $jsonBody = json_decode(self::getRawJsonBody(), TRUE);
    return is_array($jsonBody) ? array_merge($result, $jsonBody) : $result;
  }

  /**
   * Get the top-level values of a POST body sent as `application/json`.
   *
   * @param \Psr\Http\Message\ServerRequestInterface|null $request
   *   Defaults to the current request.
   * @return array
   *   Empty unless the request is a POST with a JSON media type.
   * @throws \CRM_Core_Exception
   *   If the JSON body is malformed or not an object.
   */
  public static function getJsonBody(?ServerRequestInterface $request = NULL): array {
    $raw = self::getRawJsonBody($request);
    if ($raw === '') {
      return [];
    }
    $body = json_decode($raw, TRUE);
    if (!is_array($body) || !str_starts_with($raw, '{')) {
      throw new CRM_Core_Exception('Malformed JSON in request body: expected an object.');
    }
    return $body;
  }

  /**
   * @return string
   *   The trimmed body of a POST with a JSON media type, otherwise ''.
   */
  private static function getRawJsonBody(?ServerRequestInterface $request = NULL): string {
    if ($request === NULL) {
      // Skip building a request object for the common non-POST case.
      if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return '';
      }
      return \Civi::$statics[__CLASS__]['rawJsonBody'] ??= self::getRawJsonBody(ServerRequest::fromGlobals());
    }
    if ($request->getMethod() !== 'POST' || !self::isJsonContentType($request->getHeaderLine('Content-Type'))) {
      return '';
    }
    return trim((string) $request->getBody());
  }

  /**
   * Retrieve a structured value: JSON-encoded in form/query data, or nested in a JSON body.
   *
   * @param string $name
   * @param string $method
   *   'GET', 'POST' or 'REQUEST'. A JSON body is only read for POST and REQUEST.
   * @param \Psr\Http\Message\ServerRequestInterface|null $request
   *   Defaults to the current request.
   * @return array|null
   *   NULL if the value is absent or empty.
   * @throws \CRM_Core_Exception
   *   If the value or the JSON body is malformed.
   */
  public static function retrieveJson(string $name, string $method = 'REQUEST', ?ServerRequestInterface $request = NULL): ?array {
    $value = $method === 'GET' ? NULL : (self::getJsonBody($request)[$name] ?? NULL);
    $value ??= self::retrieve($name, 'String', NULL, FALSE, NULL, $method);
    if ($value === NULL || $value === '') {
      return NULL;
    }
    if (is_string($value)) {
      $value = json_decode($value, TRUE);
    }
    if (!is_array($value)) {
      throw new CRM_Core_Exception("Malformed JSON in '$name'.");
    }
    return $value;
  }

  /**
   * Media types compare case-insensitively and ignore parameters such as `charset` (RFC 9110).
   */
  private static function isJsonContentType(string $contentType): bool {
    return strtolower(trim(explode(';', $contentType)[0])) === 'application/json';
  }

  /**
   * Retrieve a variable from the http request.
   *
   * @param string $name
   *   Name of the variable to be retrieved.
   * @param string $type
   *   Type of the variable (see CRM_Utils_Type for details).
   *   Most common options are:
   *   - 'Integer'
   *   - 'Positive'
   *   - 'CommaSeparatedIntegers'
   *   - 'Boolean'
   *   - 'String'
   *
   * @param mixed $defaultValue
   *   Default value of the variable if not present.
   * @param bool $isRequired
   *   Is the variable required for this function to proceed without an exception.
   * @param string $method
   *   Where to look for the value - GET|POST|REQUEST
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public static function retrieveValue($name, $type, $defaultValue = NULL, $isRequired = FALSE, $method = 'REQUEST') {
    $null = NULL;
    return CRM_Utils_Request::retrieve((string) $name, (string) $type, $null, (bool) $isRequired, $defaultValue, $method, TRUE);
  }

  /**
   * Retrieve the component from the action attribute of a form.
   *
   * Contribution Page forms and Event Management forms detect the value of a
   * component (and therefore the desired tab key) by reaching into the "action"
   * attribute of a form and reading the final item of the path. In WordPress,
   * however, the URL may be urlencoded, and so the URL may need to be decoded
   * before parsing it.
   *
   * @see https://lab.civicrm.org/dev/wordpress/issues/12#note_10699
   *
   * @param array $attributes
   *   The form attributes array.
   *
   * @return string
   *   The desired value.
   */
  public static function retrieveComponent($attributes) {
    $url = $attributes['action'] ?? NULL;
    // Whilst the following is a fallible universal test for urlencoded URLs,
    // thankfully the "action" URL has a limited and predictable form and
    // therefore this comparison is sufficient for our purposes.
    if (rawurlencode(rawurldecode($url)) !== $url) {
      $value = strtolower(basename(rawurldecode($url)));
    }
    else {
      $value = strtolower(basename($url));
    }
    return $value;
  }

}
