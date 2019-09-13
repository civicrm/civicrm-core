<?php

namespace Civi\Api4\Action\Afform;

/**
 * Class Submit
 * @package Civi\Api4\Action\Afform
 */
class Submit extends AbstractProcessor {

  /**
   * Submitted values
   * @var array
   * @required
   */
  protected $values;

  /**
   * @var array
   */
  protected $_submission = [];

  protected function processForm() {
    foreach ($this->_afformEntities as $entityName => $entity) {
      // Predetermined values override submitted values
      $this->_submission[$entity['type']][$entityName] = ($entity['af-values'] ?? []) + ($this->values[$entityName] ?? []);
    }
    // Determines the order in which to process entities. Contacts go first.
    $entitiesToProcess = [
      'Contact' => 'processContacts',
      'Activity' => 'processActivities',
    ];
    foreach ($entitiesToProcess as $entityType => $callback) {
      if (!empty($this->_submission[$entityType])) {
        $this->$callback($this->_submission[$entityType]);
      }
    }
    foreach (array_diff_key($this->_submission, $entitiesToProcess) as $entityType) {

    }
  }

  protected function processGenericEntity($entityType, $items) {
    foreach ($items as $name => $item) {
      civicrm_api4($entityType, 'save', $items);
    }
  }

  protected function processContacts($contacts) {
    foreach ($contacts as $name => $contact) {

    }
  }

}
