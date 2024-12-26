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

use GuzzleHttp\Psr7\Response;

/**
 * System wide utilities.
 *
 * Provides a collection of Civi utilities + access to the CMS-dependant utilities
 *
 * FIXME: This is a massive and random collection that could be split into smaller services
 *
 * @method static void getCMSPermissionsUrlParams() Immediately stop script execution and display a 401 "Access Denied" page.
 * @method static mixed permissionDenied() Show access denied screen.
 * @method static string getContentTemplate(int|string $print = 0) Get the template path to render whole content.
 * @method static mixed logout() Log out the current user.
 * @method static mixed updateCategories() Clear CMS caches related to the user registration/profile forms.
 * @method static void appendBreadCrumb(array $breadCrumbs) Append an additional breadcrumb link to the existing breadcrumbs.
 * @method static void resetBreadCrumb() Reset an additional breadcrumb tag to the existing breadcrumb.
 * @method static void addHTMLHead(string $head) Append a string to the head of the HTML file.
 * @method static string postURL(int $action) Determine the post URL for a form.
 * @method static string|null getUFLocale() Get the locale of the CMS.
 * @method static bool setUFLocale(string $civicrm_language) Set the locale of the CMS.
 * @method static bool isUserLoggedIn() Check if user is logged in.
 * @method static int getLoggedInUfID() Get current logged in user id.
 * @method static void setHttpHeader(string $name, string $value) Set http header.
 * @method static array synchronizeUsers() Create CRM contacts for all existing CMS users.
 * @method static void appendCoreResources(\Civi\Core\Event\GenericHookEvent $e) Callback for hook_civicrm_coreResourceList.
 * @method static void alterAssetUrl(\Civi\Core\Event\GenericHookEvent $e) Callback for hook_civicrm_getAssetUrl.
 * @method static bool shouldExitAfterFatal() Should the current execution exit after a fatal error?
 * @method static string|null currentPath() Path of the current page e.g. 'civicrm/contact/view'
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
    $path = $path ?: CRM_Utils_System::currentPath();
    if (!$path) {
      return '';
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
        if (str_contains($qs[$i], '=')) {
          [$name, $value] = explode('=', $qs[$i]);
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
    return CRM_Core_Config::singleton()->userSystem->theme($content, $print, $maintenance);
  }

  /**
   * Generate a query string if input is an array.
   *
   * @param array|string $query
   *
   * @return string|null
   */
  public static function makeQueryString($query) {
    if (is_array($query)) {
      $buf = '';
      foreach ($query as $key => $value) {
        $buf .= ($buf ? '&' : '') . urlencode($key ?? '') . '=' . urlencode($value ?? '');
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
    $path = '',
    $query = '',
    $absolute = FALSE,
    $fragment = NULL,
    $htmlize = TRUE,
    $frontend = FALSE,
    $forceBackend = FALSE
  ) {
    // handle legacy null params
    $path ??= '';
    $query ??= '';

    $query = self::makeQueryString($query);

    // Legacy handling for when the system passes around html escaped strings
    if (str_contains($query, '&amp;')) {
      $query = html_entity_decode($query);
    }

    // Extract fragment from path or query if munged together
    if ($query && str_contains($query, '#')) {
      [$path, $fragment] = explode('#', $query);
    }
    if ($path && str_contains($path, '#')) {
      [$path, $fragment] = explode('#', $path);
    }

    // Extract query from path if munged together
    if ($path && str_contains($path, '?')) {
      [$path, $extraQuery] = explode('?', $path);
      $query = $extraQuery . ($query ? "&$query" : '');
    }

    if ($frontend === FALSE && $forceBackend === FALSE && !empty($GLOBALS['civicrm_url_defaults'])) {
      // Caller appears to want the "current://" scheme. For newer environments (eg web-service/iframe;
      // not frontend/backend), we need
      $urlObj = \Civi::url('current://' . $path)
        ->addQuery($query)
        ->addFragment($fragment)
        ->setPreferFormat($absolute ? 'absolute' : 'relative')
        ->setHtmlEscape($htmlize);
      return (string) $urlObj;
    }

    $config = CRM_Core_Config::singleton();
    $url = $config->userSystem->url($path, $query, $absolute, $fragment, $frontend, $forceBackend);

    if ($htmlize) {
      $url = htmlentities($url);
    }

    return $url;
  }

  /**
   * Return the Notification URL for Payments.
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
   *   Unused param
   * @param bool $frontend
   *   This link should be to the CMS front end (applies to WP & Joomla).
   * @param bool $forceBackend
   *   This link should be to the CMS back end (applies to WP & Joomla).
   *
   * @return string
   *   The Notification URL.
   */
  public static function getNotifyUrl(
    $path = NULL,
    $query = NULL,
    $absolute = FALSE,
    $fragment = NULL,
    $htmlize = NULL,
    $frontend = FALSE,
    $forceBackend = FALSE
  ) {
    $config = CRM_Core_Config::singleton();
    $query = self::makeQueryString($query);
    return $config->userSystem->getNotifyUrl($path, $query, $absolute, $fragment, $frontend, $forceBackend);
  }

  /**
   * Generates an extern url.
   *
   * @param string $path
   *   The extern path, such as "extern/url".
   * @param string $query
   *   A query string to append to the link.
   * @param string $fragment
   *   A fragment identifier (named anchor) to append to the link.
   * @param bool $absolute
   *   Whether to force the output to be an absolute link (beginning with a
   *   URI-scheme such as 'http:').
   * @param bool $isSSL
   *   NULL to autodetect. TRUE to force to SSL.
   *
   * @return string rawencoded URL.
   */
  public static function externUrl($path = NULL, $query = NULL, $fragment = NULL, $absolute = TRUE, $isSSL = NULL) {
    $query = self::makeQueryString($query);

    $url = Civi::paths()->getUrl("[civicrm.root]/{$path}.php", $absolute ? 'absolute' : 'relative', $isSSL)
      . ($query ? "?$query" : "")
      . ($fragment ? "#$fragment" : "");

    $parsedUrl = CRM_Utils_Url::parseUrl($url);
    $event = \Civi\Core\Event\GenericHookEvent::create([
      'url' => &$parsedUrl,
      'path' => $path,
      'query' => $query,
      'fragment' => $fragment,
      'absolute' => $absolute,
      'isSSL' => $isSSL,
    ]);
    Civi::dispatcher()->dispatch('hook_civicrm_alterExternUrl', $event);
    return urldecode(CRM_Utils_Url::unparseUrl($event->url));
  }

  /**
   * Perform any current conversions/migrations on the extern URL.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::alterExternUrl
   */
  public static function migrateExternUrl(\Civi\Core\Event\GenericHookEvent $e) {

    /**
     * $mkRouteUri is a small adapter to return generated URL as a "UriInterface".
     * @param string $path
     * @param string $query
     * @return \Psr\Http\Message\UriInterface
     */
    $mkRouteUri = function ($path, $query) use ($e) {
      $urlTxt = CRM_Utils_System::url($path, $query, $e->absolute, $e->fragment, FALSE, TRUE);
      if ($e->isSSL || ($e->isSSL === NULL && \CRM_Utils_System::isSSL())) {
        $urlTxt = str_replace('http://', 'https://', $urlTxt);
      }
      return CRM_Utils_Url::parseUrl($urlTxt);
    };

    switch (Civi::settings()->get('defaultExternUrl') . ':' . $e->path) {
      case 'router:extern/open':
        $e->url = $mkRouteUri('civicrm/mailing/open', preg_replace('/(^|&)q=/', '\1qid=', $e->query));
        break;

      case 'router:extern/url':
        $e->url = $mkRouteUri('civicrm/mailing/url', $e->query);
        break;

      case 'router:extern/widget':
        $e->url = $mkRouteUri('civicrm/contribute/widget', $e->query);
        break;

      // Otherwise, keep the default.
    }
  }

  /**
   * @deprecated
   * @see \CRM_Utils_System::currentPath
   *
   * @return string|null
   */
  public static function getUrlPath() {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Utils_System::currentPath');
    return self::currentPath();
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
   * Compose a URL. This is a wrapper for `url()` which is optimized for use in Smarty.
   *
   * @see \smarty_function_crmURL()
   * @param array $params
   *   URL properties. Keys are abbreviated ("p"<=>"path").
   *   See Smarty doc for full details.
   * @return string
   *   URL
   */
  public static function crmURL($params) {
    $p = $params['p'] ?? NULL;
    if (!isset($p)) {
      $p = self::currentPath();
    }

    return self::url(
      $p,
      $params['q'] ?? NULL,
      $params['a'] ?? FALSE,
      $params['f'] ?? NULL,
      $params['h'] ?? TRUE,
      $params['fe'] ?? FALSE,
      $params['fb'] ?? FALSE
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
    return $config->userSystem->setTitle(CRM_Utils_String::purifyHtml($title), CRM_Utils_String::purifyHtml($pageTitle));
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
    $referer = $_SERVER['HTTP_REFERER'] ?? NULL;

    if ($referer && !empty($names)) {
      foreach ($names as $name) {
        if (str_contains($referer, $name)) {
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

    $context['output'] = $_GET['snippet'] ?? NULL;
    if ($context['noindex'] ?? FALSE) {
      self::setHttpHeader('X-Robots-Tag', 'noindex');
    }
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
    self::civiExit(0, ['url' => $url, 'context' => 'redirect']);
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
    $key = trim($_REQUEST['key'] ?? '');

    $docAdd = "More info at: " . CRM_Utils_System::docURL2('sysadmin/setup/jobs', TRUE);

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

    if (!hash_equals($siteKey, $key)) {
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
      $name = trim($_REQUEST['name'] ?? '');
      $pass = trim($_REQUEST['pass'] ?? '');
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
      [$userID, $ufID, $randomNumber] = $result;
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
    $number = trim($number ?? '');
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
    $isIE = empty($_SERVER['HTTP_USER_AGENT']) ? FALSE : strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE');
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
    $mem = (float) xdebug_memory_usage() / (float) (1024);
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
        [$className, $methodName] = explode('::', $callback);
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
    $result = explode($separator, ($string ?? ''), $limit);
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
   *
   * @throws CRM_Core_Exception
   */
  public static function checkPHPVersion($ver = 5, $abort = TRUE) {
    $phpVersion = substr(PHP_VERSION, 0, 1);
    if ($phpVersion >= $ver) {
      return TRUE;
    }

    if ($abort) {
      throw new CRM_Core_Exception(ts('This feature requires PHP Version %1 or greater',
        [1 => $ver]
      ));
    }
    return FALSE;
  }

  /**
   * Encode url.
   *
   * @param string $url
   * @deprecated
   * @return null|string
   */
  public static function urlEncode($url) {
    CRM_Core_Error::deprecatedFunctionWarning('urlEncode');
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
   *
   * @throws CRM_Core_Exception
   */
  public static function version() {
    return static::versionXml()['version_no'];
  }

  public static function versionXml(): array {
    static $version;

    if (!$version) {
      $verFile = implode(DIRECTORY_SEPARATOR,
        [dirname(__FILE__), '..', '..', 'xml', 'version.xml']
      );
      if (file_exists($verFile)) {
        $str = file_get_contents($verFile);
        $xmlObj = simplexml_load_string($str);
        $version = CRM_Utils_XML::xmlObjToArray($xmlObj);
      }

      // pattern check
      if (!$version || !CRM_Utils_System::isVersionFormatValid($version['version_no'])) {
        throw new CRM_Core_Exception('Unknown codebase version.');
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
    [$a, $b] = explode('.', self::version());
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
   * Set the html header to direct robots not to index the page.
   *
   * @return void
   */
  public static function setNoRobotsFlag(): void {
    CRM_Utils_System::addHTMLHead('<META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">');
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
   */
  public static function isSSL() {
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? NULL;
    // accept 'https' (however capitalised)
    if (is_string($proto) && (strtolower($proto) === 'https')) {
      return TRUE;
    }

    $https = $_SERVER['HTTPS'] ?? NULL;
    // accept any truthy value except 'off' (however capitalised)
    if ($https && !(is_string($https) && (strtolower($https) === 'off'))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Redirect to SSL.
   *
   * @param bool|false $abort
   *
   * @throws \CRM_Core_Exception
   */
  public static function redirectToSSL($abort = FALSE) {
    $config = CRM_Core_Config::singleton();
    $req_headers = self::getRequestHeaders();
    if (Civi::settings()->get('enableSSL') && !self::isSSL()) {
      // ensure that SSL is enabled on a civicrm url (for cookie reasons etc)
      $url = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
      // @see https://lab.civicrm.org/dev/core/issues/425 if you're seeing this message.
      Civi::log()->warning('CiviCRM thinks site is not SSL, redirecting to {url}', ['url' => $url]);
      if (!self::checkURL($url, TRUE)) {
        if ($abort) {
          throw new CRM_Core_Exception('HTTPS is not set up on this machine');
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
   * Get the client's IP address.
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
    $config = CRM_Core_Config::singleton();
    $address = $config->userSystem->ipAddress();

    // hack for safari
    if ($address == '::1') {
      $address = '127.0.0.1';
    }

    // when we need to have strictly IPV4 ip address
    // convert ipV6 to ipV4
    if ($strictIPV4) {
      // this converts 'IPV4 mapped IPV6 address' to IPV4
      if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && str_contains($address, '::ffff:')) {
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
    return $_SERVER['HTTP_REFERER'] ?? NULL;
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
   * @param string|null $text
   *   (optional) Text of HTML link (no effect if $URLonly = false).
   * @param string|null $title
   *   (optional) Tooltip text for HTML link (no effect if $URLonly = false)
   * @param string|null $style
   *   (optional) Style attribute value for HTML link (no effect if $URLonly = false)
   * @param string|null $resource
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

    if (($params['resource'] ?? NULL) == 'wiki') {
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
      $params['text'] = ts('(Learn more...)');
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
    return preg_replace('#^(installation|user|sysadmin|dev)/#', '\1/en/latest/', $url);
  }

  /**
   * Exit with provided exit code.
   *
   * @param int $status
   *   (optional) Code with which to exit.
   *
   * @param array $testParameters
   */
  public static function civiExit($status = 0, $testParameters = []) {

    if (CIVICRM_UF === 'UnitTests') {
      throw new CRM_Core_Exception_PrematureExitException('civiExit called', $testParameters);
    }
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

    if (Civi\Core\Container::isContainerBooted()) {
      Civi::cache('long')->flush();
      Civi::cache('settings')->flush();
      Civi::cache('js_strings')->flush();
      Civi::cache('community_messages')->flush();
      Civi::cache('groups')->flush();
      Civi::cache('navigation')->flush();
      Civi::cache('customData')->flush();
      Civi::cache('contactTypes')->clear();
      Civi::cache('metadata')->clear();
      \Civi\Core\ClassScanner::cache('index')->flush();
      CRM_Extension_System::singleton()->getCache()->flush();
      CRM_Cxn_CiviCxnHttp::singleton()->getCache()->flush();
    }

    // also reset the various static memory caches

    // reset the memory or array cache
    Civi::cache('fields')->flush();

    // reset ACL cache
    CRM_ACL_BAO_Cache::resetCache();

    // clear asset builder folder
    \Civi::service('asset_builder')->clear(FALSE);

    // reset various static arrays used here
    CRM_Contact_BAO_Contact::$_importableFields = CRM_Contact_BAO_Contact::$_exportableFields
      = CRM_Contribute_BAO_Contribution::$_importableFields
        = CRM_Contribute_BAO_Contribution::$_exportableFields
          = CRM_Pledge_BAO_Pledge::$_exportableFields
            = CRM_Core_DAO::$_dbColumnValueCache = NULL;

    CRM_Core_OptionGroup::flushAll();
    CRM_Utils_PseudoConstant::flushAll();

    if (Civi\Core\Container::isContainerBooted()) {
      Civi::dispatcher()->dispatch('civi.core.clearcache');
    }

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
    $config->userSystem->setTimeZone();
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
   * @deprecated
   * @return string
   */
  public static function relativeURL($url) {
    CRM_Core_Error::deprecatedFunctionWarning('url');
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
    CRM_Core_Error::deprecatedFunctionWarning('url');
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
    return CRM_Core_Config::singleton()->userSystem->languageNegotiationURL($url, $addLanguagePart, $removeLanguagePart);
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
      // While it seems pointless to have a folder that's outside open_basedir
      // listed in include_path and that seems more like a configuration issue,
      // not everyone has control over the hosting provider's include_path and
      // this does happen out in the wild, so use our wrapper to avoid flooding
      // logs.
      if (CRM_Utils_File::isDir($target_dir)) {
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
   * @param string|false $url
   *
   * @return string|FALSE
   */
  public static function evalUrl($url) {
    if (!$url || strpos($url, '{') === FALSE) {
      return $url;
    }
    else {
      $config = CRM_Core_Config::singleton();
      $tsLocale = CRM_Core_I18n::getLocale();
      $vars = [
        '{ver}' => CRM_Utils_System::version(),
        '{uf}' => $config->userFramework,
        '{php}' => phpversion(),
        '{sid}' => self::getSiteID(),
        '{baseUrl}' => $config->userFrameworkBaseURL,
        '{lang}' => $tsLocale,
        '{co}' => $config->defaultContactCountry ?? '',
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
   * Is in upgrade mode.
   *
   * @return bool
   * @deprecated
   * @see CRM_Core_Config::isUpgradeMode()
   */
  public static function isInUpgradeMode() {
    return CRM_Core_Config::isUpgradeMode();
  }

  /**
   * Determine the standard URL for view/update/delete of a given entity.
   *
   * @param array $crudLinkSpec
   *   With keys:.
   *   - action: sting|int, e.g. 'update' or CRM_Core_Action::UPDATE or 'view' or CRM_Core_Action::VIEW [default: 'view']
   *   - entity|entity_table: string, eg "Contact" or "civicrm_contact"
   *   - id|entity_id: int
   *
   * @param bool $absolute whether the generated link should have an absolute (external) URL beginning with http
   *
   * @return array|NULL
   *   NULL if unavailable, or an array. array has keys:
   *   - title: string
   *   - url: string
   */
  public static function createDefaultCrudLink($crudLinkSpec, $absolute = FALSE) {
    $action = $crudLinkSpec['action'] ?? 'view';
    if (is_numeric($action)) {
      $action = CRM_Core_Action::description($action);
    }
    else {
      $action = strtolower($action);
    }

    $daoClass = isset($crudLinkSpec['entity']) ? CRM_Core_DAO_AllCoreTables::getDAONameForEntity($crudLinkSpec['entity']) : CRM_Core_DAO_AllCoreTables::getClassForTable($crudLinkSpec['entity_table']);
    $paths = $daoClass ? $daoClass::getEntityPaths() : [];
    $path = $paths[$action] ?? NULL;
    if (!$path) {
      return NULL;
    }

    if (empty($crudLinkSpec['id']) && !empty($crudLinkSpec['entity_id'])) {
      $crudLinkSpec['id'] = $crudLinkSpec['entity_id'];
    }
    foreach ($crudLinkSpec as $key => $value) {
      $path = str_replace('[' . $key . ']', $value, $path);
    }

    switch ($action) {
      case 'add':
        $title = ts('New %1', [1 => $daoClass::getEntityTitle()]);
        break;

      case 'view':
        $title = ts('View %1', [1 => $daoClass::getEntityTitle()]);
        break;

      case 'update':
        $title = ts('Edit %1', [1 => $daoClass::getEntityTitle()]);
        break;

      case 'delete':
        $title = ts('Delete %1', [1 => $daoClass::getEntityTitle()]);
        break;

      default:
        $title = ts(ucfirst($action)) . ' ' . $daoClass::getEntityTitle();
    }

    return [
      'title' => $title,
      'url' => self::url($path, NULL, $absolute, NULL, FALSE),
    ];
  }

  /**
   * Return an HTTP Response with appropriate content and status code set.
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  public static function sendResponse(\Psr\Http\Message\ResponseInterface $response) {
    $config = CRM_Core_Config::singleton()->userSystem->sendResponse($response);
  }

  /**
   * Perform any necessary actions prior to redirecting via POST.
   */
  public static function prePostRedirect() {
    CRM_Core_Config::singleton()->userSystem->prePostRedirect();
  }

  /**
   * Send an Invalid Request response
   *
   * @param string $responseMessage Response Message
   */
  public static function sendInvalidRequestResponse(string $responseMessage): void {
    self::sendResponse(new Response(400, [], $responseMessage));
  }

  public static function sendOkRequestResponse(string $message = 'OK'): void {
    self::sendResponse(new Response(200, [], $message));
  }

}
