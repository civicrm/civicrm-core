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
        $this->handleMatchField($afformEntity['type'], $valueSets);
      }
    }
    return \CRM_Utils_Array::makeNonAssociative($entityValues, 'name', 'values');
  }

  /**
   * Set entity values based on an existing record.
   *
   * This is used for e.g. Event.template_id,
   * based on 'autofill' => 'create' metadata in APIv4 getFields.
   *
   * @param string $entityType
   * @param array $valueSets
   */
  private function handleMatchField(string $entityType, array &$valueSets): void {
    $matchFieldDefn = $this->_formDataModel->getField($entityType, $this->matchField, 'create');
    // @see EventCreationSpecProvider for the `template_id` declaration which includes this 'autofill' = 'create' flag.
    if (($matchFieldDefn['input_attrs']['autofill'] ?? NULL) === 'create') {
      $idField = CoreUtil::getIdFieldName($entityType);
      foreach ($valueSets as &$valueSet) {
        $valueSet['fields'][$this->matchField] = $valueSet['fields'][$idField];
        unset($valueSet['fields'][$idField]);
      }
    }
  }

}
