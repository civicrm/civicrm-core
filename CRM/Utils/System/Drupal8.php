<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
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

    // Validate the user object
    $violations = $account->validate();
    if (count($violations)) {
      return FALSE;
    }

    try {
      $account->save();
    }
    catch (\Drupal\Core\Entity\EntityStorageException $e) {
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
      $violations = array_filter($violations, function ($v) {
        return $v->getPropertyPath() == 'name.0.value';
      });
      if (count($violations) > 0) {
        $errors['cms_name'] = $violations[0]->getMessage();
      }
    }

    // And if we are given an email address, let's check to see if it already exists.
    if (!empty($params[$emailName])) {
      $mail = $params[$emailName];

      $user = entity_create('user');
      $user->setEmail($mail);

      // This checks for both email uniqueness.
      $violations = iterator_to_array($user->validate());
      // We only care about violations on the email field; discard the rest.
      $violations = array_filter($violations, function ($v) {
        return $v->getPropertyPath() == 'mail.0.value';
      });
      if (count($violations) > 0) {
        $errors[$emailName] = $violations[0]->getMessage();
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function getLoginURL($destination = '') {
    $query = $destination ? array('destination' => $destination) : array();
    return \Drupal::url('user.page', array(), array('query' => $query));
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
  public function addScriptUrl($url, $region) {
    static $weight = 0;

    switch ($region) {
      case 'html-header':
      case 'page-footer':
        break;

      default:
        return FALSE;
    }

    $script = array(
      '#tag' => 'script',
      '#attributes' => array(
        'src' => $url,
      ),
      '#weight' => $weight,
    );
    $weight++;
    \Drupal::service('civicrm.page_state')->addJS($script);
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function addScript($code, $region) {
    switch ($region) {
      case 'html-header':
      case 'page-footer':
        break;

      default:
        return FALSE;
    }

    $script = array(
      '#tag' => 'script',
      '#value' => $code,
    );
    \Drupal::service('civicrm.page_state')->addJS($script);
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function addStyleUrl($url, $region) {
    if ($region != 'html-header') {
      return FALSE;
    }
    $css = array(
      '#tag' => 'link',
      '#attributes' => array(
        'href' => $url,
        'rel' => 'stylesheet',
      ),
    );
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
    $css = array(
      '#tag' => 'style',
      '#value' => $code,
    );
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

    $url = \Drupal\civicrm\CivicrmHelper::parseURL("{$path}?{$query}");

    // Not all links that CiviCRM generates are Drupal routes, so we use the weaker ::fromUri method.
    try {
      $url = \Drupal\Core\Url::fromUri("base:{$url['path']}", array(
        'query' => $url['query'],
        'fragment' => $fragment,
        'absolute' => $absolute,
      ))->toString();
    }
    catch (Exception $e) {
      // @Todo: log to watchdog
      $url = '';
    }

    // Special case: CiviCRM passes us "*path*?*query*" as a skeleton, but asterisks
    // are invalid and Drupal will attempt to escape them. We unescape them here:
    if ($path == '*path*') {
      // First remove trailing equals sign that has been added since the key '?*query*' has no value.
      $url = rtrim($url, '=');
      $url = urldecode($url);
    }

    return $url;
  }

  /**
   * @inheritDoc
   */
  public function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    $system = new CRM_Utils_System_Drupal8();
    $system->loadBootStrap(array(), FALSE);

    $uid = \Drupal::service('user.auth')->authenticate($name, $password);
    $contact_id = CRM_Core_BAO_UFMatch::getContactId($uid);

    return array($contact_id, $uid, mt_rand());
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
   * @return int|NULL
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
  public function loadBootStrap($params = array(), $loadUser = TRUE, $throwError = TRUE, $realPath = NULL) {
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
    $autoloader = require_once $root . '/vendor/autoload.php';
    // @Todo: do we need to handle case where $_SERVER has no HTTP_HOST key, ie. when run via cli?
    $request = new \Symfony\Component\HttpFoundation\Request(array(), array(), array(), array(), array(), $_SERVER);

    // Create a kernel and boot it.
    \Drupal\Core\DrupalKernel::createFromRequest($request, $autoloader, 'prod')->prepareLegacyRequest($request);

    // Initialize Civicrm
    \Drupal::service('civicrm');

    // We need to call the config hook again, since we now know
    // all the modules that are listening on it (CRM-8655).
    CRM_Utils_Hook::config($config);

    if ($loadUser) {
      if (!empty($params['uid']) && $username = \Drupal\user\Entity\User::load($uid)->getUsername()) {
        $this->loadUser($username);
      }
      elseif (!empty($params['name']) && !empty($params['pass']) && $this->authenticate($params['name'], $params['pass'])) {
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
    $modules = array();

    $module_data = system_rebuild_module_data();
    foreach ($module_data as $module_name => $extension) {
      if (!isset($extension->info['hidden']) && $extension->origin != 'core') {
        $extension->schema_version = drupal_get_installed_schema_version($module_name);
        $modules[] = new CRM_Core_Module('drupal.' . $module_name, ($extension->status == 1 ? TRUE : FALSE));
      }
    }
    return $modules;
  }

}
