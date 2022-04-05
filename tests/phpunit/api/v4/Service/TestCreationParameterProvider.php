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


namespace api\v4\Service;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\Provider\FinancialItemCreationSpecProvider;
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
   * Get the required fields for the api entity + action.
   *
   * @param string $entity
   *
   * @return array
   * @throws \API_Exception
   */
  public function getRequired(string $entity) {
    $requiredFields = civicrm_api4($entity, 'getfields', [
      'action' => 'create',
      'loadOptions' => TRUE,
      'where' => [
        ['OR', [['required', '=', TRUE], ['required_if', 'IS NOT EMPTY']]],
        ['readonly', 'IS EMPTY'],
      ],
    ], 'name');

    $requiredParams = [];
    foreach ($requiredFields as $fieldName => $requiredField) {
      $requiredParams[$fieldName] = $this->getRequiredValue($requiredField);
    }

    // This is a ruthless hack to avoid peculiar constraints - but
    // it's also a test class & hard to care enough to do something
    // better
    $overrides = [];
    $overrides['UFField'] = [
      'field_name' => 'activity_campaign_id',
    ];
    $overrides['Translation'] = [
      'entity_table' => 'civicrm_event',
      'entity_field' => 'description',
      'entity_id' => \CRM_Core_DAO::singleValueQuery('SELECT min(id) FROM civicrm_event'),
    ];

    if (isset($overrides[$entity])) {
      $requiredParams = array_merge($requiredParams, $overrides[$entity]);
    }

    return $requiredParams;
  }

  /**
   * Attempt to get a value using field option, defaults, FKEntity, or a random
   * value based on the data type.
   *
   * @param array $field
   *
   * @return mixed
   * @throws \Exception
   */
  private function getRequiredValue(array $field) {

    if (!empty($field['options'])) {
      return key($field['options']);
    }
    if (!empty($field['fk_entity'])) {
      return $this->getFkID($field['fk_entity']);
    }
    if (isset($field['default_value'])) {
      return $field['default_value'];
    }
    if ($field['name'] === 'contact_id') {
      return $this->getFkID('Contact');
    }
    if ($field['name'] === 'entity_id') {
      // What could possibly go wrong with this?
      switch ($field['table_name'] ?? NULL) {
        case 'civicrm_financial_item':
          return $this->getFkID(FinancialItemCreationSpecProvider::DEFAULT_ENTITY);

        default:
          return $this->getFkID('Contact');
      }
    }

    $randomValue = $this->getRandomValue($field['data_type']);

    if ($randomValue) {
      return $randomValue;
    }

    throw new \API_Exception('Could not provide default value');
  }

  /**
   * @param \Civi\Api4\Service\Spec\FieldSpec $field
   *
   * @return mixed
   */
  private function getOption(FieldSpec $field) {
    $options = array_column($field->getOptions(), 'label', 'id');
    return key($options);
  }

  /**
   * Get an ID for the appropriate entity.
   *
   * @param string $fkEntity
   *
   * @return mixed
   *
   * @throws \API_Exception
   */
  private function getFkID(string $fkEntity) {
    $params = ['checkPermissions' => FALSE];
    // Be predictable about what type of contact we select
    if ($fkEntity === 'Contact') {
      $params['where'] = [['contact_type', '=', 'Individual']];
    }
    $entityList = civicrm_api4($fkEntity, 'get', $params);
    // If no existing entities, create one
    if ($entityList->count() < 1) {
      $entityList = civicrm_api4($fkEntity, 'create', [
        'checkPermissions' => FALSE,
        'values' => $this->getRequired($fkEntity),
      ]);
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
        return random_int(1, 2000);

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
