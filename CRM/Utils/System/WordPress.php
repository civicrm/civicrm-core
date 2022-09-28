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
            \Civi::log()->warning("The system has data from both old+new conventions. Please use civicrm.settings.php to set civicrm.files explicitly.");
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
  public function getCiviSourceStorage() {
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

    $template = CRM_Core_Smarty::singleton();
    $template->assign_by_ref('breadcrumb', $breadCrumb);
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
  public function mapConfigToSSL() {
    global $base_url;
    $base_url = str_replace('http://', 'https://', $base_url);
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
    $forceBackend = FALSE,
    $htmlize = TRUE
  ) {
    $config = CRM_Core_Config::singleton();
    $script = '';
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
        $script = get_permalink($post->ID);
        if ($config->wpBasePage == $post->post_name) {
          $basepage = TRUE;
        }
      }

    }
    else {

      // Get the Base Page URL for building front-end URLs.
      if ($frontend && !$forceBackend) {
        $script = $this->getBasePageUrl();
        $basepage = TRUE;
      }

    }

    // Get either the relative Base Page URL or the relative Admin Page URL.
    $base = $this->getBaseUrl($absolute, $frontend, $forceBackend);

    // Overwrite base URL if we already have a front-end URL.
    if (!$forceBackend && $script != '') {
      $base = $script;
    }

    $queryParts = [];
    $admin_request = ((is_admin() && !$frontend) || $forceBackend);

    if (
      // If not using Clean URLs.
      !$config->cleanURL
      // Or requesting an admin URL.
      || $admin_request
      // Or this is a Shortcode.
      || (!$basepage && $script != '')
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
    static $basepage_url = '';
    if ($basepage_url === '') {

      // Get the Base Page config setting.
      $config = CRM_Core_Config::singleton();
      $basepage_slug = $config->wpBasePage;

      // Did we get a value?
      if (!empty($basepage_slug)) {

        // Query for our Base Page.
        $pages = get_posts([
          'post_type' => 'page',
          'name' => strtolower($basepage_slug),
          'post_status' => 'publish',
          'posts_per_page' => 1,
        ]);

        // Find the Base Page object and set the URL.
        if (!empty($pages) && is_array($pages)) {
          $basepage = array_pop($pages);
          if ($basepage instanceof WP_Post) {
            $basepage_url = get_permalink($basepage->ID);
          }
        }

      }

    }

    return $basepage_url;
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
    $forceBackend = FALSE,
    $htmlize = TRUE
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
    if (!$userdata->data->ID) {
      return FALSE;
    }

    $uid = $userdata->data->ID;
    wp_set_current_user($uid);
    $contactID = CRM_Core_BAO_UFMatch::getContactId($uid);

    // lets store contact id and user id in session
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
    if (!$userdata->data->ID) {
      return NULL;
    }
    return $userdata->data->ID;
  }

  /**
   * @inheritDoc
   */
  public function logout() {
    // destroy session
    if (session_id()) {
      session_destroy();
    }
    wp_logout();
    wp_redirect(wp_login_url());
  }

  /**
   * @inheritDoc
   */
  public function getUFLocale() {
    // Bail early if method is called when WordPress isn't bootstrapped.
    // Additionally, the function checked here is located in pluggable.php
    // and is required by wp_get_referer() - so this also bails early if it is
    // called too early in the request lifecycle.
    // @see https://core.trac.wordpress.org/ticket/25294
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

      // Maybe override with the locale that Polylang reports.
      if (function_exists('pll_current_language')) {
        $pll_locale = pll_current_language('locale');
        if (!empty($pll_locale)) {
          $locale = $pll_locale;
        }
      }

      // Maybe override with the locale that WPML reports.
      elseif (defined('ICL_LANGUAGE_CODE')) {
        $languages = apply_filters('wpml_active_languages', NULL);
        foreach ($languages as $language) {
          if ($language['active']) {
            $locale = $language['default_locale'];
            break;
          }
        }
      }

      // TODO: Set locale for other WordPress plugins.
      // @see https://wordpress.org/plugins/tags/multilingual/
      // A hook would be nice here.

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
   * Load wordpress bootstrap.
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
    $wpUserTimezone = get_option('timezone_string');
    if ($wpUserTimezone) {
      date_default_timezone_set($wpUserTimezone);
      CRM_Core_Config::singleton()->userSystem->setMySQLTimeZone();
    }
    require_once $cmsRootPath . DIRECTORY_SEPARATOR . 'wp-includes/pluggable.php';
    $uid = $params['uid'] ?? NULL;
    if (!$uid) {
      $name = $name ? $name : trim(CRM_Utils_Array::value('name', $_REQUEST));
      $pass = $pass ? $pass : trim(CRM_Utils_Array::value('pass', $_REQUEST));
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
   * @param $dir
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
  public function createUser(&$params, $mail) {
    $user_data = [
      'ID' => '',
      'user_login' => $params['cms_name'],
      'user_email' => $params[$mail],
      'nickname' => $params['cms_name'],
      'role' => get_option('default_role'),
    ];

    // If there's a password add it, otherwise generate one.
    if (!empty($params['cms_pass'])) {
      $user_data['user_pass'] = $params['cms_pass'];
    }
    else {
      $user_data['user_pass'] = wp_generate_password(12, FALSE);;
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
     * Broadcast that CiviCRM is about to create a WordPress User.
     *
     * @since 5.37
     */
    do_action('civicrm_pre_create_user');

    // Remove the CiviCRM-WordPress listeners.
    $this->hooks_core_remove();

    // Now go ahead and create a WordPress User.
    $uid = wp_insert_user($user_data);

    /*
     * Call wp_signon if we aren't already logged in.
     * For example, we might be creating a new user from the Contact record.
     */
    if (!current_user_can('create_users')) {
      $creds = [];
      $creds['user_login'] = $params['cms_name'];
      $creds['remember'] = TRUE;
      wp_signon($creds, FALSE);
    }

    // Fire the new user action. Sends notification email by default.
    do_action('register_new_user', $uid);

    // Restore the CiviCRM-WordPress listeners.
    $this->hooks_core_add();

    /**
     * Broadcast that CiviCRM has creates a WordPress User.
     *
     * @since 5.37
     */
    do_action('civicrm_post_create_user');

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
   * @param array $params
   * @param $errors
   * @param string $emailName
   */
  public function checkUserNameEmailExists(&$params, &$errors, $emailName = 'email') {
    $config = CRM_Core_Config::singleton();

    $dao = new CRM_Core_DAO();
    $name = $dao->escape(CRM_Utils_Array::value('name', $params));
    $email = $dao->escape(CRM_Utils_Array::value('mail', $params));

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
        $errors[$emailName] = "Your email is invaid";
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
    return get_option('timezone_string');
  }

  /**
   * @inheritDoc
   */
  public function getUserRecordUrl($contactID) {
    $uid = CRM_Core_BAO_UFMatch::getUFId($contactID);
    if (CRM_Core_Session::singleton()
      ->get('userID') == $contactID || CRM_Core_Permission::checkAnyPerm(['cms:administer users'])
    ) {
      return CRM_Core_Config::singleton()->userFrameworkBaseURL . "wp-admin/user-edit.php?user_id=" . $uid;
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
    $wpUsers = get_users(array(
      'blog_id' => get_current_blog_id(),
      'number' => -1,
    ));

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
   * Perform any necessary actions prior to redirecting via POST.
   *
   * Redirecting via POST means that cookies need to be sent with SameSite=None.
   */
  public function prePostRedirect() {
    // Get User Agent string.
    $rawUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
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
   * @param $name The name of the cookie.
   * @param $value The value of the cookie.
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

}
