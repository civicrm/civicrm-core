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
 * Backdrop-specific logic that differs from Drupal.
 */
class CRM_Utils_System_Backdrop extends CRM_Utils_System_DrupalBase {

  /**
   * @inheritDoc
   */
  public function createUser(&$params, $mailParam) {
    $form_state = form_state_defaults();

    $form_state['input'] = [
      'name' => $params['cms_name'],
      'mail' => $params[$mailParam],
      'op' => 'Create new account',
    ];

    $admin = user_access('administer users');
    $user_register_conf = config_get('system.core', 'user_register');
    if (!$admin && $user_register_conf == 'admin_only') {
      return FALSE;
    }

    if (!config_get('system.core', 'user_email_verification') || $admin) {
      $form_state['input']['pass'] = $params['cms_pass'];
    }

    if (!empty($params['notify'])) {
      $form_state['input']['notify'] = $params['notify'];
    }

    $form_state['rebuild'] = FALSE;
    $form_state['programmed'] = TRUE;
    $form_state['complete form'] = FALSE;
    $form_state['method'] = 'post';
    $form_state['build_info']['args'] = [];
    /*
     * if we want to submit this form more than once in a process (e.g. create more than one user)
     * we must force it to validate each time for this form. Otherwise it will not validate
     * subsequent submissions and the manner in which the password is passed in will be invalid
     */
    $form_state['must_validate'] = TRUE;
    $config = CRM_Core_Config::singleton();

    // we also need to redirect b
    $config->inCiviCRM = TRUE;

    $form = backdrop_retrieve_form('user_register_form', $form_state);
    $form_state['process_input'] = 1;
    $form_state['submitted'] = 1;
    $form['#array_parents'] = [];
    $form['#tree'] = FALSE;
    backdrop_process_form('user_register_form', $form, $form_state);

    $config->inCiviCRM = FALSE;

    if (form_get_errors()) {
      return FALSE;
    }
    return $form_state['user']->uid;
  }

  /**
   * @inheritDoc
   */
  public function updateCMSName($ufID, $email) {
    // CRM-5555
    if (function_exists('user_load')) {
      $user = user_load($ufID);
      if ($user->mail != $email) {
        $user->mail = $email;
        $user->save();
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function checkUserNameEmailExists(&$params, &$errors, $emailName = 'email') {
    if ($backdrop_errors = form_get_errors()) {
      // unset Backdrop messages to avoid twice display of errors
      unset($_SESSION['messages']);
      $errors = array_merge($errors, $backdrop_errors);
    }

    if (!empty($params['name'])) {
      if ($nameError = user_validate_name($params['name'])) {
        $errors['cms_name'] = $nameError;
      }
      else {
        $uid = db_query("SELECT uid FROM {users} WHERE name = :name", [':name' => $params['name']])->fetchField();
        if ((bool) $uid) {
          $errors['cms_name'] = ts('The username %1 is already taken. Please select another username.', [1 => $params['name']]);
        }
      }
    }

    if (!empty($params['mail'])) {
      if (!valid_email_address($params['mail'])) {
        $errors[$emailName] = ts('The e-mail address %1 is not valid.', [1 => $params['mail']]);
      }
      else {
        $uid = db_query("SELECT uid FROM {users} WHERE mail = :mail", [':mail' => $params['mail']])->fetchField();
        if ((bool) $uid) {
          $resetUrl = url('user/password');
          $errors[$emailName] = ts('The email address %1 already has an account associated with it. <a href="%2">Have you forgotten your password?</a>',
            [1 => $params['mail'], 2 => $resetUrl]
          );
        }
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function getLoginURL($destination = '') {
    $query = $destination ? ['destination' => $destination] : [];
    return url('user/login', ['query' => $query, 'absolute' => TRUE]);
  }

  /**
   * @inheritDoc
   */
  public function setTitle($title, $pageTitle = NULL) {
    if (arg(0) == 'civicrm') {
      if (!$pageTitle) {
        $pageTitle = $title;
      }

      backdrop_set_title($pageTitle, PASS_THROUGH);
    }
  }

  /**
   * @inheritDoc
   */
  public function appendBreadCrumb($breadCrumbs) {
    $breadCrumb = backdrop_get_breadcrumb();

    if (is_array($breadCrumbs)) {
      foreach ($breadCrumbs as $crumbs) {
        if (stripos($crumbs['url'], 'id%%')) {
          $args = ['cid', 'mid'];
          foreach ($args as $a) {
            $val = CRM_Utils_Request::retrieve($a, 'Positive', CRM_Core_DAO::$_nullObject,
              FALSE, NULL, $_GET
            );
            if ($val) {
              $crumbs['url'] = str_ireplace("%%{$a}%%", $val, $crumbs['url']);
            }
          }
        }
        $breadCrumb[] = "<a href=\"{$crumbs['url']}\">{$crumbs['title']}</a>";
      }
    }
    backdrop_set_breadcrumb($breadCrumb);
  }

  /**
   * @inheritDoc
   */
  public function resetBreadCrumb() {
    $bc = [];
    backdrop_set_breadcrumb($bc);
  }

  /**
   * @inheritDoc
   */
  public function addHTMLHead($header) {
    static $count = 0;
    if (!empty($header)) {
      $key = 'civi_' . ++$count;
      $data = [
        '#type' => 'markup',
        '#markup' => $header,
      ];
      backdrop_add_html_head($data, $key);
    }
  }

  /**
   * @inheritDoc
   */
  public function addScriptUrl($url, $region) {
    $params = ['group' => JS_LIBRARY, 'weight' => 10];
    switch ($region) {
      case 'html-header':
      case 'page-footer':
        $params['scope'] = substr($region, 5);
        break;

      default:
        return FALSE;
    }
    // If the path is within the Backdrop directory we can use the more efficient 'file' setting
    $params['type'] = $this->formatResourceUrl($url) ? 'file' : 'external';
    backdrop_add_js($url, $params);
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function addScript($code, $region) {
    $params = ['type' => 'inline', 'group' => JS_LIBRARY, 'weight' => 10];
    switch ($region) {
      case 'html-header':
      case 'page-footer':
        $params['scope'] = substr($region, 5);
        break;

      default:
        return FALSE;
    }
    backdrop_add_js($code, $params);
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function addStyleUrl($url, $region) {
    if ($region != 'html-header') {
      return FALSE;
    }
    $params = [];
    // If the path is within the Backdrop directory we can use the more efficient 'file' setting
    $params['type'] = $this->formatResourceUrl($url) ? 'file' : 'external';
    backdrop_add_css($url, $params);
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function addStyle($code, $region) {
    if ($region != 'html-header') {
      return FALSE;
    }
    $params = ['type' => 'inline'];
    backdrop_add_css($code, $params);
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function mapConfigToSSL() {
    global $base_url;
    $base_url = str_replace('http://', 'https://', (string) $base_url);
  }

  /**
   * Get the name of the table that stores the user details.
   *
   * @return string
   */
  protected function getUsersTableName() {
    $userFrameworkUsersTableName = Civi::settings()->get('userFrameworkUsersTableName');
    if (empty($userFrameworkUsersTableName)) {
      $userFrameworkUsersTableName = 'users';
    }
    return $userFrameworkUsersTableName;
  }

  /**
   * @inheritDoc
   */
  public function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    $config = CRM_Core_Config::singleton();

    $ufDSN = $config->userFrameworkDSN;
    try {
      $dbBackdrop = CRM_Utils_SQL::connect($ufDSN);
    }
    catch (Exception $e) {
      throw new CRM_Core_Exception("Cannot connect to Backdrop database via $ufDSN, " . $e->getMessage());
    }

    $account = $userUid = $userMail = NULL;
    if ($loadCMSBootstrap) {
      $bootStrapParams = [];
      if ($name && $password) {
        $bootStrapParams = [
          'name' => $name,
          'pass' => $password,
        ];
      }
      CRM_Utils_System::loadBootStrap($bootStrapParams, TRUE, TRUE, $realPath);

      global $user;
      if ($user) {
        $userUid = $user->uid;
        $userMail = $user->mail;
      }
    }
    else {
      // CRM-8638
      // SOAP cannot load Backdrop bootstrap and hence we do it the old way
      // Contact CiviSMTP folks if we run into issues with this :)
      $cmsPath = $this->cmsRootPath();
      if (!defined('BACKDROP_ROOT')) {
        define(BACKDROP_ROOT, $cmsPath);
      }
      require_once "$cmsPath/core/includes/bootstrap.inc";
      require_once "$cmsPath/core/includes/password.inc";

      $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
      $name = $dbBackdrop->escapeSimple($strtolower($name));
      $userFrameworkUsersTableName = $this->getUsersTableName();

      // LOWER in query below roughly translates to 'hurt my database without deriving any benefit' See CRM-19811.
      $sql = "
SELECT u.*
FROM   {$userFrameworkUsersTableName} u
WHERE  LOWER(u.name) = '$name'
AND    u.status = 1
";

      $query = $dbBackdrop->query($sql);
      $row = $query->fetchRow(DB_FETCHMODE_ASSOC);

      if ($row) {
        $fakeAccount = backdrop_anonymous_user();
        $fakeAccount->name = $name;
        $fakeAccount->pass = $row['pass'];
        $passwordCheck = user_check_password($password, $fakeAccount);
        if ($passwordCheck) {
          $userUid = $row['uid'];
          $userMail = $row['mail'];
        }
      }
    }

    if ($userUid && $userMail) {
      CRM_Core_BAO_UFMatch::synchronizeUFMatch($account, $userUid, $userMail, 'Backdrop');
      $contactID = CRM_Core_BAO_UFMatch::getContactId($userUid);
      if (!$contactID) {
        return FALSE;
      }
      return [$contactID, $userUid, mt_rand()];
    }
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function loadUser($username) {
    global $user;

    $user = user_load_by_name($username);

    if (empty($user->uid)) {
      return FALSE;
    }

    $uid = $user->uid;
    $contact_id = CRM_Core_BAO_UFMatch::getContactId($uid);

    // lets store contact id and user id in session
    $session = CRM_Core_Session::singleton();
    $session->set('ufID', $uid);
    $session->set('userID', $contact_id);
    return TRUE;
  }

  /**
   * Perform any post login activities required by the UF -
   * For Backdrop this could mean recording a watchdog message about the new
   * session, saving the login timestamp, calling hook_user_login(), etc.
   *
   * @param array $params
   *   The array of form values submitted by the user.
   */
  public function userLoginFinalize($params = []) {
    user_login_finalize($params);
  }

  /**
   * @inheritDoc
   */
  public function isUserRegistrationPermitted() {
    if (config_get('system.core', 'user_register') == 'admin_only') {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function isPasswordUserGenerated() {
    if (config_get('system.core', 'user_email_verification') == TRUE) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @inheritdoc
   */
  public function verifyPassword($params, &$errors) {
    if ($backdrop_errors = form_get_errors()) {
      // unset Backdrop messages to avoid twice display of errors
      unset($_SESSION['messages']);
      $errors = array_merge($errors, $backdrop_errors);
    }

    $password = trim($params['pass']);
    $username = $params['name'];
    $email = $params['mail'];

    module_load_include('password.inc', 'user', 'user');
    $reject_weak = user_password_reject_weak($username);
    if (!$reject_weak) {
      return;
    }

    $strength = _user_password_evaluate_strength($password, $username, $email);

    if ($strength < config('system.core')->get('user_password_strength_threshold')) {
      $password_errors[] = ts('The password is too weak. Please consider making your password longer or more complex: that it contains a number of lower- and uppercase letters, digits and punctuation.');
    }

    if (backdrop_strtolower($password) == backdrop_strtolower($username)) {
      $password_errors[] = ts('The password cannot be the same as the username.');
    }
    if (backdrop_strtolower($password) == backdrop_strtolower($email)) {
      $password_errors[] = ts('The password cannot be the same as the email.');
    }

    if (!empty($password_errors)) {
      $errors['cms_pass'] = ts('Weak passwords are rejected. Please note the following issues: %1', [1 => implode(' ', $password_errors)]);
    }
  }

  /**
   * @inheritDoc
   */
  public function getUFLocale() {
    // return CiviCRM’s xx_YY locale that either matches Backdrop’s Chinese locale
    // (for CRM-6281), Backdrop’s xx_YY or is retrieved based on Backdrop’s xx
    // sometimes for CLI based on order called, this might not be set and/or empty
    global $language;

    if (empty($language)) {
      return NULL;
    }

    if ($language->langcode == 'zh-hans') {
      return 'zh_CN';
    }

    if ($language->langcode == 'zh-hant') {
      return 'zh_TW';
    }

    if (preg_match('/^.._..$/', $language->langcode)) {
      return $language->langcode;
    }

    return CRM_Core_I18n_PseudoConstant::longForShort(substr($language->langcode, 0, 2));
  }

  /**
   * @inheritDoc
   */
  public function setUFLocale($civicrm_language) {
    global $language;

    $langcode = substr($civicrm_language, 0, 2);
    $languages = language_list();

    if (isset($languages[$langcode])) {
      $language = $languages[$langcode];

      // Config must be re-initialized to reset the base URL
      // otherwise links will have the wrong language prefix/domain.
      $config = CRM_Core_Config::singleton();
      $config->free();

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Determine the native ID of the CMS user.
   *
   * @param string $username
   * @return int|null
   */
  public function getUfId($username) {
    $user = user_load_by_name($username);
    if (empty($user->uid)) {
      return NULL;
    }
    return $user->uid;
  }

  /**
   * Get the default location for CiviCRM blocks.
   *
   * @return string
   */
  public function getDefaultBlockLocation() {
    return 'sidebar_first';
  }

  /**
   * Load Backdrop bootstrap.
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
   */
  public function loadBootStrap($params = [], $loadUser = TRUE, $throwError = TRUE, $realPath = NULL) {
    $cmsPath = $this->cmsRootPath($realPath);

    if (!file_exists("$cmsPath/core/includes/bootstrap.inc")) {
      if ($throwError) {
        echo '<br />Sorry, could not locate bootstrap.inc\n';
        exit();
      }
      return FALSE;
    }
    // load Backdrop bootstrap
    chdir($cmsPath);
    if (!defined('BACKDROP_ROOT')) {
      define('BACKDROP_ROOT', $cmsPath);
    }

    // For Backdrop multi-site CRM-11313
    if ($realPath && !str_contains($realPath, 'sites/all/modules/')) {
      preg_match('@sites/([^/]*)/modules@s', $realPath, $matches);
      if (!empty($matches[1])) {
        $_SERVER['HTTP_HOST'] = $matches[1];
      }
    }
    require_once "$cmsPath/core/includes/bootstrap.inc";
    require_once "$cmsPath/core/includes/config.inc";
    backdrop_bootstrap(BACKDROP_BOOTSTRAP_FULL);

    if (!function_exists('module_exists') || !module_exists('civicrm')) {
      if ($throwError) {
        echo '<br />Sorry, could not load Backdrop bootstrap.';
        exit();
      }
      return FALSE;
    }

    // Backdrop successfully bootstrapped.
    $config = CRM_Core_Config::singleton();

    // lets also fix the clean url setting
    // CRM-6948
    $config->cleanURL = (int) config_get('system.core', 'clean_url');

    // we need to call the config hook again, since we now know
    // all the modules that are listening on it, does not apply
    // to J! and WP as yet
    // CRM-8655
    CRM_Utils_Hook::config($config, ['uf' => TRUE]);

    if (!$loadUser) {
      return TRUE;
    }

    $uid = $params['uid'] ?? NULL;
    if (!$uid) {
      // Load the user we need to check Backdrop permissions.
      $name = !empty($params['name']) ? $params['name'] : trim($_REQUEST['name'] ?? '');
      $pass = !empty($params['pass']) ? $params['pass'] : trim($_REQUEST['pass'] ?? '');

      if ($name) {
        $uid = user_authenticate($name, $pass);
        if (!$uid) {
          if ($throwError) {
            echo '<br />Sorry, unrecognized username or password.';
            exit();
          }
          return FALSE;
        }
      }
    }

    if ($uid) {
      $account = user_load($uid);
      if ($account && $account->uid) {
        global $user;
        $user = $account;
        return TRUE;
      }
    }

    if ($throwError) {
      echo '<br />Sorry, can not load CMS user account.';
      exit();
    }

    // CRM-6948: When using loadBootStrap, it's implicit that CiviCRM has already loaded its settings
    // which means that define(CIVICRM_CLEANURL) was correctly set.
    // So we correct it
    $config = CRM_Core_Config::singleton();
    $config->cleanURL = (int) config_get('system.core', 'clean_url');

    // CRM-8655: Backdrop wasn't available during bootstrap, so
    // hook_civicrm_config() never executes.
    // FIXME: This call looks redundant with the earlier call in the same function. Consider removing it.
    CRM_Utils_Hook::config($config);

    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function cmsRootPath($scriptFilename = NULL) {
    global $civicrm_paths;
    if (!empty($civicrm_paths['cms.root']['path'])) {
      return $civicrm_paths['cms.root']['path'];
    }

    if (defined('BACKDROP_ROOT')) {
      return BACKDROP_ROOT;
    }

    $cmsRoot = NULL;
    $valid = NULL;

    if (!is_null($scriptFilename)) {
      $path = $scriptFilename;
    }
    else {
      $path = $_SERVER['SCRIPT_FILENAME'];
    }

    // CRM-7582
    $pathVars = explode('/',
      str_replace('//', '/',
        str_replace('\\', '/', $path)
      )
    );

    // Keep the first directory name for later.
    $firstVar = array_shift($pathVars);

    // Remove script name to reduce one iteration.
    array_pop($pathVars);

    // CRM-7429 -- do check for uppermost 'includes' dir, which would
    // work for multisite installation.
    do {
      $cmsRoot = $firstVar . '/' . implode('/', $pathVars);
      // Stop if we find backdrop signature file.
      if (file_exists("$cmsRoot/core/misc/backdrop.js")) {
        $valid = TRUE;
        break;
      }
      // Remove one directory level.
      array_pop($pathVars);
    } while (count($pathVars));

    return ($valid) ? $cmsRoot : NULL;
  }

  /**
   * @inheritDoc
   */
  public function isUserLoggedIn() {
    $isloggedIn = FALSE;
    if (function_exists('user_is_logged_in')) {
      $isloggedIn = user_is_logged_in();
    }

    return $isloggedIn;
  }

  /**
   * @inheritDoc
   */
  public function getLoggedInUfID() {
    $ufID = NULL;
    if (function_exists('user_is_logged_in') &&
      user_is_logged_in() &&
      function_exists('user_uid_optional_to_arg')
    ) {
      $ufID = user_uid_optional_to_arg([]);
    }

    return $ufID;
  }

  /**
   * @inheritDoc
   */
  public function languageNegotiationURL($url, $addLanguagePart = TRUE, $removeLanguagePart = FALSE) {
    if (empty($url)) {
      return $url;
    }

    if (function_exists('config_get') &&
      module_exists('locale') &&
      function_exists('language_negotiation_get')
    ) {
      global $language;

      // Check if language support from the URL (Path prefix or domain) is set.
      if (language_negotiation_get('language') == 'locale-url') {
        $urlType = config_get('locale.settings', 'locale_language_negotiation_url_part');

        // URL prefix negotiation.
        if ($urlType == LANGUAGE_NEGOTIATION_URL_PREFIX) {
          if (isset($language->prefix) && $language->prefix) {
            if ($addLanguagePart) {
              $url .= $language->prefix . '/';
            }
            if ($removeLanguagePart) {
              $url = str_replace("/{$language->prefix}/", '/', $url);
            }
          }
        }
        // Domain negotiation.
        if ($urlType == LANGUAGE_NEGOTIATION_URL_DOMAIN) {
          if (isset($language->domain) && $language->domain) {
            if ($addLanguagePart) {
              $cleanedUrl = preg_replace('#^https?://#', '', $language->domain);
              // Backdrop function base_path() adds a "/" to the beginning and
              // end of the returned path.
              if (substr($cleanedUrl, -1) == '/') {
                $cleanedUrl = substr($cleanedUrl, 0, -1);
              }
              $url = (CRM_Utils_System::isSSL() ? 'https' : 'http') . '://' . $cleanedUrl . base_path();
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
   * Find any users/roles/security-principals with the given permission
   * and replace it with one or more permissions.
   *
   * @param string $oldPerm
   * @param array $newPerms
   *   Array, strings.
   */
  public function replacePermission($oldPerm, $newPerms) {
    $roles = user_roles(FALSE, $oldPerm);
    if (!empty($roles)) {
      foreach (array_keys($roles) as $rid) {
        user_role_revoke_permissions($rid, [$oldPerm]);
        user_role_grant_permissions($rid, $newPerms);
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function getCiviSourceStorage():array {
    global $civicrm_root;
    $config = CRM_Core_Config::singleton();

    // Don't use $config->userFrameworkBaseURL; it has garbage on it.
    // More generally, we shouldn't be using $config here.
    if (!defined('CIVICRM_UF_BASEURL')) {
      throw new RuntimeException('Undefined constant: CIVICRM_UF_BASEURL');
    }
    $baseURL = CRM_Utils_File::addTrailingSlash(CIVICRM_UF_BASEURL, '/');
    if (CRM_Utils_System::isSSL()) {
      $baseURL = str_replace('http://', 'https://', $baseURL);
    }

    $cmsPath = $config->userSystem->cmsRootPath();
    $userFrameworkResourceURL = $baseURL . str_replace("$cmsPath/", '',
      str_replace('\\', '/', $civicrm_root)
    );

    return [
      'url' => CRM_Utils_File::addTrailingSlash($userFrameworkResourceURL, '/'),
      'path' => CRM_Utils_File::addTrailingSlash($civicrm_root),
    ];
  }

  /**
   * Wrapper for og_membership creation.
   *
   * @param int $ogID
   *   Organic Group ID.
   * @param int $userID
   *   Backdrop User ID.
   */
  public function og_membership_create($ogID, $userID) {
    if (function_exists('og_entity_query_alter')) {
      // sort-of-randomly chose a function that only exists in the // 7.x-2.x branch
      //
      // @TODO Find more solid way to check - try system_get_info('module', 'og').
      //
      // Also, since we don't know how to get the entity type of the // group, we'll assume it's 'node'
      og_group('node', $ogID, ['entity' => user_load($userID)]);
    }
    else {
      // Works for the OG 7.x-1.x branch
      og_group($ogID, ['entity' => user_load($userID)]);
    }
  }

  /**
   * Wrapper for og_membership deletion.
   *
   * @param int $ogID
   *   Organic Group ID.
   * @param int $userID
   *   Backdrop User ID.
   */
  public function og_membership_delete($ogID, $userID) {
    if (function_exists('og_entity_query_alter')) {
      // sort-of-randomly chose a function that only exists in the 7.x-2.x branch
      // TODO: Find a more solid way to make this test
      // Also, since we don't know how to get the entity type of the group, we'll assume it's 'node'
      og_ungroup('node', $ogID, 'user', user_load($userID));
    }
    else {
      // Works for the OG 7.x-1.x branch
      og_ungroup($ogID, 'user', user_load($userID));
    }
  }

  /**
   * @inheritDoc
   */
  public function getTimeZoneString() {
    global $user;
    // Note that 0 is a valid timezone (GMT) so we use strlen not empty to check.
    if (config_get('system.date', 'user_configurable_timezones') && $user->uid && isset($user->timezone) && strlen($user->timezone)) {
      $timezone = $user->timezone;
    }
    else {
      $timezone = config_get('system.date', 'default_timezone');
    }
    if (!$timezone) {
      $timezone = parent::getTimeZoneString();
    }
    return $timezone;
  }

  /**
   * @inheritDoc
   */
  public function setHttpHeader($name, $value) {
    backdrop_add_http_header($name, $value);
  }

  /**
   * @inheritDoc
   */
  public function synchronizeUsers() {
    $config = CRM_Core_Config::singleton();
    if (PHP_SAPI != 'cli') {
      set_time_limit(300);
    }
    $id = 'uid';
    $mail = 'mail';
    $name = 'name';

    $result = db_query("SELECT uid, mail, name FROM {users} where mail != ''");

    $user = new StdClass();
    $uf = $config->userFramework;
    $contactCount = 0;
    $contactCreated = 0;
    $contactMatching = 0;
    foreach ($result as $row) {
      $user->$id = $row->$id;
      $user->$mail = $row->$mail;
      $user->$name = $row->$name;
      $contactCount++;
      if ($match = CRM_Core_BAO_UFMatch::synchronizeUFMatch($user, $row->$id, $row->$mail, $uf, 1, 'Individual', TRUE)) {
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
    if (function_exists('module_invoke_all')) {
      if (!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update') {
        module_invoke_all('exit');
      }
      if (!defined('_CIVICRM_FAKE_SESSION')) {
        backdrop_session_commit();
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function clearResourceCache() {
    // Sometimes metadata gets cleared while the cms isn't bootstrapped.
    if (function_exists('_backdrop_flush_css_js')) {
      _backdrop_flush_css_js();
    }
  }

  /**
   * Get all the contact emails for users that have a specific permission.
   *
   * @param string $permissionName
   *   Name of the permission we are interested in.
   *
   * @return string
   *   a comma separated list of email addresses
   */
  public function permissionEmails($permissionName) {
    // FIXME!!!!
    return [];
  }

  /**
   * @inheritdoc
   */
  public function getDefaultFileStorage() {
    $config = CRM_Core_Config::singleton();
    $baseURL = CRM_Utils_System::languageNegotiationURL($config->userFrameworkBaseURL, FALSE, TRUE);

    $siteName = $this->parseBackdropSiteNameFromRequest('/files/civicrm');
    if ($siteName) {
      $filesURL = $baseURL . "sites/$siteName/files/civicrm/";
    }
    else {
      $filesURL = $baseURL . "files/civicrm/";
    }

    return [
      'url' => $filesURL,
      'path' => CRM_Utils_File::baseFilePath(),
    ];
  }

  /**
   * Check if a resource url is within the Backdrop directory and format appropriately.
   *
   * @param string $url
   *   URL (reference).
   *
   * @return bool
   *   TRUE for internal paths, FALSE for external. The backdrop_add_js fn is able to add js more
   *   efficiently if it is known to be in the Backdrop site
   */
  public function formatResourceUrl(&$url) {
    $internal = FALSE;
    $base = CRM_Core_Config::singleton()->resourceBase;
    global $base_url;
    // Handle absolute urls
    // compares $url (which is some unknown/untrusted value from a third-party dev) to the CMS's base url (which is independent of civi's url)
    // to see if the url is within our Backdrop dir, if it is we are able to treated it as an internal url
    if (str_starts_with($url, $base_url)) {
      $file = trim(str_replace($base_url, '', $url), '/');
      // CRM-18130: Custom CSS URL not working if aliased or rewritten
      if (file_exists(BACKDROP_ROOT . $file)) {
        $url = $file;
        $internal = TRUE;
      }
    }
    // Handle relative urls that are within the CiviCRM module directory
    elseif (str_starts_with($url, $base)) {
      $internal = TRUE;
      $url = $this->appendCoreDirectoryToResourceBase(dirname(backdrop_get_path('module', 'civicrm')) . '/') . trim(substr($url, strlen($base)), '/');
    }
    // Strip query string
    $q = strpos($url, '?');
    if ($q && $internal) {
      $url = substr($url, 0, $q);
    }
    return $internal;
  }

  /**
   * @inheritDoc
   */
  public function setMessage($message) {
    backdrop_set_message($message);
  }

  /**
   * @inheritDoc
   */
  public function permissionDenied() {
    backdrop_access_denied();
  }

  /**
   * @inheritDoc
   */
  public function flush() {
    backdrop_flush_all_caches();
  }

  /**
   * Determine if Backdrop multi-site applies to the current request -- and,
   * specifically, determine the name of the multisite folder.
   *
   * @param string $flagFile
   *   Check if $flagFile exists inside the site dir.
   * @return null|string
   *   string, e.g. `bar.example.com` if using multisite.
   *   NULL if using the default site.
   */
  private function parseBackdropSiteNameFromRequest($flagFile = '') {
    $phpSelf = array_key_exists('PHP_SELF', $_SERVER) ? $_SERVER['PHP_SELF'] : '';
    $httpHost = array_key_exists('HTTP_HOST', $_SERVER) ? $_SERVER['HTTP_HOST'] : '';
    if (empty($httpHost)) {
      $httpHost = parse_url(CIVICRM_UF_BASEURL, PHP_URL_HOST);
      if (parse_url(CIVICRM_UF_BASEURL, PHP_URL_PORT)) {
        $httpHost .= ':' . parse_url(CIVICRM_UF_BASEURL, PHP_URL_PORT);
      }
    }

    $confdir = $this->cmsRootPath() . '/sites';

    if (file_exists($confdir . "/sites.php")) {
      include $confdir . "/sites.php";
    }
    else {
      $sites = [];
    }

    $uri = explode('/', $phpSelf);
    $server = explode('.', implode('.', array_reverse(explode(':', rtrim($httpHost, '.')))));
    for ($i = count($uri) - 1; $i > 0; $i--) {
      for ($j = count($server); $j > 0; $j--) {
        $dir = implode('.', array_slice($server, -$j)) . implode('.', array_slice($uri, 0, $i));
        if (file_exists("$confdir/$dir" . $flagFile)) {
          \Civi::$statics[__CLASS__]['drupalSiteName'] = $dir;
          return \Civi::$statics[__CLASS__]['drupalSiteName'];
        }
        // check for alias
        if (isset($sites[$dir]) && file_exists("$confdir/{$sites[$dir]}" . $flagFile)) {
          \Civi::$statics[__CLASS__]['drupalSiteName'] = $sites[$dir];
          return \Civi::$statics[__CLASS__]['drupalSiteName'];
        }
      }
    }
  }

  /**
   * Append Backdrop CSS and JS to coreResourcesList.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public function appendCoreResources(\Civi\Core\Event\GenericHookEvent $e) {
    $e->list[] = 'css/backdrop.css';
    $e->list[] = 'js/crm.backdrop.js';
  }

  /**
   * Start a new session.
   */
  public function sessionStart() {
    if (function_exists('backdrop_session_start')) {
      // https://issues.civicrm.org/jira/browse/CRM-14356
      if (!(isset($GLOBALS['lazy_session']) && $GLOBALS['lazy_session'] == TRUE)) {
        backdrop_session_start();
      }
      $_SESSION = [];
    }
    else {
      session_start();
    }
  }

  /**
   * Return the CMS-specific url for its permissions page
   * @return array
   */
  public function getCMSPermissionsUrlParams() {
    return ['ufAccessURL' => url('admin/config/people/permissions')];
  }

  /**
   * Get the CRM database as a 'prefix'.
   *
   * This returns a string that can be prepended to a query to include a CRM table.
   *
   * However, this string should contain backticks, or not, in accordance with the
   * CMS's drupal views expectations, if any.
   */
  public function getCRMDatabasePrefix(): string {
    return str_replace('`', '', parent::getCRMDatabasePrefix());
  }

  /**
   * Return the CMS-specific UF Group Types for profiles.
   * @return array
   */
  public function getUfGroupTypes() {
    return [
      'User Registration' => ts('Backdrop User Registration'),
      'User Account' => ts('View/Edit Backdrop User Account'),
    ];
  }

  /**
   * @inheritdoc
   */
  public function viewsIntegration(): string {
    global $databases;
    $config = CRM_Core_Config::singleton();
    $text = '';
    $backdrop_prefix = '';
    if (isset($databases['default']['default']['prefix'])) {
      if (is_array($databases['default']['default']['prefix'])) {
        $backdrop_prefix = $databases['default']['default']['prefix']['default'];
      }
      else {
        $backdrop_prefix = $databases['default']['default']['prefix'];
      }
    }

    if ($this->viewsExists() &&
      (
        $config->dsn != $config->userFrameworkDSN || !empty($backdrop_prefix)
      )
    ) {
      $text = '<div>' . ts('To enable CiviCRM Views integration, add or update the following item in the <code>settings.php</code> file:') . '</div>';

      $tableNames = CRM_Core_DAO::getTableNames();
      asort($tableNames);

      $text .= '<pre>$database_prefix = [';

      // Add default prefix.
      $text .= "\n  'default' => '$backdrop_prefix',";
      $prefix = $this->getCRMDatabasePrefix();
      foreach ($tableNames as $tableName) {
        $text .= "\n  '" . str_pad($tableName . "'", 41) . " => '{$prefix}',";
      }
      $text .= "\n];</pre>";
    }

    return $text;
  }

  /**
   * @inheritdoc
   */
  public function theme(&$content, $print = FALSE, $maintenance = FALSE) {
    $ret = FALSE;

    if (!$print) {
      if ($maintenance) {
        backdrop_set_breadcrumb('');
        backdrop_maintenance_theme();
        if ($region = CRM_Core_Region::instance('html-header', FALSE)) {
          CRM_Utils_System::addHTMLHead($region->render(''));
        }
        print theme('maintenance_page', ['content' => $content]);
        exit();
      }
      $ret = TRUE;
    }
    $out = $content;

    if ($ret) {
      return $out;
    }
    else {
      print $out;
      return NULL;
    }
  }

  /**
   * @inheritdoc
   */
  public function ipAddress():?string {
    // Backdrop function handles the server being behind a proxy securely. We
    // still have legacy ipn methods that reach this point without bootstrapping
    // hence the check that the fn exists.
    return function_exists('ip_address') ? ip_address() : ($_SERVER['REMOTE_ADDR'] ?? NULL);
  }

  public function isMaintenanceMode(): bool {
    return state_get('maintenance_mode', FALSE);
  }

}
