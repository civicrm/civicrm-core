<?php
namespace Civi\Core;

/**
 * The callback helper class provides a richer callback binding notation.
 *
 * @package Civi\Core
 */
class Callback {

  /**
   * Convert a callback expression to a valid PHP callback.
   *
   * @param string|array $callback
   *   A callback expression; any of the following:
   *   - 'function_name' - Call a global function.
   *   - 'ClassName::methodName" - Call a static method in class.
   *   - 'obj://objectName/method' - Call a method on an object from Civi\Core\Container.
   *     (Performance note: Requires an extra lookup to find objectName.)
   *   - 'api3://EntityName/action' - Call an API method.
   *     (Performance note: Requires full setup/teardown of API subsystem.)
   *   - 'api3://EntityName/action?first=@1&second=@2' - Call an API method, mapping the
   *     first & second args to named parameters.
   *     (Performance note: Requires parsing/interpolating arguments).
   *   - '0' or '1' - A dummy which returns the constant '0' or '1'.
   *
   * @return array
   *   A PHP callback. Do not serialize (b/c it may include an object).
   */
  public static function create($callback) {
    if (!is_string($callback)) {
      // If caller has already produced an array or object, then it's probably
      // a valid callback.
      return $callback;
    }

    if (strpos($callback, '::') !== FALSE) {
      return explode('::', $callback);
    }
    elseif (strpos($callback, '://') !== FALSE) {
      $url = parse_url($callback);
      switch ($url['scheme']) {
        case 'obj':
          $obj = Container::singleton()->get($url['host']);
          return array($obj, ltrim($url['path'], '/'));

        case 'api3':
          return new CallbackApi($url);

        default:
          throw new \RuntimeException("Unsupported callback scheme: " . $url['scheme']);
      }
    }
    elseif (in_array($callback, array('0', '1'))) {
      return new CallbackConstant($callback);
    }
    else {
      return $callback;
    }
  }

}

/**
 * Private helper which produces a dummy callback.
 *
 * @package Civi\Core
 */
class CallbackConstant {
  /**
   * @var mixed
   */
  private $value;

  /**
   * @param mixed $value
   *   The value to be returned by the dummy callback.
   */
  public function __construct($value) {
    $this->value = $value;
  }

  /**
   * @return mixed
   */
  public function __invoke() {
    return $this->value;
  }

}

/**
 * Private helper which treats an API as a callable function.
 *
 * @package Civi\Core
 */
class CallbackApi {
  /**
   * @var array
   *  - string scheme
   *  - string host
   *  - string path
   *  - string query (optional)
   */
  private $url;

  /**
   * @param array $url
   *   Parsed URL (e.g. "api3://EntityName/action?foo=bar").
   * @see parse_url
   */
  public function __construct($url) {
    $this->url = $url;
  }

  /**
   * Fire an API call.
   */
  public function __invoke() {
    $apiParams = array();
    if (isset($this->url['query'])) {
      parse_str($this->url['query'], $apiParams);
    }

    if (count($apiParams)) {
      $args = func_get_args();
      if (count($args)) {
        $this->interpolate($apiParams, $this->createPlaceholders($args));
      }
    }

    $result = civicrm_api3($this->url['host'], ltrim($this->url['path'], '/'), $apiParams);
    return isset($result['values']) ? $result['values'] : NULL;
  }

  /**
   * @param array $args
   *   Positional arguments.
   * @return array
   *   Named placeholders based on the positional arguments
   *   (e.g. "@1" => "firstValue").
   */
  protected function createPlaceholders($args) {
    $result = array();
    foreach ($args as $offset => $arg) {
      $result['@' . (1 + $offset)] = $arg;
    }
    return $result;
  }

  /**
   * Recursively interpolate values.
   *
   * @code
   * $params = array('foo' => '@1');
   * $this->interpolate($params, array('@1'=> $object))
   * assert $data['foo'] == $object;
   * @endcode
   *
   * @param array $array
   *   Array which may or many not contain a mix of tokens.
   * @param array $replacements
   *   A list of tokens to substitute.
   */
  protected function interpolate(&$array, $replacements) {
    foreach (array_keys($array) as $key) {
      if (is_array($array[$key])) {
        $this->interpolate($array[$key], $replacements);
        continue;
      }
      foreach ($replacements as $oldVal => $newVal) {
        if ($array[$key] === $oldVal) {
          $array[$key] = $newVal;
        }
      }
    }
  }

}
