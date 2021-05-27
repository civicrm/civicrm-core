<?php
namespace Civi\Afform\Event;

use Civi\Afform\FormDataModel;
use Civi\Api4\Action\Afform\Submit;

/**
 * Handle submission of an "<af-form>" entity (or set of entities in the case of `<af-repeat>`).
 *
 * @see Submit::processGenericEntity
 * which is the default handler of this event, with priority 0.
 *
 * If special processing for an entity type is desired, add a new listener with a higher priority
 * than 0, and do one of two things:
 *
 * 1. Fully process the save, and cancel event propagation to bypass `processGenericEntity`.
 * 2. Manipulate the $records and allow the default listener to perform the save.
 *    Setting $record['fields'] = NULL will cancel saving a record, e.g. if the record is not valid.
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
   * @return callable
   *   API4-style
   */
  public function getSecureApi4() {
    return $this->getFormDataModel()->getSecureApi4($this->entityName);
  }

  /**
   * @param $index
   * @param $entityId
   * @return $this
   */
  public function setEntityId($index, $entityId) {
    $this->entityIds[$this->entityName][$index]['id'] = $entityId;
    return $this;
  }

}
