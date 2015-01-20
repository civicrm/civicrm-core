<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * Drupal specific stuff goes here
 */
class CRM_Utils_System_Drupal8 extends CRM_Utils_System_DrupalBase {

  /**
   * Create a user in Drupal.
   *
   * @param array $params
   * @param string $mail
   *   Email id for cms user.
   *
   * @return int|bool
   *   uid if user exists, false otherwise
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
   * Update the Drupal user's email address.
   *
   * @param int $ufID
   *   User ID in CMS.
   * @param string $email
   *   Primary contact email address.
   */
  public function updateCMSName($ufID, $email) {
    $user = user_load($ufID);
    if ($user && $user->getEmail() != $email) {
      $user->setEmail($email);

      if (!count($user->validate())) {
        $user->save();
      }
    }
  }

  /**
   * Check if username and email exists in the drupal db
   *
   * @param array $params
   *   Array of name and mail values.
   * @param array $errors
   *   Errors.
   * @param string $emailName
   *   Field label for the 'email'.
   *
   *
   * @return void
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
   * Get the drupal destination string. When this is passed in the
   * URL the user will be directed to it after filling in the drupal form
   *
   * @param CRM_Core_Form $form
   *   Form object representing the 'current' form - to which the user will be returned.
   * @return string
   *   destination value for URL
   */
  public function getLoginDestination(&$form) {
    $args = NULL;

    $id = $form->get('id');
    if ($id) {
      $args .= "&id=$id";
    }
    else {
      $gid = $form->get('gid');
      if ($gid) {
        $args .= "&gid=$gid";
      }
      else {
        // Setup Personal Campaign Page link uses pageId
        $pageId = $form->get('pageId');
        if ($pageId) {
          $component = $form->get('component');
          $args .= "&pageId=$pageId&component=$component&action=add";
        }
      }
    }

    $destination = NULL;
    if ($args) {
      // append destination so user is returned to form they came from after login
      $destination = CRM_Utils_System::currentPath() . '?reset=1' . $args;
    }
    return $destination;
  }

  /**
   * Get user login URL for hosting CMS (method declared in each CMS system class)
   *
   * @param string $destination
   *   If present, add destination to querystring (works for Drupal only).
   *
   * @return string
   *   loginURL for the current CMS
   */
  public function getLoginURL($destination = '') {
    $query = $destination ? array('destination' => $destination) : array();
    return \Drupal::url('user.page', array(), array('query' => $query));
  }


  /**
   * Sets the title of the page
   *
   * @param string $title
   * @param string $pageTitle
   *
   * @return void
   */
  public function setTitle($title, $pageTitle = NULL) {
    if (!$pageTitle) {
      $pageTitle = $title;
    }

    \Drupal::service('civicrm.page_state')->setTitle($pageTitle);
  }

  /**
   * Append an additional breadcrumb tag to the existing breadcrumb
   *
   * @param $breadcrumbs
   *
   * @internal param string $title
   * @internal param string $url
   *
   * @return void
   */
  public function appendBreadCrumb($breadcrumbs) {
    $civicrmPageState = \Drupal::service('civicrm.page_state');
    foreach ($breadcrumbs as $breadcrumb) {
      $civicrmPageState->addBreadcrumb($breadcrumb['title'], $breadcrumb['url']);
    }
  }

  /**
   * Reset an additional breadcrumb tag to the existing breadcrumb
   *
   * @return void
   */
  public function resetBreadCrumb() {
    \Drupal::service('civicrm.page_state')->resetBreadcrumbs();
  }

  /**
   * Append a string to the head of the html file
   *
   * @param string $header
   *   The new string to be appended.
   *
   * @return void
   */
  public function addHTMLHead($header) {
    \Drupal::service('civicrm.page_state')->addHtmlHeader($header);
  }

  /**
   * Add a script file
   *
   * @param $url : string, absolute path to file
   * @param string $region
   *   location within the document: 'html-header', 'page-header', 'page-footer'.
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @return bool
   *   TRUE if we support this operation in this CMS, FALSE otherwise
   */
  public function addScriptUrl($url, $region) {
    $options = array('group' => JS_LIBRARY, 'weight' => 10);
    switch ($region) {
      case 'html-header':
      case 'page-footer':
        $options['scope'] = substr($region, 5);
        break;

      default:
        return FALSE;
    }
    // If the path is within the drupal directory we can use the more efficient 'file' setting
    $options['type'] = $this->formatResourceUrl($url) ? 'file' : 'external';
    \Drupal::service('civicrm.page_state')->addJS($url, $options);
    return TRUE;
  }

  /**
   * Add an inline script
   *
   * @param $code : string, javascript code
   * @param string $region
   *   location within the document: 'html-header', 'page-header', 'page-footer'.
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @return bool
   *   TRUE if we support this operation in this CMS, FALSE otherwise
   */
  public function addScript($code, $region) {
    $options = array('type' => 'inline', 'group' => JS_LIBRARY, 'weight' => 10);
    switch ($region) {
      case 'html-header':
      case 'page-footer':
        $options['scope'] = substr($region, 5);
        break;

      default:
        return FALSE;
    }
    \Drupal::service('civicrm.page_state')->addJS($code, $options);
    return TRUE;
  }

  /**
   * Add a css file
   *
   * @param $url : string, absolute path to file
   * @param string $region
   *   location within the document: 'html-header', 'page-header', 'page-footer'.
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @return bool
   *   TRUE if we support this operation in this CMS, FALSE otherwise
   */
  public function addStyleUrl($url, $region) {
    if ($region != 'html-header') {
      return FALSE;
    }
    $options = array();
    // If the path is within the drupal directory we can use the more efficient 'file' setting
    $options['type'] = $this->formatResourceUrl($url) ? 'file' : 'external';
    \Drupal::service('civicrm.page_state')->addCSS($url, $options);
    return TRUE;
  }

  /**
   * Add an inline style
   *
   * @param $code : string, css code
   * @param string $region
   *   location within the document: 'html-header', 'page-header', 'page-footer'.
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @return bool
   *   TRUE if we support this operation in this CMS, FALSE otherwise
   */
  public function addStyle($code, $region) {
    if ($region != 'html-header') {
      return FALSE;
    }
    $options = array('type' => 'inline');
    \Drupal::service('civicrm.page_state')->addCSS($code, $options);
    return TRUE;
  }

  /**
   * Check if a resource url is within the drupal directory and format appropriately
   *
   * This seems to be a legacy function. We assume all resources are within the drupal
   * directory and always return TRUE. As well, we clean up the $url.
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

    return TRUE;
  }

  /**
   * Rewrite various system urls to https
   *
   * This function does nothing in Drupal 8. Changes to the base_url should be made
   * in settings.php directly.
   *
   * @return void
   */
  public function mapConfigToSSL() {
  }

  /**
   * @param string $path
   *   The base path (eg. civicrm/search/contact).
   * @param string $query
   *   The query string (eg. reset=1&cid=66) but html encoded(?) (optional).
   * @param bool $absolute
   *   Produce an absolute including domain and protocol (optional).
   * @param string $fragment
   *   A named anchor (optional).
   * @param bool $htmlize
   *   Produce a html encoded url (optional).
   * @param bool $frontend
   *   A joomla hack (unused).
   * @param bool $forceBackend
   *   A joomla jack (unused).
   * @return string
   */
  public function url($path = '', $query = '', $absolute = FALSE, $fragment = '', $htmlize = FALSE, $frontend = FALSE, $forceBackend = FALSE) {
    $query = html_entity_decode($query);
    $url = \Drupal\civicrm\CivicrmHelper::parseURL("{$path}?{$query}");

    try {
      $url = \Drupal::url($url['route_name'], array(), array(
        'query' => $url['query'],
        'absolute' => $absolute,
        'fragment' => $fragment,
      ));
    }
    catch (Exception $e) {
      $url = '';
    }

    if ($htmlize) {
      $url = htmlentities($url);
    }
    return $url;
  }


  /**
   * Authenticate the user against the drupal db
   *
   * @param string $name
   *   The user name.
   * @param string $password
   *   The password for the above user name.
   * @param bool $loadCMSBootstrap
   *   Load cms bootstrap?.
   * @param NULL|string $realPath filename of script
   *
   * @return array|bool
   *   [contactID, ufID, uniqueString] if success else false if no auth
   *
   *   This always bootstraps Drupal
   */
  public function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    (new CRM_Utils_System_Drupal8())->loadBootStrap(array(), FALSE);

    $uid = \Drupal::service('user.auth')->authenticate($name, $password);
    $contact_id = CRM_Core_BAO_UFMatch::getContactId($uid);

    return array($contact_id, $uid, mt_rand());
  }

  /**
   * Load user into session
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
   * Determine the native ID of the CMS user
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
   * Set a message in the UF to display to a user
   *
   * @param string $message
   *   The message to set.
   */
  public function setMessage($message) {
    drupal_set_message($message);
  }

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
   * Load drupal bootstrap
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
    $autoloader = require_once $root . '/core/vendor/autoload.php';
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
   * @param null $path
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
   * Check if user is logged in.
   *
   * @return bool
   */
  public function isUserLoggedIn() {
    return \Drupal::currentUser()->isAuthenticated();
  }

  /**
   * Get currently logged in user uf id.
   *
   * @return int
   *   $userID logged in user uf id.
   */
  public function getLoggedInUfID() {
    if ($id = \Drupal::currentUser()->id()) {
      return $id;
    }
  }

  /**
   * Get the default location for CiviCRM blocks
   *
   * @return string
   */
  public function getDefaultBlockLocation() {
    return 'sidebar_first';
  }

}
