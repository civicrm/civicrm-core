<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Backdrop-specific logic that differs from Drupal.
 */
class CRM_Utils_System_Backdrop extends CRM_Utils_System_DrupalBase {

  /**
   * @inheritDoc
   */
  public function createUser(&$params, $mail) {
    $form_state = form_state_defaults();

    $form_state['input'] = array(
      'name' => $params['cms_name'],
      'mail' => $params[$mail],
      'op' => 'Create new account',
    );

    $admin = user_access('administer users');
    if (!config_get('system.core', 'user_email_verification') || $admin) {
      $form_state['input']['pass'] = array('pass1' => $params['cms_pass'], 'pass2' => $params['cms_pass']);
    }

    if (!empty($params['notify'])) {
      $form_state['input']['notify'] = $params['notify'];
    }

    $form_state['rebuild'] = FALSE;
    $form_state['programmed'] = TRUE;
    $form_state['complete form'] = FALSE;
    $form_state['method'] = 'post';
    $form_state['build_info']['args'] = array();
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
    $form['#array_parents'] = array();
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
   * Check if username and email exists in the Backdrop db.
   *
   * @param array $params
   *   Array of name and mail values.
   * @param array $errors
   *   Array of errors.
   * @param string $emailName
   *   Field label for the 'email'.
   */
  public static function checkUserNameEmailExists(&$params, &$errors, $emailName = 'email') {
    $errors = form_get_errors();
    if ($errors) {
      // unset Backdrop messages to avoid twice display of errors
      unset($_SESSION['messages']);
    }

    if (!empty($params['name'])) {
      if ($nameError = user_validate_name($params['name'])) {
        $errors['cms_name'] = $nameError;
      }
      else {
        $uid = db_query("SELECT uid FROM {users} WHERE name = :name", array(':name' => $params['name']))->fetchField();
        if ((bool) $uid) {
          $errors['cms_name'] = ts('The username %1 is already taken. Please select another username.', array(1 => $params['name']));
        }
      }
    }

    if (!empty($params['mail'])) {
      if (!valid_email_address($params['mail'])) {
        $errors[$emailName] = ts('The e-mail address %1 is not valid.', array('%1' => $params['mail']));
      }
      else {
        $uid = db_query("SELECT uid FROM {users} WHERE mail = :mail", array(':mail' => $params['mail']))->fetchField();
        if ((bool) $uid) {
          $resetUrl = url('user/password');
          $errors[$emailName] = ts('The email address %1 already has an account associated with it. <a href="%2">Have you forgotten your password?</a>',
            array(1 => $params['mail'], 2 => $resetUrl)
          );
        }
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function getLoginURL($destination = '') {
    $query = $destination ? array('destination' => $destination) : array();
    return url('user', array('query' => $query, 'absolute' => TRUE));
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
          $args = array('cid', 'mid');
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
    $bc = array();
    backdrop_set_breadcrumb($bc);
  }

  /**
   * @inheritDoc
   */
  public function addHTMLHead($header) {
    static $count = 0;
    if (!empty($header)) {
      $key = 'civi_' . ++$count;
      $data = array(
        '#type' => 'markup',
        '#markup' => $header,
      );
      backdrop_add_html_head($data, $key);
    }
  }

  /**
   * @inheritDoc
   */
  public function addScriptUrl($url, $region) {
    $params = array('group' => JS_LIBRARY, 'weight' => 10);
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
    $params = array('type' => 'inline', 'group' => JS_LIBRARY, 'weight' => 10);
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
    $params = array();
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
    $params = array('type' => 'inline');
    backdrop_add_css($code, $params);
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function mapConfigToSSL() {
    global $base_url;
    $base_url = str_replace('http://', 'https://', $base_url);
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

    $dbBackdrop = DB::connect($config->userFrameworkDSN);
    if (DB::isError($dbBackdrop)) {
      CRM_Core_Error::fatal("Cannot connect to Backdrop database via $config->userFrameworkDSN, " . $dbBackdrop->getMessage());
    }

    $account = $userUid = $userMail = NULL;
    if ($loadCMSBootstrap) {
      $bootStrapParams = array();
      if ($name && $password) {
        $bootStrapParams = array(
          'name' => $name,
          'pass' => $password,
        );
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
      return array($contactID, $userUid, mt_rand());
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
  public function userLoginFinalize($params = array()) {
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
    $languages = language_list(FALSE, TRUE);

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
   * @return int|NULL
   */
  public function getUfId($username) {
    $user = user_load_by_name($username);
    if (empty($user->uid)) {
      return NULL;
    }
    return $user->uid;
  }

  /**
   * @inheritDoc
   */
  public function logout() {
    module_load_include('inc', 'user', 'user.pages');
    user_logout();
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
  public function loadBootStrap($params = array(), $loadUser = TRUE, $throwError = TRUE, $realPath = NULL) {
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
    define('BACKDROP_ROOT', $cmsPath);

    // For Backdrop multi-site CRM-11313
    if ($realPath && strpos($realPath, 'sites/all/modules/') === FALSE) {
      preg_match('@sites/([^/]*)/modules@s', $realPath, $matches);
      if (!empty($matches[1])) {
        $_SERVER['HTTP_HOST'] = $matches[1];
      }
    }
    require_once "$cmsPath/core/includes/bootstrap.inc";
    require_once "$cmsPath/core/includes/config.inc";
    backdrop_bootstrap(BACKDROP_BOOTSTRAP_FULL);

    // Explicitly setting error reporting, since we cannot handle Backdrop
    // related notices.
    error_reporting(1);
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
    CRM_Utils_Hook::config($config);

    if (!$loadUser) {
      return TRUE;
    }

    $uid = CRM_Utils_Array::value('uid', $params);
    if (!$uid) {
      // Load the user we need to check Backdrop permissions.
      $name = CRM_Utils_Array::value('name', $params, FALSE) ? $params['name'] : trim(CRM_Utils_Array::value('name', $_REQUEST));
      $pass = CRM_Utils_Array::value('pass', $params, FALSE) ? $params['pass'] : trim(CRM_Utils_Array::value('pass', $_REQUEST));

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
      $ufID = user_uid_optional_to_arg(array());
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
        user_role_revoke_permissions($rid, array($oldPerm));
        user_role_grant_permissions($rid, $newPerms);
      }
    }
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
      og_group('node', $ogID, array('entity' => user_load($userID)));
    }
    else {
      // Works for the OG 7.x-1.x branch
      og_group($ogID, array('entity' => user_load($userID)));
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
      if (is_object($match)) {
        $match->free();
      }
    }

    return array(
      'contactCount' => $contactCount,
      'contactMatching' => $contactMatching,
      'contactCreated' => $contactCreated,
    );
  }

  /**
   * @inheritDoc
   */
  public function clearResourceCache() {
    _backdrop_flush_css_js();
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
    return array();
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

    return array(
      'url' => $filesURL,
      'path' => CRM_Utils_File::baseFilePath(),
    );
  }

  /**
   * Check if a resource url is within the Backdrop directory and format appropriately.
   *
   * @param $url (reference)
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
    if (strpos($url, $base_url) === 0) {
      $file = trim(str_replace($base_url, '', $url), '/');
      // CRM-18130: Custom CSS URL not working if aliased or rewritten
      if (file_exists(BACKDROP_ROOT . $file)) {
        $url = $file;
        $internal = TRUE;
      }
    }
    // Handle relative urls that are within the CiviCRM module directory
    elseif (strpos($url, $base) === 0) {
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
      $sites = array();
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
   * @param array $list
   */
  public function appendCoreResources(&$list) {
    $list[] = 'css/backdrop.css';
    $list[] = 'js/crm.backdrop.js';
  }

}
