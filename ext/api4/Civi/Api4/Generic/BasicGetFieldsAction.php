<?php

namespace Civi\Api4\Generic;

use Civi\Api4\Utils\ActionUtil;

/**
 * Get fields for an entity.
 *
 * @method $this setLoadOptions(bool $value)
 * @method bool getLoadOptions()
 * @method $this setAction(string $value)
 */
class BasicGetFieldsAction extends BasicGetAction {

  /**
   * Fetch option lists for fields?
   *
   * @var bool
   */
  protected $loadOptions = FALSE;

  /**
   * @var string
   */
  protected $action = 'get';

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
    $actionClass = ActionUtil::getAction($this->getEntityName(), $this->action);
    if (method_exists($actionClass, 'fields')) {
      $values = $actionClass->fields();
    }
    else {
      $values = $this->getRecords();
    }
    $this->padResults($values);
    $result->exchangeArray($this->queryArray($values));
  }

  /**
   * @param array $values
   */
  private function padResults(&$values) {
    foreach ($values as &$field) {
      $field += [
        'title' => ucwords(str_replace('_', ' ', $field['name'])),
        'entity' => $this->getEntityName(),
        'required' => FALSE,
        'options' => FALSE,
        'data_type' => 'String',
      ];
      if (!$this->loadOptions) {
        $field['options'] = (bool) $field['options'];
      }
    }
  }

  /**
   * @return string
   */
  public function getAction() {
    return $this->action;
  }

  public function fields() {
    return [
      [
        'name' => 'name',
        'data_type' => 'String',
      ],
      [
        'name' => 'title',
        'data_type' => 'String',
      ],
      [
        'name' => 'description',
        'data_type' => 'String',
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
        'name' => 'options',
        'data_type' => 'Array',
      ],
      [
        'name' => 'data_type',
        'data_type' => 'String',
      ],
      [
        'name' => 'fk_entity',
        'data_type' => 'String',
      ],
      [
        'name' => 'serialize',
        'data_type' => 'Integer',
      ],
    ];
  }

}
