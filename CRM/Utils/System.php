<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * System wide utilities.
 *
 * Provides a collection of Civi utilities + access to the CMS-dependant utilities
 *
 * FIXME: This is a massive and random collection that could be split into smaller services
 *
 * @method static mixed permissionDenied() Show access denied screen.
 * @method static mixed logout() Log out the current user.
 * @method static mixed updateCategories() Clear CMS caches related to the user registration/profile forms.
 * @method static appendBreadCrumb(array $breadCrumbs) Append an additional breadcrumb tag to the existing breadcrumbs.
 * @method static resetBreadCrumb() Reset an additional breadcrumb tag to the existing breadcrumb.
 * @method static addHTMLHead(string $bc) Append a string to the head of the HTML file.
 * @method static string postURL(int $action) Determine the post URL for a form.
 * @method static string|null getUFLocale() Get the locale of the CMS.
 * @method static bool setUFLocale(string $civicrm_language) Set the locale of the CMS.
 * @method static bool isUserLoggedIn() Check if user is logged in.
 * @method static int getLoggedInUfID() Get current logged in user id.
 * @method static setHttpHeader(string $name, string $value) Set http header.
 * @method static array synchronizeUsers() Create CRM contacts for all existing CMS users.
 */
class CRM_Utils_System {

  public static $_callbacks = NULL;

  /**
   * @var string
   *   Page title
   */
  public static $title = '';

  /**
   * Access methods in the appropriate CMS class
   *
   * @param $name
   * @param $arguments
   * @return mixed
   */
  public static function __callStatic($name, $arguments) {
    $userSystem = CRM_Core_Config::singleton()->userSystem;
    return call_user_func_array([$userSystem, $name], $arguments);
  }

  /**
   * Compose a new URL string from the current URL string.
   *
   * Used by all the framework components, specifically,
   * pager, sort and qfc
   *
   * @param string $urlVar
   *   The url variable being considered (i.e. crmPageID, crmSortID etc).
   * @param bool $includeReset
   *   (optional) Whether to include the reset GET string (if present).
   * @param bool $includeForce
   *   (optional) Whether to include the force GET string (if present).
   * @param string $path
   *   (optional) The path to use for the new url.
   * @param bool|string $absolute
   *   (optional) Whether to return an absolute URL.
   *
   * @return string
   *   The URL fragment.
   */
  public static function makeURL($urlVar, $includeReset = FALSE, $includeForce = TRUE, $path = NULL, $absolute = FALSE) {
    if (empty($path)) {
      $config = CRM_Core_Config::singleton();
      $path = CRM_Utils_Array::value($config->userFrameworkURLVar, $_GET);
      if (empty($path)) {
        return '';
      }
    }

    return self::url(
        $path,
        CRM_Utils_System::getLinksUrl($urlVar, $includeReset, $includeForce),
        $absolute
      );
  }

  /**
   * Get the query string and clean it up.
   *
   * Strips some variables that should not be propagated, specifically variables
   * like 'reset'. Also strips any side-affect actions (e.g. export).
   *
   * This function is copied mostly verbatim from Pager.php (_getLinksUrl)
   *
   * @param string $urlVar
   *   The URL variable being considered (e.g. crmPageID, crmSortID etc).
   * @param bool $includeReset
   *   (optional) By default this is FALSE, meaning that the reset parameter
   *   is skipped. Set to TRUE to leave the reset parameter as-is.
   * @param bool $includeForce
   *   (optional)
   * @param bool $skipUFVar
   *   (optional)
   *
   * @return string
   */
  public static function getLinksUrl($urlVar, $includeReset = FALSE, $includeForce = TRUE, $skipUFVar = TRUE) {
    // Sort out query string to prevent messy urls
    $querystring = [];
    $qs = [];
    $arrays = [];

    if (!empty($_SERVER['QUERY_STRING'])) {
      $qs = explode('&', str_replace('&amp;', '&', $_SERVER['QUERY_STRING']));
      for ($i = 0, $cnt = count($qs); $i < $cnt; $i++) {
        // check first if exist a pair
        if (strstr($qs[$i], '=') !== FALSE) {
          list($name, $value) = explode('=', $qs[$i]);
          if ($name != $urlVar) {
            $name = rawurldecode($name);
            // check for arrays in parameters: site.php?foo[]=1&foo[]=2&foo[]=3
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

    // Ok this is a big assumption but usually works
    // If we are in snippet mode, retain the 'section' param, if not, get rid
    // of it.
    if (!empty($qs['snippet'])) {
      unset($qs['snippet']);
    }
    else {
      unset($qs['section']);
    }

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

    $url = implode('&', $querystring);
    if ($urlVar) {
      $url .= (!empty($querystring) ? '&' : '') . $urlVar . '=';
    }

    return $url;
  }

  /**
   * If we are using a theming system, invoke theme, else just print the content.
   *
   * @param string $content
   *   The content that will be themed.
   * @param bool $print
   *   (optional) Are we displaying to the screen or bypassing theming?
   * @param bool $maintenance
   *   (optional) For maintenance mode.
   *
   * @return string
   */
  public static function theme(
    &$content,
    $print = FALSE,
    $maintenance = FALSE
  ) {
    $config = &CRM_Core_Config::singleton();
    return $config->userSystem->theme($content, $print, $maintenance);
  }

  /**
   * Generate a query string if input is an array.
   *
   * @param array|string $query
   *
   * @return string
   */
  public static function makeQueryString($query) {
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
   * Generate an internal CiviCRM URL.
   *
   * @param string $path
   *   The path being linked to, such as "civicrm/add".
   * @param array|string $query
   *   A query string to append to the link, or an array of key-value pairs.
   * @param bool $absolute
   *   Whether to force the output to be an absolute link (beginning with a
   *   URI-scheme such as 'http:'). Useful for links that will be displayed
   *   outside the site, such as in an RSS feed.
   * @param string $fragment
   *   A fragment identifier (named anchor) to append to the link.
   * @param bool $htmlize
   *   Whether to encode special html characters such as &.
   * @param bool $frontend
   *   This link should be to the CMS front end (applies to WP & Joomla).
   * @param bool $forceBackend
   *   This link should be to the CMS back end (applies to WP & Joomla).
   *
   * @return string
   *   An HTML string containing a link to the given path.
   */
  public static function url(
    $path = NULL,
    $query = NULL,
    $absolute = FALSE,
    $fragment = NULL,
    $htmlize = TRUE,
    $frontend = FALSE,
    $forceBackend = FALSE
  ) {
    $query = self::makeQueryString($query);

    // Legacy handling for when the system passes around html escaped strings
    if (strstr($query, '&amp;')) {
      $query = html_entity_decode($query);
    }

    // Extract fragment from path or query if munged together
    if ($query && strstr($query, '#')) {
      list($path, $fragment) = explode('#', $query);
    }
    if ($path && strstr($path, '#')) {
      list($path, $fragment) = explode('#', $path);
    }

    // Extract query from path if munged together
    if ($path && strstr($path, '?')) {
      list($path, $extraQuery) = explode('?', $path);
      $query = $extraQuery . ($query ? "&$query" : '');
    }

    $config = CRM_Core_Config::singleton();
    $url = $config->userSystem->url($path, $query, $absolute, $fragment, $frontend, $forceBackend);

    if ($htmlize) {
      $url = htmlentities($url);
    }

    return $url;
  }

  /**
   * Path of the current page e.g. 'civicrm/contact/view'
   *
   * @return string|null
   */
  public static function getUrlPath() {
    if (isset($_GET[CRM_Core_Config::singleton()->userFrameworkURLVar])) {
      return $_GET[CRM_Core_Config::singleton()->userFrameworkURLVar];
    }
    return NULL;
  }

  /**
   * Get href.
   *
   * @param string $text
   * @param string $path
   * @param string|array $query
   * @param bool $absolute
   * @param string $fragment
   * @param bool $htmlize
   * @param bool $frontend
   * @param bool $forceBackend
   *
   * @return string
   */
  public static function href(
    $text, $path = NULL, $query = NULL, $absolute = TRUE,
    $fragment = NULL, $htmlize = TRUE, $frontend = FALSE, $forceBackend = FALSE
  ) {
    $url = self::url($path, $query, $absolute, $fragment, $htmlize, $frontend, $forceBackend);
    return "<a href=\"$url\">$text</a>";
  }

  /**
   * What menu path are we currently on. Called for the primary tpl.
   *
   * @return string
   *   the current menu path
   */
  public static function currentPath() {
    $config = CRM_Core_Config::singleton();
    return trim(CRM_Utils_Array::value($config->userFrameworkURLVar, $_GET), '/');
  }

  /**
   * Called from a template to compose a url.
   *
   * @param array $params
   *   List of parameters.
   *
   * @return string
   *   url
   */
  public static function crmURL($params) {
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
   * Sets the title of the page.
   *
   * @param string $title
   *   Document title - plain text only
   * @param string $pageTitle
   *   Page title (if different) - may include html
   */
  public static function setTitle($title, $pageTitle = NULL) {
    self::$title = $title;
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->setTitle($title, $pageTitle);
  }

  /**
   * Figures and sets the userContext.
   *
   * Uses the referrer if valid else uses the default.
   *
   * @param array $names
   *   Referrer should match any str in this array.
   * @param string $default
   *   (optional) The default userContext if no match found.
   */
  public static function setUserContext($names, $default = NULL) {
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
   * Gets a class name for an object.
   *
   * @param object $object
   *   Object whose class name is needed.
   *
   * @return string
   *   The class name of the object.
   */
  public static function getClassName($object) {
    return get_class($object);
  }

  /**
   * Redirect to another URL.
   *
   * @param string $url
   *   The URL to provide to the browser via the Location header.
   * @param array $context
   *   Optional additional information for the hook.
   */
  public static function redirect($url = NULL, $context = []) {
    if (!$url) {
      $url = self::url('civicrm/dashboard', 'reset=1');
    }
    // replace the &amp; characters with &
    // this is kinda hackish but not sure how to do it right
    $url = str_replace('&amp;', '&', $url);

    $context['output'] = CRM_Utils_Array::value('snippet', $_GET);

    $parsedUrl = CRM_Utils_Url::parseUrl($url);
    CRM_Utils_Hook::alterRedirect($parsedUrl, $context);
    $url = CRM_Utils_Url::unparseUrl($parsedUrl);

    // If we are in a json context, respond appropriately
    if ($context['output'] === 'json') {
      CRM_Core_Page_AJAX::returnJsonResponse([
        'status' => 'redirect',
        'userContext' => $url,
      ]);
    }

    self::setHttpHeader('Location', $url);
    self::civiExit();
  }

  /**
   * Redirect to another URL using JavaScript.
   *
   * Use an html based file with javascript embedded to redirect to another url
   * This prevent the too many redirect errors emitted by various browsers
   *
   * @param string $url
   *   (optional) The destination URL.
   * @param string $title
   *   (optional) The page title to use for the redirect page.
   * @param string $message
   *   (optional) The message to provide in the body of the redirect page.
   */
  public static function jsRedirect(
    $url = NULL,
    $title = NULL,
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
   * Get the base URL of the system.
   *
   * @return string
   */
  public static function baseURL() {
    $config = CRM_Core_Config::singleton();
    return $config->userFrameworkBaseURL;
  }

  /**
   * Authenticate or abort.
   *
   * @param string $message
   * @param bool $abort
   *
   * @return bool
   */
  public static function authenticateAbort($message, $abort) {
    if ($abort) {
      echo $message;
      self::civiExit(0);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Authenticate key.
   *
   * @param bool $abort
   *   (optional) Whether to exit; defaults to true.
   *
   * @return bool
   */
  public static function authenticateKey($abort = TRUE) {
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

  /**
   * Authenticate script.
   *
   * @param bool $abort
   * @param string $name
   * @param string $pass
   * @param bool $storeInSession
   * @param bool $loadCMSBootstrap
   * @param bool $requireKey
   *
   * @return bool
   */
  public static function authenticateScript($abort = TRUE, $name = NULL, $pass = NULL, $storeInSession = TRUE, $loadCMSBootstrap = TRUE, $requireKey = TRUE) {
    // auth to make sure the user has a login/password to do a shell operation
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
        $config->userSystem->setUserSession([$userID, $ufID]);
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
   * Authenticate the user against the uf db.
   *
   * In case of successful authentication, returns an array consisting of
   * (contactID, ufID, unique string). Returns FALSE if authentication is
   * unsuccessful.
   *
   * @param string $name
   *   The username.
   * @param string $password
   *   The password.
   * @param bool $loadCMSBootstrap
   * @param string $realPath
   *
   * @return false|array
   */
  public static function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    $config = CRM_Core_Config::singleton();

    /* Before we do any loading, let's start the session and write to it.
     * We typically call authenticate only when we need to bootstrap the CMS
     * directly via Civi and hence bypass the normal CMS auth and bootstrap
     * process typically done in CLI and cron scripts. See: CRM-12648
     *
     * Q: Can we move this to the userSystem class so that it can be tuned
     * per-CMS? For example, when dealing with UnitTests UF, there's no
     * userFrameworkDSN.
     */
    $session = CRM_Core_Session::singleton();
    $session->set('civicrmInitSession', TRUE);

    if ($config->userFrameworkDSN) {
      $dbDrupal = DB::connect($config->userFrameworkDSN);
    }
    return $config->userSystem->authenticate($name, $password, $loadCMSBootstrap, $realPath);
  }

  /**
   * Set a message in the UF to display to a user.
   *
   * @param string $message
   *   The message to set.
   */
  public static function setUFMessage($message) {
    $config = CRM_Core_Config::singleton();
    return $config->userSystem->setMessage($message);
  }

  /**
   * Determine whether a value is null-ish.
   *
   * @param mixed $value
   *   The value to check for null.
   *
   * @return bool
   */
  public static function isNull($value) {
    // FIXME: remove $value = 'null' string test when we upgrade our DAO code to handle passing null in a better way.
    if (!isset($value) || $value === NULL || $value === '' || $value === 'null') {
      return TRUE;
    }
    if (is_array($value)) {
      // @todo Reuse of the $value variable = asking for trouble.
      foreach ($value as $key => $value) {
        if (in_array($key, CRM_Core_DAO::acceptedSQLOperators(), TRUE) || !self::isNull($value)) {
          return FALSE;
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Obscure all but the last few digits of a credit card number.
   *
   * @param string $number
   *   The credit card number to obscure.
   * @param int $keep
   *   (optional) The number of digits to preserve unmodified.
   *
   * @return string
   *   The obscured credit card number.
   */
  public static function mungeCreditCard($number, $keep = 4) {
    $number = trim($number);
    if (empty($number)) {
      return NULL;
    }
    $replace = str_repeat('*', strlen($number) - $keep);
    return substr_replace($number, $replace, 0, -$keep);
  }

  /**
   * Determine which PHP modules are loaded.
   *
   * @return array
   */
  private static function parsePHPModules() {
    ob_start();
    phpinfo(INFO_MODULES);
    $s = ob_get_contents();
    ob_end_clean();

    $s = strip_tags($s, '<h2><th><td>');
    $s = preg_replace('/<th[^>]*>([^<]+)<\/th>/', "<info>\\1</info>", $s);
    $s = preg_replace('/<td[^>]*>([^<]+)<\/td>/', "<info>\\1</info>", $s);
    $vTmp = preg_split('/(<h2>[^<]+<\/h2>)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
    $vModules = [];
    for ($i = 1; $i < count($vTmp); $i++) {
      if (preg_match('/<h2>([^<]+)<\/h2>/', $vTmp[$i], $vMat)) {
        $vName = trim($vMat[1]);
        $vTmp2 = explode("\n", $vTmp[$i + 1]);
        foreach ($vTmp2 as $vOne) {
          $vPat = '<info>([^<]+)<\/info>';
          $vPat3 = "/$vPat\s*$vPat\s*$vPat/";
          $vPat2 = "/$vPat\s*$vPat/";
          // 3cols
          if (preg_match($vPat3, $vOne, $vMat)) {
            $vModules[$vName][trim($vMat[1])] = [trim($vMat[2]), trim($vMat[3])];
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

  /**
   * Get a setting from a loaded PHP module.
   *
   * @param string $pModuleName
   * @param string $pSetting
   *
   * @return mixed
   */
  public static function getModuleSetting($pModuleName, $pSetting) {
    $vModules = self::parsePHPModules();
    return $vModules[$pModuleName][$pSetting];
  }

  /**
   * Do something no-one bothered to document.
   *
   * @param string $title
   *   (optional)
   *
   * @return mixed|string
   */
  public static function memory($title = NULL) {
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

  /**
   * Download something or other.
   *
   * @param string $name
   * @param string $mimeType
   * @param string $buffer
   * @param string $ext
   * @param bool $output
   * @param string $disposition
   */
  public static function download(
    $name, $mimeType, &$buffer,
    $ext = NULL,
    $output = TRUE,
    $disposition = 'attachment'
  ) {
    $now = gmdate('D, d M Y H:i:s') . ' GMT';

    self::setHttpHeader('Content-Type', $mimeType);
    self::setHttpHeader('Expires', $now);

    // lem9 & loic1: IE needs specific headers
    $isIE = strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE');
    if ($ext) {
      $fileString = "filename=\"{$name}.{$ext}\"";
    }
    else {
      $fileString = "filename=\"{$name}\"";
    }
    if ($isIE) {
      self::setHttpHeader("Content-Disposition", "inline; $fileString");
      self::setHttpHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
      self::setHttpHeader('Pragma', 'public');
    }
    else {
      self::setHttpHeader("Content-Disposition", "$disposition; $fileString");
      self::setHttpHeader('Pragma', 'no-cache');
    }

    if ($output) {
      print $buffer;
      self::civiExit();
    }
  }

  /**
   * Gather and print (and possibly log) amount of used memory.
   *
   * @param string $title
   * @param bool $log
   *   (optional) Whether to log the memory usage information.
   */
  public static function xMemory($title = NULL, $log = FALSE) {
    $mem = (float ) xdebug_memory_usage() / (float ) (1024);
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

  /**
   * Take a URL (or partial URL) and make it better.
   *
   * Currently, URLs pass straight through unchanged unless they are "seriously
   * malformed" (see http://us2.php.net/parse_url).
   *
   * @param string $url
   *   The URL to operate on.
   *
   * @return string
   *   The fixed URL.
   */
  public static function fixURL($url) {
    $components = parse_url($url);

    if (!$components) {
      return NULL;
    }

    // at some point we'll add code here to make sure the url is not
    // something that will mess up, so we need to clean it up here
    return $url;
  }

  /**
   * Make sure a callback is valid in the current context.
   *
   * @param string $callback
   *   Name of the function to check.
   *
   * @return bool
   */
  public static function validCallback($callback) {
    if (self::$_callbacks === NULL) {
      self::$_callbacks = [];
    }

    if (!array_key_exists($callback, self::$_callbacks)) {
      if (strpos($callback, '::') !== FALSE) {
        list($className, $methodName) = explode('::', $callback);
        $fileName = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        // ignore errors if any
        @include_once $fileName;
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
   * Like PHP's built-in explode(), but always return an array of $limit items.
   *
   * This serves as a wrapper to the PHP explode() function. In the event that
   * PHP's explode() returns an array with fewer than $limit elements, pad
   * the end of the array with NULLs.
   *
   * @param string $separator
   * @param string $string
   * @param int $limit
   *
   * @return string[]
   */
  public static function explode($separator, $string, $limit) {
    $result = explode($separator, $string, $limit);
    for ($i = count($result); $i < $limit; $i++) {
      $result[$i] = NULL;
    }
    return $result;
  }

  /**
   * Check url.
   *
   * @param string $url
   *   The URL to check.
   * @param bool $addCookie
   *   (optional)
   *
   * @return mixed
   */
  public static function checkURL($url, $addCookie = FALSE) {
    // make a GET request to $url
    $ch = curl_init($url);
    if ($addCookie) {
      curl_setopt($ch, CURLOPT_COOKIE, http_build_query($_COOKIE));
    }
    // it's quite alright to use a self-signed cert
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    // lets capture the return stuff rather than echo
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    // CRM-13227, CRM-14744: only return the SSL error status
    return (curl_exec($ch) !== FALSE);
  }

  /**
   * Assert that we are running on a particular PHP version.
   *
   * @param int $ver
   *   The major version of PHP that is required.
   * @param bool $abort
   *   (optional) Whether to fatally abort if the version requirement is not
   *   met. Defaults to TRUE.
   *
   * @return bool
   *   Returns TRUE if the requirement is met, FALSE if the requirement is not
   *   met and we're not aborting due to the failed requirement. If $abort is
   *   TRUE and the requirement fails, this function does not return.
   */
  public static function checkPHPVersion($ver = 5, $abort = TRUE) {
    $phpVersion = substr(PHP_VERSION, 0, 1);
    if ($phpVersion >= $ver) {
      return TRUE;
    }

    if ($abort) {
      CRM_Core_Error::fatal(ts('This feature requires PHP Version %1 or greater',
        [1 => $ver]
      ));
    }
    return FALSE;
  }

  /**
   * Format wiki url.
   *
   * @param string $string
   * @param bool $encode
   *
   * @return string
   */
  public static function formatWikiURL($string, $encode = FALSE) {
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

  /**
   * Encode url.
   *
   * @param string $url
   *
   * @return null|string
   */
  public static function urlEncode($url) {
    $items = parse_url($url);
    if ($items === FALSE) {
      return NULL;
    }

    if (empty($items['query'])) {
      return $url;
    }

    $items['query'] = urlencode($items['query']);

    $url = $items['scheme'] . '://';
    if (!empty($items['user'])) {
      $url .= "{$items['user']}:{$items['pass']}@";
    }

    $url .= $items['host'];
    if (!empty($items['port'])) {
      $url .= ":{$items['port']}";
    }

    $url .= "{$items['path']}?{$items['query']}";
    if (!empty($items['fragment'])) {
      $url .= "#{$items['fragment']}";
    }

    return $url;
  }

  /**
   * Return the running civicrm version.
   *
   * @return string
   *   civicrm version
   */
  public static function version() {
    static $version;

    if (!$version) {
      $verFile = implode(DIRECTORY_SEPARATOR,
        [dirname(__FILE__), '..', '..', 'xml', 'version.xml']
      );
      if (file_exists($verFile)) {
        $str = file_get_contents($verFile);
        $xmlObj = simplexml_load_string($str);
        $version = (string) $xmlObj->version_no;
      }

      // pattern check
      if (!CRM_Utils_System::isVersionFormatValid($version)) {
        CRM_Core_Error::fatal('Unknown codebase version.');
      }
    }

    return $version;
  }

  /**
   * Gives the first two parts of the version string E.g. 6.1.
   *
   * @return string
   */
  public static function majorVersion() {
    list($a, $b) = explode('.', self::version());
    return "$a.$b";
  }

  /**
   * Determines whether a string is a valid CiviCRM version string.
   *
   * @param string $version
   *   Version string to be checked.
   *
   * @return bool
   */
  public static function isVersionFormatValid($version) {
    return preg_match("/^(\d{1,2}\.){2,3}(\d{1,2}|(alpha|beta)\d{1,2})(\.upgrade)?$/", $version);
  }

  /**
   * Wraps or emulates PHP's getallheaders() function.
   */
  public static function getAllHeaders() {
    if (function_exists('getallheaders')) {
      return getallheaders();
    }

    // emulate get all headers
    // http://www.php.net/manual/en/function.getallheaders.php#66335
    $headers = [];
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

  /**
   * Get request headers.
   *
   * @return array|false
   */
  public static function getRequestHeaders() {
    if (function_exists('apache_request_headers')) {
      return apache_request_headers();
    }
    else {
      return $_SERVER;
    }
  }

  /**
   * Determine whether this is an SSL request.
   *
   * Note that we inline this function in install/civicrm.php, so if you change
   * this function, please go and change the code in the install script as well.
   */
  public static function isSSL() {
    return (isset($_SERVER['HTTPS']) &&
        !empty($_SERVER['HTTPS']) &&
        strtolower($_SERVER['HTTPS']) != 'off') ? TRUE : FALSE;
  }

  /**
   * Redirect to SSL.
   *
   * @param bool|FALSE $abort
   *
   * @throws \Exception
   */
  public static function redirectToSSL($abort = FALSE) {
    $config = CRM_Core_Config::singleton();
    $req_headers = self::getRequestHeaders();
    // FIXME: Shouldn't the X-Forwarded-Proto check be part of CRM_Utils_System::isSSL()?
    if (Civi::settings()->get('enableSSL') &&
      !self::isSSL() &&
      strtolower(CRM_Utils_Array::value('X_FORWARDED_PROTO', $req_headers)) != 'https'
    ) {
      // ensure that SSL is enabled on a civicrm url (for cookie reasons etc)
      $url = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
      // @see https://lab.civicrm.org/dev/core/issues/425 if you're seeing this message.
      Civi::log()->warning('CiviCRM thinks site is not SSL, redirecting to {url}', ['url' => $url]);
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

  /**
   * Get logged in user's IP address.
   *
   * Get IP address from HTTP REMOTE_ADDR header. If the CMS is Drupal then use
   * the Drupal function as this also handles reverse proxies (based on proper
   * configuration in settings.php)
   *
   * @param bool $strictIPV4
   *   (optional) Whether to return only IPv4 addresses.
   *
   * @return string
   *   IP address of logged in user.
   */
  public static function ipAddress($strictIPV4 = TRUE) {
    $address = CRM_Utils_Array::value('REMOTE_ADDR', $_SERVER);

    $config = CRM_Core_Config::singleton();
    if ($config->userSystem->is_drupal && function_exists('ip_address')) {
      // drupal function handles the server being behind a proxy securely. We still have legacy ipn methods
      // that reach this point without bootstrapping hence the check that the fn exists
      $address = ip_address();
    }

    // hack for safari
    if ($address == '::1') {
      $address = '127.0.0.1';
    }

    // when we need to have strictly IPV4 ip address
    // convert ipV6 to ipV4
    if ($strictIPV4) {
      // this converts 'IPV4 mapped IPV6 address' to IPV4
      if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && strstr($address, '::ffff:')) {
        $address = ltrim($address, '::ffff:');
      }
    }

    return $address;
  }

  /**
   * Get the referring / previous page URL.
   *
   * @return string
   *   The previous page URL
   */
  public static function refererPath() {
    return CRM_Utils_Array::value('HTTP_REFERER', $_SERVER);
  }

  /**
   * Get the documentation base URL.
   *
   * @return string
   *   Base URL of the CRM documentation.
   */
  public static function getDocBaseURL() {
    // FIXME: move this to configuration at some stage
    return 'https://docs.civicrm.org/';
  }

  /**
   * Returns wiki (alternate) documentation URL base.
   *
   * @return string
   *   documentation url
   */
  public static function getWikiBaseURL() {
    // FIXME: move this to configuration at some stage
    return 'http://wiki.civicrm.org/confluence/display/CRMDOC/';
  }

  /**
   * Returns URL or link to documentation page, based on provided parameters.
   *
   * For use in PHP code.
   * WARNING: Always returns URL, if ts function is not defined ($URLonly has
   * no effect).
   *
   * @param string $page
   *   Title of documentation wiki page.
   * @param bool $URLonly
   *   (optional) Whether to return URL only or full HTML link (default).
   * @param string $text
   *   (optional) Text of HTML link (no effect if $URLonly = false).
   * @param string $title
   *   (optional) Tooltip text for HTML link (no effect if $URLonly = false)
   * @param string $style
   *   (optional) Style attribute value for HTML link (no effect if $URLonly = false)
   *
   * @param null $resource
   *
   * @return string
   *   URL or link to documentation page, based on provided parameters.
   */
  public static function docURL2($page, $URLonly = FALSE, $text = NULL, $title = NULL, $style = NULL, $resource = NULL) {
    // if ts function doesn't exist, it means that CiviCRM hasn't been fully initialised yet -
    // return just the URL, no matter what other parameters are defined
    if (!function_exists('ts')) {
      if ($resource == 'wiki') {
        $docBaseURL = self::getWikiBaseURL();
      }
      else {
        $docBaseURL = self::getDocBaseURL();
        $page = self::formatDocUrl($page);
      }
      return $docBaseURL . str_replace(' ', '+', $page);
    }
    else {
      $params = [
        'page' => $page,
        'URLonly' => $URLonly,
        'text' => $text,
        'title' => $title,
        'style' => $style,
        'resource' => $resource,
      ];
      return self::docURL($params);
    }
  }

  /**
   * Returns URL or link to documentation page, based on provided parameters.
   *
   * For use in templates code.
   *
   * @param array $params
   *   An array of parameters (see CRM_Utils_System::docURL2 method for names)
   *
   * @return null|string
   *   URL or link to documentation page, based on provided parameters.
   */
  public static function docURL($params) {

    if (!isset($params['page'])) {
      return NULL;
    }

    if (CRM_Utils_Array::value('resource', $params) == 'wiki') {
      $docBaseURL = self::getWikiBaseURL();
    }
    else {
      $docBaseURL = self::getDocBaseURL();
      $params['page'] = self::formatDocUrl($params['page']);
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
      return "<a href=\"{$link}\" $style target=\"_blank\" class=\"crm-doc-link no-popup\" title=\"{$params['title']}\">{$params['text']}</a>";
    }
  }

  /**
   * Add language and version parameters to the doc url.
   *
   * Note that this function may run before CiviCRM is initialized and so should not call ts() or perform any db lookups.
   *
   * @param $url
   * @return mixed
   */
  public static function formatDocUrl($url) {
    return preg_replace('#^user/#', 'user/en/stable/', $url);
  }

  /**
   * Exit with provided exit code.
   *
   * @param int $status
   *   (optional) Code with which to exit.
   */
  public static function civiExit($status = 0) {

    if ($status > 0) {
      http_response_code(500);
    }
    // move things to CiviCRM cache as needed
    CRM_Core_Session::storeSessionObjects();

    if (Civi\Core\Container::isContainerBooted()) {
      Civi::dispatcher()->dispatch('civi.core.exit');
    }

    $userSystem = CRM_Core_Config::singleton()->userSystem;
    if (is_callable([$userSystem, 'onCiviExit'])) {
      $userSystem->onCiviExit();
    }
    exit($status);
  }

  /**
   * Reset the various system caches and some important static variables.
   */
  public static function flushCache() {
    // flush out all cache entries so we can reload new data
    // a bit aggressive, but livable for now
    CRM_Utils_Cache::singleton()->flush();

    // Traditionally, systems running on memory-backed caches were quite
    // zealous about destroying *all* memory-backed caches during a flush().
    // These flushes simulate that legacy behavior. However, they should probably
    // be removed at some point.
    $localDrivers = ['CRM_Utils_Cache_Arraycache', 'CRM_Utils_Cache_NoCache'];
    if (Civi\Core\Container::isContainerBooted()
      && !in_array(get_class(CRM_Utils_Cache::singleton()), $localDrivers)) {
      Civi::cache('long')->flush();
      Civi::cache('settings')->flush();
      Civi::cache('js_strings')->flush();
      Civi::cache('community_messages')->flush();
      CRM_Extension_System::singleton()->getCache()->flush();
      CRM_Cxn_CiviCxnHttp::singleton()->getCache()->flush();
    }

    // also reset the various static memory caches

    // reset the memory or array cache
    CRM_Core_BAO_Cache::deleteGroup('contact fields', NULL, FALSE);

    // reset ACL cache
    CRM_ACL_BAO_Cache::resetCache();

    // reset various static arrays used here
    CRM_Contact_BAO_Contact::$_importableFields = CRM_Contact_BAO_Contact::$_exportableFields
      = CRM_Contribute_BAO_Contribution::$_importableFields
        = CRM_Contribute_BAO_Contribution::$_exportableFields
          = CRM_Pledge_BAO_Pledge::$_exportableFields
            = CRM_Core_BAO_CustomField::$_importFields
              = CRM_Core_BAO_Cache::$_cache = CRM_Core_DAO::$_dbColumnValueCache = NULL;

    CRM_Core_OptionGroup::flushAll();
    CRM_Utils_PseudoConstant::flushAll();
  }

  /**
   * Load CMS bootstrap.
   *
   * @param array $params
   *   Array with uid name and pass
   * @param bool $loadUser
   *   Boolean load user or not.
   * @param bool $throwError
   * @param string $realPath
   */
  public static function loadBootStrap($params = [], $loadUser = TRUE, $throwError = TRUE, $realPath = NULL) {
    if (!is_array($params)) {
      $params = [];
    }
    $config = CRM_Core_Config::singleton();
    $result = $config->userSystem->loadBootStrap($params, $loadUser, $throwError, $realPath);
    if (is_callable([$config->userSystem, 'setMySQLTimeZone'])) {
      $config->userSystem->setMySQLTimeZone();
    }
    return $result;
  }

  /**
   * Get Base CMS url.
   *
   * @return mixed|string
   */
  public static function baseCMSURL() {
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
          ) === FALSE
        ) {
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

  /**
   * Given a URL, return a relative URL if possible.
   *
   * @param string $url
   *
   * @return string
   */
  public static function relativeURL($url) {
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

  /**
   * Produce an absolute URL from a possibly-relative URL.
   *
   * @param string $url
   * @param bool $removeLanguagePart
   *
   * @return string
   */
  public static function absoluteURL($url, $removeLanguagePart = FALSE) {
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
   * Clean url, replaces first '&' with '?'.
   *
   * @param string $url
   *
   * @return string
   *   , clean url
   */
  public static function cleanUrl($url) {
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
   * @param bool $addLanguagePart
   * @param bool $removeLanguagePart
   *
   * @return string
   *   , formatted url.
   */
  public static function languageNegotiationURL(
    $url,
    $addLanguagePart = TRUE,
    $removeLanguagePart = FALSE
  ) {
    $config = &CRM_Core_Config::singleton();
    return $config->userSystem->languageNegotiationURL($url, $addLanguagePart, $removeLanguagePart);
  }

  /**
   * Append the contents of an 'extra' smarty template file.
   *
   * It must be present in the custom template directory. This does not work if there are
   * multiple custom template directories
   *
   * @param string $fileName
   *   The name of the tpl file that we are processing.
   * @param string $content
   *   The current content string. May be modified by this function.
   * @param string $overideFileName
   *   (optional) Sent by contribution/event reg/profile pages which uses a id
   *   specific extra file name if present.
   */
  public static function appendTPLFile(
    $fileName,
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
   * Get a list of all files that are found within the directories.
   *
   * Files must be the result of appending the provided relative path to
   * each component of the PHP include path.
   *
   * @author Ken Zalewski
   *
   * @param string $relpath
   *   A relative path, typically pointing to a directory with multiple class
   *   files.
   *
   * @return array
   *   An array of files that exist in one or more of the directories that are
   *   referenced by the relative path when appended to each element of the PHP
   *   include path.
   */
  public static function listIncludeFiles($relpath) {
    $file_list = [];
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

  /**
   * Get a list of all "plugins".
   *
   * (PHP classes that implement a piece of
   * functionality using a well-defined interface) that are found in a
   * particular CiviCRM directory (both custom and core are searched).
   *
   * @author Ken Zalewski
   *
   * @param string $relpath
   *   A relative path referencing a directory that contains one or more
   *   plugins.
   * @param string $fext
   *   (optional) Only files with this extension will be considered to be
   *   plugins.
   * @param array $skipList
   *   (optional) List of files to skip.
   *
   * @return array
   *   List of plugins, where the plugin name is both the key and the value of
   *   each element.
   */
  public static function getPluginList($relpath, $fext = '.php', $skipList = []) {
    $fext_len = strlen($fext);
    $plugins = [];
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

  /**
   * Execute scheduled jobs.
   */
  public static function executeScheduledJobs() {
    $facility = new CRM_Core_JobManager();
    $facility->execute(FALSE);

    $redirectUrl = self::url('civicrm/admin/job', 'reset=1');

    CRM_Core_Session::setStatus(
      ts('Scheduled jobs have been executed according to individual timing settings. Please check log for messages.'),
      ts('Complete'), 'success');

    CRM_Utils_System::redirect($redirectUrl);
  }

  /**
   * Evaluate any tokens in a URL.
   *
   * @param string|FALSE $url
   *
   * @return string|FALSE
   */
  public static function evalUrl($url) {
    if (!$url || strpos($url, '{') === FALSE) {
      return $url;
    }
    else {
      $config = CRM_Core_Config::singleton();
      $vars = [
        '{ver}' => CRM_Utils_System::version(),
        '{uf}' => $config->userFramework,
        '{php}' => phpversion(),
        '{sid}' => self::getSiteID(),
        '{baseUrl}' => $config->userFrameworkBaseURL,
        '{lang}' => $config->lcMessages,
        '{co}' => $config->defaultContactCountry,
      ];
      return strtr($url, array_map('urlencode', $vars));
    }
  }

  /**
   * Returns the unique identifier for this site, as used by community messages.
   *
   * SiteID will be generated if it is not already stored in the settings table.
   *
   * @return string
   */
  public static function getSiteID() {
    $sid = Civi::settings()->get('site_id');
    if (!$sid) {
      $config = CRM_Core_Config::singleton();
      $sid = md5('sid_' . (defined('CIVICRM_SITE_KEY') ? CIVICRM_SITE_KEY : '') . '_' . $config->userFrameworkBaseURL);
      civicrm_api3('Setting', 'create', ['domain_id' => 'all', 'site_id' => $sid]);
    }
    return $sid;
  }

  /**
   * Determine whether this is a developmental system.
   *
   * @return bool
   */
  public static function isDevelopment() {
    static $cache = NULL;
    if ($cache === NULL) {
      global $civicrm_root;
      $cache = file_exists("{$civicrm_root}/.svn") || file_exists("{$civicrm_root}/.git");
    }
    return $cache;
  }

  /**
   * Is in upgrade mode.
   *
   * @return bool
   */
  public static function isInUpgradeMode() {
    $args = explode('/', CRM_Utils_Array::value('q', $_GET));
    $upgradeInProcess = CRM_Core_Session::singleton()->get('isUpgradePending');
    if ((isset($args[1]) && $args[1] == 'upgrade') || $upgradeInProcess) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Determine the standard URL for viewing or editing the specified link.
   *
   * This function delegates the decision-making to (a) the hook system and
   * (b) the BAO system.
   *
   * @param array $crudLinkSpec
   *   With keys:.
   *   - action: int, CRM_Core_Action::UPDATE or CRM_Core_Action::VIEW [default: VIEW]
   *   - entity_table: string, eg "civicrm_contact"
   *   - entity_id: int
   *
   * @return array|NULL
   *   NULL if unavailable, or an array. array has keys:
   *   - path: string
   *   - query: array
   *   - title: string
   *   - url: string
   */
  public static function createDefaultCrudLink($crudLinkSpec) {
    $crudLinkSpec['action'] = CRM_Utils_Array::value('action', $crudLinkSpec, CRM_Core_Action::VIEW);
    $daoClass = CRM_Core_DAO_AllCoreTables::getClassForTable($crudLinkSpec['entity_table']);
    if (!$daoClass) {
      return NULL;
    }

    $baoClass = str_replace('_DAO_', '_BAO_', $daoClass);
    if (!class_exists($baoClass)) {
      return NULL;
    }

    $bao = new $baoClass();
    $bao->id = $crudLinkSpec['entity_id'];
    if (!$bao->find(TRUE)) {
      return NULL;
    }

    $link = [];
    CRM_Utils_Hook::crudLink($crudLinkSpec, $bao, $link);
    if (empty($link) && is_callable([$bao, 'createDefaultCrudLink'])) {
      $link = $bao->createDefaultCrudLink($crudLinkSpec);
    }

    if (!empty($link)) {
      if (!isset($link['url'])) {
        $link['url'] = self::url($link['path'], $link['query'], TRUE, NULL, FALSE);
      }
      return $link;
    }

    return NULL;
  }

}
