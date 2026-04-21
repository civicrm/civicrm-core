<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Afform;

use Civi\Api4\Utils\FormattingUtil;
use Civi\Core\Event\GenericHookEvent;
use Civi\Search\Display;
use CRM_Afform_ExtensionUtil as E;

/**
 *
 * @package Civi\Afform
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class Utils {

  use \Civi\Api4\Utils\AfformSaveTrait;

  /**
   * Sorts entities according to references to each other
   *
   * Returns a list of entity names in order of when they should be processed,
   * so that an entity being referenced is saved before the entity referencing it.
   *
   * @param $formEntities
   * @param $entityValues
   * @return string[]
   */
  public static function getEntityWeights($formEntities, $entityValues) {
    $sorter = new \MJS\TopSort\Implementations\FixedArraySort();

    $formEntityNames = array_keys($formEntities);
    foreach ($formEntities as $entityName => $entity) {
      $references = [];
      foreach ($entityValues[$entityName] as $record) {
        foreach ($record['fields'] as $fieldName => $fieldValue) {
          foreach ((array) $fieldValue as $value) {
            if (in_array($value, $formEntityNames, TRUE) && $value !== $entityName) {
              $references[$value] = $value;
            }
          }
        }
      }
      $sorter->add($entityName, $references);
    }
    // Return the list of entities ordered by weight
    return $sorter->sort();
  }

  /**
   * Subset of APIv4 operators that are appropriate for use on Afforms
   *
   * This list may be further reduced by fields which declare a limited number of
   * operators in their metadata.
   *
   * @return array
   */
  public static function getSearchOperators() {
    return [
      '=' => '=',
      '!=' => '≠',
      '>' => '>',
      '<' => '<',
      '>=' => '≥',
      '<=' => '≤',
      'CONTAINS' => E::ts('Contains'),
      'NOT CONTAINS' => E::ts("Doesn't Contain"),
      'IN' => E::ts('Is One Of'),
      'NOT IN' => E::ts('Not One Of'),
      'LIKE' => E::ts('Is Like'),
      'NOT LIKE' => E::ts('Not Like'),
      'REGEXP' => E::ts('Matches Pattern'),
      'NOT REGEXP' => E::ts("Doesn't Match Pattern"),
      'REGEXP BINARY' => E::ts('Matches Pattern (case-sensitive)'),
      'NOT REGEXP BINARY' => E::ts("Doesn't Match Pattern (case-sensitive)"),
    ];
  }

  public static function getInputTypes(): array {
    $inputTypes = \Civi::cache('metadata')->get('afform.input_types');
    if ($inputTypes === NULL) {
      // Note: When adding a new input type, one must also create corresponding template and admin_template files.
      $inputTypes = [
        'ChainSelect' => [
          'label' => E::ts('Chain-Select'),
        ],
        'CheckBox' => [
          'label' => E::ts('Checkboxes'),
        ],
        'Date' => [
          'label' => E::ts('Date Picker'),
        ],
        'DisplayOnly' => [
          'label' => E::ts('Display Only'),
        ],
        'Email' => [
          'label' => E::ts('Email'),
          'extra_defn' => [
            'data_type' => 'String',
          ],
        ],
        'EntityRef' => [
          'label' => E::ts('Autocomplete Entity'),
        ],
        'File' => [
          'label' => E::ts('File'),
        ],
        'Hidden' => [
          'label' => E::ts('Hidden'),
        ],
        'Location' => [
          'label' => E::ts('Address Location'),
        ],
        'Number' => [
          'label' => E::ts('Number'),
          'extra_defn' => [
            'data_type' => 'Integer',
          ],
        ],
        'Radio' => [
          'label' => E::ts('Radio Buttons'),
        ],
        'Range' => [
          'label' => E::ts('Range'),
        ],
        'RichTextEditor' => [
          'label' => E::ts('Rich Text Editor'),
        ],
        'Select' => [
          'label' => E::ts('Select'),
        ],
        'Text' => [
          'label' => E::ts('Single-Line Text'),
          'extra_defn' => [
            'data_type' => 'String',
          ],
        ],
        'TextArea' => [
          'label' => E::ts('Multi-Line Text'),
          'extra_defn' => [
            'data_type' => 'String',
          ],
        ],
        'Toggle' => [
          'label' => E::ts('Toggle Switch'),
          'extra_defn' => [
            'data_type' => 'Boolean',
          ],
        ],
        'Url' => [
          'label' => E::ts('URL'),
          'extra_defn' => [
            'data_type' => 'String',
          ],
        ],
      ];
      // Input types shipped with Afform all follow this template file name convention,
      // but 3rd parties must specify their own template file names.
      foreach ($inputTypes as $name => &$inputType) {
        $inputType += [
          'module' => 'af',
          'admin_module' => 'afGuiEditor',
          'template' => "~/af/fields/$name.html",
          'admin_template' => "~/afGuiEditor/inputType/$name.html",
        ];
      }
      // Allow input types to be modified by event listeners
      $data = [
        'inputTypes' => &$inputTypes,
      ];
      $event = GenericHookEvent::create($data);
      \Civi::dispatcher()->dispatch('civi.afform.input_types', $event);

      // If module and admin_module are not specified, infer them from template file names.
      foreach ($inputTypes as &$inputType) {
        if (!isset($inputType['module']) && isset($inputType['template']) && str_starts_with($inputType['template'], '~/')) {
          [, $moduleName] = explode('/', $inputType['template']);
          $inputType['module'] = $moduleName;
        }
        if (!isset($inputType['admin_module']) && isset($inputType['admin_template']) && str_starts_with($inputType['admin_template'], '~/')) {
          [, $moduleName] = explode('/', $inputType['admin_template']);
          $inputType['admin_module'] = $moduleName;
        }
      }

      \Civi::cache('metadata')->set('afform.input_types', $inputTypes);
    }
    return $inputTypes;
  }

  public static function shouldReconcileManaged(array $updatedAfform, array $originalAfform = []): bool {
    $isChanged = function($field) use ($updatedAfform, $originalAfform) {
      return ($updatedAfform[$field] ?? NULL) !== ($originalAfform[$field] ?? NULL);
    };

    return $isChanged('placement') ||
      $isChanged('navigation') ||
      (!empty($updatedAfform['placement']) && $isChanged('title')) ||
      (!empty($updatedAfform['navigation']) && ($isChanged('title') || $isChanged('permission') || $isChanged('icon') || $isChanged('server_route')));
  }

  public static function shouldClearMenuCache(array $updatedAfform, array $originalAfform = []): bool {
    $isChanged = function($field) use ($updatedAfform, $originalAfform) {
      return ($updatedAfform[$field] ?? NULL) !== ($originalAfform[$field] ?? NULL);
    };

    return $isChanged('server_route') ||
      $isChanged('is_public') ||
      (!empty($updatedAfform['server_route']) && $isChanged('title'));
  }

  public static function formatViewValue(string $fieldName, array $fieldInfo, array $values, ?string $entityName = NULL, ?string $formName = NULL): string {
    $value = $values[$fieldName] ?? NULL;
    if (isset($value) && $value !== '') {
      $dataType = $fieldInfo['data_type'] ?? NULL;
      if (!empty($fieldInfo['options'])) {
        $value = FormattingUtil::replacePseudoconstant(array_column($fieldInfo['options'], 'label', 'id'), $value);
      }
      elseif (!empty($fieldInfo['fk_entity']) && $formName) {
        $autocomplete = civicrm_api4($fieldInfo['fk_entity'], 'autocomplete', [
          'checkPermissions' => FALSE,
          'formName' => "afform:$formName",
          'fieldName' => "$entityName:$fieldName",
          'ids' => (array) $value,
        ]);
        $value = $autocomplete->column('label');
      }
      elseif ($dataType === 'Boolean') {
        $value = $value ? ts('Yes') : ts('No');
      }
      elseif ($dataType === 'Date' || $dataType === 'Timestamp') {
        $value = \CRM_Utils_Date::customFormat($value);
      }
      if (is_array($value)) {
        $value = implode(', ', $value);
      }
    }
    return $value ?? '';
  }

  public static function initSourceTranslations() {
    $allAfforms = \Civi::service('afform_scanner')->findFilePaths();
    foreach ($allAfforms as $name => $path) {
      $fullpath = array_values($path)[0] . '.aff.html';
      $html = file_get_contents($fullpath);

      // Get title.
      $form = \Civi\Api4\Afform::get(FALSE)
        ->addWhere('name', '=', $name)
        ->addSelect('title')
        ->execute()
        ->first();

      self::saveTranslations($form, $html);
    }
  }

  public static function getSearchDisplayTags(): array {
    $displayTags = array_column(Display::getDisplayTypes(['name'], TRUE), 'name');
    $displayTags[] = 'crm-search-display';
    return $displayTags;
  }

}
