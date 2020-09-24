<?php

require_once 'afform.civix.php';
use CRM_Afform_ExtensionUtil as E;
use Civi\Api4\Action\Afform\Submit;

/**
 * Filter the content of $params to only have supported afform fields.
 *
 * @param array $params
 * @return array
 */
function _afform_fields_filter($params) {
  $result = [];
  $fields = \Civi\Api4\Afform::getfields()->setCheckPermissions(FALSE)->setAction('create')->execute()->indexBy('name');
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

  Civi::dispatcher()->addListener(Submit::EVENT_NAME, [Submit::class, 'processContacts'], 500);
  Civi::dispatcher()->addListener(Submit::EVENT_NAME, [Submit::class, 'processGenericEntity'], -1000);
  Civi::dispatcher()->addListener('hook_civicrm_angularModules', '_afform_civicrm_angularModules_autoReq', -1000);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function afform_civicrm_xmlMenu(&$files) {
  _afform_civix_civicrm_xmlMenu($files);
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
  _afform_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function afform_civicrm_caseTypes(&$caseTypes) {
  _afform_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Afform Angular modules.
 */
function afform_civicrm_angularModules(&$angularModules) {
  _afform_civix_civicrm_angularModules($angularModules);

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
      'exports' => [
        $afform['directive_name'] => 'AE',
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
 * Scan the list of Angular modules and inject automatic-requirements.
 *
 * TLDR: if an afform uses element "<other-el/>", and if another module defines
 * `$angularModules['otherMod']['exports']['el'][0] === 'other-el'`, then
 * the 'otherMod' is automatically required.
 *
 * @param \Civi\Core\Event\GenericHookEvent $e
 * @see CRM_Utils_Hook::angularModules()
 */
function _afform_civicrm_angularModules_autoReq($e) {
  /** @var CRM_Afform_AfformScanner $scanner */
  $scanner = Civi::service('afform_scanner');
  $moduleEnvId = md5(\CRM_Core_Config_Runtime::getId() . implode(',', array_keys($e->angularModules)));
  $depCache = CRM_Utils_Cache::create([
    'name' => 'afdep_' . substr($moduleEnvId, 0, 32 - 6),
    'type' => ['*memory*', 'SqlGroup', 'ArrayCache'],
    'withArray' => 'fast',
    'prefetch' => TRUE,
  ]);
  $depCacheTtl = 2 * 60 * 60;

  $revMap = _afform_reverse_deps($e->angularModules);

  $formNames = array_keys($scanner->findFilePaths());
  foreach ($formNames as $formName) {
    $angModule = _afform_angular_module_name($formName, 'camel');
    $cacheLine = $depCache->get($formName, NULL);

    $jFile = $scanner->findFilePath($formName, 'aff.json');
    $hFile = $scanner->findFilePath($formName, 'aff.html');

    $jStat = stat($jFile);
    $hStat = stat($hFile);

    if ($cacheLine === NULL) {
      $needsUpdate = TRUE;
    }
    elseif ($jStat !== FALSE && $jStat['size'] !== $cacheLine['js']) {
      $needsUpdate = TRUE;
    }
    elseif ($jStat !== FALSE && $jStat['mtime'] > $cacheLine['jm']) {
      $needsUpdate = TRUE;
    }
    elseif ($hStat !== FALSE && $hStat['size'] !== $cacheLine['hs']) {
      $needsUpdate = TRUE;
    }
    elseif ($hStat !== FALSE && $hStat['mtime'] > $cacheLine['hm']) {
      $needsUpdate = TRUE;
    }
    else {
      $needsUpdate = FALSE;
    }

    if ($needsUpdate) {
      $cacheLine = [
        'js' => $jStat['size'] ?? NULL,
        'jm' => $jStat['mtime'] ?? NULL,
        'hs' => $hStat['size'] ?? NULL,
        'hm' => $hStat['mtime'] ?? NULL,
        'r' => array_values(array_unique(array_merge(
          [CRM_Afform_AfformScanner::DEFAULT_REQUIRES],
          $e->angularModules[$angModule]['requires'] ?? [],
          _afform_reverse_deps_find($formName, file_get_contents($hFile), $revMap)
        ))),
      ];
      // print_r(['cache update:' . $formName => $cacheLine]);
      $depCache->set($formName, $cacheLine, $depCacheTtl);
    }

    $e->angularModules[$angModule]['requires'] = $cacheLine['r'];
  }
}

/**
 * @param $angularModules
 * @return array
 *   'attr': array(string $attrName => string $angModuleName)
 *   'el': array(string $elementName => string $angModuleName)
 */
function _afform_reverse_deps($angularModules) {
  $revMap = ['attr' => [], 'el' => []];
  foreach (array_keys($angularModules) as $module) {
    if (!isset($angularModules[$module]['exports'])) {
      continue;
    }
    foreach ($angularModules[$module]['exports'] as $symbolName => $symbolTypes) {
      if (strpos($symbolTypes, 'A') !== FALSE) {
        $revMap['attr'][$symbolName] = $module;
      }
      if (strpos($symbolTypes, 'E') !== FALSE) {
        $revMap['el'][$symbolName] = $module;
      }
    }
  }
  return $revMap;
}

/**
 * @param string $formName
 * @param string $html
 * @param array $revMap
 *   The reverse-dependencies map from _afform_reverse_deps().
 * @return array
 * @see _afform_reverse_deps()
 */
function _afform_reverse_deps_find($formName, $html, $revMap) {
  $symbols = \Civi\Afform\Symbols::scan($html);
  $elems = array_intersect_key($revMap['el'], $symbols->elements);
  $attrs = array_intersect_key($revMap['attr'], $symbols->attributes);
  return array_values(array_unique(array_merge($elems, $attrs)));
}

/**
 * @param \Civi\Angular\Manager $angular
 * @see CRM_Utils_Hook::alterAngular()
 */
function afform_civicrm_alterAngular($angular) {
  $fieldMetadata = \Civi\Angular\ChangeSet::create('fieldMetadata')
    ->alterHtml(';\\.aff\\.html$;', function($doc, $path) {
      try {
        $module = \Civi::service('angular')->getModule(basename($path, '.aff.html'));
        $meta = \Civi\Api4\Afform::get()->addWhere('name', '=', $module['_afform'])->setSelect(['join', 'block'])->setCheckPermissions(FALSE)->execute()->first();
      }
      catch (Exception $e) {
      }

      $blockEntity = $meta['join'] ?? $meta['block'] ?? NULL;
      if (!$blockEntity) {
        $entities = _afform_getMetadata($doc);
      }

      foreach (pq('af-field', $doc) as $afField) {
        /** @var DOMElement $afField */
        $entityName = pq($afField)->parents('[af-fieldset]')->attr('af-fieldset');
        $joinName = pq($afField)->parents('[af-join]')->attr('af-join');
        if (!$blockEntity && !preg_match(';^[a-zA-Z0-9\_\-\. ]+$;', $entityName)) {
          throw new \CRM_Core_Exception("Cannot process $path: malformed entity name ($entityName)");
        }
        $entityType = $blockEntity ?? $entities[$entityName]['type'];
        _af_fill_field_metadata($joinName ? $joinName : $entityType, $afField);
      }
    });
  $angular->add($fieldMetadata);
}

/**
 * Merge field definition metadata into an afform field's definition
 *
 * @param $entityType
 * @param DOMElement $afField
 * @throws API_Exception
 */
function _af_fill_field_metadata($entityType, DOMElement $afField) {
  $params = [
    'action' => 'create',
    'where' => [['name', '=', $afField->getAttribute('name')]],
    'select' => ['title', 'input_type', 'input_attrs', 'options'],
    'loadOptions' => TRUE,
  ];
  if (in_array($entityType, CRM_Contact_BAO_ContactType::basicTypes(TRUE))) {
    $params['values'] = ['contact_type' => $entityType];
    $entityType = 'Contact';
  }
  $getFields = civicrm_api4($entityType, 'getFields', $params);
  // Merge field definition data with whatever's already in the markup
  $deep = ['input_attrs'];
  foreach ($getFields as $fieldInfo) {
    $existingFieldDefn = trim(pq($afField)->attr('defn') ?: '');
    if ($existingFieldDefn && $existingFieldDefn[0] != '{') {
      // If it's not an object, don't mess with it.
      continue;
    }
    // TODO: Teach the api to return options in this format
    if (!empty($fieldInfo['options'])) {
      $fieldInfo['options'] = CRM_Utils_Array::makeNonAssociative($fieldInfo['options'], 'key', 'label');
    }
    // Default placeholder for select inputs
    if ($fieldInfo['input_type'] === 'Select') {
      $fieldInfo['input_attrs'] = ($fieldInfo['input_attrs'] ?? []) + ['placeholder' => ts('Select')];
    }

    $fieldDefn = $existingFieldDefn ? CRM_Utils_JS::getRawProps($existingFieldDefn) : [];
    foreach ($fieldInfo as $name => $prop) {
      // Merge array props 1 level deep
      if (in_array($name, $deep) && !empty($fieldDefn[$name])) {
        $fieldDefn[$name] = CRM_Utils_JS::writeObject(CRM_Utils_JS::getRawProps($fieldDefn[$name]) + array_map(['CRM_Utils_JS', 'encode'], $prop));
      }
      elseif (!isset($fieldDefn[$name])) {
        $fieldDefn[$name] = CRM_Utils_JS::encode($prop);
      }
    }
    pq($afField)->attr('defn', htmlspecialchars(CRM_Utils_JS::writeObject($fieldDefn)));
  }
}

function _afform_getMetadata(phpQueryObject $doc) {
  $entities = [];
  foreach ($doc->find('af-entity') as $afmModelProp) {
    $entities[$afmModelProp->getAttribute('name')] = [
      'type' => $afmModelProp->getAttribute('type'),
    ];
  }
  return $entities;
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function afform_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _afform_civix_civicrm_alterSettingsFolders($metaDataFolders);
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
 * Implements hook_civicrm_themes().
 */
function afform_civicrm_themes(&$themes) {
  _afform_civix_civicrm_themes($themes);
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
  $smarty = CRM_Core_Smarty::singleton();
  $smarty->assign('afform', [
    'camel' => $moduleName,
    'meta' => ['name' => $params['name']],
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
