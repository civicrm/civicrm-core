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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * System wide utilities.
 *
 */
class CRM_Utils_System {

  static $_callbacks = NULL;

  /**
   * Compose a new url string from the current url string
   * Used by all the framework components, specifically,
   * pager, sort and qfc
   *
   * @param string $urlVar the url variable being considered (i.e. crmPageID, crmSortID etc)
   *
   * @return string the url fragment
   * @access public
   */
  static function makeURL($urlVar, $includeReset = FALSE, $includeForce = TRUE, $path = NULL) {
    if (empty($path)) {
      $config = CRM_Core_Config::singleton();
      $path = CRM_Utils_Array::value($config->userFrameworkURLVar, $_GET);
      if (empty($path)) {
        return '';
      }
    }

    return self::url($path,
      CRM_Utils_System::getLinksUrl($urlVar, $includeReset, $includeForce)
    );
  }

  /**
   * get the query string and clean it up. Strip some variables that should not
   * be propagated, specically variable like 'reset'. Also strip any side-affect
   * actions (i.e. export)
   *
   * This function is copied mostly verbatim from Pager.php (_getLinksUrl)
   *
   * @param string  $urlVar       the url variable being considered (i.e. crmPageID, crmSortID etc)
   * @param boolean $includeReset should we include the reset var (generally this variable should be skipped)
   *
   * @return string
   * @access public
   */
  static function getLinksUrl($urlVar, $includeReset = FALSE, $includeForce = TRUE, $skipUFVar = TRUE) {
    // Sort out query string to prevent messy urls
    $querystring = array();
    $qs          = array();
    $arrays      = array();

    if (!empty($_SERVER['QUERY_STRING'])) {
      $qs = explode('&', str_replace('&amp;', '&', $_SERVER['QUERY_STRING']));
      for ($i = 0, $cnt = count($qs); $i < $cnt; $i++) {
        // check first if exist a pair
        if (strstr($qs[$i], '=') !== FALSE) {
          list($name, $value) = explode('=', $qs[$i]);
          if ($name != $urlVar) {
            $name = rawurldecode($name);
            //check for arrays in parameters: site.php?foo[]=1&foo[]=2&foo[]=3
            if ((strpos($name, '[') !== FALSE) &&
              (strpos($name, ']') !== FALSE)
            ) {
              $arrays[] = $qs[$i];
            }
            else {
              $qs[$name] = $value;
            }
          }
        }
        else {
          $qs[$qs[$i]] = '';
        }
        unset($qs[$i]);
      }
    }

    if ($includeForce) {
      $qs['force'] = 1;
    }

    unset($qs['snippet']);
    unset($qs['section']);

    if ($skipUFVar) {
      $config = CRM_Core_Config::singleton();
      unset($qs[$config->userFrameworkURLVar]);
    }

    foreach ($qs as $name => $value) {
      if ($name != 'reset' || $includeReset) {
        $querystring[] = $name . '=' . $value;
      }
    }

    $querystring = array_merge($querystring, array_unique($arrays));
    $querystring = array_map('htmlentities', $querystring);

    return implode('&amp;', $querystring) . (!empty($querystring) ? '&amp;' : '') . $urlVar . '=';
  }

  /**
   * if we are using a theming system, invoke theme, else just print the
   * content
   *
   * @param string  $content the content that will be themed
   * @param boolean $print   are we displaying to the screen or bypassing theming?
   * @param boolean $maintenance  for maintenance mode
   *
   * @return void           prints content on stdout
   * @access public
   * @static
   */
  static function theme(
    &$content,
    $print       = FALSE,
    $maintenance = FALSE
  ) {
    $config = &CRM_Core_Config::singleton();
    return $config->userSystem->theme($content, $print, $maintenance);
  }

  /**
   * Generate a query string if input is an array
   *
   * @param mixed $query: array or string
   * @return str
   *
   * @static
   */
  static function makeQueryString($query) {
    if (is_array($query)) {
      $buf = '';
      foreach ($query as $key => $value) {
        $buf .= ($buf ? '&' : '') . urlencode($key) . '=' . urlencode($value);
      }
      $query = $buf;
    }
    return $query;
  }

  /**
   * Generate an internal CiviCRM URL
   *
   * @param $path     string   The path being linked to, such as "civicrm/add"
   * @param $query    mixed    A query string to append to the link, or an array of key-value pairs
   * @param $absolute boolean  Whether to force the output to be an absolute link (beginning with http:).
   *                           Useful for links that will be displayed outside the site, such as in an
   *                           RSS feed.
   * @param $fragment string   A fragment identifier (named anchor) to append to the link.
   *
   * @return string            an HTML string containing a link to the given path.
   * @access public
   * @static
   */
  static function url(
    $path = NULL,
    $query    = NULL,
    $absolute = FALSE,
    $fragment = NULL,
    $htmlize  = TRUE,
    $frontend = FALSE,
    $forceBackend = FALSE
  ) {
    $query = self::makeQueryString($query);

    // we have a valid query and it has not yet been transformed
    if ($htmlize && !empty($query) && strpos($query, '&amp;') === FALSE) {
      $query = htmlentities($query);
    }

    $config = CRM_Core_Config::singleton();
    return $config->userSystem->url($path, $query, $absolute, $fragment, $htmlize, $frontend, $forceBackend);
  }

  function href($text, $path = NULL, $query = NULL, $absolute = TRUE,
    $fragment = NULL, $htmlize = TRUE, $frontend = FALSE, $forceBackend = FALSE
  ) {
    $url = self::url($path, $query, $absolute, $fragment, $htmlize, $frontend, $forceBackend);
    return "<a href=\"$url\">$text</a>";
  }

  static function permissionDenied() {
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->permissionDenied();
  }

  static function logout() {
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->logout();
  }

  // this is a very drupal specific function for now
  static function updateCategories() {
    $config = CRM_Core_Config::singleton();
    if ($config->userSystem->is_drupal) {
      $config->userSystem->updateCategories();
    }
  }

  /**
   * What menu path are we currently on. Called for the primary tpl
   *
   * @return string the current menu path
   * @access public
   */
  static function currentPath() {
    $config = CRM_Core_Config::singleton();
    return trim(CRM_Utils_Array::value($config->userFrameworkURLVar, $_GET), '/');
  }

  /**
   * this function is called from a template to compose a url
   *
   * @param array $params list of parameters
   *
   * @return string url
   * @access public
   * @static
   */
  static function crmURL($params) {
    $p = CRM_Utils_Array::value('p', $params);
    if (!isset($p)) {
      $p = self::currentPath();
    }

    return self::url(
      $p,
      CRM_Utils_Array::value('q', $params),
      CRM_Utils_Array::value('a', $params, FALSE),
      CRM_Utils_Array::value('f', $params),
      CRM_Utils_Array::value('h', $params, TRUE),
      CRM_Utils_Array::value('fe', $params, FALSE),
      CRM_Utils_Array::value('fb', $params, FALSE)
    );
  }

  /**
   * sets the title of the page
   *
   * @param string $title
   * @param string $pageTitle
   *
   * @return void
   * @access public
   * @static
   */
  static function setTitle($title, $pageTitle = NULL) {
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->setTitle($title, $pageTitle);
  }

  /**
   * figures and sets the userContext. Uses the referer if valid
   * else uses the default
   *
   * @param array  $names   refererer should match any str in this array
   * @param string $default the default userContext if no match found
   *
   * @return void
   * @access public
   */
  static function setUserContext($names, $default = NULL) {
    $url = $default;

    $session = CRM_Core_Session::singleton();
    $referer = CRM_Utils_Array::value('HTTP_REFERER', $_SERVER);

    if ($referer && !empty($names)) {
      foreach ($names as $name) {
        if (strstr($referer, $name)) {
          $url = $referer;
          break;
        }
      }
    }

    if ($url) {
      $session->pushUserContext($url);
    }
  }

  /**
   * gets a class name for an object
   *
   * @param  object $object      - object whose class name is needed
   *
   * @return string $className   - class name
   *
   * @access public
   * @static
   */
  static function getClassName($object) {
    return get_class($object);
  }

  /**
   * redirect to another url
   *
   * @param string $url the url to goto
   *
   * @return void
   * @access public
   * @static
   */
  static function redirect($url = NULL) {
    if (!$url) {
      $url = self::url('civicrm/dashboard', 'reset=1');
    }

    // replace the &amp; characters with &
    // this is kinda hackish but not sure how to do it right
    $url = str_replace('&amp;', '&', $url);
    header('Location: ' . $url);
    self::civiExit();
  }

  /**
   * use a html based file with javascript embedded to redirect to another url
   * This prevent the too many redirect errors emitted by various browsers
   *
   * @param string $url the url to goto
   *
   * @return void
   * @access public
   * @static
   */
  static function jsRedirect(
    $url     = NULL,
    $title   = NULL,
    $message = NULL
  ) {
    if (!$url) {
      $url = self::url('civicrm/dashboard', 'reset=1');
    }

    if (!$title) {
      $title = ts('CiviCRM task in progress');
    }

    if (!$message) {
      $message = ts('A long running CiviCRM task is currently in progress. This message will be refreshed till the task is completed');
    }

    // replace the &amp; characters with &
    // this is kinda hackish but not sure how to do it right
    $url = str_replace('&amp;', '&', $url);

    $template = CRM_Core_Smarty::singleton();
    $template->assign('redirectURL', $url);
    $template->assign('title', $title);
    $template->assign('message', $message);

    $html = $template->fetch('CRM/common/redirectJS.tpl');

    echo $html;

    self::civiExit();
  }

  /**
   * Append an additional breadcrumb tag to the existing breadcrumb
   *
   * @param string $title
   * @param string $url
   *
   * @return void
   * @access public
   * @static
   */
  static function appendBreadCrumb($breadCrumbs) {
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->appendBreadCrumb($breadCrumbs);
  }

  /**
   * Reset an additional breadcrumb tag to the existing breadcrumb
   *
   * @return void
   * @access public
   * @static
   */
  static function resetBreadCrumb() {
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->resetBreadCrumb();
  }

  /**
   * Append a string to the head of the html file
   *
   * @param string $head the new string to be appended
   *
   * @return void
   * @access public
   * @static
   */
  static function addHTMLHead($bc) {
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->addHTMLHead($bc);
  }

  /**
   * figure out the post url for the form
   *
   * @param the default action if one is pre-specified
   *
   * @return string the url to post the form
   * @access public
   * @static
   */
  static function postURL($action) {
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->postURL($action);
  }

  /**
   * rewrite various system urls to https
   *
   * @return void
   * access public
   * @static
   */
  static function mapConfigToSSL() {
    $config = CRM_Core_Config::singleton();
    $config->userFrameworkResourceURL = str_replace('http://', 'https://',
      $config->userFrameworkResourceURL
    );
    $config->resourceBase = $config->userFrameworkResourceURL;
    return $config->userSystem->mapConfigToSSL();
  }

  /**
   * Get the base URL from the system
   *
   * @param
   *
   * @return string
   * @access public
   * @static
   */
  static function baseURL() {
    $config = CRM_Core_Config::singleton();
    return $config->userFrameworkBaseURL;
  }

  static function authenticateAbort($message, $abort) {
    if ($abort) {
      echo $message;
      self::civiExit(0);
    }
    else {
      return FALSE;
    }
  }

  static function authenticateKey($abort = TRUE) {
    // also make sure the key is sent and is valid
    $key = trim(CRM_Utils_Array::value('key', $_REQUEST));

    $docAdd = "More info at:" . CRM_Utils_System::docURL2("Managing Scheduled Jobs", TRUE, NULL, NULL, NULL, "wiki");

    if (!$key) {
      return self::authenticateAbort(
        "ERROR: You need to send a valid key to execute this file. " . $docAdd . "\n",
        $abort
      );
    }

    $siteKey = defined('CIVICRM_SITE_KEY') ? CIVICRM_SITE_KEY : NULL;

    if (!$siteKey || empty($siteKey)) {
      return self::authenticateAbort(
        "ERROR: You need to set a valid site key in civicrm.settings.php. " . $docAdd . "\n",
        $abort
      );
    }

    if (strlen($siteKey) < 8) {
      return self::authenticateAbort(
        "ERROR: Site key needs to be greater than 7 characters in civicrm.settings.php. " . $docAdd . "\n",
        $abort
      );
    }

    if ($key !== $siteKey) {
      return self::authenticateAbort(
        "ERROR: Invalid key value sent. " . $docAdd . "\n",
        $abort
      );
    }

    return TRUE;
  }

  static function authenticateScript($abort = TRUE, $name = NULL, $pass = NULL, $storeInSession = TRUE, $loadCMSBootstrap = TRUE, $requireKey = TRUE) {
    // auth to make sure the user has a login/password to do a shell
    // operation
    // later on we'll link this to acl's
    if (!$name) {
      $name = trim(CRM_Utils_Array::value('name', $_REQUEST));
      $pass = trim(CRM_Utils_Array::value('pass', $_REQUEST));
    }

    // its ok to have an empty password
    if (!$name) {
      return self::authenticateAbort(
        "ERROR: You need to send a valid user name and password to execute this file\n",
        $abort
      );
    }

    if ($requireKey && !self::authenticateKey($abort)) {
      return FALSE;
    }

    $result = CRM_Utils_System::authenticate($name, $pass, $loadCMSBootstrap);
    if (!$result) {
      return self::authenticateAbort(
        "ERROR: Invalid username and/or password\n",
        $abort
      );
    }
    elseif ($storeInSession) {
      // lets store contact id and user id in session
      list($userID, $ufID, $randomNumber) = $result;
      if ($userID && $ufID) {
        $config = CRM_Core_Config::singleton();
        $config->userSystem->setUserSession( array($userID, $ufID) );
      }
      else {
        return self::authenticateAbort(
          "ERROR: Unexpected error, could not match userID and contactID",
          $abort
        );
      }
    }

    return $result;
  }

  /**
   * Authenticate the user against the uf db
   *
   * @param string $name     the user name
   * @param string $password the password for the above user name
   *
   * @return mixed false if no auth
   *               array(
      contactID, ufID, unique string ) if success
   * @access public
   * @static
   */
  static function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    $config = CRM_Core_Config::singleton();

    // before we do any loading, lets start the session and write to it
    // we typically call authenticate only when we need to bootstrap the CMS directly via Civi
    // and hence bypass the normal CMS auth and bootstrap process
    // typically done in cli and cron scripts
    // CRM-12648
    $session = CRM_Core_Session::singleton();
    $session->set( 'civicrmInitSession', TRUE );

    $dbDrupal = DB::connect($config->userFrameworkDSN);
    return $config->userSystem->authenticate($name, $password, $loadCMSBootstrap, $realPath);
  }

  /**
   * Set a message in the UF to display to a user
   *
   * @param string $name     the message to set
   *
   * @access public
   * @static
   */
  static function setUFMessage($message) {
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->setMessage($message);
  }



  static function isNull($value) {
    // FIXME: remove $value = 'null' string test when we upgrade our DAO code to handle passing null in a better way.
    if (!isset($value) || $value === NULL || $value === '' || $value === 'null') {
      return TRUE;
    }
    if (is_array($value)) {
      foreach ($value as $key => $value) {
        if (!self::isNull($value)) {
          return FALSE;
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  static function mungeCreditCard($number, $keep = 4) {
    $number = trim($number);
    if (empty($number)) {
      return NULL;
    }
    $replace = str_repeat('*', strlen($number) - $keep);
    return substr_replace($number, $replace, 0, -$keep);
  }

  /** parse php modules from phpinfo */
  public static function parsePHPModules() {
    ob_start();
    phpinfo(INFO_MODULES);
    $s = ob_get_contents();
    ob_end_clean();

    $s        = strip_tags($s, '<h2><th><td>');
    $s        = preg_replace('/<th[^>]*>([^<]+)<\/th>/', "<info>\\1</info>", $s);
    $s        = preg_replace('/<td[^>]*>([^<]+)<\/td>/', "<info>\\1</info>", $s);
    $vTmp     = preg_split('/(<h2>[^<]+<\/h2>)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
    $vModules = array();
    for ($i = 1; $i < count($vTmp); $i++) {
      if (preg_match('/<h2>([^<]+)<\/h2>/', $vTmp[$i], $vMat)) {
        $vName = trim($vMat[1]);
        $vTmp2 = explode("\n", $vTmp[$i + 1]);
        foreach ($vTmp2 AS $vOne) {
          $vPat  = '<info>([^<]+)<\/info>';
          $vPat3 = "/$vPat\s*$vPat\s*$vPat/";
          $vPat2 = "/$vPat\s*$vPat/";
          // 3cols
          if (preg_match($vPat3, $vOne, $vMat)) {
            $vModules[$vName][trim($vMat[1])] = array(trim($vMat[2]), trim($vMat[3]));
            // 2cols
          }
          elseif (preg_match($vPat2, $vOne, $vMat)) {
            $vModules[$vName][trim($vMat[1])] = trim($vMat[2]);
          }
        }
      }
    }
    return $vModules;
  }

  /** get a module setting */
  public static function getModuleSetting($pModuleName, $pSetting) {
    $vModules = self::parsePHPModules();
    return $vModules[$pModuleName][$pSetting];
  }

  static function memory($title = NULL) {
    static $pid = NULL;
    if (!$pid) {
      $pid = posix_getpid();
    }

    $memory = str_replace("\n", '', shell_exec("ps -p" . $pid . " -o rss="));
    $memory .= ", " . time();
    if ($title) {
      CRM_Core_Error::debug_var($title, $memory);
    }
    return $memory;
  }

  static function download($name, $mimeType, &$buffer,
    $ext = NULL,
    $output = TRUE
  ) {
    $now = gmdate('D, d M Y H:i:s') . ' GMT';

    header('Content-Type: ' . $mimeType);
    header('Expires: ' . $now);

    // lem9 & loic1: IE need specific headers
    $isIE = strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE');
    if ($ext) {
      $fileString = "filename=\"{$name}.{$ext}\"";
    }
    else {
      $fileString = "filename=\"{$name}\"";
    }
    if ($isIE) {
      header("Content-Disposition: inline; $fileString");
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Pragma: public');
    }
    else {
      header("Content-Disposition: attachment; $fileString");
      header('Pragma: no-cache');
    }

    if ($output) {
      print $buffer;
      self::civiExit();
    }
  }

  static function xMemory($title = NULL, $log = FALSE) {
    $mem = (float ) xdebug_memory_usage() / (float )(1024);
    $mem = number_format($mem, 5) . ", " . time();
    if ($log) {
      echo "<p>$title: $mem<p>";
      flush();
      CRM_Core_Error::debug_var($title, $mem);
    }
    else {
      echo "<p>$title: $mem<p>";
      flush();
    }
  }

  static function fixURL($url) {
    $components = parse_url($url);

    if (!$components) {
      return NULL;
    }

    // at some point we'll add code here to make sure the url is not
    // something that will mess up up, so we need to clean it up here
    return $url;
  }

  /**
   * make sure the callback is valid in the current context
   *
   * @param string $callback the name of the function
   *
   * @return boolean
   * @static
   */
  static function validCallback($callback) {
    if (self::$_callbacks === NULL) {
      self::$_callbacks = array();
    }

    if (!array_key_exists($callback, self::$_callbacks)) {
      if (strpos($callback, '::') !== FALSE) {
        list($className, $methodName) = explode('::', $callback);
        $fileName = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        // ignore errors if any
        @include_once ($fileName);
        if (!class_exists($className)) {
          self::$_callbacks[$callback] = FALSE;
        }
        else {
          // instantiate the class
          $object = new $className();
          if (!method_exists($object, $methodName)) {
            self::$_callbacks[$callback] = FALSE;
          }
          else {
            self::$_callbacks[$callback] = TRUE;
          }
        }
      }
      else {
        self::$_callbacks[$callback] = function_exists($callback);
      }
    }
    return self::$_callbacks[$callback];
  }

  /**
   * This serves as a wrapper to the php explode function
   * we expect exactly $limit arguments in return, and if we dont
   * get them, we pad it with null
   */
  static function explode($separator, $string, $limit) {
    $result = explode($separator, $string, $limit);
    for ($i = count($result); $i < $limit; $i++) {
      $result[$i] = NULL;
    }
    return $result;
  }

  static function checkURL($url, $addCookie = FALSE) {
    // make a GET request to $url
    $ch = curl_init($url);
    if ($addCookie) {
      curl_setopt($ch, CURLOPT_COOKIE, http_build_query($_COOKIE));
    }
    // it's quite alright to use a self-signed cert
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    // lets capture the return stuff rather than echo
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );

    return curl_exec($ch);
  }

  static function checkPHPVersion($ver = 5, $abort = TRUE) {
    $phpVersion = substr(PHP_VERSION, 0, 1);
    if ($phpVersion >= $ver) {
      return TRUE;
    }

    if ($abort) {
      CRM_Core_Error::fatal(ts('This feature requires PHP Version %1 or greater',
          array(1 => $ver)
        ));
    }
    return FALSE;
  }

  static function formatWikiURL($string, $encode = FALSE) {
    $items = explode(' ', trim($string), 2);
    if (count($items) == 2) {
      $title = $items[1];
    }
    else {
      $title = $items[0];
    }

    // fix for CRM-4044
    $url = $encode ? self::urlEncode($items[0]) : $items[0];
    return "<a href=\"$url\">$title</a>";
  }

  static function urlEncode($url) {
    $items = parse_url($url);
    if ($items === FALSE) {
      return NULL;
    }

    if (!CRM_Utils_Array::value('query', $items)) {
      return $url;
    }

    $items['query'] = urlencode($items['query']);

    $url = $items['scheme'] . '://';
    if (CRM_Utils_Array::value('user', $items)) {
      $url .= "{$items['user']}:{$items['pass']}@";
    }

    $url .= $items['host'];
    if (CRM_Utils_Array::value('port', $items)) {
      $url .= ":{$items['port']}";
    }

    $url .= "{$items['path']}?{$items['query']}";
    if (CRM_Utils_Array::value('fragment', $items)) {
      $url .= "#{$items['fragment']}";
    }

    return $url;
  }

  /**
   * Function to return the latest civicrm version.
   *
   * @return string civicrm version
   * @access public
   */
  static function version() {
    static $version;

    if (!$version) {
      $verFile = implode(DIRECTORY_SEPARATOR,
        array(dirname(__FILE__), '..', '..', 'civicrm-version.php')
      );
      if (file_exists($verFile)) {
        require_once ($verFile);
        if (function_exists('civicrmVersion')) {
          $info = civicrmVersion();
          $version = $info['version'];
        }
      }
      else {
        // svn installs don't have version.txt by default. In that case version.xml should help -
        $verFile = implode(DIRECTORY_SEPARATOR,
          array(dirname(__FILE__), '..', '..', 'xml', 'version.xml')
        );
        if (file_exists($verFile)) {
          $str     = file_get_contents($verFile);
          $xmlObj  = simplexml_load_string($str);
          $version = (string) $xmlObj->version_no;
        }
      }

      // pattern check
      if (!CRM_Utils_System::isVersionFormatValid($version)) {
        CRM_Core_Error::fatal('Unknown codebase version.');
      }
    }

    return $version;
  }

  static function isVersionFormatValid($version) {
    return preg_match("/^(\d{1,2}\.){2,3}(\d{1,2}|(alpha|beta)\d{1,2})(\.upgrade)?$/", $version);
  }

  static function getAllHeaders() {
    if (function_exists('getallheaders')) {
      return getallheaders();
    }

    // emulate get all headers
    // http://www.php.net/manual/en/function.getallheaders.php#66335
    $headers = array();
    foreach ($_SERVER as $name => $value) {
      if (substr($name, 0, 5) == 'HTTP_') {
        $headers[str_replace(' ',
          '-',
          ucwords(strtolower(str_replace('_',
                ' ',
                substr($name, 5)
              )
            ))
        )] = $value;
      }
    }
    return $headers;
  }

  static function getRequestHeaders() {
    if (function_exists('apache_request_headers')) {
      return apache_request_headers();
    }
    else {
      return $_SERVER;
    }
  }

  /**
   * Check and determine is this is an SSL request
   * Note that we inline this function in install/civicrm.php, so if
   * you change this function, please go and change the code in the install script
   */
  static function isSSL( ) {
    return
      (isset($_SERVER['HTTPS']) &&
        !empty($_SERVER['HTTPS']) &&
        strtolower($_SERVER['HTTPS']) != 'off') ? true : false;
  }

  static function redirectToSSL($abort = FALSE) {
    $config = CRM_Core_Config::singleton();
    $req_headers = self::getRequestHeaders();
    if (CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'enableSSL') &&
      !self::isSSL() &&
      strtolower(CRM_Utils_Array::value('X_FORWARDED_PROTO', $req_headers)) != 'https'
    ) {
      // ensure that SSL is enabled on a civicrm url (for cookie reasons etc)
      $url = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
      if (!self::checkURL($url, TRUE)) {
        if ($abort) {
          CRM_Core_Error::fatal('HTTPS is not set up on this machine');
        }
        else {
      CRM_Core_Session::setStatus(ts('HTTPS is not set up on this machine'), ts('Warning'), 'alert');
          // admin should be the only one following this
          // since we dont want the user stuck in a bad place
          return;
        }
      }
      CRM_Utils_System::redirect($url);
    }
  }

  /*
     * Get logged in user's IP address.
     *
     * Get IP address from HTTP Header. If the CMS is Drupal then use the Drupal function
     * as this also handles reverse proxies (based on proper configuration in settings.php)
     *
     * @return string ip address of logged in user
     */

  static function ipAddress() {
    $address = CRM_Utils_Array::value('REMOTE_ADDR', $_SERVER);

    $config = CRM_Core_Config::singleton();
    if ($config->userSystem->is_drupal) {
      //drupal function handles the server being behind a proxy securely
      return ip_address();
    }

    // hack for safari
    if ($address == '::1') {
      $address = '127.0.0.1';
    }

    return $address;
  }

  /**
   * Returns you the referring / previous page url
   *
   * @return string the previous page url
   * @access public
   */
  static function refererPath() {
    return CRM_Utils_Array::value('HTTP_REFERER', $_SERVER);
  }

  /**
   * Returns default documentation URL base
   *
   * @return string documentation url
   * @access public
   */
  static function getDocBaseURL() {
    // FIXME: move this to configuration at some stage
    return 'http://book.civicrm.org/';
  }

  /**
   * Returns wiki (alternate) documentation URL base
   *
   * @return string documentation url
   * @access public
   */
  static function getWikiBaseURL() {
    // FIXME: move this to configuration at some stage
    return 'http://wiki.civicrm.org/confluence/display/CRMDOC/';
  }

  /**
   * Returns URL or link to documentation page, based on provided parameters.
   * For use in PHP code.
   * WARNING: Always returns URL, if ts function is not defined ($URLonly has no effect).
   *
   * @param string  $page    Title of documentation wiki page
   * @param boolean $URLonly Whether function should return URL only or whole link (default)
   * @param string  $text    Text of HTML link (no effect if $URLonly = false)
   * @param string  $title   Tooltip text for HTML link (no effect if $URLonly = false)
   * @param string  $style   Style attribute value for HTML link (no effect if $URLonly = false)
   *
   * @return string URL or link to documentation page, based on provided parameters
   * @access public
   */
  static function docURL2($page, $URLonly = FALSE, $text = NULL, $title = NULL, $style = NULL, $resource = NULL) {
    // if ts function doesn't exist, it means that CiviCRM hasn't been fully initialised yet -
    // return just the URL, no matter what other parameters are defined
    if (!function_exists('ts')) {
      if ($resource == 'wiki') {
          $docBaseURL = self::getWikiBaseURL();
      } else {
        $docBaseURL = self::getDocBaseURL();
      }
      return $docBaseURL . str_replace(' ', '+', $page);
    }
    else {
      $params = array(
        'page' => $page,
        'URLonly' => $URLonly,
        'text' => $text,
        'title' => $title,
        'style' => $style,
        'resource' => $resource,
      );
      return self::docURL($params);
    }
  }

  /**
   * Returns URL or link to documentation page, based on provided parameters.
   * For use in templates code.
   *
   * @param array $params An array of parameters (see CRM_Utils_System::docURL2 method for names)
   *
   * @return string URL or link to documentation page, based on provided parameters
   * @access public
   */
  static function docURL($params) {

    if (!isset($params['page'])) {
      return;
    }

    if (CRM_Utils_Array::value('resource', $params) == 'wiki') {
      $docBaseURL = self::getWikiBaseURL();
    } else {
      $docBaseURL = self::getDocBaseURL();
    }

    if (!isset($params['title']) or $params['title'] === NULL) {
      $params['title'] = ts('Opens documentation in a new window.');
    }

    if (!isset($params['text']) or $params['text'] === NULL) {
      $params['text'] = ts('(learn more...)');
    }

    if (!isset($params['style']) || $params['style'] === NULL) {
      $style = '';
    }
    else {
      $style = "style=\"{$params['style']}\"";
    }

    $link = $docBaseURL . str_replace(' ', '+', $params['page']);

    if (isset($params['URLonly']) && $params['URLonly'] == TRUE) {
      return $link;
    }
    else {
      return "<a href=\"{$link}\" $style target=\"_blank\" title=\"{$params['title']}\">{$params['text']}</a>";
    }
  }

  /**
   * Get the locale set in the hosting CMS
   *
   * @return string  the used locale or null for none
   */
  static function getUFLocale() {
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->getUFLocale();
  }

  /**
   * Execute external or internal urls and return server response
   *
   *  @param string   $url request url
   *  @param boolean  $addCookie  should be true to access internal urls
   *
   *  @return string  $response response from url
   *  @static
   */
  static function getServerResponse($url, $addCookie = TRUE) {
    CRM_Core_Error::ignoreException();
    require_once 'HTTP/Request.php';
    $request = new HTTP_Request($url);

    if ($addCookie) {
      foreach ($_COOKIE as $name => $value) {
        $request->addCookie($name, $value);
      }
    }

    if (isset($_SERVER['AUTH_TYPE'])) {
      $request->setBasicAuth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
    }

    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'WordPress') {
      session_write_close();
    }

    $request->sendRequest();
    $response = $request->getResponseBody();

    CRM_Core_Error::setCallback();
    return $response;
  }

  static function isDBVersionValid(&$errorMessage) {
    $dbVersion = CRM_Core_BAO_Domain::version();

    if (!$dbVersion) {
      // if db.ver missing
      $errorMessage = ts('Version information found to be missing in database. You will need to determine the correct version corresponding to your current database state.');
      return FALSE;
    }
    elseif (!CRM_Utils_System::isVersionFormatValid($dbVersion)) {
      $errorMessage = ts('Database is marked with invalid version format. You may want to investigate this before you proceed further.');
      return FALSE;
    }
    elseif (stripos($dbVersion, 'upgrade')) {
      // if db.ver indicates a partially upgraded db
      $upgradeUrl = CRM_Utils_System::url("civicrm/upgrade", "reset=1");
      $errorMessage = ts('Database check failed - the database looks to have been partially upgraded. You may want to reload the database with the backup and try the <a href=\'%1\'>upgrade process</a> again.', array(1 => $upgradeUrl));
      return FALSE;
    }
    else {
      $codeVersion = CRM_Utils_System::version();

      // if db.ver < code.ver, time to upgrade
      if (version_compare($dbVersion, $codeVersion) < 0) {
        $upgradeUrl = CRM_Utils_System::url("civicrm/upgrade", "reset=1");
        $errorMessage = ts('New codebase version detected. You might want to visit <a href=\'%1\'>upgrade screen</a> to upgrade the database.', array(1 => $upgradeUrl));
        return FALSE;
      }

      // if db.ver > code.ver, sth really wrong
      if (version_compare($dbVersion, $codeVersion) > 0) {
        $errorMessage = '<p>' . ts('Your database is marked with an unexpected version number: %1. The v%2 codebase may not be compatible with your database state. You will need to determine the correct version corresponding to your current database state. You may want to revert to the codebase you were using until you resolve this problem.',
          array(1 => $dbVersion, 2 => $codeVersion)
        ) . '</p>';
        $errorMessage .= "<p>" . ts('OR if this is a manual install from git, you might want to fix civicrm-version.php file.') . "</p>";
        return FALSE;
      }
    }
    // FIXME: there should be another check to make sure version is in valid format - X.Y.alpha_num

    return TRUE;
  }

  static function civiExit($status = 0) {
    // move things to CiviCRM cache as needed
    CRM_Core_Session::storeSessionObjects();

    exit($status);
  }

  /**
   * Reset the various system caches and some important
   * static variables
   */
  static function flushCache( ) {
    // flush out all cache entries so we can reload new data
    // a bit aggressive, but livable for now
    $cache = CRM_Utils_Cache::singleton();
    $cache->flush();

    // also reset the various static memory caches

    // reset the memory or array cache
    CRM_Core_BAO_Cache::deleteGroup('contact fields', NULL, FALSE);

    // reset ACL cache
    CRM_ACL_BAO_Cache::resetCache();

    // reset various static arrays used here
    CRM_Contact_BAO_Contact::$_importableFields =
      CRM_Contact_BAO_Contact::$_exportableFields =
      CRM_Contribute_BAO_Contribution::$_importableFields =
      CRM_Contribute_BAO_Contribution::$_exportableFields =
      CRM_Pledge_BAO_Pledge::$_exportableFields =
      CRM_Contribute_BAO_Query::$_contributionFields =
      CRM_Core_BAO_CustomField::$_importFields =
      CRM_Core_DAO::$_dbColumnValueCache = NULL;

    CRM_Core_OptionGroup::flushAll();
    CRM_Utils_PseudoConstant::flushAll();
  }

  /**
   * load cms bootstrap
   *
   * @param $params   array with uid name and pass
   * @param $loadUser boolean load user or not
   */
  static function loadBootStrap($params = array(
    ), $loadUser = TRUE, $throwError = TRUE, $realPath = NULL) {
    if (!is_array($params)) {
      $params = array();
    }
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->loadBootStrap($params, $loadUser, $throwError, $realPath);
  }

  /**
   * check is user logged in.
   *
   * @return boolean.
   */
  public static function isUserLoggedIn() {
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->isUserLoggedIn();
  }

  /**
   * Get current logged in user id.
   *
   * @return int ufId, currently logged in user uf id.
   */
  public static function getLoggedInUfID() {
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->getLoggedInUfID();
  }

  static function baseCMSURL() {
    static $_baseURL = NULL;
    if (!$_baseURL) {
      $config = CRM_Core_Config::singleton();
      $_baseURL = $userFrameworkBaseURL = $config->userFrameworkBaseURL;

      if ($config->userFramework == 'Joomla') {
        // gross hack
        // we need to remove the administrator/ from the end
        $_baseURL = str_replace("/administrator/", "/", $userFrameworkBaseURL);
      }
      else {
        // Drupal setting
        global $civicrm_root;
        if (strpos($civicrm_root,
            DIRECTORY_SEPARATOR . 'sites' .
            DIRECTORY_SEPARATOR . 'all' .
            DIRECTORY_SEPARATOR . 'modules'
          ) === FALSE) {
          $startPos = strpos($civicrm_root,
            DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR
          );
          $endPos = strpos($civicrm_root,
            DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
          );
          if ($startPos && $endPos) {
            // if component is in sites/SITENAME/modules
            $siteName = substr($civicrm_root,
              $startPos + 7,
              $endPos - $startPos - 7
            );

            $_baseURL = $userFrameworkBaseURL . "sites/$siteName/";
          }
        }
      }
    }
    return $_baseURL;
  }

  static function relativeURL($url) {
    // check if url is relative, if so return immediately
    if (substr($url, 0, 4) != 'http') {
      return $url;
    }

    // make everything relative from the baseFilePath
    $baseURL = self::baseCMSURL();

    // check if baseURL is a substr of $url, if so
    // return rest of string
    if (substr($url, 0, strlen($baseURL)) == $baseURL) {
      return substr($url, strlen($baseURL));
    }

    // return the original value
    return $url;
  }

  static function absoluteURL($url, $removeLanguagePart = FALSE) {
    // check if url is already absolute, if so return immediately
    if (substr($url, 0, 4) == 'http') {
      return $url;
    }

    // make everything absolute from the baseFileURL
    $baseURL = self::baseCMSURL();

    //CRM-7622: drop the language from the URL if requested (and it’s there)
    $config = CRM_Core_Config::singleton();
    if ($removeLanguagePart) {
      $baseURL = self::languageNegotiationURL($baseURL, FALSE, TRUE);
    }

    return $baseURL . $url;
  }

  /**
   * Function to clean url, replaces first '&' with '?'
   *
   * @param string $url
   *
   * @return string $url, clean url
   * @static
   */
  static function cleanUrl($url) {
    if (!$url) {
      return NULL;
    }

    if ($pos = strpos($url, '&')) {
      $url = substr_replace($url, '?', $pos, 1);
    }

    return $url;
  }

  /**
   * Format the url as per language Negotiation.
   *
   * @param string $url
   *
   * @return string $url, formatted url.
   * @static
   */
  static function languageNegotiationURL($url,
    $addLanguagePart = TRUE,
    $removeLanguagePart = FALSE
  ) {
    $config = &CRM_Core_Config::singleton();
    return $config->userSystem->languageNegotiationURL($url, $addLanguagePart, $removeLanguagePart);
  }

  /**
   * Append the contents of an 'extra' smarty template file if it is present in
   * the custom template directory. This does not work if there are
   * multiple custom template directories
   *
   * @param string $fileName - the name of the tpl file that we are processing
   * @param string $content (by reference) - the current content string
   * @param string $overideFileName - an optional parameter which is sent by contribution/event reg/profile pages
   *               which uses a id specific extra file name if present
   *
   * @return void - the content string is modified if needed
   * @static
   */
  static function appendTPLFile($fileName,
    &$content,
    $overideFileName = NULL
  ) {
    $template = CRM_Core_Smarty::singleton();
    if ($overideFileName) {
      $additionalTPLFile = $overideFileName;
    }
    else {
      $additionalTPLFile = str_replace('.tpl', '.extra.tpl', $fileName);
    }

    if ($template->template_exists($additionalTPLFile)) {
      $content .= $template->fetch($additionalTPLFile);
    }
  }

  /**
   * Get a list of all files that are found within the directories
   * that are the result of appending the provided relative path to
   * each component of the PHP include path.
   *
   * @author Ken Zalewski
   *
   * @param string $relpath a relative path, typically pointing to
   *               a directory with multiple class files
   *
   * @return array An array of files that exist in one or more of the
   *               directories that are referenced by the relative path
   *               when appended to each element of the PHP include path
   * @access public
   */
  static function listIncludeFiles($relpath) {
    $file_list = array();
    $inc_dirs = explode(PATH_SEPARATOR, get_include_path());
    foreach ($inc_dirs as $inc_dir) {
      $target_dir = $inc_dir . DIRECTORY_SEPARATOR . $relpath;
      if (is_dir($target_dir)) {
        $cur_list = scandir($target_dir);
        foreach ($cur_list as $fname) {
          if ($fname != '.' && $fname != '..') {
            $file_list[$fname] = $fname;
          }
        }
      }
    }
    return $file_list;
  }
  // listIncludeFiles()

  /**
   * Get a list of all "plugins" (PHP classes that implement a piece of
   * functionality using a well-defined interface) that are found in a
   * particular CiviCRM directory (both custom and core are searched).
   *
   * @author Ken Zalewski
   *
   * @param string $relpath a relative path referencing a directory that
   *               contains one or more plugins
   * @param string $fext only files with this extension will be considered
   *               to be plugins
   * @param array  $skipList list of files to skip
   *
   * @return array List of plugins, where the plugin name is both the
   *               key and the value of each element.
   * @access public
   */
  static function getPluginList($relpath, $fext = '.php', $skipList = array(
    )) {
    $fext_len  = strlen($fext);
    $plugins   = array();
    $inc_files = CRM_Utils_System::listIncludeFiles($relpath);
    foreach ($inc_files as $inc_file) {
      if (substr($inc_file, 0 - $fext_len) == $fext) {
        $plugin_name = substr($inc_file, 0, 0 - $fext_len);
        if (!in_array($plugin_name, $skipList)) {
          $plugins[$plugin_name] = $plugin_name;
        }
      }
    }
    return $plugins;
  }
  // getPluginList()

  /**
   *
   * @param string $fileName - the name of the tpl file that we are processing
   * @param string $content (by reference) - the current content string
   *
   * @return void - the content string is modified if needed
   * @static
   */
  static function executeScheduledJobs() {
    $facility = new CRM_Core_JobManager();
    $facility->execute(FALSE);

    $redirectUrl = self::url('civicrm/admin/job', 'reset=1');

    CRM_Core_Session::setStatus(
      ts('Scheduled jobs have been executed according to individual timing settings. Please check log for messages.'),
      ts('Complete'), 'success');

    CRM_Utils_System::redirect($redirectUrl);
  }

  /**
   * Evaluate any tokens in a URL
   *
   * @param string|FALSE $url
   * @return string|FALSE
   */
  public static function evalUrl($url) {
    if ($url === FALSE) {
      return FALSE;
    }
    else {
      $config = CRM_Core_Config::singleton();
      $vars = array(
        '{ver}' => CRM_Utils_System::version(),
        '{uf}' => $config->userFramework,
        '{php}' => phpversion(),
        '{sid}' => md5('sid_' . (defined('CIVICRM_SITE_KEY') ? CIVICRM_SITE_KEY : '') . '_' . $config->userFrameworkBaseURL),
        '{baseUrl}' => $config->userFrameworkBaseURL,
        '{lang}' => $config->lcMessages,
        '{co}' => $config->defaultContactCountry,
      );
      foreach (array_keys($vars) as $k) {
        $vars[$k] = urlencode($vars[$k]);
      }
      return strtr($url, $vars);
    }
  }


  /**
   * Determine whether this is a developmental system.
   *
   * @return bool
   */
  static function isDevelopment() {
    static $cache = NULL;
    if ($cache === NULL) {
      global $civicrm_root;
      $cache = file_exists("{$civicrm_root}/.svn") || file_exists("{$civicrm_root}/.git");
    }
    return $cache;
  }
}

