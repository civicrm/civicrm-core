<?php

namespace Civi\AfformAdmin;

use Civi\Afform\Placement\PlacementUtils;
use Civi\Api4\Afform;
use Civi\Api4\Entity;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\GenericHookEvent;
use CRM_AfformAdmin_ExtensionUtil as E;

class AfformAdminMeta {

  /**
   * @return array
   */
  public static function getAdminSettings(): array {
    // Check minimum permission needed to reach this
    if (!\CRM_Core_Permission::check('manage own afform')) {
      return [];
    }
    $afformFields = Afform::getFields(FALSE)
      ->setAction('create')
      ->setLoadOptions(['id', 'name', 'label', 'description', 'icon', 'color'])
      ->execute()->column(NULL, 'name');
    $afformPlacement = \CRM_Utils_Array::formatForSelect2(PlacementUtils::getPlacements(), 'label', 'value');
    // Pluralize tabs (too bad option groups only store a single label)
    $plurals = [
      'form' => E::ts('Submission Forms'),
      'search' => E::ts('Search Forms'),
      'block' => E::ts('Field Blocks'),
      'system' => E::ts('System Forms'),
    ];
    foreach ($afformFields['type']['options'] as &$afformType) {
      $afformType['plural'] = $plurals[$afformType['name']] ?? \CRM_Utils_String::pluralize($afformType['label']);
    }
    $containerStyles = (array) \Civi\Api4\OptionValue::get(FALSE)
      ->addSelect('value', 'label')
      ->addWhere('is_active', '=', TRUE)
      ->addWhere('option_group_id:name', '=', 'afform_container_style')
      ->addOrderBy('weight', 'ASC')
      ->execute();
    return [
      'afform_fields' => $afformFields,
      'afform_placement' => $afformPlacement,
      'afform_container_style' => $containerStyles,
      'placement_entities' => array_column(PlacementUtils::getPlacements(), 'entities', 'value'),
      'placement_filters' => self::getPlacementFilterOptions(),
      'search_operators' => \Civi\Afform\Utils::getSearchOperators(),
      'locales' => self::getLocales(),
    ];
  }

  /**
   * Get info about an api entity
   * @param string $entityName
   * @return array|null
   */
  public static function getApiEntity(string $entityName) {
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
      $max = (int) \CRM_Core_BAO_CustomGroup::getGroup(['name' => substr($info['name'], 7)])['max_multiple'];
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
      'select' => ['name', 'label', 'input_type', 'input_attrs', 'required', 'options', 'help_pre', 'help_post', 'serialize', 'data_type', 'entity', 'fk_entity', 'readonly', 'operators'],
      'where' => [['deprecated', '=', FALSE], ['input_type', 'IS NOT NULL']],
    ];
    if ($entityName === 'Address') {
      // The stateProvince option list is waaay too long unless country limits are set
      if (!\Civi::settings()->get('provinceLimit')) {
        // If no province limit, restrict it to the default country, or if there's no default, pick one to avoid breaking the UI
        $params['values']['country_id'] = \Civi::settings()->get('defaultContactCountry') ?: 1228;
      }
      $params['values']['state_province_id'] = \Civi::settings()->get('defaultContactStateProvince');
    }
    // Exclude LocBlock fields that will be replaced by joins (see below)
    if ($params['action'] === 'create' && $entityName === 'LocBlock') {
      $joinParams = $params;
      // Omit the fk fields (email_id, email_2_id, phone_id, etc)
      // As we'll add their joined fields below
      $params['where'][] = ['fk_entity', 'IS NULL'];
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
    // Add LocBlock joins (e.g. `email_id.email`, `address_id.street_address`)
    if ($params['action'] === 'create' && $entityName === 'LocBlock') {
      // Exclude fields that don't apply to locBlocks
      $joinParams['where'][] = ['name', 'NOT IN', ['id', 'is_primary', 'is_billing', 'location_type_id', 'contact_id']];
      foreach (['Address', 'Email', 'Phone', 'IM'] as $joinEntity) {
        $joinEntityFields = (array) civicrm_api4($joinEntity, 'getFields', $joinParams);
        $joinEntityLabel = CoreUtil::getInfoItem($joinEntity, 'title');
        // LocBlock entity includes every join twice (e.g. `email_2_id.email`, `address_2_id.street_address`)
        foreach ([1 => '', 2 => '_2'] as $number => $suffix) {
          $joinField = strtolower($joinEntity) . $suffix . '_id';
          foreach ($joinEntityFields as $joinEntityField) {
            if (strtolower($joinEntity) === $joinEntityField['name']) {
              $joinEntityField['label'] .= " $number";
            }
            else {
              $joinEntityField['label'] = "$joinEntityLabel $number {$joinEntityField['label']}";
            }
            $joinEntityField['name'] = "$joinField." . $joinEntityField['name'];
            $fields[] = $joinEntityField;
          }
        }
      }
    }
    // Index by name
    $fields = array_column($fields, NULL, 'name');
    $idField = CoreUtil::getIdFieldName($entityName);
    // Convert ID field to existing entity field
    // Unless it already references another entity (e.g. GroupSubscription)
    if (isset($fields[$idField]) && empty($fields[$idField]['fk_entity'])) {
      $fields[$idField]['readonly'] = FALSE;
      $fields[$idField]['input_type'] = 'EntityRef';
      // Afform-only (so far) metadata tells the form to update an existing entity autofilled from this value
      $fields[$idField]['input_attrs']['autofill'] = 'update';
      $fields[$idField]['fk_entity'] = $entityName;
      $fields[$idField]['label'] = E::ts('Existing %1', [1 => CoreUtil::getInfoItem($entityName, 'title')]);
    }
    // Mix in alterations declared by afform entities
    if ($params['action'] === 'create') {
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
    foreach ($fields as $name => $field) {
      if ($field['input_type'] === 'EntityRef') {
        $fields[$name]['security'] = 'RBAC';
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
      $contactAndCustom = Entity::get(FALSE)
        ->addClause('OR', ['name', '=', 'Contact'], ['type', 'CONTAINS', 'CustomValue'])
        ->execute()->indexBy('name');
      foreach ($contactAndCustom as $name => $entity) {
        $entities[$name] = self::entityToAfformMeta($entity);
      }

      // Call getFields on getFields to get input type labels
      $inputTypeLabels = \Civi\Api4\Contact::getFields(FALSE)
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
          'label' => $inputTypeLabels[$name] ?? _ts($name),
          'template' => '~/af/fields/' . $name . '.html',
          'admin_template' => '~/afGuiEditor/inputType/' . $name . '.html',
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
        'tabset' => [
          'title' => E::ts('Tab Set'),
          'element' => [
            '#tag' => 'af-tabset',
            '#children' => [
              ['#tag' => 'af-tab', 'title' => E::ts('Tab 1'), '#children' => []],
              ['#tag' => 'af-tab', 'title' => E::ts('Tab 2'), '#children' => []],
            ],
          ],
        ],
        'search_param_sets' => [
          'title' => E::ts('Saved Search Picker'),
          'admin_tpl' => '~/afGuiEditor/elements/afGuiSearchParamSets.html',
          'directive' => 'af-search-param-sets',
          'afform_type' => 'search',
          'element' => [
            '#tag' => 'af-search-param-sets',
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
            'ng-if' => 'afform.showSubmitButton',
            '#children' => [
              ['#text' => E::ts('Submit')],
            ],
          ],
        ],
        'save_draft' => [
          'title' => E::ts('Save Draft Button'),
          'afform_type' => ['form'],
          'element' => [
            '#tag' => 'button',
            'class' => 'af-button btn btn-primary',
            'crm-icon' => 'fa-floppy-disk',
            'ng-click' => 'afform.submitDraft()',
            'ng-if' => 'afform.showSubmitButton',
            '#children' => [
              ['#text' => E::ts('Save Draft')],
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

      $perms = \Civi\Api4\Permission::get(FALSE)
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

  private static function getPlacementFilterOptions(): array {
    $entities = $entityFilterOptions = [];
    foreach (PlacementUtils::getPlacements() as $placement) {
      $entities += $placement['entities'];
    }
    foreach ($entities as $entityName) {
      $filterOptions = PlacementUtils::getEntityTypeFilterOptions($entityName);
      if ($filterOptions) {
        $entityFilterOptions[$entityName] = [
          'name' => PlacementUtils::getEntityTypeFilterName($entityName),
          'label' => PlacementUtils::getEntityTypeFilterLabel($entityName),
          'options' => $filterOptions,
        ];
      }
    }
    return $entityFilterOptions;
  }

  private static function getLocales(): array {
    $options = [];
    if (\CRM_Core_I18n::isMultiLingual()) {
      $languages = \CRM_Core_I18n::languages();
      $locales = \CRM_Core_I18n::getMultilingual();

      if (\Civi::settings()->get('force_translation_source_locale') ?? TRUE) {
        $defaultLocale = \Civi::settings()->get('lcMessages');
        $locales = [$defaultLocale];
      }

      foreach ($locales as $index => $locale) {
        $options[] = [
          'id' => $locale,
          'text' => $languages[$locale],
        ];
      }
    }
    return $options;
  }

}
