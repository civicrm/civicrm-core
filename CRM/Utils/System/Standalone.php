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
   */
  public function createUser(&$params, $mail) {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function updateCMSName($ufID, $email) {
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
    $request->attributes->set(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT, new \Symfony\Component\Routing\Route('<none>'));
    $request->attributes->set(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_NAME, '<none>');
    $container->get('request_stack')->push($request);
    $container->get('router.request_context')->fromRequest($request);

    // Initialize Civicrm
    \Drupal::service('civicrm')->initialize();

    // We need to call the config hook again, since we now know
    // all the modules that are listening on it (CRM-8655).
    CRM_Utils_Hook::config($config);

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
    // @todo
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
   * Function to return current language of Drupal8
   *
   * @return string
   */
  public function getCurrentLanguage() {
    // @todo FIXME
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
   * Append Drupal8 js to coreResourcesList.
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
   * Helper function to rebuild the Drupal 8 or 9 dynamic routing cache.
   * We need to do this after enabling extensions that add routes and it's worth doing when we reset Civi paths.
   */
  public function invalidateRouteCache() {
  }

}
