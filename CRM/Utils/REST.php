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
 * This class handles all REST client requests.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_REST {

  /**
   * Number of seconds we should let a REST process idle
   * @var int
   */
  public static $rest_timeout = 0;

  /**
   * Cache the actual UF Class
   * @var string
   */
  public $ufClass;

  /**
   * Class constructor.  This caches the real user framework class locally,
   * so we can use it for authentication and validation.
   *
   * @internal param string $uf The userframework class
   */
  public function __construct() {
    // any external program which call Rest Server is responsible for
    // creating and attaching the session
    $args = func_get_args();
    $this->ufClass = array_shift($args);
  }

  /**
   * Simple ping function to test for liveness.
   *
   * @param string $var
   *   The string to be echoed.
   *
   * @return array
   */
  public static function ping($var = NULL) {
    $session = CRM_Core_Session::singleton();
    $key = $session->get('key');
    // $session->set( 'key', $var );
    return self::simple(['message' => "PONG: $key"]);
  }

  /**
   * Generates values needed for error messages.
   * @param string $message
   *
   * @return array
   */
  public static function error($message = 'Unknown Error') {
    $values = [
      'error_message' => $message,
      'is_error' => 1,
    ];
    return $values;
  }

  /**
   * Generates values needed for non-error responses.
   * @param array $params
   *
   * @return array
   */
  public static function simple($params) {
    $values = ['is_error' => 0];
    $values += $params;
    return $values;
  }

  /**
   * @return string
   */
  public function run() {
    $result = self::handle();
    return self::output($result);
  }

  /**
   * @return string
   */
  public function bootAndRun() {
    $response = $this->loadCMSBootstrap();
    if (is_array($response)) {
      return self::output($response);
    }
    return $this->run();
  }

  /**
   * @param $result
   *
   * @return string
   */
  public static function output(&$result) {
    $requestParams = CRM_Utils_Request::exportValues();

    $hier = FALSE;
    if (is_scalar($result)) {
      if (!$result) {
        $result = 0;
      }
      $result = self::simple(['result' => $result]);
    }
    elseif (is_array($result)) {
      if (CRM_Utils_Array::isHierarchical($result)) {
        $hier = TRUE;
      }
      elseif (!array_key_exists('is_error', $result)) {
        $result['is_error'] = 0;
      }
    }
    else {
      $result = self::error('Could not interpret return values from function.');
    }

    if (!empty($requestParams['json'])) {
      if (!empty($requestParams['prettyprint'])) {
        // Don't set content-type header for api explorer output
        return json_encode(array_merge($result), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      }
      CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
      return json_encode(array_merge($result), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    if (isset($result['count'])) {
      $count = ' count="' . $result['count'] . '" ';
    }
    else {
      $count = "";
    }
    $xml = "<?xml version=\"1.0\"?>
      <ResultSet xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" $count>
      ";
    // check if this is a single element result (contact_get etc)
    // or multi element
    if ($hier) {
      foreach ($result['values'] as $k => $v) {
        if (is_array($v)) {
          $xml .= "<Result>\n" . CRM_Utils_Array::xml($v) . "</Result>\n";
        }
        elseif (!is_object($v)) {
          $xml .= "<Result>\n<id>{$k}</id><value>{$v}</value></Result>\n";
        }
      }
    }
    else {
      $xml .= "<Result>\n" . CRM_Utils_Array::xml($result) . "</Result>\n";
    }

    $xml .= "</ResultSet>\n";
    return $xml;
  }

  /**
   * @return array|int
   */
  public static function handle() {
    $requestParams = CRM_Utils_Request::exportValues();

    // Get the function name being called from the q parameter in the query string
    $q = $requestParams['q'] ?? NULL;
    // or for the rest interface, from fnName
    $r = $requestParams['fnName'] ?? NULL;
    if (!empty($r)) {
      $q = $r;
    }
    $entity = $requestParams['entity'] ?? NULL;
    if (empty($entity) && !empty($q)) {
      $args = explode('/', $q);
      // If the function isn't in the civicrm namespace, reject the request.
      if ($args[0] != 'civicrm') {
        return self::error('Unknown function invocation.');
      }

      // If the query string is malformed, reject the request.
      // Does this mean it will reject it
      if ((count($args) != 3) && ($args[1] != 'ping')) {
        return self::error('Unknown function invocation.');
      }

      if ($args[1] == 'ping') {
        return self::ping();
      }
    }
    else {
      // or the api format (entity+action)
      $args = [];
      $args[0] = 'civicrm';
      $args[1] = $requestParams['entity'] ?? NULL;
      $args[2] = $requestParams['action'] ?? NULL;
    }

    // Everyone should be required to provide the server key, so the whole
    // interface can be disabled in more change to the configuration file.
    // first check for civicrm site key
    if (!CRM_Utils_System::authenticateKey(FALSE)) {
      $docLink = CRM_Utils_System::docURL2('sysadmin/setup/jobs', TRUE);
      $key = $requestParams['key'] ?? NULL;
      if (empty($key)) {
        return self::error("FATAL: mandatory param 'key' missing. More info at: " . $docLink);
      }
      return self::error("FATAL: 'key' is incorrect. More info at: " . $docLink);
    }

    // At this point we know we are not calling ping which does not require authentication.
    // Therefore we now need a valid server key and API key.
    // Check and see if a valid secret API key is provided.
    $api_key = CRM_Utils_Request::retrieve('api_key', 'String');
    if (!$api_key || strtolower($api_key) == 'null') {
      return self::error("FATAL: mandatory param 'api_key' (user key) missing");
    }
    $valid_user = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $api_key, 'id', 'api_key');

    // If we didn't find a valid user, die
    if (empty($valid_user)) {
      return self::error("User API key invalid");
    }

    return self::process($args, self::buildParamList());
  }

  /**
   * @param $args
   * @param array $params
   *
   * @return array|int
   */
  public static function process(&$args, $params) {
    $params['check_permissions'] = TRUE;
    $fnName = $apiFile = NULL;
    // clean up all function / class names. they should be alphanumeric and _ only
    for ($i = 1; $i <= 3; $i++) {
      if (!empty($args[$i])) {
        $args[$i] = CRM_Utils_String::munge($args[$i]);
      }
    }

    // incase of ajax functions className is passed in url
    if (isset($params['className'])) {
      $params['className'] = CRM_Utils_String::munge($params['className']);

      // functions that are defined only in AJAX.php can be called via
      // rest interface
      if (!CRM_Core_Page_AJAX::checkAuthz('method', $params['className'], $params['fnName'])) {
        return self::error('Unknown function invocation.');
      }

      return call_user_func([$params['className'], $params['fnName']], $params);
    }

    if (!array_key_exists('version', $params)) {
      $params['version'] = 3;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'GET' &&
      strtolower(substr($args[2], 0, 3)) != 'get' &&
      strtolower($args[2] != 'check')) {
      // get only valid for non destructive methods
      require_once 'api/v3/utils.php';
      return civicrm_api3_create_error("SECURITY: All requests that modify the database must be http POST, not GET.",
        [
          'IP' => CRM_Utils_System::ipAddress(),
          'level' => 'security',
          'referer' => $_SERVER['HTTP_REFERER'],
          'reason' => 'Destructive HTTP GET',
        ]
      );
    }

    // trap all fatal errors
    try {
      $result = civicrm_api($args[1], $args[2], $params);
    }
    catch (Exception $e) {
      return self::error($e->getMessage());
    }

    if ($result === FALSE) {
      return self::error('Unknown error.');
    }
    return $result;
  }

  /**
   * @return array|mixed|null
   */
  public static function &buildParamList() {
    $requestParams = CRM_Utils_Request::exportValues();
    $params = [];

    $skipVars = [
      'q' => 1,
      'json' => 1,
      'key' => 1,
      'api_key' => 1,
      'entity' => 1,
      'action' => 1,
    ];

    if (array_key_exists('json', $requestParams) && $requestParams['json'][0] == "{") {
      $params = json_decode($requestParams['json'], TRUE);
      if ($params === NULL) {
        CRM_Utils_JSON::output([
          'is_error' => 1,
          0 => 'error_message',
          1 => 'Unable to decode supplied JSON.',
        ]);
      }
    }
    foreach ($requestParams as $n => $v) {
      if (!array_key_exists($n, $skipVars)) {
        $params[$n] = $v;
      }
    }
    if (array_key_exists('return', $requestParams) && is_array($requestParams['return'])) {
      foreach ($requestParams['return'] as $key => $v) {
        $params['return.' . $key] = 1;
      }
    }
    return $params;
  }

  /**
   * Unused function from the dark ages before PHP Exceptions
   * @deprecated
   * @param PEAR_Error $pearError
   */
  public static function fatal($pearError) {
    CRM_Utils_System::setHttpHeader('Content-Type', 'text/xml');
    $error = [];
    $error['code'] = $pearError->getCode();
    $error['error_message'] = $pearError->getMessage();
    $error['mode'] = $pearError->getMode();
    $error['debug_info'] = $pearError->getDebugInfo();
    $error['type'] = $pearError->getType();
    $error['user_info'] = $pearError->getUserInfo();
    $error['to_string'] = $pearError->toString();
    $error['is_error'] = 1;

    echo self::output($error);

    CRM_Utils_System::civiExit();
  }

  /**
   * used to load a template "inline", eg. for ajax, without having to build a menu for each template
   */
  public static function loadTemplate() {
    $request = CRM_Utils_Request::retrieve('q', 'String');
    if (FALSE !== strpos($request, '..')) {
      die("SECURITY FATAL: the url can't contain '..'. Please report the issue on the forum at civicrm.org");
    }

    $request = explode('/', $request);
    $entity = _civicrm_api_get_camel_name($request[2]);
    $tplfile = _civicrm_api_get_camel_name($request[3]);

    $tpl = 'CRM/' . $entity . '/Page/Inline/' . $tplfile . '.tpl';
    $smarty = CRM_Core_Smarty::singleton();
    CRM_Utils_System::setTitle("$entity::$tplfile inline $tpl");
    if (!$smarty->template_exists($tpl)) {
      CRM_Utils_System::setHttpHeader("Status", "404 Not Found");
      die("Can't find the requested template file templates/$tpl");
    }
    // special treatmenent, because it's often used
    if (array_key_exists('id', $_GET)) {
      // an id is always positive
      $smarty->assign('id', (int) $_GET['id']);
    }
    $pos = strpos(implode(array_keys($_GET)), '<');

    if ($pos !== FALSE) {
      die("SECURITY FATAL: one of the param names contains &lt;");
    }
    $param = array_map('htmlentities', $_GET);
    unset($param['q']);
    $smarty->assign("request", $param);

    if (!self::isWebServiceRequest()) {

      $smarty->assign('tplFile', $tpl);
      $config = CRM_Core_Config::singleton();
      $content = $smarty->fetch('CRM/common/' . strtolower($config->userFramework) . '.tpl');
      CRM_Utils_System::appendTPLFile($tpl, $content);

      return CRM_Utils_System::theme($content);

    }
    else {
      $content = "<!-- .tpl file embedded: $tpl -->\n";
      CRM_Utils_System::appendTPLFile($tpl, $content);
      echo $content . $smarty->fetch($tpl);
      CRM_Utils_System::civiExit();
    }
  }

  /**
   * This is a wrapper so you can call an api via json (it returns json too)
   * http://example.org/civicrm/api/json?entity=Contact&action=Get"&json={"contact_type":"Individual","email.get.email":{}}
   * to take all the emails from individuals.
   * Works for POST & GET (POST recommended).
   */
  public static function ajaxJson() {
    $requestParams = CRM_Utils_Request::exportValues();

    require_once 'api/v3/utils.php';
    $config = CRM_Core_Config::singleton();
    if (!$config->debug && !self::isWebServiceRequest()) {
      $error = civicrm_api3_create_error("SECURITY ALERT: Ajax requests can only be issued by javascript clients, eg. CRM.api3().",
        [
          'IP' => CRM_Utils_System::ipAddress(),
          'level' => 'security',
          'referer' => $_SERVER['HTTP_REFERER'],
          'reason' => 'CSRF suspected',
        ]
      );
      CRM_Utils_JSON::output($error);
    }
    if (empty($requestParams['entity'])) {
      CRM_Utils_JSON::output(civicrm_api3_create_error('missing entity param'));
    }
    if (empty($requestParams['entity'])) {
      CRM_Utils_JSON::output(civicrm_api3_create_error('missing entity entity'));
    }
    if (!empty($requestParams['json'])) {
      $params = json_decode($requestParams['json'], TRUE);
    }
    $entity = CRM_Utils_String::munge($requestParams['entity'] ?? '');
    $action = CRM_Utils_String::munge($requestParams['action'] ?? '');
    if (!is_array($params)) {
      CRM_Utils_JSON::output([
        'is_error' => 1,
        'error_message' => 'invalid json format: ?{"param_with_double_quote":"value"}',
      ]);
    }

    $params['check_permissions'] = TRUE;
    // $requestParams is local-only; this line seems pointless unless there's a side-effect influencing other functions
    $_GET['json'] = $requestParams['json'] = 1;
    if (!$params['sequential']) {
      $params['sequential'] = 1;
    }

    // trap all fatal errors
    try {
      $result = civicrm_api3($entity, $action, $params);
    }
    catch (Exception $e) {
      $result = self::error($e->getMessage());
    }

    echo self::output($result);

    CRM_Utils_System::civiExit();
  }

  /**
   * Run ajax request.
   *
   * @return array
   */
  public static function ajax() {
    $requestParams = CRM_Utils_Request::exportValues();

    // this is driven by the menu system, so we can use permissioning to
    // restrict calls to this etc
    // the request has to be sent by an ajax call. First line of protection against csrf
    $config = CRM_Core_Config::singleton();
    if (!$config->debug && !self::isWebServiceRequest()) {
      require_once 'api/v3/utils.php';
      $error = civicrm_api3_create_error("SECURITY ALERT: Ajax requests can only be issued by javascript clients, eg. CRM.api3().",
        [
          'IP' => CRM_Utils_System::ipAddress(),
          'level' => 'security',
          'referer' => $_SERVER['HTTP_REFERER'],
          'reason' => 'CSRF suspected',
        ]
      );
      CRM_Utils_JSON::output($error);
    }

    $q = $requestParams['fnName'] ?? NULL;
    if (!$q) {
      $entity = $requestParams['entity'] ?? NULL;
      $action = $requestParams['action'] ?? NULL;
      if (!$entity || !$action) {
        $err = [
          'error_message' => 'missing mandatory params "entity=" or "action="',
          'is_error' => 1,
        ];
        echo self::output($err);
        CRM_Utils_System::civiExit();
      }
      $args = ['civicrm', $entity, $action];
    }
    else {
      $args = explode('/', $q);
    }

    // get the class name, since all ajax functions pass className
    $className = $requestParams['className'] ?? NULL;

    // If the function isn't in the civicrm namespace, reject the request.
    if (($args[0] != 'civicrm' && count($args) != 3) && !$className) {
      return self::error('Unknown function invocation.');
    }

    // Support for multiple api calls
    if (isset($entity) && $entity === 'api3') {
      $result = self::processMultiple();
    }
    else {
      $result = self::process($args, self::buildParamList());
    }

    echo self::output($result);

    CRM_Utils_System::civiExit();
  }

  /**
   * Callback for multiple ajax api calls from CRM.api3()
   * @return array
   */
  public static function processMultiple() {
    $output = [];
    foreach (json_decode($_REQUEST['json'], TRUE) as $key => $call) {
      $args = [
        'civicrm',
        $call[0],
        $call[1],
      ];
      $output[$key] = self::process($args, $call[2] ?? []);
    }
    return $output;
  }

  /**
   * @return array|NULL
   *   NULL if execution should proceed; array if the response is already known
   */
  public function loadCMSBootstrap() {
    $requestParams = CRM_Utils_Request::exportValues();
    $q = $requestParams['q'] ?? '';
    $args = explode('/', $q);

    // Proceed with bootstrap for "?entity=X&action=Y"
    // Proceed with bootstrap for "?q=civicrm/X/Y" but not "?q=civicrm/ping"
    if (!empty($q)) {
      if (count($args) == 2 && $args[1] == 'ping') {
        CRM_Utils_System::loadBootStrap([], FALSE, FALSE);
        // this is pretty wonky but maybe there's some reason I can't see
        return NULL;
      }
      if (count($args) != 3) {
        return self::error('ERROR: Malformed REST path');
      }
      if ($args[0] != 'civicrm') {
        return self::error('ERROR: Malformed REST path');
      }
      // Therefore we have reasonably well-formed "?q=civicrm/X/Y"
    }

    if (!CRM_Utils_System::authenticateKey(FALSE)) {
      CRM_Utils_System::loadBootStrap([], FALSE, FALSE);
      return self::error('Failed to authenticate key');
    }

    $uid = NULL;
    if (!$uid) {
      $store = NULL;
      $api_key = CRM_Utils_Request::retrieve('api_key', 'String', $store, FALSE, NULL, 'REQUEST');
      if (empty($api_key)) {
        CRM_Utils_System::loadBootStrap([], FALSE, FALSE);
        return self::error("FATAL: mandatory param 'api_key' (user key) missing");
      }
      $contact_id = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $api_key, 'id', 'api_key');
      if ($contact_id) {
        $uid = CRM_Core_BAO_UFMatch::getUFId($contact_id);
      }
    }

    if ($uid && $contact_id) {
      CRM_Utils_System::loadBootStrap(['uid' => $uid], TRUE, FALSE);
      $session = CRM_Core_Session::singleton();
      $session->set('ufID', $uid);
      $session->set('userID', $contact_id);
      CRM_Core_DAO::executeQuery('SET @civicrm_user_id = %1',
        [1 => [$contact_id, 'Integer']]
      );
      return NULL;
    }
    else {
      CRM_Utils_System::loadBootStrap([], FALSE, FALSE);
      return self::error('ERROR: No CMS user associated with given api-key');
    }
  }

  /**
   * Does this request appear to be a web-service request?
   *
   * It is important to distinguish regular browser-page-loads from web-service-requests. Regular
   * page-loads can be CSRF vectors, and we don't web-services to run via CSRF.
   *
   * @return bool
   *   TRUE if the current request appears to either XMLHttpRequest or non-browser-based.
   *       Indicated by either (a) custom headers like `X-Request-With`/`X-Civi-Auth`
   *       or (b) strong-secret-params that could theoretically appear in URL bar but which
   *       cannot be meaningfully forged for CSRF purposes (like `?api_key=SECRET` or `?_authx=SECRET`).
   *   FALSE if the current request looks like a standard browser request. This request may be generated by
   *       <A HREF>, <IFRAME>, <IMG>, `Location:`, or similar CSRF vector.
   */
  public static function isWebServiceRequest(): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      return TRUE;
    }

    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? NULL) === 'XMLHttpRequest') {
      return TRUE;
    }

    // If authx is enabled, and if the user gives a credential, it will store metadata.
    $authx = \CRM_Core_Session::singleton()->get('authx');
    $allowFlows = [
      // Some flows are resistant to CSRF. Allow these:

      // <legacyrest> Current request has valid `?api_key=SECRET&key=SECRET` ==> Strong-secret params
      'legacyrest',

      // <param> Current request has valid `?_authx=SECRET` ==> Strong-secret param
      'param',

      // <xheader> Current request has valid `X-Civi-Auth:` ==> Custom header AND strong-secret param
      'xheader',

      // Other flows are not resistant to CSRF on their own (need combo w/`X-Requested-With:`).
      // Ignore these:
      // <login> Relies on a session `Cookie:` (which browsers re-send automatically).
      // <auto> First request might be resistant, but all others use session `Cookie:`.
      // <header> Browsers often retain list of credentials and re-send automatically.
    ];

    return (!empty($authx) && in_array($authx['flow'], $allowFlows));
  }

}
