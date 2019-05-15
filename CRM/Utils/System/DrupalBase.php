<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */

/**
 * Drupal specific stuff goes here
 */
abstract class CRM_Utils_System_DrupalBase extends CRM_Utils_System_Base {

  /**
   * Does this CMS / UF support a CMS specific logging mechanism?
   * @var bool
   * @todo - we should think about offering up logging mechanisms in a way that is also extensible by extensions
   */
  public $supports_UF_Logging = TRUE;

  /**
   */
  public function __construct() {
    /**
     * deprecated property to check if this is a drupal install. The correct method is to have functions on the UF classes for all UF specific
     * functions and leave the codebase oblivious to the type of CMS
     * @deprecated
     * @var bool
     */
    $this->is_drupal = TRUE;
    $this->supports_form_extensions = TRUE;
  }

  /**
   * @inheritdoc
   */
  public function getDefaultFileStorage() {
    $config = CRM_Core_Config::singleton();
    $baseURL = CRM_Utils_System::languageNegotiationURL($config->userFrameworkBaseURL, FALSE, TRUE);

    $siteName = $this->parseDrupalSiteNameFromRequest('/files/civicrm');
    if ($siteName) {
      $filesURL = $baseURL . "sites/$siteName/files/civicrm/";
    }
    else {
      $filesURL = $baseURL . "sites/default/files/civicrm/";
    }

    return [
      'url' => $filesURL,
      'path' => CRM_Utils_File::baseFilePath(),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultSiteSettings($dir) {
    $config = CRM_Core_Config::singleton();
    $siteName = $siteRoot = NULL;
    $matches = [];
    if (preg_match(
      '|/sites/([\w\.\-\_]+)/|',
      $config->templateCompileDir,
      $matches
    )) {
      $siteName = $matches[1];
      if ($siteName) {
        $siteName = "/sites/$siteName/";
        $siteNamePos = strpos($dir, $siteName);
        if ($siteNamePos !== FALSE) {
          $siteRoot = substr($dir, 0, $siteNamePos);
        }
      }
    }
    $url = $config->userFrameworkBaseURL;
    return [$url, $siteName, $siteRoot];
  }

  /**
   * Check if a resource url is within the drupal directory and format appropriately.
   *
   * @param $url (reference)
   *
   * @return bool
   *   TRUE for internal paths, FALSE for external. The drupal_add_js fn is able to add js more
   *   efficiently if it is known to be in the drupal site
   */
  public function formatResourceUrl(&$url) {
    $internal = FALSE;
    $base = CRM_Core_Config::singleton()->resourceBase;
    global $base_url;
    // Strip query string
    $q = strpos($url, '?');
    $url_path = $q ? substr($url, 0, $q) : $url;
    // Handle absolute urls
    // compares $url (which is some unknown/untrusted value from a third-party dev) to the CMS's base url (which is independent of civi's url)
    // to see if the url is within our drupal dir, if it is we are able to treated it as an internal url
    if (strpos($url_path, $base_url) === 0) {
      $file = trim(str_replace($base_url, '', $url_path), '/');
      // CRM-18130: Custom CSS URL not working if aliased or rewritten
      if (file_exists(DRUPAL_ROOT . '/' . $file)) {
        $url = $file;
        $internal = TRUE;
      }
    }
    // Handle relative urls that are within the CiviCRM module directory
    elseif (strpos($url_path, $base) === 0) {
      $internal = TRUE;
      $url = $this->appendCoreDirectoryToResourceBase(dirname(drupal_get_path('module', 'civicrm')) . '/') . trim(substr($url_path, strlen($base)), '/');
    }
    return $internal;
  }

  /**
   * In instance where civicrm folder has a drupal folder & a civicrm core folder @ the same level append the
   * civicrm folder name to the url
   * See CRM-13737 for discussion of how this allows implementers to alter the folder structure
   * @todo - this only provides a limited amount of flexiblity - it still expects a 'civicrm' folder with a 'drupal' folder
   * and is only flexible as to the name of the civicrm folder.
   *
   * @param string $url
   *   Potential resource url based on standard folder assumptions.
   * @return string
   *   with civicrm-core directory appended if not standard civi dir
   */
  public function appendCoreDirectoryToResourceBase($url) {
    global $civicrm_root;
    $lastDirectory = basename($civicrm_root);
    if ($lastDirectory != 'civicrm') {
      return $url .= $lastDirectory . '/';
    }
    return $url;
  }

  /**
   * Generate an internal CiviCRM URL (copied from DRUPAL/includes/common.inc#url)
   *
   * @inheritDoc
   */
  public function url(
    $path = NULL,
    $query = NULL,
    $absolute = FALSE,
    $fragment = NULL,
    $frontend = FALSE,
    $forceBackend = FALSE
  ) {
    $config = CRM_Core_Config::singleton();
    $script = 'index.php';

    $path = CRM_Utils_String::stripPathChars($path);

    if (isset($fragment)) {
      $fragment = '#' . $fragment;
    }

    $base = $absolute ? $config->userFrameworkBaseURL : $config->useFrameworkRelativeBase;

    $separator = '&';

    if (!$config->cleanURL) {
      if (isset($path)) {
        if (isset($query)) {
          return $base . $script . '?q=' . $path . $separator . $query . $fragment;
        }
        else {
          return $base . $script . '?q=' . $path . $fragment;
        }
      }
      else {
        if (isset($query)) {
          return $base . $script . '?' . $query . $fragment;
        }
        else {
          return $base . $fragment;
        }
      }
    }
    else {
      if (isset($path)) {
        if (isset($query)) {
          return $base . $path . '?' . $query . $fragment;
        }
        else {
          return $base . $path . $fragment;
        }
      }
      else {
        if (isset($query)) {
          return $base . $script . '?' . $query . $fragment;
        }
        else {
          return $base . $fragment;
        }
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function getUserIDFromUserObject($user) {
    return !empty($user->uid) ? $user->uid : NULL;
  }

  /**
   * @inheritDoc
   */
  public function setMessage($message) {
    drupal_set_message($message);
  }

  /**
   * @inheritDoc
   */
  public function getUniqueIdentifierFromUserObject($user) {
    return empty($user->mail) ? NULL : $user->mail;
  }

  /**
   * @inheritDoc
   */
  public function getLoggedInUniqueIdentifier() {
    global $user;
    return $this->getUniqueIdentifierFromUserObject($user);
  }

  /**
   * @inheritDoc
   */
  public function permissionDenied() {
    drupal_access_denied();
  }

  /**
   * @inheritDoc
   */
  public function getUserRecordUrl($contactID) {
    $uid = CRM_Core_BAO_UFMatch::getUFId($contactID);
    if (CRM_Core_Session::singleton()
      ->get('userID') == $contactID || CRM_Core_Permission::checkAnyPerm([
        'cms:administer users',
        'cms:view user account',
      ])
    ) {
      return $this->url('user/' . $uid);
    };
  }

  /**
   * @inheritDoc
   */
  public function checkPermissionAddUser() {
    return CRM_Core_Permission::check('administer users');
  }

  /**
   * @inheritDoc
   */
  public function logger($message) {
    if (CRM_Core_Config::singleton()->userFrameworkLogging && function_exists('watchdog')) {
      watchdog('civicrm', '%message', ['%message' => $message], NULL, WATCHDOG_DEBUG);
    }
  }

  /**
   * @inheritDoc
   */
  public function clearResourceCache() {
    _drupal_flush_css_js();
  }

  /**
   * @inheritDoc
   */
  public function flush() {
    drupal_flush_all_caches();
  }

  /**
   * @inheritDoc
   */
  public function getModules() {
    $result = [];
    $q = db_query('SELECT name, status FROM {system} WHERE type = \'module\' AND schema_version <> -1');
    foreach ($q as $row) {
      $result[] = new CRM_Core_Module('drupal.' . $row->name, ($row->status == 1) ? TRUE : FALSE);
    }
    return $result;
  }

  /**
   * Find any users/roles/security-principals with the given permission
   * and replace it with one or more permissions.
   *
   * @param string $oldPerm
   * @param array $newPerms
   *   Array, strings.
   *
   * @return void
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
              $url = (CRM_Utils_System::isSSL() ? 'https' : 'http') . '://' . $language->domain . base_path();
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
   * @inheritDoc
   */
  public function getVersion() {
    return defined('VERSION') ? VERSION : 'Unknown';
  }

  /**
   * @inheritDoc
   */
  public function isUserRegistrationPermitted() {
    if (!variable_get('user_register', TRUE)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function isPasswordUserGenerated() {
    if (variable_get('user_email_verification', TRUE)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function updateCategories() {
    // copied this from profile.module. Seems a bit inefficient, but i don't know a better way
    cache_clear_all();
    menu_rebuild();
  }

  /**
   * @inheritDoc
   */
  public function getUFLocale() {
    // return CiviCRM’s xx_YY locale that either matches Drupal’s Chinese locale
    // (for CRM-6281), Drupal’s xx_YY or is retrieved based on Drupal’s xx
    // sometimes for CLI based on order called, this might not be set and/or empty
    $language = $this->getCurrentLanguage();

    if (empty($language)) {
      return NULL;
    }

    if ($language == 'zh-hans') {
      return 'zh_CN';
    }

    if ($language == 'zh-hant') {
      return 'zh_TW';
    }

    if (preg_match('/^.._..$/', $language)) {
      return $language;
    }

    return CRM_Core_I18n_PseudoConstant::longForShort(substr($language, 0, 2));
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
   * @inheritDoc
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
   * Fixme: Why are we overriding the parent function? Seems inconsistent.
   * This version supplies slightly different params to $this->url (not absolute and html encoded) but why?
   *
   * @param string $action
   *
   * @return string
   */
  public function postURL($action) {
    if (!empty($action)) {
      return $action;
    }
    return $this->url($_GET['q']);
  }

  /**
   * Get an array of user details for a contact, containing at minimum the user ID & name.
   *
   * @param int $contactID
   *
   * @return array
   *   CMS user details including
   *   - id
   *   - name (ie the system user name.
   */
  public function getUser($contactID) {
    $userDetails = parent::getUser($contactID);
    $user = $this->getUserObject($userDetails['id']);
    $userDetails['name'] = $user->name;
    $userDetails['email'] = $user->mail;
    return $userDetails;
  }

  /**
   * Load the user object.
   *
   * Note this function still works in drupal 6, 7 & 8 but is deprecated in Drupal 8.
   *
   * @param $userID
   *
   * @return object
   */
  public function getUserObject($userID) {
    return user_load($userID);
  }

  /**
   * Parse the name of the drupal site.
   *
   * @param string $civicrm_root
   *
   * @return null|string
   * @deprecated
   */
  public function parseDrupalSiteNameFromRoot($civicrm_root) {
    $siteName = NULL;
    if (strpos($civicrm_root,
        DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . 'all' . DIRECTORY_SEPARATOR . 'modules'
      ) === FALSE
    ) {
      $startPos = strpos($civicrm_root,
        DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR
      );
      $endPos = strpos($civicrm_root,
        DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR
      );
      if ($startPos && $endPos) {
        // if component is in sites/SITENAME/modules
        $siteName = substr($civicrm_root,
          $startPos + 7,
          $endPos - $startPos - 7
        );
      }
    }
    return $siteName;
  }

  /**
   * Determine if Drupal multi-site applies to the current request -- and,
   * specifically, determine the name of the multisite folder.
   *
   * @param string $flagFile
   *   Check if $flagFile exists inside the site dir.
   * @return null|string
   *   string, e.g. `bar.example.com` if using multisite.
   *   NULL if using the default site.
   */
  private function parseDrupalSiteNameFromRequest($flagFile = '') {
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
   * Function to return current language of Drupal
   *
   * @return string
   */
  public function getCurrentLanguage() {
    global $language;
    return (!empty($language->language)) ? $language->language : $language;
  }

}
