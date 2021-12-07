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
    if (isset($params[$fieldName])) {
      $result[$fieldName] = $params[$fieldName];

      if ($field['data_type'] === 'Boolean' && !is_bool($params[$fieldName])) {
        $result[$fieldName] = CRM_Utils_String::strtobool($params[$fieldName]);
      }
    }
  }
  return $result;
}

/**
 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */
function afform_civicrm_container($container) {
  $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
  $container->setDefinition('afform_scanner', new \Symfony\Component\DependencyInjection\Definition(
    'CRM_Afform_AfformScanner',
    []
  ))->setPublic(TRUE);
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
  $dispatcher->addListener('civi.afform.submit', ['\Civi\Api4\Action\Afform\Submit', 'processGenericEntity'], 0);
  $dispatcher->addListener('civi.afform.submit', ['\Civi\Api4\Action\Afform\Submit', 'preprocessContact'], 10);
  $dispatcher->addListener('hook_civicrm_angularModules', ['\Civi\Afform\AngularDependencyMapper', 'autoReq'], -1000);
  $dispatcher->addListener('hook_civicrm_alterAngular', ['\Civi\Afform\AfformMetadataInjector', 'preprocess']);
  $dispatcher->addListener('hook_civicrm_check', ['\Civi\Afform\StatusChecks', 'hook_civicrm_check']);

  // Register support for email tokens
  if (CRM_Extension_System::singleton()->getMapper()->isActiveModule('authx')) {
    $dispatcher->addListener('hook_civicrm_alterMailContent', ['\Civi\Afform\Tokens', 'applyCkeditorWorkaround']);
    $dispatcher->addListener('hook_civicrm_tokens', ['\Civi\Afform\Tokens', 'hook_civicrm_tokens']);
    $dispatcher->addListener('hook_civicrm_tokenValues', ['\Civi\Afform\Tokens', 'hook_civicrm_tokenValues']);
  }
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
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function afform_civicrm_postInstall() {
  _afform_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function afform_civicrm_uninstall() {
  _afform_civix_civicrm_uninstall();
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
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function afform_civicrm_disable() {
  _afform_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function afform_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _afform_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function afform_civicrm_managed(&$entities) {
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
    if (empty($afform['is_dashlet']) || empty($afform['name'])) {
      continue;
    }
    $entities[] = [
      'module' => E::LONG_NAME,
      'name' => 'afform_dashlet_' . $afform['name'],
      'entity' => 'Dashboard',
      'update' => 'always',
      // ideal cleanup policy might be to (a) deactivate if used and (b) remove if unused
      'cleanup' => 'always',
      'params' => [
        'version' => 3,
        // Q: Should we loop through all domains?
        'domain_id' => CRM_Core_BAO_Domain::getDomain()->id,
        'is_active' => TRUE,
        'name' => $afform['name'],
        'label' => $afform['title'] ?? E::ts('(Untitled)'),
        'directive' => _afform_angular_module_name($afform['name'], 'dash'),
        'permission' => "@afform:" . $afform['name'],
      ],
    ];
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
  $scanner = \Civi::service('afform_scanner');
  $weight = 111;
  foreach ($scanner->getMetas() as $afform) {
    if (!empty($afform['contact_summary']) && $afform['contact_summary'] === 'tab') {
      $module = _afform_angular_module_name($afform['name']);
      $tabs[] = [
        'id' => $afform['name'],
        'title' => $afform['title'],
        'weight' => $weight++,
        'icon' => 'crm-i fa-list-alt',
        'is_active' => TRUE,
        'template' => 'afform/contactSummary/AfformTab.tpl',
        'module' => $module,
        'directive' => _afform_angular_module_name($afform['name'], 'dash'),
      ];
      // If this is the real contact summary page (and not a callback from ContactLayoutEditor), load module.
      if (empty($context['caller'])) {
        Civi::service('angularjs.loader')->addModules($module);
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
  if (get_class($page) !== 'CRM_Contact_Page_View_Summary') {
    return;
  }
  $scanner = \Civi::service('afform_scanner');
  $cid = $page->get('cid');
  $side = 'left';
  foreach ($scanner->getMetas() as $afform) {
    if (!empty($afform['contact_summary']) && $afform['contact_summary'] === 'block') {
      $module = _afform_angular_module_name($afform['name']);
      $block = [
        'module' => $module,
        'directive' => _afform_angular_module_name($afform['name'], 'dash'),
      ];
      $content = CRM_Core_Smarty::singleton()->fetchWith('afform/contactSummary/AfformBlock.tpl', ['contactId' => $cid, 'block' => $block]);
      CRM_Core_Region::instance("contact-basic-info-$side")->add([
        'markup' => '<div class="crm-summary-block">' . $content . '</div>',
        'weight' => 1,
      ]);
      Civi::service('angularjs.loader')->addModules($module);
      $side = $side === 'left' ? 'right' : 'left';
    }
  }
}

/**
 * Implements hook_civicrm_contactSummaryBlocks().
 *
 * @link https://github.com/civicrm/org.civicrm.contactlayout
 */
function afform_civicrm_contactSummaryBlocks(&$blocks) {
  $afforms = \Civi\Api4\Afform::get(FALSE)
    ->setSelect(['name', 'title', 'directive_name', 'module_name', 'type', 'type:icon', 'type:label'])
    ->addWhere('contact_summary', '=', 'block')
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
    $blocks["afform_{$afform['type']}"]['blocks'][$afform['name']] = [
      'title' => $afform['title'],
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
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Afform Angular modules.
 */
function afform_civicrm_angularModules(&$angularModules) {
  $afforms = \Civi\Api4\Afform::get(FALSE)
    ->setSelect(['name', 'requires', 'module_name', 'directive_name'])
    ->execute();

  foreach ($afforms as $afform) {
    $angularModules[$afform['module_name']] = [
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
    ];
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
 * @throws API_Exception
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
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function afform_civicrm_entityTypes(&$entityTypes) {
  _afform_civix_civicrm_entityTypes($entityTypes);
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
    'select' => ['redirect', 'name'],
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
  if (Civi::container()->has('afform_scanner')) {
    $scanner = Civi::service('afform_scanner');
  }
  else {
    // During installation...
    $scanner = new CRM_Afform_AfformScanner();
  }
  foreach ($scanner->getMetas() as $name => $meta) {
    if (!empty($meta['server_route'])) {
      $items[$meta['server_route']] = [
        'page_callback' => 'CRM_Afform_Page_AfformBase',
        'page_arguments' => 'afform=' . urlencode($name),
        'title' => $meta['title'] ?? '',
        'access_arguments' => [["@afform:$name"], 'and'],
        'is_public' => $meta['is_public'],
      ];
    }
  }
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
  if ($permission[0] !== '@') {
    // Micro-optimization - this function may get hit a lot.
    return;
  }

  if (preg_match('/^@afform:(.*)/', $permission, $m)) {
    $name = $m[1];

    $afform = \Civi\Api4\Afform::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $name)
      ->setSelect(['permission'])
      ->execute()
      ->first();
    if ($afform) {
      $granted = CRM_Core_Permission::check($afform['permission'], $contactId);
    }
  }
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
      $camelCase = '';
      foreach (preg_split('/[-_ ]/', $fileBaseName, NULL, PREG_SPLIT_NO_EMPTY) as $shortNamePart) {
        $camelCase .= ucfirst($shortNamePart);
      }
      return strtolower($camelCase[0]) . substr($camelCase, 1);

    case 'dash':
      return strtolower(implode('-', preg_split('/[-_ ]|(?=[A-Z])/', $fileBaseName, NULL, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)));

    default:
      throw new \Exception("Unrecognized format");
  }
}

/**
 * Implements hook_civicrm_alterApiRoutePermissions().
 *
 * @see CRM_Utils_Hook::alterApiRoutePermissions
 */
function afform_civicrm_alterApiRoutePermissions(&$permissions, $entity, $action) {
  if ($entity == 'Afform') {
    // These actions should be accessible to anonymous users; permissions are checked internally
    $allowedActions = ['prefill', 'submit', 'submitFile', 'getOptions'];
    if (in_array($action, $allowedActions, TRUE)) {
      $permissions = CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION;
    }
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
      'label' => E::ts('Form Builder'),
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
  if ($entity === 'SearchDisplay' && $op === 'delete') {
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
        'select' => 'name',
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
