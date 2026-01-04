<?php

namespace Civi\Api4\Action\Afform;

use Civi\Api4\SavedSearch;
use Civi\Api4\Utils\FormattingUtil;

/**
 * Loads option values for a form field
 *
 * @method $this setFieldName(string $fieldName)
 * @method $this setModelName(string $modelName)
 * @method $this setJoinEntity(string $joinEntity)
 * @method $this setValues(array $values)
 * @method string getFieldName()
 * @method string getModelName()
 * @method string getJoinEntity()
 * @method array getValues()
 * @package Civi\Api4\Action\Afform
 */
class GetOptions extends AbstractProcessor {

  /**
   * @var string
   * @required
   */
  protected $modelName;

  /**
   * @var string
   * @required
   */
  protected $fieldName;

  /**
   * @var string
   */
  protected $joinEntity;

  /**
   * @var array
   */
  protected $values;

  /**
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function processForm() {
    $formEntity = $this->_formDataModel->getEntity($this->modelName);
    $searchDisplay = $this->_formDataModel->getSearchDisplay($this->modelName);
    $fieldName = $this->fieldName;

    // For data-entry forms
    if ($formEntity) {
      $entity = $this->joinEntity ?: $formEntity['type'];
      if ($this->joinEntity && !isset($formEntity['joins'][$this->joinEntity]['fields'][$this->fieldName])) {
        throw new \CRM_Core_Exception('Cannot get options for field not present on form');
      }
      elseif (!$this->joinEntity && !isset($formEntity['fields'][$this->fieldName])) {
        throw new \CRM_Core_Exception('Cannot get options for field not present on form');
      }
    }
    // For search forms, get entity from savedSearch api params
    elseif ($searchDisplay) {
      if (!isset($searchDisplay['fields'][$this->fieldName])) {
        throw new \CRM_Core_Exception('Cannot get options for field not present on form');
      }
      $savedSearch = SavedSearch::get(FALSE)
        ->addWhere('name', '=', $searchDisplay['searchName'])
        ->addSelect('api_entity', 'api_params')
        ->execute()->single();
      // If field is not prefixed with a join, it's from the main entity
      $entity = $savedSearch['api_entity'];
      // Check to see if field belongs to a join
      foreach ($savedSearch['api_params']['join'] ?? [] as $join) {
        [$joinEntity, $joinAlias] = array_pad(explode(' AS ', $join[0]), 2, '');
        if (str_starts_with($fieldName, $joinAlias . '.')) {
          $entity = $joinEntity;
          $fieldName = substr($fieldName, strlen($joinAlias) + 1);
        }
      }
    }

    return civicrm_api4($entity, 'getFields', [
      'checkPermissions' => FALSE,
      'where' => [['name', '=', $fieldName]],
      'select' => ['options'],
      'loadOptions' => ['id', 'label'],
      'values' => FormattingUtil::filterByPath($this->values, $this->fieldName, $fieldName),
    ], 0)['options'] ?: [];
  }

  protected function loadEntities() {
    // Do nothing; this action doesn't need entity data
  }

}
