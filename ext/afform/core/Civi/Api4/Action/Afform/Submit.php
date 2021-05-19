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

  protected function processForm() {
    $entityValues = $entityIds = $entityMapping = $entityRefFields = [];
    foreach ($this->_formDataModel->getEntities() as $entityName => $entity) {
      $entityIds[$entityName] = NULL;
      $entityMapping[$entityName] = $entity['type'];
      $entityFields = \civicrm_api4($entity['type'], 'getFields', ['checkPermissions' => FALSE]);
      foreach ($entityFields as $field) {
        if ($field['input_type'] === 'EntityRef') {
          $entityRefFields[] = $field['name'];
        }
      }
      foreach ($this->values[$entityName] ?? [] as $values) {
        $entityValues[$entity['type']][$entityName][] = $values + ['fields' => []];
        // Predetermined values override submitted values
        if (!empty($entity['data'])) {
          foreach ($entityValues[$entity['type']][$entityName] as $index => $vals) {
            $entityValues[$entity['type']][$entityName][$index]['fields'] = $entity['data'] + $vals['fields'];
          }
        }
      }
    }
    $entityWeights = \Civi\Afform\Utils::getEntityWeights($this->_formDataModel->getEntities(), $entityValues);
    $event = new AfformSubmitEvent($this->_afform, $this->_formDataModel, $this, [], '', '', $entityIds);
    foreach ($entityWeights as $entity => $weight) {
      $eValues = $entityValues[$entityMapping[$entity]][$entity];
      // Replace Entity reference fields that reference other form based entities with their created ids.
      foreach ($eValues as $key => $record) {
        foreach ($record as $k => $v) {
          foreach ($v as $field => $value) {
            if (array_key_exists($value, $event->entityIds) && !empty($event->entityIds[$value]) && in_array($field, $entityRefFields, TRUE)) {
              $eValues[$key][$k][$field] = $event->entityIds[$value];
            }
          }
        }
        $event->setValues($eValues[$key]);
        $event->setEntityName($entity);
        $event->setEntityType($entityMapping[$entity]);
        \Civi::dispatcher()->dispatch(self::EVENT_NAME, $event);
      }
      unset($entityValues[$entityMapping[$entity]][$entity]);
    }
    foreach ($entityValues as $entityType => $entities) {
      if (!empty($entities)) {
        throw new \API_Exception(sprintf("Failed to process entities (type=%s; name=%s)", $entityType, implode(',', array_keys($entities))));
      }
    }

    // What should I return?
    return [];
  }

  /**
   * @param \Civi\Afform\Event\AfformSubmitEvent $event
   * @throws \API_Exception
   * @see afform_civicrm_config
   */
  public static function processContacts(AfformSubmitEvent $event) {
    if ($event->entityType !== 'Contact') {
      return;
    }
    $entityName = $event->entityName;
    $api4 = $event->formDataModel->getSecureApi4($entityName);
    $saved = $api4('Contact', 'save', ['records' => [$event->values['fields']]])->first();
    $event->entityIds[$entityName] = $saved['id'];
    self::saveJoins('Contact', $saved['id'], $event->values['joins'] ?? []);
  }

  /**
   * @param \Civi\Afform\Event\AfformSubmitEvent $event
   * @throws \API_Exception
   * @see afform_civicrm_config
   */
  public static function processGenericEntity(AfformSubmitEvent $event) {
    if ($event->entityType === 'Contact') {
      return;
    }
    $entityName = $event->entityName;
    $api4 = $event->formDataModel->getSecureApi4($event->entityName);
    $saved = $api4($event->entityType, 'save', ['records' => [$event->values['fields']]])->first();
    $event->entityIds[$entityName] = $saved['id'];
    self::saveJoins($event->entityType, $saved['id'], $event->values['joins'] ?? []);
  }

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

}
