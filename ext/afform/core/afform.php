<?php

require_once 'afform.civix.php';
use CRM_Afform_ExtensionUtil as E;
use Civi\Api4\Action\Afform\Submit;

function _afform_fields() {
  return ['name', 'title', 'description', 'requires', 'layout', 'server_route', 'client_route', 'is_public'];
}

/**
 * Filter the content of $params to only have supported afform fields.
 *
 * @param array $params
 * @return array
 */
function _afform_fields_filter($params) {
  $result = [];
  foreach (_afform_fields() as $field) {
    if (isset($params[$field])) {
      $result[$field] = $params[$field];
    }

    if (isset($result[$field])) {
      switch ($field) {
        case 'is_public':
          $result[$field] = CRM_Utils_String::strtobool($result[$field]);
          break;

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
  ));
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

  // Civi::dispatcher()->addListener(Submit::EVENT_NAME, [Submit::class, 'processContacts'], -500);
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
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function afform_civicrm_angularModules(&$angularModules) {
  _afform_civix_civicrm_angularModules($angularModules);

  /** @var CRM_Afform_AfformScanner $scanner */
  $scanner = Civi::service('afform_scanner');
  $names = array_keys($scanner->findFilePaths());
  foreach ($names as $name) {
    $meta = $scanner->getMeta($name);
    $angularModules[_afform_angular_module_name($name, 'camel')] = [
      'ext' => E::LONG_NAME,
      'js' => ['assetBuilder://afform.js?name=' . urlencode($name)],
      'requires' => $meta['requires'],
      'basePages' => [],
      'exports' => [
        // Each afform is an attribute and an element.
        'el' => [_afform_angular_module_name($name, 'dash')],
        'attr' => [_afform_angular_module_name($name, 'dash')],
      ],
    ];

    // FIXME: The HTML layout template is embedded in the JS asset.
    // This works at runtime for basic usage, but it bypasses
    // the normal workflow for templates (e.g. translation).
    // We should update core so that 'partials' can be specified more dynamically.
  }
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
  $revMap = [];
  foreach (['attr', 'el'] as $exportType) {
    $revMap[$exportType] = [];
    foreach (array_keys($angularModules) as $module) {
      if (isset($angularModules[$module]['exports'][$exportType])) {
        foreach ($angularModules[$module]['exports'][$exportType] as $exportItem) {
          $revMap[$exportType][$exportItem] = $module;
        }
      }
    }
  }
  return $revMap;
}

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
    ->alterHtml(';^~afform/;', function($doc, $path) {
      $entities = _afform_getMetadata($doc);

      foreach (pq('af-field', $doc) as $afField) {
        /** @var DOMElement $afField */
        $fieldName = $afField->getAttribute('name');
        $entityName = pq($afField)->parent('af-fieldset[model]')->attr('model');
        if (!preg_match(';^[a-zA-Z0-9\_\-\. ]+$;', $entityName)) {
          throw new \CRM_Core_Exception("Cannot process $path: malformed entity name ($entityName)");
        }
        $entityType = $entities[$entityName]['type'];
        $getFields = civicrm_api4($entityType, 'getFields', [
          'where' => [['name', '=', $fieldName]],
          'select' => ['title', 'input_type', 'input_attrs', 'options'],
          'loadOptions' => TRUE,
        ]);
        // Merge field definition data with whatever's already in the markup
        foreach ($getFields as $field) {
          $existingFieldDefn = trim(pq($afField)->attr('defn') ?: '');
          if ($existingFieldDefn && $existingFieldDefn[0] != '{') {
            // If it's not an object, don't mess with it.
            continue;
          }
          foreach ($field as &$prop) {
            $prop = json_encode($prop, JSON_UNESCAPED_SLASHES);
          }
          if ($existingFieldDefn) {
            $field = array_merge($field, CRM_Utils_JS::getRawProps($existingFieldDefn));
          }
          pq($afField)->attr('defn', CRM_Utils_JS::writeObject($field));
        }
      }
    });
  $angular->add($fieldMetadata);
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

// --- Functions below this ship commented out. Uncomment as required. ---

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

  $name = $params['name'];
  // Hmm?? $scanner = new CRM_Afform_AfformScanner();
  // Hmm?? afform_scanner
  $scanner = Civi::service('afform_scanner');
  $meta = $scanner->getMeta($name);
  // Hmm?? $scanner = new CRM_Afform_AfformScanner();

  $fileName = '~afform/' . _afform_angular_module_name($name, 'camel');
  $htmls = [
    $fileName => file_get_contents($scanner->findFilePath($name, 'aff.html')),
  ];
  $htmls = \Civi\Angular\ChangeSet::applyResourceFilters(Civi::service('angular')->getChangeSets(), 'partials', $htmls);

  $smarty = CRM_Core_Smarty::singleton();
  $smarty->assign('afform', [
    'camel' => _afform_angular_module_name($name, 'camel'),
    'meta' => $meta,
    'metaJson' => json_encode($meta),
    'layout' => $htmls[$fileName],
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
        'access_arguments' => [['access CiviCRM'], 'and'], // FIXME
        'is_public' => $meta['is_public'],
      ];
    }
  }
}

/**
 * Clear any local/in-memory caches based on afform data.
 */
function _afform_clear() {
  $container = \Civi::container();
  $container->get('afform_scanner')->clear();

  // Civi\Angular\Manager doesn't currently have a way to clear its in-memory
  // data, so we just reset the whole object.
  $container->set('angular', NULL);
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
      foreach (explode('-', $fileBaseName) as $shortNamePart) {
        $camelCase .= ucfirst($shortNamePart);
      }
      return strtolower($camelCase{0}) . substr($camelCase, 1);

    case 'dash':
      return strtolower(implode('-', array_filter(preg_split('/(?=[A-Z])/', $fileBaseName))));

    default:
      throw new \Exception("Unrecognized format");
  }
}
