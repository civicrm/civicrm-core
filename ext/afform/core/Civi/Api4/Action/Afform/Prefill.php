<?php

namespace Civi\Api4\Action\Afform;

use Civi\Api4\Utils\CoreUtil;

/**
 * Class Prefill
 * @package Civi\Api4\Action\Afform
 */
class Prefill extends AbstractProcessor {

  /**
   * Name of the field being matched (typically 'id')
   * @var string
   */
  protected $matchField;

  protected function processForm() {
    $entityValues = $this->_entityValues;
    foreach ($entityValues as $afformEntityName => &$valueSets) {
      $afformEntity = $this->_formDataModel->getEntity($afformEntityName);
      if ($this->matchField) {
        $matchFieldDefn = $this->_formDataModel->getField($afformEntity['type'], $this->matchField, 'create');
        // When creating an entity based on an existing one e.g. Event.template_id
        if (($matchFieldDefn['input_attrs']['autofill'] ?? NULL) === 'create') {
          $idField = CoreUtil::getIdFieldName($afformEntity['type']);
          foreach ($valueSets as &$valueSet) {
            $valueSet['fields'][$this->matchField] = $valueSet['fields'][$idField];
            unset($valueSet['fields'][$idField]);
          }
        }
      }
    }
    return \CRM_Utils_Array::makeNonAssociative($entityValues, 'name', 'values');
  }

}
