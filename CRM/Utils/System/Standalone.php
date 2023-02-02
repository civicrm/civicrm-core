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
 * Standalone specific stuff goes here.
 */
class CRM_Utils_System_Standalone extends CRM_Utils_System_Base {

  /**
   * @inheritdoc
   */
  public function getDefaultFileStorage() {
    return [
      'url' => 'upload',
      // @todo Not sure if this is wise
      'path' => $_SERVER['DOCUMENT_ROOT'],
    ];
  }

  /**
   * @inheritDoc
   */
  public function createUser(&$params, $mail) {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function updateCMSName($ufID, $email) {
  }

  /**
   * @inheritDoc
   */
  public function getLoginURL($destination = '') {
    $query = $destination ? ['destination' => $destination] : [];
    // @todo
    throw new \RuntimeException("Standalone getLoginURL not written yet!");
  }

  /**
   * @inheritDoc
   */
  public function setTitle($title, $pageTitle = NULL) {
    if (!$pageTitle) {
      $pageTitle = $title;
    }
    $template = CRM_Core_Smarty::singleton();
    $template->assign('pageTitle', $pageTitle);
    $template->assign('docTitle', $title);
  }

  /**
   * @inheritDoc
   */
  public function appendBreadCrumb($breadcrumbs) {
  }

  /**
   * @inheritDoc
   */
  public function resetBreadCrumb() {
  }

  /**
   * @inheritDoc
   */
  public function addHTMLHead($header) {
    $template = CRM_Core_Smarty::singleton();
    $template->append('pageHTMLHead', $header);
    return;
  }

  /**
   * @inheritDoc
   */
  public function addStyleUrl($url, $region) {
    if ($region != 'html-header') {
      return FALSE;
    }
    $this->addHTMLHead('<link rel="stylesheet" href="' . $url . '"></style>');
  }

  /**
   * @inheritDoc
   */
  public function addStyle($code, $region) {
    if ($region != 'html-header') {
      return FALSE;
    }
    $this->addHTMLHead('<style>' . $code . '</style>');
  }

  /**
   * Check if a resource url is within the public webroot and format appropriately.
   *
   * This seems to be a legacy function. We assume all resources are
   * ok directory and always return TRUE. As well, we clean up the $url.
   *
   * @todo: This is not a legacy function and the above is not a safe assumption.
   * External urls are allowed by CRM_Core_Resources and this needs to return the correct value.
   *
   * @param $url
   *
   * @return bool
   */
  public function formatResourceUrl(&$url) {
    // Remove leading slash if present.
    $url = ltrim($url, '/');

    // Remove query string — presumably added to stop intermediary caching.
    if (($pos = strpos($url, '?')) !== FALSE) {
      $url = substr($url, 0, $pos);
    }
    // @todo: Should not unconditionally return true
    return TRUE;
  }

  /**
   * Changes to the base_url should be made in settings.php directly.
   */
  public function mapConfigToSSL() {
  }

  /**
   * @inheritDoc
   */
  public function url(
    $path = '',
    $query = '',
    $absolute = FALSE,
    $fragment = NULL,
    $frontend = FALSE,
    $forceBackend = FALSE,
    $htmlize = TRUE
  ) {
    // @todo Implement absolute etc
    $fragment = $fragment ? ('#' . $fragment) : '';
    $url = "/{$path}?{$query}$fragment";
    return $url;
  }

  /**
   * @inheritDoc
   */
  public function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    // @todo
    throw new \RuntimeException("Standalone authenticate not written yet!");
  }

  /**
   * @inheritDoc
   */
  public function loadUser($username) {
    // @todo
    throw new \RuntimeException("Standalone loadUser not written yet!");
  }

  /**
   * Determine the native ID of the CMS user.
   *
   * @param string $username
   * @return int|null
   */
  public function getUfId($username) {
    // @todo
    throw new \RuntimeException("Standalone getUfId not written yet!");
  }

  /**
   * @inheritDoc
   */
  public function permissionDenied() {
    die('Standalone permissionDenied');
  }

  /**
   * @inheritDoc
   */
  public function logout() {
    // @todo
  }

  /**
   * @inheritDoc
   */
  public function theme(&$content, $print = FALSE, $maintenance = FALSE) {
    if ($maintenance) {
      $smarty = CRM_Core_Smarty::singleton();
      echo implode('', $smarty->_tpl_vars['pageHTMLHead']);
    }

    // @todo Add variables from the body tag? (for Shoreditch)

    print $content;
    return NULL;
  }

  /**
   * Bootstrap the non-existent CMS
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
   * @Todo Handle setting cleanurls configuration for CiviCRM?
   */
  public function loadBootStrap($params = [], $loadUser = TRUE, $throwError = TRUE, $realPath = NULL) {
    static $run_once = FALSE;
    if ($run_once) {
      return TRUE;
    }
    else {
      $run_once = TRUE;
    }
    // @todo ?
    return TRUE;
  }

  /**
   * @inheritdoc
   */
  public function getCiviSourceStorage(): array {
    global $civicrm_root;

    if (!defined('CIVICRM_UF_BASEURL')) {
      throw new RuntimeException('Undefined constant: CIVICRM_UF_BASEURL');
    }

    return [
      'url' => CRM_Utils_File::addTrailingSlash('', '/'),
      'path' => CRM_Utils_File::addTrailingSlash($civicrm_root),
    ];
  }

  /**
   * Determine the location of the CMS root.
   *
   * @param string $path
   *
   * @return NULL|string
   */
  public function cmsRootPath($path = NULL) {
    global $civicrm_paths;
    if (!empty($civicrm_paths['cms.root']['path'])) {
      return $civicrm_paths['cms.root']['path'];
    }

    // @todo?
    throw new \RuntimeException("Standalone requires that you set \$civicrm_paths['cms.root']['path'] in civicrm.settings.php");
  }

  /**
   * @inheritDoc
   */
  public function isUserLoggedIn() {
    // @todo
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function isUserRegistrationPermitted() {
    // @todo Have a setting
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function isPasswordUserGenerated() {
    // @todo User management not implemented, but we should do like on WP
    // and always generate a password for the user, as part of the login process.
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function updateCategories() {
    // @todo Is anything necessary?
  }

  /**
   * @inheritDoc
   */
  public function getLoggedInUfID() {
    // @todo Not implemented
    // This helps towards getting the CiviCRM menu to display
    return 1;
  }

  /**
   * @inheritDoc
   */
  public function getDefaultBlockLocation() {
    // @todo No sidebars, no blocks
    return 'sidebar_first';
  }

  /**
   * @inheritDoc
   */
  public function flush() {
  }

  /**
   * @inheritDoc
   */
  public function getUser($contactID) {
    $user_details = parent::getUser($contactID);
    $user_details['name'] = $user_details['name']->value;
    $user_details['email'] = $user_details['email']->value;
    return $user_details;
  }

  /**
   * @inheritDoc
   */
  public function getUniqueIdentifierFromUserObject($user) {
    return $user->get('mail')->value;
  }

  /**
   * @inheritDoc
   */
  public function getUserIDFromUserObject($user) {
    return $user->get('uid')->value;
  }

  /**
   * @inheritDoc
   */
  public function synchronizeUsers() {
    // @todo
    Civi::log()->debug('CRM_Utils_System_Standalone::synchronizeUsers: not implemented');
  }

  /**
   * @inheritDoc
   */
  public function setMessage($message) {
    // @todo This function is for displaying messages on public pages
    // This might not be user-friendly enough for errors on a contribution page?
    CRM_Core_Session::setStatus('', $message, 'info');
  }

  /**
   * Function to return current language.
   *
   * @return string
   */
  public function getCurrentLanguage() {
    // @todo
    Civi::log()->debug('CRM_Utils_System_Standalone::getCurrentLanguage: not implemented');
    return NULL;
  }

  /**
   * Helper function to extract path, query and route name from Civicrm URLs.
   *
   * For example, 'civicrm/contact/view?reset=1&cid=66' will be returned as:
   *
   * ```
   * array(
   *   'path' => 'civicrm/contact/view',
   *   'route' => 'civicrm.civicrm_contact_view',
   *   'query' => array('reset' => '1', 'cid' => '66'),
   * );
   * ```
   *
   * @param string $url
   *   The url to parse.
   *
   * @return string[]
   *   The parsed url parts, containing 'path', 'route' and 'query'.
   */
  public function parseUrl($url) {
    $processed = ['path' => '', 'route_name' => '', 'query' => []];

    // Remove leading '/' if it exists.
    $url = ltrim($url, '/');

    // Separate out the url into its path and query components.
    $url = parse_url($url);
    if (empty($url['path'])) {
      return $processed;
    }
    $processed['path'] = $url['path'];

    // Create a route name by replacing the forward slashes in the path with
    // underscores, civicrm/contact/search => civicrm.civicrm_contact_search.
    $processed['route_name'] = 'civicrm.' . implode('_', explode('/', $url['path']));

    // Turn the query string (if it exists) into an associative array.
    if (!empty($url['query'])) {
      parse_str($url['query'], $processed['query']);
    }

    return $processed;
  }

  /**
   * Append any Standalone js to coreResourcesList.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public function appendCoreResources(\Civi\Core\Event\GenericHookEvent $e) {
  }

  /**
   * @inheritDoc
   */
  public function getTimeZoneString() {
    $timezone = date_default_timezone_get();
    return $timezone;
  }

  /**
   * @inheritDoc
   */
  public function setUFLocale($civicrm_language) {
    throw new \RuntimeException("Standalone setUFLocale not written yet!");
    // @todo
  }

  /**
   * @inheritDoc
   */
  public function languageNegotiationURL($url, $addLanguagePart = TRUE, $removeLanguagePart = FALSE) {
    if (empty($url)) {
      return $url;
    }

    // @todo
    \Civi::log()->warning("Standalone languageNegotiationURL is not written, but was called");
    return $url;
  }

  /**
   * Return the CMS-specific url for its permissions page
   * @return array
   */
  public function getCMSPermissionsUrlParams() {
    // @todo
    \Civi::log()->warning("Standalone getCMSPermissionsUrlParams is not written, but was called");
    return ['ufAccessURL' => '/fixme/standalone/permissions/url/params'];
  }

  /**
   * Start a new session.
   */
  public function sessionStart() {
    session_start();
    // @todo This helps towards getting the CiviCRM menu to display
    // but obviously should be replaced once we have user management
    CRM_Core_Session::singleton()->set('userID', 1);
  }

  /**
   * @inheritdoc
   */
  public function getSessionId() {
    return session_id();
  }

  /**
   * @todo is anything needed here for Standalone?
   */
  public function invalidateRouteCache() {
  }

}
