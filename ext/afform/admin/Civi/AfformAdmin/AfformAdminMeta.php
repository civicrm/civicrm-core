<?php

namespace Civi\AfformAdmin;

use Civi\Api4\Entity;
use Civi\Api4\Utils\CoreUtil;
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
   * @param $entityName
   * @return array|void
   */
  public static function getAfformEntity($entityName) {
    // Optimization: look here before scanning every other extension
    global $civicrm_root;
    $fileName = \CRM_Utils_File::addTrailingSlash($civicrm_root) . "ext/afform/admin/afformEntities/$entityName.php";
    if (is_file($fileName)) {
      return include $fileName;
    }
    foreach (\CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles() as $ext) {
      $fileName = \CRM_Utils_File::addTrailingSlash(dirname($ext['filePath'])) . "afformEntities/$entityName.php";
      if (is_file($fileName)) {
        return include $fileName;
      }
    }
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
    if (in_array($entityName, ['Individual', 'Household', 'Organization'])) {
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
    return array_column($fields, NULL, 'name');
  }

  /**
   * Loads metadata for the gui editor.
   *
   * @return array
   */
  public static function getGuiSettings() {
    $data = [
      'entities' => [
        '*' => [
          'label' => E::ts('Content Block'),
          'icon' => 'fa-pencil-square-o',
          'fields' => [],
        ],
      ],
    ];

    // Explicitly load Contact and Custom entities because they do not have afformEntity files
    $entities = Entity::get(TRUE)
      ->addClause('OR', ['name', '=', 'Contact'], ['type', 'CONTAINS', 'CustomValue'])
      ->execute()->indexBy('name');
    foreach ($entities as $name => $entity) {
      $data['entities'][$name] = self::entityToAfformMeta($entity);
    }

    $contactTypes = \CRM_Contact_BAO_ContactType::basicTypeInfo();

    // Call getFields on getFields to get input type labels
    $inputTypeLabels = \Civi\Api4\Contact::getFields()
      ->setLoadOptions(TRUE)
      ->setAction('getFields')
      ->addWhere('name', '=', 'input_type')
      ->execute()
      ->column('options')[0];

    // Scan all extensions for entities & input types
    foreach (\CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles() as $ext) {
      $dir = \CRM_Utils_File::addTrailingSlash(dirname($ext['filePath']));
      if (is_dir($dir)) {
        // Scan for entities
        foreach (glob($dir . 'afformEntities/*.php') as $file) {
          $entityInfo = include $file;
          $entityName = basename($file, '.php');
          $apiInfo = self::getApiEntity($entityInfo['entity'] ?? $entityName);
          // Skip disabled contact types & entities from disabled components/extensions
          if (!$apiInfo) {
            continue;
          }
          $entityInfo += $apiInfo;
          $data['entities'][$entityName] = $entityInfo;
        }
        // Scan for input types, use label from getFields if available
        foreach (glob($dir . 'ang/afGuiEditor/inputType/*.html') as $file) {
          $name = basename($file, '.html');
          $data['inputType'][] = [
            'name' => $name,
            'label' => $inputTypeLabels[$name] ?? E::ts($name),
          ];
        }
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
          'class' => 'af-button btn btn-primary',
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

    $data['permissions'] = [];
    $perms = \Civi\Api4\Permission::get()
      ->addWhere('group', 'IN', ['afformGeneric', 'const', 'civicrm', 'cms'])
      ->addWhere('is_active', '=', 1)
      ->setOrderBy(['title' => 'ASC'])
      ->execute();
    foreach ($perms as $perm) {
      $data['permissions'][] = [
        'id' => $perm['name'],
        'text' => $perm['title'],
        'description' => $perm['description'] ?? NULL,
      ];
    }
    $dateRanges = \CRM_Utils_Array::makeNonAssociative(\CRM_Core_OptionGroup::values('relative_date_filters'), 'id', 'label');
    $data['dateRanges'] = array_merge([['id' => '{}', 'label' => E::ts('Choose Date Range')]], $dateRanges);

    return $data;
  }

}
