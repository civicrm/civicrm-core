<?php

/**
 * Base class for UF system integrations
 */
abstract class CRM_Utils_System_Base {
  /**
   * deprecated property to check if this is a drupal install. The correct method is to have functions on the UF classes for all UF specific
   * functions and leave the codebase oblivious to the type of CMS
   * @deprecated
   * @var bool
   */
  var $is_drupal = FALSE;

  /**
   * deprecated property to check if this is a joomla install. The correct method is to have functions on the UF classes for all UF specific
   * functions and leave the codebase oblivious to the type of CMS
   * @deprecated
   * @var bool
   */
  var $is_joomla = FALSE;

    /**
     * deprecated property to check if this is a wordpress install. The correct method is to have functions on the UF classes for all UF specific
     * functions and leave the codebase oblivious to the type of CMS
     * @deprecated
     * @var bool
     */
  var $is_wordpress = FALSE;

  /**
   * Does this CMS / UF support a CMS specific logging mechanism?
   * @todo - we should think about offering up logging mechanisms in a way that is also extensible by extensions
   * @var bool
   */
  var $supports_UF_Logging = FALSE;

  /*
   * Does the CMS allow CMS forms to be extended by hooks
   */
  var $supports_form_extensions = FALSE;

  /**
   * if we are using a theming system, invoke theme, else just print the
   * content
   *
   * @param string  $content the content that will be themed
   * @param boolean $print   are we displaying to the screen or bypassing theming?
   * @param boolean $maintenance  for maintenance mode
   *
   * @return void           prints content on stdout
   * @access public
   */
  function theme(&$content, $print = FALSE, $maintenance = FALSE) {
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
      if (is_admin()) {
        require_once (ABSPATH . 'wp-admin/admin-header.php');
      }
      else {
        // FIX ME: we need to figure out to replace civicrm content on the frontend pages
      }
    }

    if ($ret) {
      return $out;
    }
    else {
      print $out;
    }
  }

  /**
   * @return string
   */
  function getDefaultBlockLocation() {
    return 'left';
  }

  /**
   * @return string
   */
  function getVersion() {
    return 'Unknown';
  }

  /**
   * Format the url as per language Negotiation.
   *
   * @param string $url
   *
   * @param bool $addLanguagePart
   * @param bool $removeLanguagePart
   *
   * @return string $url, formatted url.
   * @static
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
   * @return string|NULL local file system path to CMS root, or NULL if it cannot be determined
   */
  function cmsRootPath() {
    return NULL;
  }

  /**
   * Get user login URL for hosting CMS (method declared in each CMS system class)
   *
   * @param string $destination - if present, add destination to querystring (works for Drupal only)
   *
   * @return string - loginURL for the current CMS
   * @static
   */
  public abstract function getLoginURL($destination = '');

  /**
   * Determine the native ID of the CMS user
   *
   * @param $username
   *
   * @throws CRM_Core_Exception
   * @return int|NULL
   */
  function getUfId($username) {
    $className = get_class($this);
    throw new CRM_Core_Exception("Not implemented: {$className}->getUfId");
  }

  /**
   * Set a init session with user object
   *
   * @param array $data  array with user specific data
   *
   * @access public
   */
  function setUserSession($data) {
    list($userID, $ufID) = $data;
    $session = CRM_Core_Session::singleton();
    $session->set('ufID', $ufID);
    $session->set('userID', $userID);
  }

  /**
   * Reset any system caches that may be required for proper CiviCRM
   * integration.
   */
  function flush() {
    // nullop by default
  }

  /**
   * Flush css/js caches
   */
  function clearResourceCache() {
    // nullop by default
  }

  /**
   * Return default Site Settings
   *
   * @param $dir
   *
   * @return array array
   * - $url, (Joomla - non admin url)
   * - $siteName,
   * - $siteRoot
   */
  function getDefaultSiteSettings($dir) {
    $config = CRM_Core_Config::singleton();
    $url = $config->userFrameworkBaseURL;
    return array($url, NULL, NULL);
  }

  /**
   * Perform any post login activities required by the CMS -
   * e.g. for drupal: records a watchdog message about the new session, saves the login timestamp,
   * calls hook_user op 'login' and generates a new session.
   *
   * @param array params
   *
   * FIXME: Document values accepted/required by $params
   */
  function userLoginFinalize($params = array()){
  }

  /**
   * Set timezone in mysql so that timestamp fields show the correct time
   */
  function setMySQLTimeZone(){
    $timeZoneOffset = $this->getTimeZoneOffset();
    if($timeZoneOffset){
      $sql = "SET time_zone = '$timeZoneOffset'";
      CRM_Core_DAO::executequery($sql);
    }
  }


  /**
   * Get timezone from CMS
   * @return boolean|string
   */
  /**
   * Get timezone from Drupal
   * @return boolean|string
   */
  function getTimeZoneOffset(){
    $timezone = $this->getTimeZoneString();
    if($timezone){
      $tzObj = new DateTimeZone($timezone);
      $dateTime = new DateTime("now", $tzObj);
      $tz = $tzObj->getOffset($dateTime);

      if(empty($tz)){
        return false;
      }

      $timeZoneOffset = sprintf("%02d:%02d", $tz / 3600, abs(($tz/60)%60));

      if($timeZoneOffset > 0){
        $timeZoneOffset = '+' . $timeZoneOffset;
      }
      return $timeZoneOffset;
    }
  }

  /**
   * Over-ridable function to get timezone as a string eg.
   * @return string Timezone e.g. 'America/Los_Angeles'
   */
  function getTimeZoneString() {
    return date_default_timezone_get();
  }

  /**
   * Get Unique Identifier from UserFramework system (CMS)
   * @param object $user object as described by the User Framework
   * @return mixed $uniqueIdentifer Unique identifier from the user Framework system
   *
   */
  function getUniqueIdentifierFromUserObject($user) {}

  /**
   * Get User ID from UserFramework system (CMS)
   * @param object $user object as described by the User Framework
   * @return mixed <NULL, number>
   *
   */
  function getUserIDFromUserObject($user) {}

  /**
   * Get currently logged in user uf id.
   *
   * @return int $userID logged in user uf id.
   */
  function getLoggedInUfID() {}

  /**
   * Get currently logged in user unique identifier - this tends to be the email address or user name.
   *
   * @return string $userID logged in user unique identifier
   */
  function getLoggedInUniqueIdentifier() {}

  /**
   * return a UFID (user account ID from the UserFramework / CMS system being based on the user object
   * passed, defaulting to the logged in user if not passed. Note that ambiguous situation occurs
   * in CRM_Core_BAO_UFMatch::synchronize - a cleaner approach would seem to be resolving the user id before calling
   * the function
   *
   * Note there is already a function getUFId which takes $username as a param - we could add $user
   * as a second param to it but it seems messy - just overloading it because the name is taken
   * @param object $user
   * @return int $ufid - user ID of UF System
   */
  function getBestUFID($user = NULL) {
    if($user) {
      return $this->getUserIDFromUserObject($user);
    }
    return $this->getLoggedInUfID();
  }

  /**
   * return a unique identifier (usually an email address or username) from the UserFramework / CMS system being based on the user object
   * passed, defaulting to the logged in user if not passed. Note that ambiguous situation occurs
   * in CRM_Core_BAO_UFMatch::synchronize - a cleaner approach would seem to be resolving the unique identifier before calling
   * the function
   *
   * @param object $user
   * @return string $uniqueIdentifier - unique identifier from the UF System
   */
  function getBestUFUniqueIdentifier($user = NULL) {
    if($user) {
      return $this->getUniqueIdentifierFromUserObject($user);
    }
    return $this->getLoggedInUniqueIdentifier();
  }

  /**
   * Get Url to view user record
   * @param integer $contactID Contact ID
   *
   * @return string
   */
  function getUserRecordUrl($contactID) {
    return NULL;
  }
  /**
   * Is the current user permitted to add a user
   * @return bool
   */
  function checkPermissionAddUser() {
    return FALSE;
  }

  /**
   * output code from error function
   * @param string $content
   */
  function outputError($content) {
    echo CRM_Utils_System::theme($content);
  }

  /**
   * Log error to CMS
   */
  function logger($message) {

  }

  /**
   * Append to coreResourcesList
   */
  function appendCoreResources(&$list) {}
}

