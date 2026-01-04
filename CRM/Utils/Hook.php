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
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
   * @deprecated
   */
  public static $_nullObject = NULL;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var CRM_Utils_Hook
   */
  static private $_singleton;

  /**
   * @var bool
   */
  private $commonIncluded = FALSE;

  /**
   * @var array|string
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
  public static function singleton($fresh = FALSE): CRM_Utils_Hook {
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
   * @throws CRM_Core_Exception
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
   * @param array $names
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
    if (!is_array($names)) {
      // We were called with the old contract wherein $names is actually an int.
      // Symfony dispatcher requires some kind of name.
      CRM_Core_Error::deprecatedWarning("hook_$fnSuffix should be updated to pass an array of parameter names to CRM_Utils_Hook::invoke().");
      $compatNames = ['arg1', 'arg2', 'arg3', 'arg4', 'arg5', 'arg6'];
      $names = array_slice($compatNames, 0, (int) $names);
    }

    $event = \Civi\Core\Event\GenericHookEvent::createOrdered(
      $names,
      [&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6]
    );
    Civi::dispatcher()->dispatch('hook_' . $fnSuffix, $event);
    return $event->getReturnValues();
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
   * @throws CRM_Core_Exception
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
   * @throws \CRM_Core_Exception
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
          throw new CRM_Core_Exception(ts('Invalid hook invocation'));
      }

      if (!empty($fResult) && is_array($fResult)) {
        $result = array_merge($result, $fResult);
      }
    }

    return empty($result) ? TRUE : $result;
  }

  /**
   * @param $moduleList
   */
  public function requireCiviModules(&$moduleList) {
    foreach ($GLOBALS['CIVICRM_FORCE_MODULES'] ?? [] as $prefix) {
      $moduleList[$prefix] = $prefix;
    }

    $civiModules = CRM_Core_PseudoConstant::getModuleExtensions();
    foreach ($civiModules as $civiModule) {
      if (!file_exists($civiModule['filePath'] ?? '')) {
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
   * @param int|null $id
   *   The object id if available.
   * @param array $params
   *   The parameters used for object creation / editing.
   *
   * @return null
   *   the return value is ignored
   */
  public static function pre($op, $objectName, $id, &$params = []) {
    $event = new \Civi\Core\Event\PreEvent($op, $objectName, $id, $params);
    Civi::dispatcher()->dispatch('hook_civicrm_pre', $event);
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
   * @param array $params
   *   Original params used, if available
   *
   * @return mixed
   *   based on op. pre-hooks return a boolean or
   *                           an error message which aborts the operation
   */
  public static function post($op, $objectName, $objectId, &$objectRef = NULL, $params = NULL) {
    $event = new \Civi\Core\Event\PostEvent($op, $objectName, $objectId, $objectRef, $params);
    Civi::dispatcher()->dispatch('hook_civicrm_post', $event);
    return $event->getReturnValues();
  }

  /**
   * This hook is equivalent to post(), except that it is guaranteed to run
   * outside of any SQL transaction. The objectRef is not modifiable.
   *
   * This hook is defined for two cases:
   *
   * 1. If the original action runs within a transaction, then the hook fires
   *    after the transaction commits.
   * 2. If the original action runs outside a transaction, then the data was
   *    committed immediately, and we can run the hook immediately.
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
  public static function postCommit($op, $objectName, $objectId, $objectRef = NULL) {
    $event = new \Civi\Core\Event\PostEvent($op, $objectName, $objectId, $objectRef);
    Civi::dispatcher()->dispatch('hook_civicrm_postCommit', $event);
    return $event->getReturnValues();
  }

  /**
   * This hook retrieves links from other modules and injects it into.
   * the view contact tabs
   *
   * @param string $op
   *   The type of operation being performed.
   * @param string $objectName
   *   The name of the object. This is generally a CamelCase entity (eg `Contact` or `Activity`).
   *   Historical exceptions: 'CRM_Core_BAO_LocationType', 'CRM_Core_BAO_MessageTemplate'
   * @param int $objectId
   *   The unique identifier for the object.
   * @param array $links
   *   (optional) the links array (introduced in v3.2).
   *   Each of the links may have properties:
   *   - 'name' (string): the link text
   *   - 'url' (string): the link URL base path (like civicrm/contact/view, and fillable from $values)
   *   - 'qs' (string|array): the link URL query parameters to be used by sprintf() with $values (like reset=1&cid=%%id%% when $values['id'] is the contact ID)
   *   - 'title' (string) (optional): the text that appears when hovering over the link
   *   - 'extra' (optional): additional attributes for the <a> tag (fillable from $values)
   *   - 'bit' (optional): a binary number that will be filtered by $mask (sending nothing as $links['bit'] means the link will always display)
   *   - 'ref' (optional, recommended): a CSS class to apply to the <a> tag.
   *   - 'class' (string): Optional list of CSS classes
   *   - 'weight' (int): Weight is used to order the links. If not set 0 will be used but e-notices could occur. This was introduced in CiviCRM 5.63 so it will not have any impact on earlier versions of CiviCRM.
   *   - 'accessKey' (string) (optional): HTML access key. Single letter or empty string.
   *   - 'icon' (string) (optional): FontAwesome class name
   *
   *   Depending on the specific screen, some fields (e.g. `icon`) may be ignored.
   *   If you have any idea of a clearer rule, then please update the docs.
   * @param int|null $mask
   *   (optional) the bitmask to show/hide links.
   * @param array $values
   *   (optional) the values to fill the links.
   *
   * @return null
   *   the return value is ignored
   */
  public static function links($op, $objectName, $objectId, &$links, &$mask = NULL, &$values = []) {
    return self::singleton()->invoke(['op', 'objectName', 'objectId', 'links', 'mask', 'values'], $op, $objectName, $objectId, $links, $mask, $values, 'civicrm_links');
  }

  /**
   * Alter the contents of a resource bundle (ie a collection of JS/CSS/etc).
   *
   * TIP: $bundle->add*() and $bundle->filter() should be useful for
   * adding/removing/updating items.
   *
   * @param CRM_Core_Resources_Bundle $bundle
   * @return null
   * @see CRM_Core_Resources_CollectionInterface::add()
   * @see CRM_Core_Resources_CollectionInterface::filter()
   */
  public static function alterBundle($bundle) {
    $null = NULL;
    return self::singleton()
      ->invoke(['bundle'], $bundle, $null, $null, $null, $null, $null, 'civicrm_alterBundle');
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
    $null = NULL;
    return self::singleton()
      ->invoke(['formName', 'form'], $formName, $form, $null, $null, $null, $null, 'civicrm_preProcess');
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
    $null = NULL;
    return self::singleton()->invoke(['formName', 'form'], $formName, $form,
      $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['formName', 'form'], $formName, $form,
      $null, $null, $null, $null,
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
   * @param CRM_Core_Form &$form the form object
   * @param array &$errors the array of errors.
   *
   * @return mixed
   *   formRule hooks return a boolean or
   *                           an array of error messages which display a QF Error
   */
  public static function validateForm($formName, &$fields, &$files, &$form, &$errors) {
    $null = NULL;
    return self::singleton()
      ->invoke(['formName', 'fields', 'files', 'form', 'errors'],
        $formName, $fields, $files, $form, $errors, $null, 'civicrm_validateForm');
  }

  /**
   * This hook is called after a db write on a custom table.
   *
   * @param string $op
   *   The type of operation being performed.
   * @param int $groupID
   *   The custom group ID.
   * @param int $entityID
   *   The entityID of the row in the custom table.
   * @param array $params
   *   The parameters that were sent into the calling function.
   *
   * @return null
   *   the return value is ignored
   */
  public static function custom(string $op, int $groupID, int $entityID, &$params) {
    $null = NULL;
    return self::singleton()
      ->invoke(['op', 'groupID', 'entityID', 'params'], $op, $groupID, $entityID, $params, $null, $null, 'civicrm_custom');
  }

  /**
   * This hook is called before a db write on a custom table.
   *
   * @param string $op
   *   The type of operation being performed.
   * @param int $groupID
   *   The custom group ID.
   * @param int $entityID
   *   The entityID of the row in the custom table.
   * @param array $params
   *   The parameters that were sent into the calling function.
   *
   * @return null
   *   the return value is ignored
   */
  public static function customPre(string $op, int $groupID, int $entityID, array &$params) {
    $null = NULL;
    return self::singleton()
      ->invoke(['op', 'groupID', 'entityID', 'params'], $op, $groupID, $entityID, $params, $null, $null, 'civicrm_customPre');
  }

  /**
   * This hook is called when composing the ACL where clause to restrict
   * visibility of contacts to the logged in user
   *
   * @param int $type
   *   Action being taken (CRM_Core_Permission::VIEW or CRM_Core_Permission::EDIT)
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
    $null = NULL;
    return self::singleton()
      ->invoke(['type', 'tables', 'whereTables', 'contactID', 'where'], $type, $tables, $whereTables, $contactID, $where, $null, 'civicrm_aclWhereClause');
  }

  /**
   * Called when restricting access to contact-groups or custom_field-groups or profile-groups.
   *
   * Hook subscribers should alter the array of $currentGroups by reference.
   *
   * @param int $type
   *   Action type being performed e.g. CRM_ACL_API::VIEW or CRM_ACL_API::EDIT
   * @param int $contactID
   *   User contactID for whom the check is made.
   * @param string $tableName
   *   Table name of group, e.g. 'civicrm_group' or 'civicrm_uf_group' or 'civicrm_custom_group'.
   * @param array $allGroups
   *   All groups from the above table, keyed by id.
   * @param int[] $currentGroups
   *   Ids of allowed groups (corresponding to array keys of $allGroups) to be altered by reference.
   *
   * @return null
   *   the return value is ignored
   */
  public static function aclGroup($type, $contactID, $tableName, &$allGroups, &$currentGroups) {
    $null = NULL;
    return self::singleton()
      ->invoke(['type', 'contactID', 'tableName', 'allGroups', 'currentGroups'], $type, $contactID, $tableName, $allGroups, $currentGroups, $null, 'civicrm_aclGroup');
  }

  /**
   * @param string|CRM_Core_DAO $entity
   * @param array $clauses
   * @param int|null $userId
   *   User contact id. NULL == current user.
   * @param array $conditions
   *   Values from WHERE or ON clause
   */
  public static function selectWhereClause($entity, array &$clauses, ?int $userId = NULL, array $conditions = []): void {
    $entityName = is_object($entity) ? CRM_Core_DAO_AllCoreTables::getEntityNameForClass(get_class($entity)) : $entity;
    $null = NULL;
    $userId ??= (int) CRM_Core_Session::getLoggedInContactID();
    self::singleton()->invoke(['entity', 'clauses', 'userId', 'conditions'],
      $entityName, $clauses, $userId, $conditions,
      $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['files'], $files,
      $null, $null, $null, $null, $null,
      'civicrm_xmlMenu'
    );
  }

  /**
   * This hook is called when building the menu table.
   *
   * @param array $items
   *   List of records to include in menu table.
   * @return null
   *   the return value is ignored
   */
  public static function alterMenu(&$items) {
    $null = NULL;
    return self::singleton()->invoke(['items'], $items,
      $null, $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['themes'], $themes,
      $null, $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['theme', 'context'], $theme, $context,
      $null, $null, $null, $null,
      'civicrm_activeTheme'
    );
  }

  /**
   * This hook is called for declaring managed entities via API.
   *
   * @code
   * // Example: Optimal skeleton for backward/forward compatibility
   * function example_civicrm_managed(&$entities, ?array $modules = NULL) {
   *   if ($modules !== NULL && !in_array(E::LONG_NAME, $modules, TRUE)) {
   *     return;
   *   }
   *   $entities[] = [
   *     'module' => E::LONG_NAME,
   *     'name' => 'my_option_value',
   *     'entity' => 'OptionValue',
   *     'params' => [...],
   *   ];
   * }
   * @endCode
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
   *   + 'source' (optional): string, the file which defined this entity
   * @param array|NULL $modules
   *   (Added circa v5.50) If given, only report entities related to $modules. NULL is a wildcard ("all modules").
   *
   *   This parameter is _advisory_ and is not supplied on older versions.
   *   Listeners SHOULD self-censor (only report entities which match the filter).
   *   However, all pre-existing listeners were unaware of this option, and they WILL over-report.
   *   Over-reported data will be discarded.
   * @return null
   *   the return value is ignored
   */
  public static function managed(array &$entities, ?array $modules = NULL) {
    $null = NULL;
    self::singleton()->invoke(['entities', 'modules'], $entities, $modules,
      $null, $null, $null, $null,
      'civicrm_managed'
    );
    if ($modules) {
      $entities = array_filter($entities, function($entity) use ($modules) {
        return in_array($entity['module'], $modules, TRUE);
      });
    }
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
    $null = NULL;
    $retval = self::singleton()->invoke(['contactID', 'contentPlacement'], $contactID, $contentPlacement,
      $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['recentArray'], $recentArray,
      $null, $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['dao', 'refCounts'], $dao, $refCounts,
      $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['pageType', 'form', 'amount'], $pageType, $form, $amount, $null,
      $null, $null, 'civicrm_buildAmount');
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
    $null = NULL;
    return self::singleton()->invoke(['countryID', 'states'], $countryID, $states,
      $null, $null, $null, $null,
      'civicrm_buildStateProvinceForCountry'
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
    $null = NULL;
    return self::singleton()->invoke(['tabsetName', 'tabs', 'context'], $tabsetName, $tabs,
      $context, $null, $null, $null, 'civicrm_tabset'
    );
  }

  /**
   * This hook is called when sending an email / printing labels
   *
   * @param array $tokens
   *   The list of tokens that can be used for the contact.
   *
   * @param bool $squashDeprecation
   *    Suppress the deprecation message - this should ONLY EVER BE CALLED
   *    from the backward compatibilty adapter in `evaluateLegacyHookTokens`.
   *    We are deprecating both this function, and the implementation of the hook
   *    but for now we ensure that the hook is still rendered for
   *    sites that implement it, via the TokenProcessor methodology
   *    https://docs.civicrm.org/dev/en/latest/framework/token/#compose-batch
   *
   * @return null
   */
  public static function tokens(&$tokens, bool $squashDeprecation = FALSE) {
    $null = NULL;
    if (!$squashDeprecation) {
      CRM_Core_Error::deprecatedFunctionWarning('call the token processor');
    }
    return self::singleton()->invoke(['tokens'], $tokens,
      $null, $null, $null, $null, $null, 'civicrm_tokens'
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
    $null = NULL;
    return self::singleton()->invoke(['panels'], $panels,
      $null, $null, $null, $null, $null,
      'civicrm_alterAdminPanel'
    );
  }

  /**
   * This hook is called when sending an email / printing labels to get the values for all the
   * tokens returned by the 'tokens' hook
   *
   * @param array $details
   *   The array to store the token values indexed by contactIDs.
   * @param array $contactIDs
   *   An array of contactIDs.
   * @param null $jobID
   *   The jobID if this is associated with a CiviMail mailing.
   * @param array $tokens
   *   The list of tokens associated with the content.
   * @param null $className
   *   The top level className from where the hook is invoked.
   * @param bool $squashDeprecation
   *   Suppress the deprecation message - this should ONLY EVER BE CALLED
   *   from the backward compatibilty adapter in `evaluateLegacyHookTokens`.
   *   We are deprecating both this function, and the implementation of the hook
   *   but for now we ensure that the hook is still rendered for
   *   sites that implement it, via the TokenProcessor methodology
   *   https://docs.civicrm.org/dev/en/latest/framework/token/#compose-batch
   *
   * @return null
   * @deprecated since 5.71 will be removed sometime after all core uses are fully removed.
   *
   */
  public static function tokenValues(
    &$details,
    $contactIDs,
    $jobID = NULL,
    $tokens = [],
    $className = NULL,
    $squashDeprecation = FALSE
  ) {
    $null = NULL;
    if (!$squashDeprecation) {
      CRM_Core_Error::deprecatedFunctionWarning('call the token processor');
    }
    return self::singleton()
      ->invoke(['details', 'contactIDs', 'jobID', 'tokens', 'className'], $details, $contactIDs, $jobID, $tokens, $className, $null, 'civicrm_tokenValues');
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
    $null = NULL;
    return self::singleton()->invoke(['page'], $page,
      $null, $null, $null, $null, $null,
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
   * @param int $original_id
   *   Original entity ID.
   *
   * @return null
   */
  public static function copy($objectName, &$object, $original_id = NULL) {
    $null = NULL;
    return self::singleton()->invoke(['objectName', 'object', 'original_id'], $objectName, $object, $original_id,
      $null, $null, $null,
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
    $null = NULL;
    return self::singleton()
      ->invoke(['op', 'mailingId', 'contactId', 'groups', 'baseGroups'], $op, $mailingId, $contactId, $groups, $baseGroups, $null, 'civicrm_unsubscribeGroups');
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
    $null = NULL;
    return self::singleton()->invoke(['entity', 'field', 'options', 'params'], $entity, $field, $options, $params,
      $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['objectType', 'tasks'], $objectType, $tasks,
      $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['form', 'params'], $form, $params,
      $null, $null, $null, $null,
      'civicrm_eventDiscount'
    );
  }

  /**
   * When adding a new "Mail Account" (`MailSettings`), present a menu of setup
   * options.
   *
   * @param array $setupActions
   *   Each item has a symbolic-key, and it has the properties:
   *     - title: string
   *     - callback: string|array, the function which starts the setup process.
   *        The function is expected to return a 'url' for the config screen.
   *     - url: string (optional), a URL which starts the setup process.
   *        If omitted, then a default URL is generated. The effect of opening the URL is
   *        to invoke the `callback`.
   * @return mixed
   */
  public static function mailSetupActions(&$setupActions) {
    $null = NULL;
    return self::singleton()->invoke(['setupActions'], $setupActions, $null, $null,
      $null, $null, $null,
      'civicrm_mailSetupActions'
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
    $null = NULL;
    return self::singleton()->invoke(['form', 'groups', 'mailings'], $form, $groups, $mailings,
      $null, $null, $null,
      'civicrm_mailingGroups'
    );
  }

  /**
   * Modify the list of template-types used for CiviMail composition.
   *
   * @param array $types
   *   Sequentially indexed list of template types. Each type specifies:
   *     - name: string
   *     - editorUrl: string, Angular template URL
   *     - weight: int, priority when picking a default value for new mailings
   * @return mixed
   */
  public static function mailingTemplateTypes(&$types) {
    $null = NULL;
    return self::singleton()->invoke(['types'], $types, $null, $null,
      $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['form', 'membershipTypes'], $form, $membershipTypes,
      $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['contactID', 'content', 'contentPlacement'], $contactID, $content, $contentPlacement,
      $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['query', 'queryText', 'context', 'id'], $query, $queryText, $context, $id,
      $null, $null,
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
   * @param array|\Civi\Payment\PropertyBag &$rawParams
   *    array of params as passed to to the processor
   * @param array|\Civi\Payment\PropertyBag &$cookedParams
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
    $null = NULL;
    return self::singleton()->invoke(['paymentObj', 'rawParams', 'cookedParams'], $paymentObj, $rawParams, $cookedParams,
      $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['params', 'context'], $params, $context,
      $null, $null, $null, $null,
      'civicrm_alterMailParams'
    );
  }

  /**
   * This hook is called when loading a mail-store (e.g. IMAP, POP3, or Maildir).
   *
   * @param array $params
   *   Most fields correspond to data in the MailSettings entity:
   *   - id: int
   *   - server: string
   *   - username: string
   *   - password: string
   *   - is_ssl: bool
   *   - source: string
   *   - local_part: string
   *
   *   With a few supplements
   *   - protocol: string, symbolic protocol name (e.g. "IMAP")
   *   - factory: callable, the function which instantiates the driver class
   *   - auth: string, (for some drivers) specify the authentication method (eg "Password" or "XOAuth2")
   *
   * @return mixed
   */
  public static function alterMailStore(&$params) {
    $null = NULL;
    return self::singleton()->invoke(['params'], $params, $context,
      $null, $null, $null, $null,
      'civicrm_alterMailStore'
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
    $null = NULL;
    return self::singleton()->invoke(['membershipStatus', 'arguments', 'membership'], $membershipStatus, $arguments,
      $membership, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['content'], $content,
      $null, $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['caseID'], $caseID,
      $null, $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()
      ->invoke(['caseTypes'], $caseTypes, $null, $null, $null, $null, $null, 'civicrm_caseTypes');
  }

  /**
   * This hook is called when getting case email subject patterns.
   *
   * All emails related to cases have case hash/id in the subject, e.g:
   * [case #ab12efg] Magic moment
   * [case #1234] Magic is here
   *
   * Using this hook you can replace/enrich default list with some other
   * patterns, e.g. include case type categories (see CiviCase extension) like:
   * [(case|project|policy initiative) #hash]
   * [(case|project|policy initiative) #id]
   *
   * @param array $subjectPatterns
   *   Cases related email subject regexp patterns.
   *
   * @return mixed
   */
  public static function caseEmailSubjectPatterns(&$subjectPatterns) {
    $null = NULL;
    return self::singleton()
      ->invoke(['caseEmailSubjectPatterns'], $subjectPatterns, $null, $null, $null, $null, $null, 'civicrm_caseEmailSubjectPatterns');
  }

  /**
   * You can use this hook to modify the config object and hence behavior of CiviCRM dynamically.
   *
   * In *typical* page-loads, this hook fires one time. However, the hook may fire multiple times if...
   *
   * - the process is executing test-suites, or
   * - the process involves some special configuration-changes, or
   * - the process begins with the "extern" bootstrap process (aka `loadBootStrap()`)
   *       N.B. For "extern", CiviCRM initially boots without having access to the UF APIs.
   *       When the UF eventually boots, it may re-fire the event (for the benefit UF add-ons).
   *
   * The possibility of multiple invocations means that most consumers should be guarded.
   * When registering resources, consult the `$flags`.
   *
   *   function hook_civicrm_config($config, $flags = NULL) {
   *     if ($flags['...']) {
   *       Civi::dispatcher()->addListener(...);
   *       CRM_Core_Smarty::singleton()->addTemplateDir(...);
   *     }
   *   }
   *
   * @param CRM_Core_Config $config
   *   The config object
   * @param array|NULL $flags
   *   Mix of flags:
   *     - civicrm: TRUE if this invocation is intended for CiviCRM extensions
   *     - uf: TRUE if this invocation is intended for UF modules (Drupal/Joomla/etc)
   *     - instances: The number of distinct copies of `CRM_Core_Config` which have been initialized.
   *
   *   The value of `$flags` is NULL when executing on an older CiviCRM environments (<=5.65).
   *
   * @return mixed
   */
  public static function config(&$config, ?array $flags = NULL) {
    static $count = 0;
    if (!empty($flags['civicrm'])) {
      $count++;
    }
    $defaultFlags = ['civicrm' => FALSE, 'uf' => FALSE, 'instances' => $count];
    $flags = !empty($flags) ? array_merge($defaultFlags, $flags) : $defaultFlags;
    $null = NULL;
    return self::singleton()->invoke(['config', 'flags'], $config,
      $flags, $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['options', 'groupName'], $options, $groupName,
      $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['params'], $params,
      $null, $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['type', 'data', 'mainId', 'otherId', 'tables'], $type, $data, $mainId, $otherId, $tables, $null, 'civicrm_merge');
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
    $null = NULL;
    return self::singleton()->invoke(['blocksDAO', 'mainId', 'otherId', 'migrationInfo'], $blocksDAO, $mainId, $otherId, $migrationInfo, $null, $null, 'civicrm_alterLocationMergeData');
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
   * @deprecated since 5.72
   *
   * @param string $obj
   *   Object of rulegroup class.
   * @param string $type
   *   Type of queries e.g table / threshold.
   * @param array $query
   *   Set of queries.
   */
  public static function dupeQuery($obj, $type, &$query) {
    $null = NULL;
    $original = $query;
    self::singleton()->invoke(['obj', 'type', 'query'], $obj, $type, $query,
      $null, $null, $null,
      'civicrm_dupeQuery'
    );
    if ($original !== $query && $type !== 'supportedFields') {
      CRM_Core_Error::deprecatedWarning('hook_civicrm_dupeQuery is deprecated.');
    }
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
    $null = NULL;
    return self::singleton()
      ->invoke(['dedupeParams', 'dedupeResults', 'contextParams'], $dedupeParams, $dedupeResults, $contextParams, $null, $null, $null, 'civicrm_findDuplicates');
  }

  /**
   * Check for existing duplicates in the database.
   *
   * This hook is called when
   *
   * @param array $duplicates
   *   Array of duplicate pairs found using the rule, with the weight.
   *   ['entity_id_1' => 5, 'entity_id_2' => 6, 'weight' => 7] where 5 & 6 are contact IDs and 7 is the weight of the match.
   * @param int[] $ruleGroupIDs
   *   Array of rule group IDs.
   * @param string|null $tableName
   *   Name of a table holding ids to restrict the query to. If there is no ID restriction
   *   The table will be NULL.
   * @param bool $checkPermissions
   * @todo the existing implementation looks for situations where ONE of the contacts
   *   is consistent with the where clause criteria. Potentially we might
   *   implement a mode where both/all contacts must be consistent with the clause criteria.
   *   There is a use case for both scenarios - although core code currently only implements
   *   one.
   *
   * @return mixed
   */
  public static function findExistingDuplicates(array &$duplicates, array $ruleGroupIDs, ?string $tableName, bool $checkPermissions) {
    $null = NULL;
    return self::singleton()
      ->invoke(['duplicates', 'ruleGroupIDs', 'tableName', 'checkPermissions'], $duplicates, $ruleGroupIDs, $tableName, $checkPermissions, $null, $null, 'civicrm_findExistingDuplicates');
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
   * @param int|null $mailSettingId
   *   The MailSetting ID the email relates to
   *
   * @return mixed
   */
  public static function emailProcessor($type, &$params, $mail, &$result, $action = NULL, ?int $mailSettingId = NULL) {
    $null = NULL;
    return self::singleton()
      ->invoke(['type', 'params', 'mail', 'result', 'action', 'mailSettingId'], $type, $params, $mail, $result, $action, $mailSettingId, 'civicrm_emailProcessor');
  }

  /**
   * This hook is called after a row has been processed and the
   * record (and associated records imported
   *
   * @deprecated
   *
   * @param string $object
   *   Object being imported (Contact only)
   * @param string $usage
   *   Hook usage/location (for now process only).
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
    $null = NULL;
    return self::singleton()->invoke(['object', 'usage', 'objectRef', 'params'], $object, $usage, $objectRef, $params,
      $null, $null,
      'civicrm_import'
    );
  }

  /**
   * Alter import mappings.
   *
   * @param string $importType This corresponds to the value in `civicrm_user_job.job_type`.
   * @param string $context import or validate.
   *   In validate context only 'cheap' lookups should be done (e.g. using cached information).
   *   Validate is intended to quickly process a whole file for errors. You should focus on
   *   setting or unsetting key values to or from `'invalid_import_value'`.
   *
   *   During import mode heavier lookups can be done (e.g using custom logic to find the
   *   relevant contact) as this is then passed to the api functions. If a row is invalid during
   *   import mode you should throw an exception.
   * @param array $mappedRow (reference) The rows that have been mapped to an array of params.
   * @param array $rowValues The row from the data source (non-associative array)
   * @param int $userJobID id from civicrm_user_job
   *
   * @return mixed
   */
  public static function importAlterMappedRow(string $importType, string $context, array &$mappedRow, array $rowValues, int $userJobID) {
    $null = NULL;
    return self::singleton()->invoke(['importType', 'context', 'mappedRow', 'rowValues', 'userJobID', 'fieldMappings'], $context, $importType, $mappedRow, $rowValues, $userJobID, $null,
      'civicrm_importAlterMappedRow'
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
    $null = NULL;
    return self::singleton()->invoke(['entity', 'action', 'params', 'permissions'], $entity, $action, $params, $permissions,
      $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['dao'], $dao,
      $null, $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['actions', 'contactID'], $actions, $contactID,
      $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['objectName', 'headers', 'rows', 'selector'], $objectName, $headers, $rows, $selector,
      $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['moduleName', 'ufGroups'], $moduleName, $ufGroups,
      $null, $null, $null, $null,
      'civicrm_buildUFGroupsForModule'
    );
  }

  /**
   * Build the group contact cache for the relevant group.
   *
   * This hook allows a listener to specify the sql to be used to build a group in
   * the group contact cache.
   *
   * If sql is altered then the api / bao query methods of building the cache will not
   * be called.
   *
   * An example of the sql it might be set to is:
   *
   * SELECT 7 AS group_id, contact_a.id as contact_id
   * FROM  civicrm_contact contact_a
   * WHERE contact_a.contact_type   = 'Household' AND contact_a.household_name LIKE '%' AND  ( 1 )  ORDER BY contact_a.id
   * AND contact_a.id
   *   NOT IN (
   *   SELECT contact_id FROM civicrm_group_contact
   *   WHERE civicrm_group_contact.status = 'Removed'
   *   AND civicrm_group_contact.group_id = 7 )
   *
   * @param array $savedSearch
   * @param int $groupID
   * @param string $sql
   */
  public static function buildGroupContactCache(array $savedSearch, int $groupID, string &$sql): void {
    $null = NULL;
    self::singleton()->invoke(['savedSearch', 'groupID', 'sql'], $savedSearch, $groupID,
      $sql, $null, $null, $null,
      'civicrm_buildGroupContactCache'
    );
  }

  /**
   * Scan extensions for a list of auto-registered interfaces.
   *
   * @see mixin/scan-classes@1
   *
   * @param string[] $classes
   *   List of classes which may be of interest to the class-scanner.
   */
  public static function scanClasses(array &$classes) {
    self::singleton()->invoke(['classes'], $classes, $null,
      $null, $null, $null, $null,
      'civicrm_scanClasses'
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
    $null = NULL;
    return self::singleton()->invoke(['email', 'contactID', 'result'], $email, $contactID, $result,
      $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['args'], $args,
      $null, $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['content', 'context', 'tplName', 'object'], $content, $context, $tplName, $object,
      $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['formName', 'form', 'context', 'tplName'], $formName, $form, $context, $tplName,
      $null, $null,
      'civicrm_alterTemplateFile'
    );
  }

  /**
   * Register cryptographic resources, such as keys and cipher-suites.
   *
   * Ex: $crypto->addSymmetricKey([
   *   'key' => hash_hkdf('sha256', 'abcd1234'),
   *   'suite' => 'aes-cbc-hs',
   * ]);
   *
   * @param \Civi\Crypto\CryptoRegistry $crypto
   *
   * @return mixed
   */
  public static function crypto($crypto) {
    $null = NULL;
    return self::singleton()->invoke(['crypto'], $crypto, $null,
      $null, $null, $null, $null,
      'civicrm_crypto'
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
    $null = NULL;
    return self::singleton()->invoke(['info', 'tableName'], $info, $tableName,
      $null, $null, $null, $null,
      'civicrm_triggerInfo'
    );
  }

  /**
   * Define the list of fields supported in APIv4 data-translation.
   *
   * @param array $fields
   *   List of data fields to translate, organized by table and column.
   *   Omitted/unlisted fields are not translated. Any listed field may be translated.
   *   Values should be TRUE.
   *   Ex: $fields['civicrm_event']['summary'] = TRUE
   * @return mixed
   */
  public static function translateFields(&$fields) {
    $null = NULL;
    return self::singleton()->invoke(['fields'], $fields, $null,
      $null, $null, $null, $null,
      'civicrm_translateFields'
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
    $null = NULL;
    return self::singleton()->invoke(['logTableSpec'], $logTableSpec, $_nullObject,
      $null, $null, $null, $null,
      'civicrm_alterLogTables'
    );
  }

  /**
   * Run early installation steps for an extension. Ex: Create new MySQL table.
   *
   * This dispatches directly to each new extension. You will only receive notices for your own installation.
   *
   * If multiple extensions are installed simultaneously, they will all run
   * `hook_install`/`hook_enable` back-to-back (in order of dependency).
   *
   * This runs BEFORE refreshing major caches and services (such as
   * `ManagedEntities` and `CRM_Logging_Schema`).
   *
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
   */
  public static function install() {
    // Actually invoke via CRM_Extension_Manager_Module::callHook
    throw new RuntimeException(sprintf("The method %s::%s is just a documentation stub and should not be invoked directly.", __CLASS__, __FUNCTION__));
  }

  /**
   * Run later installation steps. Ex: Call a bespoke API-job for the first time.
   *
   * This dispatches directly to each new extension. You will only receive notices for your own installation.
   *
   * If multiple extensions are installed simultaneously, they will all run
   * `hook_postInstall` back-to-back (in order of dependency).
   *
   * This runs AFTER refreshing major caches and services (such as
   * `ManagedEntities` and `CRM_Logging_Schema`).
   *
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
   */
  public static function postInstall() {
    // Actually invoke via CRM_Extension_Manager_Module::callHook
    throw new RuntimeException(sprintf("The method %s::%s is just a documentation stub and should not be invoked directly.", __CLASS__, __FUNCTION__));
  }

  /**
   * This hook is called when a module-extension is uninstalled.
   * Each module will receive hook_civicrm_uninstall during its own uninstallation (but not during the
   * uninstallation of unrelated modules).
   */
  public static function uninstall() {
    // Actually invoke via CRM_Extension_Manager_Module::callHook
    throw new RuntimeException(sprintf("The method %s::%s is just a documentation stub and should not be invoked directly.", __CLASS__, __FUNCTION__));
  }

  /**
   * This hook is called when a module-extension is re-enabled.
   * Each module will receive hook_civicrm_enable during its own re-enablement (but not during the
   * re-enablement of unrelated modules).
   */
  public static function enable() {
    // Actually invoke via CRM_Extension_Manager_Module::callHook
    throw new RuntimeException(sprintf("The method %s::%s is just a documentation stub and should not be invoked directly.", __CLASS__, __FUNCTION__));
  }

  /**
   * This hook is called when a module-extension is disabled.
   * Each module will receive hook_civicrm_disable during its own disablement (but not during the
   * disablement of unrelated modules).
   */
  public static function disable() {
    // Actually invoke via CRM_Extension_Manager_Module::callHook
    throw new RuntimeException(sprintf("The method %s::%s is just a documentation stub and should not be invoked directly.", __CLASS__, __FUNCTION__));
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
    $null = NULL;
    return self::singleton()->invoke(['url', 'context'], $url,
      $context, $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['varType', 'var', 'object'], $varType, $var, $object,
      $null, $null, $null,
      'civicrm_alterReportVar'
    );
  }

  /**
   * This hook is called to drive database upgrades for extension-modules.
   *
   * @param string $op
   *   The type of operation being performed; 'check' or 'enqueue'.
   * @param CRM_Queue_Queue|null $queue
   *   (for 'enqueue') the modifiable list of pending up upgrade tasks.
   *
   * @return bool|null
   *   NULL, if $op is 'enqueue'.
   *   TRUE, if $op is 'check' and upgrades are pending.
   *   FALSE, if $op is 'check' and upgrades are not pending.
   */
  public static function upgrade($op, ?CRM_Queue_Queue $queue = NULL) {
    $null = NULL;
    return self::singleton()->invoke(['op', 'queue'], $op, $queue,
      $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['params'], $params,
      $null, $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['mailingId'], $mailingId,
      $null, $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['settingsFolders'], $settingsFolders,
      $null, $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['settingsMetaData', 'domainID', 'profile'], $settingsMetaData,
      $domainID, $profile,
      $null, $null, $null,
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
    $null = NULL;
    return self::singleton()
      ->invoke(['wrappers', 'apiRequest'], $wrappers, $apiRequest,
        $null, $null, $null, $null,
        'civicrm_apiWrappers'
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
    $null = NULL;
    return self::singleton()->invoke(['jobManager'],
      $jobManager, $null, $null, $null, $null, $null,
      'civicrm_cron'
    );
  }

  /**
   * This hook is called when exporting Civi's permission to the CMS. Use this hook to modify
   * the array of system permissions for CiviCRM.
   *
   * @param array $newPermissions
   *   Array to be filled with permissions.
   *
   * @return null
   *   The return value is ignored
   * @throws RuntimeException
   */
  public static function permission(&$newPermissions) {
    $null = NULL;
    return self::singleton()->invoke(['permissions'], $newPermissions, $null,
      $null, $null, $null, $null,
      'civicrm_permission'
    );
  }

  /**
   * This hook is used to enumerate the list of available permissions. It may
   * include concrete permissions defined by Civi, concrete permissions defined
   * by the CMS, and/or synthetic permissions.
   *
   * @param array $permissions
   *   Array of permissions, keyed by symbolic name. Each is an array with fields:
   *     - group: string (ex: "civicrm", "cms")
   *     - title: string (ex: "CiviEvent: Register for events")
   *     - description: string (ex: "Register for events online")
   *     - is_synthetic: bool (TRUE for synthetic permissions with a bespoke evaluation. FALSE for concrete permissions that registered+granted in the UF user-management layer.
   *        Default TRUE iff name begins with '@')
   *     - is_active: bool (FALSE for permissions belonging to disabled components, TRUE otherwise)
   *
   * @return null
   *   The return value is ignored
   * @see Civi\Api4\Permission::get()
   */
  public static function permissionList(&$permissions) {
    $null = NULL;
    return self::singleton()->invoke(['permissions'], $permissions,
      $null, $null, $null, $null, $null,
      'civicrm_permissionList'
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
    $null = NULL;
    return self::singleton()->invoke(['permission', 'granted', 'contactId'], $permission, $granted, $contactId,
      $null, $null, $null,
      'civicrm_permission_check'
    );
  }

  /**
   * Rotate the cryptographic key used in the database.
   *
   * The purpose of this hook is to visit any encrypted values in the database
   * and re-encrypt the content.
   *
   * For values encoded via `CryptoToken`, you can use `CryptoToken::rekey($oldToken, $tag)`
   *
   * @param string $tag
   *   The type of crypto-key that is currently being rotated.
   *   The hook-implementer should use this to decide which (if any) fields to visit.
   *   Ex: 'CRED'
   * @param \Psr\Log\LoggerInterface $log
   *   List of messages about re-keyed values.
   *
   * @code
   * function example_civicrm_rekey($tag, &$log) {
   *   if ($tag !== 'CRED') return;
   *
   *   $cryptoToken = Civi::service('crypto.token');
   *   $rows = sql('SELECT id, secret_column FROM some_table');
   *   foreach ($rows as $row) {
   *     $new = $cryptoToken->rekey($row['secret_column']);
   *     if ($new !== NULL) {
   *       sql('UPDATE some_table SET secret_column = %1 WHERE id = %2',
   *         $new, $row['id']);
   *     }
   *   }
   * }
   * @endCode
   *
   * @return null
   *   The return value is ignored
   */
  public static function cryptoRotateKey($tag, $log) {
    $null = NULL;
    return self::singleton()->invoke(['tag', 'log'], $tag, $log, $null,
      $null, $null, $null,
      'civicrm_cryptoRotateKey'
    );
  }

  /**
   * @param Throwable $exception
   */
  public static function unhandledException($exception) {
    $null = NULL;
    $event = new \Civi\Core\Event\UnhandledExceptionEvent($exception, $null);
    Civi::dispatcher()->dispatch('hook_civicrm_unhandled_exception', $event);
  }

  /**
   * This hook is called for declaring entities.
   *
   * Note: This is a pre-boot hook. It will dispatch via the extension/module
   * subsystem but *not* the Symfony EventDispatcher.
   *
   * @param array[] $entityTypes
   *   List of entity definitions; each item is keyed by entity name.
   *   Each entity-type is an array with values:
   *   - `name`: string, a unique short name (e.g. "ReportInstance")
   *   - `module`: string, full_name of extension declaring the entity (e.g. "search_kit")
   *   - `class`: string|null, a PHP DAO class (e.g. "CRM_Report_DAO_Instance")
   *   - `table`: string|null, a SQL table name (e.g. "civicrm_report_instance")
   *
   * Other possible values in the entity definition array are documented here:
   * @see https://docs.civicrm.org/dev/en/latest/framework/entities/
   *
   * @return null
   *   The return value is ignored
   */
  public static function entityTypes(&$entityTypes) {
    $null = NULL;
    return self::singleton()->invoke(['entityTypes'], $entityTypes, $null, $null,
      $null, $null, $null, 'civicrm_entityTypes'
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
    $null = NULL;
    return self::singleton()->invoke(['profileName'], $profileName, $null, $null, $null,
      $null, $null, 'civicrm_buildProfile');
  }

  /**
   * This hook is called while validating a profile form submission.
   *
   * @param string $profileName
   * @return mixed
   */
  public static function validateProfile($profileName) {
    $null = NULL;
    return self::singleton()->invoke(['profileName'], $profileName, $null, $null, $null,
      $null, $null, 'civicrm_validateProfile');
  }

  /**
   * This hook is called processing a valid profile form submission.
   *
   * @param string $profileName
   * @return mixed
   */
  public static function processProfile($profileName) {
    $null = NULL;
    return self::singleton()->invoke(['profileName'], $profileName, $null, $null, $null,
      $null, $null, 'civicrm_processProfile');
  }

  /**
   * This hook is called while preparing a read-only profile screen
   *
   * @param string $profileName
   * @return mixed
   */
  public static function viewProfile($profileName) {
    $null = NULL;
    return self::singleton()->invoke(['profileName'], $profileName, $null, $null, $null,
      $null, $null, 'civicrm_viewProfile');
  }

  /**
   * This hook is called while preparing a list of contacts (based on a profile)
   *
   * @param string $profileName
   * @return mixed
   */
  public static function searchProfile($profileName) {
    $null = NULL;
    return self::singleton()->invoke(['profileName'], $profileName, $null, $null, $null,
      $null, $null, 'civicrm_searchProfile');
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
    $null = NULL;
    return self::singleton()
      ->invoke(['labelName', 'label', 'format', 'participant'], $labelName, $label, $format, $participant, $null, $null, 'civicrm_alterBadge');
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
    $null = NULL;
    return self::singleton()->invoke(['data', 'type', 'context'], $data, $type, $context, $null,
      $null, $null, 'civicrm_alterBarcode');
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
    $null = NULL;
    return self::singleton()
      ->invoke(['mailer', 'driver', 'params'], $mailer, $driver, $params, $null, $null, $null, 'civicrm_alterMailer');
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
    $null = NULL;
    return self::singleton()
      ->invoke(['queryObjects', 'type'], $queryObjects, $type, $null, $null, $null, $null, 'civicrm_queryObjects');
  }

  /**
   * This hook is called while initializing the default dashlets for a contact dashboard.
   *
   * @param array $availableDashlets
   *   List of dashlets; each is formatted per api/v3/Dashboard
   * @param array $defaultDashlets
   *   List of dashlets; each is formatted per api/v3/DashboardContact
   *
   * @return mixed
   */
  public static function dashboard_defaults($availableDashlets, &$defaultDashlets) {
    $null = NULL;
    return self::singleton()
      ->invoke(['availableDashlets', 'defaultDashlets'], $availableDashlets, $defaultDashlets, $null, $null, $null, $null, 'civicrm_dashboard_defaults');
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
    $null = NULL;
    return self::singleton()
      ->invoke(['mainContactId', 'mainCaseId', 'otherContactId', 'otherCaseId', 'changeClient'], $mainContactId, $mainCaseId, $otherContactId, $otherCaseId, $changeClient, $null, 'civicrm_pre_case_merge');
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
    $null = NULL;
    return self::singleton()
      ->invoke(['mainContactId', 'mainCaseId', 'otherContactId', 'otherCaseId', 'changeClient'], $mainContactId, $mainCaseId, $otherContactId, $otherCaseId, $changeClient, $null, 'civicrm_post_case_merge');
  }

  /**
   * Issue CRM-14276
   * Add a hook for altering the display name
   *
   * hook_civicrm_contact_get_displayname(&$display_name, $contactId, $dao)
   *
   * @param string $displayName
   * @param int $contactId
   * @param CRM_Core_DAO $dao
   *   A DAO object containing contact fields + primary email field as "email".
   *
   * @return mixed
   */
  public static function alterDisplayName(&$displayName, $contactId, $dao) {
    $null = NULL;
    return self::singleton()->invoke(['displayName', 'contactId', 'dao'],
      $displayName, $contactId, $dao, $null, $null,
      $null, 'civicrm_contact_get_displayname'
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
   * Register Angular modules
   *
   * @param array $angularModules
   *   List of modules. Each module defines:
   *    - ext: string, the CiviCRM extension which hosts the files.
   *    - js: array, list of JS files or globs.
   *    - css: array, list of CSS files or globs.
   *    - partials: array, list of base-dirs containing HTML.
   *    - partialsCallback: mixed, a callback function which generates a list of HTML
   *        function(string $moduleName, array $moduleDefn) => array(string $file => string $html)
   *        For future-proofing, use a serializable callback (e.g. string/array).
   *        See also: Civi\Core\Resolver.
   *    - requires: array, list of required Angular modules.
   *    - basePages: array, unconditionally load this module onto the given Angular pages. [v4.7.21+]
   *      If omitted, default to "array('civicrm/a')" for backward compat.
   *      For a utility that should only be loaded on-demand, use "array()".
   *      For a utility that should be loaded in all pages use, "array('*')".
   *
   * ```
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
   * ```
   *
   * @return null
   *   the return value is ignored
   */
  public static function angularModules(&$angularModules) {
    $null = NULL;
    return self::singleton()->invoke(['angularModules'], $angularModules,
      $null, $null, $null, $null, $null,
      'civicrm_angularModules'
    );
  }

  /**
   * Alter the definition of some Angular HTML partials.
   *
   * @param \Civi\Angular\Manager $angular
   *
   * ```
   * function example_civicrm_alterAngular($angular) {
   *   $changeSet = \Civi\Angular\ChangeSet::create('mychanges')
   *     ->alterHtml('~/crmMailing/EditMailingCtrl/2step.html', function(phpQueryObject $doc) {
   *       $doc->find('[ng-form="crmMailingSubform"]')->attr('cat-stevens', 'ts(\'wild world\')');
   *     })
   *   );
   *   $angular->add($changeSet);
   * }
   * ```
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
    $null = NULL;
    return self::singleton()->invoke(['asset', 'params'],
      $asset, $params, $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['asset', 'params', 'mimeType', 'content'],
      $asset, $params, $mimeType, $content, $null, $null,
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
    Civi::dispatcher()->dispatch('hook_civicrm_caseChange', $event);
  }

  /**
   * Modify the CiviCRM container - add new services, parameters, extensions, etc.
   *
   * ```
   * use Symfony\Component\Config\Resource\FileResource;
   * use Symfony\Component\DependencyInjection\Definition;
   *
   * function mymodule_civicrm_container($container) {
   *   $container->addResource(new FileResource(__FILE__));
   *   $container->setDefinition('mysvc', new Definition('My\Class', array()));
   * }
   * ```
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
    $null = NULL;
    self::singleton()->invoke(['container'], $container, $null, $null, $null, $null, $null, 'civicrm_container');
  }

  /**
   * @param array $fileSearches CRM_Core_FileSearchInterface
   * @return mixed
   */
  public static function fileSearches(&$fileSearches) {
    $null = NULL;
    return self::singleton()->invoke(['fileSearches'], $fileSearches,
      $null, $null, $null, $null, $null,
      'civicrm_fileSearches'
    );
  }

  /**
   * Check system status.
   *
   * @param CRM_Utils_Check_Message[] $messages
   *   A list of messages regarding system status
   * @param array $statusNames
   *   If specified, only these checks are being requested and others should be skipped
   * @param bool $includeDisabled
   *   Run checks that have been explicitly disabled (default false)
   * @return mixed
   */
  public static function check(&$messages, $statusNames = [], $includeDisabled = FALSE) {
    $null = NULL;
    return self::singleton()->invoke(['messages', 'statusNames', 'includeDisabled'],
      $messages, $statusNames, $includeDisabled,
      $null, $null, $null,
      'civicrm_check'
    );
  }

  /**
   * This hook is called when a query string of the CSV Batch export is generated.
   *
   * @param string $query
   *
   * @return mixed
   */
  public static function batchQuery(&$query) {
    $null = NULL;
    return self::singleton()->invoke(['query'], $query, $null,
      $null, $null, $null, $null,
      'civicrm_batchQuery'
    );
  }

  /**
   * This hook is called to alter Deferred revenue item values just before they are
   * inserted in civicrm_financial_trxn table
   *
   * @param array $deferredRevenues
   * @param CRM_Contribute_BAO_Contribution|CRM_Contribute_DAO_Contribution $contributionDetails
   * @param bool $update
   * @param string $context
   *
   * @return mixed
   */
  public static function alterDeferredRevenueItems(&$deferredRevenues, $contributionDetails, $update, $context) {
    $null = NULL;
    return self::singleton()->invoke(['deferredRevenues', 'contributionDetails', 'update', 'context'], $deferredRevenues, $contributionDetails, $update, $context,
      $null, $null, 'civicrm_alterDeferredRevenueItems'
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
    $null = NULL;
    return self::singleton()->invoke(['results', 'items'], $results, $items,
      $null, $null, $null, $null,
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
    $null = NULL;
    self::singleton()->invoke(['list', 'region'], $list, $region,
      $null, $null, $null, $null,
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
    $null = NULL;
    self::singleton()->invoke(['filters', 'links'], $filters, $links, $null,
      $null, $null, $null,
      'civicrm_entityRefFilters'
    );
  }

  /**
   * Build a list of ECMAScript Modules (ESM's) that are available for auto-loading.
   *
   * Subscribers should assume that the $importMap will be cached and re-used.
   *
   * Example usage:
   *
   *    function my_civicrm_esmImportMap($importMap): void {
   *      $importMap->addPrefix('geolib/', E::LONG_NAME, 'packages/geometry-library-1.2.3/');
   *    }
   *
   * @param \Civi\Esm\ImportMap $importMap
   */
  public static function esmImportMap(\Civi\Esm\ImportMap $importMap): void {
    $null = NULL;
    self::singleton()->invoke(['importMap'], $importMap, $null, $null,
      $null, $null, $null,
      'civicrm_esmImportMap'
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
    $null = NULL;
    return self::singleton()->invoke(['skip'], $skip, $null,
      $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['geoProvider', 'values', 'xml'], $geoProvider, $values, $xml,
      $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['message'], $message, $null, $null, $null, $null, $null, 'civicrm_inboundSMS');
  }

  /**
   * This hook is called to modify api params of EntityRef form field
   *
   * @param array $params
   * @param string $formName
   * @return mixed
   */
  public static function alterEntityRefParams(&$params, $formName) {
    $null = NULL;
    return self::singleton()->invoke(['params', 'formName'], $params, $formName,
      $null, $null, $null, $null,
      'civicrm_alterEntityRefParams'
    );
  }

  /**
   * Should queue processing proceed.
   *
   * This hook is called when a background process attempts to claim an item from
   * the queue to process. A hook could alter the status from 'active' to denote
   * that the server is busy & hence no item should be claimed and processed at
   * this time.
   *
   * @param string $status
   *   This will be set to active. It is recommended hooks change it to 'paused'
   *   to prevent queue processing (although currently any value other than active
   *   is treated as inactive 'paused')
   * @param string $queueName
   *   The name of the queue. Equivalent to civicrm_queue.name
   * @param array $queueSpecification
   *   Array of information about the queue loaded from civicrm_queue.
   *
   * @see https://docs.civicrm.org/dev/en/latest/framework/queues/
   */
  public static function queueActive(string &$status, string $queueName, array $queueSpecification): void {
    $null = NULL;
    self::singleton()->invoke(['status', 'queueName', 'queueSpecification'], $status,
      $queueName, $queueSpecification, $null, $null, $null,
      'civicrm_queueActive'
    );
  }

  /**
   * Fire `hook_civicrm_queueRun_{$runner}`.
   *
   * This event only fires if these conditions are met:
   *
   * 1. The `$queue` has been persisted in `civicrm_queue`.
   * 2. The `$queue` has a `runner` property.
   * 3. The `$queue` has some pending tasks.
   * 4. The system has a queue-running agent.
   *
   * @param \CRM_Queue_Queue $queue
   * @param array $items
   *   List of claimed items which we may evaluate.
   * @param array $outcomes
   *   The outcomes of each task. One of 'ok', 'retry', 'fail'.
   *   Keys should match the keys in $items.
   * @return mixed
   * @throws CRM_Core_Exception
   */
  public static function queueRun(CRM_Queue_Queue $queue, array $items, &$outcomes) {
    $runner = $queue->getSpec('runner');
    if (empty($runner) || !preg_match(';^[A-Za-z0-9_]+$;', $runner)) {
      throw new \CRM_Core_Exception("Cannot autorun queue: " . $queue->getName());
    }
    $null = NULL;
    return self::singleton()->invoke(['queue', 'items', 'outcomes'], $queue, $items,
      $outcomes, $exception, $null, $null,
      'civicrm_queueRun_' . $runner
    );
  }

  /**
   * Fired if the status of a queue changes.
   *
   * @param \CRM_Queue_Queue $queue
   * @param string $status
   *   New status.
   *   Ex: 'completed', 'active', 'aborted'
   */
  public static function queueStatus(CRM_Queue_Queue $queue, string $status): void {
    $null = NULL;
    self::singleton()->invoke(['queue', 'status'], $queue, $status,
      $null, $null, $null, $null,
      'civicrm_queueStatus'
    );
  }

  /**
   * This is called if automatic execution of a queue-task fails.
   *
   * The `$outcome` may be modified. For example, you might inspect the $item and $exception -- and then
   * decide whether to 'retry', 'delete', or 'abort'.
   *
   * @param \CRM_Queue_Queue $queue
   * @param \CRM_Queue_DAO_QueueItem|\stdClass $item
   *   The enqueued item $item.
   *   In principle, this is the $item format determined by the queue, which includes `id` and `data`.
   *   In practice, it is typically an instance of `CRM_Queue_DAO_QueueItem`.
   * @param string $outcome
   *   The outcome of the task. Legal values:
   *   - 'retry': The task encountered a problem, and it should be retried.
   *   - 'delete': The task encountered a non-recoverable problem, and it should be deleted.
   *   - 'abort': The task encountered a non-recoverable problem, and the queue should be stopped.
   *   - 'ok': The task finished normally. (You won't generally see this, but it could be useful in some customizations.)
   *   The default outcome for task-errors is determined by the queue settings (`civicrm_queue.error`).
   * @param \Throwable|null $exception
   *   If the task failed, this is the cause of the failure.
   * @return mixed
   */
  public static function queueTaskError(CRM_Queue_Queue $queue, $item, &$outcome, ?Throwable $exception) {
    $null = NULL;
    return self::singleton()->invoke(['queue', 'item', 'outcome', 'exception'], $queue, $item,
      $outcome, $exception, $null, $null,
      'civicrm_queueTaskError'
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
    $null = NULL;
    return self::singleton()->invoke(['job', 'params'], $job, $params,
      $null, $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['job', 'params', 'result'], $job, $params, $result,
      $null, $null, $null,
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
    $null = NULL;
    return self::singleton()->invoke(['mailingObject', 'params', 'context'],
      $mailingObject, $criteria, $context,
      $null, $null, $null,
      'civicrm_alterMailingRecipients'
    );
  }

  /**
   * Allow Extensions to custom process IPN hook data such as sending Google Analytics information based on the IPN
   * @param array $IPNData - Array of IPN Data
   * @return mixed
   */
  public static function postIPNProcess(&$IPNData) {
    $null = NULL;
    return self::singleton()->invoke(['IPNData'],
      $IPNData, $null, $null,
      $null, $null, $null,
      'civicrm_postIPNProcess'
    );
  }

  /**
   * Allow extensions to modify the array of acceptable fields to be included on profiles
   * @param array $fields
   *   format is [Entity => array of DAO fields]
   * @return mixed
   */
  public static function alterUFFields(&$fields) {
    $null = NULL;
    return self::singleton()->invoke(['fields'],
      $fields, $null, $null,
      $null, $null, $null,
      'civicrm_alterUFFields'
    );
  }

  /**
   * This hook is called to alter Custom field value before its displayed.
   *
   * @param string $displayValue
   * @param mixed $value
   * @param int $entityId
   * @param array $fieldInfo
   *
   * @return mixed
   */
  public static function alterCustomFieldDisplayValue(&$displayValue, $value, $entityId, $fieldInfo) {
    $null = NULL;
    return self::singleton()->invoke(
      ['displayValue', 'value', 'entityId', 'fieldInfo'],
      $displayValue, $value, $entityId, $fieldInfo, $null,
      $null, 'civicrm_alterCustomFieldDisplayValue'
    );
  }

  /**
   * Allows an extension to override the checksum validation.
   * For example you may want to invalidate checksums that were sent out/forwarded by mistake. You could also
   * intercept and redirect to a different page in this case - eg. to say "sorry, you tried to use a compromised
   * checksum".
   *
   * @param int $contactID
   * @param string $checksum
   * @param bool $invalid
   *   Leave this at FALSE to allow the core code to perform validation. Set to TRUE to invalidate
   */
  public static function invalidateChecksum($contactID, $checksum, &$invalid) {
    $null = NULL;
    return self::singleton()->invoke(
      ['contactID', 'checksum', 'invalid'],
      $contactID, $checksum, $invalid,
      $null, $null, $null,
      'civicrm_invalidateChecksum'
    );
  }

  /**
   * Extensions can define new formats for relative date filter "tokens".
   * @param string $filter - the filter token, stored in civicrm_option_value
   * @return array|false
   *   An array with two elements: $dates['from'] and $dates['to'], or FALSE if the hook isn't in use.
   */
  public static function relativeDate($filter) {
    return self::singleton()->invoke(array('filter'), $filter, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, self::$_nullObject, 'civicrm_relativeDate');
  }

}
