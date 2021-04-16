<?php

namespace Civi\AfformAdmin;

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
      'form' => E::ts('Custom Forms'),
      'search' => E::ts('Search Displays'),
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
   * @param $entityName
   * @return array
   */
  public static function getApiEntity($entityName) {
    if (in_array($entityName, ['Individual', 'Household', 'Organization'])) {
      $contactTypes = \CRM_Contact_BAO_ContactType::basicTypeInfo();
      return [
        'entity' => 'Contact',
        'label' => $contactTypes[$entityName]['label'],
      ];
    }
    $info = \Civi\Api4\Entity::get(FALSE)
      ->addWhere('name', '=', $entityName)
      ->addSelect('title', 'icon')
      ->execute()->first();
    return [
      'entity' => $entityName,
      'label' => $info['title'],
      'icon' => $info['icon'],
    ];
  }

  /**
   * @param $entityName
   * @param array $params
   * @return array
   */
  public static function getFields($entityName, $params = []) {
    $params += [
      'checkPermissions' => FALSE,
      'includeCustom' => TRUE,
      'loadOptions' => ['id', 'label'],
      'action' => 'create',
      'select' => ['name', 'label', 'input_type', 'input_attrs', 'required', 'options', 'help_pre', 'help_post', 'serialize', 'data_type', 'fk_entity'],
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
    return (array) civicrm_api4($entityName, 'getFields', $params, 'name');
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
        'Contact' => self::getApiEntity('Contact'),
      ],
    ];

    $contactTypes = \CRM_Contact_BAO_ContactType::basicTypeInfo();

    // Scan all extensions for entities & input types
    foreach (\CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles() as $ext) {
      $dir = \CRM_Utils_File::addTrailingSlash(dirname($ext['filePath']));
      if (is_dir($dir)) {
        // Scan for entities
        foreach (glob($dir . 'afformEntities/*.php') as $file) {
          $entity = include $file;
          $afformEntity = basename($file, '.php');
          // Contact pseudo-entities (Individual, Organization, Household) get special treatment,
          // notably their fields are pre-loaded since they are both commonly-used and nonstandard
          if (!empty($entity['contact_type'])) {
            // Skip disabled contact types
            if (!isset($contactTypes[$entity['contact_type']])) {
              continue;
            }
            $entity['label'] = $contactTypes[$entity['contact_type']]['label'];
          }
          elseif (empty($entity['label']) || empty($entity['icon'])) {
            $entity += self::getApiEntity($entity['entity']);
          }
          $data['entities'][$afformEntity] = $entity;
        }
        // Scan for input types
        foreach (glob($dir . 'ang/afGuiEditor/inputType/*.html') as $file) {
          $name = basename($file, '.html');
          $data['inputType'][$name] = $name;
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
