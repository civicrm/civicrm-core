<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * Given an argument list, invoke the appropriate CRM function
 * Serves as a wrapper between the UserFrameWork and Core CRM
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Core_Invoke {

  /**
   * This is the main function that is called on every click action and based on the argument
   * respective functions are called
   *
   * @param $args array this array contains the arguments of the url
   * @return string, HTML
   *
   * @static
   * @access public
   */
  static function invoke($args) {
    try {
      return self::_invoke($args);
    }

    catch (Exception $e) {
      return CRM_Core_Error::handleUnhandledException($e);
    }
  }

  /**
   * @param $args
   */
  protected static function _invoke($args) {
    if ($args[0] !== 'civicrm') {
      return;
    }

    if (!defined('CIVICRM_SYMFONY_PATH')) {
      try {
        // Traditional Civi invocation path
        self::hackMenuRebuild($args); // may exit
        self::init($args);
        self::hackStandalone($args);
        $item = self::getItem($args);
        return self::runItem($item);
      }
      catch (CRM_Core_EXCEPTION $e) {
        $params = $e->getErrorData();
        $message = $e->getMessage();
        if (isset($params['legacy_status_bounce'])) {
          //@todo remove this- see comments on
          //https://github.com/eileenmcnaughton/civicrm-core/commit/ae686b09e2c987091612bb25ba0a58e520a203e7
          CRM_Core_Error::statusBounce($params['message']);
        }
        else {
          $session = CRM_Core_Session::singleton();
          $session->setStatus(
            $message,
            CRM_Utils_Array::value('message_title', $params),
            CRM_Utils_Array::value('message_type', $params, 'error')
          );

          // @todo remove this code - legacy redirect path is an interim measure for moving redirects out of BAO
          // to somewhere slightly more acceptable. they should not be part of the exception class & should
          // be managed @ the form level - if you find a form that is triggering this piece of code
          // you should log a ticket for it to be removed with details about the form you were on.
          if(!empty($params['legacy_redirect_path'])) {
            if(CRM_Utils_System::isDevelopment()) {
              // here we could set a message telling devs to log it per above
            }
            CRM_Utils_System::redirect($params['legacy_redirect_path'], $params['legacy_redirect_query']);
          }
        }
      }
      catch (Exception $e) {
        // Recall: CRM_Core_Config is initialized before calling CRM_Core_Invoke
        $config = CRM_Core_Config::singleton();
        return CRM_Core_Error::handleUnhandledException($e);
        /*
        if ($config->backtrace) {
          return CRM_Core_Error::formatHtmlException($e);
        } else {
         // TODO
        }*/
      }
    } else {
      // Symfony-based invocation path
      require_once CIVICRM_SYMFONY_PATH . '/app/bootstrap.php.cache';
      require_once CIVICRM_SYMFONY_PATH . '/app/AppKernel.php';
      $kernel = new AppKernel('dev', true);
      $kernel->loadClassCache();
      $response = $kernel->handle(Symfony\Component\HttpFoundation\Request::createFromGlobals());
      if (preg_match(':^text/html:', $response->headers->get('Content-Type'))) {
        // let the CMS handle the trappings
        return $response->getContent();
      } else {
        $response->send();
        exit();
      }
    }
  }
  /**
   * Hackish support /civicrm/menu/rebuild
   *
   * @param array $args list of path parts
   * @void
   */
  static public function hackMenuRebuild($args) {
    if (array('civicrm','menu','rebuild') == $args || array('civicrm', 'clearcache') == $args) {
      // ensure that the user has a good privilege level
      if (CRM_Core_Permission::check('administer CiviCRM')) {
        self::rebuildMenuAndCaches();
        CRM_Core_Session::setStatus(ts('Cleared all CiviCRM caches (database, menu, templates)'), ts('Complete'), 'success');
        return CRM_Utils_System::redirect(); // exits
      }
      else {
        CRM_Core_Error::fatal('You do not have permission to execute this url');
      }
    }
  }

  /**
   * Perform general setup
   *
   * @param array $args list of path parts
   * @void
   */
  static public function init($args) {
    // first fire up IDS and check for bad stuff
    $config = CRM_Core_Config::singleton();
    if (!CRM_Core_Permission::check('skip IDS check')) {
      $ids = new CRM_Core_IDS();
      $ids->check($args);
    }

    // also initialize the i18n framework
    require_once 'CRM/Core/I18n.php';
    $i18n = CRM_Core_I18n::singleton();
  }

  /**
   * Hackish support for /standalone/*
   *
   * @param array $args list of path parts
   * @void
   */
  static public function hackStandalone($args) {
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'Standalone') {
      $session = CRM_Core_Session::singleton();
      if ($session->get('new_install') !== TRUE) {
        CRM_Core_Standalone::sidebarLeft();
      }
      elseif ($args[1] == 'standalone' && $args[2] == 'register') {
        CRM_Core_Menu::store();
      }
    }
  }

  /**
   * Determine which menu $item corresponds to $args
   *
   * @param array $args list of path parts
   * @return array; see CRM_Core_Menu
   */
  static public function getItem($args) {
    if (is_array($args)) {
      // get the menu items
      $path = implode('/', $args);
    } else {
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
   * Given a menu item, call the appropriate controller and return the response
   *
   * @param array $item see CRM_Core_Menu
   * @return string, HTML
   */
  static public function runItem($item) {
    $config = CRM_Core_Config::singleton();
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

    if ($item) {
      // CRM-7656 - make sure we send a clean sanitized path to create printer friendly url
      $printerFriendly = CRM_Utils_System::makeURL(
        'snippet', FALSE, FALSE,
        CRM_Utils_Array::value('path', $item)
      ) . '2';
      $template->assign('printerFriendly', $printerFriendly);

      if (!array_key_exists('page_callback', $item)) {
        CRM_Core_Error::debug('Bad item', $item);
        CRM_Core_Error::fatal(ts('Bad menu record in database'));
      }

      // check that we are permissioned to access this page
      if (!CRM_Core_Permission::checkMenuItem($item)) {
        CRM_Utils_System::permissionDenied();
        return;
      }

      // check if ssl is set
      if (!empty($item['is_ssl'])) {
        CRM_Utils_System::redirectToSSL();
      }

      if (isset($item['title'])) {
        CRM_Utils_System::setTitle($item['title']);
      }

      if (isset($item['breadcrumb']) && !isset($item['is_public'])) {
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
        self::versionCheck($template);
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
      if (is_array($item['page_callback'])) {
        require_once (str_replace('_', DIRECTORY_SEPARATOR, $item['page_callback'][0]) . '.php');
        $result = call_user_func($item['page_callback']);
      }
      elseif (strstr($item['page_callback'], '_Form')) {
        $wrapper = new CRM_Utils_Wrapper();
        $result = $wrapper->run(
          CRM_Utils_Array::value('page_callback', $item),
          CRM_Utils_Array::value('title', $item),
          isset($pageArgs) ? $pageArgs : NULL
        );
      }
      else {
        $newArgs = explode('/', $_GET[$config->userFrameworkURLVar]);
        require_once (str_replace('_', DIRECTORY_SEPARATOR, $item['page_callback']) . '.php');
        $mode = 'null';
        if (isset($pageArgs['mode'])) {
          $mode = $pageArgs['mode'];
          unset($pageArgs['mode']);
        }
        $title = CRM_Utils_Array::value('title', $item);
        if (strstr($item['page_callback'], '_Page')) {
          $object = new $item['page_callback'] ($title, $mode );
          $object->urlPath = explode('/', $_GET[$config->userFrameworkURLVar]);
        }
        elseif (strstr($item['page_callback'], '_Controller')) {
          $addSequence = 'false';
          if (isset($pageArgs['addSequence'])) {
            $addSequence = $pageArgs['addSequence'];
            $addSequence = $addSequence ? 'true' : 'false';
            unset($pageArgs['addSequence']);
          }
          $object = new $item['page_callback'] ($title, true, $mode, null, $addSequence );
        }
        else {
          CRM_Core_Error::fatal();
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
   * This function contains the default action
   *
   * @param $action
   *
   * @param $contact_type
   * @param $contact_sub_type
   *
   * @static
   * @access public
   */
  static function form($action, $contact_type, $contact_sub_type) {
    CRM_Utils_System::setUserContext(array('civicrm/contact/search/basic', 'civicrm/contact/view'));
    $wrapper = new CRM_Utils_Wrapper();

    $properties = CRM_Core_Component::contactSubTypeProperties($contact_sub_type, 'Edit');
    if ($properties) {
      $wrapper->run($properties['class'], ts('New %1', array(1 => $contact_sub_type)), $action, TRUE);
    }
    else {
      $wrapper->run('CRM_Contact_Form_Contact', ts('New Contact'), $action, TRUE);
    }
  }

  /**
   * Show the message about CiviCRM versions
   *
   * @param obj: $template (reference)
   */
  static function versionCheck($template) {
    if (CRM_Core_Config::isUpgradeMode()) {
      return;
    }
    $versionCheck = CRM_Utils_VersionCheck::singleton();
    $newerVersion = $versionCheck->newerVersion();
    $template->assign('newer_civicrm_version', $newerVersion);
  }

  /**
   * @param bool $triggerRebuild
   * @param bool $sessionReset
   *
   * @throws Exception
   */
  static function rebuildMenuAndCaches($triggerRebuild = FALSE, $sessionReset = FALSE) {
    $config = CRM_Core_Config::singleton();
    $config->clearModuleList();

    // also cleanup all caches
    $config->cleanupCaches($sessionReset || CRM_Utils_Request::retrieve('sessionReset', 'Boolean', CRM_Core_DAO::$_nullObject, FALSE, 0, 'GET'));

    CRM_Core_Menu::store();

    // also reset navigation
    CRM_Core_BAO_Navigation::resetNavigation();

    // also cleanup module permissions
    $config->cleanupPermissions();

    // rebuild word replacement cache - pass false to prevent operations redundant with this fn
    CRM_Core_BAO_WordReplacement::rebuild(FALSE);

    CRM_Core_BAO_Setting::updateSettingsFromMetaData();
    // Clear js caches
    CRM_Core_Resources::singleton()->flushStrings()->resetCacheCode();
    CRM_Case_XMLRepository::singleton(TRUE);

    // also rebuild triggers if requested explicitly
    if (
      $triggerRebuild ||
      CRM_Utils_Request::retrieve('triggerRebuild', 'Boolean', CRM_Core_DAO::$_nullObject, FALSE, 0, 'GET')
    ) {
      CRM_Core_DAO::triggerRebuild();
    }
    CRM_Core_DAO_AllCoreTables::reinitializeCache(TRUE);
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();
  }
}
