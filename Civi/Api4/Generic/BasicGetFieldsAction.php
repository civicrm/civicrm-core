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

namespace Civi\Api4\Generic;

use Civi\API\Exception\NotImplementedException;
use Civi\Api4\Utils\CoreUtil;

/**
 * Lists information about fields for the $ENTITY entity.
 *
 * This field information is also known as "metadata."
 *
 * Note that different actions may support different lists of fields.
 * By default this will fetch the field list relevant to `get`,
 * but a different list may be returned if you specify another action.
 *
 * @method $this setLoadOptions(bool|array $value)
 * @method bool|array getLoadOptions()
 * @method $this setAction(string $value)
 * @method $this setValues(array $values)
 * @method array getValues()
 */
class BasicGetFieldsAction extends BasicGetAction {

  use Traits\GetSetValueTrait;

  /**
   * Fetch option lists for fields?
   *
   * This parameter can be either a boolean or an array of attributes to return from the option list:
   *
   * - If `FALSE`, each field's `options` property will be a boolean indicating whether the field has an option list
   * - If `TRUE`, `options` will be returned as a flat array of the option list's `[id => label]`
   * - If an array, `options` will be a non-associative array of requested properties:
   *   id, name, label, abbr, description, color, icon
   *   e.g. `loadOptions: ['id', 'name', 'label']` will return an array like `[[id: 1, name: 'Meeting', label: 'Meeting'], ...]`
   *   (note that names and labels are generally ONLY the same when the site's language is set to English).
   *
   * @var bool|array
   */
  protected $loadOptions = FALSE;

  /**
   * Fields will be returned appropriate to the specified action (get, create, delete, etc.)
   *
   * @var string
   */
  protected $action = 'get';

  /**
   * Fields will be returned appropriate to the specified values (e.g. ['contact_type' => 'Individual'])
   *
   * @var array
   */
  protected $values = [];

  /**
   * @var bool
   * @deprecated
   */
  protected $includeCustom;

  /**
   * To implement getFields for your own entity:
   *
   * 1. From your entity class add a static getFields method.
   * 2. That method should construct and return this class.
   * 3. The 3rd argument passed to this constructor should be a function that returns an
   *    array of fields for your entity's CRUD actions.
   * 4. For non-crud actions that need a different set of fields, you can override the
   *    list from step 3 on a per-action basis by defining a fields() method in that action.
   *    See for example BasicGetFieldsAction::fields() or GetActions::fields().
   *
   * @param Result $result
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public function _run(Result $result) {
    try {
      $actionClass = \Civi\API\Request::create($this->getEntityName(), $this->getAction(), ['version' => 4]);
    }
    catch (NotImplementedException $e) {
    }
    if (isset($actionClass) && method_exists($actionClass, 'fields')) {
      $values = $actionClass->fields($this);
    }
    else {
      $values = $this->getRecords();
    }
    // $isInternal param is not part of function signature (to be compatible with parent class)
    $isInternal = func_get_args()[1] ?? FALSE;
    $this->formatResults($values, $isInternal);
    $this->queryArray($values, $result);
  }

  /**
   * Ensure every result contains, at minimum, the array keys as defined in $this->fields.
   *
   * Attempt to set some sensible defaults for some fields.
   *
   * Format option lists.
   *
   * Configure read-only input fields based on the action.
   *
   * In most cases it's not necessary to override this function, even if your entity is really weird.
   * Instead just override $this->fields and this function will respect that.
   *
   * @param array $values
   * @param bool $isInternal
   */
  protected function formatResults(&$values, $isInternal) {
    $fieldDefaults = array_column($this->fields(), 'default_value', 'name') +
      array_fill_keys(array_column($this->fields(), 'name'), NULL);
    // Enforce field permissions
    if ($this->checkPermissions) {
      foreach ($values as $key => $field) {
        if (!empty($field['permission']) && !\CRM_Core_Permission::check($field['permission'])) {
          unset($values[$key]);
        }
      }
    }
    // Unless this is an internal getFields call, filter out @internal properties
    $internalProps = $isInternal ? [] : array_filter(array_column($this->fields(), '@internal', 'name'));
    foreach ($values as &$field) {
      $defaults = array_intersect_key([
        'title' => empty($field['name']) ? NULL : ucwords(str_replace('_', ' ', $field['name'])),
        'entity' => $this->getEntityName(),
        'options' => !empty($field['pseudoconstant']),
      ], $fieldDefaults);
      $field += $defaults + $fieldDefaults;
      if (array_key_exists('label', $fieldDefaults)) {
        $field['label'] ??= $field['title'] ?? $field['name'];
      }
      if (isset($field['options']) && is_array($field['options']) && empty($field['suffixes']) && array_key_exists('suffixes', $field)) {
        $this->setFieldSuffixes($field);
      }
      if (isset($defaults['options'])) {
        $this->formatOptionList($field);
      }
      if ($this->getAction() === 'create' && $field['readonly'] === TRUE) {
        $field['input_type'] = 'DisplayOnly';
      }
      $field = array_diff_key($field, $internalProps);
    }
    // Hide the 'contact_type' field from Individual,Organization,Household pseudo-entities
    if (!$isInternal && $this->getEntityName() !== 'Contact' && CoreUtil::isContact($this->getEntityName())) {
      $values = array_filter($values, function($field) {
        return $field['name'] !== 'contact_type';
      });
    }
  }

  /**
   * Sets `options` and `suffixes` based on pseudoconstant if given.
   *
   * Transforms option list into the format specified in $this->loadOptions.
   *
   * @param array $field
   */
  private function formatOptionList(&$field) {
    $optionsExist = isset($field['options']) && is_array($field['options']);
    if (!isset($field['options'])) {
      $field['options'] = !empty($field['pseudoconstant']);
    }
    if (!empty($field['pseudoconstant']['optionGroupName'])) {
      $field['suffixes'] = CoreUtil::getOptionValueFields($field['pseudoconstant']['optionGroupName']);
    }
    // no need to load options, or no way to do so
    if (!$this->loadOptions || (!$optionsExist && empty($field['pseudoconstant']))) {
      $field['options'] = (bool) $field['options'];
      return;
    }
    // need to load options AND either options already exist or a pseudoconstant is defined)
    if (!empty($field['pseudoconstant'])) {
      $field['options'] = $this->getPseudoconstantOptions($field);
    }
    $field['options'] = CoreUtil::formatOptionList($field['options'], $this->loadOptions);
  }

  /**
   * Set supported field suffixes based on available option keys
   * @param array $field
   */
  private function setFieldSuffixes(array &$field) {
    // These suffixes are always supported if a field has options
    $field['suffixes'] = ['name', 'label'];
    $firstOption = reset($field['options']);
    // If first option is an array, merge in those keys as available suffixes
    if (is_array($firstOption)) {
      // Remove 'id' because there is no practical reason to use it as a field suffix
      $otherKeys = array_diff(array_keys($firstOption), ['id', 'name', 'label']);
      $field['suffixes'] = array_merge($field['suffixes'], $otherKeys);
    }
  }

  /**
   * @return string
   */
  public function getAction() {
    // For actions that build on top of other actions, return fields for the simpler action
    $sub = [
      'save' => 'create',
      'replace' => 'create',
    ];
    return $sub[$this->action] ?? $this->action;
  }

  /**
   * Resolve pseudoconstant options
   *
   * @param array $field
   * @throws \CRM_Core_Exception
   */
  protected function getPseudoconstantOptions(array $field): array {
    if (!empty($field['pseudoconstant']['optionGroupName'])) {
      return $this->getOptionValues($field['pseudoconstant']['optionGroupName']);
    }
    if (!empty($field['pseudoconstant']['callback'])) {
      return $this->getCallbackOptions($field);
    }
    throw new \CRM_Core_Exception('Unsupported pseudoconstant type for field "' . $field['name'] . '"');
  }

  private function getCallbackOptions(array $field): array {
    // first inspect the callback to see whether it varies based on row values or not
    $cacheKey = $this->getCallbackCacheKey($field);
    if ($cacheKey) {
      $cacheValue = $cacheKey ? \Civi::cache('metadata')->get($cacheKey) : NULL;
      if (is_array($cacheValue)) {
        return $cacheValue;
      }
    }
    $args = [$field['name'], ['values' => $this->getValues()]];
    $value = \Civi\Core\Resolver::singleton()->call($field['pseudoconstant']['callback'], $args);
    if ($cacheKey) {
      \Civi::cache('metadata')->set($cacheKey, $value);
    }
    return $value;
  }

  private function getCallbackCacheKey($field): ?string {
    $reflector = \Civi\Core\Resolver::singleton()->getReflector($field['pseudoconstant']['callback']);
    // we need to stringify the callback itself - depends on why
    $callbackName = match ($reflector::class) {
      'ReflectionMethod' => "{$reflector->class}::{$reflector->name}",
      default => NULL,
    };
    // if we dont know how to stringify the callback then we cant cache
    if (!$callbackName) {
      return NULL;
    }
    switch ($reflector->getNumberOfParameters()) {
      case 0:
        // no args are passed, can cache using just the callback name
        return implode('_', [\CRM_Core_Config::domainID(), \CRM_Core_I18n::getLocale(), 'pseudoconstantCallback', $callbackName]);

      case 1:
        // callback takes field name, include that in the cache key
        return implode('_', [\CRM_Core_Config::domainID(), \CRM_Core_I18n::getLocale(), 'pseudoconstantCallback', $callbackName, $field['name']]);

      default:
        // callback takes row values - dont attempt to cache
        return NULL;
    };
    if ($cacheKeyParts) {
      return implode('_', $cacheKeyParts);
    }

  }

  private function getOptionValues(string $optionGroupName): array {
    $cacheKey = implode('_', [
      \CRM_Core_Config::domainID(),
      \CRM_Core_I18n::getLocale(),
      'optionGroup',
      $optionGroupName,
    ]);
    $options = \Civi::cache('metadata')->get($cacheKey);

    if (!is_array($options)) {
      $options = $this->fetchOptionValues($optionGroupName);
      \Civi::cache('metadata')->set($cacheKey, $options);
    }

    return $options;
  }

  private function fetchOptionValues(string $optionGroupName): array {
    $optionGroupId = \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $optionGroupName, 'id', 'name');
    $select = CoreUtil::getOptionValueFields($optionGroupName);
    unset($select['id'], $select['value']);
    array_unshift($select, 'value AS id');
    $query = "SELECT " . implode(', ', $select) . " FROM civicrm_option_value WHERE option_group_id = %1 ORDER BY weight";
    return \CRM_Core_DAO::executeQuery($query, [1 => [$optionGroupId, 'Int']])->fetchAll();
  }

  public function fields() {
    return [
      [
        'name' => 'name',
        'data_type' => 'String',
        'description' => ts('Unique field identifier'),
      ],
      [
        'name' => 'title',
        'data_type' => 'String',
        'description' => ts('Technical name of field, shown in API and exports'),
      ],
      [
        'name' => 'label',
        'data_type' => 'String',
        'description' => ts('User-facing label, shown on most forms and displays'),
      ],
      [
        'name' => 'description',
        'data_type' => 'String',
        'description' => ts('Explanation of the purpose of the field'),
      ],
      [
        'name' => 'type',
        'data_type' => 'String',
        'default_value' => 'Field',
        'options' => [
          'Field' => ts('Primary Field'),
          'Custom' => ts('Custom Field'),
          'Filter' => ts('Search Filter'),
          'Extra' => ts('Extra API Field'),
        ],
      ],
      [
        'name' => 'default_value',
        'data_type' => 'String',
      ],
      [
        'name' => 'required',
        'description' => 'Is this field required when creating a new entity',
        'data_type' => 'Boolean',
        'default_value' => FALSE,
      ],
      [
        'name' => 'nullable',
        'description' => 'Whether a null value is allowed in this field',
        'data_type' => 'Boolean',
        'default_value' => TRUE,
      ],
      [
        'name' => 'required_if',
        'data_type' => 'String',
      ],
      [
        'name' => 'options',
        'data_type' => 'Array',
        'default_value' => FALSE,
      ],
      [
        'name' => 'pseudoconstant',
        '@internal' => TRUE,
      ],
      [
        'name' => 'suffixes',
        'data_type' => 'Array',
        'default_value' => NULL,
        'options' => ['name', 'label', 'description', 'abbr', 'color', 'icon'],
        'description' => 'Available option transformations, e.g. :name, :label',
      ],
      [
        'name' => 'operators',
        'data_type' => 'Array',
        'description' => 'If set, limits the operators that can be used on this field for "get" actions.',
      ],
      [
        'name' => 'data_type',
        'default_value' => 'String',
        'options' => [
          'Array' => ts('Array'),
          'Boolean' => ts('Boolean'),
          'Date' => ts('Date'),
          'Float' => ts('Float'),
          'Integer' => ts('Integer'),
          'Money' => ts('Money'),
          'String' => ts('String'),
          'Text' => ts('Text'),
          'Timestamp' => ts('Timestamp'),
        ],
      ],
      [
        'name' => 'input_type',
        'data_type' => 'String',
        'options' => [
          'ChainSelect' => ts('Chain-Select'),
          'CheckBox' => ts('Checkboxes'),
          'Date' => ts('Date Picker'),
          'DisplayOnly' => ts('Display Only'),
          'Email' => ts('Email'),
          'EntityRef' => ts('Autocomplete Entity'),
          'File' => ts('File'),
          'Hidden' => ts('Hidden'),
          'Location' => ts('Address Location'),
          'Number' => ts('Number'),
          'Radio' => ts('Radio Buttons'),
          'RichTextEditor' => ts('Rich Text Editor'),
          'Select' => ts('Select'),
          'Text' => ts('Single-Line Text'),
          'TextArea' => ts('Multi-Line Text'),
          'Toggle' => ts('Toggle Switch'),
          'Url' => ts('URL'),
        ],
      ],
      [
        'name' => 'input_attrs',
        'data_type' => 'Array',
      ],
      [
        'name' => 'fk_entity',
        'data_type' => 'String',
      ],
      [
        'name' => 'serialize',
        'data_type' => 'Integer',
      ],
      [
        'name' => 'entity',
        'data_type' => 'String',
      ],
      [
        'name' => 'localizable',
        'data_type' => 'Boolean',
        'default_value' => FALSE,
      ],
      [
        'name' => 'readonly',
        'data_type' => 'Boolean',
        'description' => 'True for auto-increment, calculated, or otherwise non-editable fields.',
        'default_value' => FALSE,
      ],
      [
        'name' => 'deprecated',
        'data_type' => 'Boolean',
        'default_value' => FALSE,
      ],
      [
        'name' => 'permission',
        'data_type' => 'Array',
      ],
      [
        'name' => 'usage',
        'data_type' => 'Array',
        'description' => 'Contexts in which field is used.',
        'default_value' => [],
      ],
      [
        'name' => 'output_formatters',
        'data_type' => 'Array',
        '@internal' => TRUE,
      ],
    ];
  }

}
