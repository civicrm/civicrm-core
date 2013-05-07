<?php
/*
   +--------------------------------------------------------------------+
   | CiviCRM version 4.3                                                |
   +--------------------------------------------------------------------+
   | Copyright CiviCRM LLC (c) 2004-2013                                |
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

/**
 * This class handles all REST client requests.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 *
 */
class CRM_Utils_REST {

  /**
   * Number of seconds we should let a REST process idle
   * @static
   */
  static $rest_timeout = 0;

  /**
   * Cache the actual UF Class
   */
  public $ufClass;

  /**
   * Class constructor.  This caches the real user framework class locally,
   * so we can use it for authentication and validation.
   *
   * @param  string $uf       The userframework class
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
   * @param string $var   The string to be echoed
   *
   * @return string       $var
   * @access public
   */
  public static function ping($var = NULL) {
    $session = CRM_Core_Session::singleton();
    $key = $session->get('key');
    //$session->set( 'key', $var );
    return self::simple(array('message' => "PONG: $key"));
  }

  // Generates values needed for error messages
  static function error($message = 'Unknown Error') {
    $values = array(
      'error_message' => $message,
      'is_error' => 1,
    );
    return $values;
  }

  // Generates values needed for non-error responses.
  static function simple($params) {
    $values = array('is_error' => 0);
    $values += $params;
    return $values;
  }

  function run() {
    $result = self::handle();
    return self::output($result);
  }

  function bootAndRun() {
    $response = $this->loadCMSBootstrap();
    if (is_array($response)) {
      return self::output($response);
    }
    return $this->run();
  }

  static function output(&$result) {
    $hier = FALSE;
    if (is_scalar($result)) {
      if (!$result) {
        $result = 0;
      }
      $result = self::simple(array('result' => $result));
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

    if (CRM_Utils_Array::value('json', $_REQUEST)) {
      header('Content-Type: text/javascript');
      $json = json_encode(array_merge($result));
      if (CRM_Utils_Array::value('debug', $_REQUEST)) {
        return self::jsonFormated($json);
      }
      return $json;
    }


    if (isset($result['count'])) {


      $count = ' count="' . $result['count'] . '" ';


    }
    else $count = "";
    $xml = "<?xml version=\"1.0\"?>
      <ResultSet xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" $count>
      ";
    // check if this is a single element result (contact_get etc)
    // or multi element
    if ($hier) {
      foreach ($result['values'] as $n => $v) {
        $xml .= "<Result>\n" . CRM_Utils_Array::xml($v) . "</Result>\n";
      }
    }
    else {
      $xml .= "<Result>\n" . CRM_Utils_Array::xml($result) . "</Result>\n";
    }

    $xml .= "</ResultSet>\n";
    return $xml;
  }

  static function jsonFormated($json) {
    $tabcount   = 0;
    $result     = '';
    $inquote    = FALSE;
    $inarray    = FALSE;
    $ignorenext = FALSE;

    $tab = "\t";
    $newline = "\n";

    for ($i = 0; $i < strlen($json); $i++) {
      $char = $json[$i];

      if ($ignorenext) {
        $result .= $char;
        $ignorenext = FALSE;
      }
      else {
        switch ($char) {
          case '{':
            if ($inquote) {
              $result .= $char;
            }
            else {
              $inarray = FALSE;
              $tabcount++;
              $result .= $char . $newline . str_repeat($tab, $tabcount);
            }
            break;

          case '}':
            if ($inquote) {
              $result .= $char;
            }
            else {
              $tabcount--;
              $result = trim($result) . $newline . str_repeat($tab, $tabcount) . $char;
            }
            break;

          case ',':
            if ($inquote || $inarray) {
              $result .= $char;
            }
            else $result .= $char . $newline . str_repeat($tab, $tabcount);
            break;

          case '"':
            $inquote = !$inquote;
            $result .= $char;
            break;

          case '\\':
            if ($inquote) {
              $ignorenext = TRUE;
            }
            $result .= $char;
            break;

          case '[':
            $inarray = TRUE;
            $result .= $char;
            break;

          case ']':
            $inarray = FALSE;
            $result .= $char;
            break;

          default:
            $result .= $char;
        }
      }
    }

    return $result;
  }

  static function handle() {
    // Get the function name being called from the q parameter in the query string
    $q = CRM_Utils_array::value('q', $_REQUEST);
    // or for the rest interface, from fnName
    $r = CRM_Utils_array::value('fnName', $_REQUEST);
    if (!empty($r)) {
      $q = $r;
    }
    if (!empty($q)) {
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
      $store = NULL;
      
      if ($args[1] == 'ping') {
        return self::ping();
      }
    } else {
      // or the new format (entity+action)
      $args = array();
      $args[0] = 'civicrm';
      $args[1] = CRM_Utils_array::value('entity', $_REQUEST);
      $args[2] = CRM_Utils_array::value('action', $_REQUEST);
    }
 

    // Everyone should be required to provide the server key, so the whole
    //  interface can be disabled in more change to the configuration file.
    // first check for civicrm site key
    if (!CRM_Utils_System::authenticateKey(FALSE)) {
      $docLink = CRM_Utils_System::docURL2("Managing Scheduled Jobs", TRUE, NULL, NULL, NULL, "wiki");
      $key = CRM_Utils_array::value('key', $_REQUEST);
      if (empty($key)) {
        return self::error("FATAL: mandatory param 'key' missing. More info at: " . $docLink);
      }
      return self::error("FATAL: 'key' is incorrect. More info at: " . $docLink);
    }


    // At this point we know we are not calling ping which does not require authentication.
    //  Therefore, at this point we need to make sure we're working with a trusted user.
    //  Valid users are those who provide a valid server key and API key

    $valid_user = FALSE;

    // Check and see if a valid secret API key is provided.
    $api_key = CRM_Utils_Request::retrieve('api_key', 'String', $store, FALSE, NULL, 'REQUEST');
    if (!$api_key || strtolower($api_key) == 'null') {
      return self::error("FATAL: mandatory param 'api_key' (user key) missing");
    }
    $valid_user = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $api_key, 'id', 'api_key');

    // If we didn't find a valid user, die
    if (empty($valid_user)) {
      return self::error("User API key invalid");
    }

    return self::process($args);
  }

  static function process(&$args, $restInterface = TRUE) {
    $params = &self::buildParamList();

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

      return call_user_func(array($params['className'], $params['fnName']), $params);
    }

    if (!array_key_exists('version', $params)) {
      $params['version'] = 3;
    }

    if ($params['version'] == 2) {
      $result['is_error'] = 1;
      $result['error_message'] = "FATAL: API v2 not accessible from ajax/REST";
      $result['deprecated'] = "Please upgrade to API v3";
      return $result;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'GET' && strtolower(substr( $args[2],0,3)) != 'get') {
      // get only valid for non destructive methods
      require_once 'api/v3/utils.php';
      return civicrm_api3_create_error("SECURITY: All requests that modify the database must be http POST, not GET.",
        array(
          'IP' => $_SERVER['REMOTE_ADDR'],
          'level' => 'security',
          'referer' => $_SERVER['HTTP_REFERER'],
          'reason' => 'Destructive HTTP GET',
        )
      );
    }

    // trap all fatal errors
    CRM_Core_Error::setCallback(array('CRM_Utils_REST', 'fatal'));
    $result = civicrm_api($args[1], $args[2], $params);
    CRM_Core_Error::setCallback();

    if ($result === FALSE) {
      return self::error('Unknown error.');
    }
    return $result;
  }

  static function &buildParamList() {
    $params = array();

    $skipVars = array(
      'q' => 1,
      'json' => 1,
      'key' => 1,
      'api_key' => 1,
      'entity' => 1,
      'action' => 1,
    );

    if (array_key_exists('json', $_REQUEST) &&  $_REQUEST['json'][0] == "{") {
      $params = json_decode($_REQUEST['json'], TRUE);
      if(empty($params)) {
        echo json_encode(array('is_error' => 1, 'error_message', 'Unable to decode supplied JSON.'));
        CRM_Utils_System::civiExit();
      }
    }
    foreach ($_REQUEST as $n => $v) {
      if (!array_key_exists($n, $skipVars)) {
        $params[$n] = $v;
      }
    }
    if (array_key_exists('return', $_REQUEST) && is_array($_REQUEST['return'])) {
      foreach ($_REQUEST['return'] as $key => $v) $params['return.' . $key] = 1;
    }
    return $params;
  }

  static function fatal($pearError) {
    header('Content-Type: text/xml');
    $error = array();
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

  static function APIDoc() {

    CRM_Utils_System::setTitle("API Parameters");
    $template = CRM_Core_Smarty::singleton();
    return CRM_Utils_System::theme(
      $template->fetch('CRM/Core/APIDoc.tpl')
    );
  }

  static function ajaxDoc() {
    return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/api/explorer'));
  }

  /** used to load a template "inline", eg. for ajax, without having to build a menu for each template */
  static  function loadTemplate () {
    $request = CRM_Utils_Request::retrieve( 'q', 'String');
    if (false !== strpos($request, '..')) {
      die ("SECURITY FATAL: the url can't contain '..'. Please report the issue on the forum at civicrm.org");
    }

    $request = split ('/',$request);
    $entity = _civicrm_api_get_camel_name($request[2]);
    $tplfile=_civicrm_api_get_camel_name($request[3]);

    $tpl = 'CRM/'.$entity.'/Page/Inline/'.$tplfile.'.tpl';
    $smarty= CRM_Core_Smarty::singleton( );
    CRM_Utils_System::setTitle( "$entity::$tplfile inline $tpl" );
    if( !$smarty->template_exists($tpl) ){
      header("Status: 404 Not Found");
      die ("Can't find the requested template file templates/$tpl");
    }
    if (array_key_exists('id',$_GET)) {// special treatmenent, because it's often used
      $smarty->assign ('id',(int)$_GET['id']);// an id is always positive
    }
    $pos = strpos (implode (array_keys ($_GET)),'<') ;

    if ($pos !== false) {
      die ("SECURITY FATAL: one of the param names contains &lt;");
    }
    $param = array_map( 'htmlentities' , $_GET);
    unset($param['q']);
    $smarty->assign_by_ref("request", $param);

    if  ( ! array_key_exists ( 'HTTP_X_REQUESTED_WITH', $_SERVER ) ||
      $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest"  )  {

        $smarty->assign( 'tplFile', $tpl );
        $config = CRM_Core_Config::singleton();
        $content = $smarty->fetch( 'CRM/common/'. strtolower($config->userFramework) .'.tpl' );

        if (!defined('CIVICRM_UF_HEAD') && $region = CRM_Core_Region::instance('html-header', FALSE)) {
          CRM_Utils_System::addHTMLHead($region->render(''));
        }
        CRM_Utils_System::appendTPLFile( $tpl, $content );

        return CRM_Utils_System::theme($content);

      } else {
        $content = "<!-- .tpl file embeded: $tpl -->\n";
        CRM_Utils_System::appendTPLFile( $tpl, $content );
        echo $content . $smarty->fetch ($tpl);
        CRM_Utils_System::civiExit( );
    }
  }

  /** This is a wrapper so you can call an api via json (it returns json too)
   * http://example.org/civicrm/api/json?entity=Contact&action=Get"&json={"contact_type":"Individual","email.get.email":{}} to take all the emails from individuals
   * works for POST & GET (POST recommended)
   **/
  static function ajaxJson() {
    require_once 'api/v3/utils.php';
    if (!$config->debug && (!array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) ||
        $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest"
      )) {
      $error = civicrm_api3_create_error("SECURITY ALERT: Ajax requests can only be issued by javascript clients, eg. CRM.api().",
        array(
          'IP' => $_SERVER['REMOTE_ADDR'],
          'level' => 'security',
          'referer' => $_SERVER['HTTP_REFERER'],
          'reason' => 'CSRF suspected',
        )
      );
      echo json_encode($error);
      CRM_Utils_System::civiExit();
    }
    if (empty($_REQUEST['entity'])) {
      echo json_encode(civicrm_api3_create_error('missing entity param'));
      CRM_Utils_System::civiExit();
    }
    if (empty($_REQUEST['entity'])) {
      echo json_encode(civicrm_api3_create_error('missing entity entity'));
      CRM_Utils_System::civiExit();
    }
    if (!empty($_REQUEST['json'])) {
      $params = json_decode($_REQUEST['json'], TRUE);
    }
    $entity = CRM_Utils_String::munge(CRM_Utils_Array::value('entity', $_REQUEST));
    $action = CRM_Utils_String::munge(CRM_Utils_Array::value('action', $_REQUEST));
    if (!is_array($params)) {
      echo json_encode(array('is_error' => 1, 'error_message', 'invalid json format: ?{"param_with_double_quote":"value"}'));
      CRM_Utils_System::civiExit();
    }

    $params['check_permissions'] = TRUE;
    $params['version'] = 3;
    $_REQUEST['json'] = 1;
    if (!$params['sequential']) {
      $params['sequential'] = 1;
    }
    // trap all fatal errors
    CRM_Core_Error::setCallback(array('CRM_Utils_REST', 'fatal'));
    $result = civicrm_api($entity, $action, $params);

    CRM_Core_Error::setCallback();

    echo self::output($result);

    CRM_Utils_System::civiExit();
  }

  static function ajax() {
    // this is driven by the menu system, so we can use permissioning to
    // restrict calls to this etc
    // the request has to be sent by an ajax call. First line of protection against csrf
    $config = CRM_Core_Config::singleton();
    if (!$config->debug &&
      (!array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) ||
        $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest"
      )
    ) {
      require_once 'api/v3/utils.php';
      $error = civicrm_api3_create_error("SECURITY ALERT: Ajax requests can only be issued by javascript clients, eg. CRM.api().",
        array(
          'IP' => $_SERVER['REMOTE_ADDR'],
          'level' => 'security',
          'referer' => $_SERVER['HTTP_REFERER'],
          'reason' => 'CSRF suspected',
        )
      );
      echo json_encode($error);
      CRM_Utils_System::civiExit();
    }

    $q = CRM_Utils_Array::value('fnName', $_REQUEST);
    if (!$q) {
      $entity = CRM_Utils_Array::value('entity', $_REQUEST);
      $action = CRM_Utils_Array::value('action', $_REQUEST);
      if (!$entity || !$action) {
        $err = array('error_message' => 'missing mandatory params "entity=" or "action="', 'is_error' => 1);
        echo self::output($err);
        CRM_Utils_System::civiExit();
      }
      $args = array('civicrm', $entity, $action);
    }
    else {
      $args = explode('/', $q);
    }

    // get the class name, since all ajax functions pass className
    $className = CRM_Utils_Array::value('className', $_REQUEST);

    // If the function isn't in the civicrm namespace, reject the request.
    if (($args[0] != 'civicrm' && count($args) != 3) && !$className) {
      return self::error('Unknown function invocation.');
    }

    $result = self::process($args, FALSE);

    echo self::output($result);

    CRM_Utils_System::civiExit();
  }

  /**
   * @return array|NULL NULL if execution should proceed; array if the response is already known
   */
  function loadCMSBootstrap() {
    $q = CRM_Utils_array::value('q', $_REQUEST);
    $args = explode('/', $q);

    // Proceed with bootstrap for "?entity=X&action=Y"
    // Proceed with bootstrap for "?q=civicrm/X/Y" but not "?q=civicrm/ping"
    if (!empty($q)) {
      if (count($args) == 2 && $args[1] == 'ping') {
        return NULL; // this is pretty wonky but maybe there's some reason I can't see
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
      // FIXME: At time of writing, this doesn't actually do anything because
      // authenticateKey abends, but that's a bad behavior which sends a
      // malformed response.
      return self::error('Failed to authenticate key');
    }

    $uid = NULL;
    if (!$uid) {
      $store      = NULL;
      $api_key    = CRM_Utils_Request::retrieve('api_key', 'String', $store, FALSE, NULL, 'REQUEST');
      if (empty($api_key)) {
        return self::error("FATAL: mandatory param 'api_key' (user key) missing");
      }
      $contact_id = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $api_key, 'id', 'api_key');
      if ($contact_id) {
        $uid = CRM_Core_BAO_UFMatch::getUFId($contact_id);
      }
    }

    if ($uid) {
      CRM_Utils_System::loadBootStrap(array('uid' => $uid), TRUE, FALSE);
      return NULL;
    }
    else {
      return self::error('ERROR: No CMS user associated with given api-key');
    }
  }
}

