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
  $dispatcher->addListener('civi.afform.validate', ['\Civi\Api4\Action\Afform\Submit', 'validateRequiredFields'], 50);
  $dispatcher->addListener('civi.afform.validate', ['\Civi\Api4\Action\Afform\Submit', 'validateEntityRefFields'], 45);
  $dispatcher->addListener('civi.afform.submit', ['\Civi\Api4\Action\Afform\Submit', 'processGenericEntity'], 0);
  $dispatcher->addListener('civi.afform.submit', ['\Civi\Api4\Action\Afform\Submit', 'preprocessContact'], 10);
  $dispatcher->addListener('civi.afform.submit', ['\Civi\Api4\Action\Afform\Submit', 'processRelationships'], 1);
  $dispatcher->addListener('hook_civicrm_angularModules', '_afform_hook_civicrm_angularModules', -1000);
  $dispatcher->addListener('hook_civicrm_alterAngular', ['\Civi\Afform\AfformMetadataInjector', 'preprocess']);
  $dispatcher->addListener('hook_civicrm_check', ['\Civi\Afform\StatusChecks', 'hook_civicrm_check']);
  $dispatcher->addListener('civi.afform.get', ['\Civi\Api4\Action\Afform\Get', 'getCustomGroupBlocks']);
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
 * Implements hook_civicrm_tabset().
 *
 * Adds afforms as contact summary tabs.
 */
function afform_civicrm_tabset($tabsetName, &$tabs, $context) {
  if ($tabsetName !== 'civicrm/contact/view') {
    return;
  }
  $existingTabs = array_combine(array_keys($tabs), array_column($tabs, 'id'));
  $contactTypes = array_merge((array) ($context['contact_type'] ?? []), $context['contact_sub_type'] ?? []);
  $afforms = Civi\Api4\Afform::get()
    ->addSelect('name', 'title', 'icon', 'module_name', 'directive_name', 'summary_contact_type', 'summary_weight')
    ->addWhere('placement', 'CONTAINS', 'contact_summary_tab')
    ->addOrderBy('title')
    ->execute();
  $weight = 111;
  foreach ($afforms as $afform) {
    $summaryContactType = $afform['summary_contact_type'] ?? [];
    if (!$summaryContactType || !$contactTypes || array_intersect($summaryContactType, $contactTypes)) {
      // Convention is to name the afform like "afformTabMyInfo" which gets the tab name "my_info"
      $tabId = CRM_Utils_String::convertStringToSnakeCase(preg_replace('#^(afformtab|afsearchtab|afform|afsearch)#i', '', $afform['name']));
      // If a tab with that id already exists, allow the afform to replace it.
      $existingTab = array_search($tabId, $existingTabs);
      if ($existingTab !== FALSE) {
        unset($tabs[$existingTab]);
      }
      $tabs[] = [
        'id' => $tabId,
        'title' => $afform['title'],
        'weight' => $afform['summary_weight'] ?? $weight++,
        'icon' => 'crm-i ' . ($afform['icon'] ?: 'fa-list-alt'),
        'is_active' => TRUE,
        'contact_type' => _afform_get_contact_types($summaryContactType) ?: NULL,
        'template' => 'afform/contactSummary/AfformTab.tpl',
        'module' => $afform['module_name'],
        'directive' => $afform['directive_name'],
      ];
      // If this is the real contact summary page (and not a callback from ContactLayoutEditor), load module.
      if (empty($context['caller'])) {
        Civi::service('angularjs.loader')->addModules($afform['module_name']);
      }
    }
  }
}

/**
 * Implements hook_civicrm_pageRun().
 *
 * Adds afforms as contact summary blocks.
 */
function afform_civicrm_pageRun(&$page) {
  if (!in_array(get_class($page), ['CRM_Contact_Page_View_Summary', 'CRM_Contact_Page_View_Print'])) {
    return;
  }
  $afforms = Civi\Api4\Afform::get()
    ->addSelect('name', 'title', 'icon', 'module_name', 'directive_name', 'summary_contact_type')
    ->addWhere('placement', 'CONTAINS', 'contact_summary_block')
    ->addOrderBy('summary_weight')
    ->addOrderBy('title')
    ->execute();
  $cid = $page->get('cid');
  $contact = NULL;
  $side = 'left';
  $weight = ['left' => 1, 'right' => 1];
  foreach ($afforms as $afform) {
    // If Afform specifies a contact type, lookup the contact and compare
    if (!empty($afform['summary_contact_type'])) {
      // Contact.get only needs to happen once
      $contact ??= civicrm_api4('Contact', 'get', [
        'select' => ['contact_type', 'contact_sub_type'],
        'where' => [['id', '=', $cid]],
      ])->first();
      $contactTypes = array_merge([$contact['contact_type']], $contact['contact_sub_type'] ?? []);
      if (!array_intersect($afform['summary_contact_type'], $contactTypes)) {
        continue;
      }
    }
    $block = [
      'module' => $afform['module_name'],
      'directive' => _afform_angular_module_name($afform['name'], 'dash'),
    ];
    $content = CRM_Core_Smarty::singleton()->fetchWith('afform/contactSummary/AfformBlock.tpl', ['contactId' => $cid, 'block' => $block]);
    CRM_Core_Region::instance("contact-basic-info-$side")->add([
      'markup' => '<div class="crm-summary-block">' . $content . '</div>',
      'name' => 'afform:' . $afform['name'],
      'weight' => $weight[$side]++,
    ]);
    Civi::service('angularjs.loader')->addModules($afform['module_name']);
    $side = $side === 'left' ? 'right' : 'left';
  }
}

/**
 * Implements hook_civicrm_contactSummaryBlocks().
 *
 * @link https://github.com/civicrm/org.civicrm.contactlayout
 */
function afform_civicrm_contactSummaryBlocks(&$blocks) {
  $afforms = \Civi\Api4\Afform::get()
    ->setSelect(['name', 'title', 'directive_name', 'module_name', 'type', 'type:icon', 'type:label', 'summary_contact_type'])
    ->addWhere('placement', 'CONTAINS', 'contact_summary_block')
    ->addOrderBy('title')
    ->execute();
  foreach ($afforms as $index => $afform) {
    // Create a group per afform type
    $blocks += [
      "afform_{$afform['type']}" => [
        'title' => $afform['type:label'],
        'icon' => $afform['type:icon'],
        'blocks' => [],
      ],
    ];
    // If the form specifies contact types, resolve them to just the parent types (Individual, Organization, Household)
    // because ContactLayout doesn't care about sub-types
    $contactType = _afform_get_contact_types($afform['summary_contact_type'] ?? []);
    $blocks["afform_{$afform['type']}"]['blocks'][$afform['name']] = [
      'title' => $afform['title'],
      'contact_type' => $contactType ?: NULL,
      'tpl_file' => 'afform/contactSummary/AfformBlock.tpl',
      'module' => $afform['module_name'],
      'directive' => $afform['directive_name'],
      'sample' => [
        $afform['type:label'],
      ],
      'edit' => 'civicrm/admin/afform#/edit/' . $afform['name'],
      'system_default' => [0, $index % 2],
    ];
  }
}

/**
 * Resolve a mixed list of contact types and sub-types into just top-level contact types (Individual, Organization, Household)
 *
 * @param array $mixedTypes
 * @return array
 * @throws CRM_Core_Exception
 */
function _afform_get_contact_types(array $mixedTypes): array {
  $allContactTypes = \CRM_Contact_BAO_ContactType::getAllContactTypes();
  $contactTypes = [];
  foreach ($mixedTypes as $name) {
    $parent = $allContactTypes[$name]['parent'] ?? $name;
    $contactTypes[$parent] = $parent;
  }
  return array_values($contactTypes);
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
    'select' => ['redirect', 'name', 'title'],
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
  if (!str_starts_with($permission, '@afform:') || strlen($permission) < 9) {
    // Micro-optimization - this function may get hit a lot.
    return;
  }
  [, $name] = explode(':', $permission, 2);
  // Delegate permission check to APIv4
  $check = \Civi\Api4\Afform::checkAccess()
    ->addValue('name', $name)
    ->setAction('get')
    ->execute()
    ->first();
  $granted = $check['access'];
}

/**
 * Implements hook_civicrm_permissionList().
 *
 * @see CRM_Utils_Hook::permissionList()
 */
function afform_civicrm_permissionList(&$permissions) {
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
    'module' => 'afSearchTasks',
    'title' => E::ts('Process Submissions'),
    'icon' => 'fa-check-square-o',
    'uiDialog' => ['templateUrl' => '~/afSearchTasks/afformSubmissionProcessTask.html'],
  ];
}
