<?php
namespace Civi\Core;

/**
 * The resolver takes a string expression and returns an object or callable.
 *
 * The following patterns will resolve to objects:
 *   - 'obj://objectName' - An object from Civi\Core\Container
 *   - 'ClassName' - An instance of ClassName (with default constructor).
 *     If you need more control over construction, then register with the
 *     container.
 *
 * The following patterns will resolve to callables:
 *   - 'function_name' - A function(callable).
 *   - 'ClassName::methodName" - A static method of a class.
 *   - 'call://objectName/method' - A method on an object from Civi\Core\Container.
 *   - 'api3://EntityName/action' - A method call on an API.
 *     (Performance note: Requires full setup/teardown of API subsystem.)
 *   - 'api3://EntityName/action?first=@1&second=@2' - Call an API method, mapping the
 *     first & second args to named parameters.
 *     (Performance note: Requires parsing/interpolating arguments).
 *   - 'global://Variable/Key2/Key3?getter' - A dummy which looks up a global variable.
 *   - 'global://Variable/Key2/Key3?setter' - A dummy which updates a global variable.
 *   - '0' or '1' - A dummy which returns the constant '0' or '1'.
 *
 * Note: To differentiate classes and functions, there is a hard requirement that
 * class names begin with an uppercase letter.
 *
 * Note: If you are working in a context which requires a callable, it is legitimate to use
 * an object notation ("obj://objectName" or "ClassName") if the object supports __invoke().
 *
 * @package Civi\Core
 */
class Resolver {

  protected static $_singleton;

  /**
   * Singleton function.
   *
   * @return Resolver
   */
  public static function singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new Resolver();
    }
    return self::$_singleton;
  }

  /**
   * Convert a callback expression to a valid PHP callback.
   *
   * @param string|array $id
   *   A callback expression; any of the following.
   *
   * @return array|callable
   *   A PHP callback. Do not serialize (b/c it may include an object).
   * @throws \RuntimeException
   */
  public function get($id) {
    if (!is_string($id)) {
      // An array or object does not need to be further resolved.
      return $id;
    }

    if (strpos($id, '::') !== FALSE) {
      // Callback: Static method.
      return explode('::', $id);
    }
    elseif (strpos($id, '://') !== FALSE) {
      $url = parse_url($id);
      switch ($url['scheme']) {
        case 'obj':
          // Object: Lookup in container.
          return \Civi::service($url['host']);

        case 'call':
          // Callback: Object/method in container.
          $obj = \Civi::service($url['host']);
          return array($obj, ltrim($url['path'], '/'));

        case 'api3':
          // Callback: API.
          return new ResolverApi($url);

        case 'global':
          // Lookup in a global variable.
          return new ResolverGlobalCallback($url['query'], $url['host'] . (isset($url['path']) ? rtrim($url['path'], '/') : ''));

        default:
          throw new \RuntimeException("Unsupported callback scheme: " . $url['scheme']);
      }
    }
    elseif (in_array($id, array('0', '1'))) {
      // Callback: Constant value.
      return new ResolverConstantCallback((int) $id);
    }
    elseif ($id{0} >= 'A' && $id{0} <= 'Z') {
      // Object: New/default instance.
      return new $id();
    }
    else {
      // Callback: Function.
      return $id;
    }
  }

  /**
   * Invoke a callback expression.
   *
   * @param string|callable $id
   * @param array $args
   *   Ordered parameters. To call-by-reference, set an array-parameter by reference.
   *
   * @return mixed
   */
  public function call($id, $args) {
    $cb = $this->get($id);
    return $cb ? call_user_func_array($cb, $args) : NULL;
  }

}

/**
 * Private helper which produces a dummy callback.
 *
 * @package Civi\Core
 */
class ResolverConstantCallback {
  /**
   * @var mixed
   */
  private $value;

  /**
   * Class constructor.
   *
   * @param mixed $value
   *   The value to be returned by the dummy callback.
   */
  public function __construct($value) {
    $this->value = $value;
  }

  /**
   * Invoke function.
   *
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
class ResolverApi {
  /**
   * @var array
   *  - string scheme
   *  - string host
   *  - string path
   *  - string query (optional)
   */
  private $url;

  /**
   * Class constructor.
   *
   * @param array $url
   *   Parsed URL (e.g. "api3://EntityName/action?foo=bar").
   *
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
        $this->interpolate($apiParams, $this->createPlaceholders('@', $args));
      }
    }

    $result = civicrm_api3($this->url['host'], ltrim($this->url['path'], '/'), $apiParams);
    return isset($result['values']) ? $result['values'] : NULL;
  }

  /**
   * Create placeholders.
   *
   * @param string $prefix
   * @param array $args
   *   Positional arguments.
   *
   * @return array
   *   Named placeholders based on the positional arguments
   *   (e.g. "@1" => "firstValue").
   */
  protected function createPlaceholders($prefix, $args) {
    $result = array();
    foreach ($args as $offset => $arg) {
      $result[$prefix . (1 + $offset)] = $arg;
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

class ResolverGlobalCallback {
  private $mode, $path;

  /**
   * Class constructor.
   *
   * @param string $mode
   *   'getter' or 'setter'.
   * @param string $path
   */
  public function __construct($mode, $path) {
    $this->mode = $mode;
    $this->path = $path;
  }

  /**
   * Invoke function.
   *
   * @param mixed $arg1
   *
   * @return mixed
   */
  public function __invoke($arg1 = NULL) {
    if ($this->mode === 'getter') {
      return \CRM_Utils_Array::pathGet($GLOBALS, explode('/', $this->path));
    }
    elseif ($this->mode === 'setter') {
      \CRM_Utils_Array::pathSet($GLOBALS, explode('/', $this->path), $arg1);
      return NULL;
    }
    else {
      throw new \RuntimeException("Resolver failed: global:// must specify getter or setter mode.");
    }
  }

}
