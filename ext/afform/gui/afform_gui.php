<?php

require_once 'afform_gui.civix.php';
use CRM_AfformGui_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function afform_gui_civicrm_config(&$config) {
  _afform_gui_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function afform_gui_civicrm_xmlMenu(&$files) {
  _afform_gui_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function afform_gui_civicrm_install() {
  _afform_gui_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function afform_gui_civicrm_postInstall() {
  _afform_gui_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function afform_gui_civicrm_uninstall() {
  _afform_gui_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function afform_gui_civicrm_enable() {
  _afform_gui_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function afform_gui_civicrm_disable() {
  _afform_gui_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function afform_gui_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _afform_gui_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function afform_gui_civicrm_managed(&$entities) {
  _afform_gui_civix_civicrm_managed($entities);
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
function afform_gui_civicrm_caseTypes(&$caseTypes) {
  _afform_gui_civix_civicrm_caseTypes($caseTypes);
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
function afform_gui_civicrm_angularModules(&$angularModules) {
  _afform_gui_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function afform_gui_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _afform_gui_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function afform_gui_civicrm_entityTypes(&$entityTypes) {
  _afform_gui_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_themes().
 */
function afform_gui_civicrm_themes(&$themes) {
  _afform_gui_civix_civicrm_themes($themes);
}

/**
 * Implements hook_civicrm_pageRun().
 */
function afform_gui_civicrm_pageRun(&$page) {
  if (get_class($page) == 'CRM_Afform_Page_AfformBase' && $page->get('afModule') == 'afGuiAdmin') {
    Civi::resources()->addScriptUrl(Civi::service('asset_builder')->getUrl('af-gui-vars.js'));
  }
}

/**
 * Implements hook_civicrm_buildAsset().
 *
 * Loads metadata to send to the gui editor.
 *
 * FIXME: This is a prototype and should get broken out into separate callbacks with hooks, events, etc.
 */
function afform_gui_civicrm_buildAsset($asset, $params, &$mimeType, &$content) {
  if ($asset !== 'af-gui-vars.js') {
    return;
  }

  $getFieldParams = [
    'checkPermissions' => FALSE,
    'includeCustom' => TRUE,
    'loadOptions' => TRUE,
    'action' => 'create',
    'select' => ['name', 'title', 'input_type', 'input_attrs', 'required', 'options', 'help_pre', 'help_post', 'serialize', 'data_type'],
    'where' => [['input_type', 'IS NOT NULL']],
  ];

  $data = [
    'entities' => [
      'Contact' => [
        'entity' => 'Contact',
        'label' => ts('Contact'),
        'fields' => (array) civicrm_api4('Contact', 'getFields', $getFieldParams, 'name'),
      ],
    ],
    'blocks' => [],
  ];

  $contactTypes = CRM_Contact_BAO_ContactType::basicTypeInfo();

  // Scan all extensions for our list of supported entities
  foreach (CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles() as $ext) {
    $dir = CRM_Utils_File::addTrailingSlash(dirname($ext['filePath'])) . 'afformEntities';
    if (is_dir($dir)) {
      foreach (glob($dir . '/*.php') as $file) {
        $entity = include $file;
        // Skip disabled contact types
        if (!empty($entity['contact_type']) && !isset($contactTypes[$entity['contact_type']])) {
          continue;
        }
        if (!empty($entity['contact_type'])) {
          $entity['label'] = $contactTypes[$entity['contact_type']]['label'];
        }
        // For Contact pseudo-entities (Individual, Organization, Household)
        $values = array_intersect_key($entity, ['contact_type' => NULL]);
        $afformEntity = $entity['contact_type'] ?? $entity['entity'];
        $entity['fields'] = (array) civicrm_api4($entity['entity'], 'getFields', $getFieldParams + ['values' => $values], 'name');
        $data['entities'][$afformEntity] = $entity;
      }
    }
  }

  // Load fields from afform blocks with joins
  $blockData = \Civi\Api4\Afform::get()
    ->setCheckPermissions(FALSE)
    ->addWhere('join', 'IS NOT NULL')
    ->setSelect(['join'])
    ->execute();
  foreach ($blockData as $block) {
    if (!isset($data['entities'][$block['join']]['fields'])) {
      $data['entities'][$block['join']]['entity'] = $block['join'];
      // Normally you shouldn't pass variables to ts() but very common strings like "Email" should already exist
      $data['entities'][$block['join']]['label'] = ts($block['join']);
      $data['entities'][$block['join']]['fields'] = (array) civicrm_api4($block['join'], 'getFields', $getFieldParams, 'name');
    }
  }

  // Todo: add method for extensions to define other elements
  $data['elements'] = [
    'container' => [
      'title' => ts('Container'),
      'element' => [
        '#tag' => 'div',
        'class' => 'af-container',
        '#children' => [],
      ],
    ],
    'text' => [
      'title' => ts('Text box'),
      'element' => [
        '#tag' => 'p',
        'class' => 'af-text',
        '#children' => [
          ['#text' => ts('Enter text')],
        ],
      ],
    ],
    'markup' => [
      'title' => ts('Rich content'),
      'element' => [
        '#tag' => 'div',
        'class' => 'af-markup',
        '#markup' => FALSE,
      ],
    ],
    'submit' => [
      'title' => ts('Submit Button'),
      'element' => [
        '#tag' => 'button',
        'class' => 'af-button btn-primary',
        'crm-icon' => 'fa-check',
        'ng-click' => 'afform.submit()',
        '#children' => [
          ['#text' => ts('Submit')],
        ],
      ],
    ],
    'fieldset' => [
      'title' => ts('Fieldset'),
      'element' => [
        '#tag' => 'fieldset',
        'af-fieldset' => NULL,
        '#children' => [
          [
            '#tag' => 'legend',
            'class' => 'af-text',
            '#children' => [
              [
                '#text' => ts('Enter title'),
              ],
            ],
          ],
        ],
      ],
    ],
  ];

  // Reformat options
  // TODO: Teach the api to return options in this format
  foreach ($data['entities'] as $entityName => $entity) {
    foreach ($entity['fields'] as $name => $field) {
      if (!empty($field['options'])) {
        $data['entities'][$entityName]['fields'][$name]['options'] = CRM_Utils_Array::makeNonAssociative($field['options'], 'key', 'label');
      }
      else {
        unset($data['entities'][$entityName]['fields'][$name]['options']);
      }
    }
  }

  // Scan for input types
  // FIXME: Need a way to load this from other extensions too
  foreach (glob(__DIR__ . '/ang/afGuiEditor/inputType/*.html') as $file) {
    $matches = [];
    preg_match('/([-a-z_A-Z0-9]*).html/', $file, $matches);
    $data['inputType'][$matches[1]] = $matches[1];
  }

  $data['styles'] = [
    'default' => ts('Default'),
    'primary' => ts('Primary'),
    'success' => ts('Success'),
    'info' => ts('Info'),
    'warning' => ts('Warning'),
    'danger' => ts('Danger'),
  ];

  $data['permissions'] = [];
  foreach (CRM_Core_Permission::basicPermissions(TRUE, TRUE) as $name => $perm) {
    $data['permissions'][] = [
      'id' => $name,
      'text' => $perm[0],
      'description' => $perm[1] ?? NULL,
    ];
  }

  $mimeType = 'text/javascript';
  $content = "CRM.afformAdminData=" . json_encode($data, JSON_UNESCAPED_SLASHES) . ';';
}
