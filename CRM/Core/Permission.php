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
 * This is the basic permission class wrapper
 */
class CRM_Core_Permission {

  /**
   * Static strings used to compose permissions.
   *
   * @const
   * @var string
   */
  const EDIT_GROUPS = 'edit contacts in ', VIEW_GROUPS = 'view contacts in ';

  /**
   * The various type of permissions.
   *
   * @var int
   */
  const EDIT = 1, VIEW = 2, DELETE = 3, CREATE = 4, SEARCH = 5, ALL = 6, ADMIN = 7;

  /**
   * A placeholder permission which always fails.
   */
  const ALWAYS_DENY_PERMISSION = "*always deny*";

  /**
   * A placeholder permission which always fails.
   */
  const ALWAYS_ALLOW_PERMISSION = "*always allow*";

  /**
   * Various authentication sources.
   *
   * @var int
   */
  const AUTH_SRC_UNKNOWN = 0, AUTH_SRC_CHECKSUM = 1, AUTH_SRC_SITEKEY = 2, AUTH_SRC_LOGIN = 4;

  /**
   * Get the maximum permission of the current user with respect to _any_ contact records.
   *
   * Note: This appears to be hydrated via `CRM_Core_Permission*::group()`, which appears to run in
   * many page-views, but I'm not certain that it's guaranteed.
   *
   * @return int|string|null
   *   Highest permission held by the current user.
   *   If the user has "edit" rights to at least 1 contact (via permission or ACL),
   *     then CRM_Core_Permission::EDIT.
   *   If the user has "view" rights to at least 1 contact (via permission or ACL),
   *     then CRM_Core_Permission::VIEW.
   *   Otherwise, NULL.
   * @see \CRM_Core_Permission_Base::group()
   */
  public static function getPermission() {
    $config = CRM_Core_Config::singleton();
    return $config->userPermissionClass->getPermission();
  }

  /**
   * Given a permission string or array, check for access requirements
   *
   * Ex 1: Must have 'access CiviCRM'
   * (string) 'access CiviCRM'
   *
   *  Ex 2: Must have 'access CiviCRM' and 'access AJAX API'
   *    ['access CiviCRM', 'access AJAX API']
   *
   * Ex 3: Must have 'access CiviCRM' or 'access AJAX API'
   *   [
   *     ['access CiviCRM', 'access AJAX API'],
   *   ],
   *
   * Ex 4: Must have 'access CiviCRM' or 'access AJAX API' AND 'access CiviEvent'
   *   [
   *     ['access CiviCRM', 'access AJAX API'],
   *     'access CiviEvent',
   *   ],
   *
   * Note that in permissions.php this is keyed by the action eg.
   *   (access Civi || access AJAX) && (access CiviEvent || access CiviContribute)
   *   'myaction' => [
   *     ['access CiviCRM', 'access AJAX API'],
   *     ['access CiviEvent', 'access CiviContribute']
   *   ],
   *
   * @param string|array $permissions
   *   The permission to check as an array or string -see examples.
   *
   * @param int $contactId
   *   Contact id to check permissions for. Defaults to current logged-in user.
   *
   * @return bool
   *   true if contact has permission(s), else false
   */
  public static function check($permissions, $contactId = NULL) {
    $permissions = (array) $permissions;
    $userId = CRM_Core_BAO_UFMatch::getUFId($contactId);

    /** @var CRM_Core_Permission_Temp $tempPerm */
    $tempPerm = CRM_Core_Config::singleton()->userPermissionTemp;

    foreach ($permissions as $permission) {
      if (is_array($permission)) {
        foreach ($permission as $orPerm) {
          if (self::check($orPerm, $contactId)) {
            //one of our 'or' permissions has succeeded - stop checking this permission
            return TRUE;
          }
        }
        //none of our our conditions was met
        return FALSE;
      }
      else {
        // This is an individual permission
        $impliedPermissions = self::getImpliedBy($permission);
        foreach ($impliedPermissions as $permissionOption) {
          $granted = CRM_Core_Config::singleton()->userPermissionClass->check($permissionOption, $userId);
          // Call the permission_check hook to permit dynamic escalation (CRM-19256)
          CRM_Utils_Hook::permission_check($permissionOption, $granted, $contactId);
          if ($granted) {
            break;
          }
        }

        if (
          !$granted
          && !($tempPerm && $tempPerm->check($permission))
        ) {
          //one of our 'and' conditions has not been met
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * Determine if any one of the permissions strings applies to current user.
   *
   * @param array $perms
   * @return bool
   */
  public static function checkAnyPerm($perms) {
    foreach ($perms as $perm) {
      if (CRM_Core_Permission::check($perm)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Given a group/role array, check for access requirements
   *
   * @param array $array
   *   The group/role to check.
   *
   * @return bool
   *   true if yes, else false
   */
  public static function checkGroupRole($array) {
    $config = CRM_Core_Config::singleton();
    return $config->userPermissionClass->checkGroupRole($array);
  }

  /**
   * Get the permissioned where clause for the user.
   *
   * @param int $type
   *   The type of permission needed.
   * @param array $tables
   *   (reference ) add the tables that are needed for the select clause.
   * @param array $whereTables
   *   (reference ) add the tables that are needed for the where clause.
   *
   * @return string
   *   the group where clause for this user
   */
  public static function getPermissionedStaticGroupClause($type, &$tables, &$whereTables) {
    $config = CRM_Core_Config::singleton();
    return $config->userPermissionClass->getPermissionedStaticGroupClause($type, $tables, $whereTables);
  }

  /**
   * Get all groups from database, filtered by permissions
   * for this user
   *
   * @param string $groupType
   *   Type of group(Access/Mailing).
   * @param bool $excludeHidden
   *   exclude hidden groups.
   *
   *
   * @return array
   *   array reference of all groups.
   */
  public static function group($groupType, $excludeHidden = TRUE) {
    $config = CRM_Core_Config::singleton();
    return $config->userPermissionClass->group($groupType, $excludeHidden);
  }

  /**
   * @param int $userId
   * @return bool
   */
  public static function customGroupAdmin($userId = NULL) {
    // check if user has all powerful permission
    // or administer civicrm permission (CRM-1905)
    if (self::check('access all custom data', $userId)) {
      return TRUE;
    }

    if (
      self::check('administer Multiple Organizations', $userId) &&
      self::isMultisiteEnabled()
    ) {
      return TRUE;
    }

    return self::check('administer CiviCRM data', $userId);
  }

  /**
   * Returns the ids of all custom groups the user is permitted to perform action of "$type"
   *
   * @param int $type
   *   Type of action e.g. CRM_Core_Permission::VIEW or CRM_Core_Permission::EDIT
   * @param bool $reset
   *   Flush cache
   * @param int $userId
   *
   * @return int[]
   */
  public static function customGroup($type = CRM_Core_Permission::VIEW, $reset = FALSE, $userId = NULL) {
    $customGroups = CRM_Core_BAO_CustomGroup::getAll();
    // Hook expects a flat array of [id => name]
    $customGroups = array_combine(array_keys($customGroups), array_column($customGroups, 'name'));

    // Administrators and users with 'access all custom data' can see all custom groups.
    if (self::customGroupAdmin($userId)) {
      return array_keys($customGroups);
    }

    // By default, users without 'access all custom data' are permitted to see no groups.
    $allowedGroups = [];

    // Allow ACLs and hooks to grant permissions to certain groups.
    return CRM_ACL_API::group($type, $userId, 'civicrm_custom_group', $customGroups, $allowedGroups);
  }

  /**
   * @param int $type
   * @param string|null $prefix
   * @param bool $reset
   *
   * @return string
   */
  public static function customGroupClause($type = CRM_Core_Permission::VIEW, $prefix = NULL, $reset = FALSE) {
    if (self::customGroupAdmin()) {
      return ' ( 1 ) ';
    }

    $groups = self::customGroup($type, $reset);
    if (empty($groups)) {
      return ' ( 0 ) ';
    }
    else {
      return "{$prefix}id IN ( " . implode(',', $groups) . ' ) ';
    }
  }

  /**
   * @param int $gid
   * @param int $type
   *
   * @return bool
   */
  public static function ufGroupValid($gid, $type = CRM_Core_Permission::VIEW) {
    if (empty($gid)) {
      return TRUE;
    }

    $groups = self::ufGroup($type);
    return !empty($groups) && in_array($gid, $groups);
  }

  /**
   * @param int $type
   *
   * @return array
   */
  public static function ufGroup($type = CRM_Core_Permission::VIEW) {
    $ufGroups = CRM_Core_DAO_UFField::buildOptions('uf_group_id');

    $allGroups = array_keys($ufGroups);

    // check if user has all powerful permission
    if (self::check('profile listings and forms')) {
      return $allGroups;
    }

    switch ($type) {
      case CRM_Core_Permission::VIEW:
        if (self::check('profile view')) {
          return $allGroups;
        }
        break;

      case CRM_Core_Permission::CREATE:
        if (self::check('profile create')) {
          return $allGroups;
        }
        break;

      case CRM_Core_Permission::EDIT:
        if (self::check('profile edit')) {
          return $allGroups;
        }
        break;

      case CRM_Core_Permission::SEARCH:
        if (self::check('profile listings')) {
          return $allGroups;
        }
        break;
    }

    return CRM_ACL_API::group($type, NULL, 'civicrm_uf_group', $ufGroups);
  }

  /**
   * @param int $type
   * @param string $prefix
   * @param bool $returnUFGroupIds
   *
   * @return array|string
   */
  public static function ufGroupClause($type = CRM_Core_Permission::VIEW, $prefix = NULL, $returnUFGroupIds = FALSE) {
    $groups = self::ufGroup($type);
    if ($returnUFGroupIds) {
      return $groups;
    }
    elseif (empty($groups)) {
      return ' ( 0 ) ';
    }
    else {
      return "{$prefix}id IN ( " . implode(',', $groups) . ' ) ';
    }
  }

  /**
   * @param int $type
   * @param int $eventID
   * @param string $context
   *
   * @return array|null
   */
  public static function event($type = CRM_Core_Permission::VIEW, $eventID = NULL, $context = '') {
    if (!empty($context)) {
      if (CRM_Core_Permission::check($context)) {
        return TRUE;
      }
    }
    $events = CRM_Event_PseudoConstant::event(NULL, TRUE);
    $includeEvents = [];

    // check if user has all powerful permission
    if (self::check('register for events')) {
      $includeEvents = array_keys($events);
    }

    if ($type == CRM_Core_Permission::VIEW &&
      self::check('view event info')
    ) {
      $includeEvents = array_keys($events);
    }

    $permissionedEvents = CRM_ACL_API::group($type, NULL, 'civicrm_event', $events, $includeEvents);
    if (!$eventID) {
      return $permissionedEvents;
    }
    if (!empty($permissionedEvents)) {
      return array_search($eventID, $permissionedEvents) === FALSE ? NULL : $eventID;
    }
    return NULL;
  }

  /**
   * @param int $type
   * @param null $prefix
   *
   * @return string
   */
  public static function eventClause($type = CRM_Core_Permission::VIEW, $prefix = NULL) {
    $events = self::event($type);
    if (empty($events)) {
      return ' ( 0 ) ';
    }
    else {
      return "{$prefix}id IN ( " . implode(',', $events) . ' ) ';
    }
  }

  /**
   * Checks that component is enabled and optionally that user has basic perm.
   *
   * @param string $module
   *   Specifies the name of the CiviCRM component.
   * @param bool $checkPermission
   *   Check not only that module is enabled, but that user has necessary
   *   permission.
   * @param bool $requireAllCasesPermOnCiviCase
   *   Significant only if $module == CiviCase
   *   Require "access all cases and activities", not just
   *   "access my cases and activities".
   *
   * @return bool
   *   Access to specified $module is granted.
   */
  public static function access($module, $checkPermission = TRUE, $requireAllCasesPermOnCiviCase = FALSE) {
    if (!CRM_Core_Component::isEnabled($module)) {
      return FALSE;
    }

    if ($checkPermission) {
      switch ($module) {
        case 'CiviCase':
          $access_all_cases = CRM_Core_Permission::check("access all cases and activities");
          $access_my_cases  = CRM_Core_Permission::check("access my cases and activities");
          return $access_all_cases || (!$requireAllCasesPermOnCiviCase && $access_my_cases);

        case 'CiviCampaign':
          return CRM_Core_Permission::check("administer $module");

        default:
          return CRM_Core_Permission::check("access $module");
      }
    }

    return TRUE;
  }

  /**
   * Check permissions for delete and edit actions.
   *
   * @param string $module
   *   Component name.
   * @param int $action
   *   Action to be check across component.
   *
   *
   * @return bool
   */
  public static function checkActionPermission($module, $action) {
    //check delete related permissions.
    if ($action & CRM_Core_Action::DELETE) {
      $permissionName = "delete in $module";
    }
    else {
      $editPermissions = [
        'CiviEvent' => 'edit event participants',
        'CiviMember' => 'edit memberships',
        'CiviPledge' => 'edit pledges',
        'CiviContribute' => 'edit contributions',
        'CiviMail' => 'access CiviMail',
      ];
      $permissionName = $editPermissions[$module] ?? NULL;
    }

    if ($module == 'CiviCase' && !$permissionName) {
      return CRM_Case_BAO_Case::accessCiviCase();
    }
    else {
      //check for permission.
      return CRM_Core_Permission::check($permissionName);
    }
  }

  /**
   * @param $args
   * @param string $op
   *
   * @return bool
   */
  public static function checkMenu(&$args, $op = 'and') {
    if (!is_array($args)) {
      return $args;
    }
    foreach ($args as $str) {
      $res = CRM_Core_Permission::check($str);
      if ($op == 'or' && $res) {
        return TRUE;
      }
      elseif ($op == 'and' && !$res) {
        return FALSE;
      }
    }
    return ($op == 'or') ? FALSE : TRUE;
  }

  /**
   * @param $item
   *
   * @return bool|mixed
   * @throws Exception
   */
  public static function checkMenuItem(&$item) {
    if (!array_key_exists('access_callback', $item)) {
      CRM_Core_Error::backtrace();
      throw new CRM_Core_Exception('Missing Access Callback key in menu item');
    }

    // if component_id is present, ensure it is enabled
    if (!empty($item['component_id'])) {
      $componentName = CRM_Core_Component::getComponentName($item['component_id']);
      if (!$componentName || !CRM_Core_Component::isEnabled($componentName)) {
        return FALSE;
      }
    }

    // the following is imitating drupal 6 code in includes/menu.inc
    if (empty($item['access_callback']) ||
      is_numeric($item['access_callback'])
    ) {
      return (bool) $item['access_callback'];
    }

    // check whether the following Ajax requests submitted the right key
    // FIXME: this should be integrated into ACLs proper
    if (($item['page_type'] ?? NULL) == 3) {
      if (!CRM_Core_Key::validate($_REQUEST['key'], $item['path'])) {
        return FALSE;
      }
    }

    // check if callback is for checkMenu, if so optimize it
    if (is_array($item['access_callback']) &&
      $item['access_callback'][0] == 'CRM_Core_Permission' &&
      $item['access_callback'][1] == 'checkMenu'
    ) {
      $op = CRM_Utils_Array::value(1, $item['access_arguments'], 'and');
      return self::checkMenu($item['access_arguments'][0], $op);
    }
    else {
      return call_user_func_array($item['access_callback'], $item['access_arguments']);
    }
  }

  /**
   * @param bool $includeDisabled
   *   Include permissions from disabled components/settings.
   * @param bool $returnAssociative
   *   If true, returns arrays with keys: [label, description, disabled, implies, implied_by].
   *   If false, returns strings (label only).
   *
   * @return array[]|string[]
   * @throws RuntimeException
   */
  public static function basicPermissions($includeDisabled = FALSE, $returnAssociative = FALSE): array {
    $permissions = Civi::$statics[__CLASS__][__FUNCTION__] ??= self::assembleBasicPermissions();
    if (!$includeDisabled) {
      $permissions = array_filter($permissions, fn($permission) => empty($permission['disabled']));
    }
    if ($returnAssociative) {
      return $permissions;
    }
    return array_combine(array_keys($permissions), array_column($permissions, 'label'));
  }

  /**
   * @return array
   * @throws RuntimeException
   */
  protected static function assembleBasicPermissions(): array {
    $permissions = self::getCoreAndComponentPermissions();
    $module_permissions = CRM_Core_Config::singleton()->userPermissionClass->getAllModulePermissions();
    $allPermissions = array_merge($permissions, $module_permissions);
    // Propagate implied_by permissions to their parents
    foreach ($allPermissions as $name => $permission) {
      foreach ($permission['implied_by'] ?? [] as $parent) {
        if (isset($allPermissions[$parent])) {
          $allPermissions[$parent]['implies'][] = $name;
          $allPermissions[$name]['parent'] = $parent;
        }
      }
    }
    // Propagate implied permissions to their children
    foreach ($allPermissions as $name => $permission) {
      if (!empty($permission['implies'])) {
        self::setImpliedBy([$name], $permission['implies'], $allPermissions);
      }
    }
    return $allPermissions;
  }

  /**
   * Recursively sets the 'implied_by' value for every sub-permission,
   * based on the 'implies' declaration in meta-permissions.
   *
   * @param array $metaPermissions
   * @param array $subPermissions
   * @param array $allPermissions
   * @param int $recursionLevel
   */
  protected static function setImpliedBy(array $metaPermissions, array $subPermissions, array &$allPermissions, int $recursionLevel = 0): void {
    foreach ($subPermissions as $name) {
      if (isset($allPermissions[$name])) {
        $allPermissions[$name]['implied_by'] = array_unique(array_merge($allPermissions[$name]['implied_by'] ?? [], $metaPermissions));
        if (!$recursionLevel) {
          $allPermissions[$name]['parent'] = $metaPermissions[0];
        }
        if (!empty($allPermissions[$name]['implies'])) {
          self::setImpliedBy(array_merge([$name], $metaPermissions), $allPermissions[$name]['implies'], $allPermissions, $recursionLevel + 1);
        }
      }
    }
  }

  /**
   * @return array
   */
  public static function getAnonymousPermissionsWarnings() {
    static $permissions = [];
    if (empty($permissions)) {
      $permissions = [
        'administer CiviCRM',
      ];
      $components = CRM_Core_Component::getComponents();
      foreach ($components as $comp) {
        if (!method_exists($comp, 'getAnonymousPermissionWarnings')) {
          continue;
        }
        $permissions = array_merge($permissions, $comp->getAnonymousPermissionWarnings());
      }
    }
    return $permissions;
  }

  /**
   * @param $anonymous_perms
   *
   * @return array
   */
  public static function validateForPermissionWarnings($anonymous_perms) {
    return array_intersect($anonymous_perms, self::getAnonymousPermissionsWarnings());
  }

  /**
   * Get core permissions.
   *
   * @return array
   */
  public static function getCorePermissions() {
    $prefix = ts('CiviCRM') . ': ';
    $permissions = [
      'add contacts' => [
        'label' => $prefix . ts('add contacts'),
        'description' => ts('Create a new contact record in CiviCRM'),
      ],
      'view all contacts' => [
        'label' => $prefix . ts('view all contacts'),
        'description' => ts('View ANY CONTACT in the CiviCRM database, export contact info and perform activities such as Send Email, Phone Call, etc.'),
        'implies' => [
          'view my contact',
        ],
      ],
      'edit all contacts' => [
        'label' => $prefix . ts('edit all contacts'),
        'description' => ts('View, Edit and Delete ANY CONTACT in the CiviCRM database; Create and edit relationships, tags and other info about the contacts'),
        'implies' => [
          'view all contacts',
          'edit my contact',
        ],
      ],
      'view my contact' => [
        'label' => $prefix . ts('view my contact'),
      ],
      'edit my contact' => [
        'label' => $prefix . ts('edit my contact'),
      ],
      'delete contacts' => [
        'label' => $prefix . ts('delete contacts'),
      ],
      'access deleted contacts' => [
        'label' => $prefix . ts('access deleted contacts'),
        'description' => ts('Access contacts in the trash'),
      ],
      'import contacts' => [
        'label' => $prefix . ts('import contacts'),
        'description' => ts('Import contacts and activities'),
      ],
      'import SQL datasource' => [
        'label' => $prefix . ts('import SQL datasource'),
        'description' => ts('When importing, consume data directly from a SQL datasource'),
      ],
      'edit groups' => [
        'label' => $prefix . ts('edit groups'),
        'description' => ts('Create new groups, edit group settings (e.g. group name, visibility...), delete groups'),
      ],
      'administer CiviCRM' => [
        'label' => $prefix . ts('administer CiviCRM'),
        'description' => ts('Perform all tasks in the Administer CiviCRM control panel and Import Contacts'),
        'implies' => [
          'administer CiviCRM system',
          'administer CiviCRM data',
          'access CiviCRM',
        ],
      ],
      'skip IDS check' => [
        'label' => $prefix . ts('skip IDS check'),
        'description' => ts('Warning: Give to trusted roles only; this permission has security implications. IDS system is bypassed for users with this permission. Prevents false errors for admin users.'),
      ],
      'access uploaded files' => [
        'label' => $prefix . ts('access uploaded files'),
        'description' => ts('View / download files including images and photos'),
      ],
      'profile listings and forms' => [
        'label' => $prefix . ts('profile listings and forms'),
        'description' => ts('Warning: Give to trusted roles only; this permission has privacy implications. Add/edit data in online forms and access public searchable directories.'),
        'implies' => [
          'profile listings',
        ],
      ],
      'profile listings' => [
        'label' => $prefix . ts('profile listings'),
        'description' => ts('Warning: Give to trusted roles only; this permission has privacy implications. Access public searchable directories.'),
      ],
      'profile create' => [
        'label' => $prefix . ts('profile create'),
        'description' => ts('Add data in a profile form.'),
      ],
      'profile edit' => [
        'label' => $prefix . ts('profile edit'),
        'description' => ts('Edit data in a profile form.'),
      ],
      'profile view' => [
        'label' => $prefix . ts('profile view'),
        'description' => ts('View data in a profile.'),
      ],
      'access all custom data' => [
        'label' => $prefix . ts('access all custom data'),
        'description' => ts('View all custom fields regardless of ACL rules'),
      ],
      'view all activities' => [
        'label' => $prefix . ts('view all activities'),
        'description' => ts('View all activities (for visible contacts)'),
      ],
      'delete activities' => [
        'label' => $prefix . ts('Delete activities'),
      ],
      'edit inbound email basic information' => [
        'label' => $prefix . ts('edit inbound email basic information'),
        'description' => ts('Edit all inbound email activities (for visible contacts) basic information. Content editing not allowed.'),
      ],
      'edit inbound email basic information and content' => [
        'label' => $prefix . ts('edit inbound email basic information and content'),
        'description' => ts('Edit all inbound email activities (for visible contacts) basic information and content.'),
      ],
      'access CiviCRM' => [
        'label' => $prefix . ts('access CiviCRM backend and API'),
        'description' => ts('Master control for access to the main CiviCRM backend and API. Give to trusted roles only.'),
      ],
      'access Contact Dashboard' => [
        'label' => $prefix . ts('access Contact Dashboard'),
        'description' => ts('View Contact Dashboard (for themselves and visible contacts)'),
      ],
      'translate CiviCRM' => [
        'label' => $prefix . ts('translate CiviCRM'),
        'description' => ts('Allow User to enable multilingual'),
      ],
      'manage tags' => [
        'label' => $prefix . ts('manage tags'),
        'description' => ts('Create and rename tags'),
      ],
      'administer reserved groups' => [
        'label' => $prefix . ts('administer reserved groups'),
        'description' => ts('Edit and disable Reserved Groups (Needs Edit Groups)'),
      ],
      'administer Tagsets' => [
        'label' => $prefix . ts('administer Tagsets'),
      ],
      'administer reserved tags' => [
        'label' => $prefix . ts('administer reserved tags'),
      ],
      'administer queues' => [
        'label' => $prefix . ts('administer queues'),
        'description' => ts('Initialize, browse, and cancel background processing queues'),
        // At time of writing, we have specifically omitted the ability to edit fine-grained
        // data about specific queue-tasks. Tasks are usually defined as PHP callables...
        // and one should hesitate before allowing open-ended edits of PHP callables.
        // However, it seems fine for web-admins to browse and cancel these things.
      ],
      'administer dedupe rules' => [
        'label' => $prefix . ts('administer dedupe rules'),
        'description' => ts('Create and edit rules, change the supervised and unsupervised rules'),
      ],
      'merge duplicate contacts' => [
        'label' => $prefix . ts('merge duplicate contacts'),
        'description' => ts('Delete Contacts must also be granted in order for this to work.'),
      ],
      'force merge duplicate contacts' => [
        'label' => $prefix . ts('force merge duplicate contacts'),
        'description' => ts('Delete Contacts must also be granted in order for this to work.'),
      ],
      'view debug output' => [
        'label' => $prefix . ts('view debug output'),
        'description' => ts('View results of debug and backtrace'),
      ],

      'view all notes' => [
        'label' => $prefix . ts('view all notes'),
        'description' => ts("View notes (for visible contacts) even if they're marked author only"),
      ],
      'add contact notes' => [
        'label' => $prefix . ts('add contact notes'),
        'description' => ts("Create notes for contacts"),
      ],
      'access AJAX API' => [
        'label' => $prefix . ts('access AJAX API'),
        'description' => ts('Allow API access even if Access CiviCRM is not granted'),
      ],
      'access contact reference fields' => [
        'label' => $prefix . ts('access contact reference fields'),
        'description' => ts('Allow entering data into contact reference fields'),
      ],
      'create manual batch' => [
        'label' => $prefix . ts('create manual batch'),
        'description' => ts('Create an accounting batch (with Access to CiviContribute and View Own/All Manual Batches)'),
      ],
      'edit own manual batches' => [
        'label' => $prefix . ts('edit own manual batches'),
        'description' => ts('Edit accounting batches created by user'),
      ],
      'edit all manual batches' => [
        'label' => $prefix . ts('edit all manual batches'),
        'description' => ts('Edit all accounting batches'),
        'implies' => [
          'view all manual batches',
          'edit own manual batches',
        ],
      ],
      'close own manual batches' => [
        'label' => $prefix . ts('close own manual batches'),
        'description' => ts('Close accounting batches created by user (with Access to CiviContribute)'),
      ],
      'close all manual batches' => [
        'label' => $prefix . ts('close all manual batches'),
        'description' => ts('Close all accounting batches (with Access to CiviContribute)'),
        'implies' => [
          'close own manual batches',
        ],
      ],
      'reopen own manual batches' => [
        'label' => $prefix . ts('reopen own manual batches'),
        'description' => ts('Reopen accounting batches created by user (with Access to CiviContribute)'),
      ],
      'reopen all manual batches' => [
        'label' => $prefix . ts('reopen all manual batches'),
        'description' => ts('Reopen all accounting batches (with Access to CiviContribute)'),
        'implies' => [
          'reopen own manual batches',
        ],
      ],
      'view own manual batches' => [
        'label' => $prefix . ts('view own manual batches'),
        'description' => ts('View accounting batches created by user (with Access to CiviContribute)'),
      ],
      'view all manual batches' => [
        'label' => $prefix . ts('view all manual batches'),
        'description' => ts('View all accounting batches (with Access to CiviContribute)'),
        'implies' => [
          'view own manual batches',
        ],
      ],
      'delete own manual batches' => [
        'label' => $prefix . ts('delete own manual batches'),
        'description' => ts('Delete accounting batches created by user'),
      ],
      'delete all manual batches' => [
        'label' => $prefix . ts('delete all manual batches'),
        'description' => ts('Delete all accounting batches'),
        'implies' => [
          'delete own manual batches',
        ],
      ],
      'export own manual batches' => [
        'label' => $prefix . ts('export own manual batches'),
        'description' => ts('Export accounting batches created by user'),
      ],
      'export all manual batches' => [
        'label' => $prefix . ts('export all manual batches'),
        'description' => ts('Export all accounting batches'),
        'implies' => [
          'export own manual batches',
        ],
      ],
      'administer payment processors' => [
        'label' => $prefix . ts('administer payment processors'),
        'description' => ts('Add, Update, or Disable Payment Processors'),
      ],
      'render templates' => [
        'label' => $prefix . ts('render templates'),
        'description' => ts('Render open-ended template content. (Additional constraints may apply to autoloaded records and specific notations.)'),
      ],
      'edit message templates' => [
        'label' => $prefix . ts('edit message templates'),
      ],
      'edit system workflow message templates' => [
        'label' => $prefix . ts('edit system workflow message templates'),
      ],
      'edit user-driven message templates' => [
        'label' => $prefix . ts('edit user-driven message templates'),
      ],
      'view my invoices' => [
        'label' => $prefix . ts('view my invoices'),
        'description' => ts('Allow users to view/ download their own invoices'),
      ],
      'edit api keys' => [
        'label' => $prefix . ts('edit api keys'),
        'description' => ts('Edit API keys'),
        'implies' => [
          'edit own api keys',
        ],
      ],
      'edit own api keys' => [
        'label' => $prefix . ts('edit own api keys'),
        'description' => ts("Edit user's own API keys"),
      ],
      'send SMS' => [
        'label' => $prefix . ts('send SMS'),
        'description' => ts('Send an SMS'),
      ],
      'administer CiviCRM system' => [
        'label' => $prefix . ts('administer CiviCRM System'),
        'description' => ts('Perform all system administration tasks in CiviCRM'),
        'implies' => [
          'edit system workflow message templates',
        ],
      ],
      'administer CiviCRM data' => [
        'label' => $prefix . ts('administer CiviCRM Data'),
        'description' => ts('Permit altering all restricted data options'),
        'implies' => [
          'edit message templates',
          'administer dedupe rules',
        ],
      ],
      // This is a very special permission that supersedes all others;
      // it's the equivalent of user 1 in Drupal.
      'all CiviCRM permissions and ACLs' => [
        'label' => $prefix . ts('all CiviCRM permissions and ACLs'),
        'description' => ts('Administer and use CiviCRM bypassing any other permission or ACL checks and enabling the creation of displays and forms that allow others to bypass checks. This permission should be given out with care'),
        // This line is here more as a bit of documentation (so it will show in `Civi\Api4\Permission::get()`).
        // The functionality that actually propagates this permission into all others
        // is in `self::getImpliedBy`.
        'implies' => ['*'],
      ],
    ];
    if (self::isMultisiteEnabled()) {
      // This could arguably be moved to the multisite extension but
      // within core it does permit editing group-organization records.
      $permissions['administer Multiple Organizations'] = [
        'label' => $prefix . ts('administer Multiple Organizations'),
        'description' => ts('Administer multiple organizations. In practice this allows editing the group organization link'),
      ];
    }
    return $permissions;
  }

  /**
   * Get all permissions that would grant the given permission.
   *
   * This always includes the permission itself and the super 'all CiviCRM permissions and ACLs'
   * plus any meta-permissions that imply this one.
   *
   * @param string $permissionName
   * @return array
   */
  private static function getImpliedBy(string $permissionName): array {
    if (in_array($permissionName[0], ['@', '*'], TRUE)) {
      // Special permissions like '*always deny*' - see DynamicFKAuthorizationTest.
      // Also '@afform - see AfformUsageTest.
      return [$permissionName];
    }
    try {
      $permission = self::basicPermissions(TRUE, TRUE)[$permissionName] ?? NULL;
      $impliedPermissions = array_merge([$permissionName], $permission['implied_by'] ?? []);
      // Permission for a disabled component: always deny
      if (!empty($permission['disabled'])) {
        return [self::ALWAYS_DENY_PERMISSION];
      }
      // If it's a CiviCRM permission, then it's also implied by the master permission
      elseif ($permission) {
        $impliedPermissions[] = 'all CiviCRM permissions and ACLs';
      }
    }
    // This could happen early in the boot-cycle or during upgrade
    catch (RuntimeException $e) {
      $impliedPermissions = [$permissionName, 'all CiviCRM permissions and ACLs'];
    }
    return $impliedPermissions;
  }

  /**
   * For each entity provides an array of permissions required for each action
   *
   * The action is the array key, possible values:
   *  * create: applies to create (with no id in params)
   *  * update: applies to update, setvalue, create (with id in params)
   *  * get: applies to getcount, getsingle, getvalue and other gets
   *  * delete: applies to delete, replace
   *  * meta: applies to getfields, getoptions, getspec
   *  * default: catch-all for anything not declared
   *
   *  Note: some APIs declare other actions as well
   *
   * Permissions should use arrays for AND and arrays of arrays for OR
   * @see CRM_Core_Permission::check
   *
   * @return array of permissions
   */
  public static function getEntityActionPermissions() {
    $permissions = [];
    // These are the default permissions - if any entity does not declare permissions for a given action,
    // (or the entity does not declare permissions at all) - then the action will be used from here
    $permissions['default'] = [
      // applies to getfields, getoptions, etc.
      'meta' => ['access CiviCRM'],
      // catch-all, applies to create, get, delete, etc.
      // If an entity declares it's own 'default' action it will override this one
      'default' => ['administer CiviCRM'],
    ];

    // Note: Additional permissions in DynamicFKAuthorization
    $permissions['attachment'] = [
      'default' => [
        ['access CiviCRM', 'access AJAX API'],
      ],
    ];

    // Contact permissions
    $permissions['contact'] = [
      'create' => [
        'access CiviCRM',
        'add contacts',
      ],
      'delete' => [
        'access CiviCRM',
        'delete contacts',
      ],
      // managed by query object
      'get' => [],
      // managed by _civicrm_api3_check_edit_permissions
      'update' => [],
      'duplicatecheck' => [
        'access CiviCRM',
      ],
      'merge' => ['merge duplicate contacts'],
    ];

    $permissions['dedupe'] = [
      'getduplicates' => ['access CiviCRM'],
      'getstatistics' => ['access CiviCRM'],
    ];

    // CRM-16963 - Permissions for country.
    $permissions['country'] = [
      'get' => [
        'access CiviCRM',
      ],
      'default' => [
        'administer CiviCRM',
      ],
    ];

    // Contact-related data permissions.
    $permissions['address'] = [
      // get is managed by BAO::addSelectWhereClause
      // create/delete are managed by _civicrm_api3_check_edit_permissions
      'default' => [],
    ];
    $permissions['email'] = $permissions['address'];
    $permissions['phone'] = $permissions['address'];
    $permissions['website'] = $permissions['address'];
    $permissions['im'] = $permissions['address'];
    $permissions['open_i_d'] = $permissions['address'];

    // Also managed by ACLs - CRM-19448
    $permissions['entity_tag'] = ['default' => []];
    $permissions['note'] = $permissions['entity_tag'];

    // Allow non-admins to get and create tags to support tagset widget
    // Delete is still reserved for admins
    $permissions['tag'] = [
      'get' => ['access CiviCRM'],
      'create' => ['access CiviCRM'],
      'update' => ['access CiviCRM'],
    ];

    //relationship permissions
    $permissions['relationship'] = [
      // get is managed by BAO::addSelectWhereClause
      'get' => [],
      'delete' => [
        'access CiviCRM',
        'edit all contacts',
      ],
      'default' => [
        'access CiviCRM',
        'edit all contacts',
      ],
    ];
    // Readonly relationship_cache table
    $permissions['relationship_cache'] = [
      // get is managed by BAO::addSelectWhereClause
      'get' => [],
    ];

    // CRM-17741 - Permissions for RelationshipType.
    $permissions['relationship_type'] = [
      'get' => [
        'access CiviCRM',
      ],
      'default' => [
        'administer CiviCRM',
      ],
    ];

    // Activity permissions
    $permissions['activity'] = [
      'delete' => [
        'access CiviCRM',
        'delete activities',
      ],
      'get' => [
        'access CiviCRM',
        // Note that view all activities is also required within the api
        // if the id is not passed in. Where the id is passed in the activity
        // specific check functions are used and tested.
      ],
      'default' => [
        'access CiviCRM',
        'view all activities',
      ],
    ];
    $permissions['activity_contact'] = $permissions['activity'];

    // Case permissions
    $permissions['case'] = [
      'create' => [
        'access CiviCRM',
        'add cases',
      ],
      'delete' => [
        'access CiviCRM',
        'delete in CiviCase',
      ],
      'restore' => [
        'administer CiviCase',
      ],
      'merge' => [
        'administer CiviCase',
      ],
      'default' => [
        // At minimum the user needs one of the following. Finer-grained access is controlled by CRM_Case_BAO_Case::addSelectWhereClause
        ['access my cases and activities', 'access all cases and activities'],
      ],
    ];
    $permissions['case_contact'] = $permissions['case'];
    $permissions['case_activity'] = $permissions['case'];

    $permissions['case_type'] = [
      'default' => ['administer CiviCase'],
      'get' => [
        // nested array = OR
        ['access my cases and activities', 'access all cases and activities'],
      ],
    ];

    // Campaign permissions
    $permissions['campaign'] = [
      'get' => ['access CiviCRM'],
      'default' => [
        // nested array = OR
        ['administer CiviCampaign', 'manage campaign'],
      ],
    ];
    $permissions['survey'] = $permissions['campaign'];

    // Financial permissions
    $permissions['contribution'] = [
      'get' => [
        'access CiviCRM',
        'access CiviContribute',
      ],
      'delete' => [
        'access CiviCRM',
        'access CiviContribute',
        'delete in CiviContribute',
      ],
      'completetransaction' => [
        'edit contributions',
      ],
      'default' => [
        'access CiviCRM',
        'access CiviContribute',
        'edit contributions',
      ],
    ];
    $permissions['line_item'] = $permissions['contribution'];
    $permissions['product'] = $permissions['premiums'] = $permissions['premiums_product'] = $permissions['contribution'];
    // Add 'make online contributions' permissions to allow anon users to access these entities
    // (permissions are controlled by financial ACLs)
    $permissions['product']['get'] = $permissions['premium']['get'] = $permissions['premiums_product']['get'] = [['access CiviCRM', 'access CiviContribute', 'make online contributions']];
    $permissions['product']['meta'] = $permissions['premium']['meta'] = $permissions['premiums_product']['meta'] = [['access CiviCRM', 'access CiviContribute', 'make online contributions']];

    $permissions['financial_item'] = $permissions['contribution'];
    $permissions['financial_type']['get'] = $permissions['contribution']['get'];
    $permissions['entity_financial_account']['get'] = $permissions['contribution']['get'];
    $permissions['financial_account']['get'] = $permissions['contribution']['get'];
    $permissions['financial_trxn']['get'] = $permissions['contribution']['get'];
    $permissions['contribution_soft'] = $permissions['contribution'];

    // Payment permissions
    $permissions['payment'] = [
      'get' => [
        'access CiviCRM',
        'access CiviContribute',
      ],
      'delete' => [
        'access CiviCRM',
        'access CiviContribute',
        'delete in CiviContribute',
      ],
      'cancel' => [
        'access CiviCRM',
        'access CiviContribute',
        'edit contributions',
      ],
      'create' => [
        'access CiviCRM',
        'access CiviContribute',
        'edit contributions',
      ],
      'default' => [
        'access CiviCRM',
        'access CiviContribute',
        'edit contributions',
      ],
    ];
    $permissions['contribution_recur'] = $permissions['payment'];

    // Custom field permissions
    $permissions['custom_field'] = [
      'default' => [
        'administer CiviCRM',
        'access all custom data',
      ],
    ];
    $permissions['custom_group'] = $permissions['custom_field'];

    // Event permissions
    $permissions['event'] = [
      'create' => [
        'access CiviCRM',
        'access CiviEvent',
        'edit all events',
      ],
      'delete' => [
        'access CiviCRM',
        'access CiviEvent',
        'delete in CiviEvent',
      ],
      'get' => [
        'access CiviCRM',
        'access CiviEvent',
        'view event info',
      ],
      'update' => [
        'access CiviCRM',
        'access CiviEvent',
        'edit all events',
      ],
    ];
    // Exception refers to dedupe_exception.
    $permissions['exception'] = [
      'default' => ['merge duplicate contacts'],
    ];

    $permissions['job'] = [
      'process_batch_merge' => ['merge duplicate contacts'],
    ];
    $permissions['job_log'] = ['default' => 'administer CiviCRM system'];
    $permissions['rule_group']['get'] = [['merge duplicate contacts', 'administer CiviCRM']];
    // Loc block is only used for events
    $permissions['loc_block'] = $permissions['event'];

    $permissions['state_province'] = [
      'get' => [
        'access CiviCRM',
      ],
    ];

    // Price sets are shared by several components, user needs access to at least one of them
    $permissions['price_set'] = $permissions['price_field'] = $permissions['price_field_value'] = $permissions['price_set_entity'] = [
      'default' => [
        ['access CiviEvent', 'access CiviContribute', 'access CiviMember'],
      ],
      'get' => [
        ['access CiviCRM', 'view event info', 'make online contributions'],
      ],
    ];

    // File permissions
    $permissions['file'] = [
      'default' => [
        'access CiviCRM',
        'access uploaded files',
      ],
    ];
    $permissions['files_by_entity'] = $permissions['file'];
    $permissions['entity_file'] = $permissions['file'];

    // Group permissions
    $permissions['group'] = [
      'get' => [
        'access CiviCRM',
      ],
      'default' => [
        'access CiviCRM',
        'edit groups',
      ],
    ];

    $permissions['group_nesting'] = $permissions['group'];
    $permissions['group_organization'] = $permissions['group'];

    // Note: The v3 GroupContact API is nonstandard and not easy to fix, so these permissions
    // are unnecessarily strict for v3. The v4 API overrides them.
    // @see Civi\Api4\GroupContact::permissions
    $permissions['group_contact'] = [
      'get' => [
        'access CiviCRM',
      ],
      'default' => [
        'access CiviCRM',
        'edit all contacts',
      ],
    ];

    // CiviMail Permissions
    $civiMailBasePerms = [
      // To get/preview/update, one must have least one of these perms:
      // Mailing API implementations enforce nuances of create/approve/schedule permissions.
      'access CiviMail',
      'create mailings',
      'schedule mailings',
      'approve mailings',
    ];
    $permissions['mailing'] = [
      'get' => [
        'access CiviCRM',
        $civiMailBasePerms,
      ],
      'delete' => [
        'access CiviCRM',
        $civiMailBasePerms,
        'delete in CiviMail',
      ],
      'submit' => [
        'access CiviCRM',
        ['access CiviMail', 'schedule mailings'],
      ],
      'default' => [
        'access CiviCRM',
        $civiMailBasePerms,
      ],
    ];
    $permissions['mailing_group'] = $permissions['mailing'];
    $permissions['mailing_job'] = $permissions['mailing'];
    $permissions['mailing_recipients'] = $permissions['mailing'];

    $permissions['mailing_a_b'] = [
      'get' => [
        'access CiviCRM',
        'access CiviMail',
      ],
      'delete' => [
        'access CiviCRM',
        'access CiviMail',
        'delete in CiviMail',
      ],
      'submit' => [
        'access CiviCRM',
        ['access CiviMail', 'schedule mailings'],
      ],
      'default' => [
        'access CiviCRM',
        'access CiviMail',
      ],
    ];

    // Membership permissions
    $permissions['membership'] = [
      'get' => [
        'access CiviCRM',
        'access CiviMember',
      ],
      'delete' => [
        'access CiviCRM',
        'access CiviMember',
        'delete in CiviMember',
      ],
      'default' => [
        'access CiviCRM',
        'access CiviMember',
        'edit memberships',
      ],
    ];
    $permissions['membership_status'] = $permissions['membership'];
    $permissions['membership_type'] = $permissions['membership'];
    $permissions['membership_payment'] = [
      'create' => [
        'access CiviCRM',
        'access CiviMember',
        'edit memberships',
        'access CiviContribute',
        'edit contributions',
      ],
      'delete' => [
        'access CiviCRM',
        'access CiviMember',
        'delete in CiviMember',
        'access CiviContribute',
        'delete in CiviContribute',
      ],
      'get' => [
        'access CiviCRM',
        'access CiviMember',
        'access CiviContribute',
      ],
      'update' => [
        'access CiviCRM',
        'access CiviMember',
        'edit memberships',
        'access CiviContribute',
        'edit contributions',
      ],
    ];

    // Participant permissions
    $permissions['participant'] = [
      'create' => [
        'access CiviCRM',
        'access CiviEvent',
        'register for events',
      ],
      'delete' => [
        'access CiviCRM',
        'access CiviEvent',
        'edit event participants',
      ],
      'get' => [
        'access CiviCRM',
        'access CiviEvent',
        'view event participants',
      ],
      'update' => [
        'access CiviCRM',
        'access CiviEvent',
        'edit event participants',
      ],
    ];
    $permissions['participant_payment'] = [
      'create' => [
        'access CiviCRM',
        'access CiviEvent',
        'register for events',
        'access CiviContribute',
        'edit contributions',
      ],
      'delete' => [
        'access CiviCRM',
        'access CiviEvent',
        'edit event participants',
        'access CiviContribute',
        'delete in CiviContribute',
      ],
      'get' => [
        'access CiviCRM',
        'access CiviEvent',
        'view event participants',
        'access CiviContribute',
      ],
      'update' => [
        'access CiviCRM',
        'access CiviEvent',
        'edit event participants',
        'access CiviContribute',
        'edit contributions',
      ],
    ];

    // Pledge permissions
    $permissions['pledge'] = [
      'create' => [
        'access CiviCRM',
        'access CiviPledge',
        'edit pledges',
      ],
      'delete' => [
        'access CiviCRM',
        'access CiviPledge',
        'delete in CiviPledge',
      ],
      'get' => [
        'access CiviCRM',
        'access CiviPledge',
      ],
      'update' => [
        'access CiviCRM',
        'access CiviPledge',
        'edit pledges',
      ],
    ];

    //CRM-16777: Disable schedule reminder for user that have 'edit all events' and 'administer CiviCRM' permission.
    $permissions['action_schedule'] = [
      'update' => [
        [
          'access CiviCRM',
          'edit all events',
        ],
      ],
    ];

    $permissions['pledge_payment'] = [
      'create' => [
        'access CiviCRM',
        'access CiviPledge',
        'edit pledges',
        'access CiviContribute',
        'edit contributions',
      ],
      'delete' => [
        'access CiviCRM',
        'access CiviPledge',
        'delete in CiviPledge',
        'access CiviContribute',
        'delete in CiviContribute',
      ],
      'get' => [
        'access CiviCRM',
        'access CiviPledge',
        'access CiviContribute',
      ],
      'update' => [
        'access CiviCRM',
        'access CiviPledge',
        'edit pledges',
        'access CiviContribute',
        'edit contributions',
      ],
    ];

    // Dashboard permissions
    $permissions['dashboard'] = [
      'get' => [
        'access CiviCRM',
      ],
    ];
    $permissions['dashboard_contact'] = [
      'default' => [
        'access CiviCRM',
      ],
    ];
    $permissions['mapping'] = [
      'default' => [
        'access CiviCRM',
      ],
    ];
    $permissions['mapping_field'] = $permissions['mapping'];

    $permissions['saved_search'] = [
      'default' => ['administer CiviCRM data'],
    ];

    // Profile permissions
    $permissions['profile'] = [
      // the profile will take care of this
      'get' => [],
    ];

    $permissions['uf_group'] = [
      'create' => [
        'access CiviCRM',
        [
          'administer CiviCRM',
          'manage event profiles',
        ],
      ],
      'get' => [
        'access CiviCRM',
      ],
      'update' => [
        'access CiviCRM',
        [
          'administer CiviCRM',
          'manage event profiles',
        ],
      ],
    ];
    $permissions['uf_field'] = $permissions['uf_join'] = $permissions['uf_group'];
    $permissions['uf_field']['delete'] = [
      'access CiviCRM',
      [
        'administer CiviCRM',
        'manage event profiles',
      ],
    ];
    $permissions['option_value'] = $permissions['uf_group'];
    $permissions['option_group'] = $permissions['option_value'];

    // User Job permissions - we access these using acls on the get action.
    // For create it probably makes sense (at least initially) to be stricter
    // as the forms doing the work can set the permission check to FALSE.
    $permissions['user_job'] = [
      'get' => [
        'access CiviCRM',
      ],
      'default' => [
        'administer CiviCRM',
      ],
    ];

    $permissions['custom_value'] = [
      'gettree' => ['access CiviCRM'],
    ];

    $permissions['location_type'] = [
      'get' => ['access CiviCRM'],
      'update' => ['administer CiviCRM data'],
      'delete' => ['administer CiviCRM data'],
    ];

    $permissions['message_template'] = [
      'get' => ['access CiviCRM'],
      'create' => [['edit message templates', 'edit user-driven message templates', 'edit system workflow message templates']],
      'update' => [['edit message templates', 'edit user-driven message templates', 'edit system workflow message templates']],
    ];

    $permissions['report_template']['update'] = 'save Report Criteria';
    $permissions['report_template']['create'] = 'save Report Criteria';
    return $permissions;
  }

  /**
   * Translate an unknown action to a canonical form.
   *
   * @param string $action
   *
   * @return string
   *   the standardised action name
   */
  public static function getGenericAction($action) {
    $snippet = substr($action, 0, 3);
    if ($action == 'replace' || $snippet == 'del') {
      // 'Replace' is a combination of get+create+update+delete; however, the permissions
      // on each of those will be tested separately at runtime. This is just a sniff-test
      // based on the heuristic that 'delete' tends to be the most closely guarded
      // of the necessary permissions.
      $action = 'delete';
    }
    elseif ($action == 'setvalue' || $snippet == 'upd') {
      $action = 'update';
    }
    elseif ($action == 'getfields' || $action == 'getfield' || $action == 'getspec' || $action == 'getoptions') {
      $action = 'meta';
    }
    elseif ($snippet == 'get') {
      $action = 'get';
    }
    return $action;
  }

  /**
   * Validate user permission across.
   * edit or view or with supportable acls.
   *
   * @return bool
   */
  public static function giveMeAllACLs() {
    if (CRM_Core_Permission::check('view all contacts') ||
      CRM_Core_Permission::check('edit all contacts')
    ) {
      return TRUE;
    }

    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');

    //check for acl.
    $aclPermission = self::getPermission();
    if (in_array($aclPermission, [
      CRM_Core_Permission::EDIT,
      CRM_Core_Permission::VIEW,
    ])
    ) {
      return TRUE;
    }

    // run acl where hook and see if the user is supplying an ACL clause
    // that is not false
    $tables = $whereTables = [];
    $where = NULL;

    CRM_Utils_Hook::aclWhereClause(CRM_Core_Permission::VIEW,
      $tables, $whereTables,
      $contactID, $where
    );
    return empty($whereTables) ? FALSE : TRUE;
  }

  /**
   * Get component name from given permission.
   *
   * @param string $permission
   *
   * @return null|string
   *   the name of component.
   */
  public static function getComponentName($permission) {
    $componentName = NULL;
    $permission = trim($permission);
    if (empty($permission)) {
      return $componentName;
    }

    static $allCompPermissions = [];
    if (empty($allCompPermissions)) {
      $components = CRM_Core_Component::getComponents();
      foreach ($components as $name => $comp) {
        //get all permissions of each components unconditionally
        $allCompPermissions[$name] = $comp->getPermissions(TRUE);
      }
    }

    if (is_array($allCompPermissions)) {
      foreach ($allCompPermissions as $name => $permissions) {
        if (array_key_exists($permission, $permissions)) {
          $componentName = $name;
          break;
        }
      }
    }

    return $componentName;
  }

  /**
   * Get all the contact emails for users that have a specific permission.
   *
   * @param string $permissionName
   *   Name of the permission we are interested in.
   *
   * @return string
   *   a comma separated list of email addresses
   */
  public static function permissionEmails($permissionName) {
    $config = CRM_Core_Config::singleton();
    return $config->userPermissionClass->permissionEmails($permissionName);
  }

  /**
   * Get all the contact emails for users that have a specific role.
   *
   * @param string $roleName
   *   Name of the role we are interested in.
   *
   * @return string
   *   a comma separated list of email addresses
   */
  public static function roleEmails($roleName) {
    $config = CRM_Core_Config::singleton();
    return $config->userRoleClass->roleEmails($roleName);
  }

  /**
   * @return bool
   */
  public static function isMultisiteEnabled() {
    return (bool) Civi::settings()->get('is_enabled');
  }

  /**
   * Verify if the user has permission to get the invoice.
   *
   * @return bool
   *   TRUE if the user has download all invoices permission or download my
   *   invoices permission and the invoice author is the current user.
   */
  public static function checkDownloadInvoice() {
    $cid = CRM_Core_Session::getLoggedInContactID();
    if (CRM_Core_Permission::check('access CiviContribute') ||
      (CRM_Core_Permission::check('view my invoices') && $_GET['cid'] == $cid)
    ) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get permissions for components.
   *
   * @return array
   */
  protected static function getComponentPermissions(): array {
    $permissions = [];
    foreach (CRM_Core_Component::getComponents() as $component) {
      $perms = $component->getPermissions();
      if ($perms) {
        $info = $component->getInfo();
        foreach ($perms as $name => $perm) {
          $perm['label'] = $info['translatedName'] . ': ' . $perm['label'];
          if (!$component->isEnabled()) {
            $perm['disabled'] = TRUE;
          }
          $permissions[$name] = $perm;
        }
      }
    }
    return $permissions;
  }

  /**
   * Get permissions for core functionality and for that of core components.
   *
   * @return array
   */
  protected static function getCoreAndComponentPermissions(): array {
    $permissions = self::getCorePermissions();
    $permissions = array_merge($permissions, self::getComponentPermissions());
    return $permissions;
  }

}
