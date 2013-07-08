<?php

/**
 * Base class for UF system integrations
 */
abstract class CRM_Utils_System_Base {
  var $is_drupal = FALSE;
  var $is_joomla = FALSE;
  var $is_wordpress = FALSE;

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
    if (function_exists('theme') && !$print) {
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
    if (!$print &&
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

  function getDefaultBlockLocation() {
    return 'left';
  }

  function getVersion() {
    return 'Unknown';
  }

  /**
   * Format the url as per language Negotiation.
   *
   * @param string $url
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

  /*
   * Currently this is just helping out the test class as defaults is calling it - maybe move fix to defaults
   */
  function cmsRootPath() {
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
   * Perform an post login activities required by the UF -
   * e.g. for drupal: records a watchdog message about the new session, saves the login timestamp, calls hook_user op 'login' and generates a new session.
   * @param array $edit: The array of form values submitted by the user.
   *
  function userLoginFinalize($edit = array()){
  }
  */
}

