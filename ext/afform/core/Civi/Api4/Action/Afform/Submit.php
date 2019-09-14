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
    foreach ($this->_afformEntities as $entityName => $entity) {
      // Predetermined values override submitted values
      $entityValues[$entity['type']][$entityName] = ($entity['af-values'] ?? []) + ($this->values[$entityName] ?? []);
    }

    $event = new AfformSubmitEvent($this->_afformEntities, $entityValues);
    \Civi::dispatcher()->dispatch(self::EVENT_NAME, $event);
    foreach ($event->entityValues as $entityType => $entities) {
      if (!empty($entities)) {
        throw new \API_Exception(sprintf("Failed to process entities (type=%s; name=%s)", $entityType, implode(',', array_keys($entities))));
      }
    }

    // What should I return?
    return [];
  }

  ///**
  // * @param \Civi\Afform\Event\AfformSubmitEvent $event
  // * @see afform_civicrm_config
  // */
  //public function processContacts(AfformSubmitEvent $event) {
  //  if (empty($event->entityValues['Contact'])) {
  //    return;
  //  }
  //  foreach ($event->entityValues['Contact'] as $entityName => $contact) {
  //    // Do something
  //    unset($event->entityValues['Contact'][$entityName]);
  //  }
  //}

  /**
   * @param \Civi\Afform\Event\AfformSubmitEvent $event
   * @see afform_civicrm_config
   */
  public static function processGenericEntity(AfformSubmitEvent $event) {
    foreach ($event->entityValues as $entityType => $records) {
      civicrm_api4($entityType, 'save', [
        'records' => $records,
      ]);
      unset($event->entityValues[$entityType]);
    }
  }

}
