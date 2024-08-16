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

  /**
   * Determine if the UF/CMS has been loaded already.
   *
   * This is generally TRUE. If using the "extern" boot protocol, then this may initially be false (until loadBootStrap runs).
   *
   * @internal
   * @return bool
   */
  abstract public function isLoaded(): bool;

  abstract public function loadBootStrap($params = [], $loadUser = TRUE, $throwError = TRUE, $realPath = NULL);

  /**
   * Returns the Smarty template path to the main template that renders the content.
   *
   * In CMS contexts, this goes inside their theme, but Standalone needs to render the full HTML page.
   *
   * @var int|string $print
   *   Should match a CRM_Core_Smarty::PRINT_* constant,
   *   or equal 0 if not in print mode.
   */
  public static function getContentTemplate($print = 0): string {
    if ($print === CRM_Core_Smarty::PRINT_JSON) {
      return 'CRM/common/snippet.tpl';
    }

    switch ($print) {
      case 0:
        // Not a print context.
        $config = CRM_Core_Config::singleton();
        return 'CRM/common/' . strtolower($config->userFramework) . '.tpl';

      case CRM_Core_Smarty::PRINT_PAGE:
        return 'CRM/common/print.tpl';

      case 'xls':
      case 'doc':
        return 'CRM/Contact/Form/Task/Excel.tpl';

      default:
        return 'CRM/common/snippet.tpl';
    }
  }

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
    if (!empty($action)) {
      return $action;
    }

    $current_path = CRM_Utils_System::currentPath();
    return (string) Civi::url('current://' . $current_path, 'a');
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
  abstract public function url(
    $path = NULL,
    $query = NULL,
    $absolute = FALSE,
    $fragment = NULL,
    $frontend = FALSE,
    $forceBackend = FALSE
  );

  /**
   * Compose the URL for a page/route.
   *
   * @internal
   * @see \Civi\Core\Url::__toString
   * @param string $scheme
   *   Ex: 'frontend', 'backend', 'service'
   * @param string $path
   *   Ex: 'civicrm/event/info'
   * @param string|null $query
   *   Ex: 'id=100&msg=Hello+world'
   * @return string|null
   *   Absolute URL, or NULL if scheme is unsupported.
   *   Ex: 'https://subdomain.example.com/index.php?q=civicrm/event/info&id=100&msg=Hello+world'
   */
  public function getRouteUrl(string $scheme, string $path, ?string $query): ?string {
    switch ($scheme) {
      case 'frontend':
        return $this->url($path, $query, TRUE, NULL, TRUE, FALSE, FALSE);

      case 'service':
        // The original `url()` didn't have an analog for "service://". But "frontend" is probably the closer bet?
        // Or maybe getNotifyUrl() makes sense?
        return $this->url($path, $query, TRUE, NULL, TRUE, FALSE, FALSE);

      case 'backend':
        return $this->url($path, $query, TRUE, NULL, FALSE, TRUE, FALSE);

      // If the UF defines other major UI/URL conventions, then you might hypothetically handle
      // additional schemes.

      default:
        return NULL;
    }
  }

  /**
   * Return the Notification URL for Payments.
   *
   * The default is to pass the params through to `url()`. However the WordPress
   * CMS class overrides this method because Notification URLs must always target
   * the Base Page to avoid IPN failures when Forms are embedded in pages that
   * require authentication.
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
   *   The Notification URL.
   */
  public function getNotifyUrl(
    $path = NULL,
    $query = NULL,
    $absolute = FALSE,
    $fragment = NULL,
    $frontend = FALSE,
    $forceBackend = FALSE
  ) {
    return $this->url($path, $query, $absolute, $fragment, $frontend, $forceBackend);
  }

  /**
   * Path of the current page e.g. 'civicrm/contact/view'
   *
   * @return string|null
   *   the current menu path
   */
  public static function currentPath() {
    $config = CRM_Core_Config::singleton();
    return isset($_GET[$config->userFrameworkURLVar]) ? trim($_GET[$config->userFrameworkURLVar], '/') : NULL;
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
   * @throws \CRM_Core_Exception.
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
   * @param string $username
   *
   * @return bool
   */
  public function loadUser($username) {
    return TRUE;
  }

  /**
   * Immediately stop script execution and display a 401 "Access Denied" page.
   * @throws \CRM_Core_Exception
   */
  public function permissionDenied() {
    throw new CRM_Core_Exception(ts('You do not have permission to access this page.'));
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
    print $content;
    return NULL;
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
   * @return string|false
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
   * @param string $mailParam
   *   Name of the $param which contains the email address.
   *   Because. Right. OK. That's what it is.
   *
   * @return int|bool
   *   uid if user exists, false otherwise
   */
  public function createUser(&$params, $mailParam) {
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
   * Verify password
   *
   * @param array $params
   *   Array of name, mail and password values.
   * @param array $errors
   *   Array of errors.
   */
  public function verifyPassword($params, &$errors) {
  }

  /**
   * Is a front end page being accessed.
   *
   * Generally this would be a contribution form or other public page as opposed to a backoffice page (like contact edit).
   *
   * @todo Drupal uses the is_public setting - clarify & rationalise. See https://github.com/civicrm/civicrm-drupal/pull/546/files
   *
   * @return bool
   */
  public function isFrontEndPage() {
    return CRM_Core_Config::singleton()->userFrameworkFrontend;
  }

  /**
   * Get user login URL for hosting CMS (method declared in each CMS system class)
   *
   * @param string $destination
   *   If present, add destination to querystring (works for Drupal and WordPress only).
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
   * @return array
   *   - url: string. ex: "http://example.com/sites/all/modules/civicrm"
   *   - path: string. ex: "/var/www/sites/all/modules/civicrm"
   */
  abstract public function getCiviSourceStorage():array;

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
      CRM_Core_DAO::executeQuery($sql);
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
   * @param string|NULL $priority
   */
  public function logger($message, $priority = NULL) {
  }

  /**
   * Append to coreResourcesList.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public function appendCoreResources(\Civi\Core\Event\GenericHookEvent $e) {
  }

  /**
   * Modify dynamic assets.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public function alterAssetUrl(\Civi\Core\Event\GenericHookEvent $e) {
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

  /**
   * Whether to allow access to CMS user sync action
   * @return bool
   */
  public function allowSynchronizeUsers() {
    return TRUE;
  }

  /**
   * Run CMS user sync if allowed, otherwise just returns empty array
   * @return array
   */
  public function synchronizeUsersIfAllowed() {
    if ($this->allowSynchronizeUsers()) {
      return $this->synchronizeUsers();
    }
    else {
      return [];
    }
  }

  /**
   * Send an HTTP Response base on PSR HTTP RespnseInterface response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  public function sendResponse(\Psr\Http\Message\ResponseInterface $response) {
    http_response_code($response->getStatusCode());
    foreach ($response->getHeaders() as $name => $values) {
      CRM_Utils_System::setHttpHeader($name, implode(', ', (array) $values));
    }
    echo $response->getBody();
    CRM_Utils_System::civiExit(0, ['response' => $response]);
  }

  /**
   * Start a new session.
   */
  public function sessionStart() {
    session_start();
  }

  /**
   * This exists because of https://www.drupal.org/node/3006306 where
   * they changed so that they don't start sessions for anonymous, but we
   * want that.
   */
  public function getSessionId() {
    return session_id();
  }

  /**
   * Get role names
   *
   * @return array|null
   */
  public function getRoleNames() {
    return NULL;
  }

  /**
   * Determine if the Views module exists.
   *
   * @return bool
   */
  public function viewsExists() {
    return FALSE;
  }

  /**
   * Perform any necessary actions prior to redirecting via POST.
   */
  public function prePostRedirect() {
  }

  /**
   * Return the CMS-specific url for its permissions page
   * @return array
   */
  public function getCMSPermissionsUrlParams() {
    return [];
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
    $crmDatabase = DB::parseDSN(CRM_Core_Config::singleton()->dsn)['database'];
    $cmsDatabase = DB::parseDSN(CRM_Core_Config::singleton()->userFrameworkDSN)['database'];
    if ($crmDatabase === $cmsDatabase) {
      return '';
    }
    return "`$crmDatabase`.";
  }

  /**
   * Invalidates the cache of dynamic routes and forces a rebuild.
   */
  public function invalidateRouteCache() {
  }

  /**
   * Should the admin be able to set the password when creating a user
   * or does the CMS want it a different way.
   */
  public function showPasswordFieldWhenAdminCreatesUser() {
    return TRUE;
  }

  /**
   * Return the CMS-specific UF Group Types for profiles.
   * @return array
   */
  public function getUfGroupTypes() {
    return [];
  }

  /**
   * Should the current execution exit after a fatal error?
   * This is the appropriate functionality in most cases.
   *
   * @internal
   * @return bool
   */
  public function shouldExitAfterFatal() {
    return TRUE;
  }

  public function checkCleanurls() {
    return [];
  }

  /**
   * Suppress profile form errors
   *
   * @return bool
   */
  public function suppressProfileFormErrors():bool {
    return FALSE;
  }

  /**
   * Get email field name from form values
   *
   * @param CRM_Core_Form $form
   * @param array $fields
   *
   * @return string
   */
  public function getEmailFieldName(CRM_Core_Form $form, array $fields):string {
    return 'email';
  }

  /**
   * Check if username and email exists in the CMS.
   *
   * @param array $params
   *   Array of name and mail values.
   * @param array $errors
   *   Array of errors.
   * @param string $emailName
   *   Field label for the 'email'.
   */
  public function checkUserNameEmailExists(&$params, &$errors, $emailName = 'email') {
  }

  /**
   * Has CMS users table
   *
   * @return bool
   */
  public function hasUsersTable():bool {
    return FALSE;
  }

  /**
   * CiviCRM Table prefixes
   *   To display for CMS integration.
   *
   * @return string
   */
  public function viewsIntegration():string {
    return '';
  }

  /**
   * Can set base page for CiviCRM
   *   By default, CiviCRM will generate front-facing pages
   *   using the home page. This allows a different template
   *   for CiviCRM pages.
   * @return bool
   */
  public function canSetBasePage():bool {
    return FALSE;
  }

  /**
   * Get the client's IP address.
   *
   * @return string
   *   IP address
   */
  public function ipAddress():?string {
    return $_SERVER['REMOTE_ADDR'] ?? NULL;
  }

  /**
   * Check if mailing workflow is enabled
   *
   * @return bool
   */
  public function mailingWorkflowIsEnabled():bool {
    return FALSE;
  }

  /**
   * Get Contact details from User
   *   The contact parameters here will be used to create a Contact
   *   record to match the user record.
   *
   * @param array $uf_match
   *   Array of user object, unique ID.
   * @return array
   *   Array of contact parameters.
   */
  public function getContactDetailsFromUser($uf_match):array {
    $contactParameters = [];
    $contactParameters['email'] = $uf_match['user']->email;

    return $contactParameters;
  }

  /**
   * Modify standalone profile
   *
   * @param string $profile
   * @param array $params
   *
   * @return string
   */
  public function modifyStandaloneProfile($profile, $params):string {
    // Not sure how to circumvent our own navigation system to generate the
    // right form url.
    $urlReplaceWith = 'civicrm/profile/create&amp;gid=' . $params['gid'] . '&amp;reset=1';
    $profile = str_replace('civicrm/admin/uf/group', $urlReplaceWith, $profile);

    return $profile;
  }

  /**
   * Hook for further system boot once the main CiviCRM
   * Container is up (only used in Standalone currently)
   */
  public function postContainerBoot(): void {
  }

}
