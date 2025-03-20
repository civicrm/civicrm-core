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
 * WordPress specific stuff goes here
 */
class CRM_Utils_System_WordPress extends CRM_Utils_System_Base {

  /**
   * Get a normalized version of the wpBasePage.
   */
  public static function getBasePage() {
    return strtolower(rtrim(Civi::settings()->get('wpBasePage'), '/'));
  }

  /**
   */
  public function __construct() {
    /**
     * deprecated property to check if this is a drupal install. The correct method is to have functions on the UF classes for all UF specific
     * functions and leave the codebase oblivious to the type of CMS
     * @deprecated
     * @var bool
     */
    $this->is_drupal = FALSE;
    $this->is_wordpress = TRUE;
  }

  public function initialize() {
    parent::initialize();
    $this->registerPathVars();
  }

  /**
   * Specify the default computation for various paths/URLs.
   */
  protected function registerPathVars():void {
    $isNormalBoot = function_exists('get_option');
    if ($isNormalBoot) {
      // Normal mode - CMS boots first, then calls Civi. "Normal" web pages and newer extern routes.
      // To simplify the code-paths, some items are re-registered with WP-specific functions.
      $cmsRoot = function() {
        return [
          'path' => untrailingslashit(ABSPATH),
          'url' => home_url(),
        ];
      };
      Civi::paths()->register('cms', $cmsRoot);
      Civi::paths()->register('cms.root', $cmsRoot);
      Civi::paths()->register('civicrm.root', function () {
        return [
          'path' => CIVICRM_PLUGIN_DIR . 'civicrm' . DIRECTORY_SEPARATOR,
          'url' => CIVICRM_PLUGIN_URL . 'civicrm/',
        ];
      });
      Civi::paths()->register('wp.frontend.base', function () {
        return [
          'url' => home_url('/'),
        ];
      });
      Civi::paths()->register('wp.frontend', function () {
        $config = CRM_Core_Config::singleton();
        $basepage = get_page_by_path($config->wpBasePage);
        return [
          'url' => get_permalink($basepage->ID),
        ];
      });
      Civi::paths()->register('wp.backend.base', function () {
        return [
          'url' => admin_url(),
        ];
      });
      Civi::paths()->register('wp.backend', function() {
        return [
          'url' => admin_url('admin.php'),
        ];
      });
      Civi::paths()->register('civicrm.files', function () {
        $upload_dir = wp_get_upload_dir();

        $old = CRM_Core_Config::singleton()->userSystem->getDefaultFileStorage();
        $new = [
          'path' => $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR,
          'url' => $upload_dir['baseurl'] . '/civicrm/',
        ];

        if ($old['path'] === $new['path']) {
           return $new;
        }

        $oldExists = file_exists($old['path']);
        $newExists = file_exists($new['path']);

        if ($oldExists && !$newExists) {
          return $old;
        }
        elseif (!$oldExists && $newExists) {
          return $new;
        }
        elseif (!$oldExists && !$newExists) {
          // neither exists. but that's ok. we're in one of these two cases:
          // - we're just starting installation... which will get sorted in a moment
          //   when someone calls mkdir().
          // - we're running a bespoke setup... which will get sorted in a moment
          //   by applying $civicrm_paths.
          return $new;
        }
        elseif ($oldExists && $newExists) {
          // situation ambiguous. encourage admin to set value explicitly.
          if (!isset($GLOBALS['civicrm_paths']['civicrm.files'])) {
            // Let's ensure these are different paths before issuing a warning.
            // Because WordPress uses __DIR__ to calculate paths, symlinks get
            // resolved with the new path, but not the old path. Replace
            // backslash with forward slash (in case we are on Windows) and
            // remove trailing slashes to normalize each path.
            $oldNormalizedPath = rtrim(str_replace('\\', '/', realpath($old['path'])), '/');
            $newNormalizedPath = rtrim(str_replace('\\', '/', $new['path']), '/');
            if ($oldNormalizedPath != $newNormalizedPath) {
              // If these paths really are different, display a warning.
              \Civi::log()->warning("The system has data from both old+new conventions. Please use civicrm.settings.php to set civicrm.files explicitly.");
            }
          }
          return $new;
        }
      });
    }
    else {
      // Legacy support - only relevant for older extern routes.
      Civi::paths()
        ->register('wp.frontend.base', function () {
          return ['url' => rtrim(CIVICRM_UF_BASEURL, '/') . '/'];
        })
        ->register('wp.frontend', function () {
          $config = \CRM_Core_Config::singleton();
          $suffix = defined('CIVICRM_UF_WP_BASEPAGE') ? CIVICRM_UF_WP_BASEPAGE : $config->wpBasePage;
          return [
            'url' => Civi::paths()->getVariable('wp.frontend.base', 'url') . $suffix,
          ];
        })
        ->register('wp.backend.base', function () {
          return ['url' => rtrim(CIVICRM_UF_BASEURL, '/') . '/wp-admin/'];
        })
        ->register('wp.backend', function () {
          return [
            'url' => Civi::paths()->getVariable('wp.backend.base', 'url') . 'admin.php',
          ];
        });
    }
  }

  /**
   * @inheritDoc
   */
  public function setTitle($title, $pageTitle = NULL) {
    if (!$pageTitle) {
      $pageTitle = $title;
    }

    // FIXME: Why is this global?
    global $civicrm_wp_title;
    $civicrm_wp_title = $title;

    // yes, set page title, depending on context
    $context = civi_wp()->civicrm_context_get();
    switch ($context) {
      case 'admin':
      case 'shortcode':
        $template = CRM_Core_Smarty::singleton();
        $template->assign('pageTitle', $pageTitle);
    }
  }

  /**
   * Moved from CRM_Utils_System_Base
   */
  public function getDefaultFileStorage() {
    // NOTE: On WordPress, this will be circumvented in the future. However,
    // should retain it to allow transitional/upgrade code determine the old value.

    $config = CRM_Core_Config::singleton();
    $cmsUrl = CRM_Utils_System::languageNegotiationURL($config->userFrameworkBaseURL, FALSE, TRUE);
    $cmsPath = $this->cmsRootPath();
    $filesPath = CRM_Utils_File::baseFilePath();
    $filesRelPath = CRM_Utils_File::relativize($filesPath, $cmsPath);
    $filesURL = rtrim($cmsUrl, '/') . '/' . ltrim($filesRelPath, ' /');
    return [
      'url' => CRM_Utils_File::addTrailingSlash($filesURL, '/'),
      'path' => CRM_Utils_File::addTrailingSlash($filesPath),
    ];
  }

  /**
   * Determine the location of the CiviCRM source tree.
   *
   * @return array
   *   - url: string. ex: "http://example.com/sites/all/modules/civicrm"
   *   - path: string. ex: "/var/www/sites/all/modules/civicrm"
   */
  public function getCiviSourceStorage():array {
    global $civicrm_root;

    // Don't use $config->userFrameworkBaseURL; it has garbage on it.
    // More generally, we shouldn't be using $config here.
    if (!defined('CIVICRM_UF_BASEURL')) {
      throw new RuntimeException('Undefined constant: CIVICRM_UF_BASEURL');
    }

    $cmsPath = $this->cmsRootPath();

    // $config  = CRM_Core_Config::singleton();
    // overkill? // $cmsUrl = CRM_Utils_System::languageNegotiationURL($config->userFrameworkBaseURL, FALSE, TRUE);
    $cmsUrl = CIVICRM_UF_BASEURL;
    if (CRM_Utils_System::isSSL()) {
      $cmsUrl = str_replace('http://', 'https://', $cmsUrl);
    }
    $civiRelPath = CRM_Utils_File::relativize(realpath($civicrm_root), realpath($cmsPath));
    $civiUrl = rtrim($cmsUrl, '/') . '/' . ltrim($civiRelPath, ' /');
    return [
      'url' => CRM_Utils_File::addTrailingSlash($civiUrl, '/'),
      'path' => CRM_Utils_File::addTrailingSlash($civicrm_root),
    ];
  }

  /**
   * @inheritDoc
   */
  public function appendBreadCrumb($breadCrumbs) {
    $breadCrumb = wp_get_breadcrumb();

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

    CRM_Core_Smarty::singleton()->assign('breadcrumb', $breadCrumb);
    wp_set_breadcrumb($breadCrumb);
  }

  /**
   * @inheritDoc
   */
  public function resetBreadCrumb() {
    $bc = [];
    wp_set_breadcrumb($bc);
  }

  /**
   * @inheritDoc
   */
  public function addHTMLHead($head) {
    \CRM_Core_Error::deprecatedFunctionWarning("addHTMLHead is deprecated in WordPress and will be removed in a future version");
    static $registered = FALSE;
    if (!$registered) {
      // front-end view
      add_action('wp_head', [__CLASS__, '_showHTMLHead']);
      // back-end views
      add_action('admin_head', [__CLASS__, '_showHTMLHead']);
      $registered = TRUE;
    }
    CRM_Core_Region::instance('wp_head')->add([
      'markup' => $head,
    ]);
  }

  /**
   * WP action callback.
   */
  public static function _showHTMLHead() {
    $region = CRM_Core_Region::instance('wp_head', FALSE);
    if ($region) {
      echo $region->render('');
    }
  }

  /**
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
    $frontend_url = '';
    $separator = '&';
    $fragment = isset($fragment) ? ('#' . $fragment) : '';
    $path = CRM_Utils_String::stripPathChars($path);
    $basepage = FALSE;

    // FIXME: Why bootstrap in url()?
    // Generally want to define 1-2 strategic places to put bootstrap.
    if (!function_exists('get_option')) {
      $this->loadBootStrap();
    }

    // When on the front-end.
    if ($config->userFrameworkFrontend) {

      // Try and find the "calling" page/post.
      global $post;
      if ($post) {
        $frontend_url = get_permalink($post->ID);
        if (civi_wp()->basepage->is_match($post->ID)) {
          $basepage = TRUE;
        }
      }

    }
    else {

      // Get the Base Page URL for building front-end URLs.
      if ($frontend && !$forceBackend) {
        $frontend_url = $this->getBasePageUrl();
        $basepage = TRUE;
      }

    }

    // Get either the relative Base Page URL or the relative Admin Page URL.
    $base = $this->getBaseUrl($absolute, $frontend, $forceBackend);

    // Overwrite base URL if we already have a front-end URL.
    if (!$forceBackend && $frontend_url != '') {
      $base = $frontend_url;
    }

    $queryParts = [];
    $admin_request = ((is_admin() && !$frontend) || $forceBackend);

    /**
     * Filter the Base URL.
     *
     * @since 5.67
     *
     * @param str $base The Base URL.
     * @param bool $admin_request True if building an admin URL, false otherwise.
     */
    $base = apply_filters('civicrm/core/url/base', $base, $admin_request);

    if (
      // If not using Clean URLs.
      !$config->cleanURL
      // Or requesting an admin URL.
      || $admin_request
      // Or this is a Shortcode.
      || (!$basepage && $frontend_url != '')
    ) {

      // Build URL according to pre-existing logic.
      if (!empty($path)) {
        // Admin URLs still need "page=CiviCRM", front-end URLs do not.
        if ($admin_request) {
          $queryParts[] = 'page=CiviCRM';
        }
        else {
          $queryParts[] = 'civiwp=CiviCRM';
        }
        $queryParts[] = 'q=' . rawurlencode($path);
      }
      if (!empty($query)) {
        $queryParts[] = $query;
      }

      // Append our query parts, taking Permlink Structure into account.
      if (get_option('permalink_structure') == '' && !$admin_request) {
        $final = $base . $separator . implode($separator, $queryParts) . $fragment;
      }
      else {
        $final = $base . '?' . implode($separator, $queryParts) . $fragment;
      }

    }
    else {

      // Build Clean URL.
      if (!empty($path)) {
        $base = trailingslashit($base) . str_replace('civicrm/', '', $path) . '/';
      }
      if (!empty($query)) {
        $query = ltrim($query, '=?&');
        $queryParts[] = $query;
      }

      if (!empty($queryParts)) {
        $final = $base . '?' . implode($separator, $queryParts) . $fragment;
      }
      else {
        $final = $base . $fragment;
      }

    }

    return $final;
  }

  /**
   * Get either the relative Base Page URL or the relative Admin Page URL.
   *
   * @param bool $absolute
   *   Whether to force the output to be an absolute link beginning with http(s).
   * @param bool $frontend
   *   True if this link should be to the CMS front end.
   * @param bool $forceBackend
   *   True if this link should be to the CMS back end.
   *
   * @return mixed|null|string
   */
  public function getBaseUrl($absolute, $frontend, $forceBackend) {
    $config = CRM_Core_Config::singleton();
    if ((is_admin() && !$frontend) || $forceBackend) {
      return Civi::paths()->getUrl('[wp.backend]/.', $absolute ? 'absolute' : 'relative');
    }
    else {
      return Civi::paths()->getUrl('[wp.frontend]/.', $absolute ? 'absolute' : 'relative');
    }
  }

  /**
   * Get the URL of the WordPress Base Page.
   *
   * @return string|bool
   *   The Base Page URL, or false on failure.
   */
  public function getBasePageUrl() {
    return civi_wp()->basepage->url_get();
  }

  /**
   * @inheritDoc
   */
  public function getNotifyUrl(
    $path = NULL,
    $query = NULL,
    $absolute = FALSE,
    $fragment = NULL,
    $frontend = FALSE,
    $forceBackend = FALSE
  ) {
    $config = CRM_Core_Config::singleton();
    $separator = '&';
    $fragment = isset($fragment) ? ('#' . $fragment) : '';
    $path = CRM_Utils_String::stripPathChars($path);
    $queryParts = [];

    // Get the Base Page URL.
    $base = $this->getBasePageUrl();

    // If not using Clean URLs.
    if (!$config->cleanURL) {

      // Build URL according to pre-existing logic.
      if (!empty($path)) {
        $queryParts[] = 'civiwp=CiviCRM';
        $queryParts[] = 'q=' . rawurlencode($path);
      }
      if (!empty($query)) {
        $queryParts[] = $query;
      }

      // Append our query parts, taking Permlink Structure into account.
      if (get_option('permalink_structure') == '') {
        $final = $base . $separator . implode($separator, $queryParts) . $fragment;
      }
      else {
        $final = $base . '?' . implode($separator, $queryParts) . $fragment;
      }

    }
    else {

      // Build Clean URL.
      if (!empty($path)) {
        $base = trailingslashit($base) . str_replace('civicrm/', '', $path) . '/';
      }
      if (!empty($query)) {
        $query = ltrim($query, '=?&');
        $queryParts[] = $query;
      }

      if (!empty($queryParts)) {
        $final = $base . '?' . implode($separator, $queryParts) . $fragment;
      }
      else {
        $final = $base . $fragment;
      }

    }

    return $final;
  }

  /**
   * @inheritDoc
   */
  public function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    /* Before we do any loading, let's start the session and write to it.
     * We typically call authenticate only when we need to bootstrap the CMS
     * directly via Civi and hence bypass the normal CMS auth and bootstrap
     * process typically done in CLI and cron scripts. See: CRM-12648
     */
    $session = CRM_Core_Session::singleton();
    $session->set('civicrmInitSession', TRUE);

    $config = CRM_Core_Config::singleton();

    if ($loadCMSBootstrap) {
      $config->userSystem->loadBootStrap([
        'name' => $name,
        'pass' => $password,
      ]);
    }

    $user = wp_authenticate($name, $password);
    if (is_a($user, 'WP_Error')) {
      return FALSE;
    }

    // TODO: need to change this to make sure we matched only one row

    CRM_Core_BAO_UFMatch::synchronizeUFMatch($user->data, $user->data->ID, $user->data->user_email, 'WordPress');
    $contactID = CRM_Core_BAO_UFMatch::getContactId($user->data->ID);
    if (!$contactID) {
      return FALSE;
    }
    return [$contactID, $user->data->ID, mt_rand()];
  }

  /**
   * FIXME: Do something
   *
   * @param string $message
   */
  public function setMessage($message) {
  }

  /**
   * @param \string $user
   *
   * @return bool
   */
  public function loadUser($user) {
    $userdata = get_user_by('login', $user);
    if (empty($userdata->ID)) {
      return FALSE;
    }

    $uid = $userdata->ID;
    wp_set_current_user($uid);
    $contactID = CRM_Core_BAO_UFMatch::getContactId($uid);

    // Lets store contact id and user id in session.
    $session = CRM_Core_Session::singleton();
    $session->set('ufID', $uid);
    $session->set('userID', $contactID);
    return TRUE;
  }

  /**
   * FIXME: Use CMS-native approach
   * @throws \CRM_Core_Exception
   */
  public function permissionDenied() {
    status_header(403);
    global $civicrm_wp_title;
    $civicrm_wp_title = ts('You do not have permission to access this page.');
    throw new CRM_Core_Exception(ts('You do not have permission to access this page.'));
  }

  /**
   * Determine the native ID of the CMS user.
   *
   * @param string $username
   *
   * @return int|null
   */
  public function getUfId($username) {
    $userdata = get_user_by('login', $username);
    if (empty($userdata->ID)) {
      return NULL;
    }
    return $userdata->ID;
  }

  /**
   * @inheritDoc
   */
  public function postLogoutUrl(): string {
    return wp_login_url();
  }

  /**
   * @inheritDoc
   */
  public function getUFLocale() {
    /*
     * Bail early if method is called when WordPress isn't bootstrapped.
     * Additionally, the function checked here is located in pluggable.php
     * and is required by wp_get_referer() - so this also bails early if it is
     * called too early in the request lifecycle.
     *
     * @see https://core.trac.wordpress.org/ticket/25294
     */
    if (!function_exists('wp_validate_redirect')) {
      return NULL;
    }

    // Default to WordPress User locale.
    $locale = get_user_locale();

    // Is this a "back-end" AJAX call?
    $is_backend = FALSE;
    if (wp_doing_ajax() && FALSE !== strpos(wp_get_referer(), admin_url())) {
      $is_backend = TRUE;
    }

    // Ignore when in WordPress admin or it's a "back-end" AJAX call.
    if (!(is_admin() || $is_backend)) {

      // Reaching here means it is very likely to be a front-end context.

      // Default to WordPress locale.
      $locale = get_locale();

      /**
       * Filter the default WordPress locale.
       *
       * The CiviCRM-WordPress plugin supports Polylang and WPML via this filter.
       *
       * @since 5.67
       *
       * @param str $locale The WordPress locale.
       */
      $locale = apply_filters('civicrm/core/locale', $locale);

    }

    if (!empty($locale)) {
      // If for some reason only we get a language code, convert it to a locale.
      if (2 === strlen($locale)) {
        $locale = CRM_Core_I18n_PseudoConstant::longForShort($locale);
      }
      return $locale;
    }
    else {
      return NULL;
    }
  }

  /**
   * @inheritDoc
   */
  public function setUFLocale($civicrm_language) {
    // TODO (probably not possible with WPML?)
    return TRUE;
  }

  /**
   * @internal
   * @return bool
   */
  public function isLoaded(): bool {
    return function_exists('__');
  }

  /**
   * Tries to bootstrap WordPress.
   *
   * @param array $params
   *   Optional credentials
   *   - name: string, cms username
   *   - pass: string, cms password
   * @param bool $loadUser
   * @param bool $throwError
   * @param mixed $realPath
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function loadBootStrap($params = [], $loadUser = TRUE, $throwError = TRUE, $realPath = NULL) {
    global $wp, $wp_rewrite, $wp_the_query, $wp_query, $wpdb, $current_site, $current_blog, $current_user;

    $name = $params['name'] ?? NULL;
    $pass = $params['pass'] ?? NULL;

    if (!defined('WP_USE_THEMES')) {
      define('WP_USE_THEMES', FALSE);
    }

    // Load bootstrap file.
    $cmsRootPath = $this->cmsRootPath();
    if (!$cmsRootPath) {
      throw new CRM_Core_Exception("Could not find the install directory for WordPress");
    }
    $path = Civi::settings()->get('wpLoadPhp');
    if (!empty($path)) {
      require_once $path;
    }
    elseif (file_exists($cmsRootPath . DIRECTORY_SEPARATOR . 'wp-load.php')) {
      require_once $cmsRootPath . DIRECTORY_SEPARATOR . 'wp-load.php';
    }
    else {
      throw new CRM_Core_Exception("Could not find the bootstrap file for WordPress");
    }

    // Match CiviCRM timezone to WordPress site timezone.
    $wpSiteTimezone = $this->getTimeZoneString();
    if ($wpSiteTimezone) {
      $this->setTimeZone($wpSiteTimezone);
    }

    // Make sure pluggable WordPress functions are available.
    if (!function_exists('wp_set_current_user')) {
      require_once $cmsRootPath . DIRECTORY_SEPARATOR . 'wp-includes/pluggable.php';
    }

    // Maybe login user.
    $uid = $params['uid'] ?? NULL;
    if (!$uid) {
      $name = $name ?: trim($_REQUEST['name'] ?? '');
      $pass = $pass ?: trim($_REQUEST['pass'] ?? '');
      if ($name) {
        $uid = wp_authenticate($name, $pass);
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
      if ($uid instanceof WP_User) {
        $account = wp_set_current_user($uid->ID);
      }
      else {
        $account = wp_set_current_user($uid);
      }
      if ($account && $account->data->ID) {
        global $user;
        $user = $account;
        return TRUE;
      }
    }

    return TRUE;
  }

  /**
   * @param string $dir
   *
   * @return bool
   */
  public function validInstallDir($dir) {
    $includePath = "$dir/wp-includes";
    if (@file_exists("$includePath/version.php")) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Determine the location of the CMS root.
   *
   * @return string|NULL
   *   local file system path to CMS root, or NULL if it cannot be determined
   */
  public function cmsRootPath() {

    // Return early if the path is already set.
    global $civicrm_paths;
    if (!empty($civicrm_paths['cms.root']['path'])) {
      return $civicrm_paths['cms.root']['path'];
    }

    // Return early if constant has been defined.
    if (defined('CIVICRM_CMSDIR')) {
      if ($this->validInstallDir(CIVICRM_CMSDIR)) {
        return CIVICRM_CMSDIR;
      }
    }

    // Return early if path to wp-load.php can be retrieved from settings.
    $setting = Civi::settings()->get('wpLoadPhp');
    if (!empty($setting)) {
      $path = str_replace('wp-load.php', '', $setting);
      $cmsRoot = rtrim($path, '/\\');
      if ($this->validInstallDir($cmsRoot)) {
        return $cmsRoot;
      }
    }

    /*
     * Keep previous logic as fallback of last resort.
     *
     * At some point, it would be good to remove this because there are serious
     * problems in correctly locating WordPress in this manner. In summary, it
     * is impossible to do so reliably.
     *
     * @see https://github.com/civicrm/civicrm-wordpress/pull/63#issuecomment-61792328
     * @see https://github.com/civicrm/civicrm-core/pull/11086#issuecomment-335454992
     */
    $cmsRoot = $valid = NULL;

    $pathVars = explode('/', str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']));

    // Might be Windows installation.
    $firstVar = array_shift($pathVars);
    if ($firstVar) {
      $cmsRoot = $firstVar;
    }

    // Start with CMS dir search.
    foreach ($pathVars as $var) {
      $cmsRoot .= "/$var";
      if ($this->validInstallDir($cmsRoot)) {
        // Stop as we found bootstrap.
        $valid = TRUE;
        break;
      }
    }

    return ($valid) ? $cmsRoot : NULL;
  }

  /**
   * @inheritDoc
   */
  public function createUser(&$params, $mailParam) {
    $user_data = [
      'ID' => '',
      'user_login' => $params['cms_name'],
      'user_email' => $params[$mailParam],
      'nickname' => $params['cms_name'],
      'role' => get_option('default_role'),
    ];

    /*
     * The notify parameter was ignored on WordPress and default behaviour
     * was to always notify. Preserve that behaviour but allow the "notify"
     * parameter to be used.
     */
    if (!isset($params['notify'])) {
      $params['notify'] = TRUE;
    }

    // If there's a password add it, otherwise generate one.
    if (!empty($params['cms_pass'])) {
      $user_data['user_pass'] = $params['cms_pass'];
    }
    else {
      $user_data['user_pass'] = wp_generate_password(12, FALSE);
    }

    // Assign WordPress User "name" field(s).
    if (isset($params['contactID'])) {
      $contactType = CRM_Contact_BAO_Contact::getContactType($params['contactID']);
      if ($contactType == 'Individual') {
        $user_data['first_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $params['contactID'], 'first_name'
        );
        $user_data['last_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $params['contactID'], 'last_name'
        );
      }
      if ($contactType == 'Organization') {
        $user_data['first_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $params['contactID'], 'organization_name'
        );
      }
      if ($contactType == 'Household') {
        $user_data['first_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $params['contactID'], 'household_name'
        );
      }
    }

    /**
     * Fires when CiviCRM is about to create a WordPress User.
     *
     * @since 5.37
     * @since 5.71 Added $params, $mailParam and $user_data.
     *
     * @param array $params The array of source Contact data.
     * @param string $mailParam The name of the param which contains the email address.
     * @param array $user_data The array of data to create the WordPress User with.
     */
    do_action('civicrm_pre_create_user', $params, $mailParam, $user_data);

    // Remove the CiviCRM-WordPress listeners.
    $this->hooks_core_remove();

    // User is not logged in by default.
    $logged_in = FALSE;

    // Now go ahead and create a WordPress User.
    $uid = wp_insert_user($user_data);
    if (is_wp_error($uid)) {
      Civi::log()->error("Could not create the user. WordPress returned: " . $uid->get_error_message());
    }
    else {

      /*
       * Call wp_signon if we aren't already logged in.
       * For example, we might be creating a new user from the Contact record.
       */
      if (!current_user_can('create_users')) {
        $creds = [];
        $creds['user_login'] = $params['cms_name'];
        $creds['user_password'] = $user_data['user_pass'];
        $creds['remember'] = TRUE;

        $should_login_user = boolval(get_option('civicrm_automatically_sign_in_user', TRUE));
        if (TRUE === $should_login_user) {
          // Authenticate and log the user in.
          $user = wp_signon($creds, FALSE);
          if (is_wp_error($user)) {
            Civi::log()
              ->error("Could not log the user in. WordPress returned: " . $user->get_error_message());
          }
          else {
            $logged_in = TRUE;
          }
        }
      }

      if ($params['notify']) {
        // Fire the new user action. Sends notification email by default.
        do_action('register_new_user', $uid);
      }

    }

    // Restore the CiviCRM-WordPress listeners.
    $this->hooks_core_add();

    /**
     * Fires after CiviCRM has tried to create a WordPress User.
     *
     * @since 5.37
     * @since 5.71 Added $uid and $params.
     *
     * @param int|WP_Error $uid The ID of the new WordPress User, or WP_Error on failure.
     * @param array $params The array of source Contact data.
     * @param bool $logged_in TRUE when the User has been auto-logged-in, FALSE otherwise.
     */
    do_action('civicrm_post_create_user', $uid, $params, $logged_in);

    return $uid;
  }

  /**
   * @inheritDoc
   */
  public function updateCMSName($ufID, $ufName) {
    // CRM-10620
    if (function_exists('wp_update_user')) {
      $ufID = CRM_Utils_Type::escape($ufID, 'Integer');
      $ufName = CRM_Utils_Type::escape($ufName, 'String');

      $values = ['ID' => $ufID, 'user_email' => $ufName];
      if ($ufID) {
        wp_update_user($values);
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function getEmailFieldName(CRM_Core_Form $form, array $fields):string {
    $emailName = '';
    $billingLocationTypeID = CRM_Core_BAO_LocationType::getBilling();
    if (array_key_exists("email-{$billingLocationTypeID}", $fields)) {
      // this is a transaction related page
      $emailName = 'email-' . $billingLocationTypeID;
    }
    else {
      // find the email field in a profile page
      foreach ($fields as $name => $dontCare) {
        if (str_starts_with($name, 'email')) {
          $emailName = $name;
          break;
        }
      }
    }

    return $emailName;
  }

  /**
   * @inheritdoc
   */
  public function checkUserNameEmailExists(&$params, &$errors, $emailName = 'email') {
    if (!empty($params['name'])) {
      if (!validate_username($params['name'])) {
        $errors['cms_name'] = ts("Your username contains invalid characters");
      }
      elseif (username_exists(sanitize_user($params['name']))) {
        $errors['cms_name'] = ts('The username %1 is already taken. Please select another username.', [1 => $params['name']]);
      }
    }

    if (!empty($params['mail'])) {
      if (!is_email($params['mail'])) {
        $errors[$emailName] = ts("Your email is invalid");
      }
      elseif (email_exists($params['mail'])) {
        $errors[$emailName] = ts('The email address %1 already has an account associated with it. <a href="%2">Have you forgotten your password?</a>',
          [1 => $params['mail'], 2 => wp_lostpassword_url()]
        );
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function isUserLoggedIn() {
    $isloggedIn = FALSE;
    if (function_exists('is_user_logged_in')) {
      $isloggedIn = is_user_logged_in();
    }

    return $isloggedIn;
  }

  /**
   * @inheritDoc
   */
  public function isUserRegistrationPermitted() {
    if (!get_option('users_can_register')) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function isPasswordUserGenerated() {
    return FALSE;
  }

  /**
   * @return mixed
   */
  public function getLoggedInUserObject() {
    if (function_exists('is_user_logged_in') &&
      is_user_logged_in()
    ) {
      global $current_user;
    }
    return $current_user;
  }

  /**
   * @inheritDoc
   */
  public function getLoggedInUfID() {
    $ufID = NULL;
    $current_user = $this->getLoggedInUserObject();
    return $current_user->ID ?? NULL;
  }

  /**
   * @inheritDoc
   */
  public function getLoggedInUniqueIdentifier() {
    $user = $this->getLoggedInUserObject();
    return $this->getUniqueIdentifierFromUserObject($user);
  }

  /**
   * Get User ID from UserFramework system (Joomla)
   * @param object $user
   *   Object as described by the CMS.
   *
   * @return int|null
   */
  public function getUserIDFromUserObject($user) {
    return !empty($user->ID) ? $user->ID : NULL;
  }

  /**
   * @inheritDoc
   */
  public function getUniqueIdentifierFromUserObject($user) {
    return empty($user->user_email) ? NULL : $user->user_email;
  }

  /**
   * @inheritDoc
   */
  public function getLoginURL($destination = '') {
    return wp_login_url($destination);
  }

  /**
   * @param \CRM_Core_Form $form
   *
   * @return NULL|string
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
      $destination = CRM_Utils_System::url(CRM_Utils_System::currentPath(), 'reset=1' . $args);
    }
    return $destination;
  }

  /**
   * @inheritDoc
   */
  public function getVersion() {
    if (function_exists('get_bloginfo')) {
      return get_bloginfo('version', 'display');
    }
    else {
      return 'Unknown';
    }
  }

  /**
   * @inheritDoc
   */
  public function getTimeZoneString() {
    // Return the timezone string when set.
    $tzstring = get_option('timezone_string');
    if (!empty($tzstring)) {
      return $tzstring;
    }

    /*
     * Try and build a deprecated (but currently valid) timezone string
     * from the GMT offset value.
     *
     * Note: manual offsets should be discouraged. WordPress works more
     * reliably when setting an actual timezone (e.g. "Europe/London")
     * because of support for Daylight Saving changes.
     *
     * Note: the IANA timezone database that provides PHP's timezone
     * support uses (reversed) POSIX style signs.
     *
     * @see https://www.php.net/manual/en/timezones.others.php
     */
    $offset = get_option('gmt_offset');
    if (0 != $offset && floor($offset) == $offset) {
      $offset_string = $offset > 0 ? "-$offset" : '+' . abs((int) $offset);
      $tzstring = 'Etc/GMT' . $offset_string;
    }

    // Default to "UTC" if the timezone string is still empty.
    if (empty($tzstring)) {
      $tzstring = 'UTC';
    }

    return $tzstring;
  }

  /**
   * @inheritDoc
   */
  public function getUserRecordUrl($contactID) {
    $uid = CRM_Core_BAO_UFMatch::getUFId($contactID);
    if (CRM_Core_Session::singleton()
      ->get('userID') == $contactID || CRM_Core_Permission::checkAnyPerm(['cms:administer users'])
    ) {
      return Civi::paths()->getVariable('wp.backend.base', 'url') . 'user-edit.php?user_id=' . $uid;
    }
  }

  /**
   * Append WP js to coreResourcesList.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public function appendCoreResources(\Civi\Core\Event\GenericHookEvent $e) {
    $e->list[] = 'js/crm.wordpress.js';
  }

  /**
   * @inheritDoc
   */
  public function alterAssetUrl(\Civi\Core\Event\GenericHookEvent $e) {
    // Set menubar breakpoint to match WP admin theme
    if ($e->asset == 'crm-menubar.css') {
      $e->params['breakpoint'] = 783;
    }
  }

  /**
   * @inheritDoc
   */
  public function checkPermissionAddUser() {
    return current_user_can('create_users');
  }

  /**
   * @inheritDoc
   */
  public function synchronizeUsers() {
    $config = CRM_Core_Config::singleton();
    if (PHP_SAPI != 'cli') {
      set_time_limit(300);
    }
    $id = 'ID';
    $mail = 'user_email';

    $uf = $config->userFramework;
    $contactCount = 0;
    $contactCreated = 0;
    $contactMatching = 0;

    // Previously used the $wpdb global - which means WordPress *must* be bootstrapped.
    $wpUsers = get_users([
      'blog_id' => get_current_blog_id(),
      'number' => -1,
    ]);

    foreach ($wpUsers as $wpUserData) {
      $contactCount++;
      if ($match = CRM_Core_BAO_UFMatch::synchronizeUFMatch($wpUserData,
        $wpUserData->$id,
        $wpUserData->$mail,
        $uf,
        1,
        'Individual',
        TRUE
      )
      ) {
        $contactCreated++;
      }
      else {
        $contactMatching++;
      }
      if (is_object($match)) {
        $match->free();
      }
    }

    return [
      'contactCount' => $contactCount,
      'contactMatching' => $contactMatching,
      'contactCreated' => $contactCreated,
    ];
  }

  /**
   * Send an HTTP Response base on PSR HTTP RespnseInterface response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  public function sendResponse(\Psr\Http\Message\ResponseInterface $response) {
    // use WordPress function status_header to ensure 404 response is sent
    status_header($response->getStatusCode());
    foreach ($response->getHeaders() as $name => $values) {
      CRM_Utils_System::setHttpHeader($name, implode(', ', (array) $values));
    }
    echo $response->getBody();
    CRM_Utils_System::civiExit();
  }

  /**
   * Start a new session if there's no existing session ID.
   *
   * Checks are needed to prevent sessions being started when not necessary.
   */
  public function sessionStart() {
    $session_id = session_id();

    // Check WordPress pseudo-cron.
    $wp_cron = FALSE;
    if (function_exists('wp_doing_cron') && wp_doing_cron()) {
      $wp_cron = TRUE;
    }

    // Check WP-CLI.
    $wp_cli = FALSE;
    if (defined('WP_CLI') && WP_CLI) {
      $wp_cli = TRUE;
    }

    // Check PHP on the command line - e.g. `cv`.
    $php_cli = TRUE;
    if (PHP_SAPI !== 'cli') {
      $php_cli = FALSE;
    }

    // Maybe start session.
    if (empty($session_id) && !$wp_cron && !$wp_cli && !$php_cli) {
      session_start();
    }
  }

  /**
   * Get role names
   *
   * @return array
   */
  public function getRoleNames() {
    return wp_roles()->role_names;
  }

  /**
   * Perform any necessary actions prior to redirecting via POST.
   *
   * Redirecting via POST means that cookies need to be sent with SameSite=None.
   */
  public function prePostRedirect() {
    // Get User Agent string.
    $rawUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $userAgent = mb_convert_encoding($rawUserAgent, 'UTF-8');

    // Bail early if User Agent does not support `SameSite=None`.
    $shouldUseSameSite = CRM_Utils_SameSite::shouldSendSameSiteNone($userAgent);
    if (!$shouldUseSameSite) {
      return;
    }

    // Make sure session cookie is present in header.
    $cookie_params = session_name() . '=' . session_id() . '; SameSite=None; Secure';
    CRM_Utils_System::setHttpHeader('Set-Cookie', $cookie_params);

    // Add WordPress auth cookies when user is logged in.
    $user = wp_get_current_user();
    if ($user->exists()) {
      self::setAuthCookies($user->ID, TRUE, TRUE);
    }
  }

  /**
   * Explicitly set WordPress authentication cookies.
   *
   * Chrome 84 introduced a cookie policy change which prevents cookies for the
   * session and for WordPress user authentication from being indentified when
   * a purchaser returns to the site from PayPal using the "Back to Merchant"
   * button.
   *
   * In order to comply with this policy, cookies need to be sent with their
   * "SameSite" attribute set to "None" and with the "Secure" flag set, but this
   * isn't possible to do via `wp_set_auth_cookie()` as it stands.
   *
   * This method is a modified clone of `wp_set_auth_cookie()` which satisfies
   * the Chrome policy.
   *
   * @see wp_set_auth_cookie()
   *
   * The $remember parameter increases the time that the cookie will be kept. The
   * default the cookie is kept without remembering is two days. When $remember is
   * set, the cookies will be kept for 14 days or two weeks.
   *
   * @param int $user_id The WordPress User ID.
   * @param bool $remember Whether to remember the user.
   * @param bool|string $secure Whether the auth cookie should only be sent over
   *                            HTTPS. Default is an empty string which means the
   *                            value of `is_ssl()` will be used.
   * @param string $token Optional. User's session token to use for this cookie.
   */
  private function setAuthCookies($user_id, $remember = FALSE, $secure = '', $token = '') {
    if ($remember) {
      /** This filter is documented in wp-includes/pluggable.php */
      $expiration = time() + apply_filters('auth_cookie_expiration', 14 * DAY_IN_SECONDS, $user_id, $remember);

      /*
       * Ensure the browser will continue to send the cookie after the expiration time is reached.
       * Needed for the login grace period in wp_validate_auth_cookie().
       */
      $expire = $expiration + (12 * HOUR_IN_SECONDS);
    }
    else {
      /** This filter is documented in wp-includes/pluggable.php */
      $expiration = time() + apply_filters('auth_cookie_expiration', 2 * DAY_IN_SECONDS, $user_id, $remember);
      $expire = 0;
    }

    if ('' === $secure) {
      $secure = is_ssl();
    }

    // Front-end cookie is secure when the auth cookie is secure and the site's home URL is forced HTTPS.
    $secure_logged_in_cookie = $secure && 'https' === parse_url(get_option('home'), PHP_URL_SCHEME);

    /** This filter is documented in wp-includes/pluggable.php */
    $secure = apply_filters('secure_auth_cookie', $secure, $user_id);

    /** This filter is documented in wp-includes/pluggable.php */
    $secure_logged_in_cookie = apply_filters('secure_logged_in_cookie', $secure_logged_in_cookie, $user_id, $secure);

    if ($secure) {
      $auth_cookie_name = SECURE_AUTH_COOKIE;
      $scheme = 'secure_auth';
    }
    else {
      $auth_cookie_name = AUTH_COOKIE;
      $scheme = 'auth';
    }

    if ('' === $token) {
      $manager = WP_Session_Tokens::get_instance($user_id);
      $token = $manager->create($expiration);
    }

    $auth_cookie = wp_generate_auth_cookie($user_id, $expiration, $scheme, $token);
    $logged_in_cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in', $token);

    /** This filter is documented in wp-includes/pluggable.php */
    do_action('set_auth_cookie', $auth_cookie, $expire, $expiration, $user_id, $scheme, $token);

    /** This filter is documented in wp-includes/pluggable.php */
    do_action('set_logged_in_cookie', $logged_in_cookie, $expire, $expiration, $user_id, 'logged_in', $token);

    /** This filter is documented in wp-includes/pluggable.php */
    if (!apply_filters('send_auth_cookies', TRUE)) {
      return;
    }

    $base_options = [
      'expires' => $expire,
      'domain' => COOKIE_DOMAIN,
      'httponly' => TRUE,
      'samesite' => 'None',
    ];

    self::setAuthCookie($auth_cookie_name, $auth_cookie, $base_options + ['secure' => $secure, 'path' => PLUGINS_COOKIE_PATH]);
    self::setAuthCookie($auth_cookie_name, $auth_cookie, $base_options + ['secure' => $secure, 'path' => ADMIN_COOKIE_PATH]);
    self::setAuthCookie(LOGGED_IN_COOKIE, $logged_in_cookie, $base_options + ['secure' => $secure_logged_in_cookie, 'path' => COOKIEPATH]);
    if (COOKIEPATH != SITECOOKIEPATH) {
      self::setAuthCookie(LOGGED_IN_COOKIE, $logged_in_cookie, $base_options + ['secure' => $secure_logged_in_cookie, 'path' => SITECOOKIEPATH]);
    }
  }

  /**
   * Set cookie with "SameSite" flag.
   *
   * The method here is compatible with all versions of PHP. Needed because it
   * is only as of PHP 7.3.0 that the setcookie() method supports the "SameSite"
   * attribute in its options and will accept "None" as a valid value.
   *
   * @param string $name The name of the cookie.
   * @param string $value The value of the cookie.
   * @param array $options The header options for the cookie.
   */
  private function setAuthCookie($name, $value, $options) {
    $header = 'Set-Cookie: ';
    $header .= rawurlencode($name) . '=' . rawurlencode($value) . '; ';
    $header .= 'expires=' . gmdate('D, d-M-Y H:i:s T', $options['expires']) . '; ';
    $header .= 'Max-Age=' . max(0, (int) ($options['expires'] - time())) . '; ';
    $header .= 'path=' . rawurlencode($options['path']) . '; ';
    $header .= 'domain=' . rawurlencode($options['domain']) . '; ';

    if (!empty($options['secure'])) {
      $header .= 'secure; ';
    }
    $header .= 'httponly; ';
    $header .= 'SameSite=' . rawurlencode($options['samesite']);

    header($header, FALSE);
    $_COOKIE[$name] = $value;
  }

  /**
   * Return the CMS-specific url for its permissions page
   * @return array
   */
  public function getCMSPermissionsUrlParams() {
    return ['ufAccessURL' => CRM_Utils_System::url('civicrm/admin/access/wp-permissions', 'reset=1')];
  }

  /**
   * Remove CiviCRM's callbacks.
   *
   * These may cause recursive updates when creating or editing a WordPress
   * user. This doesn't seem to have been necessary in the past, but seems
   * to be causing trouble when newer versions of BuddyPress and CiviCRM are
   * active.
   *
   * Based on the civicrm-wp-profile-sync plugin by Christian Wach.
   *
   * @see self::hooks_core_add()
   */
  public function hooks_core_remove() {
    $civicrm = civi_wp();

    // Remove current CiviCRM plugin filters.
    remove_action('user_register', [$civicrm->users, 'update_user']);
    remove_action('profile_update', [$civicrm->users, 'update_user']);
  }

  /**
   * Add back CiviCRM's callbacks.
   * This method undoes the removal of the callbacks above.
   *
   * @see self::hooks_core_remove()
   */
  public function hooks_core_add() {
    $civicrm = civi_wp();

    // Re-add current CiviCRM plugin filters.
    add_action('user_register', [$civicrm->users, 'update_user']);
    add_action('profile_update', [$civicrm->users, 'update_user']);
  }

  /**
   * Depending on configuration, either let the admin enter the password
   * when creating a user or let the user do it via email link.
   */
  public function showPasswordFieldWhenAdminCreatesUser() {
    return !$this->isUserRegistrationPermitted();
  }

  /**
   * Should the current execution exit after a fatal error?
   *
   * In WordPress, it is not usually possible to trigger theming outside of the WordPress theme process,
   * meaning that in order to render an error inside the theme we cannot exit on error.
   *
   * @internal
   * @return bool
   */
  public function shouldExitAfterFatal() {
    $ret = TRUE;
    if (!is_admin() && !wp_doing_ajax()) {
      $ret = FALSE;
    }

    return apply_filters('civicrm_exit_after_fatal', $ret);
  }

  /**
   * Make sure clean URLs are properly set in settings file.
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkCleanurls() {
    $config = CRM_Core_Config::singleton();
    $clean = 0;
    if (defined('CIVICRM_CLEANURL')) {
      $clean = CIVICRM_CLEANURL;
    }
    if ($clean == 1) {
      //cleanURLs are enabled in CiviCRM, let's make sure the wordpress permalink settings and cache are actually correct by checking the first active contribution page
      $contributionPages = !CRM_Core_Component::isEnabled('CiviContribute') ? [] : \Civi\Api4\ContributionPage::get(FALSE)
        ->addSelect('id')
        ->addWhere('is_active', '=', TRUE)
        ->setLimit(1)
        ->execute();
      if (count($contributionPages) > 0) {
        $activePageId = $contributionPages[0]['id'];
        $message = self::checkCleanPage('/contribute/transact/?reset=1&id=', $activePageId, $config);

        return $message;
      }
      else {
        //no active contribution pages, we can check an event page. This probably won't ever happen.
        $eventPages = !CRM_Core_Component::isEnabled('CiviEvent') ? [] : \Civi\Api4\Event::get(FALSE)
          ->addSelect('id')
          ->addWhere('is_active', '=', TRUE)
          ->setLimit(1)
          ->execute();
        if (count($eventPages) > 0) {
          $activePageId = $eventPages[0]['id'];
          $message = self::checkCleanPage('/event/info/?reset=1&id=', $activePageId, $config);

          return $message;
        }
        else {
          //If there are no active event or contribution pages, we'll skip this check for now.

          return [];
        }
      }
    }
    else {
      //cleanURLs aren't enabled or aren't defined correctly in CiviCRM, admin should check civicrm.settings.php
      $warning = ts('Clean URLs are not enabled correctly in CiviCRM. This can lead to "valid id" errors for users registering for events or making donations. Check civicrm.settings.php and review <a %1>the documentation</a> for more information.', [1 => 'href="' . CRM_Utils_System::docURL2('sysadmin/integration/wordpress/clean-urls/', TRUE) . '"']);

      return [
        new CRM_Utils_Check_Message(
          __FUNCTION__,
          $warning,
          ts('Clean URLs Not Enabled'),
          \Psr\Log\LogLevel::WARNING,
          'fa-wordpress'
        ),
      ];
    }
  }

  private static function checkCleanPage($slug, $id, $config) {
    $page = $config->userFrameworkBaseURL . $config->wpBasePage . $slug . $id;
    try {
      $client = new \GuzzleHttp\Client();
      $res = $client->head($page, ['http_errors' => FALSE]);
      $httpCode = $res->getStatusCode();
    }
    catch (Exception $e) {
      Civi::log()->error("Could not run " . __FUNCTION__ . " on $page. GuzzleHttp\Client returned " . $e->getMessage());

      return [
        new CRM_Utils_Check_Message(
          __FUNCTION__,
          ts('Could not load a clean page to check'),
          ts('Guzzle client error'),
          \Psr\Log\LogLevel::ERROR,
          'fa-wordpress'
        ),
      ];
    }

    if ($httpCode == 404) {
      $warning = ts('<a %1>Click here to go to Settings > Permalinks, then click "Save" to refresh the cache.</a>', [1 => 'href="' . get_admin_url(NULL, 'options-permalink.php') . '"']);
      $message = new CRM_Utils_Check_Message(
        __FUNCTION__,
        $warning,
        ts('Wordpress Permalinks cache needs to be refreshed.'),
        \Psr\Log\LogLevel::WARNING,
        'fa-wordpress'
      );

      return [$message];
    }

    //sanity
    return [];

  }

  /**
   * @inheritdoc
   */
  public function canSetBasePage():bool {
    return TRUE;
  }

  /**
   * @inheritdoc
   * @todo why are the environment checks here? could they be removed
   */
  public function theme(&$content, $print = FALSE, $maintenance = FALSE) {
    if ($maintenance) {
      \CRM_Core_Error::deprecatedWarning('Calling CRM_Utils_Base::theme with $maintenance is deprecated - use renderMaintenanceMessage instead');
    }
    if (!$print) {
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

    print $content;
    return NULL;
  }

  /**
   * @inheritdoc
   * @todo environment checks are copied from the original implementation of `theme` above and should probably
   * be removed
   */
  public function renderMaintenanceMessage(string $content): string {
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

    return $content;
  }

  /**
   * @inheritdoc
   */
  public function getContactDetailsFromUser($uf_match):array {
    $contactParameters = [];

    $user = $uf_match['user'];
    $contactParameters['email'] = $user->user_email;
    if ($user->first_name) {
      $contactParameters['first_name'] = $user->first_name;
    }
    if ($user->last_name) {
      $contactParameters['last_name'] = $user->last_name;
    }

    return $contactParameters;
  }

  /**
   * @inheritdoc
   */
  public function modifyStandaloneProfile($profile, $params):string {
    $urlReplaceWith = 'civicrm/profile/create&amp;gid=' . $params['gid'] . '&amp;reset=1';
    $profile = str_replace('civicrm/admin/uf/group', $urlReplaceWith, $profile);

    //@todo remove this part when it is OK to deprecate CIVICRM_UF_WP_BASEPAGE-CRM-15933
    $config = CRM_Core_Config::singleton();
    if (defined('CIVICRM_UF_WP_BASEPAGE')) {
      $wpbase = CIVICRM_UF_WP_BASEPAGE;
    }
    elseif (!empty($config->wpBasePage)) {
      $wpbase = $config->wpBasePage;
    }
    else {
      $wpbase = 'index.php';
    }
    $profile = str_replace('/wp-admin/admin.php', '/' . $wpbase . '/', $profile);
    return $profile;
  }

}
