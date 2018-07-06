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
 * Drupal specific stuff goes here.
 */
class CRM_Utils_System_Drupal6 extends CRM_Utils_System_DrupalBase {

  /**
   * Theme output.
   *
   * If we are using a theming system, invoke theme, else just print the content.
   *
   * @param string $content
   *   The content that will be themed.
   * @param bool $print
   *   Are we displaying to the screen or bypassing theming?.
   * @param bool $maintenance
   *   For maintenance mode.
   *
   * @return null|string
   *   prints content on stdout
   */
  public function theme(&$content, $print = FALSE, $maintenance = FALSE) {
    // TODO: Simplify; this was copied verbatim from CiviCRM 3.4's multi-UF theming function, but that's more complex than necessary
    if (function_exists('theme') && !$print) {
      if ($maintenance) {
        drupal_set_breadcrumb('');
        drupal_maintenance_theme();
      }

      // Arg 3 for D6 theme() is "show_blocks". Previously, we passed
      // through a badly named variable ("$args") which was almost always
      // TRUE (except on fatal error screen).  However, this feature is
      // non-functional on D6 default themes, was purposefully removed from
      // D7, has no analog in other our other CMS's, and clutters the code.
      // Hard-wiring to TRUE should be OK.
      $out = theme('page', $content, TRUE);
    }
    else {
      $out = $content;
    }

    print $out;
    return NULL;
  }

  /**
   * Create user.
   *
   * @inheritDoc
   */
  public function createUser(&$params, $mail) {
    $form_state = array();
    $form_state['values'] = array(
      'name' => $params['cms_name'],
      'mail' => $params[$mail],
      'op' => 'Create new account',
    );

    $admin = user_access('administer users');
    if (!variable_get('user_email_verification', TRUE) || $admin) {
      $form_state['values']['pass']['pass1'] = $params['cms_pass'];
      $form_state['values']['pass']['pass2'] = $params['cms_pass'];
    }

    $config = CRM_Core_Config::singleton();

    // we also need to redirect b
    $config->inCiviCRM = TRUE;

    $form = drupal_retrieve_form('user_register', $form_state);
    $form['#post'] = $form_state['values'];
    drupal_prepare_form('user_register', $form, $form_state);

    // remove the captcha element from the form prior to processing
    unset($form['captcha']);

    drupal_process_form('user_register', $form, $form_state);

    $config->inCiviCRM = FALSE;

    if (form_get_errors() || !isset($form_state['user'])) {
      return FALSE;
    }
    return $form_state['user']->uid;
  }

  /**
   * @inheritDoc
   */
  public function updateCMSName($ufID, $ufName) {
    // CRM-5555
    if (function_exists('user_load')) {
      $user = user_load(array('uid' => $ufID));
      if ($user->mail != $ufName) {
        user_save($user, array('mail' => $ufName));
        $user = user_load(array('uid' => $ufID));
      }
    }
  }

  /**
   * Check if username and email exists in the drupal db.
   *
   * @param array $params
   *   Array of name and mail values.
   * @param array $errors
   *   Array of errors.
   * @param string $emailName
   *   Field label for the 'email'.
   */
  public function checkUserNameEmailExists(&$params, &$errors, $emailName = 'email') {
    $config = CRM_Core_Config::singleton();

    $dao = new CRM_Core_DAO();
    $name = $dao->escape(CRM_Utils_Array::value('name', $params));
    $email = $dao->escape(CRM_Utils_Array::value('mail', $params));
    _user_edit_validate(NULL, $params);
    $errors = form_get_errors();
    if ($errors) {
      if (!empty($errors['name'])) {
        $errors['cms_name'] = $errors['name'];
      }
      if (!empty($errors['mail'])) {
        $errors[$emailName] = $errors['mail'];
      }
      // also unset drupal messages to avoid twice display of errors
      unset($_SESSION['messages']);
    }

    // Do the name check manually.
    $nameError = user_validate_name($params['name']);
    if ($nameError) {
      $errors['cms_name'] = $nameError;
    }

    // LOWER in query below roughly translates to 'hurt my database without deriving any benefit' See CRM-19811.
    $sql = "
      SELECT name, mail
      FROM {users}
      WHERE (LOWER(name) = LOWER('$name')) OR (LOWER(mail) = LOWER('$email'))
    ";

    $result = db_query($sql);
    $row = db_fetch_array($result);
    if (!$row) {
      return;
    }

    $user = NULL;

    if (!empty($row)) {
      $dbName = CRM_Utils_Array::value('name', $row);
      $dbEmail = CRM_Utils_Array::value('mail', $row);
      if (strtolower($dbName) == strtolower($name)) {
        $errors['cms_name'] = ts('The username %1 is already taken. Please select another username.',
          array(1 => $name)
        );
      }
      if (strtolower($dbEmail) == strtolower($email)) {
        if (empty($email)) {
          $errors[$emailName] = ts('You cannot create an email account for a contact with no email',
            array(1 => $email)
          );
        }
        else {
          $errors[$emailName] = ts('This email %1 already has an account associated with it. Please select another email.',
            array(1 => $email)
          );
        }
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function setTitle($title, $pageTitle = NULL) {
    if (!$pageTitle) {
      $pageTitle = $title;
    }
    if (arg(0) == 'civicrm') {
      //set drupal title
      drupal_set_title($pageTitle);
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
    drupal_set_breadcrumb($breadCrumb);
  }

  /**
   * @inheritDoc
   */
  public function resetBreadCrumb() {
    $bc = array();
    drupal_set_breadcrumb($bc);
  }

  /**
   * Append a string to the head of the html file.
   *
   * @param string $head
   *   The new string to be appended.
   */
  public function addHTMLHead($head) {
    drupal_set_html_head($head);
  }

  /**
   * Add a css file.
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
    if ($region != 'html-header' || !$this->formatResourceUrl($url)) {
      return FALSE;
    }
    drupal_add_css($url);
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
    //@todo this 'PEAR-y' stuff is only required when bookstrap is not being loaded which is rare
    // if ever now.
    // probably if bootstrap is loaded this call
    // CRM_Utils_System::loadBootStrap($bootStrapParams, TRUE, TRUE, $realPath); would be
    // sufficient to do what this fn does. It does exist as opposed to return which might need some hanky-panky to make
    // safe in the unknown situation where authenticate might be called & it is important that
    // false is returned
    require_once 'DB.php';

    $config = CRM_Core_Config::singleton();

    $dbDrupal = DB::connect($config->userFrameworkDSN);
    if (DB::isError($dbDrupal)) {
      CRM_Core_Error::fatal("Cannot connect to drupal db via $config->userFrameworkDSN, " . $dbDrupal->getMessage());
    }

    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
    $dbpassword = md5($password);
    $name = $dbDrupal->escapeSimple($strtolower($name));
    $userFrameworkUsersTableName = $this->getUsersTableName();
    $sql = 'SELECT u.* FROM ' . $userFrameworkUsersTableName . " u WHERE LOWER(u.name) = '$name' AND u.pass = '$dbpassword' AND u.status = 1";
    $query = $dbDrupal->query($sql);

    $user = NULL;
    // need to change this to make sure we matched only one row
    while ($row = $query->fetchRow(DB_FETCHMODE_ASSOC)) {
      CRM_Core_BAO_UFMatch::synchronizeUFMatch($user, $row['uid'], $row['mail'], 'Drupal');
      $contactID = CRM_Core_BAO_UFMatch::getContactId($row['uid']);
      if (!$contactID) {
        return FALSE;
      }
      else {
        //success
        if ($loadCMSBootstrap) {
          $bootStrapParams = array();
          if ($name && $password) {
            $bootStrapParams = array(
              'name' => $name,
              'pass' => $password,
            );
          }
          CRM_Utils_System::loadBootStrap($bootStrapParams, TRUE, TRUE, $realPath);
        }
        return array($contactID, $row['uid'], mt_rand());
      }
    }
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function loadUser($username) {
    global $user;
    $user = user_load(array('name' => $username));
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
   * e.g. for drupal : records a watchdog message about the new session,
   * saves the login timestamp, calls hook_user op 'login' and generates a new session.
   *
   * @param array $params
   *
   * FIXME: Document values accepted/required by $params
   */
  public function userLoginFinalize($params = array()) {
    user_authenticate_finalize($params);
  }

  /**
   * Determine the native ID of the CMS user.
   *
   * @param string $username
   * @return int|NULL
   */
  public function getUfId($username) {
    $user = user_load(array('name' => $username));
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
  public function loadBootStrap($params = array(), $loadUser = TRUE, $throwError = TRUE, $realPath = NULL) {
    //take the cms root path.
    $cmsPath = $this->cmsRootPath($realPath);

    if (!file_exists("$cmsPath/includes/bootstrap.inc")) {
      if ($throwError) {
        echo '<br />Sorry, could not locate bootstrap.inc\n';
        exit();
      }
      return FALSE;
    }
    // load drupal bootstrap
    chdir($cmsPath);
    define('DRUPAL_ROOT', $cmsPath);

    // For drupal multi-site CRM-11313
    if ($realPath && strpos($realPath, 'sites/all/modules/') === FALSE) {
      preg_match('@sites/([^/]*)/modules@s', $realPath, $matches);
      if (!empty($matches[1])) {
        $_SERVER['HTTP_HOST'] = $matches[1];
      }
    }
    require_once 'includes/bootstrap.inc';
    // @ to suppress notices eg 'DRUPALFOO already defined'.
    @drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

    // explicitly setting error reporting, since we cannot handle drupal related notices
    error_reporting(1);
    if (!function_exists('module_exists') || !module_exists('civicrm')) {
      if ($throwError) {
        echo '<br />Sorry, could not load drupal bootstrap.';
        exit();
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
    CRM_Utils_Hook::config($config);

    if (!$loadUser) {
      return TRUE;
    }
    global $user;
    // If $uid is passed in, authentication has been done already.
    $uid = CRM_Utils_Array::value('uid', $params);
    if (!$uid) {
      //load user, we need to check drupal permissions.
      $name = CRM_Utils_Array::value('name', $params, FALSE) ? $params['name'] : trim(CRM_Utils_Array::value('name', $_REQUEST));
      $pass = CRM_Utils_Array::value('pass', $params, FALSE) ? $params['pass'] : trim(CRM_Utils_Array::value('pass', $_REQUEST));

      if ($name) {
        $user = user_authenticate(array('name' => $name, 'pass' => $pass));
        if (!$user->uid) {
          if ($throwError) {
            echo '<br />Sorry, unrecognized username or password.';
            exit();
          }
          return FALSE;
        }
        else {
          return TRUE;
        }
      }
    }

    if ($uid) {
      $account = user_load($uid);
      if ($account && $account->uid) {
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
    $config->cleanURL = (int) variable_get('clean_url', '0');

    // CRM-8655: Drupal wasn't available during bootstrap, so hook_civicrm_config never executes
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

    //lets remove sript name to reduce one iteration.
    array_pop($pathVars);

    //CRM-7429 --do check for upper most 'includes' dir,
    //which would effectually work for multisite installation.
    do {
      $cmsRoot = $firstVar . '/' . implode('/', $pathVars);
      $cmsIncludePath = "$cmsRoot/includes";
      // Stop if we found bootstrap.
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

    //up to d6 only, already we have code in place for d7
    $config = CRM_Core_Config::singleton();
    if (function_exists('variable_get') &&
      module_exists('locale')
    ) {
      global $language;

      //get the mode.
      $mode = variable_get('language_negotiation', LANGUAGE_NEGOTIATION_NONE);

      //url prefix / path.
      if (isset($language->prefix) &&
        $language->prefix &&
        in_array($mode, array(
          LANGUAGE_NEGOTIATION_PATH,
          LANGUAGE_NEGOTIATION_PATH_DEFAULT,
        ))
      ) {

        if ($addLanguagePart) {
          $url .= $language->prefix . '/';
        }
        if ($removeLanguagePart) {
          $url = str_replace("/{$language->prefix}/", '/', $url);
        }
      }
      if (isset($language->domain) &&
        $language->domain &&
        $mode == LANGUAGE_NEGOTIATION_DOMAIN
      ) {

        if ($addLanguagePart) {
          $url = CRM_Utils_File::addTrailingSlash($language->domain, '/');
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
    foreach ($roles as $rid => $roleName) {
      $permList = db_result(db_query('SELECT perm FROM {permission} WHERE rid = %d', $rid));
      $perms = drupal_map_assoc(explode(', ', $permList));
      unset($perms[$oldPerm]);
      $perms = $perms + drupal_map_assoc($newPerms);
      $permList = implode(', ', $perms);
      db_query('UPDATE {permission} SET perm = "%s" WHERE rid = %d', $permList, $rid);
      /* @codingStandardsIgnoreStart
        if ( ! empty( $roles ) ) {
            $rids = implode(',', array_keys($roles));
            db_query( 'UPDATE {permission} SET perm = CONCAT( perm, \', edit all events\') WHERE rid IN (' . implode(',', array_keys($roles)) . ')' );
            db_query( "UPDATE {permission} SET perm = REPLACE( perm, '%s', '%s' ) WHERE rid IN ($rids)",
                $oldPerm, implode(', ', $newPerms) );
      @codingStandardsIgnoreEnd */
    }
  }

  /**
   * @inheritDoc
   */
  public function getModules() {
    $result = array();
    $q = db_query('SELECT name, status FROM {system} WHERE type = \'module\' AND schema_version <> -1');
    while ($row = db_fetch_object($q)) {
      $result[] = new CRM_Core_Module('drupal.' . $row->name, ($row->status == 1) ? TRUE : FALSE);
    }
    return $result;
  }

  /**
   * @inheritDoc
   */
  public function getLoginURL($destination = '') {
    $config = CRM_Core_Config::singleton();
    $loginURL = $config->userFrameworkBaseURL;
    $loginURL .= 'user';
    if (!empty($destination)) {
      // append destination so user is returned to form they came from after login
      $loginURL .= '?destination=' . urlencode($destination);
    }
    return $loginURL;
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
    og_save_subscription($ogID, $drupalID, array('is_active' => 1));
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
    og_delete_subscription($ogID, $drupalID);
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

    // Retrieved timezone will be represented as GMT offset in seconds but, according
    // to the doc for the overridden method, ought to be returned as a region string
    // (e.g., America/Havana).
    if (strlen($timezone)) {
      $timezone = timezone_name_from_abbr("", (int) $timezone);
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
    drupal_set_header("$name: $value");
  }

  /**
   * @inheritDoc
   */
  public function synchronizeUsers() {
    $config = CRM_Core_Config::singleton();
    if (PHP_SAPI != 'cli') {
      set_time_limit(300);
    }
    $rows = array();
    $id = 'uid';
    $mail = 'mail';
    $name = 'name';

    $result = db_query("SELECT uid, mail, name FROM {users} where mail != ''");

    while ($row = db_fetch_array($result)) {
      $rows[] = $row;
    }

    $user = new StdClass();
    $uf = $config->userFramework;
    $contactCount = 0;
    $contactCreated = 0;
    $contactMatching = 0;
    foreach ($rows as $row) {
      $user->$id = $row[$id];
      $user->$mail = $row[$mail];
      $user->$name = $row[$name];
      $contactCount++;
      if ($match = CRM_Core_BAO_UFMatch::synchronizeUFMatch($user, $row[$id], $row[$mail], $uf, 1, 'Individual', TRUE)) {
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

}
