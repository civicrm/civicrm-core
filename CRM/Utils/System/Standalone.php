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

  public function missingStandaloneExtension() {
    // error_log("sessionStart, " . (class_exists(\Civi\Standalone\Security::class) ? 'exists' : 'no ext'));
    return !class_exists(\Civi\Standalone\Security::class);
  }

  public function initialize() {
    parent::initialize();
    // Initialize the session if it looks like there might be one.
    // Case 1: user sends no session cookie: do NOT start the session. May be anon access that does not require session data. Good for caching.
    // Case 2: user sends a session cookie: start the session, so we can access data like lcMessages for localization (which occurs early in the boot process).
    // Case 3: user sends a session cookie but it's invalid: start the session, it will be empty and a new cookie will be sent.
    if (isset($_COOKIE['PHPSESSID'])) {
      // Note: passing $isRead = FALSE in the arguments will cause the session to be started.
      CRM_Core_Session::singleton()->initialize(FALSE);
    }
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
    if ($this->missingStandaloneExtension()) {
      return FALSE;
    }
    return Security::singleton()->createUser($params, $mailParam);
  }

  /**
   * @inheritDoc
   */
  public function updateCMSName($ufID, $email) {
    if ($this->missingStandaloneExtension()) {
      return FALSE;
    }
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
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = trim($path, '/');
    return $path;
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
    if ($this->missingStandaloneExtension()) {
      return FALSE;
    }
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
    if ($this->missingStandaloneExtension()) {
      return NULL;
    }
    return Security::singleton()->getUserIDFromUsername($username);
  }

  /**
   * Immediately stop script execution, log out the user and redirect to the home page.
   *
   * @deprecated
   *   This function should be removed in favor of linking to the CMS's logout page
   */
  public function logout() {
    if ($this->missingStandaloneExtension()) {
      return;
    }
    return Security::singleton()->logoutUser();
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

    if ($this->missingStandaloneExtension()) {
      return FALSE;
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
      'url' => CRM_Utils_File::addTrailingSlash(CIVICRM_UF_BASEURL, '/') . '/core/',
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

  /**
   * @inheritDoc
   */
  public function isUserLoggedIn() {
    if ($this->missingStandaloneExtension()) {
      return TRUE;
    }
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

    if ($this->missingStandaloneExtension()) {
      // This helps towards getting the CiviCRM menu to display
      return 1;
    }
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
   * @inheritDoc
   */
  public function synchronizeUsers() {
    if ($this->missingStandaloneExtension()) {
      return parent::synchronizeUsers();
    }
    return Security::singleton()->synchronizeUsers();
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
    $timezone = date_default_timezone_get();
    $userId = Security::singleton()->getLoggedInUfID();
    if ($userId) {
      $user = Security::singleton()->loadUserByID($userId);
      if ($user && !empty($user['timezone'])) {
        $timezone = $user['timezone'];
      }
    }
    return $timezone;
  }

  /**
   * @inheritDoc
   */
  public function languageNegotiationURL($url, $addLanguagePart = TRUE, $removeLanguagePart = FALSE) {
    if (empty($url)) {
      return $url;
    }

    // Notice: we CANNOT call log here, it creates a nasty crash.
    // \Civi::log()->warning("Standalone languageNegotiationURL is not written, but was called");
    if ($this->missingStandaloneExtension()) {
      return $url;
    }
    return Security::singleton()->languageNegotiationURL($url, $addLanguagePart = TRUE, $removeLanguagePart = FALSE);
  }

  /**
   * Return the CMS-specific url for its permissions page
   * @return array
   */
  public function getCMSPermissionsUrlParams() {
    if ($this->missingStandaloneExtension()) {
      return ['ufAccessURL' => '/civicrm/admin/roles'];
    }
    return Security::singleton()->getCMSPermissionsUrlParams();
  }

  public function permissionDenied() {
    // If not logged in, they need to.
    if (CRM_Core_Session::singleton()->get('ufID')) {
      // They are logged in; they're just not allowed this page.
      CRM_Core_Error::statusBounce(ts("Access denied"), CRM_Utils_System::url('civicrm'));
    }
    else {
      CRM_Utils_System::redirect('/civicrm/login?anonAccessDenied');
    }

    // TODO: Prettier error page
  }

}
