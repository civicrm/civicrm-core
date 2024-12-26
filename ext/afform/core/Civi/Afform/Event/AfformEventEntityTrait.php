<?php

namespace Civi\Afform\Event;

use Civi\Api4\Utils\CoreUtil;

trait AfformEventEntityTrait {

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
    $this->records[$index]['fields'][$idField] = $entityId;
    return $this;
  }

  /**
   * Get the id of an instance of the current entity
   * @param int $index
   * @return mixed
   */
  public function getEntityId(int $index = 0) {
    $apiEntity = $this->getFormDataModel()->getEntity($this->entityName)['type'];
    $idField = CoreUtil::getIdFieldName($apiEntity);
    return $this->entityIds[$this->entityName][$index][$idField] ?? NULL;
  }

  /**
   * Get the id(s) of an entity
   *
   * @param string|null $entityName
   * @return array
   */
  public function getEntityIds(?string $entityName = NULL): array {
    $entityName = $entityName ?: $this->entityName;
    $apiEntity = $this->getFormDataModel()->getEntity($entityName)['type'];
    $idField = CoreUtil::getIdFieldName($apiEntity);
    return array_column($this->entityIds[$entityName] ?? [], $idField);
  }

  /**
   * @param int $index
   * @param string $joinEntity
   * @param array $joinIds
   * @return $this
   */
  public function setJoinIds($index, $joinEntity, $joinIds) {
    $idField = CoreUtil::getIdFieldName($joinEntity);
    $this->entityIds[$this->entityName][$index]['joins'][$joinEntity] = \CRM_Utils_Array::filterColumns($joinIds, [$idField]);
    return $this;
  }

}
