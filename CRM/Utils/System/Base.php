<?php

/**
 * Base class for UF system integrations
 */
abstract class CRM_Utils_System_Base {

  /**
   * Deprecated property to check if this is a drupal install.
   *
   * The correct method is to have functions on the UF classes for all UF specific
   * functions and leave the codebase oblivious to the type of CMS
   *
   * @var bool
   * @deprecated
   *   TRUE, if the CMS is Drupal.
   */
  public $is_drupal = FALSE;

  /**
   * Deprecated property to check if this is a joomla install. The correct method is to have functions on the UF classes for all UF specific
   * functions and leave the codebase oblivious to the type of CMS
   *
   * @var bool
   * @deprecated
   *   TRUE, if the CMS is Joomla!.
   */
  public $is_joomla = FALSE;

  /**
   * deprecated property to check if this is a wordpress install. The correct method is to have functions on the UF classes for all UF specific
   * functions and leave the codebase oblivious to the type of CMS
   *
   * @var bool
   * @deprecated
   *   TRUE, if the CMS is WordPress.
   */
  public $is_wordpress = FALSE;

  /**
   * Does this CMS / UF support a CMS specific logging mechanism?
   * @var bool
   * @todo - we should think about offering up logging mechanisms in a way that is also extensible by extensions
   */
  public $supports_UF_Logging = FALSE;

  /**
   * @var bool
   *   TRUE, if the CMS allows CMS forms to be extended by hooks.
   */
  public $supports_form_extensions = FALSE;

  public function initialize() {
    if (\CRM_Utils_System::isSSL()) {
      $this->mapConfigToSSL();
    }
  }

  abstract public function loadBootStrap($params = [], $loadUser = TRUE, $throwError = TRUE, $realPath = NULL);

  /**
   * Append an additional breadcrumb tag to the existing breadcrumb.
   *
   * @param array $breadCrumbs
   */
  public function appendBreadCrumb($breadCrumbs) {
  }

  /**
   * Reset an additional breadcrumb tag to the existing breadcrumb.
   */
  public function resetBreadCrumb() {
  }

  /**
   * Append a string to the head of the html file.
   *
   * @param string $head
   *   The new string to be appended.
   */
  public function addHTMLHead($head) {
  }

  /**
   * Rewrite various system urls to https.
   */
  public function mapConfigToSSL() {
    // dont need to do anything, let CMS handle their own switch to SSL
  }

  /**
   * Figure out the post url for QuickForm.
   *
   * @param string $action
   *   The default url if one is pre-specified.
   *
   * @return string
   *   The url to post the form.
   */
  public function postURL($action) {
    $config = CRM_Core_Config::singleton();
    if (!empty($action)) {
      return $action;
    }

    return $this->url(CRM_Utils_Array::value($config->userFrameworkURLVar, $_GET),
      NULL, TRUE, NULL, FALSE
    );
  }

  /**
   * Generate the url string to a CiviCRM path.
   *
   * @param string $path
   *   The path being linked to, such as "civicrm/add".
   * @param string $query
   *   A query string to append to the link.
   * @param bool $absolute
   *   Whether to force the output to be an absolute link (beginning with http).
   *   Useful for links that will be displayed outside the site, such as in an RSS feed.
   * @param string $fragment
   *   A fragment identifier (named anchor) to append to the link.
   * @param bool $frontend
   *   This link should be to the CMS front end (applies to WP & Joomla).
   * @param bool $forceBackend
   *   This link should be to the CMS back end (applies to WP & Joomla).
   *
   * @return string
   */
  public function url(
    $path = NULL,
    $query = NULL,
    $absolute = FALSE,
    $fragment = NULL,
    $frontend = FALSE,
    $forceBackend = FALSE
  ) {
    return NULL;
  }

  /**
   * Authenticate the user against the CMS db.
   *
   * @param string $name
   *   The user name.
   * @param string $password
   *   The password for the above user.
   * @param bool $loadCMSBootstrap
   *   Load cms bootstrap?.
   * @param string $realPath
   *   Filename of script
   *
   * @return array|bool
   *   [contactID, ufID, unique string] else false if no auth
   */
  public function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    return FALSE;
  }

  /**
   * Set a message in the CMS to display to a user.
   *
   * @param string $message
   *   The message to set.
   */
  public function setMessage($message) {
  }

  /**
   * Load user into session.
   *
   * @param obj $user
   *
   * @return bool
   */
  public function loadUser($user) {
    return TRUE;
  }

  /**
   * Immediately stop script execution and display a 401 "Access Denied" page.
   */
  public function permissionDenied() {
    CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
  }

  /**
   * Immediately stop script execution, log out the user and redirect to the home page.
   *
   * @deprecated
   *   This function should be removed in favor of linking to the CMS's logout page
   */
  public function logout() {
  }

  /**
   * Clear CMS caches related to the user registration/profile forms.
   * Used when updating/embedding profiles on CMS user forms.
   * @see CRM-3600
   */
  public function updateCategories() {
  }

  /**
   * Get the locale set in the CMS.
   *
   * @return string|null
   *   Locale or null for none
   */
  public function getUFLocale() {
    return NULL;
  }

  /**
   * If we are using a theming system, invoke theme, else just print the content.
   *
   * @param string $content
   *   The content that will be themed.
   * @param bool $print
   *   Are we displaying to the screen or bypassing theming?.
   * @param bool $maintenance
   *   For maintenance mode.
   *
   * @throws Exception
   * @return string|null
   *   NULL, If $print is FALSE, and some other criteria match up.
   *   The themed string, otherwise.
   *
   * @todo The return value is inconsistent.
   * @todo Better to always return, and never print.
   */
  public function theme(&$content, $print = FALSE, $maintenance = FALSE) {
    $ret = FALSE;

    // TODO: Split up; this was copied verbatim from CiviCRM 4.0's multi-UF theming function
    // but the parts should be copied into cleaner subclass implementations
    $config = CRM_Core_Config::singleton();
    if (
      $config->userSystem->is_drupal &&
      function_exists('theme') &&
      !$print
    ) {
      if ($maintenance) {
        drupal_set_breadcrumb('');
        drupal_maintenance_theme();
        if ($region = CRM_Core_Region::instance('html-header', FALSE)) {
          CRM_Utils_System::addHTMLHead($region->render(''));
        }
        print theme('maintenance_page', ['content' => $content]);
        exit();
      }
      // TODO: Figure out why D7 returns but everyone else prints
      $ret = TRUE;
    }
    $out = $content;

    $config = &CRM_Core_Config::singleton();
    if (
      !$print &&
      $config->userFramework == 'WordPress'
    ) {
      if (!function_exists('is_admin')) {
        throw new \Exception('Function "is_admin()" is missing, even though WordPress is the user framework.');
      }
      if (!defined('ABSPATH')) {
        throw new \Exception('Constant "ABSPATH" is not defined, even though WordPress is the user framework.');
      }
      if (is_admin()) {
        require_once ABSPATH . 'wp-admin/admin-header.php';
      }
      else {
        // FIXME: we need to figure out to replace civicrm content on the frontend pages
      }
    }

    if ($ret) {
      return $out;
    }
    else {
      print $out;
      return NULL;
    }
  }

  /**
   * @return string
   */
  public function getDefaultBlockLocation() {
    return 'left';
  }

  /**
   * Get the absolute path to the site's base url.
   *
   * @return bool|mixed|string
   */
  public function getAbsoluteBaseURL() {
    if (!defined('CIVICRM_UF_BASEURL')) {
      return FALSE;
    }

    $url = CRM_Utils_File::addTrailingSlash(CIVICRM_UF_BASEURL, '/');

    //format url for language negotiation, CRM-7803
    $url = $this->languageNegotiationURL($url);

    if (CRM_Utils_System::isSSL()) {
      $url = str_replace('http://', 'https://', $url);
    }

    return $url;
  }

  /**
   * Get the relative path to the sites base url.
   *
   * @return bool
   */
  public function getRelativeBaseURL() {
    $absoluteBaseURL = $this->getAbsoluteBaseURL();
    if ($absoluteBaseURL === FALSE) {
      return FALSE;
    }
    $parts = parse_url($absoluteBaseURL);
    return $parts['path'];
    //$this->useFrameworkRelativeBase = empty($base['path']) ? '/' : $base['path'];
  }

  /**
   * Get CMS Version.
   *
   * @return string
   */
  public function getVersion() {
    return 'Unknown';
  }

  /**
   * Format the url as per language Negotiation.
   *
   * @param string $url
   * @param bool $addLanguagePart
   * @param bool $removeLanguagePart
   *
   * @return string
   *   Formatted url.
   */
  public function languageNegotiationURL(
    $url,
    $addLanguagePart = TRUE,
    $removeLanguagePart = FALSE
  ) {
    return $url;
  }

  /**
   * Determine the location of the CMS root.
   *
   * @return string|null
   *   Local file system path to CMS root, or NULL if it cannot be determined
   */
  public function cmsRootPath() {
    return NULL;
  }

  /**
   * Create a user in the CMS.
   *
   * @param array $params
   * @param string $mail
   *   Email id for cms user.
   *
   * @return int|bool
   *   uid if user exists, false otherwise
   */
  public function createUser(&$params, $mail) {
    return FALSE;
  }

  /**
   * Update a user's email address in the CMS.
   *
   * @param int $ufID
   *   User ID in CMS.
   * @param string $email
   *   Primary contact email address.
   */
  public function updateCMSName($ufID, $email) {
  }

  /**
   * Check if user is logged in to the CMS.
   *
   * @return bool
   */
  public function isUserLoggedIn() {
    return FALSE;
  }

  /**
   * Check if user registration is permitted.
   *
   * @return bool
   */
  public function isUserRegistrationPermitted() {
    return FALSE;
  }

  /**
   * Check if user can create passwords or is initially assigned a system-generated one.
   *
   * @return bool
   */
  public function isPasswordUserGenerated() {
    return FALSE;
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
  abstract public function getLoginURL($destination = '');

  /**
   * Get the login destination string.
   *
   * When this is passed in the URL the user will be directed to it after filling in the CMS form.
   *
   * @param CRM_Core_Form $form
   *   Form object representing the 'current' form - to which the user will be returned.
   *
   * @return string|NULL
   *   destination value for URL
   */
  public function getLoginDestination(&$form) {
    return NULL;
  }

  /**
   * Determine the native ID of the CMS user.
   *
   * @param string $username
   *
   * @throws CRM_Core_Exception
   */
  public function getUfId($username) {
    $className = get_class($this);
    throw new CRM_Core_Exception("Not implemented: {$className}->getUfId");
  }

  /**
   * Set the localisation from the user framework.
   *
   * @param string $civicrm_language
   *
   * @return bool
   */
  public function setUFLocale($civicrm_language) {
    return TRUE;
  }

  /**
   * Set a init session with user object.
   *
   * @param array $data
   *   Array with user specific data
   */
  public function setUserSession($data) {
    list($userID, $ufID) = $data;
    $session = CRM_Core_Session::singleton();
    $session->set('ufID', $ufID);
    $session->set('userID', $userID);
  }

  /**
   * Reset any system caches that may be required for proper CiviCRM integration.
   */
  public function flush() {
    // nullop by default
  }

  /**
   * Flush css/js caches.
   */
  public function clearResourceCache() {
    // nullop by default
  }

  /**
   * Add a script file.
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @param string $url absolute path to file
   * @param string $region
   *   location within the document: 'html-header', 'page-header', 'page-footer'.
   *
   * @return bool
   *   TRUE if we support this operation in this CMS, FALSE otherwise
   */
  public function addScriptUrl($url, $region) {
    return FALSE;
  }

  /**
   * Add an inline script.
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @param string $code javascript code
   * @param string $region
   *   location within the document: 'html-header', 'page-header', 'page-footer'.
   *
   * @return bool
   *   TRUE if we support this operation in this CMS, FALSE otherwise
   */
  public function addScript($code, $region) {
    return FALSE;
  }

  /**
   * Add a css file.
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @param string $url absolute path to file
   * @param string $region
   *   location within the document: 'html-header', 'page-header', 'page-footer'.
   *
   * @return bool
   *   TRUE if we support this operation in this CMS, FALSE otherwise
   */
  public function addStyleUrl($url, $region) {
    return FALSE;
  }

  /**
   * Add an inline style.
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @param string $code css code
   * @param string $region
   *   location within the document: 'html-header', 'page-header', 'page-footer'.
   *
   * @return bool
   *   TRUE if we support this operation in this CMS, FALSE otherwise
   */
  public function addStyle($code, $region) {
    return FALSE;
  }

  /**
   * Sets the title of the page.
   *
   * @param string $title
   *   Title to set in html header
   * @param string|null $pageTitle
   *   Title to set in html body (if different)
   */
  public function setTitle($title, $pageTitle = NULL) {
  }

  /**
   * Return default Site Settings.
   *
   * @param string $dir
   *
   * @return array
   *   - $url, (Joomla - non admin url)
   *   - $siteName,
   *   - $siteRoot
   */
  public function getDefaultSiteSettings($dir) {
    $config = CRM_Core_Config::singleton();
    $url = $config->userFrameworkBaseURL;
    return [$url, NULL, NULL];
  }

  /**
   * Determine the default location for file storage.
   *
   * FIXME:
   *  1. This was pulled out from a bigger function. It should be split
   *     into even smaller pieces and marked abstract.
   *  2. This would be easier to compute by a calling a CMS API, but
   *     for whatever reason Civi gets it from config data.
   *
   * @return array
   *   - url: string. ex: "http://example.com/sites/foo.com/files/civicrm"
   *   - path: string. ex: "/var/www/sites/foo.com/files/civicrm"
   */
  public function getDefaultFileStorage() {
    global $civicrm_root;
    $config = CRM_Core_Config::singleton();
    $baseURL = CRM_Utils_System::languageNegotiationURL($config->userFrameworkBaseURL, FALSE, TRUE);

    $filesURL = NULL;
    $filesPath = NULL;

    if ($config->userFramework == 'Joomla') {
      // gross hack
      // we need to remove the administrator/ from the end
      $tempURL = str_replace("/administrator/", "/", $baseURL);
      $filesURL = $tempURL . "media/civicrm/";
    }
    elseif ($config->userFramework == 'UnitTests') {
      $filesURL = $baseURL . "sites/default/files/civicrm/";
    }
    else {
      throw new CRM_Core_Exception("Failed to locate default file storage ($config->userFramework)");
    }

    return [
      'url' => $filesURL,
      'path' => CRM_Utils_File::baseFilePath(),
    ];
  }

  /**
   * Determine the location of the CiviCRM source tree.
   *
   * FIXME:
   *  1. This was pulled out from a bigger function. It should be split
   *     into even smaller pieces and marked abstract.
   *  2. This would be easier to compute by a calling a CMS API, but
   *     for whatever reason we take the hard way.
   *
   * @return array
   *   - url: string. ex: "http://example.com/sites/all/modules/civicrm"
   *   - path: string. ex: "/var/www/sites/all/modules/civicrm"
   */
  public function getCiviSourceStorage() {
    global $civicrm_root;
    $config = CRM_Core_Config::singleton();

    // Don't use $config->userFrameworkBaseURL; it has garbage on it.
    // More generally, w shouldn't be using $config here.
    if (!defined('CIVICRM_UF_BASEURL')) {
      throw new RuntimeException('Undefined constant: CIVICRM_UF_BASEURL');
    }
    $baseURL = CRM_Utils_File::addTrailingSlash(CIVICRM_UF_BASEURL, '/');
    if (CRM_Utils_System::isSSL()) {
      $baseURL = str_replace('http://', 'https://', $baseURL);
    }

    if ($config->userFramework == 'Joomla') {
      $userFrameworkResourceURL = $baseURL . "components/com_civicrm/civicrm/";
    }
    elseif ($config->userFramework == 'WordPress') {
      $userFrameworkResourceURL = CIVICRM_PLUGIN_URL . "civicrm/";
    }
    elseif ($this->is_drupal) {
      // Drupal setting
      // check and see if we are installed in sites/all (for D5 and above)
      // we dont use checkURL since drupal generates an error page and throws
      // the system for a loop on lobo's macosx box
      // or in modules
      $cmsPath = $config->userSystem->cmsRootPath();
      $userFrameworkResourceURL = $baseURL . str_replace("$cmsPath/", '',
          str_replace('\\', '/', $civicrm_root)
        );

      $siteName = $config->userSystem->parseDrupalSiteNameFromRoot($civicrm_root);
      if ($siteName) {
        $civicrmDirName = trim(basename($civicrm_root));
        $userFrameworkResourceURL = $baseURL . "sites/$siteName/modules/$civicrmDirName/";
      }
    }
    else {
      $userFrameworkResourceURL = NULL;
    }

    return [
      'url' => CRM_Utils_File::addTrailingSlash($userFrameworkResourceURL),
      'path' => CRM_Utils_File::addTrailingSlash($civicrm_root),
    ];
  }

  /**
   * Perform any post login activities required by the CMS.
   *
   * e.g. for drupal: records a watchdog message about the new session, saves the login timestamp,
   * calls hook_user op 'login' and generates a new session.
   *
   * @param array $params
   *
   * FIXME: Document values accepted/required by $params
   */
  public function userLoginFinalize($params = []) {
  }

  /**
   * Set timezone in mysql so that timestamp fields show the correct time.
   */
  public function setMySQLTimeZone() {
    $timeZoneOffset = $this->getTimeZoneOffset();
    if ($timeZoneOffset) {
      $sql = "SET time_zone = '$timeZoneOffset'";
      CRM_Core_DAO::executequery($sql);
    }
  }

  /**
   * Get timezone from CMS.
   *
   * @return string|false|null
   */
  public function getTimeZoneOffset() {
    $timezone = $this->getTimeZoneString();
    if ($timezone) {
      if ($timezone == 'UTC' || $timezone == 'Etc/UTC') {
        // CRM-17072 Let's short-circuit all the zero handling & return it here!
        return '+00:00';
      }
      $tzObj = new DateTimeZone($timezone);
      $dateTime = new DateTime("now", $tzObj);
      $tz = $tzObj->getOffset($dateTime);

      if ($tz === 0) {
        // CRM-21422
        return '+00:00';
      }

      if (empty($tz)) {
        return FALSE;
      }

      $timeZoneOffset = sprintf("%02d:%02d", $tz / 3600, abs(($tz / 60) % 60));

      if ($timeZoneOffset > 0) {
        $timeZoneOffset = '+' . $timeZoneOffset;
      }
      return $timeZoneOffset;
    }
    return NULL;
  }

  /**
   * Get timezone as a string.
   * @return string
   *   Timezone string e.g. 'America/Los_Angeles'
   */
  public function getTimeZoneString() {
    return date_default_timezone_get();
  }

  /**
   * Get Unique Identifier from UserFramework system (CMS).
   *
   * @param object $user
   *   Object as described by the User Framework.
   *
   * @return mixed
   *   Unique identifier from the user Framework system
   */
  public function getUniqueIdentifierFromUserObject($user) {
    return NULL;
  }

  /**
   * Get User ID from UserFramework system (CMS).
   *
   * @param object $user
   *
   *   Object as described by the User Framework.
   * @return null|int
   */
  public function getUserIDFromUserObject($user) {
    return NULL;
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
    $ufMatch = civicrm_api3('UFMatch', 'getsingle', [
      'contact_id' => $contactID,
      'domain_id' => CRM_Core_Config::domainID(),
    ]);
    return [
      'id' => $ufMatch['uf_id'],
      'name' => $ufMatch['uf_name'],
    ];
  }

  /**
   * Get currently logged in user uf id.
   *
   * @return int|null
   *   logged in user uf id.
   */
  public function getLoggedInUfID() {
    return NULL;
  }

  /**
   * Get currently logged in user unique identifier - this tends to be the email address or user name.
   *
   * @return string|null
   *   logged in user unique identifier
   */
  public function getLoggedInUniqueIdentifier() {
    return NULL;
  }

  /**
   * Return a UFID (user account ID from the UserFramework / CMS system.
   *
   * ID is based on the user object passed, defaulting to the logged in user if not passed.
   *
   * Note that ambiguous situation occurs in CRM_Core_BAO_UFMatch::synchronize - a cleaner approach would
   * seem to be resolving the user id before calling the function.
   *
   * Note there is already a function getUFId which takes $username as a param - we could add $user
   * as a second param to it but it seems messy - just overloading it because the name is taken.
   *
   * @param object $user
   *
   * @return int
   *   User ID of UF System
   */
  public function getBestUFID($user = NULL) {
    if ($user) {
      return $this->getUserIDFromUserObject($user);
    }
    return $this->getLoggedInUfID();
  }

  /**
   * Return a unique identifier (usually an email address or username) from the UserFramework / CMS system.
   *
   * This is based on the user object passed, defaulting to the logged in user if not passed.
   *
   * Note that ambiguous situation occurs in CRM_Core_BAO_UFMatch::synchronize - a cleaner approach would seem to be
   * resolving the unique identifier before calling the function.
   *
   * @param object $user
   *
   * @return string
   *   unique identifier from the UF System
   */
  public function getBestUFUniqueIdentifier($user = NULL) {
    if ($user) {
      return $this->getUniqueIdentifierFromUserObject($user);
    }
    return $this->getLoggedInUniqueIdentifier();
  }

  /**
   * List modules installed in the CMS, including enabled and disabled ones.
   *
   * @return array
   *   [CRM_Core_Module]
   */
  public function getModules() {
    return [];
  }

  /**
   * Get Url to view user record.
   *
   * @param int $contactID
   *   Contact ID.
   *
   * @return string|null
   */
  public function getUserRecordUrl($contactID) {
    return NULL;
  }

  /**
   * Is the current user permitted to add a user.
   *
   * @return bool
   */
  public function checkPermissionAddUser() {
    return FALSE;
  }

  /**
   * Output code from error function.
   *
   * @param string $content
   */
  public function outputError($content) {
    echo CRM_Utils_System::theme($content);
  }

  /**
   * Log error to CMS.
   *
   * @param string $message
   */
  public function logger($message) {
  }

  /**
   * Append to coreResourcesList.
   *
   * @param array $list
   */
  public function appendCoreResources(&$list) {
  }

  /**
   * @param string $name
   * @param string $value
   */
  public function setHttpHeader($name, $value) {
    header("$name: $value");
  }

  /**
   * Create CRM contacts for all existing CMS users
   *
   * @return array
   * @throws \Exception
   */
  public function synchronizeUsers() {
    throw new Exception('CMS user creation not supported for this framework');
    return [];
  }

}
