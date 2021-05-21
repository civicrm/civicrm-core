<?php

namespace Civi\Api4\Action\Afform;

use Civi\Afform\Event\AfformSubmitEvent;

/**
 * Class Submit
 * @package Civi\Api4\Action\Afform
 */
class Submit extends AbstractProcessor {

  const EVENT_NAME = 'civi.afform.submit';

  /**
   * Submitted values
   * @var array
   * @required
   */
  protected $values;

  /**
   * Ids of each saved entity.
   *
   * Each key in the array corresponds to the name of an entity,
   * and the value is an array of ids
   * (because of `<af-repeat>` all entities are treated as if they may be multi)
   * E.g. $entityIds['Individual1'] = [1];
   *
   * @var array
   */
  private $entityIds = [];

  protected function processForm() {
    $entityValues = [];
    foreach ($this->_formDataModel->getEntities() as $entityName => $entity) {
      $this->entityIds[$entityName] = [];
      $entityValues[$entityName] = [];

      // Gather submitted field values from $values['fields'] and sub-entities from $values['joins']
      foreach ($this->values[$entityName] ?? [] as $values) {
        $values['fields'] = $values['fields'] ?? [];
        $entityValues[$entityName][] = $values;
      }
      // Predetermined values override submitted values
      if (!empty($entity['data'])) {
        foreach ($entityValues[$entityName] as $index => $vals) {
          $entityValues[$entityName][$index]['fields'] = $entity['data'] + $vals['fields'];
        }
      }
    }
    $entityWeights = \Civi\Afform\Utils::getEntityWeights($this->_formDataModel->getEntities(), $entityValues);
    foreach ($entityWeights as $entityName) {
      $entityType = $this->_formDataModel->getEntity($entityName)['type'];
      $records = $this->replaceReferences($entityName, $entityValues[$entityName]);
      $event = new AfformSubmitEvent($this->_afform, $this->_formDataModel, $this, $records, $entityType, $entityName, $this->entityIds);
      \Civi::dispatcher()->dispatch(self::EVENT_NAME, $event);
    }

    // What should I return?
    return [];
  }

  /**
   * Replace Entity reference fields with the id of the referenced entity.
   * @param string $entityName
   * @param $records
   */
  private function replaceReferences($entityName, $records) {
    $entityNames = array_diff(array_keys($this->entityIds), [$entityName]);
    $entityType = $this->_formDataModel->getEntity($entityName)['type'];
    foreach ($records as $key => $record) {
      foreach ($record['fields'] as $field => $value) {
        if (array_intersect($entityNames, (array) $value) && $this->getEntityField($entityType, $field)['input_type'] === 'EntityRef') {
          if (is_array($value)) {
            foreach ($value as $i => $val) {
              if (in_array($val, $entityNames, TRUE)) {
                $records[$key]['fields'][$field][$i] = $this->entityIds[$val][0];
              }
            }
          }
          else {
            $records[$key]['fields'][$field] = $this->entityIds[$value][0];
          }
        }
      }
    }
    return $records;
  }

  /**
   * @param \Civi\Afform\Event\AfformSubmitEvent $event
   * @throws \API_Exception
   * @see afform_civicrm_config
   */
  public static function processGenericEntity(AfformSubmitEvent $event) {
    $api4 = $event->getSecureApi4();
    foreach ($event->records as $index => $record) {
      $saved = $api4($event->getEntityType(), 'save', ['records' => [$record['fields']]])->first();
      $event->setEntityId($index, $saved['id']);
      self::saveJoins($event->getEntityType(), $saved['id'], $record['joins'] ?? []);
    }
  }

  /**
   * This saves joins (sub-entities) such as Email, Address, Phone, etc.
   *
   * @param $mainEntityName
   * @param $entityId
   * @param $joins
   * @throws \API_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  protected static function saveJoins($mainEntityName, $entityId, $joins) {
    foreach ($joins as $joinEntityName => $join) {
      $values = self::filterEmptyJoins($joinEntityName, $join);
      // TODO: REPLACE works for creating or updating contacts, but different logic would be needed if
      // the contact was being auto-updated via a dedupe rule; in that case we would not want to
      // delete any existing records.
      if ($values) {
        civicrm_api4($joinEntityName, 'replace', [
          // Disable permission checks because the main entity has already been vetted
          'checkPermissions' => FALSE,
          'where' => self::getJoinWhereClause($mainEntityName, $joinEntityName, $entityId),
          'records' => $values,
        ]);
      }
      // REPLACE doesn't work if there are no records, have to use DELETE
      else {
        try {
          civicrm_api4($joinEntityName, 'delete', [
            // Disable permission checks because the main entity has already been vetted
            'checkPermissions' => FALSE,
            'where' => self::getJoinWhereClause($mainEntityName, $joinEntityName, $entityId),
          ]);
        }
        catch (\API_Exception $e) {
          // No records to delete
        }
      }
    }
  }

  /**
   * Filter out joins that have been left blank on the form
   *
   * @param $entity
   * @param $join
   * @return array
   */
  private static function filterEmptyJoins($entity, $join) {
    return array_filter($join, function($item) use($entity) {
      switch ($entity) {
        case 'Email':
          return !empty($item['email']);

        case 'Phone':
          return !empty($item['phone']);

        case 'IM':
          return !empty($item['name']);

        case 'Website':
          return !empty($item['url']);

        default:
          \CRM_Utils_Array::remove($item, 'id', 'is_primary', 'location_type_id', 'entity_id', 'contact_id', 'entity_table');
          return (bool) array_filter($item);
      }
    });
  }

  /**
   * @return array
   */
  public function getValues():array {
    return $this->values;
  }

  /**
   * @param array $values
   * @return $this
   */
  public function setValues(array $values) {
    $this->values = $values;
    return $this;
  }

}
