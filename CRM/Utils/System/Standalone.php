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

use Civi\Standalone\SessionHandler;

/**
 * Standalone specific stuff goes here.
 */
class CRM_Utils_System_Standalone extends CRM_Utils_System_Base {

  /**
   * Standalone uses a CiviCRM Extension, Standaloneusers, to provide user
   * functionality
   *
   * This is great for modularity - but does mean that there are points in
   * bootstrap / install / failure where the extension isn't available
   * and we need to provide fallback/failsafe behaviours
   *
   * This function provides a general check for whether we are in such a
   * scenario
   *
   * (In the future, alternative user-providing extensions may be available - in
   * which case this check might need generalising. One possibility could be
   * to use the Api4 User interface as a spec for what any extension must provide
   *
   * Then maybe the check could be if (class_exists(\Civi\Api4\User::class))?
   *
   * @return bool
   *   Whether user extension is available
   */
  protected function isUserExtensionAvailable(): bool {
    if (!class_exists(\Civi\Api4\User::class)) {
      return FALSE;
    }
    // TODO: the following would be be a better check, as sometimes during
    // upgrade the User class can exist but the entity is not actually loaded
    //
    // HOWEVER: it currently causes a crash during the install phase.
    // https://github.com/civicrm/civicrm-core/pull/31198 may help.
    //
    // if (!\Civi\Api4\Utils\CoreUtil::entityExists('User')) {
    //   return FALSE;
    // }

    // authx function is required for standalone user system
    if (!function_exists('_authx_uf')) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @inheritdoc
   *
   * In Standalone the UF is CiviCRM, so we're never
   * running without it
   */
  public function isLoaded(): bool {
    return TRUE;
  }

  /**
   * @inheritdoc
   */
  public function getDefaultFileStorage() {
    return [
      'path' => \Civi::paths()->getPath('[cms.root]/public'),
      'url' => \Civi::paths()->getUrl('[cms.root]/public'),
    ];
  }

  /**
   * @inheritDoc
   */
  public function createUser(&$params, $emailParam) {
    try {
      $email = $params[$emailParam];
      $userID = \Civi\Api4\User::create(FALSE)
        ->addValue('username', $params['cms_name'])
        ->addValue('uf_name', $email)
        ->addValue('password', $params['cms_pass'])
        ->addValue('contact_id', $params['contact_id'] ?? NULL)
        // ->addValue('uf_id', 0) // does not work without this.
        ->execute()->single()['id'];
    }
    catch (\Exception $e) {
      \Civi::log()->warning("Failed to create user '$email': " . $e->getMessage());
      return FALSE;
    }

    return (int) $userID;
  }

  /**
   * @inheritDoc
   */
  public function updateCMSName($ufID, $email) {
    \Civi\Api4\User::update(FALSE)
      ->addWhere('id', '=', $ufID)
      ->addValue('uf_name', $email)
      ->execute();
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
    if (!is_array($breadcrumbs)) {
      // invalid - but no need to crash the whole page
      \Civi::log()->warning('Non-array passed to appendBreadCrumb');
      return;
    }
    $allCrumbs = array_merge(\Civi::$statics[__CLASS__]['breadcrumb'] ?? [], $breadcrumbs);
    \Civi::$statics[__CLASS__]['breadcrumb'] = $allCrumbs;
    CRM_Core_Smarty::singleton()->assign('breadcrumb', array_values($allCrumbs));
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
   *
   * Note: Standalone renders the html-header region directly in its smarty page template
   * so this should never be called
   */
  public function addHTMLHead($header) {
    throw new \CRM_Core_Exception('addHTMLHead should never be called in Standalone');
  }

  /**
   * @inheritdoc
   *
   * No such things as CMS-rendering in Standalone => always return FALSE
   */
  public function addStyleUrl($url, $region) {
    return FALSE;
  }

  /**
   * @inheritdoc
   *
   * No such things as CMS-rendering in Standalone => always return FALSE
   */
  public function addStyle($code, $region) {
    return FALSE;
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
    // TODO: Add type hints
    $query = (string) $query;
    if (strlen($query)) {
      $query = "?$query";
    }
    $fragment = (string) $fragment;
    if (strlen($fragment)) {
      $fragment = "#$fragment";
    }
    return Civi::paths()->getUrl("[cms.root]/$path$query$fragment", $absolute ? 'absolute' : 'relative');
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
   * I think this is only used by CLI so setting the session
   * doesn't make sense
   *
   * @param string $name
   *   The user name.
   * @param string $password
   *   The password for the above user.
   * @param bool $loadCMSBootstrap
   *   Not used in Standalone context
   * @param string $realPath
   *   Not used in Standalone context
   *
   * @return array|bool
   *   [contactID, ufID, unique string] else false if no auth
   * @throws \CRM_Core_Exception.
   */
  public function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    $authxLogin = authx_login(['flow' => 'login', 'cred' => 'Basic ' . base64_encode("{$name}:{$password}")]);

    // Note: random_int is more appropriate for cryptographical use than mt_rand
    // The long number is the max 32 bit value.
    return [$authxLogin['contactId'], $authxLogin['userId'], random_int(0, 2147483647)];
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
    if (!$this->isUserExtensionAvailable()) {
      return NULL;
    }
    return \Civi\Api4\User::get(FALSE)
      ->addWhere('username', '=', $username)
      ->execute()
      ->first()['id'] ?? NULL;
  }

  /**
   * @inheritdoc
   */
  public function postLogoutUrl(): string {
    return '/civicrm/login?justLoggedOut';
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
  public function renderMaintenanceMessage(string $content): string {
    // wrap in a minimal header
    $headerContent = CRM_Core_Region::instance('html-header', FALSE)->render('');

    // note - not adding #crm-container is a hacky way to avoid rendering
    // the civicrm menubar. @todo a better way
    return <<<HTML
      <!DOCTYPE html >
      <html class="crm-standalone">
        <head>
          {$headerContent}
        </head>
        <body>
          <div class="crm-container standalone-page-padding">
            {$content}
          </div>
        </body>
      </html>
    HTML;
  }

  /**
   * Bootstrap Standalone.
   *
   * In CRM_Utils_System context, this function is used by cv/civix/? to bootstrap
   * the CMS *after* CiviCRM is already loaded (as compared to normal web requests,
   * which load the CMS then CiviCRM)
   *
   * For Standalone there shouldn't be anything additional to load at this
   * stage in terms of system services.
   *
   *
   * This is used by cv and civix, but not I (artfulrobot) think, in the main http requests.
   * External scripts may assume loading a users requires the CMS bootstrap
   * - so we keep support for logging in a user now
   *
   * @param array $params
   *   Either uid, or name & pass.
   * @param bool $loadUser
   *   Boolean Require CMS user load.
   * @param bool $throwError
   *   If true, print error on failure and exit.
   * @param bool|string $realPath
   *   Not used in Standalone context
   *
   * @return bool
   * @Todo Handle setting cleanurls configuration for CiviCRM?
   */
  public function loadBootStrap($params = [], $loadUser = TRUE, $throwError = TRUE, $realPath = NULL) {
    static $runOnce;

    if (isset($runOnce)) {
      // we've already run
      return TRUE;
    }

    // dont run again
    $runOnce = TRUE;

    if (!$loadUser) {
      return TRUE;
    }

    try {
      if (!empty($params['uid'])) {
        _authx_uf()->loginStateless($params['uid']);
        return TRUE;
      }
      elseif (!empty($params['name']) && !empty($params['pass'])) {
        // It seems from looking at the Drupal implementation, that
        // if given username we expect a correct password.

        /**
         * @throws CRM_Core_Exception if login unsuccessful
         */
        $this->authenticate($params['name'], $params['pass']);
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    catch (\CRM_Core_Exception $e) {
      // swallow any errors if $throwError is false
      // (presume the expectation is these are login errors
      // - though that isn't guaranteed?)
      if (!$throwError) {
        return FALSE;
      }
      throw $e;
    }
  }

  /**
   * @inheritdoc
   */
  public function loadUser($username) {
    $userID = $this->getUfId($username) ?? NULL;
    if (!$userID) {
      return FALSE;
    }
    _authx_uf()->loginSession($userID);
    return TRUE;
  }

  /**
   * Load an active user by internal user ID.
   *
   * @return array|bool FALSE if not found.
   */
  public function getUserById(int $userID) {
    if (!$this->isUserExtensionAvailable()) {
      return FALSE;
    }
    return \Civi\Api4\User::get(FALSE)
      ->addWhere('id', '=', $userID)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->first() ?: FALSE;
  }

  /**
   * @inheritdoc
   */
  public function getCiviSourceStorage(): array {
    return [
      'path' => Civi::paths()->getPath('[cms.root]/core'),
      'url' => Civi::paths()->getUrl('[cms.root]/core'),
    ];
  }

  /**
   * In Standalone, this returns the app root
   *
   * The $appRootPath global is set in civicrm.standalone.php
   *
   * @return NULL|string
   */
  public function cmsRootPath() {
    global $appRootPath;
    return $appRootPath;
  }

  public function isFrontEndPage() {
    return CRM_Core_Menu::isPublicRoute(CRM_Utils_System::currentPath() ?? '');
  }

  /**
   * @inheritDoc
   */
  public function isUserLoggedIn() {
    return !empty($this->getLoggedInUfID());
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
   *
   * If the User extension isn't available
   * then no one is logged in
   */
  public function getLoggedInUfID() {
    if (!$this->isUserExtensionAvailable()) {
      return NULL;
    }
    return _authx_uf()->getCurrentUserId();
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
    $userId = $this->getLoggedInUfID();
    if ($userId) {
      $user = $this->getUserById($userId);
      if ($user && !empty($user['timezone'])) {
        return $user['timezone'];
      }
    }
    return date_default_timezone_get();
  }

  /**
   * @inheritDoc
   */
  public function getUFLocale(): ?string {
    $userId = $this->getLoggedInUfID();
    if ($userId) {
      $user = $this->getUserById($userId);
      if ($user && !empty($user['language'])) {
        return $user['language'];
      }
    }

    return NULL;
  }

  /**
   * @inheritDoc
   * @todo implement language negotiation for Standalone?
   */
  public function languageNegotiationURL($url, $addLanguagePart = TRUE, $removeLanguagePart = FALSE) {
    return $url;
  }

  /**
   * Return the CMS-specific url for its permissions page
   * @return array
   */
  public function getCMSPermissionsUrlParams() {
    return ['ufAccessURL' => '/civicrm/admin/roles'];
  }

  /**
   * Respond that permission has been denied.
   *
   * There are a few variations:
   * - For stateful requests where no one is logged in => redirect to login page
   * - For stateful requests where user is logged in => redirect to home page with a message (unless caught in redirect loop)
   * - Otherwise, show a "Permission Denied" page
   */
  public function permissionDenied() {
    http_response_code(403);

    $session = CRM_Core_Session::singleton();
    $useSession = ($session->get('authx')['useSession'] ?? TRUE);

    if ($useSession && !$this->isUserLoggedIn()) {
      // Stateful request, but no one is logged in => show log in prompt
      $loginPage = new CRM_Standaloneusers_Page_Login();
      CRM_Core_Session::setStatus(ts('You need to be logged in to access this page.'), ts('Please sign in.'));
      return $loginPage->run();
    }

    if ($useSession && $this->isUserLoggedIn()) {
      // Stateful login => redirect to home page with message (unless they are caught in a redirect loop)
      if (!\CRM_Utils_Request::retrieve('permissionDeniedRedirect', 'Boolean')) {
        CRM_Core_Error::statusBounce(ts("Access denied"), \Civi::url('current://civicrm/home')->setQuery([
          'permissionDeniedRedirect' => 1,
        ]));
        return;
      }
    }

    // show a stateless access denied page
    return (new CRM_Standaloneusers_Page_PermissionDenied())->run();
  }

  /**
   * Start a new session.
   *
   * Generally this uses the SessionHander provided by Standaloneusers
   * extension - but we fallback to a default PHP session to:
   * a) allow the installer to work (early in the Standalone install, we dont have Standaloneusers yet)
   * b) avoid unhelpfully hard crash if the ExtensionSystem goes down (without the fallback, the crash
   * here swallows whatever error is actually causing the crash)
   */
  public function sessionStart() {
    if (!$this->isUserExtensionAvailable()) {
      $session_cookie_name = 'SESSCIVISOFALLBACK';
    }
    else {
      $session_cookie_name = 'SESSCIVISO';
    }
    if (ini_get('session.save_handler') === 'redis') {
      // We'll just use the default, take no action.
    }
    else {
      $session_handler = new SessionHandler();
      session_set_save_handler($session_handler);
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

  public function initialize() {
    parent::initialize();
    $this->registerDefaultPaths();
  }

  /**
   * Specify the default computation for various paths/URLs.
   */
  protected function registerDefaultPaths(): void {
    \Civi::paths()
      ->register('civicrm.private', function () {
          return [
            'path' => \Civi::paths()->getPath('[cms.root]/private'),
          ];
      })
      ->register('civicrm.compile', function () {
        return [
          'path' => \Civi::paths()->getPath('[civicrm.private]/cache'),
        ];
      })
      ->register('civicrm.log', function () {
        return [
          'path' => \Civi::paths()->getPath('[civicrm.private]/log'),
        ];
      })
      ->register('civicrm.l10n', function () {
        return [
          'path' => \Civi::paths()->getPath('[civicrm.private]/l10n'),
        ];
      });
  }

  /**
   * Standalone's session cannot be initialized until CiviCRM is booted,
   * since it is defined in an extension,
   *
   * This used to be when we set timezone, but this is moved to
   * standaloneusers_civicrm_config hook to avoid crashing multilingual sites`
   */
  public function postContainerBoot(): void {
    $sess = \CRM_Core_Session::singleton();
    $sess->initialize();
  }

}
