<?php
namespace Civi\Afform\Event;

use Civi\Afform\FormDataModel;
use Civi\Api4\Action\Afform\AbstractProcessor;

/**
 * Handle submission of an "<af-form>" entity (or set of entities in the case of `<af-repeat>`).
 *
 * @see Submit::processGenericEntity
 * which is the default handler of this event, with priority 0.
 *
 * If special processing for an entity type is desired, add a new listener with a higher priority
 * than 0, and do one of two things:
 *
 * 1. Fully process the save, and call `$event->stopPropagation()` to skip `processGenericEntity`.
 * 2. Manipulate the $records and allow `processGenericEntity` to perform the save.
 *    Setting $record['fields'] = NULL will prevent saving a record, e.g. if the record is not valid.
 *
 * @package Civi\Afform\Event
 */
class AfformSubmitEvent extends AfformBaseEvent {
  use AfformEventEntityTrait;

  /**
   * One or more records to be saved for this entity.
   * (because of `<af-repeat>` all entities are treated as if they may be multi)
   * @var array
   */
  public $records;

  public $saved = [];

  /**
   * AfformSubmitEvent constructor.
   *
   * @param array $afform
   * @param \Civi\Afform\FormDataModel $formDataModel
   * @param \Civi\Api4\Action\Afform\AbstractProcessor $apiRequest
   * @param array $records
   * @param string $entityType
   * @param string $entityName
   * @param array $entityIds
   */
  public function __construct(array $afform, FormDataModel $formDataModel, AbstractProcessor $apiRequest, &$records, string $entityType, string $entityName, array &$entityIds) {
    parent::__construct($afform, $formDataModel, $apiRequest);
    $this->records =& $records;
    $this->entityType = $entityType;
    $this->entityName = $entityName;
    $this->entityIds =& $entityIds;
  }

  /**
   * Get records to be saved
   * @return array
   */
  public function getRecords(): array {
    return $this->records;
  }

  /**
   * @param array $records
   * @return $this
   */
  public function setRecords(array $records) {
    $this->records = $records;
    return $this;
  }

  /**
   * Returns the array with the saved values.
   *
   * @return array
   */
  public function getSaved(): array {
    return $this->saved;
  }

  /**
   * Set the saved values
   *
   * @param int $index
   *   Index of the record for which the saved values are set.
   * @param array $savedValues
   *   The saved values. (Probably the return values from the API4 Save action)
   * @return $this
   */
  public function setSaved(int $index, array $savedValues) {
    $this->saved[$index] = $savedValues;
    return $this;
  }

  /**
   * Get submitted values for all entities on the form
   * @return array
   */
  public function getSubmittedValues() {
    return $this->getApiRequest()->getSubmittedValues();
  }

}
