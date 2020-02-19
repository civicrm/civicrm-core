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
 * Drupal specific stuff goes here.
 */
class CRM_Utils_System_Drupal8 extends CRM_Utils_System_DrupalBase {

  /**
   * @inheritDoc
   */
  public function createUser(&$params, $mail) {
    $user = \Drupal::currentUser();
    $user_register_conf = \Drupal::config('user.settings')->get('register');
    $verify_mail_conf = \Drupal::config('user.settings')->get('verify_mail');

    // Don't create user if we don't have permission to.
    if (!$user->hasPermission('administer users') && $user_register_conf == 'admin_only') {
      return FALSE;
    }

    /** @var \Drupal\user\Entity\User $account */
    $account = entity_create('user');
    $account->setUsername($params['cms_name'])->setEmail($params[$mail]);

    // Allow user to set password only if they are an admin or if
    // the site settings don't require email verification.
    if (!$verify_mail_conf || $user->hasPermission('administer users')) {
      // @Todo: do we need to check that passwords match or assume this has already been done for us?
      $account->setPassword($params['cms_pass']);
    }

    // Only activate account if we're admin or if anonymous users don't require
    // approval to create accounts.
    if ($user_register_conf != 'visitors' && !$user->hasPermission('administer users')) {
      $account->block();
    }
    elseif (!$verify_mail_conf) {
      $account->activate();
    }

    // Validate the user object
    $violations = $account->validate();
    if (count($violations)) {
      return FALSE;
    }

    // Let the Drupal module know we're already in CiviCRM.
    $config = CRM_Core_Config::singleton();
    $config->inCiviCRM = TRUE;

    try {
      $account->save();
      $config->inCiviCRM = FALSE;
    }
    catch (\Drupal\Core\Entity\EntityStorageException $e) {
      $config->inCiviCRM = FALSE;
      return FALSE;
    }

    // Send off any emails as required.
    // Possible values for $op:
    //    - 'register_admin_created': Welcome message for user created by the admin.
    //    - 'register_no_approval_required': Welcome message when user
    //      self-registers.
    //    - 'register_pending_approval': Welcome message, user pending admin
    //      approval.
    // @Todo: Should we only send off emails if $params['notify'] is set?
    switch (TRUE) {
      case $user_register_conf == 'admin_only' || $user->isAuthenticated():
        _user_mail_notify('register_admin_created', $account);
        break;

      case $user_register_conf == 'visitors':
        _user_mail_notify('register_no_approval_required', $account);
        break;

      case 'visitors_admin_approval':
        _user_mail_notify('register_pending_approval', $account);
        break;
    }

    // If this is a user creating their own account, login them in!
    if ($account->isActive() && $user->isAnonymous()) {
      \user_login_finalize($account);
    }

    return $account->id();
  }

  /**
   * @inheritDoc
   */
  public function updateCMSName($ufID, $email) {
    $user = entity_load('user', $ufID);
    if ($user && $user->getEmail() != $email) {
      $user->setEmail($email);

      if (!count($user->validate())) {
        $user->save();
      }
    }
  }

  /**
   * Check if username and email exists in the drupal db.
   *
   * @param array $params
   *   Array of name and mail values.
   * @param array $errors
   *   Errors.
   * @param string $emailName
   *   Field label for the 'email'.
   */
  public static function checkUserNameEmailExists(&$params, &$errors, $emailName = 'email') {
    // If we are given a name, let's check to see if it already exists.
    if (!empty($params['name'])) {
      $name = $params['name'];

      $user = entity_create('user');
      $user->setUsername($name);

      // This checks for both username uniqueness and validity.
      $violations = iterator_to_array($user->validate());
      // We only care about violations on the username field; discard the rest.
      $violations = array_values(array_filter($violations, function ($v) {
        return $v->getPropertyPath() == 'name';
      }));
      if (count($violations) > 0) {
        $errors['cms_name'] = (string) $violations[0]->getMessage();
      }
    }

    // And if we are given an email address, let's check to see if it already exists.
    if (!empty($params['mail'])) {
      $mail = $params['mail'];

      $user = entity_create('user');
      $user->setEmail($mail);

      // This checks for both email uniqueness.
      $violations = iterator_to_array($user->validate());
      // We only care about violations on the email field; discard the rest.
      $violations = array_values(array_filter($violations, function ($v) {
        return $v->getPropertyPath() == 'mail';
      }));
      if (count($violations) > 0) {
        $errors[$emailName] = (string) $violations[0]->getMessage();
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function getLoginURL($destination = '') {
    $query = $destination ? ['destination' => $destination] : [];
    return \Drupal::url('user.login', [], ['query' => $query]);
  }

  /**
   * @inheritDoc
   */
  public function setTitle($title, $pageTitle = NULL) {
    if (!$pageTitle) {
      $pageTitle = $title;
    }
    \Drupal::service('civicrm.page_state')->setTitle($pageTitle);
  }

  /**
   * @inheritDoc
   */
  public function appendBreadCrumb($breadcrumbs) {
    $civicrmPageState = \Drupal::service('civicrm.page_state');
    foreach ($breadcrumbs as $breadcrumb) {
      $civicrmPageState->addBreadcrumb($breadcrumb['title'], $breadcrumb['url']);
    }
  }

  /**
   * @inheritDoc
   */
  public function resetBreadCrumb() {
    \Drupal::service('civicrm.page_state')->resetBreadcrumbs();
  }

  /**
   * @inheritDoc
   */
  public function addHTMLHead($header) {
    \Drupal::service('civicrm.page_state')->addHtmlHeader($header);
  }

  /**
   * @inheritDoc
   */
  public function addStyleUrl($url, $region) {
    if ($region != 'html-header') {
      return FALSE;
    }
    $css = [
      '#tag' => 'link',
      '#attributes' => [
        'href' => $url,
        'rel' => 'stylesheet',
      ],
    ];
    \Drupal::service('civicrm.page_state')->addCSS($css);
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function addStyle($code, $region) {
    if ($region != 'html-header') {
      return FALSE;
    }
    $css = [
      '#tag' => 'style',
      '#value' => $code,
    ];
    \Drupal::service('civicrm.page_state')->addCSS($css);
    return TRUE;
  }

  /**
   * Check if a resource url is within the drupal directory and format appropriately.
   *
   * This seems to be a legacy function. We assume all resources are within the drupal
   * directory and always return TRUE. As well, we clean up the $url.
   *
   * FIXME: This is not a legacy function and the above is not a safe assumption.
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
    // FIXME: Should not unconditionally return true
    return TRUE;
  }

  /**
   * This function does nothing in Drupal 8. Changes to the base_url should be made
   * in settings.php directly.
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
    $query = html_entity_decode($query);

    $config = CRM_Core_Config::singleton();
    $base = $absolute ? $config->userFrameworkBaseURL : 'internal:/';

    $url = $this->parseURL("{$path}?{$query}");

    // Not all links that CiviCRM generates are Drupal routes, so we use the weaker ::fromUri method.
    try {
      $url = \Drupal\Core\Url::fromUri("{$base}{$url['path']}", array(
        'query' => $url['query'],
        'fragment' => $fragment,
        'absolute' => $absolute,
      ))->toString();
    }
    catch (Exception $e) {
      \Drupal::logger('civicrm')->error($e->getMessage());
    }

    return $url;
  }

  /**
   * @inheritDoc
   */
  public function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    $system = new CRM_Utils_System_Drupal8();
    $system->loadBootStrap([], FALSE);

    $uid = \Drupal::service('user.auth')->authenticate($name, $password);
    if ($uid) {
      if ($this->loadUser($name)) {
        $contact_id = CRM_Core_BAO_UFMatch::getContactId($uid);
        return [$contact_id, $uid, mt_rand()];
      }
    }

    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function loadUser($username) {
    $user = user_load_by_name($username);
    if (!$user) {
      return FALSE;
    }

    // Set Drupal's current user to the loaded user.
    \Drupal::currentUser()->setAccount($user);

    $uid = $user->id();
    $contact_id = CRM_Core_BAO_UFMatch::getContactId($uid);

    // Store the contact id and user id in the session
    $session = CRM_Core_Session::singleton();
    $session->set('ufID', $uid);
    $session->set('userID', $contact_id);
    return TRUE;
  }

  /**
   * Determine the native ID of the CMS user.
   *
   * @param string $username
   * @return int|null
   */
  public function getUfId($username) {
    if ($id = user_load_by_name($username)->id()) {
      return $id;
    }
  }

  /**
   * @inheritDoc
   */
  public function permissionDenied() {
    \Drupal::service('civicrm.page_state')->setAccessDenied();
  }

  /**
   * In previous versions, this function was the controller for logging out. In Drupal 8, we rewrite the route
   * to hand off logout to the standard Drupal logout controller. This function should therefore never be called.
   */
  public function logout() {
    // Pass
  }

  /**
   * Load drupal bootstrap.
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
    static $run_once = FALSE;
    if ($run_once) {
      return TRUE;
    }
    else {
      $run_once = TRUE;
    }

    if (!($root = $this->cmsRootPath())) {
      return FALSE;
    }
    chdir($root);

    // Create a mock $request object
    $autoloader = require_once $root . '/autoload.php';
    if ($autoloader === TRUE) {
      $autoloader = ComposerAutoloaderInitDrupal8::getLoader();
    }
    // @Todo: do we need to handle case where $_SERVER has no HTTP_HOST key, ie. when run via cli?
    $request = new \Symfony\Component\HttpFoundation\Request([], [], [], [], [], $_SERVER);

    // Create a kernel and boot it.
    \Drupal\Core\DrupalKernel::createFromRequest($request, $autoloader, 'prod')->prepareLegacyRequest($request);

    // Initialize Civicrm
    \Drupal::service('civicrm')->initialize();

    // We need to call the config hook again, since we now know
    // all the modules that are listening on it (CRM-8655).
    CRM_Utils_Hook::config($config);

    if ($loadUser) {
      if (!empty($params['uid']) && $username = \Drupal\user\Entity\User::load($params['uid'])->getUsername()) {
        $this->loadUser($username);
      }
      elseif (!empty($params['name']) && !empty($params['pass']) && \Drupal::service('user.auth')->authenticate($params['name'], $params['pass'])) {
        $this->loadUser($params['name']);
      }
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

    if (defined('DRUPAL_ROOT')) {
      return DRUPAL_ROOT;
    }

    // It looks like Drupal hasn't been bootstrapped.
    // We're going to attempt to discover the root Drupal path
    // by climbing out of the folder hierarchy and looking around to see
    // if we've found the Drupal root directory.
    if (!$path) {
      $path = $_SERVER['SCRIPT_FILENAME'];
    }

    // Normalize and explode path into its component paths.
    $paths = explode(DIRECTORY_SEPARATOR, realpath($path));

    // Remove script filename from array of directories.
    array_pop($paths);

    while (count($paths)) {
      $candidate = implode('/', $paths);
      if (file_exists($candidate . "/core/includes/bootstrap.inc")) {
        return $candidate;
      }

      array_pop($paths);
    }
  }

  /**
   * @inheritDoc
   */
  public function isUserLoggedIn() {
    return \Drupal::currentUser()->isAuthenticated();
  }

  /**
   * @inheritDoc
   */
  public function isUserRegistrationPermitted() {
    if (\Drupal::config('user.settings')->get('register') == 'admin_only') {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function isPasswordUserGenerated() {
    if (\Drupal::config('user.settings')->get('verify_mail') == TRUE) {
      return FALSE;
    }
    return TRUE;
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
    if ($id = \Drupal::currentUser()->id()) {
      return $id;
    }
  }

  /**
   * @inheritDoc
   */
  public function getDefaultBlockLocation() {
    return 'sidebar_first';
  }

  /**
   * @inheritDoc
   */
  public function flush() {
    // CiviCRM and Drupal both provide (different versions of) Symfony (and possibly share other classes too).
    // If we call drupal_flush_all_caches(), Drupal will attempt to rediscover all of its classes, use Civicrm's
    // alternatives instead and then die. Instead, we only clear cache bins and no more.
    foreach (Drupal\Core\Cache\Cache::getBins() as $service_id => $cache_backend) {
      $cache_backend->deleteAll();
    }
  }

  /**
   * @inheritDoc
   */
  public function getModules() {
    $modules = [];

    $module_data = system_rebuild_module_data();
    foreach ($module_data as $module_name => $extension) {
      if (!isset($extension->info['hidden']) && $extension->origin != 'core') {
        $extension->schema_version = drupal_get_installed_schema_version($module_name);
        $modules[] = new CRM_Core_Module('drupal.' . $module_name, ($extension->status == 1 ? TRUE : FALSE));
      }
    }
    return $modules;
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
    return $user->get('mail')->value;
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
    $config = CRM_Core_Config::singleton();
    if (PHP_SAPI != 'cli') {
      set_time_limit(300);
    }

    $users = [];
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties();

    $uf = $config->userFramework;
    $contactCount = 0;
    $contactCreated = 0;
    $contactMatching = 0;
    foreach ($users as $user) {
      $mail = $user->get('mail')->value;
      if (empty($mail)) {
        continue;
      }
      $uid = $user->get('uid')->value;
      $contactCount++;
      if ($match = CRM_Core_BAO_UFMatch::synchronizeUFMatch($user, $uid, $mail, $uf, 1, 'Individual', TRUE)) {
        $contactCreated++;
      }
      else {
        $contactMatching++;
      }
    }

    return [
      'contactCount' => $contactCount,
      'contactMatching' => $contactMatching,
      'contactCreated' => $contactCreated,
    ];
  }

  /**
   * @inheritDoc
   */
  public function setMessage($message) {
    // CiviCRM sometimes includes markup in messages (ex: Event Cart)
    // it needs to be rendered before being displayed.
    $message = \Drupal\Core\Render\Markup::create($message);
    \Drupal::messenger()->addMessage($message);
  }

  /**
   * Drupal 8 has a different function to get current path, hence
   * overriding the postURL function
   *
   * @param string $action
   *
   * @return string
   */
  public function postURL($action) {
    if (!empty($action)) {
      return $action;
    }
    $current_path = \Drupal::service('path.current')->getPath();
    return $this->url($current_path);
  }

  /**
   * Function to return current language of Drupal8
   *
   * @return string
   */
  public function getCurrentLanguage() {
    // Drupal might not be bootstrapped if being called by the REST API.
    if (!class_exists('Drupal') || !\Drupal::hasContainer()) {
      return NULL;
    }

    return \Drupal::languageManager()->getCurrentLanguage()->getId();
  }

  /**
   * Helper function to extract path, query and route name from Civicrm URLs.
   *
   * For example, 'civicrm/contact/view?reset=1&cid=66' will be returned as:
   *
   * @code
   * array(
   *   'path' => 'civicrm/contact/view',
   *   'route' => 'civicrm.civicrm_contact_view',
   *   'query' => array('reset' => '1', 'cid' => '66'),
   * );
   * @endcode
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
   * Append Drupal8 js to coreResourcesList.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public function appendCoreResources(\Civi\Core\Event\GenericHookEvent $e) {
    $e->list[] = 'js/crm.drupal8.js';
  }

  /**
   * @inheritDoc
   */
  public function getTimeZoneString() {
    $timezone = drupal_get_user_timezone();
    return $timezone;
  }

  /**
   * @inheritDoc
   */
  public function setUFLocale($civicrm_language) {
    $langcode = substr(str_replace('_', '', $civicrm_language), 0, 2);
    $languageManager = \Drupal::languageManager();
    $languages = $languageManager->getLanguages();

    if (isset($languages[$langcode])) {
      $languageManager->setConfigOverrideLanguage($languages[$langcode]);

      // Config must be re-initialized to reset the base URL
      // otherwise links will have the wrong language prefix/domain.
      $config = CRM_Core_Config::singleton();
      $config->free();

      return TRUE;
    }

    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function languageNegotiationURL($url, $addLanguagePart = TRUE, $removeLanguagePart = FALSE) {
    if (empty($url)) {
      return $url;
    }

    // Drupal might not be bootstrapped if being called by the REST API.
    if (!class_exists('Drupal') || !\Drupal::hasContainer()) {
      return $url;
    }

    $language = $this->getCurrentLanguage();
    if (\Drupal::service('module_handler')->moduleExists('language')) {
      $config = \Drupal::config('language.negotiation')->get('url');

      //does user configuration allow language
      //support from the URL (Path prefix or domain)
      $enabledLanguageMethods = \Drupal::config('language.types')->get('negotiation.language_interface.enabled') ?: [];
      if (array_key_exists(\Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl::METHOD_ID, $enabledLanguageMethods)) {
        $urlType = $config['source'];

        //url prefix
        if ($urlType == \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl::CONFIG_PATH_PREFIX) {
          if (!empty($language)) {
            if ($addLanguagePart && !empty($config['prefixes'][$language])) {
              $url .= $config['prefixes'][$language] . '/';
            }
            if ($removeLanguagePart && !empty($config['prefixes'][$language])) {
              $url = str_replace("/" . $config['prefixes'][$language] . "/", '/', $url);
            }
          }
        }
        //domain
        if ($urlType == \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl::CONFIG_DOMAIN) {
          if (isset($language->domain) && $language->domain) {
            if ($addLanguagePart) {
              $url = (CRM_Utils_System::isSSL() ? 'https' : 'http') . '://' . $config['domains'][$language] . base_path();
            }
            if ($removeLanguagePart && defined('CIVICRM_UF_BASEURL')) {
              $url = str_replace('\\', '/', $url);
              $parseUrl = parse_url($url);

              //kinda hackish but not sure how to do it right
              //hope http_build_url() will help at some point.
              if (is_array($parseUrl) && !empty($parseUrl)) {
                $urlParts = explode('/', $url);
                $hostKey = array_search($parseUrl['host'], $urlParts);
                $ufUrlParts = parse_url(CIVICRM_UF_BASEURL);
                $urlParts[$hostKey] = $ufUrlParts['host'];
                $url = implode('/', $urlParts);
              }
            }
          }
        }
      }
    }

    return $url;
  }

  /**
   * Get role names
   *
   * @return array|null
   */
  public function getRoleNames() {
    return user_role_names();
  }

}
