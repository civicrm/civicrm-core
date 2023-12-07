<?php

/**
 *
 * This class allows to consume the API, either from within a module that knows civicrm already:
 *
 * ```
 *   require_once('api/class.api.php');
 *   $api = new civicrm_api3();
 * ```
 *
 * or from any code on the same server as civicrm
 *
 * ```
 *   require_once('/your/civi/folder/api/class.api.php');
 *   // the path to civicrm.settings.php
 *   $api = new civicrm_api3 (['conf_path'=> '/your/path/to/your/civicrm/or/joomla/site']);
 * ```
 *
 * or to query a remote server via the rest api
 *
 * ```
 *   $api = new civicrm_api3 (['server' => 'http://example.org',
 *                             'api_key'=>'theusersecretkey',
 *                             'key'=>'thesitesecretkey']);
 * ```
 *
 * No matter how initialised and if civicrm is local or remote, you use the class the same way.
 *
 * ```
 *   $api->{entity}->{action}($params);
 * ```
 *
 * So, to get the individual contacts:
 *
 * ```
 *   if ($api->Contact->Get(['contact_type'=>'Individual','return'=>'sort_name,current_employer']) {
 *     // each key of the result array is an attribute of the api
 *     echo "\n contacts found " . $api->count;
 *     foreach ($api->values as $c) {
 *       echo "\n".$c->sort_name. " working for ". $c->current_employer;
 *     }
 *     // in theory, doesn't append
 *   } else {
 *     echo $api->errorMsg();
 *   }
 * ```
 *
 * Or, to create an event:
 *
 * ```
 *   if ($api->Event->Create(['title'=>'Test','event_type_id' => 1,'is_public' => 1,'start_date' => 19430429])) {
 *     echo "created event id:". $api->id;
 *   } else {
 *     echo $api->errorMsg();
 *   }
 * ```
 *
 * To make it easier, the Actions can either take for input an
 * associative array $params, or simply an id. The following two lines
 * are equivalent.
 *
 * ```
 *   $api->Activity->Get (42);
 *   $api->Activity->Get (['id'=>42]);
 * ```
 *
 *
 * You can also get the result like civicrm_api does, but as an object
 * instead of an array (eg $entity->attribute instead of
 * $entity['attribute']).
 *
 * ```
 *   $result = $api->result;
 *   // is the json encoded result
 *   echo $api;
 * ```
 *
 * For remote calls, you may need to set the UserAgent and Referer strings for some environments (eg WordFence)
 * Add 'referer' and 'useragent' to the initialisation config:
 *
 * ```
 *   $api = new civicrm_api3 (['server' => 'http://example.org',
 *                             'api_key'=>'theusersecretkey',
 *                             'key'=>'thesitesecretkey',
 *                             'referer'=>'https://my_site',
 *                             'useragent'=>'curl']);
 * ```
 */
class civicrm_api3 {

  /**
   * Are we performing a local or remote API call?
   *
   * @var bool
   */
  public $local = TRUE;

  /**
   * Array of inputs to pass to `call`, if param not passed directly
   *
   * @var array
   * @internal
   */
  public $input = [];

  /**
   * Holds the result of the last API request.
   * If the request has not yet run, lastResult will be empty.
   *
   * @var \stdClass
   * @internal
   */
  public $lastResult;

  /**
   * When making a remote API request,
   * $uri will be the path to the remote server's API endpoint
   *
   * @var string|null
   * @internal
   */
  public $uri = NULL;

  /**
   * When making a remote API request,
   * $key will be sent as part of the request
   *
   * @var string|null
   * @internal
   */
  public $key = NULL;

  /**
   * When making a remote API request,
   * $api_key will be sent as part of the request
   *
   * @var string|null
   * @internal
   */
  public $api_key = NULL;

  /**
   * When making a remote API request,
   * $referer holds the Referer header value to be sent as part of the request
   *
   * @var string|null
   * @internal
   */
  public $referer = NULL;

  /**
   * When making a remote API request,
   * $useragent holds the User-Agent header value to be sent as part of the request
   *
   * @var string|null
   * @internal
   */
  public $useragent = NULL;

  /**
   * Reference to the CRM_Core_Config singleton
   *
   * @var CRM_Core_Config
   */
  protected $cfg;

  /**
   * The current entity, which actions should be performed against
   *
   * @var string|null
   */
  protected $currentEntity = NULL;

  /**
   * Class constructor.
   *
   * @param array $config API configuration.
   */
  public function __construct($config = NULL) {
    $this->local      = TRUE;
    $this->input      = [];
    $this->lastResult = new stdClass();
    if (!empty($config) && !empty($config['server'])) {
      // we are calling a remote server via REST
      $this->local = FALSE;
      $this->uri = $config['server'];
      if (!empty($config['path'])) {
        $this->uri .= "/" . $config['path'];
      }
      else {
        $this->uri .= '/sites/all/modules/civicrm/extern/rest.php';
      }
      if (!empty($config['key'])) {
        $this->key = $config['key'];
      }
      else {
        die("\nFATAL:param['key] missing\n");
      }
      if (!empty($config['api_key'])) {
        $this->api_key = $config['api_key'];
      }
      else {
        die("\nFATAL:param['api_key] missing\n");
      }
      $this->referer = !empty($config['referer']) ? $config['referer'] : '';
      $this->useragent = !empty($config['useragent']) ? $config['useragent'] : 'curl';
      return;
    }
    if (!empty($config) && !empty($config['conf_path'])) {
      if (!defined('CIVICRM_SETTINGS_PATH')) {
        define('CIVICRM_SETTINGS_PATH', $config['conf_path'] . '/civicrm.settings.php');
      }
      require_once CIVICRM_SETTINGS_PATH;
      require_once 'CRM/Core/ClassLoader.php';
      require_once 'api/api.php';
      require_once "api/v3/utils.php";
      CRM_Core_ClassLoader::singleton()->register();
      $this->cfg = CRM_Core_Config::singleton();
      $this->init();
    }
    else {
      $this->cfg = CRM_Core_Config::singleton();
    }
  }

  /**
   * Convert to string.
   *
   * @return string
   */
  public function __toString() {
    return json_encode($this->lastResult);
  }

  /**
   * Perform action.
   *
   * @param string $action
   * @param array $params
   *
   * @return bool
   */
  public function __call($action, $params) {
    // @TODO Check if it's a valid action.
    if (isset($params[0])) {
      return $this->call($this->currentEntity, $action, $params[0]);
    }
    else {
      return $this->call($this->currentEntity, $action, $this->input);
    }
  }

  /**
   * Call via rest.
   *
   * @param string $entity
   * @param string $action
   * @param array $params
   *
   * @return \stdClass
   */
  private function remoteCall($entity, $action, $params = []) {
    $query = $this->uri . "?entity=$entity&action=$action";
    $fields = http_build_query([
      'key' => $this->key,
      'api_key' => $this->api_key,
      'json' => json_encode($params),
    ]);

    if (function_exists('curl_init')) {
      // To facilitate debugging without leaking info, entity & action
      // are GET, other data is POST.
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $query);
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
      if ($this->referer) {
        curl_setopt($ch, CURLOPT_REFERER, $this->referer);
      }
      $result = curl_exec($ch);
      // CiviCRM expects to get back a CiviCRM error object.
      if (curl_errno($ch)) {
        $res = new stdClass();
        $res->is_error = 1;
        $res->error_message = curl_error($ch);
        $res->level = "cURL";
        $res->error = ['cURL error' => curl_error($ch)];
        return $res;
      }
      curl_close($ch);
    }
    else {
      // Should be discouraged, because the API credentials and data
      // are submitted as GET data, increasing chance of exposure..
      $result = file_get_contents($query . '&' . $fields);
    }
    if (!$res = json_decode($result)) {
      $res = new stdClass();
      $res->is_error = 1;
      $res->error_message = 'Unable to parse returned JSON';
      $res->level = 'json_decode';
      $res->error = ['Unable to parse returned JSON' => $result];
      $res->row_result = $result;
    }
    return $res;
  }

  /**
   * Call api function.
   *
   * @param string $entity
   * @param string $action
   * @param array $params
   *
   * @return bool
   */
  private function call($entity, $action = 'Get', $params = []) {
    if (is_int($params)) {
      $params = ['id' => $params];
    }
    elseif (is_string($params)) {
      $params = json_decode($params);
    }

    if (!isset($params['version'])) {
      $params['version'] = 3;
    }
    if (!isset($params['sequential'])) {
      $params['sequential'] = 1;
    }

    if (!$this->local) {
      $this->lastResult = $this->remoteCall($entity, $action, $params);
    }
    else {
      // Converts a multi-dimentional array into an object.
      $this->lastResult = json_decode(json_encode(civicrm_api($entity, $action, $params)));
    }
    // Reset the input to be ready for a new call.
    $this->input = [];
    if (property_exists($this->lastResult, 'is_error')) {
      return !$this->lastResult->is_error;
    }
    // getsingle doesn't have is_error.
    return TRUE;
  }

  /**
   * Helper method for long running programs (eg bots).
   */
  public function ping() {
    global $_DB_DATAOBJECT;
    foreach ($_DB_DATAOBJECT['CONNECTIONS'] as & $c) {
      if (!$c->connection->ping()) {
        $c->connect($this->cfg->dsn);
        if (!$c->connection->ping()) {
          die("we couldn't connect");
        }
      }
    }
  }

  /**
   * Return the last error message.
   * @return string
   */
  public function errorMsg() {
    return $this->lastResult->error_message;
  }

  /**
   * Initialize.
   */
  public function init() {
    CRM_Core_DAO::init($this->cfg->dsn);
  }

  /**
   * Get attribute.
   *
   * @param string $name
   * @param mixed $value
   *
   * @return $this
   */
  public function attr($name, $value = NULL) {
    if ($value === NULL) {
      if (property_exists($this->lastResult, $name)) {
        return $this->lastResult->$name;
      }
    }
    else {
      $this->input[$name] = $value;
    }
    return $this;
  }

  /**
   * Is this an error.
   *
   * @return bool
   */
  public function is_error() {
    return (property_exists($this->lastResult, 'is_error') && $this->lastResult->is_error);
  }

  /**
   * Check if var is set.
   *
   * @param string $name
   *
   * @return bool
   */
  public function is_set($name) {
    return (isset($this->lastResult->$name));
  }

  /**
   * Get object.
   *
   * @param string $name
   *
   * @return $this
   */
  public function __get($name) {
    // @TODO Test if valid entity.
    if (strtolower($name) !== $name) {
      // Cheap and dumb test to differentiate call to
      // $api->Entity->Action & value retrieval.
      $this->currentEntity = $name;
      return $this;
    }
    if ($name === 'result') {
      return $this->lastResult;
    }
    if ($name === 'values') {
      return $this->lastResult->values;
    }
    if (property_exists($this->lastResult, $name)) {
      return $this->lastResult->$name;
    }
    $this->currentEntity = $name;
    return $this;
  }

  /**
   * Or use $api->value.
   * @return array
   */
  public function values() {
    if (is_array($this->lastResult)) {
      return $this->lastResult['values'];
    }
    else {
      return $this->lastResult->values;
    }
  }

  /**
   * Or use $api->result.
   * @return array
   */
  public function result() {
    return $this->lastResult;
  }

}
