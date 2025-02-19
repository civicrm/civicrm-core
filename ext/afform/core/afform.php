<?php

require_once 'afform.civix.php';

use CRM_Afform_ExtensionUtil as E;

/**
 * Filter the content of $params to only have supported afform fields.
 *
 * @param array $params
 * @return array
 */
function _afform_fields_filter($params) {
  $result = [];
  $fields = \Civi\Api4\Afform::getfields(FALSE)->setAction('create')->execute()->indexBy('name');
  foreach ($fields as $fieldName => $field) {
    if (array_key_exists($fieldName, $params)) {
      $result[$fieldName] = $params[$fieldName];

      if ($field['data_type'] === 'Boolean' && !is_bool($params[$fieldName])) {
        $result[$fieldName] = CRM_Utils_String::strtobool($params[$fieldName]);
      }
    }
  }
  return $result;
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function afform_civicrm_config(&$config) {
  _afform_civix_civicrm_config($config);

  if (isset(Civi::$statics[__FUNCTION__])) {
    return;
  }
  Civi::$statics[__FUNCTION__] = 1;

  $dispatcher = Civi::dispatcher();
  $dispatcher->addListener('civi.afform.validate', ['\Civi\Api4\Action\Afform\Submit', 'validateFieldInput'], 50);
  $dispatcher->addListener('civi.afform.validate', ['\Civi\Api4\Action\Afform\Submit', 'validateEntityRefFields'], 45);
  $dispatcher->addListener('civi.afform.submit', ['\Civi\Api4\Action\Afform\Submit', 'processGenericEntity'], 0);
  $dispatcher->addListener('civi.afform.submit', ['\Civi\Api4\Action\Afform\Submit', 'preprocessContact'], 10);
  $dispatcher->addListener('civi.afform.submit', ['\Civi\Api4\Action\Afform\Submit', 'preprocessParentFormValues'], 100);
  $dispatcher->addListener('civi.afform.submit', ['\Civi\Api4\Action\Afform\Submit', 'processRelationships'], 1);
  $dispatcher->addListener('hook_civicrm_angularModules', '_afform_hook_civicrm_angularModules', -1000);
  $dispatcher->addListener('hook_civicrm_alterAngular', ['\Civi\Afform\AfformMetadataInjector', 'preprocess']);
  $dispatcher->addListener('hook_civicrm_check', ['\Civi\Afform\StatusChecks', 'hook_civicrm_check']);
  $dispatcher->addListener('civi.afform.get', ['\Civi\Api4\Action\CustomGroup\GetAfforms', 'getCustomGroupAfforms']);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function afform_civicrm_install() {
  _afform_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function afform_civicrm_enable() {
  _afform_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function afform_civicrm_managed(&$entities, $modules) {
  if ($modules && !in_array(E::LONG_NAME, $modules, TRUE)) {
    return;
  }
  /** @var \CRM_Afform_AfformScanner $scanner */
  if (\Civi::container()->has('afform_scanner')) {
    $scanner = \Civi::service('afform_scanner');
  }
  else {
    // This might happen at oddballs points - e.g. while you're in the middle of re-enabling the ext.
    // This AfformScanner instance only lives during this method call, and it feeds off the regular cache.
    $scanner = new CRM_Afform_AfformScanner();
  }

  foreach ($scanner->getMetas() as $afform) {
    if (empty($afform['name'])) {
      continue;
    }
    // Backward-compat with legacy `is_dashlet`
    if (!empty($afform['is_dashlet'])) {
      $afform['placement'][] = 'dashboard_dashlet';
    }
    if (in_array('dashboard_dashlet', $afform['placement'] ?? [], TRUE)) {
      $entities[] = [
        'module' => E::LONG_NAME,
        'name' => 'afform_dashlet_' . $afform['name'],
        'entity' => 'Dashboard',
        'update' => 'always',
        // ideal cleanup policy might be to (a) deactivate if used and (b) remove if unused
        'cleanup' => 'always',
        'params' => [
          'version' => 4,
          'values' => [
            'is_active' => TRUE,
            'name' => $afform['name'],
            'label' => $afform['title'] ?? E::ts('(Untitled)'),
            'directive' => _afform_angular_module_name($afform['name'], 'dash'),
            'permission' => "@afform:" . $afform['name'],
            'url' => NULL,
          ],
        ],
      ];
    }
    if (!empty($afform['navigation']) && !empty($afform['server_route'])) {
      $params = [
        'version' => 4,
        'values' => [
          'name' => $afform['name'],
          'label' => $afform['navigation']['label'] ?: $afform['title'],
          'permission' => (array) (empty($afform['permission']) ? 'access CiviCRM' : $afform['permission']),
          'permission_operator' => $afform['permission_operator'] ?? 'AND',
          'weight' => $afform['navigation']['weight'] ?? 0,
          'url' => $afform['server_route'],
          'icon' => !empty($afform['icon']) ? 'crm-i ' . $afform['icon'] : '',
        ],
        'match' => ['domain_id', 'name'],
      ];
      if (!empty($afform['navigation']['parent'])) {
        $params['values']['parent_id.name'] = $afform['navigation']['parent'];
      }
      $entities[] = [
        'module' => E::LONG_NAME,
        'name' => 'navigation_' . $afform['name'],
        'cleanup' => 'always',
        'update' => 'unmodified',
        'entity' => 'Navigation',
        'params' => $params,
      ];
    }
  }
}

/**
 * Late-listener for Angular modules: adds all Afforms and their dependencies.
 *
 * Must run last so that all other modules are present for reverse-dependency mapping.
 *
 * @implements CRM_Utils_Hook::angularModules
 * @param \Civi\Core\Event\GenericHookEvent $e
 */
function _afform_hook_civicrm_angularModules($e) {
  $afforms = \Civi\Api4\Afform::get(FALSE)
    ->setSelect(['name', 'requires', 'module_name', 'directive_name', 'layout'])
    ->setLayoutFormat('html')
    ->execute();

  // 1st pass, add each Afform as angular module
  foreach ($afforms as $afform) {
    $e->angularModules[$afform['module_name']] = [
      'ext' => E::LONG_NAME,
      'js' => ['assetBuilder://afform.js?name=' . urlencode($afform['name'])],
      'requires' => $afform['requires'],
      'basePages' => [],
      'partialsCallback' => '_afform_get_partials',
      '_afform' => $afform['name'],
      // TODO: Allow afforms to declare their own theming requirements
      'bundles' => ['bootstrap3'],
      'exports' => [
        $afform['directive_name'] => 'E',
      ],
      // Permissions needed for conditionally displaying edit-links
      'permissions' => [
        'administer afform',
        'administer search_kit',
        'all CiviCRM permissions and ACLs',
      ],
    ];
  }

  // 2nd pass, now that all Angular modules are declared, add reverse dependencies
  $dependencyMapper = new \Civi\Afform\AngularDependencyMapper($e->angularModules);
  foreach ($afforms as $afform) {
    $e->angularModules[$afform['module_name']]['requires'] = $dependencyMapper->autoReq($afform);
  }
}

/**
 * Callback to retrieve partials for a given afform/angular module.
 *
 * @see afform_civicrm_angularModules
 *
 * @param string $moduleName
 *   The module name.
 * @param array $module
 *   The module definition.
 * @return array
 *   Array(string $filename => string $html).
 * @throws CRM_Core_Exception
 */
function _afform_get_partials($moduleName, $module) {
  $afform = civicrm_api4('Afform', 'get', [
    'where' => [['name', '=', $module['_afform']]],
    'select' => ['layout'],
    'layoutFormat' => 'html',
    'checkPermissions' => FALSE,
  ], 0);
  return [
    "~/$moduleName/$moduleName.aff.html" => $afform['layout'],
  ];
}

/**
 * Implements hook_civicrm_buildAsset().
 */
function afform_civicrm_buildAsset($asset, $params, &$mimeType, &$content) {
  if ($asset !== 'afform.js') {
    return;
  }

  if (empty($params['name'])) {
    throw new RuntimeException("Missing required parameter: afform.js?name=NAME");
  }

  $moduleName = _afform_angular_module_name($params['name'], 'camel');
  $formMetaData = (array) civicrm_api4('Afform', 'get', [
    'checkPermissions' => FALSE,
    'select' => ['redirect', 'name', 'title', 'autosave_draft'],
    'where' => [['name', '=', $params['name']]],
  ], 0);
  $smarty = CRM_Core_Smarty::singleton();
  $smarty->assign('afform', [
    'camel' => $moduleName,
    'meta' => $formMetaData,
    'templateUrl' => "~/$moduleName/$moduleName.aff.html",
  ]);
  $mimeType = 'text/javascript';
  $content = $smarty->fetch('afform/AfformAngularModule.tpl');
}

/**
 * Implements hook_civicrm_alterMenu().
 */
function afform_civicrm_alterMenu(&$items) {
  try {
    $afforms = \Civi\Api4\Afform::get(FALSE)
      ->addWhere('server_route', 'IS NOT EMPTY')
      ->addSelect('name', 'server_route', 'is_public', 'title')
      ->execute()->indexBy('name');
  }
  catch (Exception $e) {
    // During installation...
    $scanner = new CRM_Afform_AfformScanner();
    $afforms = $scanner->getMetas();
  }
  foreach ($afforms as $name => $meta) {
    if (!empty($meta['server_route'])) {
      $items[$meta['server_route']] = [
        'title' => $meta['title'] ?? NULL,
        'page_callback' => 'CRM_Afform_Page_AfformBase',
        'page_arguments' => 'afform=' . urlencode($name),
        'access_arguments' => [["@afform:$name"], 'and'],
        'is_public' => $meta['is_public'] ?? FALSE,
      ];
    }
  }
}

/**
 * Implements hook_civicrm_permission().
 *
 * Define Afform permissions.
 */
function afform_civicrm_permission(&$permissions) {
  $permissions['administer afform'] = [
    'label' => E::ts('FormBuilder: edit and delete forms'),
    'description' => E::ts('Allows non-admin users to create, update and delete forms'),
    'implied_by' => ['administer CiviCRM'],
  ];
}

/**
 * Implements hook_civicrm_permission_check().
 *
 * This extends the list of permissions available in `CRM_Core_Permission:check()`
 * by introducing virtual-permissions named `@afform:myForm`. The evaluation
 * of these virtual-permissions is dependent on the settings for `myForm`.
 * `myForm` may be exposed/integrated through multiple subsystems (routing,
 * nav-menu, API, etc), and the use of virtual-permissions makes easy to enforce
 * consistent permissions across any relevant subsystems.
 *
 * @see CRM_Utils_Hook::permission_check()
 */
function afform_civicrm_permission_check($permission, &$granted, $contactId) {
  // This function may get hit a lot. Try to keep the conditionals efficient.
  if (str_starts_with($permission, '@afform:') && strlen($permission) >= 9) {
    [, $name] = explode(':', $permission, 2);
    // Delegate permission check to APIv4
    $check = \Civi\Api4\Afform::checkAccess()
      ->addValue('name', $name)
      ->setAction('get')
      ->execute()
      ->first();
    $granted = $check['access'];
  }
  elseif ($permission === '@afformPageToken') {
    $session = CRM_Core_Session::singleton();
    $data = $session->get('authx');
    $granted =
      // Check authx token
      isset($data['jwt']['scope'], $data['flow']) && $data['jwt']['scope'] === 'afform' && $data['flow'] === 'afformpage'
      // Allow admins to edit forms without requiring a token
      || CRM_Core_Permission::check('administer afform');
  }
}

/**
 * Implements hook_civicrm_permissionList().
 *
 * @see CRM_Utils_Hook::permissionList()
 */
function afform_civicrm_permissionList(&$permissions) {
  $permissions['@afformPageToken'] = [
    'group' => 'const',
    'title' => E::ts('Generic: Anyone with secret link'),
    'description' => E::ts('If you link to the form with a secure token, then no other permission is needed.'),
    'parent' => 'administer afform',
  ];
  $permissions['administer afform']['implies'] ??= [];
  $permissions['administer afform']['implies'][] = '@afformPageToken';
  $scanner = Civi::service('afform_scanner');
  foreach ($scanner->getMetas() as $name => $meta) {
    $permissions['@afform:' . $name] = [
      'group' => 'afform',
      'title' => E::ts('Afform: Inherit permission of %1', [
        1 => $name,
      ]),
    ];
  }
}

/**
 * Clear any local/in-memory caches based on afform data.
 */
function _afform_clear() {
  $container = \Civi::container();
  $container->get('afform_scanner')->clear();
  $container->get('angular')->clear();
}

/**
 * @param string $fileBaseName
 *   Ex: foo-bar
 * @param string $format
 *   'camel' or 'dash'.
 * @return string
 *   Ex: 'FooBar' or 'foo-bar'.
 * @throws \Exception
 */
function _afform_angular_module_name($fileBaseName, $format = 'camel') {
  switch ($format) {
    case 'camel':
      return \CRM_Utils_String::convertStringToCamel($fileBaseName, FALSE);

    case 'dash':
      return \CRM_Utils_String::convertStringToDash($fileBaseName);

    default:
      throw new \Exception("Unrecognized format");
  }
}

/**
 * Implements hook_civicrm_preProcess().
 *
 * Wordpress only: Adds Afforms to the shortcode dialog (when editing pages/posts).
 */
function afform_civicrm_preProcess($formName, &$form) {
  if ($formName === 'CRM_Core_Form_ShortCode') {
    $form->components['afform'] = [
      'label' => E::ts('FormBuilder'),
      'select' => [
        'key' => 'name',
        'entity' => 'Afform',
        'select' => ['minimumInputLength' => 0],
        'api' => [
          'params' => ['type' => ['IN' => ['form', 'search']]],
        ],
      ],
    ];
  }
}

/**
 * Implements hook_civicrm_pre().
 */
function afform_civicrm_pre($op, $entity, $id, &$params) {
  // When deleting a searchDisplay, also delete any Afforms the display is embedded within
  if ($entity === 'SearchDisplay' && $op === 'delete') {
    $display = \Civi\Api4\SearchDisplay::get(FALSE)
      ->addSelect('saved_search_id.name', 'name')
      ->addWhere('id', '=', $id)
      ->execute()->first();
    \Civi\Api4\Afform::revert(FALSE)
      ->addWhere('search_displays', 'CONTAINS', $display['saved_search_id.name'] . ".{$display['name']}")
      ->execute();
  }
  // When deleting a savedSearch, delete any Afforms which use the default display
  elseif ($entity === 'SavedSearch' && $op === 'delete') {
    $search = \Civi\Api4\SavedSearch::get(FALSE)
      ->addSelect('name')
      ->addWhere('id', '=', $id)
      ->execute()->first();
    \Civi\Api4\Afform::revert(FALSE)
      ->addWhere('search_displays', 'CONTAINS', $search['name'])
      ->execute();
  }
}

/**
 * Implements hook_civicrm_post().
 */
function afform_civicrm_post($op, $entityName, $id, $object, $params) {
  // When editing custom fields, refresh the autogenerated afforms
  if ($entityName === 'CustomGroup' || $entityName === 'CustomField') {
    _afform_clear();
  }
}

/**
 * Implements hook_civicrm_referenceCounts().
 */
function afform_civicrm_referenceCounts($dao, &$counts) {
  // Count afforms which contain a search display
  if (is_a($dao, 'CRM_Search_DAO_SearchDisplay') && $dao->id) {
    if (empty($dao->saved_search_id) || empty($dao->name)) {
      $dao->find(TRUE);
    }
    $search = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $dao->saved_search_id);
    $afforms = \Civi\Api4\Afform::get(FALSE)
      ->selectRowCount()
      ->addWhere('search_displays', 'CONTAINS', "$search.$dao->name")
      ->execute();
    if ($afforms->count()) {
      $counts[] = [
        'name' => 'Afform',
        'type' => 'Afform',
        'count' => $afforms->count(),
      ];
    }
  }
  // Count afforms which contain any displays from a SavedSearch (including the default display)
  elseif (is_a($dao, 'CRM_Contact_DAO_SavedSearch') && $dao->id) {
    if (empty($dao->name)) {
      $dao->find(TRUE);
    }
    $clauses = [
      ['search_displays', 'CONTAINS', $dao->name],
    ];
    try {
      $displays = civicrm_api4('SearchDisplay', 'get', [
        'where' => [['saved_search_id', '=', $dao->id]],
      ], ['name']);
      foreach ($displays as $displayName) {
        $clauses[] = ['search_displays', 'CONTAINS', $dao->name . '.' . $displayName];
      }
    }
    catch (Exception $e) {
      // In case SearchKit is not installed, the api call would fail
    }
    $afforms = \Civi\Api4\Afform::get(FALSE)
      ->selectRowCount()
      ->addClause('OR', $clauses)
      ->execute();
    if ($afforms->count()) {
      $counts[] = [
        'name' => 'Afform',
        'type' => 'Afform',
        'count' => $afforms->count(),
      ];
    }
  }
}

// Wordpress only: Register callback for rendering shortcodes
if (function_exists('add_filter')) {
  add_filter('civicrm_shortcode_get_markup', 'afform_shortcode_content', 10, 4);
}

/**
 * Wordpress only: Render Afform content for shortcodes.
 *
 * @param string $content
 *   HTML Markup
 * @param array $atts
 *   Shortcode attributes.
 * @param array $args
 *   Existing shortcode arguments.
 * @param string $context
 *   How many shortcodes are present on the page: 'single' or 'multiple'.
 * @return string
 *   Modified markup.
 */
function afform_shortcode_content($content, $atts, $args, $context) {
  if ($atts['component'] === 'afform') {
    $afform = civicrm_api4('Afform', 'get', [
      'select' => ['directive_name', 'module_name'],
      'where' => [['name', '=', $atts['name']]],
    ])->first();
    if ($afform) {
      Civi::service('angularjs.loader')->addModules($afform['module_name']);
      $content = "
        <div class='crm-container' id='bootstrap-theme'>
          <crm-angular-js modules='{$afform['module_name']}'>
            <{$afform['directive_name']}></{$afform['directive_name']}>
          </crm-angular-js>
        </div>";
    }
  }
  return $content;
}

/**
 * Implements hook_civicrm_searchKitTasks().
 *
 */
function afform_civicrm_searchKitTasks(array &$tasks, bool $checkPermissions, ?int $userID) {
  $tasks['AfformSubmission']['process'] = [
    'title' => E::ts('Process Submissions'),
    'icon' => 'fa-check-square-o',
    // The Afform.process API doesn't support batches so use get+chaining
    'apiBatch' => [
      'action' => 'get',
      'params' => [
        'select' => ['id', 'afform_name'],
        'where' => [['status_id:name', '=', 'Pending']],
        'chain' => [
          ['Afform', 'process', ['submissionId' => '$id', 'name' => '$afform_name']],
        ],
      ],
      'conditions' => [
        ['check user permission', '=', ['administer afform']],
      ],
      'confirmMsg' => E::ts('Confirm processing %1 %2.'),
      'runMsg' => E::ts('Processing %1 %2...'),
      'successMsg' => E::ts('Successfully processed %1 %2.'),
      'errorMsg' => E::ts('An error occurred while attempting to process %1 %2.'),
    ],
  ];
  $tasks['AfformSubmission']['reject'] = [
    'title' => E::ts('Reject Submissions'),
    'icon' => 'fa-rectangle-xmark',
    'apiBatch' => [
      'action' => 'update',
      'params' => [
        'where' => [['status_id:name', '=', 'Pending']],
        'values' => ['status_id:name' => 'Rejected'],
      ],
      'confirmMsg' => E::ts('Reject %1 %2.'),
      'runMsg' => E::ts('Updating %1 %2...'),
      'successMsg' => E::ts('%1 %2 have been rejected.'),
      'errorMsg' => E::ts('An error occurred while attempting to process %1 %2.'),
    ],
  ];
}
