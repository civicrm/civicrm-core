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

use Civi\Standalone\Security;
use Civi\Standalone\SessionHandler;

/**
 * Standalone specific stuff goes here.
 */
class CRM_Utils_System_Standalone extends CRM_Utils_System_Base {

  /**
   * @internal
   * @return bool
   */
  public function isLoaded(): bool {
    return TRUE;
  }

  /**
   * @inheritdoc
   */
  public function getDefaultFileStorage() {
    return [
      'url' => 'upload',
      // @todo Not sure if this is wise - what about CLI invocation?
      'path' => $_SERVER['DOCUMENT_ROOT'],
    ];
  }

  /**
   * @inheritDoc
   *
   * Create a user in the CMS.
   *
   * @param array $params keys:
   *    - 'cms_name'
   *    - 'cms_pass' plaintext password
   *    - 'notify' boolean
   * @param string $mailParam
   *   Name of the param which contains the email address.
   *   Because. Right. OK. That's what it is.
   *
   * @return int|bool
   *   uid if user was created, false otherwise
   */
  public function createUser(&$params, $mailParam) {
    return Security::singleton()->createUser($params, $mailParam);
  }

  /**
   * @inheritDoc
   */
  public function updateCMSName($ufID, $email) {
    return Security::singleton()->updateCMSName($ufID, $email);
  }

  /**
   * @inheritDoc
   */
  public function getLoginURL($destination = '') {
    $query = $destination ? ['destination' => $destination] : [];
    return CRM_Utils_System::url('civicrm/login', $query, TRUE);
  }

  /**
   * @inheritDoc
   */
  public function setTitle($title, $pageTitle = NULL) {
    if (!$pageTitle) {
      $pageTitle = $title;
    }
    $template = CRM_Core_Smarty::singleton();
    $template->assign('pageTitle', $pageTitle);
    $template->assign('docTitle', $title);
  }

  /**
   * @inheritDoc
   */
  public function appendBreadCrumb($breadcrumbs) {
    $crumbs = \Civi::$statics[__CLASS__]['breadcrumb'] ?? [];
    $crumbs += array_column($breadcrumbs, NULL, 'url');
    \Civi::$statics[__CLASS__]['breadcrumb'] = $crumbs;
    CRM_Core_Smarty::singleton()->assign('breadcrumb', array_values($crumbs));
  }

  /**
   * @inheritDoc
   */
  public function resetBreadCrumb() {
    \Civi::$statics[__CLASS__]['breadcrumb'] = [];
    CRM_Core_Smarty::singleton()->assign('breadcrumb', NULL);
  }

  /**
   * @inheritDoc
   */
  public function addHTMLHead($header) {
    $template = CRM_Core_Smarty::singleton();
    // Smarty's append function does not check for the existence of the var before appending to it.
    // So this prevents a stupid notice error:
    $template->ensureVariablesAreAssigned(['pageHTMLHead']);
    $template->append('pageHTMLHead', $header);
    return;
  }

  /**
   * @inheritDoc
   */
  public function addStyleUrl($url, $region) {
    if ($region != 'html-header') {
      return FALSE;
    }
    $this->addHTMLHead('<link rel="stylesheet" href="' . $url . '"></style>');
  }

  /**
   * @inheritDoc
   */
  public function addStyle($code, $region) {
    if ($region != 'html-header') {
      return FALSE;
    }
    $this->addHTMLHead('<style>' . $code . '</style>');
  }

  /**
   * Check if a resource url is within the public webroot and format appropriately.
   *
   * This seems to be a legacy function. We assume all resources are
   * ok directory and always return TRUE. As well, we clean up the $url.
   *
   * @todo: This is not a legacy function and the above is not a safe assumption.
   * External urls are allowed by CRM_Core_Resources and this needs to return the correct value.
   *
   * @param $url
   *
   * @return bool
   */
  public function formatResourceUrl(&$url) {
    // Remove leading slash if present.
    $url = ltrim($url, '/');

    // Remove query string — presumably added to stop intermediary caching.
    if (($pos = strpos($url, '?')) !== FALSE) {
      $url = substr($url, 0, $pos);
    }
    // @todo: Should not unconditionally return true
    return TRUE;
  }

  /**
   * Changes to the base_url should be made in settings.php directly.
   */
  public function mapConfigToSSL() {
  }

  /**
   * @inheritDoc
   */
  public function url(
    $path = '',
    $query = '',
    $absolute = FALSE,
    $fragment = NULL,
    $frontend = FALSE,
    $forceBackend = FALSE
  ) {
    $fragment = $fragment ? ('#' . $fragment) : '';
    if ($absolute) {
      return Civi::paths()->getUrl("[cms.root]/{$path}?{$query}$fragment", 'absolute');
    }
    else {
      return Civi::paths()->getUrl("[cms.root]/{$path}?{$query}$fragment");
    }
  }

  /**
   * Path of the current page e.g. 'civicrm/contact/view'
   *
   * @return string|null
   *   the current menu path
   */
  public static function currentPath() {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

    return $path ? trim($path, '/') : NULL;
  }

  /**
   * @inheritDoc
   * Authenticate the user against the CMS db.
   *
   * @param string $name
   *   The user name.
   * @param string $password
   *   The password for the above user.
   * @param bool $loadCMSBootstrap
   *   Load cms bootstrap?.
   * @param string $realPath
   *   Filename of script
   *
   * @return array|bool
   *   [contactID, ufID, unique string] else false if no auth
   * @throws \CRM_Core_Exception.
   */
  public function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    return Security::singleton()->authenticate($name, $password, $loadCMSBootstrap, $realPath);
  }

  /**
   * Determine the CMS-native ID from the user name
   *
   * In standalone this means the User ID.
   *
   * @param string $username
   * @return int|null
   */
  public function getUfId($username) {
    return Security::singleton()->getUserIDFromUsername($username);
  }

  /**
   * Immediately stop script execution, log out the user and redirect to the home page.
   *
   * @deprecated
   *   This function should be removed in favor of linking to the CMS's logout page
   */
  public function logout() {
    return Security::singleton()->logoutUser();
  }

  /**
   * @inheritDoc
   *
   * Standalone offers different HTML templates for front and back-end routes.
   *
   */
  public static function getContentTemplate($print = 0): string {
    if ($print) {
      return parent::getContentTemplate($print);
    }
    else {
      $isPublic = CRM_Utils_System::isFrontEndPage();
      return $isPublic ? 'CRM/common/standalone-frontend.tpl' : 'CRM/common/standalone.tpl';
    }
  }

  /**
   * @inheritDoc
   */
  public function theme(&$content, $print = FALSE, $maintenance = FALSE) {

    // Q. what does this do? Why do we only include this for maintenance?
    if ($maintenance) {
      $smarty = CRM_Core_Smarty::singleton();
      echo implode('', $smarty->getTemplateVars('pageHTMLHead'));
    }

    // @todo Add variables from the body tag? (for Shoreditch)
    print $content;
    return NULL;
  }

  /**
   * Bootstrap Standalone.
   *
   * This is used by cv and civix, but not I (artfulrobot) think, in the main http requests.
   *
   * @param array $params
   *   Either uid, or name & pass.
   * @param bool $loadUser
   *   Boolean Require CMS user load.
   * @param bool $throwError
   *   If true, print error on failure and exit.
   * @param bool|string $realPath path to script
   *
   * @return bool
   * @Todo Handle setting cleanurls configuration for CiviCRM?
   */
  public function loadBootStrap($params = [], $loadUser = TRUE, $throwError = TRUE, $realPath = NULL) {
    static $runOnce;

    if (!isset($runOnce)) {
      $runOnce = TRUE;
    }
    else {
      return TRUE;
    }

    global $civicrm_paths;
    require_once $civicrm_paths['civicrm.vendor']['path'] . '/autoload.php';

    // seems like we've bootstrapped drupal
    $config = CRM_Core_Config::singleton();
    $config->cleanURL = 1;

    // I don't *think* this applies to Standalone:
    //
    // we need to call the config hook again, since we now know
    // all the modules that are listening on it, does not apply
    // to J! and WP as yet
    // CRM-8655
    // CRM_Utils_Hook::config($config);

    if (!$loadUser) {
      return TRUE;
    }

    $security = \Civi\Standalone\Security::singleton();
    if (!empty($params['uid'])) {
      $user = $security->loadUserByID($params['uid']);
    }
    elseif (!empty($params['name'] && !empty($params['pass']))) {
      // It seems from looking at the Drupal implementation, that
      // if given username we expect a correct password.
      $user = $security->loadUserByName($params['name']);
      if ($user) {
        if (!$security->checkPassword($params['pass'], $user['hashed_password'] ?? '')) {
          return FALSE;
        }
      }
    }
    if (!$user) {
      return FALSE;
    }

    $security->loginAuthenticatedUserRecord($user, FALSE);

    return TRUE;
  }

  public function loadUser($username) {
    $security = \Civi\Standalone\Security::singleton();
    $user = $security->loadUserByName($username);
    if ($user) {
      $security->loginAuthenticatedUserRecord($user, TRUE);
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * @inheritdoc
   */
  public function getCiviSourceStorage(): array {
    global $civicrm_root;

    if (!defined('CIVICRM_UF_BASEURL')) {
      throw new RuntimeException('Undefined constant: CIVICRM_UF_BASEURL');
    }

    return [
      'url' => CRM_Utils_File::addTrailingSlash(CIVICRM_UF_BASEURL, '/') . 'core/',
      'path' => CRM_Utils_File::addTrailingSlash($civicrm_root),
    ];
  }

  /**
   * Determine the location of the CMS root.
   *
   * @param string $path
   *
   * @return NULL|string
   */
  public function cmsRootPath($path = NULL) {
    global $civicrm_paths;
    if (!empty($civicrm_paths['cms.root']['path'])) {
      return $civicrm_paths['cms.root']['path'];
    }
    throw new \RuntimeException("Standalone requires the path is set for now. Set \$civicrm_paths['cms.root']['path'] in civicrm.settings.php to the webroot.");
  }

  public function isFrontEndPage() {
    return CRM_Core_Menu::isPublicRoute(CRM_Utils_System::currentPath() ?? '');
  }

  /**
   * @inheritDoc
   */
  public function isUserLoggedIn() {
    return Security::singleton()->isUserLoggedIn();
  }

  /**
   * @inheritDoc
   */
  public function isUserRegistrationPermitted() {
    // We don't support user registration in Standalone.
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function isPasswordUserGenerated() {
    // @todo User management not implemented, but we should do like on WP
    // and always generate a password for the user, as part of the login process.
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function updateCategories() {
    // @todo Is anything necessary?
  }

  /**
   * @inheritDoc
   */
  public function getLoggedInUfID() {
    return Security::singleton()->getLoggedInUfID();
  }

  /**
   * @inheritDoc
   *
   * In Standalone our user object is just an array from a User::get() call.
   */
  public function getUserIDFromUserObject($user) {
    return $user['id'] ?? NULL;
  }

  /**
   * CMS User Sync doesn't make sense when using standaloneusers
   * (but leave open the door for other user extensions, which might have a sync method)
   * @return bool
   */
  public function allowSynchronizeUsers() {
    return !\CRM_Extension_System::singleton()->getManager()->isEnabled('standaloneusers');
  }

  /**
   * @inheritDoc
   */
  public function setMessage($message) {
    // @todo This function is for displaying messages on public pages
    // This might not be user-friendly enough for errors on a contribution page?
    CRM_Core_Session::setStatus('', $message, 'info');
  }

  /**
   * I don't know why this needs to be here? Does it even?
   *
   * Helper function to extract path, query and route name from Civicrm URLs.
   *
   * For example, 'civicrm/contact/view?reset=1&cid=66' will be returned as:
   *
   * ```
   * array(
   *   'path' => 'civicrm/contact/view',
   *   'route' => 'civicrm.civicrm_contact_view',
   *   'query' => array('reset' => '1', 'cid' => '66'),
   * );
   * ```
   *
   * @param string $url
   *   The url to parse.
   *
   * @return string[]
   *   The parsed url parts, containing 'path', 'route' and 'query'.
   */
  public function parseUrl($url) {
    $processed = ['path' => '', 'route_name' => '', 'query' => []];

    // Remove leading '/' if it exists.
    $url = ltrim($url, '/');

    // Separate out the url into its path and query components.
    $url = parse_url($url);
    if (empty($url['path'])) {
      return $processed;
    }
    $processed['path'] = $url['path'];

    // Create a route name by replacing the forward slashes in the path with
    // underscores, civicrm/contact/search => civicrm.civicrm_contact_search.
    $processed['route_name'] = 'civicrm.' . implode('_', explode('/', $url['path']));

    // Turn the query string (if it exists) into an associative array.
    if (!empty($url['query'])) {
      parse_str($url['query'], $processed['query']);
    }

    return $processed;
  }

  /**
   * Append any Standalone js to coreResourcesList.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public function appendCoreResources(\Civi\Core\Event\GenericHookEvent $e) {
  }

  /**
   * @inheritDoc
   */
  public function getTimeZoneString() {
    $userId = Security::singleton()->getLoggedInUfID();
    if ($userId) {
      $user = Security::singleton()->loadUserByID($userId);
      if ($user && !empty($user['timezone'])) {
        return $user['timezone'];
      }
    }
    return date_default_timezone_get();
  }

  /**
   * @inheritDoc
   */
  public function languageNegotiationURL($url, $addLanguagePart = TRUE, $removeLanguagePart = FALSE) {
    if (empty($url)) {
      return $url;
    }

    // This method is called early in the boot process.
    // Check if the extensions are available yet as our implementation requires Standaloneusers.
    // Debugging note: calling Civi::log() methods here creates a nasty crash.
    if (!class_exists(\Civi\Standalone\Security::class)) {
      return $url;
    }
    return Security::singleton()->languageNegotiationURL($url, $addLanguagePart = TRUE, $removeLanguagePart = FALSE);
  }

  /**
   * Return the CMS-specific url for its permissions page
   * @return array
   */
  public function getCMSPermissionsUrlParams() {
    return Security::singleton()->getCMSPermissionsUrlParams();
  }

  public function permissionDenied() {
    // If not logged in, they need to.
    if (CRM_Core_Session::singleton()->get('ufID')) {
      // They are logged in; they're just not allowed this page.
      CRM_Core_Error::statusBounce(ts("Access denied"), CRM_Utils_System::url('civicrm'));
    }
    else {
      http_response_code(403);

      // render a login page
      if (class_exists('CRM_Standaloneusers_Page_Login')) {
        $loginPage = new CRM_Standaloneusers_Page_Login();
        $loginPage->assign('anonAccessDenied', TRUE);
        return $loginPage->run();
      }

      throw new CRM_Core_Exception('Access denied. Standaloneusers extension not found');
    }
  }

  /**
   * Start a new session.
   */
  public function sessionStart() {
    if (defined('CIVI_SETUP')) {
      // during installation we can't use the session
      // handler from the extension yet so we just
      // use a default php session
      // use a different cookie name to avoid any nasty clash
      $session_cookie_name = 'SESSCIVISOINSTALL';
    }
    else {
      $session_handler = new SessionHandler();
      session_set_save_handler($session_handler);
      $session_cookie_name = 'SESSCIVISO';
    }

    // session lifetime in seconds (default = 24 minutes)
    $session_max_lifetime = (Civi::settings()->get('standaloneusers_session_max_lifetime') ?? 24) * 60;

    session_start([
      'cookie_httponly'  => 1,
      'cookie_secure'    => !empty($_SERVER['HTTPS']),
      'gc_maxlifetime'   => $session_max_lifetime,
      'name'             => $session_cookie_name,
      'use_cookies'      => 1,
      'use_only_cookies' => 1,
      'use_strict_mode'  => 1,
    ]);
  }

  /**
   * Standalone's session cannot be initialized until CiviCRM is booted,
   * since it is defined in an extension,
   *
   * This is also when we set timezone
   */
  public function postContainerBoot(): void {
    $sess = \CRM_Core_Session::singleton();
    $sess->initialize();

    // We want to apply timezone for this session
    // However - our implementation relies on checks against standaloneusers
    // so we need a guard if this is called in install
    //
    // Doesn't the session handler started above also need standalonusers?
    // Yes it does - but we put in some guards further into those functions
    // to use a fake session instead for this install bit.
    // Maybe they could get moved up here
    if (class_exists(\Civi\Standalone\Security::class)) {
      $sessionTime = $this->getTimeZoneString();
      date_default_timezone_set($sessionTime);
      $this->setMySQLTimeZone();
    }
  }

}
