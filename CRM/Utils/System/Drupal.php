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
 * Drupal specific stuff goes here
 */
class CRM_Utils_System_Drupal extends CRM_Utils_System_DrupalBase {

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
    if (!variable_get('user_email_verification', TRUE) || $admin) {
      $form_state['input']['pass'] = ['pass1' => $params['cms_pass'], 'pass2' => $params['cms_pass']];
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

    $form = drupal_retrieve_form('user_register_form', $form_state);
    $form_state['process_input'] = 1;
    $form_state['submitted'] = 1;
    $form['#array_parents'] = [];
    $form['#tree'] = FALSE;
    drupal_process_form('user_register_form', $form, $form_state);

    $config->inCiviCRM = FALSE;

    if (form_get_errors()) {
      return FALSE;
    }
    return $form_state['user']->uid;
  }

  /**
   * Appends a Drupal 7 Javascript file when the CRM Menubar Javascript file has
   * been included. The file is added before the menu bar so we can properly listen
   * for the menu bar ready event.
   */
  public function appendCoreResources(\Civi\Core\Event\GenericHookEvent $event) {
    $menuBarFileIndex = array_search('js/crm.menubar.js', $event->list);

    if ($menuBarFileIndex !== FALSE) {
      array_splice($event->list, $menuBarFileIndex, 0, ['js/crm.drupal7.js']);
    }
  }

  /**
   * @inheritDoc
   */
  public function updateCMSName($ufID, $ufName) {
    // CRM-5555
    if (function_exists('user_load')) {
      $user = user_load($ufID);
      if ($user->mail != $ufName) {
        user_save($user, ['mail' => $ufName]);
        $user = user_load($ufID);
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function checkUserNameEmailExists(&$params, &$errors, $emailName = 'email') {
    if ($drupal_errors = form_get_errors()) {
      // unset Drupal messages to avoid twice display of errors
      unset($_SESSION['messages']);
      $errors = array_merge($errors, $drupal_errors);
    }

    if (!empty($params['name'])) {
      if ($nameError = user_validate_name($params['name'])) {
        $errors['cms_name'] = $nameError;
      }
      else {
        $uid = db_query(
          "SELECT uid FROM {users} WHERE name = :name",
          [':name' => $params['name']]
        )->fetchField();
        if ((bool) $uid) {
          $errors['cms_name'] = ts('The username %1 is already taken. Please select another username.', [1 => $params['name']]);
        }
      }
    }

    if (!empty($params['mail'])) {
      if ($emailError = user_validate_mail($params['mail'])) {
        $errors[$emailName] = $emailError;
      }
      else {
        $uid = db_query(
          "SELECT uid FROM {users} WHERE mail = :mail",
          [':mail' => $params['mail']]
        )->fetchField();
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
    $query = $destination ? ['destination' => $destination] : NULL;
    return CRM_Utils_System::url('user', $query, TRUE);
  }

  /**
   * @inheritDoc
   */
  public function setTitle($title, $pageTitle = NULL) {
    if (arg(0) == 'civicrm') {
      if (!$pageTitle) {
        $pageTitle = $title;
      }

      drupal_set_title($pageTitle, PASS_THROUGH);
    }
  }

  /**
   * @inheritDoc
   */
  public function appendBreadCrumb($breadCrumbs) {
    $breadCrumb = drupal_get_breadcrumb();

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
    drupal_set_breadcrumb($breadCrumb);
  }

  /**
   * @inheritDoc
   */
  public function resetBreadCrumb() {
    $bc = [];
    drupal_set_breadcrumb($bc);
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
      drupal_add_html_head($data, $key);
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
    // If the path is within the drupal directory we can use the more efficient 'file' setting
    $params['type'] = $this->formatResourceUrl($url) ? 'file' : 'external';
    drupal_add_js($url, $params);
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
    drupal_add_js($code, $params);
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
    // If the path is within the drupal directory we can use the more efficient 'file' setting
    $params['type'] = $this->formatResourceUrl($url) ? 'file' : 'external';
    drupal_add_css($url, $params);
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
    drupal_add_css($code, $params);
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
   * Get the name of the users table.
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
    require_once 'DB.php';

    /* Before we do any loading, let's start the session and write to it.
     * We typically call authenticate only when we need to bootstrap the CMS
     * directly via Civi and hence bypass the normal CMS auth and bootstrap
     * process typically done in CLI and cron scripts. See: CRM-12648
     */
    $session = CRM_Core_Session::singleton();
    $session->set('civicrmInitSession', TRUE);

    $config = CRM_Core_Config::singleton();

    $ufDSN = $config->userFrameworkDSN;

    try {
      $dbDrupal = CRM_Utils_SQL::connect($ufDSN);
    }
    catch (Exception $e) {
      throw new CRM_Core_Exception("Cannot connect to drupal db via $ufDSN, " . $e->getMessage());
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
      // SOAP cannot load drupal bootstrap and hence we do it the old way
      // Contact CiviSMTP folks if we run into issues with this :)
      $cmsPath = $config->userSystem->cmsRootPath($realPath);

      require_once "$cmsPath/includes/bootstrap.inc";
      require_once "$cmsPath/includes/password.inc";

      $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
      $name = $dbDrupal->escapeSimple($strtolower($name));
      $userFrameworkUsersTableName = $this->getUsersTableName();

      // LOWER in query below roughly translates to 'hurt my database without deriving any benefit' See CRM-19811.
      $sql = "
SELECT u.*
FROM   {$userFrameworkUsersTableName} u
WHERE  LOWER(u.name) = '$name'
AND    u.status = 1
";

      $query = $dbDrupal->query($sql);
      $row = $query->fetchRow(DB_FETCHMODE_ASSOC);

      if ($row) {
        $fakeDrupalAccount = drupal_anonymous_user();
        $fakeDrupalAccount->name = $name;
        $fakeDrupalAccount->pass = $row['pass'];
        $passwordCheck = user_check_password($password, $fakeDrupalAccount);
        if ($passwordCheck) {
          $userUid = $row['uid'];
          $userMail = $row['mail'];
        }
      }
    }

    if ($userUid && $userMail) {
      CRM_Core_BAO_UFMatch::synchronizeUFMatch($account, $userUid, $userMail, 'Drupal');
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
   * e.g. for drupal: records a watchdog message about the new session, saves the login timestamp,
   * calls hook_user op 'login' and generates a new session.
   *
   * @param array $params
   *
   * FIXME: Document values accepted/required by $params
   */
  public function userLoginFinalize($params = []) {
    user_login_finalize($params);
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
   * @inheritDoc
   */
  public function logout() {
    module_load_include('inc', 'user', 'user.pages');
    return user_logout();
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
   */
  public function loadBootStrap($params = [], $loadUser = TRUE, $throwError = TRUE, $realPath = NULL) {
    //take the cms root path.
    $cmsPath = $this->cmsRootPath($realPath);

    if (!file_exists("$cmsPath/includes/bootstrap.inc")) {
      if ($throwError) {
        throw new Exception('Sorry, could not locate bootstrap.inc');
      }
      return FALSE;
    }
    // load drupal bootstrap
    chdir($cmsPath);
    define('DRUPAL_ROOT', $cmsPath);

    // For drupal multi-site CRM-11313
    if ($realPath && !str_contains($realPath, 'sites/all/modules/')) {
      preg_match('@sites/([^/]*)/modules@s', $realPath, $matches);
      if (!empty($matches[1])) {
        $_SERVER['HTTP_HOST'] = $matches[1];
      }
    }
    require_once 'includes/bootstrap.inc';
    // @ to suppress notices eg 'DRUPALFOO already defined'.
    @drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

    if (!function_exists('module_exists')) {
      if ($throwError) {
        throw new Exception('Sorry, could not load drupal bootstrap.');
      }
      return FALSE;
    }
    if (!module_exists('civicrm')) {
      if ($throwError) {
        throw new Exception('Sorry, drupal cannot find CiviCRM');
      }
      return FALSE;
    }

    // seems like we've bootstrapped drupal
    $config = CRM_Core_Config::singleton();

    // lets also fix the clean url setting
    // CRM-6948
    $config->cleanURL = (int) variable_get('clean_url', '0');

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
      //load user, we need to check drupal permissions.
      $name = !empty($params['name']) ? $params['name'] : trim($_REQUEST['name'] ?? '');
      $pass = !empty($params['pass']) ? $params['pass'] : trim($_REQUEST['pass'] ?? '');

      if ($name) {
        $uid = user_authenticate($name, $pass);
        if (!$uid) {
          if ($throwError) {
            throw new Exception('Sorry, unrecognized username or password.');
          }
          return FALSE;
        }
      }
    }

    if ($uid) {
      $account = user_load($uid);
      if ($account && $account->uid && $account->status) {
        global $user;
        $user = $account;
        return TRUE;
      }
    }

    if ($throwError) {
      throw new Exception('Sorry, can not load CMS user account.');
    }

    // CRM-6948: When using loadBootStrap, it's implicit that CiviCRM has already loaded its settings
    // which means that define(CIVICRM_CLEANURL) was correctly set.
    // So we correct it
    $config = CRM_Core_Config::singleton();
    $config->cleanURL = (int) variable_get('clean_url', '0');

    // CRM-8655: Drupal wasn't available during bootstrap, so hook_civicrm_config never executes
    // FIXME: This call looks redundant with the earlier call in the same function. Consider removing it.
    CRM_Utils_Hook::config($config);

    return FALSE;
  }

  /**
   * Get CMS root path.
   *
   * @param string $scriptFilename
   *
   * @return null|string
   */
  public function cmsRootPath($scriptFilename = NULL) {
    $cmsRoot = $valid = NULL;

    if (!is_null($scriptFilename)) {
      $path = $scriptFilename;
    }
    else {
      $path = $_SERVER['SCRIPT_FILENAME'];
    }

    if (function_exists('drush_get_context')) {
      // drush anyway takes care of multisite install etc
      return drush_get_context('DRUSH_DRUPAL_ROOT');
    }

    global $civicrm_paths;
    if (!empty($civicrm_paths['cms.root']['path'])) {
      return $civicrm_paths['cms.root']['path'];
    }

    // CRM-7582
    $pathVars = explode('/',
      str_replace('//', '/',
        str_replace('\\', '/', $path)
      )
    );

    //lets store first var,
    //need to get back for windows.
    $firstVar = array_shift($pathVars);

    // Remove the script name to remove an necessary iteration of the loop.
    array_pop($pathVars);

    // CRM-7429 -- do check for uppermost 'includes' dir, which would
    // work for multisite installation.
    do {
      $cmsRoot = $firstVar . '/' . implode('/', $pathVars);
      $cmsIncludePath = "$cmsRoot/includes";
      // Stop if we find bootstrap.
      if (file_exists("$cmsIncludePath/bootstrap.inc")) {
        $valid = TRUE;
        break;
      }
      //remove one directory level.
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

    //CRM-7803 -from d7 onward.
    $config = CRM_Core_Config::singleton();
    if (function_exists('variable_get') &&
      module_exists('locale') &&
      function_exists('language_negotiation_get')
    ) {
      global $language;

      //does user configuration allow language
      //support from the URL (Path prefix or domain)
      if (language_negotiation_get('language') == 'locale-url') {
        $urlType = variable_get('locale_language_negotiation_url_part');

        //url prefix
        if ($urlType == LOCALE_LANGUAGE_NEGOTIATION_URL_PREFIX) {
          if (isset($language->prefix) && $language->prefix) {
            if ($addLanguagePart) {
              $url .= $language->prefix . '/';
            }
            if ($removeLanguagePart) {
              $url = str_replace("/{$language->prefix}/", '/', $url);
            }
          }
        }
        //domain
        if ($urlType == LOCALE_LANGUAGE_NEGOTIATION_URL_DOMAIN) {
          if (isset($language->domain) && $language->domain) {
            if ($addLanguagePart) {
              $cleanedUrl = preg_replace('#^https?://#', '', $language->domain);
              // drupal function base_path() adds a "/" to the beginning and end of the returned path
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
   * Wrapper for og_membership creation.
   *
   * @param int $ogID
   *   Organic Group ID.
   * @param int $drupalID
   *   Drupal User ID.
   */
  public function og_membership_create($ogID, $drupalID) {
    if (function_exists('og_entity_query_alter')) {
      // sort-of-randomly chose a function that only exists in the // 7.x-2.x branch
      //
      // @TODO Find more solid way to check - try system_get_info('module', 'og').
      //
      // Also, since we don't know how to get the entity type of the // group, we'll assume it's 'node'
      og_group('node', $ogID, ['entity' => user_load($drupalID)]);
    }
    else {
      // Works for the OG 7.x-1.x branch
      og_group($ogID, ['entity' => user_load($drupalID)]);
    }
  }

  /**
   * Wrapper for og_membership deletion.
   *
   * @param int $ogID
   *   Organic Group ID.
   * @param int $drupalID
   *   Drupal User ID.
   */
  public function og_membership_delete($ogID, $drupalID) {
    if (function_exists('og_entity_query_alter')) {
      // sort-of-randomly chose a function that only exists in the 7.x-2.x branch
      // TODO: Find a more solid way to make this test
      // Also, since we don't know how to get the entity type of the group, we'll assume it's 'node'
      og_ungroup('node', $ogID, 'user', user_load($drupalID));
    }
    else {
      // Works for the OG 7.x-1.x branch
      og_ungroup($ogID, 'user', user_load($drupalID));
    }
  }

  /**
   * @inheritDoc
   */
  public function getTimeZoneString() {
    global $user;
    // Note that 0 is a valid timezone (GMT) so we use strlen not empty to check.
    if (variable_get('configurable_timezones', 1) && $user->uid && isset($user->timezone) && strlen($user->timezone)) {
      $timezone = $user->timezone;
    }
    else {
      $timezone = variable_get('date_default_timezone', NULL);
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
    drupal_add_http_header($name, $value);
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
        drupal_session_commit();
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function viewsIntegration(): string {
    global $databases;
    $config = CRM_Core_Config::singleton();
    $text = '';
    $drupal_prefix = '';
    if (isset($databases['default']['default']['prefix'])) {
      if (is_array($databases['default']['default']['prefix'])) {
        $drupal_prefix = $databases['default']['default']['prefix']['default'];
      }
      else {
        $drupal_prefix = $databases['default']['default']['prefix'];
      }
    }

    if ($this->viewsExists() &&
      (
        $config->dsn != $config->userFrameworkDSN || !empty($drupal_prefix)
      )
    ) {
      $text = '<div>' . ts('To enable CiviCRM Views integration, add or update the following item in the <code>settings.php</code> file:') . '</div>';

      $tableNames = CRM_Core_DAO::getTableNames();
      asort($tableNames);
      $text .= '<pre>$databases[\'default\'][\'default\'][\'prefix\']= [';

      // Add default prefix.
      $text .= "\n  'default' => '$drupal_prefix',";
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
        drupal_set_breadcrumb('');
        drupal_maintenance_theme();
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
    // Drupal function handles the server being behind a proxy securely. We
    // still have legacy ipn methods that reach this point without bootstrapping
    // hence the check that the fn exists.
    return function_exists('ip_address') ? ip_address() : ($_SERVER['REMOTE_ADDR'] ?? NULL);
  }

  public function isMaintenanceMode(): bool {
    return variable_get('maintenance_mode', FALSE);
  }

}
