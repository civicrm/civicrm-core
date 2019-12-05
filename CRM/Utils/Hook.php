<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC (c) 2004-2019
 */
abstract class CRM_Utils_Hook {

  // Allowed values for dashboard hook content placement
  // Default - place content below activity list
  const DASHBOARD_BELOW = 1;
  // Place content above activity list
  const DASHBOARD_ABOVE = 2;
  // Don't display activity list at all
  const DASHBOARD_REPLACE = 3;

  // by default - place content below existing content
  const SUMMARY_BELOW = 1;
  // place hook content above
  const SUMMARY_ABOVE = 2;
  /**
   *create your own summaries
   */
  const SUMMARY_REPLACE = 3;

  /**
   * Object to pass when an object is required to be passed by params.
   *
   * This is supposed to be a convenience but note that it is a bad
   * pattern as it can get contaminated & result in hard-to-diagnose bugs.
   *
   * @var null
   */
  public static $_nullObject = NULL;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var CRM_Utils_Hook
   */
  static private $_singleton = NULL;

  /**
   * @var bool
   */
  private $commonIncluded = FALSE;

  /**
   * @var array(string)
   */
  private $commonCiviModules = [];

  /**
   * @var CRM_Utils_Cache_Interface
   */
  protected $cache;

  /**
   * Constructor and getter for the singleton instance.
   *
   * @param bool $fresh
   *
   * @return CRM_Utils_Hook
   *   An instance of $config->userHookClass
   */
  public static function singleton($fresh = FALSE) {
    if (self::$_singleton == NULL || $fresh) {
      $config = CRM_Core_Config::singleton();
      $class = $config->userHookClass;
      self::$_singleton = new $class();
    }
    return self::$_singleton;
  }

  /**
   * CRM_Utils_Hook constructor.
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct() {
    $this->cache = CRM_Utils_Cache::create([
      'name' => 'hooks',
      'type' => ['ArrayCache'],
      'prefetch' => 1,
    ]);
  }

  /**
   * Invoke a hook through the UF/CMS hook system and the extension-hook
   * system.
   *
   * @param int $numParams
   *   Number of parameters to pass to the hook.
   * @param mixed $arg1
   *   Parameter to be passed to the hook.
   * @param mixed $arg2
   *   Parameter to be passed to the hook.
   * @param mixed $arg3
   *   Parameter to be passed to the hook.
   * @param mixed $arg4
   *   Parameter to be passed to the hook.
   * @param mixed $arg5
   *   Parameter to be passed to the hook.
   * @param mixed $arg6
   *   Parameter to be passed to the hook.
   * @param string $fnSuffix
   *   Function suffix, this is effectively the hook name.
   *
   * @return mixed
   */
  abstract public function invokeViaUF(
    $numParams,
    &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6,
    $fnSuffix
  );

  /**
   * Invoke a hook.
   *
   * This is a transitional adapter. It supports the legacy syntax
   * but also accepts enough information to support Symfony Event
   * dispatching.
   *
   * @param array|int $names
   *   (Recommended) Array of parameter names, in order.
   *   Using an array is recommended because it enables full
   *   event-broadcasting behaviors.
   *   (Legacy) Number of parameters to pass to the hook.
   *   This is provided for transitional purposes.
   * @param mixed $arg1
   * @param mixed $arg2
   * @param mixed $arg3
   * @param mixed $arg4
   * @param mixed $arg5
   * @param mixed $arg6
   * @param mixed $fnSuffix
   * @return mixed
   */
  public function invoke(
    $names,
    &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6,
    $fnSuffix
  ) {
    // Per https://github.com/civicrm/civicrm-core/pull/13551 we have had ongoing significant problems where hooks from modules are
    // invoked during upgrade but not those from extensions. The issues are both that an incorrect module list & settings are cached and that
    // some hooks REALLY need to run during upgrade - the loss of triggers during upgrade causes significant loss of data
    // whereas loss of logTable hooks means that log tables are created for tables specifically excluded - e.g due to large
    // quantities of low-importance data or the table not having an id field, which could cause a fatal error.
    // Instead of not calling any hooks we only call those we know to be frequently important - if a particular extension wanted
    // to avoid this they could do an early return on CRM_Core_Config::singleton()->isUpgradeMode
    $upgradeFriendlyHooks = ['civicrm_alterSettingsFolders', 'civicrm_alterSettingsMetaData', 'civicrm_triggerInfo', 'civicrm_alterLogTables', 'civicrm_container'];
    if (CRM_Core_Config::singleton()->isUpgradeMode() && !in_array($fnSuffix, $upgradeFriendlyHooks)) {
      return;
    }
    if (is_array($names) && !defined('CIVICRM_FORCE_LEGACY_HOOK') && \Civi\Core\Container::isContainerBooted()) {
      $event = \Civi\Core\Event\GenericHookEvent::createOrdered(
        $names,
        array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6)
      );
      \Civi::dispatcher()->dispatch('hook_' . $fnSuffix, $event);
      return $event->getReturnValues();
    }
    else {
      $count = is_array($names) ? count($names) : $names;
      return $this->invokeViaUF($count, $arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $fnSuffix);
    }
  }

  /**
   * @param array $numParams
   * @param $arg1
   * @param $arg2
   * @param $arg3
   * @param $arg4
   * @param $arg5
   * @param $arg6
   * @param $fnSuffix
   * @param $fnPrefix
   *
   * @return array|bool
   */
  public function commonInvoke(
    $numParams,
    &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6,
    $fnSuffix, $fnPrefix
  ) {

    $this->commonBuildModuleList($fnPrefix);

    return $this->runHooks($this->commonCiviModules, $fnSuffix,
      $numParams, $arg1, $arg2, $arg3, $arg4, $arg5, $arg6
    );
  }

  /**
   * Build the list of modules to be processed for hooks.
   *
   * @param string $fnPrefix
   */
  public function commonBuildModuleList($fnPrefix) {
    if (!$this->commonIncluded) {
      // include external file
      $this->commonIncluded = TRUE;

      $config = CRM_Core_Config::singleton();
      if (!empty($config->customPHPPathDir)) {
        $civicrmHooksFile = CRM_Utils_File::addTrailingSlash($config->customPHPPathDir) . 'civicrmHooks.php';
        if (file_exists($civicrmHooksFile)) {
          @include_once $civicrmHooksFile;
        }
      }

      if (!empty($fnPrefix)) {
        $this->commonCiviModules[$fnPrefix] = $fnPrefix;
      }

      $this->requireCiviModules($this->commonCiviModules);
    }
  }

  /**
   * Run hooks.
   *
   * @param array $civiModules
   * @param string $fnSuffix
   * @param int $numParams
   * @param mixed $arg1
   * @param mixed $arg2
   * @param mixed $arg3
   * @param mixed $arg4
   * @param mixed $arg5
   * @param mixed $arg6
   *
   * @return array|bool
   * @throws \Exception
   */
  public function runHooks(
    $civiModules, $fnSuffix, $numParams,
    &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6
  ) {
    // $civiModules is *not* passed by reference because runHooks
    // must be reentrant. PHP is finicky about running
    // multiple loops over the same variable. The circumstances
    // to reproduce the issue are pretty intricate.
    $result = [];

    $fnNames = $this->cache->get($fnSuffix);
    if (!is_array($fnNames)) {
      $fnNames = [];
      if ($civiModules !== NULL) {
        foreach ($civiModules as $module) {
          $fnName = "{$module}_{$fnSuffix}";
          if (function_exists($fnName)) {
            $fnNames[] = $fnName;
          }
        }
        $this->cache->set($fnSuffix, $fnNames);
      }
    }

    foreach ($fnNames as $fnName) {
      $fResult = [];
      switch ($numParams) {
        case 0:
          $fResult = $fnName();
          break;

        case 1:
          $fResult = $fnName($arg1);
          break;

        case 2:
          $fResult = $fnName($arg1, $arg2);
          break;

        case 3:
          $fResult = $fnName($arg1, $arg2, $arg3);
          break;

        case 4:
          $fResult = $fnName($arg1, $arg2, $arg3, $arg4);
          break;

        case 5:
          $fResult = $fnName($arg1, $arg2, $arg3, $arg4, $arg5);
          break;

        case 6:
          $fResult = $fnName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6);
          break;

        default:
          CRM_Core_Error::fatal(ts('Invalid hook invocation'));
          break;
      }

      if (!empty($fResult) &&
        is_array($fResult)
      ) {
        $result = array_merge($result, $fResult);
      }
    }

    return empty($result) ? TRUE : $result;
  }

  /**
   * @param $moduleList
   */
  public function requireCiviModules(&$moduleList) {
    $civiModules = CRM_Core_PseudoConstant::getModuleExtensions();
    foreach ($civiModules as $civiModule) {
      if (!file_exists($civiModule['filePath'])) {
        CRM_Core_Session::setStatus(
          ts('Error loading module file (%1). Please restore the file or disable the module.',
            [1 => $civiModule['filePath']]),
          ts('Warning'), 'error');
        continue;
      }
      include_once $civiModule['filePath'];
      $moduleList[$civiModule['prefix']] = $civiModule['prefix'];
    }
  }

  /**
   * This hook is called before a db write on some core objects.
   * This hook does not allow the abort of the operation
   *
   * @param string $op
   *   The type of operation being performed.
   * @param string $objectName
   *   The name of the object.
   * @param int $id
   *   The object id if available.
   * @param array $params
   *   The parameters used for object creation / editing.
   *
   * @return null
   *   the return value is ignored
   */
  public static function pre($op, $objectName, $id, &$params) {
    $event = new \Civi\Core\Event\PreEvent($op, $objectName, $id, $params);
    \Civi::dispatcher()->dispatch('hook_civicrm_pre', $event);
    return $event->getReturnValues();
  }

  /**
   * This hook is called after a db write on some core objects.
   *
   * @param string $op
   *   The type of operation being performed.
   * @param string $objectName
   *   The name of the object.
   * @param int $objectId
   *   The unique identifier for the object.
   * @param object $objectRef
   *   The reference to the object if available.
   *
   * @return mixed
   *   based on op. pre-hooks return a boolean or
   *                           an error message which aborts the operation
   */
  public static function post($op, $objectName, $objectId, &$objectRef = NULL) {
    $event = new \Civi\Core\Event\PostEvent($op, $objectName, $objectId, $objectRef);
    \Civi::dispatcher()->dispatch('hook_civicrm_post', $event);
    return $event->getReturnValues();
  }

  /**
   * This hook retrieves links from other modules and injects it into.
   * the view contact tabs
   *
   * @param string $op
   *   The type of operation being performed.
   * @param string $objectName
   *   The name of the object.
   * @param int $objectId
   *   The unique identifier for the object.
   * @param array $links
   *   (optional) the links array (introduced in v3.2).
   * @param int $mask
   *   (optional) the bitmask to show/hide links.
   * @param array $values
   *   (optional) the values to fill the links.
   *
   * @return null
   *   the return value is ignored
   */
  public static function links($op, $objectName, &$objectId, &$links, &$mask = NULL, &$values = []) {
    return self::singleton()->invoke(['op', 'objectName', 'objectId', 'links', 'mask', 'values'], $op, $objectName, $objectId, $links, $mask, $values, 'civicrm_links');
  }

  /**
   * This hook is invoked during the CiviCRM form preProcess phase.
   *
   * @param string $formName
   *   The name of the form.
   * @param CRM_Core_Form $form
   *   Reference to the form object.
   *
   * @return null
   *   the return value is ignored
   */
  public static function preProcess($formName, &$form) {
    return self::singleton()
      ->invoke(['formName', 'form'], $formName, $form, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_preProcess');
  }

  /**
   * This hook is invoked when building a CiviCRM form. This hook should also
   * be used to set the default values of a form element
   *
   * @param string $formName
   *   The name of the form.
   * @param CRM_Core_Form $form
   *   Reference to the form object.
   *
   * @return null
   *   the return value is ignored
   */
  public static function buildForm($formName, &$form) {
    return self::singleton()->invoke(['formName', 'form'], $formName, $form,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_buildForm'
    );
  }

  /**
   * This hook is invoked when a CiviCRM form is submitted. If the module has injected
   * any form elements, this hook should save the values in the database
   *
   * @param string $formName
   *   The name of the form.
   * @param CRM_Core_Form $form
   *   Reference to the form object.
   *
   * @return null
   *   the return value is ignored
   */
  public static function postProcess($formName, &$form) {
    return self::singleton()->invoke(['formName', 'form'], $formName, $form,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_postProcess'
    );
  }

  /**
   * This hook is invoked during all CiviCRM form validation. An array of errors
   * detected is returned. Else we assume validation succeeded.
   *
   * @param string $formName
   *   The name of the form.
   * @param array &$fields the POST parameters as filtered by QF
   * @param array &$files the FILES parameters as sent in by POST
   * @param array &$form the form object
   * @param array &$errors the array of errors.
   *
   * @return mixed
   *   formRule hooks return a boolean or
   *                           an array of error messages which display a QF Error
   */
  public static function validateForm($formName, &$fields, &$files, &$form, &$errors) {
    return self::singleton()
      ->invoke(['formName', 'fields', 'files', 'form', 'errors'],
        $formName, $fields, $files, $form, $errors, self::$_nullObject, 'civicrm_validateForm');
  }

  /**
   * This hook is called after a db write on a custom table.
   *
   * @param string $op
   *   The type of operation being performed.
   * @param string $groupID
   *   The custom group ID.
   * @param object $entityID
   *   The entityID of the row in the custom table.
   * @param array $params
   *   The parameters that were sent into the calling function.
   *
   * @return null
   *   the return value is ignored
   */
  public static function custom($op, $groupID, $entityID, &$params) {
    return self::singleton()
      ->invoke(['op', 'groupID', 'entityID', 'params'], $op, $groupID, $entityID, $params, self::$_nullObject, self::$_nullObject, 'civicrm_custom');
  }

  /**
   * This hook is called when composing the ACL where clause to restrict
   * visibility of contacts to the logged in user
   *
   * @param int $type
   *   The type of permission needed.
   * @param array $tables
   *   (reference ) add the tables that are needed for the select clause.
   * @param array $whereTables
   *   (reference ) add the tables that are needed for the where clause.
   * @param int $contactID
   *   The contactID for whom the check is made.
   * @param string $where
   *   The currrent where clause.
   *
   * @return null
   *   the return value is ignored
   */
  public static function aclWhereClause($type, &$tables, &$whereTables, &$contactID, &$where) {
    return self::singleton()
      ->invoke(['type', 'tables', 'whereTables', 'contactID', 'where'], $type, $tables, $whereTables, $contactID, $where, self::$_nullObject, 'civicrm_aclWhereClause');
  }

  /**
   * This hook is called when composing the ACL where clause to restrict
   * visibility of contacts to the logged in user
   *
   * @param int $type
   *   The type of permission needed.
   * @param int $contactID
   *   The contactID for whom the check is made.
   * @param string $tableName
   *   The tableName which is being permissioned.
   * @param array $allGroups
   *   The set of all the objects for the above table.
   * @param array $currentGroups
   *   The set of objects that are currently permissioned for this contact.
   *
   * @return null
   *   the return value is ignored
   */
  public static function aclGroup($type, $contactID, $tableName, &$allGroups, &$currentGroups) {
    return self::singleton()
      ->invoke(['type', 'contactID', 'tableName', 'allGroups', 'currentGroups'], $type, $contactID, $tableName, $allGroups, $currentGroups, self::$_nullObject, 'civicrm_aclGroup');
  }

  /**
   * @param string|CRM_Core_DAO $entity
   * @param array $clauses
   * @return mixed
   */
  public static function selectWhereClause($entity, &$clauses) {
    $entityName = is_object($entity) ? _civicrm_api_get_entity_name_from_dao($entity) : $entity;
    return self::singleton()->invoke(['entity', 'clauses'], $entityName, $clauses,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_selectWhereClause'
    );
  }

  /**
   * This hook is called when building the menu table.
   *
   * @param array $files
   *   The current set of files to process.
   *
   * @return null
   *   the return value is ignored
   */
  public static function xmlMenu(&$files) {
    return self::singleton()->invoke(['files'], $files,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_xmlMenu'
    );
  }

  /**
   * (Experimental) This hook is called when build the menu table.
   *
   * @param array $items
   *   List of records to include in menu table.
   * @return null
   *   the return value is ignored
   */
  public static function alterMenu(&$items) {
    return self::singleton()->invoke(['items'], $items,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_alterMenu'
    );
  }

  /**
   * A theme is a set of CSS files which are loaded on CiviCRM pages. To register a new
   * theme, add it to the $themes array. Use these properties:
   *
   *  - ext: string (required)
   *         The full name of the extension which defines the theme.
   *         Ex: "org.civicrm.themes.greenwich".
   *  - title: string (required)
   *         Visible title.
   *  - help: string (optional)
   *         Description of the theme's appearance.
   *  - url_callback: mixed (optional)
   *         A function ($themes, $themeKey, $cssExt, $cssFile) which returns the URL(s) for a CSS resource.
   *         Returns either an array of URLs or PASSTHRU.
   *         Ex: \Civi\Core\Themes\Resolvers::simple (default)
   *         Ex: \Civi\Core\Themes\Resolvers::none
   *  - prefix: string (optional)
   *         A prefix within the extension folder to prepend to the file name.
   *  - search_order: array (optional)
   *         A list of themes to search.
   *         Generally, the last theme should be "*fallback*" (Civi\Core\Themes::FALLBACK).
   *  - excludes: array (optional)
   *         A list of files (eg "civicrm:css/bootstrap.css" or "$ext:$file") which should never
   *         be returned (they are excluded from display).
   *
   * @param array $themes
   *   List of themes, keyed by name.
   * @return null
   *   the return value is ignored
   */
  public static function themes(&$themes) {
    return self::singleton()->invoke(1, $themes,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_themes'
    );
  }

  /**
   * The activeTheme hook determines which theme is active.
   *
   * @param string $theme
   *   The identifier for the theme. Alterable.
   *   Ex: 'greenwich'.
   * @param array $context
   *   Information about the current page-request. Includes some mix of:
   *   - page: the relative path of the current Civi page (Ex: 'civicrm/dashboard').
   *   - themes: an instance of the Civi\Core\Themes service.
   * @return null
   *   the return value is ignored
   */
  public static function activeTheme(&$theme, $context) {
    return self::singleton()->invoke(array('theme', 'context'), $theme, $context,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_activeTheme'
    );
  }

  /**
   * This hook is called for declaring managed entities via API.
   *
   * @param array $entities
   *   List of pending entities. Each entity is an array with keys:
   *   + 'module': string; for module-extensions, this is the fully-qualifed name (e.g. "com.example.mymodule"); for CMS modules, the name is prefixed by the CMS (e.g. "drupal.mymodule")
   *   + 'name': string, a symbolic name which can be used to track this entity (Note: Each module creates its own namespace)
   *   + 'entity': string, an entity-type supported by the CiviCRM API (Note: this currently must be an entity which supports the 'is_active' property)
   *   + 'params': array, the entity data as supported by the CiviCRM API
   *   + 'update' (v4.5+): string, a policy which describes when to update records
   *     - 'always' (default): always update the managed-entity record; changes in $entities will override any local changes (eg by the site-admin)
   *     - 'never': never update the managed-entity record; changes made locally (eg by the site-admin) will override changes in $entities
   *   + 'cleanup' (v4.5+): string, a policy which describes whether to cleanup the record when it becomes orphaned (ie when $entities no longer references the record)
   *     - 'always' (default): always delete orphaned records
   *     - 'never': never delete orphaned records
   *     - 'unused': only delete orphaned records if there are no other references to it in the DB. (This is determined by calling the API's "getrefcount" action.)
   *
   * @return null
   *   the return value is ignored
   */
  public static function managed(&$entities) {
    return self::singleton()->invoke(['entities'], $entities,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_managed'
    );
  }

  /**
   * This hook is called when rendering the dashboard (q=civicrm/dashboard)
   *
   * @param int $contactID
   *   The contactID for whom the dashboard is being rendered.
   * @param int $contentPlacement
   *   (output parameter) where should the hook content be displayed.
   * relative to the activity list
   *
   * @return string
   *   the html snippet to include in the dashboard
   */
  public static function dashboard($contactID, &$contentPlacement = self::DASHBOARD_BELOW) {
    $retval = self::singleton()->invoke(['contactID', 'contentPlacement'], $contactID, $contentPlacement,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_dashboard'
    );

    /*
     * Note we need this seemingly unnecessary code because in the event that the implementation
     * of the hook declares the second parameter but doesn't set it, then it comes back unset even
     * though we have a default value in this function's declaration above.
     */
    if (!isset($contentPlacement)) {
      $contentPlacement = self::DASHBOARD_BELOW;
    }

    return $retval;
  }

  /**
   * This hook is called before storing recently viewed items.
   *
   * @param array $recentArray
   *   An array of recently viewed or processed items, for in place modification.
   *
   * @return array
   */
  public static function recent(&$recentArray) {
    return self::singleton()->invoke(['recentArray'], $recentArray,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_recent'
    );
  }

  /**
   * Determine how many other records refer to a given record.
   *
   * @param CRM_Core_DAO $dao
   *   The item for which we want a reference count.
   * @param array $refCounts
   *   Each item in the array is an Array with keys:
   *   - name: string, eg "sql:civicrm_email:contact_id"
   *   - type: string, eg "sql"
   *   - count: int, eg "5" if there are 5 email addresses that refer to $dao
   *
   * @return mixed
   *   Return is not really intended to be used.
   */
  public static function referenceCounts($dao, &$refCounts) {
    return self::singleton()->invoke(['dao', 'refCounts'], $dao, $refCounts,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_referenceCounts'
    );
  }

  /**
   * This hook is called when building the amount structure for a Contribution or Event Page.
   *
   * @param int $pageType
   *   Is this a contribution or event page.
   * @param CRM_Core_Form $form
   *   Reference to the form object.
   * @param array $amount
   *   The amount structure to be displayed.
   *
   * @return null
   */
  public static function buildAmount($pageType, &$form, &$amount) {
    return self::singleton()->invoke(['pageType', 'form', 'amount'], $pageType, $form, $amount, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, 'civicrm_buildAmount');
  }

  /**
   * This hook is called when building the state list for a particular country.
   *
   * @param array $countryID
   *   The country id whose states are being selected.
   * @param $states
   *
   * @return null
   */
  public static function buildStateProvinceForCountry($countryID, &$states) {
    return self::singleton()->invoke(['countryID', 'states'], $countryID, $states,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_buildStateProvinceForCountry'
    );
  }

  /**
   * This hook is called when rendering the tabs for a contact (q=civicrm/contact/view)c
   *
   * @param array $tabs
   *   The array of tabs that will be displayed.
   * @param int $contactID
   *   The contactID for whom the dashboard is being rendered.
   *
   * @return null
   * @deprecated Use tabset() instead.
   */
  public static function tabs(&$tabs, $contactID) {
    return self::singleton()->invoke(['tabs', 'contactID'], $tabs, $contactID,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_tabs'
    );
  }

  /**
   * This hook is called when rendering the tabs used for events and potentially
   * contribution pages, etc.
   *
   * @param string $tabsetName
   *   Name of the screen or visual element.
   * @param array $tabs
   *   Tabs that will be displayed.
   * @param array $context
   *   Extra data about the screen or context in which the tab is used.
   *
   * @return null
   */
  public static function tabset($tabsetName, &$tabs, $context) {
    return self::singleton()->invoke(['tabsetName', 'tabs', 'context'], $tabsetName, $tabs,
      $context, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_tabset'
    );
  }

  /**
   * This hook is called when sending an email / printing labels
   *
   * @param array $tokens
   *   The list of tokens that can be used for the contact.
   *
   * @return null
   */
  public static function tokens(&$tokens) {
    return self::singleton()->invoke(['tokens'], $tokens,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_tokens'
    );
  }

  /**
   * This hook allows modification of the admin panels
   *
   * @param array $panels
   *   Associated array of admin panels
   *
   * @return mixed
   */
  public static function alterAdminPanel(&$panels) {
    return self::singleton()->invoke(array('panels'), $panels,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_alterAdminPanel'
    );
  }

  /**
   * This hook is called when sending an email / printing labels to get the values for all the
   * tokens returned by the 'tokens' hook
   *
   * @param array $details
   *   The array to store the token values indexed by contactIDs (unless it a single).
   * @param array $contactIDs
   *   An array of contactIDs.
   * @param int $jobID
   *   The jobID if this is associated with a CiviMail mailing.
   * @param array $tokens
   *   The list of tokens associated with the content.
   * @param string $className
   *   The top level className from where the hook is invoked.
   *
   * @return null
   */
  public static function tokenValues(
    &$details,
    $contactIDs,
    $jobID = NULL,
    $tokens = [],
    $className = NULL
  ) {
    return self::singleton()
      ->invoke(['details', 'contactIDs', 'jobID', 'tokens', 'className'], $details, $contactIDs, $jobID, $tokens, $className, self::$_nullObject, 'civicrm_tokenValues');
  }

  /**
   * This hook is called before a CiviCRM Page is rendered. You can use this hook to insert smarty variables
   * in a  template
   *
   * @param object $page
   *   The page that will be rendered.
   *
   * @return null
   */
  public static function pageRun(&$page) {
    return self::singleton()->invoke(['page'], $page,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_pageRun'
    );
  }

  /**
   * This hook is called after a copy of an object has been made. The current objects are
   * Event, Contribution Page and UFGroup
   *
   * @param string $objectName
   *   Name of the object.
   * @param object $object
   *   Reference to the copy.
   *
   * @return null
   */
  public static function copy($objectName, &$object) {
    return self::singleton()->invoke(['objectName', 'object'], $objectName, $object,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_copy'
    );
  }

  /**
   * This hook is called when a contact unsubscribes from a mailing.  It allows modules
   * to override what the contacts are removed from.
   *
   * @param string $op
   *   Ignored for now
   * @param int $mailingId
   *   The id of the mailing to unsub from
   * @param int $contactId
   *   The id of the contact who is unsubscribing
   * @param array|int $groups
   *   Groups the contact will be removed from.
   * @param array|int $baseGroups
   *   Base groups (used in smart mailings) the contact will be removed from.
   *
   *
   * @return mixed
   */
  public static function unsubscribeGroups($op, $mailingId, $contactId, &$groups, &$baseGroups) {
    return self::singleton()
      ->invoke(['op', 'mailingId', 'contactId', 'groups', 'baseGroups'], $op, $mailingId, $contactId, $groups, $baseGroups, self::$_nullObject, 'civicrm_unsubscribeGroups');
  }

  /**
   * This hook is called when CiviCRM needs to edit/display a custom field with options
   *
   * @deprecated in favor of hook_civicrm_fieldOptions
   *
   * @param int $customFieldID
   *   The custom field ID.
   * @param array $options
   *   The current set of options for that custom field.
   *   You can add/remove existing options.
   *   Important: This array may contain meta-data about the field that is needed elsewhere, so it is important
   *              to be careful to not overwrite the array.
   *   Only add/edit/remove the specific field options you intend to affect.
   * @param bool $detailedFormat
   *   If true, the options are in an ID => array ( 'id' => ID, 'label' => label, 'value' => value ) format
   * @param array $selectAttributes
   *   Contain select attribute(s) if any.
   *
   * @return mixed
   */
  public static function customFieldOptions($customFieldID, &$options, $detailedFormat = FALSE, $selectAttributes = []) {
    // Weird: $selectAttributes is inputted but not outputted.
    return self::singleton()->invoke(['customFieldID', 'options', 'detailedFormat'], $customFieldID, $options, $detailedFormat,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_customFieldOptions'
    );
  }

  /**
   * Hook for modifying field options
   *
   * @param string $entity
   * @param string $field
   * @param array $options
   * @param array $params
   *
   * @return mixed
   */
  public static function fieldOptions($entity, $field, &$options, $params) {
    return self::singleton()->invoke(['entity', 'field', 'options', 'params'], $entity, $field, $options, $params,
      self::$_nullObject, self::$_nullObject,
      'civicrm_fieldOptions'
    );
  }

  /**
   *
   * This hook is called to display the list of actions allowed after doing a search.
   * This allows the module developer to inject additional actions or to remove existing actions.
   *
   * @param string $objectType
   *   The object type for this search.
   *   - activity, campaign, case, contact, contribution, event, grant, membership, and pledge are supported.
   * @param array $tasks
   *   The current set of tasks for that custom field.
   *   You can add/remove existing tasks.
   *   Each task needs to have a title (eg 'title'  => ts( 'Group - add contacts')) and a class
   *   (eg 'class'  => 'CRM_Contact_Form_Task_AddToGroup').
   *   Optional result (boolean) may also be provided. Class can be an array of classes (not sure what that does :( ).
   *   The key for new Task(s) should not conflict with the keys for core tasks of that $objectType, which can be
   *   found in CRM/$objectType/Task.php.
   *
   * @return mixed
   */
  public static function searchTasks($objectType, &$tasks) {
    return self::singleton()->invoke(['objectType', 'tasks'], $objectType, $tasks,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_searchTasks'
    );
  }

  /**
   * @param mixed $form
   * @param array $params
   *
   * @return mixed
   */
  public static function eventDiscount(&$form, &$params) {
    return self::singleton()->invoke(['form', 'params'], $form, $params,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_eventDiscount'
    );
  }

  /**
   * This hook is called when composing a mailing. You can include / exclude other groups as needed.
   *
   * @param mixed $form
   *   The form object for which groups / mailings being displayed
   * @param array $groups
   *   The list of groups being included / excluded
   * @param array $mailings
   *   The list of mailings being included / excluded
   *
   * @return mixed
   */
  public static function mailingGroups(&$form, &$groups, &$mailings) {
    return self::singleton()->invoke(['form', 'groups', 'mailings'], $form, $groups, $mailings,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_mailingGroups'
    );
  }

  /**
   * (Experimental) Modify the list of template-types used for CiviMail composition.
   *
   * @param array $types
   *   Sequentially indexed list of template types. Each type specifies:
   *     - name: string
   *     - editorUrl: string, Angular template URL
   *     - weight: int, priority when picking a default value for new mailings
   * @return mixed
   */
  public static function mailingTemplateTypes(&$types) {
    return self::singleton()->invoke(['types'], $types, self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_mailingTemplateTypes'
    );
  }

  /**
   * This hook is called when composing the array of membershipTypes and their cost during a membership registration
   * (new or renewal).
   * Note the hook is called on initial page load and also reloaded after submit (PRG pattern).
   * You can use it to alter the membership types when first loaded, or after submission
   * (for example if you want to gather data in the form and use it to alter the fees).
   *
   * @param mixed $form
   *   The form object that is presenting the page
   * @param array $membershipTypes
   *   The array of membership types and their amount
   *
   * @return mixed
   */
  public static function membershipTypeValues(&$form, &$membershipTypes) {
    return self::singleton()->invoke(['form', 'membershipTypes'], $form, $membershipTypes,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_membershipTypeValues'
    );
  }

  /**
   * This hook is called when rendering the contact summary.
   *
   * @param int $contactID
   *   The contactID for whom the summary is being rendered
   * @param mixed $content
   * @param int $contentPlacement
   *   Specifies where the hook content should be displayed relative to the
   *   existing content
   *
   * @return string
   *   The html snippet to include in the contact summary
   */
  public static function summary($contactID, &$content, &$contentPlacement = self::SUMMARY_BELOW) {
    return self::singleton()->invoke(['contactID', 'content', 'contentPlacement'], $contactID, $content, $contentPlacement,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_summary'
    );
  }

  /**
   * Use this hook to populate the list of contacts returned by Contact Reference custom fields.
   * By default, Contact Reference fields will search on and return all CiviCRM contacts.
   * If you want to limit the contacts returned to a specific group, or some other criteria
   * - you can override that behavior by providing a SQL query that returns some subset of your contacts.
   * The hook is called when the query is executed to get the list of contacts to display.
   *
   * @param mixed $query
   *   - the query that will be executed (input and output parameter);.
   *   It's important to realize that the ACL clause is built prior to this hook being fired,
   *   so your query will ignore any ACL rules that may be defined.
   *   Your query must return two columns:
   *     the contact 'data' to display in the autocomplete dropdown (usually contact.sort_name - aliased as 'data')
   *     the contact IDs
   * @param string $queryText
   *   The name string to execute the query against (this is the value being typed in by the user).
   * @param string $context
   *   The context in which this ajax call is being made (for example: 'customfield', 'caseview').
   * @param int $id
   *   The id of the object for which the call is being made.
   *   For custom fields, it will be the custom field id
   *
   * @return mixed
   */
  public static function contactListQuery(&$query, $queryText, $context, $id) {
    return self::singleton()->invoke(['query', 'queryText', 'context', 'id'], $query, $queryText, $context, $id,
      self::$_nullObject, self::$_nullObject,
      'civicrm_contactListQuery'
    );
  }

  /**
   * Hook definition for altering payment parameters before talking to a payment processor back end.
   *
   * Definition will look like this:
   *
   *   function hook_civicrm_alterPaymentProcessorParams(
   *     $paymentObj,
   *     &$rawParams,
   *     &$cookedParams
   *   );
   *
   * @param CRM_Core_Payment $paymentObj
   *   Instance of payment class of the payment processor invoked (e.g., 'CRM_Core_Payment_Dummy')
   *   See discussion in CRM-16224 as to whether $paymentObj should be passed by reference.
   * @param array &$rawParams
   *    array of params as passed to to the processor
   * @param array &$cookedParams
   *     params after the processor code has translated them into its own key/value pairs
   *
   * @return mixed
   *   This return is not really intended to be used.
   */
  public static function alterPaymentProcessorParams(
    $paymentObj,
    &$rawParams,
    &$cookedParams
  ) {
    return self::singleton()->invoke(['paymentObj', 'rawParams', 'cookedParams'], $paymentObj, $rawParams, $cookedParams,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_alterPaymentProcessorParams'
    );
  }

  /**
   * This hook is called when an email is about to be sent by CiviCRM.
   *
   * @param array $params
   *   Array fields include: groupName, from, toName, toEmail, subject, cc, bcc, text, html,
   *   returnPath, replyTo, headers, attachments (array)
   * @param string $context
   *   The context in which the hook is being invoked, eg 'civimail'.
   *
   * @return mixed
   */
  public static function alterMailParams(&$params, $context = NULL) {
    return self::singleton()->invoke(['params', 'context'], $params, $context,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_alterMailParams'
    );
  }

  /**
   * This hook is called when membership status is being calculated.
   *
   * @param array $membershipStatus
   *   Membership status details as determined - alter if required.
   * @param array $arguments
   *   Arguments passed in to calculate date.
   * - 'start_date'
   * - 'end_date'
   * - 'status_date'
   * - 'join_date'
   * - 'exclude_is_admin'
   * - 'membership_type_id'
   * @param array $membership
   *   Membership details from the calling function.
   *
   * @return mixed
   */
  public static function alterCalculatedMembershipStatus(&$membershipStatus, $arguments, $membership) {
    return self::singleton()->invoke(['membershipStatus', 'arguments', 'membership'], $membershipStatus, $arguments,
      $membership, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_alterCalculatedMembershipStatus'
    );
  }

  /**
   * This hook is called after getting the content of the mail and before tokenizing it.
   *
   * @param array $content
   *   Array fields include: html, text, subject
   *
   * @return mixed
   */
  public static function alterMailContent(&$content) {
    return self::singleton()->invoke(['content'], $content,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_alterMailContent'
    );
  }

  /**
   * This hook is called when rendering the Manage Case screen.
   *
   * @param int $caseID
   *   The case ID.
   *
   * @return array
   *   Array of data to be displayed, where the key is a unique id to be used for styling (div id's)
   *   and the value is an array with keys 'label' and 'value' specifying label/value pairs
   */
  public static function caseSummary($caseID) {
    return self::singleton()->invoke(['caseID'], $caseID,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_caseSummary'
    );
  }

  /**
   * This hook is called when locating CiviCase types.
   *
   * @param array $caseTypes
   *
   * @return mixed
   */
  public static function caseTypes(&$caseTypes) {
    return self::singleton()
      ->invoke(['caseTypes'], $caseTypes, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_caseTypes');
  }

  /**
   * This hook is called soon after the CRM_Core_Config object has ben initialized.
   * You can use this hook to modify the config object and hence behavior of CiviCRM dynamically.
   *
   * @param CRM_Core_Config|array $config
   *   The config object
   *
   * @return mixed
   */
  public static function config(&$config) {
    return self::singleton()->invoke(['config'], $config,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_config'
    );
  }

  /**
   * This hooks allows to change option values.
   *
   * @deprecated in favor of hook_civicrm_fieldOptions
   *
   * @param array $options
   *   Associated array of option values / id
   * @param string $groupName
   *   Option group name
   *
   * @return mixed
   */
  public static function optionValues(&$options, $groupName) {
    return self::singleton()->invoke(['options', 'groupName'], $options, $groupName,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_optionValues'
    );
  }

  /**
   * This hook allows modification of the navigation menu.
   *
   * @param array $params
   *   Associated array of navigation menu entry to Modify/Add
   *
   * @return mixed
   */
  public static function navigationMenu(&$params) {
    return self::singleton()->invoke(['params'], $params,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_navigationMenu'
    );
  }

  /**
   * This hook allows modification of the data used to perform merging of duplicates.
   *
   * @param string $type
   *   The type of data being passed (cidRefs|eidRefs|relTables|sqls).
   * @param array $data
   *   The data, as described in $type.
   * @param int $mainId
   *   Contact_id of the contact that survives the merge.
   * @param int $otherId
   *   Contact_id of the contact that will be absorbed and deleted.
   * @param array $tables
   *   When $type is "sqls", an array of tables as it may have been handed to the calling function.
   *
   * @return mixed
   */
  public static function merge($type, &$data, $mainId = NULL, $otherId = NULL, $tables = NULL) {
    return self::singleton()->invoke(['type', 'data', 'mainId', 'otherId', 'tables'], $type, $data, $mainId, $otherId, $tables, self::$_nullObject, 'civicrm_merge');
  }

  /**
   * This hook allows modification of the data calculated for merging locations.
   *
   * @param array $blocksDAO
   *   Array of location DAO to be saved. These are arrays in 2 keys 'update' & 'delete'.
   * @param int $mainId
   *   Contact_id of the contact that survives the merge.
   * @param int $otherId
   *   Contact_id of the contact that will be absorbed and deleted.
   * @param array $migrationInfo
   *   Calculated migration info, informational only.
   *
   * @return mixed
   */
  public static function alterLocationMergeData(&$blocksDAO, $mainId, $otherId, $migrationInfo) {
    return self::singleton()->invoke(['blocksDAO', 'mainId', 'otherId', 'migrationInfo'], $blocksDAO, $mainId, $otherId, $migrationInfo, self::$_nullObject, self::$_nullObject, 'civicrm_alterLocationMergeData');
  }

  /**
   * This hook provides a way to override the default privacy behavior for notes.
   *
   * @param array &$noteValues
   *   Associative array of values for this note
   *
   * @return mixed
   */
  public static function notePrivacy(&$noteValues) {
    return self::singleton()->invoke(['noteValues'], $noteValues,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_notePrivacy'
    );
  }

  /**
   * This hook is called before record is exported as CSV.
   *
   * @param string $exportTempTable
   *   Name of the temporary export table used during export.
   * @param array $headerRows
   *   Header rows for output.
   * @param array $sqlColumns
   *   SQL columns.
   * @param int $exportMode
   *   Export mode ( contact, contribution, etc...).
   * @param string $componentTable
   *   Name of temporary table
   * @param array $ids
   *   Array of object's ids
   *
   * @return mixed
   */
  public static function export(&$exportTempTable, &$headerRows, &$sqlColumns, $exportMode, $componentTable, $ids) {
    return self::singleton()->invoke(['exportTempTable', 'headerRows', 'sqlColumns', 'exportMode', 'componentTable', 'ids'],
      $exportTempTable, $headerRows, $sqlColumns,
      $exportMode, $componentTable, $ids,
      'civicrm_export'
    );
  }

  /**
   * This hook allows modification of the queries constructed from dupe rules.
   *
   * @param string $obj
   *   Object of rulegroup class.
   * @param string $type
   *   Type of queries e.g table / threshold.
   * @param array $query
   *   Set of queries.
   *
   * @return mixed
   */
  public static function dupeQuery($obj, $type, &$query) {
    return self::singleton()->invoke(['obj', 'type', 'query'], $obj, $type, $query,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_dupeQuery'
    );
  }

  /**
   * Check for duplicate contacts
   *
   * @param array $dedupeParams
   *   Array of params for finding duplicates: [
   *    '{parameters returned by CRM_Dedupe_Finder::formatParams}
   *    'check_permission' => TRUE/FALSE;
   *    'contact_type' => $contactType;
   *    'rule' = $rule;
   *    'rule_group_id' => $ruleGroupID;
   *    'excludedContactIDs' => $excludedContactIDs;
   * @param array $dedupeResults
   *   Array of results ['handled' => TRUE/FALSE, 'ids' => array of IDs of duplicate contacts]
   * @param array $contextParams
   *   The context if relevant, eg. ['event_id' => X]
   *
   * @return mixed
   */
  public static function findDuplicates($dedupeParams, &$dedupeResults, $contextParams) {
    return self::singleton()
      ->invoke(['dedupeParams', 'dedupeResults', 'contextParams'], $dedupeParams, $dedupeResults, $contextParams, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_findDuplicates');
  }

  /**
   * This hook is called AFTER EACH email has been processed by the script bin/EmailProcessor.php
   *
   * @param string $type
   *   Type of mail processed: 'activity' OR 'mailing'.
   * @param array &$params the params that were sent to the CiviCRM API function
   * @param object $mail
   *   The mail object which is an ezcMail class.
   * @param array &$result the result returned by the api call
   * @param string $action
   *   (optional ) the requested action to be performed if the types was 'mailing'.
   *
   * @return mixed
   */
  public static function emailProcessor($type, &$params, $mail, &$result, $action = NULL) {
    return self::singleton()
      ->invoke(['type', 'params', 'mail', 'result', 'action'], $type, $params, $mail, $result, $action, self::$_nullObject, 'civicrm_emailProcessor');
  }

  /**
   * This hook is called after a row has been processed and the
   * record (and associated records imported
   *
   * @param string $object
   *   Object being imported (for now Contact only, later Contribution, Activity,.
   *                               Participant and Member)
   * @param string $usage
   *   Hook usage/location (for now process only, later mapping and others).
   * @param string $objectRef
   *   Import record object.
   * @param array $params
   *   Array with various key values: currently.
   *                  contactID       - contact id
   *                  importID        - row id in temp table
   *                  importTempTable - name of tempTable
   *                  fieldHeaders    - field headers
   *                  fields          - import fields
   *
   * @return mixed
   */
  public static function import($object, $usage, &$objectRef, &$params) {
    return self::singleton()->invoke(['object', 'usage', 'objectRef', 'params'], $object, $usage, $objectRef, $params,
      self::$_nullObject, self::$_nullObject,
      'civicrm_import'
    );
  }

  /**
   * This hook is called when API permissions are checked (cf. civicrm_api3_api_check_permission()
   * in api/v3/utils.php and _civicrm_api3_permissions() in CRM/Core/DAO/permissions.php).
   *
   * @param string $entity
   *   The API entity (like contact).
   * @param string $action
   *   The API action (like get).
   * @param array &$params the API parameters
   * @param array &$permissions the associative permissions array (probably to be altered by this hook)
   *
   * @return mixed
   */
  public static function alterAPIPermissions($entity, $action, &$params, &$permissions) {
    return self::singleton()->invoke(['entity', 'action', 'params', 'permissions'], $entity, $action, $params, $permissions,
      self::$_nullObject, self::$_nullObject,
      'civicrm_alterAPIPermissions'
    );
  }

  /**
   * @param CRM_Core_DAO $dao
   *
   * @return mixed
   */
  public static function postSave(&$dao) {
    $hookName = 'civicrm_postSave_' . $dao->getTableName();
    return self::singleton()->invoke(['dao'], $dao,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      $hookName
    );
  }

  /**
   * This hook allows user to customize context menu Actions on contact summary page.
   *
   * @param array $actions
   *   Array of all Actions in contextmenu.
   * @param int $contactID
   *   ContactID for the summary page.
   *
   * @return mixed
   */
  public static function summaryActions(&$actions, $contactID = NULL) {
    return self::singleton()->invoke(['actions', 'contactID'], $actions, $contactID,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_summaryActions'
    );
  }

  /**
   * This hook is called from CRM_Core_Selector_Controller through which all searches in civicrm go.
   * This enables us hook implementors to modify both the headers and the rows
   *
   * The BIGGEST drawback with this hook is that you may need to modify the result template to include your
   * fields. The result files are CRM/{Contact,Contribute,Member,Event...}/Form/Selector.tpl
   *
   * However, if you use the same number of columns, you can overwrite the existing columns with the values that
   * you want displayed. This is a hackish, but avoids template modification.
   *
   * @param string $objectName
   *   The component name that we are doing the search.
   *                           activity, campaign, case, contact, contribution, event, grant, membership, and pledge
   * @param array &$headers the list of column headers, an associative array with keys: ( name, sort, order )
   * @param array &$rows the list of values, an associate array with fields that are displayed for that component
   * @param array $selector
   *   the selector object. Allows you access to the context of the search
   *
   * @return mixed
   *   modify the header and values object to pass the data you need
   */
  public static function searchColumns($objectName, &$headers, &$rows, &$selector) {
    return self::singleton()->invoke(['objectName', 'headers', 'rows', 'selector'], $objectName, $headers, $rows, $selector,
      self::$_nullObject, self::$_nullObject,
      'civicrm_searchColumns'
    );
  }

  /**
   * This hook is called when uf groups are being built for a module.
   *
   * @param string $moduleName
   *   Module name.
   * @param array $ufGroups
   *   Array of ufgroups for a module.
   *
   * @return null
   */
  public static function buildUFGroupsForModule($moduleName, &$ufGroups) {
    return self::singleton()->invoke(['moduleName', 'ufGroups'], $moduleName, $ufGroups,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_buildUFGroupsForModule'
    );
  }

  /**
   * This hook is called when we are determining the contactID for a specific
   * email address
   *
   * @param string $email
   *   The email address.
   * @param int $contactID
   *   The contactID that matches this email address, IF it exists.
   * @param array $result
   *   (reference) has two fields.
   *                          contactID - the new (or same) contactID
   *                          action - 3 possible values:
   *                          CRM_Utils_Mail_Incoming::EMAILPROCESSOR_CREATE_INDIVIDUAL - create a new contact record
   *                          CRM_Utils_Mail_Incoming::EMAILPROCESSOR_OVERRIDE - use the new contactID
   *                          CRM_Utils_Mail_Incoming::EMAILPROCESSOR_IGNORE   - skip this email address
   *
   * @return null
   */
  public static function emailProcessorContact($email, $contactID, &$result) {
    return self::singleton()->invoke(['email', 'contactID', 'result'], $email, $contactID, $result,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_emailProcessorContact'
    );
  }

  /**
   * Hook definition for altering the generation of Mailing Labels.
   *
   * @param array $args
   *   An array of the args in the order defined for the tcpdf multiCell api call.
   *                    with the variable names below converted into string keys (ie $w become 'w'
   *                    as the first key for $args)
   *   float $w Width of cells. If 0, they extend up to the right margin of the page.
   *   float $h Cell minimum height. The cell extends automatically if needed.
   *   string $txt String to print
   *   mixed $border Indicates if borders must be drawn around the cell block. The value can
   *                 be either a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul>or
   *                 a string containing some or all of the following characters (in any order):
   *                 <ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul>
   *   string $align Allows to center or align the text. Possible values are:<ul><li>L or empty string:
   *                 left align</li><li>C: center</li><li>R: right align</li><li>J: justification
   *                 (default value when $ishtml=false)</li></ul>
   *   int $fill Indicates if the cell background must be painted (1) or transparent (0). Default value: 0.
   *   int $ln Indicates where the current position should go after the call. Possible values are:<ul><li>0:
   *           to the right</li><li>1: to the beginning of the next line [DEFAULT]</li><li>2: below</li></ul>
   *   float $x x position in user units
   *   float $y y position in user units
   *   boolean $reseth if true reset the last cell height (default true).
   *   int $stretch stretch character mode: <ul><li>0 = disabled</li><li>1 = horizontal scaling only if
   *                necessary</li><li>2 = forced horizontal scaling</li><li>3 = character spacing only if
   *                necessary</li><li>4 = forced character spacing</li></ul>
   *   boolean $ishtml set to true if $txt is HTML content (default = false).
   *   boolean $autopadding if true, uses internal padding and automatically adjust it to account for line width.
   *   float $maxh maximum height. It should be >= $h and less then remaining space to the bottom of the page,
   *               or 0 for disable this feature. This feature works only when $ishtml=false.
   *
   * @return mixed
   */
  public static function alterMailingLabelParams(&$args) {
    return self::singleton()->invoke(['args'], $args,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_alterMailingLabelParams'
    );
  }

  /**
   * This hooks allows alteration of generated page content.
   *
   * @param $content
   *   Previously generated content.
   * @param $context
   *   Context of content - page or form.
   * @param $tplName
   *   The file name of the tpl.
   * @param $object
   *   A reference to the page or form object.
   *
   * @return mixed
   */
  public static function alterContent(&$content, $context, $tplName, &$object) {
    return self::singleton()->invoke(['content', 'context', 'tplName', 'object'], $content, $context, $tplName, $object,
      self::$_nullObject, self::$_nullObject,
      'civicrm_alterContent'
    );
  }

  /**
   * This hooks allows alteration of the tpl file used to generate content. It differs from the
   * altercontent hook as the content has already been rendered through the tpl at that point
   *
   * @param $formName
   *   Previously generated content.
   * @param $form
   *   Reference to the form object.
   * @param $context
   *   Context of content - page or form.
   * @param $tplName
   *   Reference the file name of the tpl.
   *
   * @return mixed
   */
  public static function alterTemplateFile($formName, &$form, $context, &$tplName) {
    return self::singleton()->invoke(['formName', 'form', 'context', 'tplName'], $formName, $form, $context, $tplName,
      self::$_nullObject, self::$_nullObject,
      'civicrm_alterTemplateFile'
    );
  }

  /**
   * This hook collects the trigger definition from all components.
   *
   * @param $info
   * @param string $tableName
   *   (optional) the name of the table that we are interested in only.
   *
   * @internal param \reference $triggerInfo to an array of trigger information
   *   each element has 4 fields:
   *     table - array of tableName
   *     when  - BEFORE or AFTER
   *     event - array of eventName - INSERT OR UPDATE OR DELETE
   *     sql   - array of statements optionally terminated with a ;
   *             a statement can use the tokes {tableName} and {eventName}
   *             to do token replacement with the table / event. This allows
   *             templatizing logging and other hooks
   * @return mixed
   */
  public static function triggerInfo(&$info, $tableName = NULL) {
    return self::singleton()->invoke(['info', 'tableName'], $info, $tableName,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      self::$_nullObject,
      'civicrm_triggerInfo'
    );
  }

  /**
   * This hook allows changes to the spec of which tables to log.
   *
   * @param array $logTableSpec
   *
   * @return mixed
   */
  public static function alterLogTables(&$logTableSpec) {
    return self::singleton()->invoke(['logTableSpec'], $logTableSpec, $_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      self::$_nullObject,
      'civicrm_alterLogTables'
    );
  }

  /**
   * This hook is called when a module-extension is installed.
   * Each module will receive hook_civicrm_install during its own installation (but not during the
   * installation of unrelated modules).
   */
  public static function install() {
    return self::singleton()->invoke(0, self::$_nullObject,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_install'
    );
  }

  /**
   * This hook is called when a module-extension is uninstalled.
   * Each module will receive hook_civicrm_uninstall during its own uninstallation (but not during the
   * uninstallation of unrelated modules).
   */
  public static function uninstall() {
    return self::singleton()->invoke(0, self::$_nullObject,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_uninstall'
    );
  }

  /**
   * This hook is called when a module-extension is re-enabled.
   * Each module will receive hook_civicrm_enable during its own re-enablement (but not during the
   * re-enablement of unrelated modules).
   */
  public static function enable() {
    return self::singleton()->invoke(0, self::$_nullObject,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_enable'
    );
  }

  /**
   * This hook is called when a module-extension is disabled.
   * Each module will receive hook_civicrm_disable during its own disablement (but not during the
   * disablement of unrelated modules).
   */
  public static function disable() {
    return self::singleton()->invoke(0, self::$_nullObject,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_disable'
    );
  }

  /**
   * Alter redirect.
   *
   * This hook is called when the browser is being re-directed and allows the url
   * to be altered.
   *
   * @param \Psr\Http\Message\UriInterface $url
   * @param array $context
   *   Additional information about context
   *   - output - if this is 'json' then it will return json.
   *
   * @return null
   *   the return value is ignored
   */
  public static function alterRedirect(&$url, &$context) {
    return self::singleton()->invoke(['url', 'context'], $url,
      $context, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_alterRedirect'
    );
  }

  /**
   * @param $varType
   * @param $var
   * @param $object
   *
   * @return mixed
   */
  public static function alterReportVar($varType, &$var, &$object) {
    return self::singleton()->invoke(['varType', 'var', 'object'], $varType, $var, $object,
      self::$_nullObject,
      self::$_nullObject, self::$_nullObject,
      'civicrm_alterReportVar'
    );
  }

  /**
   * This hook is called to drive database upgrades for extension-modules.
   *
   * @param string $op
   *   The type of operation being performed; 'check' or 'enqueue'.
   * @param CRM_Queue_Queue $queue
   *   (for 'enqueue') the modifiable list of pending up upgrade tasks.
   *
   * @return bool|null
   *   NULL, if $op is 'enqueue'.
   *   TRUE, if $op is 'check' and upgrades are pending.
   *   FALSE, if $op is 'check' and upgrades are not pending.
   */
  public static function upgrade($op, CRM_Queue_Queue $queue = NULL) {
    return self::singleton()->invoke(['op', 'queue'], $op, $queue,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      self::$_nullObject,
      'civicrm_upgrade'
    );
  }

  /**
   * This hook is called when an email has been successfully sent by CiviCRM, but not on an error.
   *
   * @param array $params
   *   The mailing parameters. Array fields include: groupName, from, toName,
   *   toEmail, subject, cc, bcc, text, html, returnPath, replyTo, headers,
   *   attachments (array)
   *
   * @return mixed
   */
  public static function postEmailSend(&$params) {
    return self::singleton()->invoke(['params'], $params,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_postEmailSend'
    );
  }

  /**
   * This hook is called when a CiviMail mailing has completed
   *
   * @param int $mailingId
   *   Mailing ID
   *
   * @return mixed
   */
  public static function postMailing($mailingId) {
    return self::singleton()->invoke(['mailingId'], $mailingId,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_postMailing'
    );
  }

  /**
   * This hook is called when Settings specifications are loaded.
   *
   * @param array $settingsFolders
   *   List of paths from which to derive metadata
   *
   * @return mixed
   */
  public static function alterSettingsFolders(&$settingsFolders) {
    return self::singleton()->invoke(['settingsFolders'], $settingsFolders,
      self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_alterSettingsFolders'
    );
  }

  /**
   * This hook is called when Settings have been loaded from the xml
   * It is an opportunity for hooks to alter the data
   *
   * @param array $settingsMetaData
   *   Settings Metadata.
   * @param int $domainID
   * @param mixed $profile
   *
   * @return mixed
   */
  public static function alterSettingsMetaData(&$settingsMetaData, $domainID, $profile) {
    return self::singleton()->invoke(['settingsMetaData', 'domainID', 'profile'], $settingsMetaData,
      $domainID, $profile,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_alterSettingsMetaData'
    );
  }

  /**
   * This hook is called before running an api call.
   *
   * @param API_Wrapper[] $wrappers
   *   (see CRM_Utils_API_ReloadOption as an example)
   * @param mixed $apiRequest
   *
   * @return null
   *   The return value is ignored
   */
  public static function apiWrappers(&$wrappers, $apiRequest) {
    return self::singleton()
      ->invoke(['wrappers', 'apiRequest'], $wrappers, $apiRequest, self::$_nullObject, self::$_nullObject, self::$_nullObject,
        self::$_nullObject, 'civicrm_apiWrappers'
      );
  }

  /**
   * This hook is called before running pending cron jobs.
   *
   * @param CRM_Core_JobManager $jobManager
   *
   * @return null
   *   The return value is ignored.
   */
  public static function cron($jobManager) {
    return self::singleton()->invoke(['jobManager'],
      $jobManager, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_cron'
    );
  }

  /**
   * This hook is called when loading CMS permissions; use this hook to modify
   * the array of system permissions for CiviCRM.
   *
   * @param array $permissions
   *   Array of permissions. See CRM_Core_Permission::getCorePermissions() for
   *   the format of this array.
   *
   * @return null
   *   The return value is ignored
   */
  public static function permission(&$permissions) {
    return self::singleton()->invoke(['permissions'], $permissions,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_permission'
    );
  }

  /**
   * This hook is called when checking permissions; use this hook to dynamically
   * escalate user permissions in certain use cases (cf. CRM-19256).
   *
   * @param string $permission
   *   The name of an atomic permission, ie. 'access deleted contacts'
   * @param bool $granted
   *   Whether this permission is currently granted. The hook can change this value.
   * @param int $contactId
   *   Contact whose permissions we are checking (if null, assume current user).
   *
   * @return null
   *   The return value is ignored
   */
  public static function permission_check($permission, &$granted, $contactId) {
    return self::singleton()->invoke(['permission', 'granted', 'contactId'], $permission, $granted, $contactId,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_permission_check'
    );
  }

  /**
   * @param CRM_Core_Exception $exception
   * @param mixed $request
   *   Reserved for future use.
   */
  public static function unhandledException($exception, $request = NULL) {
    $event = new \Civi\Core\Event\UnhandledExceptionEvent($exception, self::$_nullObject);
    \Civi::dispatcher()->dispatch('hook_civicrm_unhandled_exception', $event);
  }

  /**
   * This hook is called for declaring managed entities via API.
   *
   * Note: This is a pre-boot hook. It will dispatch via the extension/module
   * subsystem but *not* the Symfony EventDispatcher.
   *
   * @param array[] $entityTypes
   *   List of entity types; each entity-type is an array with keys:
   *   - name: string, a unique short name (e.g. "ReportInstance")
   *   - class: string, a PHP DAO class (e.g. "CRM_Report_DAO_Instance")
   *   - table: string, a SQL table name (e.g. "civicrm_report_instance")
   *   - fields_callback: array, list of callables which manipulates field list
   *   - links_callback: array, list of callables which manipulates fk list
   *
   * @return null
   *   The return value is ignored
   */
  public static function entityTypes(&$entityTypes) {
    return self::singleton()->invoke(['entityTypes'], $entityTypes, self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_entityTypes'
    );
  }

  /**
   * Build a description of available hooks.
   *
   * @param \Civi\Core\CiviEventInspector $inspector
   */
  public static function eventDefs($inspector) {
    $event = \Civi\Core\Event\GenericHookEvent::create([
      'inspector' => $inspector,
    ]);
    Civi::dispatcher()->dispatch('hook_civicrm_eventDefs', $event);
  }

  /**
   * This hook is called while preparing a profile form.
   *
   * @param string $profileName
   * @return mixed
   */
  public static function buildProfile($profileName) {
    return self::singleton()->invoke(['profileName'], $profileName, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, 'civicrm_buildProfile');
  }

  /**
   * This hook is called while validating a profile form submission.
   *
   * @param string $profileName
   * @return mixed
   */
  public static function validateProfile($profileName) {
    return self::singleton()->invoke(['profileName'], $profileName, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, 'civicrm_validateProfile');
  }

  /**
   * This hook is called processing a valid profile form submission.
   *
   * @param string $profileName
   * @return mixed
   */
  public static function processProfile($profileName) {
    return self::singleton()->invoke(['profileName'], $profileName, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, 'civicrm_processProfile');
  }

  /**
   * This hook is called while preparing a read-only profile screen
   *
   * @param string $profileName
   * @return mixed
   */
  public static function viewProfile($profileName) {
    return self::singleton()->invoke(['profileName'], $profileName, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, 'civicrm_viewProfile');
  }

  /**
   * This hook is called while preparing a list of contacts (based on a profile)
   *
   * @param string $profileName
   * @return mixed
   */
  public static function searchProfile($profileName) {
    return self::singleton()->invoke(['profileName'], $profileName, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, 'civicrm_searchProfile');
  }

  /**
   * This hook is invoked when building a CiviCRM name badge.
   *
   * @param string $labelName
   *   String referencing name of badge format.
   * @param object $label
   *   Reference to the label object.
   * @param array $format
   *   Array of format data.
   * @param array $participant
   *   Array of participant values.
   *
   * @return null
   *   the return value is ignored
   */
  public static function alterBadge($labelName, &$label, &$format, &$participant) {
    return self::singleton()
      ->invoke(['labelName', 'label', 'format', 'participant'], $labelName, $label, $format, $participant, self::$_nullObject, self::$_nullObject, 'civicrm_alterBadge');
  }

  /**
   * This hook is called before encoding data in barcode.
   *
   * @param array $data
   *   Associated array of values available for encoding.
   * @param string $type
   *   Type of barcode, classic barcode or QRcode.
   * @param string $context
   *   Where this hooks is invoked.
   *
   * @return mixed
   */
  public static function alterBarcode(&$data, $type = 'barcode', $context = 'name_badge') {
    return self::singleton()->invoke(['data', 'type', 'context'], $data, $type, $context, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, 'civicrm_alterBarcode');
  }

  /**
   * Modify or replace the Mailer object used for outgoing mail.
   *
   * @param object $mailer
   *   The default mailer produced by normal configuration; a PEAR "Mail" class (like those returned by Mail::factory)
   * @param string $driver
   *   The type of the default mailer (eg "smtp", "sendmail", "mock", "CRM_Mailing_BAO_Spool")
   * @param array $params
   *   The default mailer config options
   *
   * @return mixed
   * @see Mail::factory
   */
  public static function alterMailer(&$mailer, $driver, $params) {
    return self::singleton()
      ->invoke(['mailer', 'driver', 'params'], $mailer, $driver, $params, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_alterMailer');
  }

  /**
   * Deprecated: Misnamed version of alterMailer(). Remove post-4.7.x.
   * Modify or replace the Mailer object used for outgoing mail.
   *
   * @param object $mailer
   *   The default mailer produced by normal configuration; a PEAR "Mail" class (like those returned by Mail::factory)
   * @param string $driver
   *   The type of the default mailer (eg "smtp", "sendmail", "mock", "CRM_Mailing_BAO_Spool")
   * @param array $params
   *   The default mailer config options
   *
   * @return mixed
   * @see Mail::factory
   * @deprecated
   */
  public static function alterMail(&$mailer, $driver, $params) {
    // This has been deprecated on the premise it MIGHT be called externally for a long time.
    // We don't have a clear policy on how much we support external extensions calling internal
    // hooks (ie. in general we say 'don't call internal functions', but some hooks like pre hooks
    // are expected to be called externally.
    // It's really really unlikely anyone uses this - but let's add deprecations for a couple
    // of releases first.
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Utils_Hook::alterMailer');
    return CRM_Utils_Hook::alterMailer($mailer, $driver, $params);
  }

  /**
   * This hook is called while building the core search query,
   * so hook implementers can provide their own query objects which alters/extends core search.
   *
   * @param array $queryObjects
   * @param string $type
   *
   * @return mixed
   */
  public static function queryObjects(&$queryObjects, $type = 'Contact') {
    return self::singleton()
      ->invoke(['queryObjects', 'type'], $queryObjects, $type, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_queryObjects');
  }

  /**
   * This hook is called while viewing contact dashboard.
   *
   * @param array $availableDashlets
   *   List of dashlets; each is formatted per api/v3/Dashboard
   * @param array $defaultDashlets
   *   List of dashlets; each is formatted per api/v3/DashboardContact
   *
   * @return mixed
   */
  public static function dashboard_defaults($availableDashlets, &$defaultDashlets) {
    return self::singleton()
      ->invoke(['availableDashlets', 'defaultDashlets'], $availableDashlets, $defaultDashlets, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_dashboard_defaults');
  }

  /**
   * This hook is called before a case merge (or a case reassign)
   *
   * @param int $mainContactId
   * @param int $mainCaseId
   * @param int $otherContactId
   * @param int $otherCaseId
   * @param bool $changeClient
   *
   * @return mixed
   */
  public static function pre_case_merge($mainContactId, $mainCaseId = NULL, $otherContactId = NULL, $otherCaseId = NULL, $changeClient = FALSE) {
    return self::singleton()
      ->invoke(['mainContactId', 'mainCaseId', 'otherContactId', 'otherCaseId', 'changeClient'], $mainContactId, $mainCaseId, $otherContactId, $otherCaseId, $changeClient, self::$_nullObject, 'civicrm_pre_case_merge');
  }

  /**
   * This hook is called after a case merge (or a case reassign)
   *
   * @param int $mainContactId
   * @param int $mainCaseId
   * @param int $otherContactId
   * @param int $otherCaseId
   * @param bool $changeClient
   *
   * @return mixed
   */
  public static function post_case_merge($mainContactId, $mainCaseId = NULL, $otherContactId = NULL, $otherCaseId = NULL, $changeClient = FALSE) {
    return self::singleton()
      ->invoke(['mainContactId', 'mainCaseId', 'otherContactId', 'otherCaseId', 'changeClient'], $mainContactId, $mainCaseId, $otherContactId, $otherCaseId, $changeClient, self::$_nullObject, 'civicrm_post_case_merge');
  }

  /**
   * Issue CRM-14276
   * Add a hook for altering the display name
   *
   * hook_civicrm_contact_get_displayname(&$display_name, $objContact)
   *
   * @param string $displayName
   * @param int $contactId
   * @param object $dao
   *   The contact object.
   *
   * @return mixed
   */
  public static function alterDisplayName(&$displayName, $contactId, $dao) {
    return self::singleton()->invoke(['displayName', 'contactId', 'dao'],
      $displayName, $contactId, $dao, self::$_nullObject, self::$_nullObject,
      self::$_nullObject, 'civicrm_contact_get_displayname'
    );
  }

  /**
   * Modify the CRM_Core_Resources settings data.
   *
   * @param array $data
   * @see CRM_Core_Resources::addSetting
   */
  public static function alterResourceSettings(&$data) {
    $event = \Civi\Core\Event\GenericHookEvent::create([
      'data' => &$data,
    ]);
    Civi::dispatcher()->dispatch('hook_civicrm_alterResourceSettings', $event);
  }

  /**
   * EXPERIMENTAL: This hook allows one to register additional Angular modules
   *
   * @param array $angularModules
   *   List of modules. Each module defines:
   *    - ext: string, the CiviCRM extension which hosts the files.
   *    - js: array, list of JS files or globs.
   *    - css: array, list of CSS files or globs.
   *    - partials: array, list of base-dirs containing HTML.
   *    - requires: array, list of required Angular modules.
   *    - basePages: array, uncondtionally load this module onto the given Angular pages. [v4.7.21+]
   *      If omitted, default to "array('civicrm/a')" for backward compat.
   *      For a utility that should only be loaded on-demand, use "array()".
   *      For a utility that should be loaded in all pages use, "array('*')".
   * @return null
   *   the return value is ignored
   *
   * @code
   * function mymod_civicrm_angularModules(&$angularModules) {
   *   $angularModules['myAngularModule'] = array(
   *     'ext' => 'org.example.mymod',
   *     'js' => array('js/myAngularModule.js'),
   *   );
   *   $angularModules['myBigAngularModule'] = array(
   *     'ext' => 'org.example.mymod',
   *     'js' => array('js/part1.js', 'js/part2.js', 'ext://other.ext.name/file.js', 'assetBuilder://dynamicAsset.js'),
   *     'css' => array('css/myAngularModule.css', 'ext://other.ext.name/file.css', 'assetBuilder://dynamicAsset.css'),
   *     'partials' => array('partials/myBigAngularModule'),
   *     'requires' => array('otherModuleA', 'otherModuleB'),
   *     'basePages' => array('civicrm/a'),
   *   );
   * }
   * @endcode
   */
  public static function angularModules(&$angularModules) {
    return self::singleton()->invoke(['angularModules'], $angularModules,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_angularModules'
    );
  }

  /**
   * Alter the definition of some Angular HTML partials.
   *
   * @param \Civi\Angular\Manager $angular
   *
   * @code
   * function example_civicrm_alterAngular($angular) {
   *   $changeSet = \Civi\Angular\ChangeSet::create('mychanges')
   *     ->alterHtml('~/crmMailing/EditMailingCtrl/2step.html', function(phpQueryObject $doc) {
   *       $doc->find('[ng-form="crmMailingSubform"]')->attr('cat-stevens', 'ts(\'wild world\')');
   *     })
   *   );
   *   $angular->add($changeSet);
   * }
   * @endCode
   */
  public static function alterAngular($angular) {
    $event = \Civi\Core\Event\GenericHookEvent::create([
      'angular' => $angular,
    ]);
    Civi::dispatcher()->dispatch('hook_civicrm_alterAngular', $event);
  }

  /**
   * This hook is called when building a link to a semi-static asset.
   *
   * @param string $asset
   *   The name of the asset.
   *   Ex: 'angular.json'
   * @param array $params
   *   List of optional arguments which influence the content.
   * @return null
   *   the return value is ignored
   */
  public static function getAssetUrl(&$asset, &$params) {
    return self::singleton()->invoke(['asset', 'params'],
      $asset, $params, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_getAssetUrl'
    );
  }

  /**
   * This hook is called whenever the system builds a new copy of
   * semi-static asset.
   *
   * @param string $asset
   *   The name of the asset.
   *   Ex: 'angular.json'
   * @param array $params
   *   List of optional arguments which influence the content.
   *   Note: Params are immutable because they are part of the cache-key.
   * @param string $mimeType
   *   Initially, NULL. Modify to specify the mime-type.
   * @param string $content
   *   Initially, NULL. Modify to specify the rendered content.
   * @return null
   *   the return value is ignored
   */
  public static function buildAsset($asset, $params, &$mimeType, &$content) {
    return self::singleton()->invoke(['asset', 'params', 'mimeType', 'content'],
      $asset, $params, $mimeType, $content, self::$_nullObject, self::$_nullObject,
      'civicrm_buildAsset'
    );
  }

  /**
   * This hook fires whenever a record in a case changes.
   *
   * @param \Civi\CCase\Analyzer $analyzer
   *   A bundle of data about the case (such as the case and activity records).
   */
  public static function caseChange(\Civi\CCase\Analyzer $analyzer) {
    $event = new \Civi\CCase\Event\CaseChangeEvent($analyzer);
    \Civi::dispatcher()->dispatch('hook_civicrm_caseChange', $event);
  }

  /**
   * Generate a default CRUD URL for an entity.
   *
   * @param array $spec
   *   With keys:.
   *   - action: int, eg CRM_Core_Action::VIEW or CRM_Core_Action::UPDATE
   *   - entity_table: string
   *   - entity_id: int
   * @param CRM_Core_DAO $bao
   * @param array $link
   *   To define the link, add these keys to $link:.
   *   - title: string
   *   - path: string
   *   - query: array
   *   - url: string (used in lieu of "path"/"query")
   *      Note: if making "url" CRM_Utils_System::url(), set $htmlize=false
   * @return mixed
   */
  public static function crudLink($spec, $bao, &$link) {
    return self::singleton()->invoke(['spec', 'bao', 'link'], $spec, $bao, $link,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_crudLink'
    );
  }

  /**
   * Modify the CiviCRM container - add new services, parameters, extensions, etc.
   *
   * @code
   * use Symfony\Component\Config\Resource\FileResource;
   * use Symfony\Component\DependencyInjection\Definition;
   *
   * function mymodule_civicrm_container($container) {
   *   $container->addResource(new FileResource(__FILE__));
   *   $container->setDefinition('mysvc', new Definition('My\Class', array()));
   * }
   * @endcode
   *
   * Tip: The container configuration will be compiled/cached. The default cache
   * behavior is aggressive. When you first implement the hook, be sure to
   * flush the cache. Additionally, you should relax caching during development.
   * In `civicrm.settings.php`, set define('CIVICRM_CONTAINER_CACHE', 'auto').
   *
   * Note: This is a preboot hook. It will dispatch via the extension/module
   * subsystem but *not* the Symfony EventDispatcher.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   * @see http://symfony.com/doc/current/components/dependency_injection/index.html
   */
  public static function container(\Symfony\Component\DependencyInjection\ContainerBuilder $container) {
    self::singleton()->invoke(['container'], $container, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_container');
  }

  /**
   * @param array $fileSearches CRM_Core_FileSearchInterface
   * @return mixed
   */
  public static function fileSearches(&$fileSearches) {
    return self::singleton()->invoke(['fileSearches'], $fileSearches,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_fileSearches'
    );
  }

  /**
   * Check system status.
   *
   * @param array $messages
   *   Array<CRM_Utils_Check_Message>. A list of messages regarding system status.
   * @return mixed
   */
  public static function check(&$messages) {
    return self::singleton()
      ->invoke(['messages'], $messages, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_check');
  }

  /**
   * This hook is called when a query string of the CSV Batch export is generated.
   *
   * @param string $query
   *
   * @return mixed
   */
  public static function batchQuery(&$query) {
    return self::singleton()->invoke(['query'], $query, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_batchQuery'
    );
  }

  /**
   * This hook is called to alter Deferred revenue item values just before they are
   * inserted in civicrm_financial_trxn table
   *
   * @param array $deferredRevenues
   *
   * @param array $contributionDetails
   *
   * @param bool $update
   *
   * @param string $context
   *
   * @return mixed
   */
  public static function alterDeferredRevenueItems(&$deferredRevenues, $contributionDetails, $update, $context) {
    return self::singleton()->invoke(['deferredRevenues', 'contributionDetails', 'update', 'context'], $deferredRevenues, $contributionDetails, $update, $context,
      self::$_nullObject, self::$_nullObject, 'civicrm_alterDeferredRevenueItems'
    );
  }

  /**
   * This hook is called when the entries of the CSV Batch export are mapped.
   *
   * @param array $results
   * @param array $items
   *
   * @return mixed
   */
  public static function batchItems(&$results, &$items) {
    return self::singleton()->invoke(['results', 'items'], $results, $items,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_batchItems'
    );
  }

  /**
   * This hook is called when core resources are being loaded
   *
   * @see CRM_Core_Resources::coreResourceList
   *
   * @param array $list
   * @param string $region
   */
  public static function coreResourceList(&$list, $region) {
    self::singleton()->invoke(['list', 'region'], $list, $region,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_coreResourceList'
    );
  }

  /**
   * Allows the list of filters on the EntityRef widget to be altered.
   *
   * @see CRM_Core_Resources::entityRefFilters
   *
   * @param array $filters
   * @param array $links
   */
  public static function entityRefFilters(&$filters, &$links = NULL) {
    self::singleton()->invoke(['filters', 'links'], $filters, $links, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_entityRefFilters'
    );
  }

  /**
   * This hook is called for bypass a few civicrm urls from IDS check.
   *
   * @param array $skip list of civicrm urls
   *
   * @return mixed
   */
  public static function idsException(&$skip) {
    return self::singleton()->invoke(['skip'], $skip, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_idsException'
    );
  }

  /**
   * This hook is called when a geocoder's format method is called.
   *
   * @param string $geoProvider
   * @param array $values
   * @param SimpleXMLElement $xml
   *
   * @return mixed
   */
  public static function geocoderFormat($geoProvider, &$values, $xml) {
    return self::singleton()->invoke(['geoProvider', 'values', 'xml'], $geoProvider, $values, $xml,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_geocoderFormat'
    );
  }

  /**
   * This hook is called before an inbound SMS is processed.
   *
   * @param \CRM_SMS_Message $message
   *   An SMS message received
   * @return mixed
   */
  public static function inboundSMS(&$message) {
    return self::singleton()->invoke(['message'], $message, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_inboundSMS');
  }

  /**
   * This hook is called to modify api params of EntityRef form field
   *
   * @param array $params
   * @param string $formName
   * @return mixed
   */
  public static function alterEntityRefParams(&$params, $formName) {
    return self::singleton()->invoke(['params', 'formName'], $params, $formName,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_alterEntityRefParams'
    );
  }

  /**
   * This hook is called before a scheduled job is executed
   *
   * @param CRM_Core_DAO_Job $job
   *   The job to be executed
   * @param array $params
   *   The arguments to be given to the job
   */
  public static function preJob($job, $params) {
    return self::singleton()->invoke(['job', 'params'], $job, $params,
      self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_preJob'
    );
  }

  /**
   * This hook is called after a scheduled job is executed
   *
   * @param CRM_Core_DAO_Job $job
   *   The job that was executed
   * @param array $params
   *   The arguments given to the job
   * @param array $result
   *   The result of the API call, or the thrown exception if any
   */
  public static function postJob($job, $params, $result) {
    return self::singleton()->invoke(['job', 'params', 'result'], $job, $params, $result,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_postJob'
    );
  }

  /**
   * This hook is called before and after constructing mail recipients.
   *  Allows user to alter filter and/or search query to fetch mail recipients
   *
   * @param CRM_Mailing_DAO_Mailing $mailingObject
   * @param array $criteria
   *   A list of SQL criteria; you can add/remove/replace/modify criteria.
   *   Array(string $name => CRM_Utils_SQL_Select $criterion).
   *   Ex: array('do_not_email' => CRM_Utils_SQL_Select::fragment()->where("$contact.do_not_email = 0")).
   * @param string $context
   *   Ex: 'pre', 'post'
   * @return mixed
   */
  public static function alterMailingRecipients(&$mailingObject, &$criteria, $context) {
    return self::singleton()->invoke(['mailingObject', 'params', 'context'],
      $mailingObject, $criteria, $context,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_alterMailingRecipients'
    );
  }

  /**
   * ALlow Extensions to custom process IPN hook data such as sending Google Analyitcs information based on the IPN
   * @param array $IPNData - Array of IPN Data
   * @return mixed
   */
  public static function postIPNProcess(&$IPNData) {
    return self::singleton()->invoke(['IPNData'],
      $IPNData, self::$_nullObject, self::$_nullObject,
      self::$_nullObject, self::$_nullObject, self::$_nullObject,
      'civicrm_postIPNProcess'
    );
  }

}
