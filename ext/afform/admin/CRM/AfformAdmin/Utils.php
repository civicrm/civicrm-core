<?php
use CRM_AfformAdmin_ExtensionUtil as E;

class CRM_AfformAdmin_Utils {

  /**
   * @return array
   */
  public static function getAdminSettings() {
    return [
      'afform_type' => \Civi\Api4\OptionValue::get(FALSE)
        ->addSelect('name', 'label', 'icon')
        ->addWhere('is_active', '=', TRUE)
        ->addWhere('option_group_id:name', '=', 'afform_type')
        ->addOrderBy('weight', 'ASC')
        ->execute(),
    ];
  }

  /**
   * Loads metadata for the gui editor.
   *
   * FIXME: This is a prototype and should get broken out into separate callbacks with hooks, events, etc.
   * @return array
   */
  public static function getGuiSettings() {
    $getFieldParams = [
      'checkPermissions' => FALSE,
      'includeCustom' => TRUE,
      'loadOptions' => ['id', 'label'],
      'action' => 'create',
      'select' => ['name', 'label', 'input_type', 'input_attrs', 'required', 'options', 'help_pre', 'help_post', 'serialize', 'data_type'],
      'where' => [['input_type', 'IS NOT NULL']],
    ];

    $data = [
      'entities' => [
        'Contact' => [
          'entity' => 'Contact',
          'label' => E::ts('Contact'),
          'fields' => (array) civicrm_api4('Contact', 'getFields', $getFieldParams, 'name'),
        ],
      ],
      'blocks' => [],
    ];

    $contactTypes = CRM_Contact_BAO_ContactType::basicTypeInfo();

    // Scan all extensions for entities & input types
    foreach (CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles() as $ext) {
      $dir = CRM_Utils_File::addTrailingSlash(dirname($ext['filePath']));
      if (is_dir($dir)) {
        // Scan for entities
        foreach (glob($dir . 'afformEntities/*.php') as $file) {
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
        // Scan for input types
        foreach (glob($dir . 'ang/afGuiEditor/inputType/*.html') as $file) {
          $matches = [];
          preg_match('/([-a-z_A-Z0-9]*).html/', $file, $matches);
          $data['inputType'][$matches[1]] = $matches[1];
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
        $data['entities'][$block['join']]['label'] = E::ts($block['join']);
        $data['entities'][$block['join']]['fields'] = (array) civicrm_api4($block['join'], 'getFields', $getFieldParams, 'name');
      }
    }

    // Todo: add method for extensions to define other elements
    $data['elements'] = [
      'container' => [
        'title' => E::ts('Container'),
        'element' => [
          '#tag' => 'div',
          'class' => 'af-container',
          '#children' => [],
        ],
      ],
      'text' => [
        'title' => E::ts('Text box'),
        'element' => [
          '#tag' => 'p',
          'class' => 'af-text',
          '#children' => [
            ['#text' => E::ts('Enter text')],
          ],
        ],
      ],
      'markup' => [
        'title' => E::ts('Rich content'),
        'element' => [
          '#tag' => 'div',
          'class' => 'af-markup',
          '#markup' => FALSE,
        ],
      ],
      'submit' => [
        'title' => E::ts('Submit Button'),
        'element' => [
          '#tag' => 'button',
          'class' => 'af-button btn-primary',
          'crm-icon' => 'fa-check',
          'ng-click' => 'afform.submit()',
          '#children' => [
            ['#text' => E::ts('Submit')],
          ],
        ],
      ],
      'fieldset' => [
        'title' => E::ts('Fieldset'),
        'element' => [
          '#tag' => 'fieldset',
          'af-fieldset' => NULL,
          '#children' => [
            [
              '#tag' => 'legend',
              'class' => 'af-text',
              '#children' => [
                [
                  '#text' => E::ts('Enter title'),
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    $data['styles'] = [
      'default' => E::ts('Default'),
      'primary' => E::ts('Primary'),
      'success' => E::ts('Success'),
      'info' => E::ts('Info'),
      'warning' => E::ts('Warning'),
      'danger' => E::ts('Danger'),
    ];

    $perms = Civi\Api4\Permission::get()
      ->addWhere('group', 'IN', ['afformGeneric', 'const', 'civicrm', 'cms'])
      ->addWhere('is_active', '=', 1)
      ->addOrderBy('group')
      ->addOrderBy('name')
      ->execute();
    foreach ($perms as $perm) {
      $data['permissions'][] = [
        'id' => $perm['name'],
        'text' => $perm['title'],
        'description' => $perm['description'] ?? NULL,
      ];
    }

    return $data;
  }

}
