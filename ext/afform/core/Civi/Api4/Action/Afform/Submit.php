<?php

namespace Civi\Api4\Action\Afform;

use Civi\Afform\Event\AfformSubmitEvent;
use Civi\API\Exception\NotImplementedException;

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
      // Predetermined values override submitted values
      $entityValues[$entity['type']][$entityName] = ($entity['af-values'] ?? []) + ($this->values[$entityName] ?? []);
    }

    $event = new AfformSubmitEvent($this->_formDataModel->getEntities(), $entityValues);
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
  public function processContacts(AfformSubmitEvent $event) {
    foreach ($event->entityValues['Contact'] ?? [] as $entityName => $contact) {
      $blocks = $contact['blocks'] ?? [];
      unset($contact['blocks']);
      $saved = civicrm_api4('Contact', 'save', ['records' => [$contact]])->first();
      foreach ($blocks as $entity => $block) {
        $values = self::filterEmptyBlocks($entity, $block);
        // FIXME: Replace/delete should only be done to known contacts
        if ($values) {
          civicrm_api4($entity, 'replace', [
            'where' => [['contact_id', '=', $saved['id']]],
            'records' => $values,
          ]);
        }
        else {
          try {
            civicrm_api4($entity, 'delete', [
              'where' => [['contact_id', '=', $saved['id']]],
            ]);
          } catch (\API_Exception $e) {
            // No records to delete
          }
        }
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
    foreach ($event->entityValues as $entityType => $records) {
      civicrm_api4($entityType, 'save', [
        'records' => $records,
      ]);
      unset($event->entityValues[$entityType]);
    }
  }

  /**
   * Filter out blocks that have been left blank on the form
   *
   * @param $entity
   * @param $block
   * @return array
   */
  private static function filterEmptyBlocks($entity, $block) {
    return array_filter($block, function($item) use($entity) {
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
