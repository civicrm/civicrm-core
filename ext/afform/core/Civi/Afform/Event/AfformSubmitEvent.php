<?php
namespace Civi\Afform\Event;

use Civi\Afform\FormDataModel;
use Civi\Api4\Action\Afform\Submit;
use Civi\Api4\Utils\CoreUtil;

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

  /**
   * One or more records to be saved for this entity.
   * (because of `<af-repeat>` all entities are treated as if they may be multi)
   * @var array
   */
  public $records;

  /**
   * @var string
   *   entityType
   */
  private $entityType;

  /**
   * @var string
   *   entityName e.g. Individual1, Activity1,
   */
  private $entityName;

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
  private $entityIds;

  /**
   * AfformSubmitEvent constructor.
   *
   * @param array $afform
   * @param \Civi\Afform\FormDataModel $formDataModel
   * @param \Civi\Api4\Action\Afform\Submit $apiRequest
   * @param array $records
   * @param string $entityType
   * @param string $entityName
   * @param array $entityIds
   */
  public function __construct(array $afform, FormDataModel $formDataModel, Submit $apiRequest, &$records, string $entityType, string $entityName, array &$entityIds) {
    parent::__construct($afform, $formDataModel, $apiRequest);
    $this->records =& $records;
    $this->entityType = $entityType;
    $this->entityName = $entityName;
    $this->entityIds =& $entityIds;
  }

  /**
   * Get the entity type associated with this event
   * @return string
   */
  public function getEntityType(): string {
    return $this->entityType;
  }

  /**
   * Get the entity name associated with this event
   * @return string
   */
  public function getEntityName(): string {
    return $this->entityName;
  }

  /**
   * @return array{type: string, fields: array, joins: array, security: string, actions: array}
   */
  public function getEntity() {
    return $this->getFormDataModel()->getEntity($this->entityName);
  }

  /**
   * @return callable
   *   API4-style
   */
  public function getSecureApi4() {
    return $this->getFormDataModel()->getSecureApi4($this->entityName);
  }

  /**
   * @param int $index
   * @param int|string $entityId
   * @return $this
   */
  public function setEntityId($index, $entityId) {
    $idField = CoreUtil::getIdFieldName($this->entityName);
    $this->entityIds[$this->entityName][$index][$idField] = $entityId;
    return $this;
  }

  /**
   * Get the id of a saved record
   * @param int $index
   * @return mixed
   */
  public function getEntityId(int $index = 0) {
    $idField = CoreUtil::getIdFieldName($this->entityName);
    return $this->entityIds[$this->entityName][$index][$idField] ?? NULL;
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
   * @param int $index
   * @param string $joinEntity
   * @param array $joinIds
   * @return $this
   */
  public function setJoinIds($index, $joinEntity, $joinIds) {
    $this->entityIds[$this->entityName][$index]['joins'][$joinEntity] = $joinIds;
    return $this;
  }

}
