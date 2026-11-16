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
   * Indexes of records whose id was found by a dedupe rule rather than given by the form.
   * @var array
   */
  public $dedupeMatches = [];

  /**
   * Action taken ('create' or 'update') for each saved entity and join.
   *
   * @var array
   */
  private $entityActions;

  /**
   * AfformSubmitEvent constructor.
   *
   * @param array $afform
   * @param \Civi\Afform\FormDataModel $formDataModel
   * @param \Civi\Api4\Action\Afform\AbstractProcessor $apiRequest
   * @param array $records
   * @param string|null $entityType
   * @param string $entityName
   * @param array $entityIds
   * @param array $entityActions
   */
  public function __construct(array $afform, FormDataModel $formDataModel, AbstractProcessor $apiRequest, &$records, ?string $entityType, string $entityName, array &$entityIds, array &$entityActions = []) {
    parent::__construct($afform, $formDataModel, $apiRequest);
    $this->records =& $records;
    $this->entityType = $entityType;
    $this->entityName = $entityName;
    $this->entityIds =& $entityIds;
    $this->entityActions =& $entityActions;
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
   * Record that an entity instance has been saved, along with its ID and action taken.
   *
   * @param int $index
   *   Index of the record for which the saved values are set.
   * @param array $savedValues
   *   The saved values. (Probably the return values from the API4 Save action)
   * @param string|null $action
   *   Action taken ('create' or 'update'). Auto-detected from `$this->records` if omitted.
   * @return $this
   */
  public function setSaved(int $index, array $savedValues, ?string $action = NULL): self {
    $this->saved[$index] = $savedValues;
    $idField = $this->getIdFieldName();
    if ($action !== NULL || !isset($this->entityActions[$this->entityName][$index]['action'])) {
      $action ??= empty($this->records[$index]['fields'][$idField]) ? 'create' : 'update';
      $this->setEntityAction($index, $action);
    }
    if (isset($savedValues[$idField])) {
      $this->setEntityId($index, $savedValues[$idField]);
    }
    return $this;
  }

  /**
   * Record the action taken ('create' or 'update') on an entity instance.
   *
   * @param int $index
   * @param string $action
   * @return $this
   */
  public function setEntityAction(int $index, string $action): self {
    $this->entityActions[$this->entityName][$index]['action'] = $action;
    return $this;
  }

  /**
   * Record the action taken ('create' or 'update') on join entity instances.
   *
   * @param int $index
   * @param string $joinEntity
   * @param array $joinActions
   * @return $this
   */
  public function setJoinActions(int $index, string $joinEntity, array $joinActions): self {
    $this->entityActions[$this->entityName][$index]['joins'][$joinEntity] = $joinActions;
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
