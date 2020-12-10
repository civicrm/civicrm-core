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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace Civi\Api4\Generic;

use Civi\API\Exception\NotImplementedException;

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
      $values = $actionClass->fields();
    }
    else {
      $values = $this->getRecords();
    }
    $this->formatResults($values);
    $this->queryArray($values, $result);
  }

  /**
   * Ensure every result contains, at minimum, the array keys as defined in $this->fields.
   *
   * Attempt to set some sensible defaults for some fields.
   *
   * Format option lists.
   *
   * In most cases it's not necessary to override this function, even if your entity is really weird.
   * Instead just override $this->fields and this function will respect that.
   *
   * @param array $values
   */
  protected function formatResults(&$values) {
    $fields = array_column($this->fields(), 'name');
    // Enforce field permissions
    if ($this->checkPermissions) {
      foreach ($values as $key => $field) {
        if (!empty($field['permission']) && !\CRM_Core_Permission::check($field['permission'])) {
          unset($values[$key]);
        }
      }
    }
    foreach ($values as &$field) {
      $defaults = array_intersect_key([
        'title' => empty($field['name']) ? NULL : ucwords(str_replace('_', ' ', $field['name'])),
        'entity' => $this->getEntityName(),
        'required' => FALSE,
        'options' => !empty($field['pseudoconstant']),
        'data_type' => \CRM_Utils_Array::value('type', $field, 'String'),
      ], array_flip($fields));
      $field += $defaults;
      $field['label'] = $field['label'] ?? $field['title'];
      if (isset($defaults['options'])) {
        $field['options'] = $this->formatOptionList($field['options']);
      }
      $field += array_fill_keys($fields, NULL);
    }
  }

  /**
   * Transforms option list into the format specified in $this->loadOptions
   *
   * @param $options
   * @return array|bool
   */
  private function formatOptionList($options) {
    if (!$this->loadOptions || !is_array($options)) {
      return (bool) $options;
    }
    if (!$options) {
      return $options;
    }
    $formatted = [];
    $first = reset($options);
    // Flat array requested
    if ($this->loadOptions === TRUE) {
      // Convert non-associative to flat array
      if (is_array($first) && isset($first['id'])) {
        foreach ($options as $option) {
          $formatted[$option['id']] = $option['label'] ?? $option['name'] ?? $option['id'];
        }
        return $formatted;
      }
      return $options;
    }
    // Non-associative array of multiple properties requested
    foreach ($options as $id => $option) {
      // Transform a flat list
      if (!is_array($option)) {
        $option = [
          'id' => $id,
          'name' => $option,
          'label' => $option,
        ];
      }
      $formatted[] = array_intersect_key($option, array_flip($this->loadOptions));
    }
    return $formatted;
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
   * Add an item to the values array
   * @param string $fieldName
   * @param mixed $value
   * @return $this
   */
  public function addValue(string $fieldName, $value) {
    $this->values[$fieldName] = $value;
    return $this;
  }

  /**
   * @param bool $includeCustom
   * @return $this
   */
  public function setIncludeCustom(bool $includeCustom) {
    // Be forgiving if the param doesn't exist and don't throw an exception
    if (property_exists($this, 'includeCustom')) {
      $this->includeCustom = $includeCustom;
    }
    return $this;
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
        'name' => 'default_value',
        'data_type' => 'String',
      ],
      [
        'name' => 'required',
        'data_type' => 'Boolean',
      ],
      [
        'name' => 'required_if',
        'data_type' => 'String',
      ],
      [
        'name' => 'options',
        'data_type' => 'Array',
      ],
      [
        'name' => 'data_type',
        'data_type' => 'String',
      ],
      [
        'name' => 'input_type',
        'data_type' => 'String',
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
    ];
  }

}
