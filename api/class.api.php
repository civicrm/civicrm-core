<?php
// $Id$

/**

 This class allows to consume the API, either from within a module that knows civicrm already:

 require_once('api/class/api.php');
 $api = new civicrm_api3();

 or from any code on the same server as civicrm

 require_once('/your/civi/folder/api/class.api.php');
 // the path to civicrm.settings.php
 $api = new civicrm_api3 (array('conf_path'=> '/your/path/to/your/civicrm/or/joomla/site));

 or to query a remote server via the rest api

 $api = new civicrm_api3 (array ('server' => 'http://example.org','api_key'=>'theusersecretkey','key'=>'thesitesecretkey'));

 no matter how initialised and if civicrm is local or remote, you use the class the same way

 $api->{entity}->{action}($params);

 so to get the individual contacts

 if ($api->Contact->Get(array(
   'contact_type'=>'Individual','return'=>'sort_name,current_employer')) {
 // each key of the result array is an attribute of the api
 echo "\n contacts found " . $api->count;
 foreach ($api->values as $c) {
 echo "\n".$c->sort_name. " working for ". $c->current_employer;
 }
 // in theory, doesn't append
 } else {
 echo $api->errorMsg();
 }

 or to create an event

 if ($api->Event->Create(array(
   'title'=>'Test','event_type_id' => 1,'is_public' => 1,'start_date' => 19430429))) {
 echo "created event id:". $api->id;
 } else {
 echo $api->errorMsg();
 }

 To make it easier, the Actions can either take for input an associative array $params, or simply an id

 $api->Activity->Get (42);

 being the same as:

 $api->Activity->Get (array('id'=>42));

 you can too get the result like what civicrm_api does, but as an object instead of an array (eg $entity->attribute  instead of $entity['attribute']

 $result = $api->result;
 // is the json encoded result
 echo $api;

 */
class civicrm_api3 {
  function __construct($config = NULL) {
    $this->local      = TRUE;
    $this->input      = array();
    $this->lastResult = array();
    if (isset($config) && isset($config['server'])) {
      // we are calling a remote server via REST
      $this->local = FALSE;
      $this->uri = $config['server'];
      if (isset($config['path'])) {
        $this->uri .= "/" . $config['path'];
      }
      else $this->uri .= '/sites/all/modules/civicrm/extern/rest.php';
      $this->uri .= '?json=1';
      if (isset($config['key'])) {
        $this->key = $config['key'];
      }
      else {
        die("\nFATAL:param['key] missing\n");
      }
      if (isset($config['api_key'])) {
        $this->api_key = $config['api_key'];
      }
      else {
        die("\nFATAL:param['api_key] missing\n");
      }

      return;
    }
    if (isset($config) && isset($config['conf_path'])) {
      define('CIVICRM_SETTINGS_PATH', $config['conf_path'] . '/civicrm.settings.php');
      require_once CIVICRM_SETTINGS_PATH;
      require_once 'CRM/Core/Config.php';
      require_once 'api/api.php';
      require_once "api/v3/utils.php";
      $this->cfg = CRM_Core_Config::singleton();
      $this->init();
    }
    else {
      $this->cfg = CRM_Core_Config::singleton();
    }
  }

  public function __toString() {
    return json_encode($this->lastResult);
  }

  public function __call($action, $params) {
    // TODO : check if it's a valid action
    if (isset($params[0])) {
      return $this->call($this->currentEntity, $action, $params[0]);
    }
    else {
      return $this->call($this->currentEntity, $action, $this->input);
    }
  }

  /**  As of PHP 5.3.0  */
  public static function __callStatic($name, $arguments) {
    // Should we implement it ?
    echo "Calling static method '$name' " . implode(', ', $arguments) . "\n";
  }

  function remoteCall($entity, $action, $params = array(
    )) {
    $fields = "key={$this->key}&api_key={$this->api_key}";
    $query = $this->uri . "&entity=$entity&action=$action";
    foreach ($params as $k => $v) {
      $fields .= "&$k=" . urlencode($v);
    }
    if (function_exists('curl_init')) {
      //to make it easier to debug but avoid leaking info, entity&action are the url, the rest is in the POST
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $query);
      curl_setopt($ch, CURLOPT_POST, count($params) + 2);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

      //execute post
      $result = curl_exec($ch);
      curl_close($ch);
      return json_decode($result);
      // not good, all in get when should be in post.
    }
    else {
      $result = file_get_contents($query . '&' . $fields);
      return json_decode($result);
    }
  }

  function call($entity, $action = 'Get', $params = array(
    )) {
    if (is_int($params)) {
      $params = array('id' => $params);
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
      // easiest to convert a multi-dimentional array into an object
      $this->lastResult = json_decode(json_encode(civicrm_api($entity, $action, $params)));
    }
    // reset the input to be ready for a new call
    $this->input = array();
    if (property_exists($this->lastResult, 'is_error')) {
      return !$this->lastResult->is_error;
    }
    // getsingle doesn't have is_error
    return TRUE;
  }

  //* helper method for long running programs (eg bots)
  function ping() {
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

  function errorMsg() {
    return $this->lastResult->error_message;
  }

  function init() {
    CRM_Core_DAO::init($this->cfg->dsn);
  }

  /*
   // return the id
   * $api->attr ('id');
   * or
   * $api->attr ('id',42) //set the id
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

  public function is_error() {
    return (property_exists($this->lastResult, 'is_error') && $this->lastResult->is_error);
  }

  public function is_set($name) {
    return (isset($this->lastResult->$name));
  }

  /*  public function __set($name, $value)    {
     echo "Setting '$name' to '$value'\n";
  }
 */



  public function __get($name) {
    //TODO, test if valid entity
    if (strtolower($name) !== $name) {
      //cheap and dumb test to differenciate call to $api->Entity->Action & value retrieval
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


  // or use $api->value
  public function values() {
    if (is_array($this->lastResult)) {
      return $this->lastResult['values'];
    }
    else return $this->lastResult->values;
  }

  // or use $api->result
  public function result() {
    return $this->lastResult;
  }
}

