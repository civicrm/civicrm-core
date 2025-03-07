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
  public function createUser(&$params, $mailParam) {
    $user = \Drupal::currentUser();
    $user_register_conf = \Drupal::config('user.settings')->get('register');
    $verify_mail_conf = \Drupal::config('user.settings')->get('verify_mail');

    // Don't create user if we don't have permission to.
    if (!$user->hasPermission('administer users') && $user_register_conf == 'admin_only') {
      return FALSE;
    }

    /** @var \Drupal\user\Entity\User $account */
    $account = \Drupal::entityTypeManager()->getStorage('user')->create();
    $account->setUsername($params['cms_name'])->setEmail($params[$mailParam]);

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
    else {
      $account->activate();
    }

    // Validate the user object
    $violations = $account->validate();
    if (count($violations)) {
      foreach ($violations as $violation) {
        CRM_Core_Session::setStatus($violation->getPropertyPath() . ': ' . $violation->getMessage(), '', 'alert');
      }
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
    switch (TRUE) {
      case $user_register_conf == 'admin_only' || $user->isAuthenticated():
        if (!empty($params['notify'])) {
          _user_mail_notify('register_admin_created', $account);
        }
        break;

      case $user_register_conf == 'visitors':
        _user_mail_notify('register_no_approval_required', $account);
        break;

      case 'visitors_admin_approval':
        _user_mail_notify('register_pending_approval', $account);
        break;
    }

    // If this is a user creating their own account, login them in!
    if (!$verify_mail_conf && $account->isActive() && $user->isAnonymous()) {
      \user_login_finalize($account);
    }

    return $account->id();
  }

  /**
   * @inheritDoc
   */
  public function updateCMSName($ufID, $email) {
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($ufID);
    if ($user && $user->getEmail() != $email) {
      $user->setEmail($email);

      // Skip requirement for password when changing the current user fields
      $user->_skipProtectedUserFieldConstraint = TRUE;

      if (!count($user->validate())) {
        $user->save();
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function checkUserNameEmailExists(&$params, &$errors, $emailName = 'email') {
    // If we are given a name, let's check to see if it already exists.
    if (!empty($params['name'])) {
      $name = $params['name'];

      $user = \Drupal::entityTypeManager()->getStorage('user')->create();
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

      $user = \Drupal::entityTypeManager()->getStorage('user')->create();
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
    return \Drupal\Core\Url::fromRoute('user.login', [], ['query' => $query])->toString();
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
      if (stripos($breadcrumb['url'], 'id%%')) {
        $args = ['cid', 'mid'];
        foreach ($args as $a) {
          $val = CRM_Utils_Request::retrieve($a, 'Positive');
          if ($val) {
            $breadcrumb['url'] = str_ireplace("%%{$a}%%", $val, $breadcrumb['url']);
          }
        }
      }
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
   * @param string $url
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
    $base = $absolute ? $config->userFrameworkBaseURL : 'base:/';

    $url = $this->parseURL("{$path}?{$query}");

    // Not all links that CiviCRM generates are Drupal routes, so we use the weaker ::fromUri method.
    try {
      $url = \Drupal\Core\Url::fromUri("{$base}{$url['path']}", [
        'query' => $url['query'],
        'fragment' => $fragment,
        'absolute' => $absolute,
      ])->toString();
      // Decode %% for better readability, e.g., %%cid%%.
      $url = str_replace('%25%25', '%%', $url);
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
    /* Before we do any loading, let's start the session and write to it.
     * We typically call authenticate only when we need to bootstrap the CMS
     * directly via Civi and hence bypass the normal CMS auth and bootstrap
     * process typically done in CLI and cron scripts. See: CRM-12648
     */
    $session = CRM_Core_Session::singleton();
    $session->set('civicrmInitSession', TRUE);

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
    $user = user_load_by_name($username);
    if ($user && $id = $user->id()) {
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
    $kernel = \Drupal\Core\DrupalKernel::createFromRequest($request, $autoloader, 'prod');
    $kernel->boot();
    $kernel->preHandle($request);
    $container = $kernel->rebuildContainer();
    // Add our request to the stack and route context.
    $request->attributes->set(\Drupal\Core\Routing\RouteObjectInterface::ROUTE_OBJECT, new \Symfony\Component\Routing\Route('<none>'));
    $request->attributes->set(\Drupal\Core\Routing\RouteObjectInterface::ROUTE_NAME, '<none>');
    $container->get('request_stack')->push($request);
    $container->get('router.request_context')->fromRequest($request);

    // Initialize Civicrm
    \Drupal::service('civicrm')->initialize();

    // We need to call the config hook again, since we now know
    // all the modules that are listening on it (CRM-8655).
    $config = CRM_Core_Config::singleton();
    CRM_Utils_Hook::config($config, ['uf' => TRUE]);

    if ($loadUser) {
      if (!empty($params['uid']) && $username = \Drupal\user\Entity\User::load($params['uid'])->getAccountName()) {
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
  public function logger($message, $priority = NULL) {
    if (CRM_Core_Config::singleton()->userFrameworkLogging) {
      // dev/core#3438 Prevent cv fatal if logging before CMS bootstrap
      if (!class_exists('Drupal') || !\Drupal::hasContainer()) {
        return;
      }
      \Drupal::logger('civicrm')->log($priority ?? \Drupal\Core\Logger\RfcLogLevel::DEBUG, '%message', ['%message' => $message]);
    }
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

    $module_data = \Drupal::service('extension.list.module')->reset()->getList();
    foreach ($module_data as $module_name => $extension) {
      if (!isset($extension->info['hidden']) && $extension->origin != 'core' && $extension->status == 1) {
        $modules[] = new CRM_Core_Module('drupal.' . $module_name, TRUE,
          _ts('%1 (%2)', [1 => $extension->info['name'] ?? $module_name, _ts('Drupal')])
        );
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
   * Commit the session before exiting.
   * Similar to drupal_exit().
   */
  public function onCiviExit() {
    if (class_exists('Drupal')) {
      if (!defined('_CIVICRM_FAKE_SESSION')) {
        $session = \Drupal::service('session');
        if (!$session->isEmpty()) {
          $session->save();
        }
      }
    }
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
    $current_path = ltrim(\Drupal::service('path.current')->getPath(), '/');
    return (string) Civi::url('current://' . $current_path);
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

    return \Drupal::languageManager()->getConfigOverrideLanguage()->getId();
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
    $timezone = date_default_timezone_get();
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
    $roles = \Drupal\user\Entity\Role::loadMultiple();
    $names = array_map(fn(\Drupal\user\RoleInterface $role) => $role->label(), $roles);
    return $names;
  }

  /**
   * Determine if the Views module exists.
   *
   * @return bool
   */
  public function viewsExists() {
    if (\Drupal::moduleHandler()->moduleExists('views')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Return the CMS-specific url for its permissions page
   * @return array
   */
  public function getCMSPermissionsUrlParams() {
    return ['ufAccessURL' => \Drupal\Core\Url::fromRoute('user.admin_permissions')->toString()];
  }

  /**
   * Start a new session.
   */
  public function sessionStart() {
    if (\Drupal::hasContainer()) {
      $session = \Drupal::service('session');
      if (!$session->isStarted()) {
        $session->start();
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function getSessionId() {
    if (\Drupal::hasContainer()) {
      $session = \Drupal::service('session');
      if (!$session->has('civicrm.tempstore.sessionid')) {
        $session->set('civicrm.tempstore.sessionid', \Drupal\Component\Utility\Crypt::randomBytesBase64());
      }
      return $session->get('civicrm.tempstore.sessionid');
    }
    return '';
  }

  /**
   * Load the user object.
   *
   * @param int $userID
   *
   * @return object
   */
  public function getUserObject($userID) {
    return \Drupal::entityTypeManager()->getStorage('user')->load($userID);
  }

  /**
   * Helper function to rebuild the Drupal 8 or 9 dynamic routing cache.
   * We need to do this after enabling extensions that add routes and it's worth doing when we reset Civi paths.
   */
  public function invalidateRouteCache() {
    if (class_exists('\Drupal') && \Drupal::hasContainer()) {
      \Drupal::service('router.builder')->rebuild();
    }
  }

  public function getVersion() {
    if (class_exists('\Drupal')) {
      return \Drupal::VERSION;
    }
    return 'Unknown';
  }

  /**
   * @inheritdoc
   */
  public function suppressProfileFormErrors():bool {
    // Suppress the errors if they are displayed using
    // setErrorByName method on FormStateInterface.
    $current_path = \Drupal::service('path.current')->getPath();
    $path_args = explode('/', $current_path);
    if ($path_args[1] == 'user' || ($path_args[1] == 'admin' && $path_args[2] == 'people')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @inheritdoc
   */
  public function viewsIntegration(): string {
    return '<p><strong>' . ts('To enable CiviCRM Views integration, install the <a %1>CiviCRM Entity</a> module.', [1 => 'href="https://www.drupal.org/project/civicrm_entity"']) . '</strong></p>';
  }

  /**
   * @inheritdoc
   */
  public function theme(&$content, $print = FALSE, $maintenance = FALSE) {
    // @todo use Drupal "maintenance page" template and theme during installation
    // or upgrade.
    print $content;
    return NULL;
  }

  /**
   * @inheritdoc
   */
  public function ipAddress():?string {
    // dev/core#4756 fallback if checking before CMS bootstrap
    if (!class_exists('Drupal') || !\Drupal::hasContainer()) {
      return ($_SERVER['REMOTE_ADDR'] ?? NULL);
    }
    return \Drupal::request()->getClientIp();
  }

  /**
   * @inheritdoc
   */
  public function mailingWorkflowIsEnabled():bool {
    if (!\Drupal::moduleHandler()->moduleExists('rules')) {
      return FALSE;
    }

    $enableWorkflow = Civi::settings()->get('civimail_workflow');

    return (bool) $enableWorkflow;
  }

  /**
   * @inheritDoc
   */
  public function clearResourceCache() {
    $cleared = FALSE;
    // @todo When only drupal 10.2+ is supported can remove the try catch
    // and the fallback to drupal_flush_css_js. Still need the class_exists.
    try {
      // Sometimes metadata gets cleared while the cms isn't bootstrapped.
      if (class_exists('\Drupal') && \Drupal::hasContainer()) {
        \Drupal::service('asset.query_string')->reset();
        $cleared = TRUE;
      }
    }
    catch (\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException $e) {
      // probably < drupal 10.2 - fall thru
    }
    // Sometimes metadata gets cleared while the cms isn't bootstrapped.
    if (!$cleared && function_exists('_drupal_flush_css_js')) {
      _drupal_flush_css_js();
    }
  }

  /**
   * @inheritdoc
   */
  public function isMaintenanceMode(): bool {
    try {
      return \Drupal::state()->get('system.maintenance_mode') ?: FALSE;
    }
    catch (\Exception $e) {
      // catch in case Drupal isn't fully booted and can't answer
      //
      // we assume we are *NOT* in maintenance mode
      //
      // TODO: this may not be a good assumption for e.g. cv cron job
      // which could be exactly the sort of thing we would want to
      // prevent running in maintenance mode... maybe we should check
      // try to check the drupal database directly here?
      return FALSE;
    }
  }

}
