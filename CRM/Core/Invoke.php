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
 * Given an argument list, invoke the appropriate CRM function
 * Serves as a wrapper between the UserFrameWork and Core CRM
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Invoke {

  /**
   * This is the main front-controller that integrates with the CMS. Any
   * page-request that is sent to the CMS and intended for CiviCRM should
   * be processed by invoke().
   *
   * @param array $args
   *   The parts of the URL which identify the intended CiviCRM page
   *   (e.g. array('civicrm', 'event', 'register')).
   * @return string
   *   HTML. For non-HTML content, invoke() may call print() and exit().
   *
   */
  public static function invoke($args) {
    try {
      return self::_invoke($args);
    }
    catch (Exception $e) {
      CRM_Core_Error::handleUnhandledException($e);
    }
  }

  /**
   * This is the same as invoke(), but it does *not* include exception
   * handling.
   *
   * @param array $args
   *   The parts of the URL which identify the intended CiviCRM page
   *   (e.g. array('civicrm', 'event', 'register')).
   * @return string
   *   HTML. For non-HTML content, invoke() may call print() and exit().
   */
  public static function _invoke($args) {
    if ($args[0] !== 'civicrm') {
      return NULL;
    }
    // CRM-15901: Turn off PHP errors display for all ajax calls
    if (($args[1] ?? NULL) == 'ajax' || !empty($_REQUEST['snippet'])) {
      ini_set('display_errors', 0);
    }
    // Guard against CSRF for ajax snippets
    $ajaxModes = [CRM_Core_Smarty::PRINT_SNIPPET, CRM_Core_Smarty::PRINT_NOFORM, CRM_Core_Smarty::PRINT_JSON];
    if (in_array($_REQUEST['snippet'] ?? NULL, $ajaxModes)) {
      CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    }

    self::hackMenuRebuild($args);
    self::init($args);
    Civi::dispatcher()->dispatch('civi.invoke.auth', \Civi\Core\Event\GenericHookEvent::create(['args' => $args]));
    $item = self::getItem($args);
    return self::runItem($item);
    // NOTE: runItem() may return HTML, or it may call print+exit.
  }

  /**
   * Hackish support /civicrm/menu/rebuild
   *
   * @param array $args
   *   List of path parts.
   * @void
   */
  public static function hackMenuRebuild($args) {
    if (['civicrm', 'menu', 'rebuild'] == $args || ['civicrm', 'clearcache'] == $args) {
      // ensure that the user has a good privilege level
      if (CRM_Core_Permission::check('administer CiviCRM')) {
        self::rebuildMenuAndCaches();
        CRM_Core_Session::setStatus(ts('Cleared all CiviCRM caches (database, menu, templates)'), ts('Complete'), 'success');
        // exits
        return CRM_Utils_System::redirect();
      }
      else {
        CRM_Core_Error::statusBounce(ts('You do not have permission to execute this url'));
      }
    }
  }

  /**
   * Perform general setup.
   *
   * @param array $args
   *   List of path parts.
   * @void
   */
  public static function init($args) {
    // first fire up IDS and check for bad stuff
    $config = CRM_Core_Config::singleton();

    // also initialize the i18n framework
    $i18n = CRM_Core_I18n::singleton();
  }

  /**
   * Determine which menu $item corresponds to $args
   *
   * @param string|string[] $args
   *   Path to lookup
   *   Ex: 'civicrm/foo/bar'
   *   Ex: ['civicrm', 'foo', 'bar']
   * @return array; see CRM_Core_Menu
   */
  public static function getItem($args) {
    if (is_array($args)) {
      // get the menu items
      $path = implode('/', $args);
    }
    else {
      $path = $args;
    }
    $item = CRM_Core_Menu::get($path);

    // we should try to compute menus, if item is empty and stay on the same page,
    // rather than compute and redirect to dashboard.
    if (!$item) {
      CRM_Core_Menu::store(FALSE);
      $item = CRM_Core_Menu::get($path);
    }

    return $item;
  }

  /**
   * Register an alternative phar:// stream wrapper to filter out insecure Phars
   *
   * PHP makes it possible to trigger Object Injection vulnerabilities by using
   * a side-effect of the phar:// stream wrapper that unserializes Phar
   * metadata. To mitigate this vulnerability, projects such as TYPO3 and Drupal
   * have implemented an alternative Phar stream wrapper that disallows
   * inclusion of phar files based on certain parameters.
   *
   * This code attempts to register the TYPO3 Phar stream wrapper using the
   * interceptor defined in \Civi\Core\Security\PharExtensionInterceptor. In an
   * environment where the stream wrapper was already registered via
   * \TYPO3\PharStreamWrapper\Manager (i.e. Drupal), this code does not do
   * anything. In other environments (e.g. WordPress, at the time of this
   * writing), the TYPO3 library is used to register the interceptor to mitigate
   * the vulnerability.
   */
  private static function registerPharHandler() {
    try {
      // try to get the existing stream wrapper, registered e.g. by Drupal
      \TYPO3\PharStreamWrapper\Manager::instance();
    }
    catch (\LogicException $e) {
      if ($e->getCode() === 1535189872) {
        // no phar stream wrapper was registered by \TYPO3\PharStreamWrapper\Manager.
        // This means we're probably not on Drupal and need to register our own.
        \TYPO3\PharStreamWrapper\Manager::initialize(
          (new \TYPO3\PharStreamWrapper\Behavior())
            ->withAssertion(new \Civi\Core\Security\PharExtensionInterceptor())
        );
        if (in_array('phar', stream_get_wrappers())) {
          stream_wrapper_unregister('phar');
          stream_wrapper_register('phar', \TYPO3\PharStreamWrapper\PharStreamWrapper::class);
        }
      }
      else {
        // this is not an exception we can handle
        throw $e;
      }
    }
  }

  /**
   * Given a menu item, call the appropriate controller and return the response
   *
   * @param array $item
   *   See CRM_Core_Menu.
   *
   * @return string, HTML
   * @throws \CRM_Core_Exception
   */
  public static function runItem($item) {
    $ids = new CRM_Core_IDS();
    $ids->check($item);

    self::registerPharHandler();

    $config = CRM_Core_Config::singleton();

    // WISHLIST: if $item is a web-service route, swap prepend to $civicrm_url_defaults

    if ($config->userFramework == 'Joomla' && $item) {
      $config->userFrameworkURLVar = 'task';

      // joomla 1.5RC1 seems to push this in the POST variable, which messes
      // QF and checkboxes
      unset($_POST['option']);
      CRM_Core_Joomla::sidebarLeft();
    }

    // set active Component
    $template = CRM_Core_Smarty::singleton();
    $template->assign('activeComponent', 'CiviCRM');
    $template->assign('formTpl', 'default');
    // Ensure template variables have 'something' assigned for e-notice
    // prevention. These are ones that are included very often
    // and not tied to a specific form.
    // jsortable.tpl (datatables)
    $template->assign('sourceUrl');
    $template->assign('useAjax', 0);
    $template->assign('defaultOrderByDirection', 'asc');

    if ($item) {

      if (!array_key_exists('page_callback', $item)) {
        CRM_Core_Error::debug('Bad item', $item);
        CRM_Core_Error::statusBounce(ts('Bad menu record in database'));
      }

      // check that we are permissioned to access this page
      if (!CRM_Core_Permission::checkMenuItem($item)) {
        CRM_Utils_System::permissionDenied();
        return NULL;
      }

      // check if ssl is set
      if (!empty($item['is_ssl'])) {
        CRM_Utils_System::redirectToSSL();
      }

      if (isset($item['title'])) {
        CRM_Utils_System::setTitle($item['title']);
      }

      if (!CRM_Core_Config::isUpgradeMode() && isset($item['breadcrumb']) && empty($item['is_public'])) {
        CRM_Utils_System::appendBreadCrumb($item['breadcrumb']);
      }

      $pageArgs = NULL;
      if (!empty($item['page_arguments'])) {
        $pageArgs = CRM_Core_Menu::getArrayForPathArgs($item['page_arguments']);
      }

      $template = CRM_Core_Smarty::singleton();
      if (!empty($item['is_public'])) {
        $template->assign('urlIsPublic', TRUE);
      }
      else {
        $template->assign('urlIsPublic', FALSE);
        self::statusCheck($template);
      }

      if (isset($item['return_url'])) {
        $session = CRM_Core_Session::singleton();
        $args = CRM_Utils_Array::value(
          'return_url_args',
          $item,
          'reset=1'
        );
        $session->pushUserContext(CRM_Utils_System::url($item['return_url'], $args));
      }

      $result = NULL;
      // WISHLIST: Refactor this. Instead of pattern-matching on page_callback, lookup
      // page_callback via Civi\Core\Resolver and check the implemented interfaces. This
      // would require rethinking the default constructor.
      if (is_array($item['page_callback']) || strpos($item['page_callback'], ':')) {
        $result = call_user_func(Civi\Core\Resolver::singleton()->get($item['page_callback']));
      }
      elseif (strpos($item['page_callback'], '_Form') !== FALSE) {
        $wrapper = new CRM_Utils_Wrapper();
        $result = $wrapper->run(
          $item['page_callback'] ?? NULL,
          $item['title'] ?? NULL,
          $pageArgs ?? NULL
        );
      }
      else {
        $newArgs = explode('/', $_GET[$config->userFrameworkURLVar]);
        $mode = 'null';
        if (isset($pageArgs['mode'])) {
          $mode = $pageArgs['mode'];
          unset($pageArgs['mode']);
        }
        $title = $item['title'] ?? NULL;
        if (str_contains($item['page_callback'], '_Page') || str_contains($item['page_callback'], '\\Page\\')) {
          $object = new $item['page_callback']($title, $mode);
          $object->urlPath = explode('/', $_GET[$config->userFrameworkURLVar]);
        }
        elseif (str_contains($item['page_callback'], '_Controller') || str_contains($item['page_callback'], '\\Controller\\')) {
          $addSequence = 'false';
          if (isset($pageArgs['addSequence'])) {
            $addSequence = $pageArgs['addSequence'];
            $addSequence = $addSequence ? 'true' : 'false';
            unset($pageArgs['addSequence']);
          }
          if ($item['page_callback'] === 'CRM_Import_Controller') {
            // Let the generic import controller have the page arguments.... so we don't need
            // one class per import.
            $object = new CRM_Import_Controller($title, $pageArgs ?? []);
          }
          else {
            $object = new $item['page_callback']($title, TRUE, $mode, NULL, $addSequence);
          }
        }
        else {
          throw new CRM_Core_Exception('Execute supplied menu action');
        }
        $result = $object->run($newArgs, $pageArgs);
      }

      CRM_Core_Session::storeSessionObjects();
      return $result;
    }

    CRM_Core_Menu::store();
    CRM_Core_Session::setStatus(ts('Menu has been rebuilt'), ts('Complete'), 'success');
    return CRM_Utils_System::redirect();
  }

  /**
   * This function contains the default action.
   *
   * Unused function.
   *
   * @param $action
   *
   * @param $contact_type
   * @param $contact_sub_type
   *
   * @Deprecated
   */
  public static function form($action, $contact_type, $contact_sub_type) {
    CRM_Core_Error::deprecatedWarning('unused');
    CRM_Utils_System::setUserContext(['civicrm/contact/search/basic', 'civicrm/contact/view']);
    $wrapper = new CRM_Utils_Wrapper();

    $properties = CRM_Core_Component::contactSubTypeProperties($contact_sub_type, 'Edit');
    if ($properties) {
      $wrapper->run($properties['class'], ts('New %1', [1 => $contact_sub_type]), $action, TRUE);
    }
    else {
      $wrapper->run('CRM_Contact_Form_Contact', ts('New Contact'), $action, TRUE);
    }
  }

  /**
   * Show status in the footer (admin only)
   *
   * @param CRM_Core_Smarty $template
   */
  public static function statusCheck($template) {
    if (CRM_Core_Config::isUpgradeMode() || !CRM_Core_Permission::check('administer CiviCRM')) {
      return;
    }
    // always use cached results - they will be refreshed by the session timer
    $status = Civi::cache('checks')->get('systemStatusCheckResult');
    $template->assign('footer_status_severity', $status);
    $template->assign('footer_status_message', CRM_Utils_Check::toStatusLabel($status));
  }

  /**
   * @param bool $triggerRebuild
   * @param bool $sessionReset
   *
   * @throws Exception
   */
  public static function rebuildMenuAndCaches(bool $triggerRebuild = FALSE, bool $sessionReset = FALSE): void {
    $config = CRM_Core_Config::singleton();
    $config->clearModuleList();

    // dev/core#3660 - Activate any new classloaders/mixins/etc before re-hydrating any data-structures.
    CRM_Extension_System::singleton()->getClassLoader()->refresh();
    CRM_Extension_System::singleton()->getMixinLoader()->run(TRUE);

    // also cleanup all caches
    $config->cleanupCaches($sessionReset || CRM_Utils_Request::retrieve('sessionReset', 'Boolean', CRM_Core_DAO::$_nullObject, FALSE, 0, 'GET'));

    CRM_Core_Menu::store();

    // also reset navigation
    CRM_Core_BAO_Navigation::resetNavigation();

    // also cleanup module permissions
    $config->cleanupPermissions();

    // rebuild word replacement cache - pass false to prevent operations redundant with this fn
    CRM_Core_BAO_WordReplacement::rebuild(FALSE);

    Civi::service('settings_manager')->flush();
    // Clear js caches
    CRM_Core_Resources::singleton()->flushStrings()->resetCacheCode();
    CRM_Case_XMLRepository::singleton(TRUE);

    // also rebuild triggers if requested explicitly
    if (
      $triggerRebuild ||
      CRM_Utils_Request::retrieve('triggerRebuild', 'Boolean', CRM_Core_DAO::$_nullObject, FALSE, 0, 'GET')
    ) {
      Civi::service('sql_triggers')->rebuild();
      // Rebuild Drupal 8/9/10 route cache only if "triggerRebuild" is set to TRUE as it's
      // computationally very expensive and only needs to be done when routes change on the Civi-side.
      // For example - when uninstalling an extension. We already set "triggerRebuild" to true for these operations.
      $config->userSystem->invalidateRouteCache();
    }

    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();
  }

}
