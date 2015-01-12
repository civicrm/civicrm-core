<?php

/**
 * Base class for UF system integrations
 */
abstract class CRM_Utils_System_Base {
  /**
   * Deprecated property to check if this is a drupal install. The correct method is to have functions on the UF classes for all UF specific
   * functions and leave the codebase oblivious to the type of CMS
   *
   * @deprecated
   * @var bool
   *   TRUE, if the CMS is Drupal.
   */
  var $is_drupal = FALSE;

  /**
   * Deprecated property to check if this is a joomla install. The correct method is to have functions on the UF classes for all UF specific
   * functions and leave the codebase oblivious to the type of CMS
   *
   * @deprecated
   * @var bool
   *   TRUE, if the CMS is Joomla!.
   */
  var $is_joomla = FALSE;

  /**
   * deprecated property to check if this is a wordpress install. The correct method is to have functions on the UF classes for all UF specific
   * functions and leave the codebase oblivious to the type of CMS
   *
   * @deprecated
   * @var bool
   *   TRUE, if the CMS is WordPress.
   */
  var $is_wordpress = FALSE;

  /**
   * Does this CMS / UF support a CMS specific logging mechanism?
   * @todo - we should think about offering up logging mechanisms in a way that is also extensible by extensions
   * @var bool
   */
  var $supports_UF_Logging = FALSE;

  /**
   * @var bool
   *   TRUE, if the CMS allows CMS forms to be extended by hooks.
   */
  var $supports_form_extensions = FALSE;

  /**
   * If we are using a theming system, invoke theme, else just print the
   * content
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
        print theme('maintenance_page', array('content' => $content));
        exit();
      }
      $ret = TRUE; // TODO: Figure out why D7 returns but everyone else prints
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
  function languageNegotiationURL(
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
   * Get user login URL for hosting CMS (method declared in each CMS system class)
   *
   * @param string $destination
   *   If present, add destination to querystring (works for Drupal only).
   *
   * @return string
   *   loginURL for the current CMS
   */
  public abstract function getLoginURL($destination = '');

  /**
   * Determine the native ID of the CMS user
   *
   * @param string $username
   *
   * @throws CRM_Core_Exception
   * @return int|NULL
   */
  public function getUfId($username) {
    $className = get_class($this);
    throw new CRM_Core_Exception("Not implemented: {$className}->getUfId");
  }

  /**
   * Set a init session with user object
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
   * Reset any system caches that may be required for proper CiviCRM
   * integration.
   */
  public function flush() {
    // nullop by default
  }

  /**
   * Flush css/js caches
   */
  public function clearResourceCache() {
    // nullop by default
  }

  /**
   * Return default Site Settings
   *
   * @param string $dir
   *
   * @return array
   * - $url, (Joomla - non admin url)
   * - $siteName,
   * - $siteRoot
   */
  public function getDefaultSiteSettings($dir) {
    $config = CRM_Core_Config::singleton();
    $url = $config->userFrameworkBaseURL;
    return array($url, NULL, NULL);
  }

  /**
   * Perform any post login activities required by the CMS -
   * e.g. for drupal: records a watchdog message about the new session, saves the login timestamp,
   * calls hook_user op 'login' and generates a new session.
   *
   * @param array $params
   *
   * FIXME: Document values accepted/required by $params
   */
  public function userLoginFinalize($params = array()) {
  }

  /**
   * Set timezone in mysql so that timestamp fields show the correct time
   */
  public function setMySQLTimeZone() {
    $timeZoneOffset = $this->getTimeZoneOffset();
    if ($timeZoneOffset) {
      $sql = "SET time_zone = '$timeZoneOffset'";
      CRM_Core_DAO::executequery($sql);
    }
  }


  /**
   * Get timezone from CMS
   *
   * @return string|false|null
   */
  public function getTimeZoneOffset() {
    $timezone = $this->getTimeZoneString();
    if ($timezone) {
      $tzObj = new DateTimeZone($timezone);
      $dateTime = new DateTime("now", $tzObj);
      $tz = $tzObj->getOffset($dateTime);

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
   * Over-ridable function to get timezone as a string eg.
   *
   * @return string
   *   Time zone, e.g. 'America/Los_Angeles'
   */
  public function getTimeZoneString() {
    return date_default_timezone_get();
  }

  /**
   * Get Unique Identifier from UserFramework system (CMS)
   * @param object $user
   *   Object as described by the User Framework.
   * @return mixed
   *   $uniqueIdentifer Unique identifier from the user Framework system
   */
  public function getUniqueIdentifierFromUserObject($user) {
  }

  /**
   * Get User ID from UserFramework system (CMS)
   * @param object $user
   *   Object as described by the User Framework.
   * @return mixed
   *   <NULL, number>
   */
  public function getUserIDFromUserObject($user) {
  }

  /**
   * Get currently logged in user uf id.
   *
   * @return int
   *   $userID logged in user uf id.
   */
  public function getLoggedInUfID() {
  }

  /**
   * Get currently logged in user unique identifier - this tends to be the email address or user name.
   *
   * @return string
   *   logged in user unique identifier
   */
  public function getLoggedInUniqueIdentifier() {
  }

  /**
   * Return a UFID (user account ID from the UserFramework / CMS system being based on the user object
   * passed, defaulting to the logged in user if not passed. Note that ambiguous situation occurs
   * in CRM_Core_BAO_UFMatch::synchronize - a cleaner approach would seem to be resolving the user id before calling
   * the function
   *
   * Note there is already a function getUFId which takes $username as a param - we could add $user
   * as a second param to it but it seems messy - just overloading it because the name is taken
   * @param object $user
   * @return int
   *   $ufid - user ID of UF System
   */
  public function getBestUFID($user = NULL) {
    if ($user) {
      return $this->getUserIDFromUserObject($user);
    }
    return $this->getLoggedInUfID();
  }

  /**
   * Return a unique identifier (usually an email address or username) from the UserFramework / CMS system being based on the user object
   * passed, defaulting to the logged in user if not passed. Note that ambiguous situation occurs
   * in CRM_Core_BAO_UFMatch::synchronize - a cleaner approach would seem to be resolving the unique identifier before calling
   * the function
   *
   * @param object $user
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
   * Get Url to view user record
   * @param int $contactID
   *   Contact ID.
   *
   * @return string
   */
  public function getUserRecordUrl($contactID) {
    return NULL;
  }

  /**
   * Is the current user permitted to add a user
   * @return bool
   */
  public function checkPermissionAddUser() {
    return FALSE;
  }

  /**
   * Output code from error function
   * @param string $content
   */
  public function outputError($content) {
    echo CRM_Utils_System::theme($content);
  }

  /**
   * Log error to CMS
   */
  public function logger($message) {

  }

  /**
   * Append to coreResourcesList
   */
  public function appendCoreResources(&$list) {
  }
}
