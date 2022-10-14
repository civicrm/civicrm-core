<?php

namespace Civi\AfformAdmin;

use Civi\Api4\Entity;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\GenericHookEvent;
use CRM_AfformAdmin_ExtensionUtil as E;

class AfformAdminMeta {

  /**
   * @return array
   */
  public static function getAdminSettings() {
    $afformTypes = (array) \Civi\Api4\OptionValue::get(FALSE)
      ->addSelect('name', 'label', 'icon')
      ->addWhere('is_active', '=', TRUE)
      ->addWhere('option_group_id:name', '=', 'afform_type')
      ->addOrderBy('weight', 'ASC')
      ->execute();
    // Pluralize tabs (too bad option groups only store a single label)
    $plurals = [
      'form' => E::ts('Submission Forms'),
      'search' => E::ts('Search Forms'),
      'block' => E::ts('Field Blocks'),
      'system' => E::ts('System Forms'),
    ];
    foreach ($afformTypes as $index => $type) {
      $afformTypes[$index]['plural'] = $plurals[$type['name']] ?? \CRM_Utils_String::pluralize($type['label']);
    }
    return [
      'afform_type' => $afformTypes,
    ];
  }

  /**
   * Get info about an api entity, with special handling for contact types
   * @param string $entityName
   * @return array|null
   */
  public static function getApiEntity(string $entityName) {
    $contactTypes = \CRM_Contact_BAO_ContactType::basicTypeInfo();
    if (isset($contactTypes[$entityName])) {
      return [
        'entity' => 'Contact',
        'contact_type' => $entityName,
        'label' => $contactTypes[$entityName]['label'],
      ];
    }
    $info = \Civi\Api4\Entity::get(FALSE)
      ->addWhere('name', '=', $entityName)
      ->execute()->first();
    if (!$info) {
      // Disabled contact type or nonexistent api entity
      return NULL;
    }
    return self::entityToAfformMeta($info);
  }

  /**
   * Converts info from API.Entity.get to an array of afform entity metadata
   * @param array $info
   * @return array
   */
  private static function entityToAfformMeta(array $info): array {
    $meta = [
      'entity' => $info['name'],
      'label' => $info['title'],
      'icon' => $info['icon'] ?? NULL,
    ];
    // Custom entities are always type 'join'
    if (in_array('CustomValue', $info['type'], TRUE)) {
      $meta['type'] = 'join';
      $max = (int) \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', substr($info['name'], 7), 'max_multiple', 'name');
      $meta['repeat_max'] = $max ?: NULL;
    }
    return $meta;
  }

  /**
   * @param $entityName
   * @param array $params
   * @return array
   */
  public static function getFields($entityName, $params = []) {
    $params += [
      'checkPermissions' => FALSE,
      'loadOptions' => ['id', 'label'],
      'action' => 'create',
      'select' => ['name', 'label', 'input_type', 'input_attrs', 'required', 'options', 'help_pre', 'help_post', 'serialize', 'data_type', 'fk_entity', 'readonly'],
      'where' => [['input_type', 'IS NOT NULL']],
    ];
    if (in_array($entityName, \CRM_Contact_BAO_ContactType::basicTypes(TRUE), TRUE)) {
      $params['values']['contact_type'] = $entityName;
      $entityName = 'Contact';
    }
    if ($entityName === 'Address') {
      // The stateProvince option list is waaay too long unless country limits are set
      if (!\Civi::settings()->get('provinceLimit')) {
        // If no province limit, restrict it to the default country, or if there's no default, pick one to avoid breaking the UI
        $params['values']['country_id'] = \Civi::settings()->get('defaultContactCountry') ?: 1228;
      }
      $params['values']['state_province_id'] = \Civi::settings()->get('defaultContactStateProvince');
    }
    $fields = (array) civicrm_api4($entityName, 'getFields', $params);
    // Add implicit joins to search fields
    if ($params['action'] === 'get') {
      foreach (array_reverse($fields, TRUE) as $index => $field) {
        if (!empty($field['fk_entity']) && !$field['options']) {
          $fkLabelField = CoreUtil::getInfoItem($field['fk_entity'], 'label_field');
          if ($fkLabelField) {
            // Add the label field from the other entity to this entity's list of fields
            $newField = civicrm_api4($field['fk_entity'], 'getFields', [
              'where' => [['name', '=', $fkLabelField]],
            ])->first();
            $newField['name'] = $field['name'] . '.' . $newField['name'];
            $newField['label'] = $field['label'] . ' ' . $newField['label'];
            array_splice($fields, $index, 0, [$newField]);
          }
        }
      }
    }
    // Index by name
    $fields = array_column($fields, NULL, 'name');
    if ($params['action'] === 'create') {
      // Add existing entity field
      $idField = CoreUtil::getIdFieldName($entityName);
      $fields[$idField]['readonly'] = FALSE;
      $fields[$idField]['input_type'] = 'Existing';
      $fields[$idField]['is_id'] = TRUE;
      $fields[$idField]['label'] = E::ts('Existing %1', [1 => CoreUtil::getInfoItem($entityName, 'title')]);
      // Mix in alterations declared by afform entities
      $afEntity = self::getMetadata()['entities'][$entityName] ?? [];
      if (!empty($afEntity['alterFields'])) {
        foreach ($afEntity['alterFields'] as $fieldName => $changes) {
          // Allow field to be deleted
          if ($changes === FALSE) {
            unset($fields[$fieldName]);
          }
          else {
            $fields[$fieldName] = \CRM_Utils_Array::crmArrayMerge($changes, ($fields[$fieldName] ?? []));
          }
        }
      }
    }
    return $fields;
  }

  /**
   * Loads metadata for the gui editor.
   *
   * @return array
   */
  public static function getMetadata() {
    $data = \Civi::cache('metadata')->get('afform_admin.metadata');
    if (!$data) {
      $entities = [
        '*' => [
          'label' => E::ts('Content Block'),
          'icon' => 'fa-pencil-square-o',
          'fields' => [],
        ],
      ];

      // Explicitly load Contact and Custom entities because they do not have afformEntity files
      $contactAndCustom = Entity::get(TRUE)
        ->addClause('OR', ['name', '=', 'Contact'], ['type', 'CONTAINS', 'CustomValue'])
        ->execute()->indexBy('name');
      foreach ($contactAndCustom as $name => $entity) {
        $entities[$name] = self::entityToAfformMeta($entity);
      }

      // Call getFields on getFields to get input type labels
      $inputTypeLabels = \Civi\Api4\Contact::getFields()
        ->setLoadOptions(TRUE)
        ->setAction('getFields')
        ->addWhere('name', '=', 'input_type')
        ->execute()
        ->column('options')[0];
      // Scan for input types, use label from getFields if available
      $inputTypes = [];
      foreach (glob(__DIR__ . '/../../ang/afGuiEditor/inputType/*.html') as $file) {
        $name = basename($file, '.html');
        $inputTypes[] = [
          'name' => $name,
          'label' => $inputTypeLabels[$name] ?? E::ts($name),
        ];
      }

      // Static elements
      $elements = [
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
          'afform_type' => ['form'],
          'element' => [
            '#tag' => 'button',
            'class' => 'af-button btn btn-primary',
            'crm-icon' => 'fa-check',
            'ng-click' => 'afform.submit()',
            '#children' => [
              ['#text' => E::ts('Submit')],
            ],
          ],
        ],
        'reset' => [
          'title' => E::ts('Reset Button'),
          'afform_type' => ['form', 'search'],
          'element' => [
            '#tag' => 'button',
            'class' => 'af-button btn btn-warning',
            'type' => 'reset',
            'crm-icon' => 'fa-undo',
            '#children' => [
              ['#text' => E::ts('Reset')],
            ],
          ],
        ],
        'fieldset' => [
          'title' => E::ts('Fieldset'),
          'afform_type' => ['form'],
          'element' => [
            '#tag' => 'fieldset',
            'af-fieldset' => NULL,
            'class' => 'af-container',
            'af-title' => E::ts('Enter title'),
            '#children' => [],
          ],
        ],
      ];

      $styles = [
        'default' => E::ts('Default'),
        'primary' => E::ts('Primary'),
        'success' => E::ts('Success'),
        'info' => E::ts('Info'),
        'warning' => E::ts('Warning'),
        'danger' => E::ts('Danger'),
      ];

      $perms = \Civi\Api4\Permission::get()
        ->addWhere('group', 'IN', ['afformGeneric', 'const', 'civicrm', 'cms'])
        ->addWhere('is_active', '=', 1)
        ->setOrderBy(['title' => 'ASC'])
        ->execute();
      $permissions = [];
      foreach ($perms as $perm) {
        $permissions[] = [
          'id' => $perm['name'],
          'text' => $perm['title'],
          'description' => $perm['description'] ?? NULL,
        ];
      }

      $dateRanges = \CRM_Utils_Array::makeNonAssociative(\CRM_Core_OptionGroup::values('relative_date_filters'), 'id', 'label');
      $dateRanges = array_merge([['id' => '{}', 'label' => E::ts('Choose Date Range')]], $dateRanges);

      // Allow data to be modified by event listeners
      $data = [
        // @see afform-entity-php/mixin.php
        'entities' => &$entities,
        'inputTypes' => &$inputTypes,
        'elements' => &$elements,
        'styles' => &$styles,
        'permissions' => &$permissions,
        'dateRanges' => &$dateRanges,
      ];
      $event = GenericHookEvent::create($data);
      \Civi::dispatcher()->dispatch('civi.afform_admin.metadata', $event);
      \Civi::cache('metadata')->set('afform_admin.metadata', $data);
    }

    return $data;
  }

}
