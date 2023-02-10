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

/**
 * Standalone specific stuff goes here.
 */
class CRM_Utils_System_Standalone extends CRM_Utils_System_Base {

  /**
   * @inheritdoc
   */
  public function getDefaultFileStorage() {
    return [
      'url' => 'upload',
      // @todo Not sure if this is wise
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
   * @param string $mail
   *   Email id for cms user.
   *
   * @return int|bool
   *   uid if user was created, false otherwise
   */
  public function createUser(&$params, $mail) {

    try {
      $userID = \Civi\Api4\User::create(TRUE)
      ->addValue('username', $params['cms_name'])
      ->addValue('mail', $mail)
      // @todo the Api should ensure a password is encrypted? Or call a method to do that here?
      ->addValue('password', $params['cms_pass'])
      ->execute()->single()['id'];
      }
    catch (\Exception $e) {
      \Civi::log()->warning("Failed to create user '$mail': " . $e->getMessage());
      return FALSE;
    }

    // @todo This is what Drupal does, but it's unclear why.
    // CRM_Core_Config::singleton()->inCiviCRM = FALSE;
    return (int) $userID;
  }

  /**
   * @inheritDoc
   */
  public function updateCMSName($ufID, $email) {
    \Civi\Api4\User::update(FALSE)
    ->addWhere('id', '=', $ufID)
    ->addValue('email', $email)
    ->execute();
  }

  /**
   * @inheritDoc
   */
  public function getLoginURL($destination = '') {
    $query = $destination ? ['destination' => $destination] : [];
    // @todo
    throw new \RuntimeException("Standalone getLoginURL not written yet!");
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
  }

  /**
   * @inheritDoc
   */
  public function resetBreadCrumb() {
  }

  /**
   * @inheritDoc
   */
  public function addHTMLHead($header) {
    $template = CRM_Core_Smarty::singleton();
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
    $forceBackend = FALSE,
    $htmlize = TRUE
  ) {
    // @todo Implement absolute etc
    $fragment = $fragment ? ('#' . $fragment) : '';
    $url = "/{$path}?{$query}$fragment";
    return $url;
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

    // this comment + session lines: copied from Drupal's implementation in case it's important...
    /* Before we do any loading, let's start the session and write to it.
     * We typically call authenticate only when we need to bootstrap the CMS
     * directly via Civi and hence bypass the normal CMS auth and bootstrap
     * process typically done in CLI and cron scripts. See: CRM-12648
     */
    $session = CRM_Core_Session::singleton();
    $session->set('civicrmInitSession', TRUE);

    $user = \Civi\Api4\User::get(FALSE)
    ->addWhere('name', '=', $name)
    ->addWhere('is_active', '=', TRUE)
    ->addSelect('password', 'contact_id')
    ->execute()->first() ?? [];
    $user += ['password' => ''];

    // @todo consider moving this elsewhere.
    $type = substr($user['password'], 0, 3);
    switch ($type) {
      case '$S$':
        // A normal Drupal 7 password using sha512.
        $hash = $this->_password_crypt('sha512', $password, $user['password']);
        break;
      default:
      return FALSE;
    }

    if (!hash_equals($user['password'], $hash)) {
      return FALSE;
    }

    // Note: random_int is more appropriate for cryptographical use than mt_rand
    // The long number is the max 32 bit value.
    return [$user['civicrm_id'], $user['id'], random_int(0, 2147483647)];
  }


  /**
   * This is a copy of Drupal7's _password_get_count_log2
   */

  /**
   * @inheritDoc
   *
   * Note that the parent signature in the docblock says object, but we use a string username for stanalone.
   *
   * @todo I (artfulrobot) am unclear what this is really needed for/expected to do.
   */
  public function loadUser($username) {
    $user = \Civi\Api4\User::get(FALSE)
    ->addWhere('username', '=', $username)
    ->execute()
    ->single();

    // Do we do something like this?:
    // CRM_Core_Session::singleton()->set('userID', $user['id']);
    // (but we'd need to clear the session etc. and probably use a special method for this?
    // or maybe this IS the special method for that?)
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
    return \Civi\Api4\User::get(FALSE)
    ->addWhere('username', '=', $username)
    ->execute()
    ->single()['id'];
  }

  /**
   * @inheritDoc
   */
  public function permissionDenied() {
    die('Standalone permissionDenied');
  }

  /**
   * @inheritDoc
   */
  public function logout() {
    // @todo
  }

  /**
   * @inheritDoc
   */
  public function theme(&$content, $print = FALSE, $maintenance = FALSE) {
    if ($maintenance) {
      $smarty = CRM_Core_Smarty::singleton();
      echo implode('', $smarty->_tpl_vars['pageHTMLHead']);
    }

    // @todo Add variables from the body tag? (for Shoreditch)

    print $content;
    return NULL;
  }

  /**
   * Bootstrap composer libs.
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

    error_log("artfulrobot: " . __FILE__ . " " . __METHOD__);
    if (!isset($runOnce)) {
      $runOnce = TRUE;
      return TRUE;
    }

    if (!($root = $this->cmsRootPath())) {
      // What does this guard against?
      return FALSE;
    }
    chdir($root);

    require_once $root . '../vendor/autoload.php'; /* assumes $root to be the _web_ root path, not the project root path. */

    if ($loadUser) {
      // @todo
      // if (!empty($params['uid']) && ...) {
      //   $this->loadUser($username);
      // }
      // elseif (!empty($params['name']) && !empty($params['pass']) && ...can authenticate...) {
      //   $this->loadUser($params['name']);
      // }
    }
    return TRUE;
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
    error_log("artfulrobot: " . __FILE__ . " " . __METHOD__);
    throw new \RuntimeException("Standalone requires the path is set for now. Set \$civicrm_paths['cms.root']['path'] in civicrm.settings.php to the webroot.");
  }

  /**
   * @inheritDoc
   */
  public function isUserLoggedIn() {
    // @todo
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function isUserRegistrationPermitted() {
    // @todo Have a setting
    return TRUE;
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
    // @todo Not implemented
    // This helps towards getting the CiviCRM menu to display
    return 1;
  }

  /**
   * @inheritDoc
   */
  public function getDefaultBlockLocation() {
    // @todo No sidebars, no blocks
    return 'sidebar_first';
  }

  /**
   * @inheritDoc
   */
  public function flush() {
  }

  /**
   * @inheritDoc
   */
  public function getUser($contactID) {
    $user_details = parent::getUser($contactID);
    $user_details['name'] = $user_details['name']->value;
    $user_details['email'] = $user_details['email']->value;
    return $user_details;
  }

  /**
   * @inheritDoc
   */
  public function getUniqueIdentifierFromUserObject($user) {
    // @todo I (artfulrobot) am not sure what object the 'user' is here.
    // Pretty sure this won't work.
    return $user->get('email')->value;
  }

  /**
   * @inheritDoc
   */
  public function getUserIDFromUserObject($user) {
    return $user->get('uid')->value;
  }

  /**
   * @inheritDoc
   */
  public function synchronizeUsers() {
    // @todo? artfulrobot says: I don't think we will need this?
    Civi::log()->debug('CRM_Utils_System_Standalone::synchronizeUsers: not implemented');
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
   * Function to return current language.
   *
   * @return string
   */
  public function getCurrentLanguage() {
    // @todo
    Civi::log()->debug('CRM_Utils_System_Standalone::getCurrentLanguage: not implemented');
    return NULL;
  }

  /**
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
    return $timezone;
  }

  /**
   * @inheritDoc
   */
  public function setUFLocale($civicrm_language) {
    throw new \RuntimeException("Standalone setUFLocale not written yet!");
    // @todo
  }

  /**
   * @inheritDoc
   */
  public function languageNegotiationURL($url, $addLanguagePart = TRUE, $removeLanguagePart = FALSE) {
    if (empty($url)) {
      return $url;
    }

    // @todo
    // \Civi::log()->warning("Standalone languageNegotiationURL is not written, but was called");
    return $url;
  }

  /**
   * Return the CMS-specific url for its permissions page
   * @return array
   */
  public function getCMSPermissionsUrlParams() {
    // @todo
    \Civi::log()->warning("Standalone getCMSPermissionsUrlParams is not written, but was called");
    return ['ufAccessURL' => '/fixme/standalone/permissions/url/params'];
  }

  /**
   * Start a new session.
   */
  public function sessionStart() {
    session_start();
    // @todo This helps towards getting the CiviCRM menu to display
    // but obviously should be replaced once we have user management
    CRM_Core_Session::singleton()->set('userID', 1);
  }

  /**
   * @inheritdoc
   */
  public function getSessionId() {
    return session_id();
  }

  /**
   * @todo is anything needed here for Standalone?
   */
  public function invalidateRouteCache() {
  }

}
