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
 * $Id$
 *
 */


namespace api\v4\Service;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\SpecGatherer;

class TestCreationParameterProvider {

  /**
   * @var \Civi\Api4\Service\Spec\SpecGatherer
   */
  protected $gatherer;

  /**
   * @param \Civi\Api4\Service\Spec\SpecGatherer $gatherer
   */
  public function __construct(SpecGatherer $gatherer) {
    $this->gatherer = $gatherer;
  }

  /**
   * @param $entity
   *
   * @return array
   */
  public function getRequired($entity) {
    $createSpec = $this->gatherer->getSpec($entity, 'create', FALSE);
    $requiredFields = array_merge($createSpec->getRequiredFields(), $createSpec->getConditionalRequiredFields());

    if ($entity === 'Contact') {
      $requiredFields[] = $createSpec->getFieldByName('first_name');
      $requiredFields[] = $createSpec->getFieldByName('last_name');
    }

    $requiredParams = [];
    foreach ($requiredFields as $requiredField) {
      $value = $this->getRequiredValue($requiredField);
      $requiredParams[$requiredField->getName()] = $value;
    }

    unset($requiredParams['id']);

    return $requiredParams;
  }

  /**
   * Attempt to get a value using field option, defaults, FKEntity, or a random
   * value based on the data type.
   *
   * @param \Civi\Api4\Service\Spec\FieldSpec $field
   *
   * @return mixed
   * @throws \Exception
   */
  private function getRequiredValue(FieldSpec $field) {

    if ($field->getOptions()) {
      return $this->getOption($field);
    }
    elseif ($field->getDefaultValue()) {
      return $field->getDefaultValue();
    }
    elseif ($field->getFkEntity()) {
      return $this->getFkID($field, $field->getFkEntity());
    }
    elseif (in_array($field->getName(), ['entity_id', 'contact_id'])) {
      return $this->getFkID($field, 'Contact');
    }

    $randomValue = $this->getRandomValue($field->getDataType());

    if ($randomValue) {
      return $randomValue;
    }

    throw new \Exception('Could not provide default value');
  }

  /**
   * @param \Civi\Api4\Service\Spec\FieldSpec $field
   *
   * @return mixed
   */
  private function getOption(FieldSpec $field) {
    $options = $field->getOptions();
    return array_rand($options);
  }

  /**
   * @param \Civi\Api4\Service\Spec\FieldSpec $field
   * @param string $fkEntity
   *
   * @return mixed
   * @throws \Exception
   */
  private function getFkID(FieldSpec $field, $fkEntity) {
    $params = ['checkPermissions' => FALSE];
    // Be predictable about what type of contact we select
    if ($fkEntity === 'Contact') {
      $params['where'] = [['contact_type', '=', 'Individual']];
    }
    $entityList = civicrm_api4($fkEntity, 'get', $params);
    if ($entityList->count() < 1) {
      $msg = sprintf('At least one %s is required in test', $fkEntity);
      throw new \Exception($msg);
    }

    return $entityList->last()['id'];
  }

  /**
   * @param $dataType
   *
   * @return int|null|string
   */
  private function getRandomValue($dataType) {
    switch ($dataType) {
      case 'Boolean':
        return TRUE;

      case 'Integer':
        return rand(1, 2000);

      case 'String':
        return \CRM_Utils_String::createRandom(10, implode('', range('a', 'z')));

      case 'Text':
        return \CRM_Utils_String::createRandom(100, implode('', range('a', 'z')));

      case 'Money':
        return sprintf('%d.%2d', rand(0, 2000), rand(10, 99));

      case 'Date':
        return '20100102';

      case 'Timestamp':
        return 'now';
    }

    return NULL;
  }

}
