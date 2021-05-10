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
    $entityValues = [];
    foreach ($this->_formDataModel->getEntities() as $entityName => $entity) {
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
    $event = new AfformSubmitEvent($this->_afform, $this->_formDataModel, $this, $this->_formDataModel->getEntities(), $entityValues);
    \Civi::dispatcher()->dispatch(self::EVENT_NAME, $event);
    foreach ($event->entityValues as $entityType => $entities) {
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
    foreach ($event->entityValues['Contact'] ?? [] as $entityName => $contacts) {
      $api4 = $event->formDataModel->getSecureApi4($entityName);
      foreach ($contacts as $contact) {
        $saved = $api4('Contact', 'save', ['records' => [$contact['fields']]])->first();
        self::saveJoins($api4, 'Contact', $saved['id'], $contact['joins'] ?? []);
      }
    }
    unset($event->entityValues['Contact']);
  }

  /**
   * @param \Civi\Afform\Event\AfformSubmitEvent $event
   * @throws \API_Exception
   * @see afform_civicrm_config
   */
  public static function processGenericEntity(AfformSubmitEvent $event) {
    foreach ($event->entityValues as $entityType => $entities) {
      // Each record is an array of one or more items (can be > 1 if af-repeat is used)
      foreach ($entities as $entityName => $records) {
        $api4 = $event->formDataModel->getSecureApi4($entityName);
        foreach ($records as $record) {
          $saved = $api4($entityType, 'save', ['records' => [$record['fields']]])->first();
          self::saveJoins($api4, $entityType, $saved['id'], $record['joins'] ?? []);
        }
      }
      unset($event->entityValues[$entityType]);
    }
  }

  protected static function saveJoins($api4, $mainEntityName, $entityId, $joins) {
    foreach ($joins as $joinEntityName => $join) {
      $values = self::filterEmptyJoins($joinEntityName, $join);
      // FIXME: Replace/delete should only be done to known contacts
      if ($values) {
        foreach ($values as $value) {
          if (array_key_exists('id', $value)) {
            $api4($joinEntityName, 'replace', [
              'where' => self::getJoinWhereClause($mainEntityName, $joinEntityName, $entityId),
              'records' => [$value],
            ]);
          }
          else {
            $value['contact_id'] = $entityId;
            $api4($joinEntityName, 'create', ['values' => $value]);
          }
        }
      }
      else {
        try {
          $api4($joinEntityName, 'delete', [
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
